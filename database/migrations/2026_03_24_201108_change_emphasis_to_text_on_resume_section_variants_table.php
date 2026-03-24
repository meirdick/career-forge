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
        Schema::table('resume_section_variants', function (Blueprint $table) {
            $table->text('emphasis')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resume_section_variants', function (Blueprint $table) {
            $table->string('emphasis')->nullable()->change();
        });
    }
};
