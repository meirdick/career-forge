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
        Schema::create('ideal_candidate_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained()->cascadeOnDelete();
            $table->json('required_skills');
            $table->json('preferred_skills');
            $table->json('experience_profile');
            $table->json('cultural_fit');
            $table->json('language_guidance');
            $table->json('red_flags');
            $table->json('company_research')->nullable();
            $table->json('industry_standards')->nullable();
            $table->boolean('is_user_edited')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ideal_candidate_profiles');
    }
};
