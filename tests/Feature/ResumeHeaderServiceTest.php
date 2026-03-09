<?php

use App\Models\ProfessionalIdentity;
use App\Models\Resume;
use App\Models\User;
use App\Models\UserLink;
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
    ]);
    UserLink::factory()->create([
        'user_id' => $user->id,
        'url' => 'https://jane.dev',
        'label' => null,
        'type' => 'portfolio',
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
        ]);
    expect($header['portfolio_links'])->toHaveCount(1);
    expect($header['portfolio_links'][0]['url'])->toBe('https://jane.dev');
    expect($header['portfolio_links'][0]['label'])->toBe('jane.dev');
});

test('resolves multiple portfolio links', function () {
    $user = User::factory()->create(['name' => 'Jane Doe']);
    UserLink::factory()->create([
        'user_id' => $user->id,
        'url' => 'https://jane.dev',
        'label' => 'Portfolio',
        'type' => 'portfolio',
        'sort_order' => 0,
    ]);
    UserLink::factory()->create([
        'user_id' => $user->id,
        'url' => 'https://github.com/jane',
        'label' => null,
        'type' => 'github',
        'sort_order' => 1,
    ]);
    $resume = Resume::factory()->create(['user_id' => $user->id]);

    $header = $this->service->resolveHeader($resume);

    expect($header['portfolio_links'])->toHaveCount(2);
    expect($header['portfolio_links'][0]['label'])->toBe('Portfolio');
    expect($header['portfolio_links'][1]['label'])->toBe('github.com');
});

test('hides portfolio links when show_portfolio is false', function () {
    $user = User::factory()->create();
    UserLink::factory()->create(['user_id' => $user->id]);
    ProfessionalIdentity::factory()->create([
        'user_id' => $user->id,
        'resume_header_config' => ['show_portfolio' => false],
    ]);
    $resume = Resume::factory()->create(['user_id' => $user->id]);

    $header = $this->service->resolveHeader($resume);

    expect($header['portfolio_links'])->toBeEmpty();
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

test('returns empty portfolio links when user has no links', function () {
    $user = User::factory()->create();
    $resume = Resume::factory()->create(['user_id' => $user->id]);

    $header = $this->service->resolveHeader($resume);

    expect($header['portfolio_links'])->toBeEmpty();
});
