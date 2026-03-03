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
        Schema::create('professional_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('values')->nullable();
            $table->text('philosophy')->nullable();
            $table->text('passions')->nullable();
            $table->text('leadership_style')->nullable();
            $table->text('collaboration_approach')->nullable();
            $table->text('communication_style')->nullable();
            $table->text('cultural_preferences')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('professional_identities');
    }
};
