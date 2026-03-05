<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resume_section_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_section_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->longText('content');
            $table->string('emphasis')->nullable();
            $table->boolean('is_ai_generated')->default(false);
            $table->boolean('is_user_edited')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('resume_section_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_section_variants');
    }
};
