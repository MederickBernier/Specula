<?php

use App\Enums\DecisionStatus;
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
        Schema::create('decision_records', function (Blueprint $table) {
            $table->id();
            $table->string('project_prefix');
            $table->string('category');
            $table->unsignedInteger('sequence');
            $table->string('title');
            $table->string('status')->default(DecisionStatus::Draft->value);
            $table->string('author');
            $table->string('deciders')->nullable();
            $table->string('affects')->nullable();
            $table->text('proposal_context');
            $table->text('recommendation');
            $table->text('consequences')->nullable();
            $table->text('conditions_for_revisiting')->nullable();
            $table->timestamps();

            $table->unique(['project_prefix', 'category', 'sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decision_records');
    }
};
