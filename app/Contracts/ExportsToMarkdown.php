<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Turns one record into the markdown document a person would hand to someone.
 *
 * The heading level is a parameter because the same renderer is used on its
 * own, where the record's title is the document's H1, and nested inside a
 * larger document, where it is a subsection.
 */
interface ExportsToMarkdown
{
    public function __invoke(Model $record, int $headingLevel = 1): string;

    /**
     * What the downloaded file should be called, without a format extension.
     */
    public function basename(Model $record): string;
}
