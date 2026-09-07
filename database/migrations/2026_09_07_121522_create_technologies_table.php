<?php

use App\Enums\TechnologyRing;
use App\Enums\TechnologyStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The inventory of what the estate is built from. Ring and status are two
     * separate questions: what you would choose today, and what is actually
     * running. A technology can sensibly be held and current at once, which is
     * exactly the case worth being able to see.
     */
    public function up(): void
    {
        Schema::create('technologies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('category');
            $table->string('ring')->default(TechnologyRing::Assess->value);
            $table->string('status')->default(TechnologyStatus::Current->value);
            $table->string('vendor')->nullable();
            $table->string('homepage_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['category', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technologies');
    }
};
