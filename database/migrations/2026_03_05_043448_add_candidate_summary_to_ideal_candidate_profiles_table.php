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
        Schema::table('ideal_candidate_profiles', function (Blueprint $table) {
            $table->text('candidate_summary')->nullable()->after('company_research');
        });
    }

    public function down(): void
    {
        Schema::table('ideal_candidate_profiles', function (Blueprint $table) {
            $table->dropColumn('candidate_summary');
        });
    }
};
