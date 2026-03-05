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
        Schema::table('gap_analyses', function (Blueprint $table) {
            $table->json('gap_resolutions')->nullable()->after('gaps');
            $table->unsignedTinyInteger('previous_match_score')->nullable()->after('overall_match_score');
        });
    }

    public function down(): void
    {
        Schema::table('gap_analyses', function (Blueprint $table) {
            $table->dropColumn(['gap_resolutions', 'previous_match_score']);
        });
    }
};
