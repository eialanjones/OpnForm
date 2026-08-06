<?php

namespace App\Rules;

use App\Models\Forms\AI\FormAiPdfGeneration;
use App\Models\Forms\Form;
use App\Service\Storage\FileUploadPathService;
use App\Service\Storage\StorageFileNameParser;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Storage;

/**
 * An AI PDF field holds a reference to a file the server itself produced.
 *
 * Without this rule a respondent could submit any temporary file reference and
 * have StoreFormSubmissionJob move a stranger's upload into their submission.
 */
class ValidAiPdfReference implements ValidationRule
{
    public function __construct(
        private readonly Form $form,
        private readonly string $blockId,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (!is_string($value)) {
            $fail('The generated document is invalid.');

            return;
        }

        if ($this->matchesGeneration($value) || $this->alreadyStoredWithSubmission($value)) {
            return;
        }

        $fail('The generated document is invalid.');
    }

    private function matchesGeneration(string $value): bool
    {
        return FormAiPdfGeneration::query()
            ->where('form_id', $this->form->id)
            ->where('block_id', $this->blockId)
            ->where('status', FormAiPdfGeneration::STATUS_COMPLETED)
            ->where('file_reference', $value)
            ->exists();
    }

    /**
     * Editing an existing submission resubmits the stored file name, which no
     * longer matches the original reference once it has been moved.
     */
    private function alreadyStoredWithSubmission(string $value): bool
    {
        $storedName = StorageFileNameParser::parse($value)->getMovedFileName();

        if (!$storedName) {
            return false;
        }

        return Storage::exists(FileUploadPathService::getFileUploadPath($this->form->id, $storedName));
    }
}
