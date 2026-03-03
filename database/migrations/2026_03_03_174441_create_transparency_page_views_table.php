<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transparency_page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transparency_page_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('referer', 2048)->nullable();
            $table->timestamp('viewed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transparency_page_views');
    }
};
