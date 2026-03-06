<?php

use App\Models\User;

test('welcome dismiss route sets welcome_dismissed_at', function () {
    $user = User::factory()->create(['welcome_dismissed_at' => null]);

    $this->actingAs($user)
        ->post(route('welcome.dismiss'))
        ->assertRedirect();

    expect($user->fresh()->welcome_dismissed_at)->not->toBeNull();
});

test('welcome dismiss route requires authentication', function () {
    $this->post(route('welcome.dismiss'))
        ->assertRedirect(route('login'));
});

test('showWelcome is true for new user in gated mode', function () {
    config(['ai.gating.mode' => 'gated']);

    $user = User::factory()->create(['welcome_dismissed_at' => null]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('showWelcome', true));
});

test('showWelcome is false after dismissal', function () {
    config(['ai.gating.mode' => 'gated']);

    $user = User::factory()->create(['welcome_dismissed_at' => now()]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('showWelcome', false));
});

test('showWelcome is false in selfhosted mode', function () {
    config(['ai.gating.mode' => 'selfhosted']);

    $user = User::factory()->create(['welcome_dismissed_at' => null]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('showWelcome', false));
});

test('referralCode is shared via inertia', function () {
    $user = User::factory()->create(['referral_code' => 'ABC12345']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('referralCode', 'ABC12345'));
});
