<?php

use App\Models\ProfessionalIdentity;
use App\Models\Resume;
use App\Models\User;
use App\Services\ResumeHeaderService;

beforeEach(function () {
    $this->service = new ResumeHeaderService;
});

test('resolves defaults when no config exists', function () {
    $user = User::factory()->create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '555-1234',
        'location' => 'NYC',
        'linkedin_url' => 'https://linkedin.com/in/jane',
        'portfolio_url' => 'https://jane.dev',
    ]);
    $resume = Resume::factory()->create(['user_id' => $user->id]);

    $header = $this->service->resolveHeader($resume);

    expect($header)
        ->toMatchArray([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '555-1234',
            'location' => 'NYC',
            'linkedin_url' => 'https://linkedin.com/in/jane',
            'portfolio_url' => 'https://jane.dev',
        ]);
});

test('resolves global config from professional identity', function () {
    $user = User::factory()->create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '555-1234',
        'linkedin_url' => 'https://linkedin.com/in/jane',
    ]);
    ProfessionalIdentity::factory()->create([
        'user_id' => $user->id,
        'resume_header_config' => [
            'show_phone' => false,
            'show_linkedin' => false,
        ],
    ]);
    $resume = Resume::factory()->create(['user_id' => $user->id]);

    $header = $this->service->resolveHeader($resume);

    expect($header['name'])->toBe('Jane Doe');
    expect($header['email'])->toBe('jane@example.com');
    expect($header['phone'])->toBeNull();
    expect($header['linkedin_url'])->toBeNull();
});

test('per-resume config overrides global config', function () {
    $user = User::factory()->create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '555-1234',
    ]);
    ProfessionalIdentity::factory()->create([
        'user_id' => $user->id,
        'resume_header_config' => ['show_phone' => false],
    ]);
    $resume = Resume::factory()->create([
        'user_id' => $user->id,
        'header_config' => ['show_phone' => true],
    ]);

    $header = $this->service->resolveHeader($resume);

    expect($header['phone'])->toBe('555-1234');
});

test('uses legal name when preference set', function () {
    $user = User::factory()->create([
        'name' => 'Jane Doe',
        'legal_name' => 'Jane Marie Doe',
    ]);
    ProfessionalIdentity::factory()->create([
        'user_id' => $user->id,
        'resume_header_config' => ['name_preference' => 'legal_name'],
    ]);
    $resume = Resume::factory()->create(['user_id' => $user->id]);

    $header = $this->service->resolveHeader($resume);

    expect($header['name'])->toBe('Jane Marie Doe');
});

test('falls back to display name when legal name is null', function () {
    $user = User::factory()->create([
        'name' => 'Jane Doe',
        'legal_name' => null,
    ]);
    ProfessionalIdentity::factory()->create([
        'user_id' => $user->id,
        'resume_header_config' => ['name_preference' => 'legal_name'],
    ]);
    $resume = Resume::factory()->create(['user_id' => $user->id]);

    $header = $this->service->resolveHeader($resume);

    expect($header['name'])->toBe('Jane Doe');
});
