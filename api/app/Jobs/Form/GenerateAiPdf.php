<?php

namespace App\Jobs\Form;

use App\Models\Forms\AI\FormAiPdfGeneration;
use App\Service\AI\AiPdfContextBuilder;
use App\Service\AI\AiPdfRenderer;
use App\Service\AI\Prompts\Form\GenerateAiPdfPrompt;
use App\Service\Storage\FileUploadPathService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Writes the respondent's PDF and parks it in tmp storage.
 *
 * The block configuration — prompt, source fields, file name — is read from the
 * stored form rather than from the request that triggered the generation, so a
 * crafted payload can only choose *which* block runs, never what it does.
 */
class GenerateAiPdf implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public FormAiPdfGeneration $generation)
    {
    }

    public function handle(AiPdfContextBuilder $contextBuilder, AiPdfRenderer $renderer): void
    {
        $this->generation->update(['status' => FormAiPdfGeneration::STATUS_PROCESSING]);

        try {
            $form = $this->generation->form;
            if (!$form) {
                $this->markFailed('form_missing');

                return;
            }

            $block = $this->resolveBlock($form->properties ?? []);
            if (!$block) {
                $this->markFailed('block_missing');

                return;
            }

            $instructions = trim((string) ($block['ai_pdf_prompt'] ?? ''));
            if ($instructions === '') {
                $this->markFailed('prompt_missing');

                return;
            }

            $answers = $this->generation->answers ?? [];
            $context = $contextBuilder->build($form, $block, $answers);

            $bodyHtml = GenerateAiPdfPrompt::run(
                $instructions,
                (string) $form->title,
                $this->languageLabel($form->language),
                $context['answersBlock'],
                $context['knowledgeBlock'],
                $context['structureBlock'],
            );

            if (!is_string($bodyHtml) || trim($bodyHtml) === '') {
                $this->markFailed('empty_response');

                return;
            }

            $displayName = $contextBuilder->resolveFileName($form, $block, $answers);

            $pdfBytes = $renderer->render(
                $bodyHtml,
                preg_replace('/\.pdf$/i', '', $displayName) ?: (string) $form->title,
                (bool) ($form->layout_rtl ?? false)
            );

            $this->storeResult($pdfBytes, $displayName);
        } catch (\Throwable $exception) {
            Log::error('AI PDF generation failed', [
                'generation_id' => $this->generation->id,
                'form_id' => $this->generation->form_id,
                'exception' => $exception->getMessage(),
            ]);

            // Kept on the record as well as in the log: the log channel is not
            // always reachable on a hosted worker, and without this the only
            // trace of a failure is an unqualified "generation_failed".
            $this->markFailed('generation_failed', sprintf(
                '%s: %s',
                class_basename($exception),
                $exception->getMessage()
            ));
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->markFailed('generation_failed');
    }

    private function resolveBlock(array $properties): ?array
    {
        foreach ($properties as $property) {
            if (
                ($property['type'] ?? null) === 'ai_pdf'
                && ($property['id'] ?? null) === $this->generation->block_id
            ) {
                return $property;
            }
        }

        return null;
    }

    /**
     * Stores the PDF under the temporary path convention that
     * StoreFormSubmissionJob understands, so submitting the form moves the file
     * next to the submission with no extra handling.
     */
    private function storeResult(string $pdfBytes, string $displayName): void
    {
        $uuid = (string) Str::uuid();
        $slug = $this->referenceSlug($displayName);

        Storage::put(FileUploadPathService::getTmpFileUploadPath($uuid), $pdfBytes);

        $this->generation->update([
            'status' => FormAiPdfGeneration::STATUS_COMPLETED,
            'file_name' => $displayName,
            'file_reference' => $slug . '_' . $uuid . '.pdf',
            'storage_uuid' => $uuid,
            'size_bytes' => strlen($pdfBytes),
            'error' => null,
            'completed_at' => now(),
        ]);
    }

    /**
     * StorageFileNameParser splits on the last underscore, so the readable part
     * of the reference must not contain one.
     */
    private function referenceSlug(string $displayName): string
    {
        $base = preg_replace('/\.pdf$/i', '', $displayName) ?? $displayName;
        $slug = str_replace('_', '-', Str::slug($base));

        return $slug !== '' ? Str::limit($slug, 50, '') : 'documento';
    }

    private function languageLabel(?string $language): string
    {
        return match (strtolower((string) ($language ?: 'pt'))) {
            'pt', 'pt-br', 'pt_br' => 'Português do Brasil',
            'es' => 'Español',
            'fr' => 'Français',
            'de' => 'Deutsch',
            default => 'English',
        };
    }

    /**
     * `error` is owner-facing only — the public status endpoint never returns
     * it — so it can safely carry the underlying message.
     */
    private function markFailed(string $reason, ?string $detail = null): void
    {
        $this->generation->update([
            'status' => FormAiPdfGeneration::STATUS_FAILED,
            'error' => $detail ? Str::limit($reason . ' — ' . $detail, 500) : $reason,
        ]);
    }
}
