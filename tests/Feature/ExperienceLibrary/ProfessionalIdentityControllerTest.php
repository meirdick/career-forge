<?php

use App\Models\ProfessionalIdentity;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('edit page renders', function () {
    $this->actingAs($this->user)
        ->get('/identity')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('experience-library/identity'));
});

test('edit page shows existing identity', function () {
    ProfessionalIdentity::factory()->create([
        'user_id' => $this->user->id,
        'values' => 'Integrity and innovation',
    ]);

    $this->actingAs($this->user)
        ->get('/identity')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->where('identity.values', 'Integrity and innovation')
        );
});

test('update creates identity if none exists', function () {
    $this->actingAs($this->user)
        ->put('/identity', [
            'values' => 'Growth mindset',
            'philosophy' => 'Ship early, iterate often',
        ])
        ->assertRedirect('/identity');

    expect($this->user->fresh()->professionalIdentity)
        ->values->toBe('Growth mindset')
        ->philosophy->toBe('Ship early, iterate often');
});

test('update modifies existing identity', function () {
    ProfessionalIdentity::factory()->create([
        'user_id' => $this->user->id,
        'values' => 'Old values',
    ]);

    $this->actingAs($this->user)
        ->put('/identity', [
            'values' => 'New values',
        ])
        ->assertRedirect('/identity');

    expect($this->user->fresh()->professionalIdentity->values)->toBe('New values');
});

test('edit page passes resumeHeaderConfig and user info', function () {
    $this->actingAs($this->user)
        ->get('/identity')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->has('resumeHeaderConfig')
                ->has('user.name')
                ->has('user.legal_name')
        );
});

test('update saves resume header config', function () {
    $this->actingAs($this->user)
        ->put('/identity', [
            'resume_header_config' => [
                'name_preference' => 'legal_name',
                'show_email' => true,
                'show_phone' => false,
                'show_location' => true,
                'show_linkedin' => false,
                'show_portfolio' => true,
            ],
        ])
        ->assertRedirect('/identity');

    $config = $this->user->fresh()->professionalIdentity->resume_header_config;
    expect($config['name_preference'])->toBe('legal_name');
    expect($config['show_phone'])->toBeFalse();
    expect($config['show_linkedin'])->toBeFalse();
});

test('update saves legal name on user', function () {
    $this->actingAs($this->user)
        ->put('/identity', [
            'legal_name' => 'Jane Marie Doe',
        ])
        ->assertRedirect('/identity');

    expect($this->user->fresh()->legal_name)->toBe('Jane Marie Doe');
});
