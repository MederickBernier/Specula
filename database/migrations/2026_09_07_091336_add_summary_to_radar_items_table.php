<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Holds the feed entry's own description so triage can happen from the
     * queue instead of opening every link. Stored as plain text, never the
     * markup the feed supplied.
     */
    public function up(): void
    {
        Schema::table('radar_items', function (Blueprint $table) {
            $table->text('summary')->nullable()->after('url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('radar_items', function (Blueprint $table) {
            $table->dropColumn('summary');
        });
    }
};
