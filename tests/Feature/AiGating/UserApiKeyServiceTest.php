<?php

use App\Models\User;
use App\Models\UserApiKey;
use App\Services\UserApiKeyService;

test('store creates a new api key', function () {
    $user = User::factory()->create();
    $service = app(UserApiKeyService::class);

    $apiKey = $service->store($user, 'anthropic', 'sk-test-key');

    expect($apiKey->provider)->toBe('anthropic');
    expect($apiKey->is_active)->toBeFalse();
    expect($apiKey->user_id)->toBe($user->id);
});

test('store updates existing key for same provider', function () {
    $user = User::factory()->create();
    $service = app(UserApiKeyService::class);

    $service->store($user, 'anthropic', 'sk-old-key');
    $apiKey = $service->store($user, 'anthropic', 'sk-new-key');

    expect(UserApiKey::where('user_id', $user->id)->where('provider', 'anthropic')->count())->toBe(1);
    expect($apiKey->encrypted_key)->toBe('sk-new-key');
});

test('activate sets key as active and deactivates others', function () {
    $user = User::factory()->create();
    $key1 = UserApiKey::factory()->active()->create(['user_id' => $user->id, 'provider' => 'anthropic']);
    $key2 = UserApiKey::factory()->create(['user_id' => $user->id, 'provider' => 'openai']);

    $service = app(UserApiKeyService::class);
    $service->activate($key2);

    $key1->refresh();
    $key2->refresh();

    expect($key1->is_active)->toBeFalse();
    expect($key2->is_active)->toBeTrue();
    expect($key2->validated_at)->not->toBeNull();
});

test('deactivate deactivates all user keys', function () {
    $user = User::factory()->create();
    UserApiKey::factory()->active()->create(['user_id' => $user->id, 'provider' => 'anthropic']);
    UserApiKey::factory()->active()->create(['user_id' => $user->id, 'provider' => 'openai']);

    $service = app(UserApiKeyService::class);
    $service->deactivate($user);

    expect($user->apiKeys()->where('is_active', true)->count())->toBe(0);
});
