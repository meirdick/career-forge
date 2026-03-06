<?php

use App\Models\CreditBalance;
use App\Models\UsageLimit;
use App\Models\User;
use App\Models\UserApiKey;

test('selfhosted mode passes through all requests', function () {
    config(['ai.gating.mode' => 'selfhosted']);

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('job-postings.store'), [
            'raw_text' => 'Test job posting with enough content to be valid for analysis',
        ]);

    // Should not get 402 - may get validation error or redirect, but not gating error
    expect($response->status())->not->toBe(402);
});

test('gated mode returns 402 for free tier user exceeding limits', function () {
    config(['ai.gating.mode' => 'gated']);

    $user = User::factory()->create();
    UsageLimit::factory()->create([
        'user_id' => $user->id,
        'job_postings_used' => 1,
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('job-postings.store'), [
            'raw_text' => 'Test job posting',
        ]);

    $response->assertStatus(402);
    $response->assertJson([
        'message' => 'AI access limit reached',
        'access_mode' => 'free_tier',
        'purpose' => 'job_analysis',
    ]);
});

test('gated mode allows byok users', function () {
    config(['ai.gating.mode' => 'gated']);

    $user = User::factory()->create();
    UserApiKey::factory()->active()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->post(route('job-postings.store'), [
            'raw_text' => 'Test job posting',
        ]);

    // Should not be blocked by gating (may fail for other reasons)
    expect($response->status())->not->toBe(402);
});

test('gated mode allows credits users with sufficient balance', function () {
    config(['ai.gating.mode' => 'gated']);

    $user = User::factory()->create();
    CreditBalance::factory()->withBalance(500)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->post(route('job-postings.store'), [
            'raw_text' => 'Test job posting',
        ]);

    expect($response->status())->not->toBe(402);
});

test('gated mode returns 402 for credits users with insufficient balance', function () {
    config(['ai.gating.mode' => 'gated']);

    $user = User::factory()->create();
    CreditBalance::factory()->withBalance(1)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->postJson(route('job-postings.store'), [
            'raw_text' => 'Test job posting',
        ]);

    $response->assertStatus(402);
});

test('gated mode allows free tier user within limits', function () {
    config(['ai.gating.mode' => 'gated']);

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('job-postings.store'), [
            'raw_text' => 'Test job posting',
        ]);

    expect($response->status())->not->toBe(402);
});

test('inertia request redirects back with flash on access denied', function () {
    config(['ai.gating.mode' => 'gated']);

    $user = User::factory()->create();
    UsageLimit::factory()->create([
        'user_id' => $user->id,
        'job_postings_used' => 1,
    ]);

    $response = $this->actingAs($user)
        ->post(route('job-postings.store'), [
            'raw_text' => 'Test job posting',
        ], [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => '1',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('ai_access_denied', function (array $data) {
        return $data['message'] === 'AI access limit reached'
            && $data['access_mode'] === 'free_tier'
            && $data['purpose'] === 'job_analysis';
    });
});

test('chat route is gated', function () {
    config(['ai.gating.mode' => 'gated']);

    $user = User::factory()->create();

    // Free tier users cannot use chat
    $chatSession = \App\Models\ChatSession::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->postJson(route('career-chat.chat', $chatSession), [
            'message' => 'Hello',
        ]);

    $response->assertStatus(402);
});
