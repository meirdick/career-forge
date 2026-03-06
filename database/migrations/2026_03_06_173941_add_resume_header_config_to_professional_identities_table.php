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
        Schema::table('professional_identities', function (Blueprint $table) {
            $table->json('resume_header_config')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('professional_identities', function (Blueprint $table) {
            $table->dropColumn('resume_header_config');
        });
    }
};
