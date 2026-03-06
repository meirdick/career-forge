<?php

use App\Models\GapAnalysis;
use App\Models\Resume;
use App\Models\User;

test('gap analysis show page returns null latestResume when no resume exists', function () {
    $user = User::factory()->create();
    $gapAnalysis = GapAnalysis::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get("/gap-analyses/{$gapAnalysis->id}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('gap-analyses/show')
        ->where('latestResume', null)
    );
});

test('gap analysis show page returns latestResume when resume exists', function () {
    $user = User::factory()->create();
    $gapAnalysis = GapAnalysis::factory()->create(['user_id' => $user->id]);
    $resume = Resume::factory()->create([
        'user_id' => $user->id,
        'gap_analysis_id' => $gapAnalysis->id,
        'title' => 'Test Resume',
    ]);

    $response = $this->actingAs($user)->get("/gap-analyses/{$gapAnalysis->id}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('gap-analyses/show')
        ->has('latestResume')
        ->where('latestResume.id', $resume->id)
        ->where('latestResume.title', 'Test Resume')
        ->where('latestResume.is_finalized', false)
    );
});

test('gap analysis show page returns the most recent resume', function () {
    $user = User::factory()->create();
    $gapAnalysis = GapAnalysis::factory()->create(['user_id' => $user->id]);

    Resume::factory()->create([
        'user_id' => $user->id,
        'gap_analysis_id' => $gapAnalysis->id,
        'title' => 'Older Resume',
        'created_at' => now()->subDay(),
    ]);
    $latest = Resume::factory()->create([
        'user_id' => $user->id,
        'gap_analysis_id' => $gapAnalysis->id,
        'title' => 'Newer Resume',
    ]);

    $response = $this->actingAs($user)->get("/gap-analyses/{$gapAnalysis->id}");

    $response->assertInertia(fn ($page) => $page
        ->where('latestResume.id', $latest->id)
        ->where('latestResume.title', 'Newer Resume')
    );
});
