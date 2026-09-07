<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Encrypt anything the first pass left in the clear.
     *
     * That pass skipped empty strings, which are values rather than absences: a
     * decision with no recommendation written yet still has the column set, and
     * a plain empty string cannot be decrypted on read. This sweeps up those and
     * anything else that is readable, and is safe to run against a database
     * that is already fully encrypted, since it only touches what fails to
     * decrypt.
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
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            DB::table($table)->orderBy('id')->chunkById(200, function ($rows) use ($table, $columns): void {
                foreach ($rows as $row) {
                    $changes = [];

                    foreach ($columns as $column) {
                        $value = $row->{$column} ?? null;

                        if ($value === null || $this->isEncrypted((string) $value)) {
                            continue;
                        }

                        $changes[$column] = Crypt::encryptString((string) $value);
                    }

                    if ($changes !== []) {
                        DB::table($table)->where('id', $row->id)->update($changes);
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * Nothing to undo: the pass this repairs owns the decryption.
     */
    public function down(): void {}

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (Throwable) {
            return false;
        }
    }
};
