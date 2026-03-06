<?php

use App\Models\User;
use App\Models\UserApiKey;
use App\Services\UserApiKeyService;

test('api keys page is displayed when gating enabled', function () {
    config(['ai.gating.mode' => 'gated']);

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('api-keys.show'));

    $response->assertOk();
});

test('api key can be stored and activated', function () {
    config(['ai.gating.mode' => 'gated']);

    $user = User::factory()->create();

    $this->mock(UserApiKeyService::class, function ($mock) {
        $mock->shouldReceive('validate')
            ->once()
            ->andReturn(true);

        $mock->shouldReceive('store')
            ->once()
            ->andReturn(UserApiKey::factory()->make(['id' => 1, 'user_id' => 1]));

        $mock->shouldReceive('activate')
            ->once();
    });

    $response = $this->actingAs($user)
        ->post(route('api-keys.store'), [
            'provider' => 'anthropic',
            'api_key' => 'sk-ant-test-key-123456',
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
});

test('invalid api key returns error', function () {
    config(['ai.gating.mode' => 'gated']);

    $user = User::factory()->create();

    $this->mock(UserApiKeyService::class, function ($mock) {
        $mock->shouldReceive('validate')
            ->once()
            ->andReturn(false);
    });

    $response = $this->actingAs($user)
        ->post(route('api-keys.store'), [
            'provider' => 'anthropic',
            'api_key' => 'invalid-key',
        ]);

    $response->assertSessionHasErrors('api_key');
});

test('api key can be deleted', function () {
    config(['ai.gating.mode' => 'gated']);

    $user = User::factory()->create();
    $apiKey = UserApiKey::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->delete(route('api-keys.destroy', $apiKey->id));

    $response->assertRedirect();
    expect(UserApiKey::find($apiKey->id))->toBeNull();
});

test('cannot delete another users api key', function () {
    config(['ai.gating.mode' => 'gated']);

    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $apiKey = UserApiKey::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)
        ->delete(route('api-keys.destroy', $apiKey->id));

    $response->assertNotFound();
});

test('invalid provider is rejected', function () {
    config(['ai.gating.mode' => 'gated']);

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('api-keys.store'), [
            'provider' => 'invalid-provider',
            'api_key' => 'sk-test-key',
        ]);

    $response->assertSessionHasErrors('provider');
});
