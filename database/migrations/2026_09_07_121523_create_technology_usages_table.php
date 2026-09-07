<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One row per place a technology is used. The version lives here rather
     * than on the technology, because the answer to "which Postgres" is
     * different for every project running it, and that spread is the point.
     */
    public function up(): void
    {
        Schema::create('technology_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technology_id')->constrained()->cascadeOnDelete();
            $table->morphs('usable');
            $table->string('version')->nullable();
            $table->string('role')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // A record uses a technology once; the version and role say the rest.
            $table->unique(['technology_id', 'usable_type', 'usable_id'], 'technology_usages_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technology_usages');
    }
};
