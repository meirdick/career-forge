<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Copy existing portfolio_url values into user_links
        $users = DB::table('users')
            ->whereNotNull('portfolio_url')
            ->where('portfolio_url', '!=', '')
            ->get(['id', 'portfolio_url']);

        foreach ($users as $user) {
            DB::table('user_links')->insert([
                'user_id' => $user->id,
                'url' => $user->portfolio_url,
                'label' => null,
                'type' => 'portfolio',
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Drop the portfolio_url column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('portfolio_url');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('portfolio_url')->nullable()->after('linkedin_url');
        });

        // Copy first portfolio link back
        $links = DB::table('user_links')
            ->where('type', 'portfolio')
            ->orderBy('sort_order')
            ->get(['user_id', 'url']);

        $seen = [];
        foreach ($links as $link) {
            if (! isset($seen[$link->user_id])) {
                DB::table('users')
                    ->where('id', $link->user_id)
                    ->update(['portfolio_url' => $link->url]);
                $seen[$link->user_id] = true;
            }
        }
    }
};
