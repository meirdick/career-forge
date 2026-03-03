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
        Schema::create('transparency_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->text('authorship_statement');
            $table->text('research_summary');
            $table->text('ideal_profile_summary');
            $table->json('section_decisions');
            $table->text('tool_description')->nullable();
            $table->string('repository_url')->nullable();
            $table->boolean('is_published')->default(false);
            $table->longText('content_html')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transparency_pages');
    }
};
