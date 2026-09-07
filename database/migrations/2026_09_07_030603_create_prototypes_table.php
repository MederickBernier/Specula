<?php

use App\Enums\PrototypeStatus;
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
        Schema::create('prototypes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('status')->default(PrototypeStatus::Planned->value);
            $table->text('hypothesis');
            $table->text('test_approach')->nullable();
            $table->text('result')->nullable();
            $table->text('abandoned_reason')->nullable();
            $table->string('confidence_level')->nullable();
            $table->boolean('is_reusable')->nullable();
            $table->text('reusability_note')->nullable();
            $table->string('repo_reference')->nullable();
            $table->date('date_started');
            $table->date('date_completed')->nullable();
            $table->timestamps();

            $table->index(['status', 'date_started']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prototypes');
    }
};
