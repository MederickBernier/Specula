<?php
namespace App\Concerns;

use Illuminate\Support\Str;

trait RendersMarkdown{
    /**
     * Convert stored markdown into HTML that is safe to render as raw HTML
     */
    protected function renderMarkdown(?string $markdown):?string{
        if($markdown === null || trim($markdown) === '') return null;

        return Str::markdown($markdown,[
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
