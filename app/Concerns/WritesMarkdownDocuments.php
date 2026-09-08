<?php

namespace App\Concerns;

/**
 * The shared mechanics of assembling a markdown document.
 *
 * Sections that were never written are skipped rather than printed empty, and
 * anything going into a table cell has its pipes escaped so free text cannot
 * break the table around it.
 */
trait WritesMarkdownDocuments
{
    /**
     * The hashes for a heading level, clamped to the six that exist.
     *
     * Documents nest — a decision inside a project inside the archive — and
     * without a clamp the deepest headings render as literal hashes.
     */
    protected function heading(int $level): string
    {
        return str_repeat('#', max(1, min($level, 6)));
    }

    /**
     * @param  list<string>  $lines
     */
    protected function section(array &$lines, string $heading, ?string $body): void
    {
        if ($body === null || trim($body) === '') {
            return;
        }

        $lines[] = '';
        $lines[] = $heading;
        $lines[] = '';
        $lines[] = trim($body);
    }

    /**
     * @param  list<array{0: string, 1: string|null}>  $rows
     * @param  list<string>  $lines
     */
    protected function metadata(array &$lines, array $rows): void
    {
        $lines[] = '';
        $lines[] = '| | |';
        $lines[] = '|---|---|';

        foreach ($rows as [$label, $value]) {
            $lines[] = '| '.$label.' | '.$this->cell($value).' |';
        }
    }

    protected function cell(?string $value): string
    {
        $value = trim((string) $value);

        return $value === '' ? '—' : str_replace('|', '\\|', $value);
    }
}
