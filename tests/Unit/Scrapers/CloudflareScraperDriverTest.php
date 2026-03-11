<?php

use App\Services\Scrapers\CloudflareScraperDriver;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

beforeEach(function () {
    config()->set('services.cloudflare_browser.account_id', 'test-account-id');
    config()->set('services.cloudflare_browser.api_token', 'test-api-token');
    config()->set('services.cloudflare_browser.base_url', 'https://api.cloudflare.com/client/v4');
});

test('isConfigured returns true when account_id and api_token are set', function () {
    $driver = new CloudflareScraperDriver;

    expect($driver->isConfigured())->toBeTrue();
});

test('isConfigured returns false when account_id is missing', function () {
    config()->set('services.cloudflare_browser.account_id', null);

    $driver = new CloudflareScraperDriver;

    expect($driver->isConfigured())->toBeFalse();
});

test('isConfigured returns false when api_token is missing', function () {
    config()->set('services.cloudflare_browser.api_token', null);

    $driver = new CloudflareScraperDriver;

    expect($driver->isConfigured())->toBeFalse();
});

test('scrape returns markdown from result field', function () {
    Http::fake([
        'api.cloudflare.com/*' => Http::response([
            'result' => '# Hello World',
        ]),
    ]);

    $driver = new CloudflareScraperDriver;

    expect($driver->scrape('https://example.com'))->toBe('# Hello World');
});

test('scrape returns null on HTTP failure', function () {
    Http::fake([
        'api.cloudflare.com/*' => Http::response([], 500),
    ]);

    $driver = new CloudflareScraperDriver;

    expect($driver->scrape('https://example.com'))->toBeNull();
});

test('discoverLinks returns filtered same-domain links', function () {
    Http::fake([
        'api.cloudflare.com/*' => Http::response([
            'result' => [
                'https://example.com/about',
                'https://example.com/projects',
                'https://other.com/external',
                'https://example.com',
            ],
        ]),
    ]);

    $driver = new CloudflareScraperDriver;
    $links = $driver->discoverLinks('https://example.com');

    expect($links)->toBe([
        ['url' => 'https://example.com/about'],
        ['url' => 'https://example.com/projects'],
    ]);
});

test('discoverLinks returns null on HTTP failure', function () {
    Http::fake([
        'api.cloudflare.com/*' => Http::response([], 500),
    ]);

    $driver = new CloudflareScraperDriver;

    expect($driver->discoverLinks('https://example.com'))->toBeNull();
});
