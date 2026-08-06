<?php

namespace App\Service\AI;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;

/**
 * Turns an uploaded knowledge source into plain text for the prompt.
 *
 * Every format is capped at the same character budget, so a 100MB spreadsheet
 * and a two page note cost the same worst case memory once extracted.
 */
class DocumentTextExtractor
{
    public const UNSUPPORTED = 'unsupported';
    public const EMPTY_RESULT = 'empty';

    /**
     * @param string $localPath Absolute path to a readable local file
     * @param string $originalName Used to resolve the extension
     * @param int $maxCharacters Hard ceiling on the returned string
     *
     * @throws DocumentTextExtractionException
     */
    public function extract(string $localPath, string $originalName, int $maxCharacters): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        $text = match ($extension) {
            'pdf' => $this->fromPdf($localPath, $maxCharacters),
            'xlsx', 'xls' => $this->fromSpreadsheet($localPath, $maxCharacters),
            'csv' => $this->fromDelimitedText($localPath, $maxCharacters),
            'txt', 'md' => $this->fromPlainText($localPath, $maxCharacters),
            default => throw new DocumentTextExtractionException(self::UNSUPPORTED),
        };

        $text = trim($this->collapseWhitespace($text));

        if ($text === '') {
            throw new DocumentTextExtractionException(self::EMPTY_RESULT);
        }

        return $this->cap($text, $maxCharacters);
    }

    private function fromPdf(string $localPath, int $maxCharacters): string
    {
        $parser = new PdfParser();
        $document = $parser->parseFile($localPath);

        $text = $document->getText();

        return $this->cap($text, $maxCharacters);
    }

    private function fromSpreadsheet(string $localPath, int $maxCharacters): string
    {
        $reader = IOFactory::createReaderForFile($localPath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($localPath);

        $chunks = [];
        $length = 0;

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $chunks[] = '## ' . $sheet->getTitle();

            foreach ($sheet->toArray(null, true, false, false) as $row) {
                $cells = array_filter(
                    array_map(fn ($cell) => is_scalar($cell) ? trim((string) $cell) : '', $row),
                    fn ($cell) => $cell !== ''
                );

                if ($cells === []) {
                    continue;
                }

                $line = implode(' | ', $cells);
                $chunks[] = $line;
                $length += mb_strlen($line) + 1;

                if ($length >= $maxCharacters) {
                    break 2;
                }
            }
        }

        $spreadsheet->disconnectWorksheets();

        return implode("\n", $chunks);
    }

    private function fromDelimitedText(string $localPath, int $maxCharacters): string
    {
        $handle = fopen($localPath, 'rb');
        if ($handle === false) {
            throw new DocumentTextExtractionException(self::EMPTY_RESULT);
        }

        $lines = [];
        $length = 0;

        try {
            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                $cells = array_filter(
                    array_map(fn ($cell) => is_string($cell) ? trim($cell) : '', $row),
                    fn ($cell) => $cell !== ''
                );

                if ($cells === []) {
                    continue;
                }

                $line = implode(' | ', $cells);
                $lines[] = $line;
                $length += mb_strlen($line) + 1;

                if ($length >= $maxCharacters) {
                    break;
                }
            }
        } finally {
            fclose($handle);
        }

        return implode("\n", $lines);
    }

    private function fromPlainText(string $localPath, int $maxCharacters): string
    {
        $handle = fopen($localPath, 'rb');
        if ($handle === false) {
            throw new DocumentTextExtractionException(self::EMPTY_RESULT);
        }

        try {
            // Read bytes rather than characters, then cap properly once decoded.
            $raw = (string) fread($handle, $maxCharacters * 4);
        } finally {
            fclose($handle);
        }

        if (!mb_check_encoding($raw, 'UTF-8')) {
            $raw = mb_convert_encoding($raw, 'UTF-8', 'UTF-8');
        }

        return $raw;
    }

    private function collapseWhitespace(string $text): string
    {
        // PDF extraction in particular produces long runs of spaces and blank
        // lines that would otherwise eat into the prompt budget.
        $text = preg_replace('/[ \t\x{00A0}]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/(\R){3,}/u', "\n\n", $text) ?? $text;

        return $text;
    }

    private function cap(string $text, int $maxCharacters): string
    {
        if ($maxCharacters > 0 && mb_strlen($text) > $maxCharacters) {
            return mb_substr($text, 0, $maxCharacters);
        }

        return $text;
    }
}
