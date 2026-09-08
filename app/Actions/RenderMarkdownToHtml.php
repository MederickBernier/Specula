<?php

namespace App\Actions;

use Illuminate\Support\Str;

/**
 * The one place markdown becomes HTML.
 *
 * Preview, the show pages and the PDF all go through here, so what someone sees
 * while writing is what gets saved and what gets printed. A second converter
 * anywhere would mean those three could quietly disagree.
 *
 * Feed and record text is written by people and, in the radar's case, by
 * strangers, so embedded HTML is stripped rather than trusted and unsafe link
 * schemes are refused.
 */
class RenderMarkdownToHtml
{
    public function __invoke(?string $markdown): ?string
    {
        if ($markdown === null || trim($markdown) === '') {
            return null;
        }

        return Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
