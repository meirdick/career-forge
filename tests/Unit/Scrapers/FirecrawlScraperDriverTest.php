<?php

use App\Services\Scrapers\FirecrawlScraperDriver;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

beforeEach(function () {
    config()->set('services.firecrawl.api_key', 'test-api-key');
    config()->set('services.firecrawl.base_url', 'https://api.firecrawl.dev');
});

test('isConfigured returns true when api_key is set', function () {
    $driver = new FirecrawlScraperDriver;

    expect($driver->isConfigured())->toBeTrue();
});

test('isConfigured returns false when api_key is missing', function () {
    config()->set('services.firecrawl.api_key', null);

    $driver = new FirecrawlScraperDriver;

    expect($driver->isConfigured())->toBeFalse();
});

test('scrape returns markdown from response', function () {
    Http::fake([
        'api.firecrawl.dev/*' => Http::response([
            'success' => true,
            'data' => ['markdown' => '# Hello World'],
        ]),
    ]);

    $driver = new FirecrawlScraperDriver;

    expect($driver->scrape('https://example.com'))->toBe('# Hello World');
});

test('scrape returns null on failure', function () {
    Http::fake([
        'api.firecrawl.dev/*' => Http::response([
            'success' => false,
            'error' => 'Something went wrong',
        ]),
    ]);

    $driver = new FirecrawlScraperDriver;

    expect($driver->scrape('https://example.com'))->toBeNull();
});

test('discoverLinks returns filtered same-domain links', function () {
    Http::fake([
        'api.firecrawl.dev/*' => Http::response([
            'success' => true,
            'links' => [
                'https://example.com/about',
                'https://example.com/projects',
                'https://other.com/external',
                'https://example.com',
            ],
        ]),
    ]);

    $driver = new FirecrawlScraperDriver;
    $links = $driver->discoverLinks('https://example.com');

    expect($links)->toBe([
        ['url' => 'https://example.com/about'],
        ['url' => 'https://example.com/projects'],
    ]);
});

test('discoverLinks returns null on failure', function () {
    Http::fake([
        'api.firecrawl.dev/*' => Http::response([
            'success' => false,
            'error' => 'Something went wrong',
        ]),
    ]);

    $driver = new FirecrawlScraperDriver;

    expect($driver->discoverLinks('https://example.com'))->toBeNull();
});
