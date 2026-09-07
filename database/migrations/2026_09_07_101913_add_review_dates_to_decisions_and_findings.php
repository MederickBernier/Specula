<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Both fields turn something already written down into something that comes
     * back on its own: a decision already records the conditions for revisiting
     * it, and a deferred finding already records why it was put off. Neither
     * had a date, so neither ever resurfaced.
     */
    public function up(): void
    {
        Schema::table('decision_records', function (Blueprint $table) {
            $table->date('next_review_at')->nullable()->after('conditions_for_revisiting');
            $table->index('next_review_at');
        });

        Schema::table('security_notes', function (Blueprint $table) {
            $table->date('deferred_until')->nullable()->after('deferral_reason');
            $table->index('deferred_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('decision_records', function (Blueprint $table) {
            $table->dropIndex(['next_review_at']);
            $table->dropColumn('next_review_at');
        });

        Schema::table('security_notes', function (Blueprint $table) {
            $table->dropIndex(['deferred_until']);
            $table->dropColumn('deferred_until');
        });
    }
};
