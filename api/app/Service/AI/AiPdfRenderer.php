<?php

namespace App\Service\AI;

use Barryvdh\DomPDF\Facade\Pdf;
use Stevebauman\Purify\Facades\Purify;

/**
 * Takes model written HTML and produces PDF bytes.
 *
 * Two independent guards sit between the model and the renderer: an HTML
 * allowlist that drops every tag and attribute capable of referencing a
 * resource, and a dompdf configuration with remote fetching, PHP evaluation
 * and inline scripting all switched off.
 */
class AiPdfRenderer
{
    public function render(string $bodyHtml, string $documentTitle, bool $rightToLeft = false): string
    {
        $safeBody = Purify::config('ai_pdf')->clean($bodyHtml);

        if (trim(strip_tags($safeBody)) === '') {
            throw new \RuntimeException('The generated document was empty after sanitisation.');
        }

        $document = $this->wrapInDocument($safeBody, $documentTitle, $rightToLeft);

        $pdf = Pdf::setOptions([
            // dompdf would otherwise fetch http(s) resources and read local
            // files referenced from the markup.
            'isRemoteEnabled' => false,
            'isPhpEnabled' => false,
            'isJavascriptEnabled' => false,
            'isHtml5ParserEnabled' => true,
            // DejaVu ships with dompdf and covers accented Latin characters.
            'defaultFont' => 'DejaVu Sans',
            'defaultPaperSize' => 'a4',
            // Confine any surviving local path resolution to an empty directory.
            'chroot' => $this->chrootPath(),
        ])->loadHTML($document, 'UTF-8');

        $pdf->setPaper('a4');

        return $pdf->output();
    }

    private function chrootPath(): string
    {
        $path = storage_path('app/ai-pdf-chroot');

        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }

        return $path;
    }

    private function wrapInDocument(string $body, string $title, bool $rightToLeft): string
    {
        $escapedTitle = e($title);
        $direction = $rightToLeft ? 'rtl' : 'ltr';

        return <<<HTML
<!DOCTYPE html>
<html dir="{$direction}">
<head>
<meta charset="utf-8">
<title>{$escapedTitle}</title>
<style>
  @page { margin: 28mm 20mm; }
  body {
    font-family: "DejaVu Sans", sans-serif;
    font-size: 11pt;
    line-height: 1.55;
    color: #1a1613;
    direction: {$direction};
  }
  h1 { font-size: 20pt; line-height: 1.2; margin: 0 0 14pt; }
  h2 { font-size: 14pt; line-height: 1.25; margin: 20pt 0 8pt; }
  h3 { font-size: 12pt; margin: 16pt 0 6pt; }
  h4 { font-size: 11pt; margin: 14pt 0 4pt; }
  p { margin: 0 0 9pt; }
  ul, ol { margin: 0 0 9pt; padding-left: 16pt; }
  li { margin-bottom: 4pt; }
  blockquote {
    margin: 0 0 10pt; padding-left: 10pt;
    border-left: 2pt solid #d2cac1; color: #57504a;
  }
  hr { border: none; border-top: 0.5pt solid #d2cac1; margin: 14pt 0; }
  table { width: 100%; border-collapse: collapse; margin: 0 0 10pt; }
  th, td {
    border: 0.5pt solid #d2cac1; padding: 5pt 6pt;
    text-align: left; vertical-align: top; font-size: 10pt;
  }
  th { background: #f5f2ee; font-weight: bold; }
  small { font-size: 9pt; color: #57504a; }
</style>
</head>
<body>
{$body}
</body>
</html>
HTML;
    }
}
