<?php

namespace App\Actions;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Carbon\CarbonImmutable;

/**
 * A markdown document as a PDF.
 *
 * Runs the markdown through the same converter the pages use, so the PDF says
 * what the app says, then renders that HTML with a stylesheet of its own: the
 * app's CSS is built on flexbox and grid, neither of which dompdf supports.
 */
class RenderMarkdownPdf
{
    public function __construct(private RenderMarkdownToHtml $toHtml) {}

    public function __invoke(string $markdown, string $title): PdfDocument
    {
        return Pdf::loadView('pdf.document', [
            'title' => $title,
            'html' => ($this->toHtml)($markdown) ?? '',
            'generatedAt' => CarbonImmutable::now()->toDateString(),
        ])->setPaper('a4');
    }
}
