<?php

use App\Enums\AiAccessMode;
use App\Enums\AiPurpose;
use App\Models\CreditBalance;
use App\Models\UsageLimit;
use App\Models\User;
use App\Models\UserApiKey;
use App\Services\AiGatingService;

beforeEach(function () {
    config(['ai.gating.mode' => 'gated']);
});

test('resolves selfhosted mode when config is selfhosted', function () {
    config(['ai.gating.mode' => 'selfhosted']);

    $user = User::factory()->create();
    $service = app(AiGatingService::class);

    expect($service->resolveAccessMode($user))->toBe(AiAccessMode::Selfhosted);
});

test('resolves byok mode when user has active api key', function () {
    $user = User::factory()->create();
    UserApiKey::factory()->active()->anthropic()->create(['user_id' => $user->id]);

    $service = app(AiGatingService::class);

    expect($service->resolveAccessMode($user))->toBe(AiAccessMode::Byok);
});

test('resolves credits mode when user has positive balance', function () {
    $user = User::factory()->create();
    CreditBalance::factory()->withBalance(100)->create(['user_id' => $user->id]);

    $service = app(AiGatingService::class);

    expect($service->resolveAccessMode($user))->toBe(AiAccessMode::Credits);
});

test('resolves free tier when user has no key and no credits', function () {
    $user = User::factory()->create();

    $service = app(AiGatingService::class);

    expect($service->resolveAccessMode($user))->toBe(AiAccessMode::FreeTier);
});

test('selfhosted mode always allows actions', function () {
    config(['ai.gating.mode' => 'selfhosted']);

    $user = User::factory()->create();
    $service = app(AiGatingService::class);

    expect($service->canPerformAction($user, AiPurpose::JobAnalysis))->toBeTrue();
    expect($service->canPerformAction($user, AiPurpose::ResumeParsing))->toBeTrue();
    expect($service->canPerformAction($user, AiPurpose::ChatMessage))->toBeTrue();
});

test('byok mode allows all actions', function () {
    $user = User::factory()->create();
    UserApiKey::factory()->active()->create(['user_id' => $user->id]);

    $service = app(AiGatingService::class);

    expect($service->canPerformAction($user, AiPurpose::JobAnalysis))->toBeTrue();
    expect($service->canPerformAction($user, AiPurpose::ResumeGeneration))->toBeTrue();
});

test('credits mode allows actions when sufficient balance', function () {
    $user = User::factory()->create();
    CreditBalance::factory()->withBalance(100)->create(['user_id' => $user->id]);

    $service = app(AiGatingService::class);

    expect($service->canPerformAction($user, AiPurpose::ChatMessage))->toBeTrue();
});

test('credits mode denies actions when insufficient balance', function () {
    $user = User::factory()->create();
    CreditBalance::factory()->withBalance(1)->create(['user_id' => $user->id]);

    $service = app(AiGatingService::class);

    // Resume generation costs 25 credits
    expect($service->canPerformAction($user, AiPurpose::ResumeGeneration))->toBeFalse();
});

test('free tier allows first job posting analysis', function () {
    $user = User::factory()->create();

    $service = app(AiGatingService::class);

    expect($service->canPerformAction($user, AiPurpose::JobAnalysis))->toBeTrue();
});

test('free tier denies job posting analysis after limit', function () {
    $user = User::factory()->create();
    UsageLimit::factory()->create([
        'user_id' => $user->id,
        'job_postings_used' => 1,
    ]);

    $service = app(AiGatingService::class);

    expect($service->canPerformAction($user, AiPurpose::JobAnalysis))->toBeFalse();
});

test('free tier allows document uploads within limit', function () {
    $user = User::factory()->create();
    UsageLimit::factory()->create([
        'user_id' => $user->id,
        'documents_used' => 2,
    ]);

    $service = app(AiGatingService::class);

    expect($service->canPerformAction($user, AiPurpose::ResumeParsing))->toBeTrue();
});

test('free tier denies document uploads after limit', function () {
    $user = User::factory()->create();
    UsageLimit::factory()->create([
        'user_id' => $user->id,
        'documents_used' => 3,
    ]);

    $service = app(AiGatingService::class);

    expect($service->canPerformAction($user, AiPurpose::ResumeParsing))->toBeFalse();
});

test('free tier denies non-free-tier actions', function () {
    $user = User::factory()->create();

    $service = app(AiGatingService::class);

    expect($service->canPerformAction($user, AiPurpose::ChatMessage))->toBeFalse();
    expect($service->canPerformAction($user, AiPurpose::ResumeGeneration))->toBeFalse();
});

test('configureRuntimeProvider sets byok config for byok users', function () {
    $user = User::factory()->create();
    UserApiKey::factory()->active()->create([
        'user_id' => $user->id,
        'provider' => 'anthropic',
        'encrypted_key' => 'sk-test-key-123',
    ]);

    $service = app(AiGatingService::class);
    $service->configureRuntimeProvider($user);

    expect(config('ai.providers.byok.driver'))->toBe('anthropic');
    expect(config('ai.default'))->toBe('byok');
});

test('configureRuntimeProvider does nothing for non-byok users', function () {
    $user = User::factory()->create();

    $service = app(AiGatingService::class);
    $service->configureRuntimeProvider($user);

    expect(config('ai.default'))->toBe('anthropic');
});

test('chargeCredits deducts from balance for credits mode', function () {
    $user = User::factory()->create();
    CreditBalance::factory()->withBalance(100)->create(['user_id' => $user->id]);

    $service = app(AiGatingService::class);
    $service->chargeCredits($user, AiPurpose::ChatMessage);

    $user->refresh();
    $expectedBalance = 100 - config('ai.gating.costs.chat_message');
    expect($user->creditBalance->balance)->toBe($expectedBalance);
});

test('chargeCredits does nothing in selfhosted mode', function () {
    config(['ai.gating.mode' => 'selfhosted']);

    $user = User::factory()->create();
    CreditBalance::factory()->withBalance(100)->create(['user_id' => $user->id]);

    $service = app(AiGatingService::class);
    $service->chargeCredits($user, AiPurpose::ChatMessage);

    $user->refresh();
    expect($user->creditBalance->balance)->toBe(100);
});

test('incrementFreeTierUsage tracks job postings', function () {
    $user = User::factory()->create();

    $service = app(AiGatingService::class);
    $service->incrementFreeTierUsage($user, AiPurpose::JobAnalysis);

    $user->refresh();
    expect($user->usageLimit->job_postings_used)->toBe(1);
});

test('incrementFreeTierUsage tracks documents', function () {
    $user = User::factory()->create();

    $service = app(AiGatingService::class);
    $service->incrementFreeTierUsage($user, AiPurpose::ResumeParsing);

    $user->refresh();
    expect($user->usageLimit->documents_used)->toBe(1);
});

test('getFreeTierUsage returns correct counts', function () {
    $user = User::factory()->create();
    UsageLimit::factory()->create([
        'user_id' => $user->id,
        'job_postings_used' => 1,
        'documents_used' => 2,
    ]);

    $service = app(AiGatingService::class);
    $usage = $service->getFreeTierUsage($user);

    expect($usage)->toBe([
        'job_postings_used' => 1,
        'job_postings_limit' => 1,
        'documents_used' => 2,
        'documents_limit' => 3,
    ]);
});
