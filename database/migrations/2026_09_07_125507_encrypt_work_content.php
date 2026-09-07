<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The content of the five work modules, encrypted at rest.
     *
     * Only the free text is covered. Statuses, dates, severities, foreign keys
     * and the project prefix stay in the clear, because the app has to sort,
     * filter and enforce uniqueness on them and none of them carries the
     * substance of the work. The radar is deliberately untouched: it holds
     * public feed items, and its url column is the dedup key behind a unique
     * index, which encryption would defeat.
     *
     * @var array<string, list<string>>
     */
    private const COLUMNS = [
        'projects' => ['name', 'description'],
        'project_notes' => ['title', 'body'],
        'decision_records' => [
            'title', 'author', 'deciders', 'affects',
            'proposal_context', 'recommendation', 'consequences', 'conditions_for_revisiting',
        ],
        'decision_options' => ['name', 'description', 'pros', 'cons'],
        'decision_links' => ['scope_note', 'role_note', 'impact_summary'],
        'vetting_items' => [
            'title', 'source_detail', 'proposal_description',
            'assessment', 'rejection_reason', 'external_url',
        ],
        'prototypes' => [
            'title', 'hypothesis', 'test_approach', 'result',
            'abandoned_reason', 'reusability_note', 'repo_reference',
        ],
        'security_notes' => [
            'title', 'category', 'finding', 'non_issue_reason',
            'deferral_reason', 'external_url',
        ],
        'item_links' => ['note'],
    ];

    /**
     * The columns that are not nullable, and so must be written back as a
     * string rather than left null.
     *
     * @var array<string, list<string>>
     */
    private const REQUIRED = [
        'projects' => ['name'],
        'project_notes' => ['title', 'body'],
        'decision_records' => ['title', 'author', 'proposal_context', 'recommendation'],
        'decision_options' => ['name'],
        'vetting_items' => ['title', 'proposal_description'],
        'prototypes' => ['title', 'hypothesis'],
        'security_notes' => ['title', 'finding'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ciphertext is several times longer than what it replaces, so every
        // covered column becomes text before anything is written into it.
        $this->widen();

        foreach (self::COLUMNS as $table => $columns) {
            $this->transform($table, $columns, fn (string $value): string => Crypt::encryptString($value));
        }
    }

    /**
     * Reverse the migrations.
     *
     * The column types are left as text: narrowing them again would truncate
     * anything written since, and text costs nothing here.
     */
    public function down(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            $this->transform($table, $columns, function (string $value): string {
                try {
                    return Crypt::decryptString($value);
                } catch (Throwable) {
                    // Already plain text, so leave it as it is.
                    return $value;
                }
            });
        }
    }

    private function widen(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns) {
                foreach ($columns as $column) {
                    $required = in_array($column, self::REQUIRED[$table] ?? [], true);

                    $definition = $blueprint->text($column)->change();

                    if (! $required) {
                        $definition->nullable();
                    }
                }
            });
        }
    }

    /**
     * @param  list<string>  $columns
     * @param  callable(string): string  $transform
     */
    private function transform(string $table, array $columns, callable $transform): void
    {
        DB::table($table)->orderBy('id')->chunkById(200, function ($rows) use ($table, $columns, $transform): void {
            foreach ($rows as $row) {
                $changes = [];

                foreach ($columns as $column) {
                    $value = $row->{$column} ?? null;

                    // An empty string is a value, not an absence: a decision
                    // with no recommendation yet still has the column set, and
                    // leaving it in the clear makes it undecryptable on read.
                    if ($value === null) {
                        continue;
                    }

                    $changes[$column] = $transform((string) $value);
                }

                if ($changes !== []) {
                    DB::table($table)->where('id', $row->id)->update($changes);
                }
            }
        });
    }
};
