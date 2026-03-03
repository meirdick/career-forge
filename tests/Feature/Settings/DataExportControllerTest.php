<?php

use App\Models\Experience;
use App\Models\Skill;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('export returns json with user data', function () {
    Experience::factory()->create(['user_id' => $this->user->id, 'company' => 'Acme Corp']);
    Skill::factory()->create(['user_id' => $this->user->id, 'name' => 'PHP']);

    $response = $this->actingAs($this->user)
        ->get('/settings/export')
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/json');

    $data = $response->json();

    expect($data)
        ->toHaveKey('exported_at')
        ->toHaveKey('user')
        ->toHaveKey('experiences')
        ->toHaveKey('skills')
        ->toHaveKey('education')
        ->toHaveKey('evidence')
        ->toHaveKey('job_postings')
        ->toHaveKey('applications');

    expect($data['user']['name'])->toBe($this->user->name);
    expect($data['experiences'])->toHaveCount(1);
    expect($data['experiences'][0]['company'])->toBe('Acme Corp');
    expect($data['skills'])->toHaveCount(1);
    expect($data['skills'][0]['name'])->toBe('PHP');
});

test('export requires authentication', function () {
    $this->get('/settings/export')
        ->assertRedirect();
});

test('export returns empty arrays when user has no data', function () {
    $response = $this->actingAs($this->user)
        ->get('/settings/export')
        ->assertSuccessful();

    $data = $response->json();

    expect($data['experiences'])->toBeEmpty();
    expect($data['skills'])->toBeEmpty();
});
