<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('decision_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('decision_records')->cascadeOnDelete();
            $table->foreignId('target_id')->constrained('decision_records')->cascadeOnDelete();
            $table->string('relationship_type');
            $table->string('scope_note')->nullable();
            $table->string('role_note')->nullable();
            $table->text('impact_summary')->nullable();
            $table->timestamps();

            $table->unique(['source_id', 'target_id', 'relationship_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decision_links');
    }
};
