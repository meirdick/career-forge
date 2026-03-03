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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('experience_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description');
            $table->string('role')->nullable();
            $table->string('url')->nullable();
            $table->string('scale')->nullable();
            $table->text('outcome')->nullable();
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'experience_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
