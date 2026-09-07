<?php

use App\Enums\VettingStatus;
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
        Schema::create('vetting_items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('source_type');
            $table->string('source_detail')->nullable();
            $table->date('date_raised');
            $table->text('proposal_description');
            $table->text('assessment')->nullable();
            $table->string('status')->default(VettingStatus::New->value);
            $table->text('rejection_reason')->nullable();
            $table->date('date_resolved')->nullable();
            $table->string('external_url')->nullable();
            $table->timestamps();

            $table->index(['status', 'date_raised']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vetting_items');
    }
};
