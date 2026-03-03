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

    expect($this->user->professionalIdentity)
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
