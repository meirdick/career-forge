<?php

use App\Models\User;

function signWebhookPayload(string $payload, string $secret = '', string $webhookId = 'msg_test123', string $timestamp = ''): array
{
    if (! $timestamp) {
        $timestamp = (string) time();
    }

    if (! $secret) {
        $secret = 'whsec_'.base64_encode(random_bytes(32));
    }

    $secretBytes = base64_decode(str_replace('whsec_', '', $secret));
    $signedContent = "{$webhookId}.{$timestamp}.{$payload}";
    $signature = 'v1,'.base64_encode(hash_hmac('sha256', $signedContent, $secretBytes, true));

    return [
        'headers' => [
            'webhook-id' => $webhookId,
            'webhook-timestamp' => $timestamp,
            'webhook-signature' => $signature,
        ],
        'secret' => $secret,
    ];
}

test('polar webhook handles order.created event', function () {
    $user = User::factory()->create();

    $payload = json_encode([
        'type' => 'order.created',
        'data' => [
            'id' => 'polar-order-abc',
            'metadata' => [
                'user_id' => (string) $user->id,
            ],
        ],
    ]);

    $signed = signWebhookPayload($payload);
    config(['services.polar.webhook_secret' => $signed['secret']]);

    $response = $this->call('POST', route('polar.webhook'), [], [], [], [
        'HTTP_WEBHOOK_ID' => $signed['headers']['webhook-id'],
        'HTTP_WEBHOOK_TIMESTAMP' => $signed['headers']['webhook-timestamp'],
        'HTTP_WEBHOOK_SIGNATURE' => $signed['headers']['webhook-signature'],
        'CONTENT_TYPE' => 'application/json',
    ], $payload);

    $response->assertOk();

    $user->refresh();
    expect($user->creditBalance->balance)->toBe(500);
    expect($user->creditTransactions)->toHaveCount(1);
    expect($user->creditTransactions->first()->polar_order_id)->toBe('polar-order-abc');
});

test('polar webhook rejects invalid signature', function () {
    config(['services.polar.webhook_secret' => 'whsec_'.base64_encode(random_bytes(32))]);

    $payload = json_encode([
        'type' => 'order.created',
        'data' => ['id' => 'polar-order-abc', 'metadata' => ['user_id' => '1']],
    ]);

    $response = $this->call('POST', route('polar.webhook'), [], [], [], [
        'HTTP_WEBHOOK_ID' => 'msg_test123',
        'HTTP_WEBHOOK_TIMESTAMP' => (string) time(),
        'HTTP_WEBHOOK_SIGNATURE' => 'v1,invalidsignature',
        'CONTENT_TYPE' => 'application/json',
    ], $payload);

    $response->assertStatus(403);
});

test('polar webhook skips verification when no secret configured', function () {
    $user = User::factory()->create();

    config(['services.polar.webhook_secret' => '']);

    $response = $this->postJson(route('polar.webhook'), [
        'type' => 'order.created',
        'data' => [
            'id' => 'polar-order-abc',
            'metadata' => [
                'user_id' => (string) $user->id,
            ],
        ],
    ]);

    $response->assertOk();
    $user->refresh();
    expect($user->creditBalance->balance)->toBe(500);
});

test('polar webhook ignores non-order events', function () {
    config(['services.polar.webhook_secret' => '']);

    $response = $this->postJson(route('polar.webhook'), [
        'type' => 'subscription.created',
        'data' => [],
    ]);

    $response->assertOk();
});

test('polar webhook handles missing user gracefully', function () {
    config(['services.polar.webhook_secret' => '']);

    $response = $this->postJson(route('polar.webhook'), [
        'type' => 'order.created',
        'data' => [
            'id' => 'polar-order-abc',
            'metadata' => [
                'user_id' => '99999',
            ],
        ],
    ]);

    $response->assertOk();
});

test('polar webhook handles missing metadata gracefully', function () {
    config(['services.polar.webhook_secret' => '']);

    $response = $this->postJson(route('polar.webhook'), [
        'type' => 'order.created',
        'data' => [
            'id' => 'polar-order-abc',
        ],
    ]);

    $response->assertOk();
});
