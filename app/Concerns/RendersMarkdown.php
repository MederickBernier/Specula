<?php

namespace App\Concerns;

use App\Actions\RenderMarkdownToHtml;

trait RendersMarkdown
{
    /**
     * Convert stored markdown into HTML that is safe to render as raw HTML.
     *
     * Delegates rather than converting here: the options live in one place so
     * the preview, the page and the PDF cannot drift apart.
     */
    protected function renderMarkdown(?string $markdown): ?string
    {
        return app(RenderMarkdownToHtml::class)($markdown);
    }
}
