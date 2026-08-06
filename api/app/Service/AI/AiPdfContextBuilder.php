<?php

namespace App\Service\AI;

use App\Models\Forms\AI\FormAiDocument;
use App\Models\Forms\Form;
use App\Open\MentionParser;
use Illuminate\Support\Str;

/**
 * Assembles everything the PDF prompt needs from a form, one of its AI PDF
 * blocks and the answers a respondent gave.
 *
 * Every section is capped so that a large knowledge base cannot silently turn
 * each submission into an oversized request.
 */
class AiPdfContextBuilder
{
    /**
     * The set of fields the block is allowed to read.
     *
     * Read from the stored form, never from the request, so a crafted payload
     * cannot widen the block's reach into other answers.
     *
     * @return array<int, string>
     */
    public static function allowedSourceFieldIds(Form $form, array $block): array
    {
        $properties = collect($form->properties)->values();

        $blockIndex = $properties->search(fn ($property) => ($property['id'] ?? null) === ($block['id'] ?? null));
        if ($blockIndex === false) {
            return [];
        }

        // Only answers collected before this block exist by the time it runs.
        $precedingFieldIds = $properties
            ->take($blockIndex)
            ->filter(fn ($property) => self::isReadableSource($property))
            ->pluck('id')
            ->filter()
            ->values();

        $configured = $block['ai_pdf_source_fields'] ?? null;

        // Never configured means "every answer before this block". An explicit
        // list — including an empty one — is taken at face value, so the editor
        // can express "use none of them".
        if (!is_array($configured)) {
            return $precedingFieldIds->all();
        }

        return $precedingFieldIds
            ->intersect(array_filter($configured, 'is_string'))
            ->values()
            ->all();
    }

    private static function isReadableSource(array $property): bool
    {
        $type = $property['type'] ?? null;

        if (!$type || str_starts_with($type, 'nf-')) {
            return false;
        }

        // Another PDF block holds a file reference, and payment blocks hold a
        // transaction id — neither is answer text worth feeding to the model.
        return $type !== 'ai_pdf' && $type !== 'payment';
    }

    /**
     * @param array<string, mixed> $answers Already restricted to allowed fields
     * @return array{answersBlock: string, knowledgeBlock: string, structureBlock: string}
     */
    public function build(Form $form, array $block, array $answers): array
    {
        return [
            'answersBlock' => $this->buildAnswers($form, $answers),
            'knowledgeBlock' => $this->buildKnowledge($form, $block),
            'structureBlock' => $this->buildStructure($form, $block),
        ];
    }

    private function buildAnswers(Form $form, array $answers): string
    {
        $maxAnswerLength = (int) config('ai_pdf.context.max_answer_characters');
        $isPortuguese = str_starts_with(strtolower((string) ($form->language ?? 'pt')), 'pt');

        $lines = [];

        foreach ($form->properties as $property) {
            $id = $property['id'] ?? null;
            if (!$id || !array_key_exists($id, $answers)) {
                continue;
            }

            $value = $this->formatValue($answers[$id], $isPortuguese);
            if ($value === '') {
                continue;
            }

            $label = trim((string) ($property['name'] ?? $id));
            $lines[] = '- ' . $label . ': ' . Str::limit($value, $maxAnswerLength, '…');
        }

        return $lines === []
            ? '(sem respostas preenchidas)'
            : implode("\n", $lines);
    }

    private function formatValue(mixed $value, bool $isPortuguese): string
    {
        if (is_bool($value)) {
            return $isPortuguese ? ($value ? 'Sim' : 'Não') : ($value ? 'Yes' : 'No');
        }

        if (is_array($value)) {
            $parts = array_filter(
                array_map(fn ($item) => is_scalar($item) ? trim((string) $item) : '', $value),
                fn ($item) => $item !== ''
            );

            return implode(', ', $parts);
        }

        if ($value === null || is_object($value)) {
            return '';
        }

        return trim((string) $value);
    }

    private function buildKnowledge(Form $form, array $block): string
    {
        $budget = (int) config('ai_pdf.context.max_knowledge_characters');

        // Scoped to the form's own workspace: block ids are public, so reading
        // by block id alone would let an outsider plant a knowledge source that
        // this form then treats as trusted reference material.
        $documents = FormAiDocument::query()
            ->forBlock($form->workspace_id, $block['id'])
            ->where('role', FormAiDocument::ROLE_KNOWLEDGE)
            ->where('status', FormAiDocument::STATUS_COMPLETED)
            ->orderBy('id')
            ->get(['id', 'workspace_id', 'block_id', 'original_name', 'extracted_text']);

        if ($documents->isEmpty()) {
            return '(nenhuma base de conhecimento anexada)';
        }

        $sections = [];
        $used = 0;

        foreach ($documents as $document) {
            if ($used >= $budget) {
                break;
            }

            $text = (string) $document->extracted_text;
            if (trim($text) === '') {
                continue;
            }

            $remaining = $budget - $used;
            if (mb_strlen($text) > $remaining) {
                $text = mb_substr($text, 0, $remaining);
            }

            $sections[] = '### ' . $document->original_name . "\n" . $text;
            $used += mb_strlen($text);
        }

        return $sections === []
            ? '(nenhuma base de conhecimento anexada)'
            : implode("\n\n", $sections);
    }

    private function buildStructure(Form $form, array $block): string
    {
        $document = FormAiDocument::query()
            ->forBlock($form->workspace_id, $block['id'])
            ->where('role', FormAiDocument::ROLE_TEMPLATE)
            ->where('status', FormAiDocument::STATUS_COMPLETED)
            ->orderByDesc('id')
            ->first(['id', 'workspace_id', 'block_id', 'original_name', 'extracted_text']);

        if (!$document || trim((string) $document->extracted_text) === '') {
            return '(nenhum modelo de estrutura anexado)';
        }

        return mb_substr(
            (string) $document->extracted_text,
            0,
            (int) config('ai_pdf.context.max_template_characters')
        );
    }

    /**
     * Resolves the owner's file name template against the answers.
     *
     * Returns the human facing name including the .pdf extension.
     */
    public function resolveFileName(Form $form, array $block, array $answers): string
    {
        $template = trim((string) ($block['ai_pdf_file_name'] ?? ''));

        $resolved = '';
        if ($template !== '') {
            $mentionData = collect($form->properties)
                ->map(fn ($property) => [
                    'id' => $property['id'] ?? null,
                    'value' => $answers[$property['id'] ?? ''] ?? null,
                ])
                ->filter(fn ($entry) => $entry['id'] !== null)
                ->values()
                ->all();

            $resolved = strip_tags((new MentionParser($template, $mentionData))->parse());
        }

        // Answers feed this name and it ends up in a Content-Disposition
        // header, so quotes, backslashes and control characters go first.
        $resolved = preg_replace('/["\\\\\p{C}]+/u', '', $resolved) ?? $resolved;
        $resolved = trim(preg_replace('/\s+/u', ' ', $resolved) ?? '');

        if ($resolved === '') {
            $resolved = trim((string) ($block['name'] ?? '')) ?: (string) $form->title;
        }

        $resolved = Str::limit($resolved, 120, '');
        $resolved = preg_replace('/\.pdf$/i', '', $resolved) ?? $resolved;

        return trim($resolved) . '.pdf';
    }
}
