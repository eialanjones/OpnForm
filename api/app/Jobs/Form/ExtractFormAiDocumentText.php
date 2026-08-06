<?php

namespace App\Jobs\Form;

use App\Models\Forms\AI\FormAiDocument;
use App\Service\AI\DocumentTextExtractionException;
use App\Service\AI\DocumentTextExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Reads an uploaded knowledge source once and stores its text on the document,
 * so answering the form never re-parses the file.
 */
class ExtractFormAiDocumentText implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public FormAiDocument $document)
    {
    }

    public function handle(DocumentTextExtractor $extractor): void
    {
        // Parsing a large PDF holds the whole document in memory. This runs on
        // a worker, where a generous ceiling is cheaper than a failed upload.
        @ini_set('memory_limit', '1G');

        $this->document->update(['status' => FormAiDocument::STATUS_PROCESSING]);

        $localPath = null;

        try {
            $localPath = $this->copyToLocalTempFile();

            $text = $extractor->extract(
                $localPath,
                $this->document->original_name,
                (int) config('ai_pdf.documents.max_extracted_characters')
            );

            $this->document->update([
                'status' => FormAiDocument::STATUS_COMPLETED,
                'extracted_text' => $text,
                'extracted_characters' => mb_strlen($text),
                'error' => null,
            ]);
        } catch (DocumentTextExtractionException $exception) {
            $this->markFailed($exception->reason);
        } catch (\Throwable $exception) {
            Log::warning('AI PDF document extraction failed', [
                'document_id' => $this->document->id,
                'exception' => $exception->getMessage(),
            ]);
            $this->markFailed('extraction_failed');
        } finally {
            if ($localPath && file_exists($localPath)) {
                @unlink($localPath);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->markFailed('extraction_failed');
    }

    /**
     * The configured disk can be S3, and every parser needs a seekable local
     * file, so stream the object down before touching it.
     */
    private function copyToLocalTempFile(): string
    {
        $storagePath = $this->document->storagePath();

        if (!Storage::exists($storagePath)) {
            throw new DocumentTextExtractionException('file_missing');
        }

        $localPath = tempnam(sys_get_temp_dir(), 'ai-doc-');
        if ($localPath === false) {
            throw new \RuntimeException('Unable to allocate a temporary file.');
        }

        $source = Storage::readStream($storagePath);
        if ($source === null || $source === false) {
            throw new DocumentTextExtractionException('file_missing');
        }

        $target = fopen($localPath, 'wb');
        if ($target === false) {
            fclose($source);
            throw new \RuntimeException('Unable to open the temporary file for writing.');
        }

        try {
            stream_copy_to_stream($source, $target);
        } finally {
            fclose($source);
            fclose($target);
        }

        return $localPath;
    }

    private function markFailed(string $reason): void
    {
        $this->document->update([
            'status' => FormAiDocument::STATUS_FAILED,
            'error' => $reason,
        ]);
    }
}
