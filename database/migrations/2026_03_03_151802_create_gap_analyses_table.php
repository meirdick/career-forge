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
        Schema::create('gap_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ideal_candidate_profile_id')->constrained()->cascadeOnDelete();
            $table->json('strengths');
            $table->json('gaps');
            $table->unsignedTinyInteger('overall_match_score')->nullable();
            $table->text('ai_summary')->nullable();
            $table->boolean('is_finalized')->default(false);
            $table->timestamps();

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gap_analyses');
    }
};
