<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_interactions', function (Blueprint $table) {
            $table->unsignedInteger('credits_charged')->default(0);
            $table->string('billing_type')->default('server');
        });
    }

    public function down(): void
    {
        Schema::table('ai_interactions', function (Blueprint $table) {
            $table->dropColumn(['credits_charged', 'billing_type']);
        });
    }
};
