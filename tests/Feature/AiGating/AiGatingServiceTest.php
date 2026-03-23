<?php

use App\Enums\AiAccessMode;
use App\Enums\AiPurpose;
use App\Models\CreditBalance;
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

test('resolves credits mode when user has no key and no credits', function () {
    $user = User::factory()->create();

    $service = app(AiGatingService::class);

    expect($service->resolveAccessMode($user))->toBe(AiAccessMode::Credits);
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

test('user with zero credits is denied all actions', function () {
    $user = User::factory()->create();

    $service = app(AiGatingService::class);

    expect($service->canPerformAction($user, AiPurpose::JobAnalysis))->toBeFalse();
    expect($service->canPerformAction($user, AiPurpose::ResumeParsing))->toBeFalse();
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

    expect(config('ai.default'))->toBe(['anthropic', 'gemini']);
});

test('configureRuntimeProvider clears byok config between sequential calls', function () {
    $byokUser = User::factory()->create();
    UserApiKey::factory()->active()->create([
        'user_id' => $byokUser->id,
        'provider' => 'anthropic',
        'encrypted_key' => 'sk-test-key-456',
    ]);

    $creditsUser = User::factory()->create();
    CreditBalance::factory()->withBalance(100)->create(['user_id' => $creditsUser->id]);

    $service = app(AiGatingService::class);

    // First call sets BYOK config
    $service->configureRuntimeProvider($byokUser);
    expect(config('ai.providers.byok.driver'))->toBe('anthropic');
    expect(config('ai.default'))->toBe('byok');

    // Second call for a non-BYOK user should clear BYOK config
    $service->configureRuntimeProvider($creditsUser);
    expect(config('ai.providers.byok'))->toBeNull();
    expect(config('ai.default'))->toBe(['anthropic', 'gemini']);
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
