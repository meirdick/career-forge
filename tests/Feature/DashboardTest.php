<?php

use App\Models\JobPosting;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('new user sees onboarding state', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard')
        ->where('isNewUser', true)
        ->where('pipelineContinuation', null)
    );
});

test('user with data sees normal dashboard', function () {
    $user = User::factory()->create();
    $user->experiences()->create([
        'company' => 'Acme Corp',
        'title' => 'Engineer',
        'started_at' => now()->subYear(),
    ]);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard')
        ->where('isNewUser', false)
    );
});

test('pipeline continuation suggests next step for incomplete pipeline', function () {
    $user = User::factory()->create();
    $jobPosting = JobPosting::factory()->create([
        'user_id' => $user->id,
        'title' => 'Senior Dev',
        'company' => 'Test Corp',
    ]);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard')
        ->where('isNewUser', false)
        ->has('pipelineContinuation', fn ($continuation) => $continuation
            ->where('nextStep', 'gap_analysis')
            ->where('nextStepLabel', 'Run Gap Analysis')
            ->has('jobPosting')
            ->etc()
        )
    );
});
