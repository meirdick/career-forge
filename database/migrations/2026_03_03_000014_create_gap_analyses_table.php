<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gap_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ideal_candidate_profile_id')->constrained()->cascadeOnDelete();
            $table->json('strengths');
            $table->json('gaps');
            $table->json('gap_resolutions')->nullable();
            $table->unsignedTinyInteger('overall_match_score')->nullable();
            $table->unsignedTinyInteger('previous_match_score')->nullable();
            $table->text('ai_summary')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gap_analyses');
    }
};
