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
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->string('step')->nullable()->after('mode');
            $table->string('pipeline_key')->nullable()->after('step');

            $table->index('pipeline_key');
        });
    }

    public function down(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->dropIndex(['pipeline_key']);
            $table->dropColumn(['step', 'pipeline_key']);
        });
    }
};
