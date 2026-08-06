<?php

namespace App\Http\Controllers\Forms\AI;

use App\Http\Controllers\Controller;
use App\Models\Forms\AI\FormAiPdfGeneration;
use App\Models\Forms\Form;
use App\Service\AI\AiPdfContextBuilder;
use App\Service\Storage\SafeFileResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Public endpoints a respondent uses to get their PDF.
 *
 * The request only ever names the block. The prompt, the knowledge sources, the
 * file name template and the set of answers the block may read all come from
 * the stored form, so a crafted payload cannot redirect the model or reach
 * answers the block was not configured to see.
 */
class AiPdfGenerationController extends Controller
{
    public function generate(Request $request, Form $form)
    {
        $this->authorize('answer', $form);

        $validated = $request->validate([
            'block_id' => ['required', 'uuid'],
            'answers' => ['nullable', 'array'],
        ]);

        $block = $this->resolveBlock($form, $validated['block_id']);
        if (!$block) {
            return $this->error(['message' => 'This block no longer exists on the form.'], 404);
        }

        if (trim((string) ($block['ai_pdf_prompt'] ?? '')) === '') {
            return $this->error(['message' => 'This block has not been configured yet.'], 422);
        }

        $allowedFieldIds = AiPdfContextBuilder::allowedSourceFieldIds($form, $block);
        $answers = $this->sanitizeAnswers($request->input('answers', []), $allowedFieldIds);

        $token = Str::random(64);

        $generation = FormAiPdfGeneration::create([
            'form_id' => $form->id,
            'block_id' => $block['id'],
            'token' => $token,
            'answers' => $answers,
            'ip' => $request->ip(),
        ]);

        return $this->success([
            'message' => 'We are writing your document.',
            'generation' => [
                'id' => $generation->id,
                'status' => $generation->status,
            ],
            // Returned once, at creation. Polling and downloading both need it.
            'token' => $token,
        ]);
    }

    public function show(Request $request, Form $form, FormAiPdfGeneration $generation)
    {
        if ($generation->form_id !== $form->id) {
            return $this->error(['message' => 'Generation not found.'], 404);
        }

        if (!$generation->tokenMatches($this->tokenFrom($request))) {
            return $this->error(['message' => 'Generation not found.'], 404);
        }

        // No `error` here on purpose: it carries the underlying failure message
        // for the form owner, and this endpoint answers anonymous respondents.
        $payload = [
            'id' => $generation->id,
            'status' => $generation->status,
        ];

        if ($generation->isReady()) {
            $payload['file_name'] = $generation->file_name;
            $payload['file_reference'] = $generation->file_reference;
            $payload['size_bytes'] = $generation->size_bytes;
            $payload['download_url'] = URL::temporarySignedRoute(
                'forms.ai-pdf.download',
                now()->addMinutes((int) config('ai_pdf.download_url_ttl')),
                ['generation' => $generation->id]
            );
        }

        return $this->success(['generation' => $payload]);
    }

    /**
     * The signature is the authorisation here: this URL is only ever handed to
     * a caller that already proved it holds the generation token.
     */
    public function download(FormAiPdfGeneration $generation, SafeFileResponseService $files)
    {
        if (!$generation->isReady() || !$generation->fileStillExists()) {
            return $this->error(['message' => 'File not found.'], 404);
        }

        return $files->serve($generation->storagePath(), $generation->file_name ?: 'document.pdf');
    }

    private function resolveBlock(Form $form, string $blockId): ?array
    {
        foreach ($form->properties ?? [] as $property) {
            if (($property['type'] ?? null) === 'ai_pdf' && ($property['id'] ?? null) === $blockId) {
                return $property;
            }
        }

        return null;
    }

    private function tokenFrom(Request $request): ?string
    {
        $token = $request->header('X-Ai-Pdf-Token') ?: $request->query('token');

        return is_string($token) ? $token : null;
    }

    /**
     * Keeps only the fields the block is allowed to read, and only values that
     * are plain text — nested structures never reach the prompt.
     *
     * @param array<int, string> $allowedFieldIds
     * @return array<string, mixed>
     */
    private function sanitizeAnswers(mixed $answers, array $allowedFieldIds): array
    {
        if (!is_array($answers) || $allowedFieldIds === []) {
            return [];
        }

        $maxLength = (int) config('ai_pdf.context.max_answer_characters');
        $clean = [];

        foreach ($allowedFieldIds as $fieldId) {
            if (!array_key_exists($fieldId, $answers)) {
                continue;
            }

            $value = $answers[$fieldId];

            if (is_bool($value) || is_int($value) || is_float($value)) {
                $clean[$fieldId] = $value;
                continue;
            }

            if (is_string($value)) {
                $clean[$fieldId] = Str::limit($value, $maxLength, '');
                continue;
            }

            if (is_array($value)) {
                $items = [];
                foreach (array_slice($value, 0, 100) as $item) {
                    if (is_scalar($item)) {
                        $items[] = Str::limit((string) $item, $maxLength, '');
                    }
                }
                $clean[$fieldId] = $items;
            }
        }

        return $clean;
    }
}
