<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The tables whose records can belong to a project.
     *
     * @var list<string>
     */
    private const TABLES = [
        'decision_records',
        'vetting_items',
        'prototypes',
        'security_notes',
    ];

    /**
     * Run the migrations.
     *
     * Nullable, because plenty of this work is not attached to a project, and
     * nulled rather than cascaded on delete: retiring a project must not take
     * the decisions it produced with it.
     */
    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('project_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('project_id');
            });
        }
    }
};
