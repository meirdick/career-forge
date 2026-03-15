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
        Schema::table('resumes', function (Blueprint $table) {
            $table->text('transparency_text')->nullable()->after('generation_progress');
            $table->boolean('show_transparency')->default(false)->after('transparency_text');
        });

        Schema::table('resume_sections', function (Blueprint $table) {
            $table->string('display_mode')->default('expanded')->after('is_hidden');
        });

        Schema::table('resume_section_variants', function (Blueprint $table) {
            $table->text('compact_content')->nullable()->after('blocks');
        });
    }

    public function down(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            $table->dropColumn(['transparency_text', 'show_transparency']);
        });

        Schema::table('resume_sections', function (Blueprint $table) {
            $table->dropColumn('display_mode');
        });

        Schema::table('resume_section_variants', function (Blueprint $table) {
            $table->dropColumn('compact_content');
        });
    }
};
