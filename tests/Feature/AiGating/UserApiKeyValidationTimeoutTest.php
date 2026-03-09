<?php

use App\Services\UserApiKeyService;
use Illuminate\Support\Facades\Http;

test('anthropic validation sends request and returns result', function () {
    Http::fake(['api.anthropic.com/*' => Http::response(['type' => 'message'], 200)]);

    $service = app(UserApiKeyService::class);

    expect($service->validate('anthropic', 'sk-test-key'))->toBeTrue();

    Http::assertSent(fn ($request) => $request->url() === 'https://api.anthropic.com/v1/messages');
});

test('openai validation sends request and returns result', function () {
    Http::fake(['api.openai.com/*' => Http::response(['data' => []], 200)]);

    $service = app(UserApiKeyService::class);

    expect($service->validate('openai', 'sk-test-key'))->toBeTrue();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.openai.com'));
});

test('gemini validation sends request and returns result', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['models' => []], 200)]);

    $service = app(UserApiKeyService::class);

    expect($service->validate('gemini', 'test-key'))->toBeTrue();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'generativelanguage.googleapis.com'));
});

test('groq validation sends request and returns result', function () {
    Http::fake(['api.groq.com/*' => Http::response(['data' => []], 200)]);

    $service = app(UserApiKeyService::class);

    expect($service->validate('groq', 'gsk-test-key'))->toBeTrue();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.groq.com'));
});

test('validation returns false for failed responses', function (string $provider, string $key, string $urlPattern) {
    Http::fake([$urlPattern => Http::response([], 401)]);

    $service = app(UserApiKeyService::class);

    expect($service->validate($provider, $key))->toBeFalse();
})->with([
    'anthropic' => ['anthropic', 'bad-key', 'api.anthropic.com/*'],
    'openai' => ['openai', 'bad-key', 'api.openai.com/*'],
    'gemini' => ['gemini', 'bad-key', 'generativelanguage.googleapis.com/*'],
    'groq' => ['groq', 'bad-key', 'api.groq.com/*'],
]);

test('validation returns false for unknown provider', function () {
    $service = app(UserApiKeyService::class);

    expect($service->validate('unknown', 'some-key'))->toBeFalse();
});
