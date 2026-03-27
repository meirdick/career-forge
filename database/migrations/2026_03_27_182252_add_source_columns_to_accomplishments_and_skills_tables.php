<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accomplishments', function (Blueprint $table) {
            $table->nullableMorphs('source');
        });

        Schema::table('skills', function (Blueprint $table) {
            $table->nullableMorphs('source');
        });
    }

    public function down(): void
    {
        Schema::table('accomplishments', function (Blueprint $table) {
            $table->dropMorphs('source');
        });

        Schema::table('skills', function (Blueprint $table) {
            $table->dropMorphs('source');
        });
    }
};
