<?php

use App\Enums\TriageStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * url is unique because it is the dedup key on every re-fetch: a feed that
     * still lists an item you already triaged must not resurface it.
     */
    public function up(): void
    {
        Schema::create('radar_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feed_source_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('url')->unique();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('fetched_at');
            $table->string('triage_status')->default(TriageStatus::Pending->value);
            $table->timestamp('triaged_at')->nullable();
            $table->text('relevance_note')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();

            $table->index(['triage_status', 'is_hidden']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('radar_items');
    }
};
