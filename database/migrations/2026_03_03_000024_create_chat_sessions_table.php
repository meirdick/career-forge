<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('conversation_id', 36)->nullable();
            $table->foreignId('job_posting_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('status')->default('active');
            $table->string('mode')->default('general');
            $table->string('step')->nullable();
            $table->string('pipeline_key')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'updated_at']);
            $table->index('pipeline_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_sessions');
    }
};
