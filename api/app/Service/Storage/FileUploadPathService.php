<?php

namespace App\Service\Storage;

use Illuminate\Support\Str;

/**
 * Service for managing file upload paths in the application
 *
 * This service centralizes all path generation for file uploads, ensuring
 * consistent path structures throughout the application.
 */
class FileUploadPathService
{
    /**
     * Base path for form submission file uploads
     */
    private const FILE_UPLOAD_PATH = 'forms/?/submissions';

    /**
     * Base path for temporary file uploads
     */
    private const TMP_FILE_UPLOAD_PATH = 'tmp/';

    /**
     * Base path for AI PDF knowledge sources and structure templates
     */
    private const AI_DOCUMENT_PATH = 'forms/ai-documents';

    /**
     * Generate the file upload path for a specific form
     *
     * @param int|string $formId The form ID
     * @param string|null $fileName Optional filename to append
     * @return string The complete file upload path
     *
     * @example FileUploadPathService::getFileUploadPath(123) // returns "forms/123/submissions"
     * @example FileUploadPathService::getFileUploadPath(123, "document.pdf") // returns "forms/123/submissions/document.pdf"
     */
    public static function getFileUploadPath(int|string $formId, ?string $fileName = null): string
    {
        // Validate form ID to prevent path traversal attacks
        self::validatePathComponent($formId);

        $path = Str::of(self::FILE_UPLOAD_PATH)->replace('?', $formId);

        if ($fileName) {
            // Validate filename
            self::validatePathComponent($fileName);

            // Ensure consistent directory separator
            $path = Str::finish($path, '/') . $fileName;
        }

        return $path;
    }

    /**
     * Generate the temporary file upload path
     *
     * @param string|null $fileName Optional filename to append
     * @return string The complete temporary file upload path
     *
     * @example FileUploadPathService::getTmpFileUploadPath() // returns "tmp/"
     * @example FileUploadPathService::getTmpFileUploadPath("abc123") // returns "tmp/abc123"
     */
    public static function getTmpFileUploadPath(?string $fileName = null): string
    {
        $path = self::TMP_FILE_UPLOAD_PATH;

        if ($fileName) {
            // Validate filename
            self::validatePathComponent($fileName);

            // Ensure consistent directory separator
            $path = Str::finish($path, '/') . $fileName;
        }

        return $path;
    }

    /**
     * Generate the storage path for an AI PDF knowledge source or template
     *
     * Keyed by workspace first: block ids are client generated and visible in
     * the public form payload, so they alone must never decide where a file
     * lands or which tenant can reach it.
     *
     * @param int|string $workspaceId The workspace owning the document
     * @param string $blockId The UUID of the AI PDF block owning the document
     * @param string|null $fileName Optional filename to append
     * @return string The complete AI document path
     *
     * @example FileUploadPathService::getAiDocumentPath(12, '9f1c…') // returns "forms/ai-documents/12/9f1c…"
     */
    public static function getAiDocumentPath(int|string $workspaceId, string $blockId, ?string $fileName = null): string
    {
        self::validatePathComponent($workspaceId);
        self::validatePathComponent($blockId);

        $path = self::AI_DOCUMENT_PATH . '/' . $workspaceId . '/' . $blockId;

        if ($fileName) {
            self::validatePathComponent($fileName);

            $path = Str::finish($path, '/') . $fileName;
        }

        return $path;
    }

    /**
     * Validates a path component to prevent path traversal attacks
     *
     * @param string|int $component The path component to validate
     * @throws \InvalidArgumentException If the path component contains invalid characters
     */
    private static function validatePathComponent(string|int $component): void
    {
        $component = (string) $component;

        // Check for path traversal attempts or problematic characters
        if (
            str_contains($component, '/') ||
            str_contains($component, '\\')
        ) {
            throw new \InvalidArgumentException('Path component contains invalid characters');
        }
    }
}
