<?php

use App\Enums\SecurityNoteStatus;
use App\Enums\SecurityRoutedTo;
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
        Schema::create('security_notes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('source');
            $table->string('category')->nullable();
            $table->string('severity');
            $table->text('finding');
            $table->boolean('is_issue')->default(true);
            $table->text('non_issue_reason')->nullable();
            $table->string('routed_to')->default(SecurityRoutedTo::Unrouted->value);
            $table->string('status')->default(SecurityNoteStatus::Flagged->value);
            $table->text('deferral_reason')->nullable();
            $table->date('date_flagged');
            $table->date('date_resolved')->nullable();
            $table->string('external_url')->nullable();
            $table->timestamps();

            $table->index(['status', 'severity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_notes');
    }
};
