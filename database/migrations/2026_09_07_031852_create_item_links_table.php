<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The morph columns cannot carry foreign keys, so the referenced rows are
     * validated in the application instead. That tradeoff is accepted in the
     * spec: one link mechanism to learn beats a junction table per module pair.
     */
    public function up(): void
    {
        Schema::create('item_links', function (Blueprint $table) {
            $table->id();
            $table->morphs('source');
            $table->morphs('target');
            $table->string('link_type');
            $table->string('note')->nullable();
            $table->timestamp('date_linked');
            $table->timestamps();

            $table->unique([
                'source_type', 'source_id',
                'target_type', 'target_id',
                'link_type',
            ], 'item_links_pair_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_links');
    }
};
