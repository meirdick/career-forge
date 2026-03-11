<?php

use App\Contracts\WebScraperContract;
use App\Services\WebScraperService;

uses(Tests\TestCase::class);

function mockDriver(bool $configured, ?array $links = null, ?string $markdown = null): WebScraperContract
{
    $driver = Mockery::mock(WebScraperContract::class);
    $driver->shouldReceive('isConfigured')->andReturn($configured);

    if ($configured) {
        $driver->shouldReceive('discoverLinks')->andReturn($links)->byDefault();
        $driver->shouldReceive('scrape')->andReturn($markdown)->byDefault();
    }

    return $driver;
}

test('uses primary driver when it succeeds', function () {
    $primary = mockDriver(true, [['url' => 'https://example.com/about']], '# Primary');
    $secondary = mockDriver(true, [['url' => 'https://example.com/fallback']], '# Secondary');

    $service = new WebScraperService([$primary, $secondary]);

    expect($service->discoverLinks('https://example.com'))->toBe([['url' => 'https://example.com/about']]);
    expect($service->scrape('https://example.com'))->toBe('# Primary');

    $secondary->shouldNotHaveReceived('discoverLinks');
    $secondary->shouldNotHaveReceived('scrape');
});

test('falls back to secondary when primary fails', function () {
    $primary = mockDriver(true, null, null);
    $secondary = mockDriver(true, [['url' => 'https://example.com/fallback']], '# Fallback');

    $service = new WebScraperService([$primary, $secondary]);

    expect($service->discoverLinks('https://example.com'))->toBe([['url' => 'https://example.com/fallback']]);
    expect($service->scrape('https://example.com'))->toBe('# Fallback');
});

test('returns null when all drivers fail', function () {
    $primary = mockDriver(true, null, null);
    $secondary = mockDriver(true, null, null);

    $service = new WebScraperService([$primary, $secondary]);

    expect($service->discoverLinks('https://example.com'))->toBeNull();
    expect($service->scrape('https://example.com'))->toBeNull();
});

test('skips unconfigured drivers', function () {
    $unconfigured = mockDriver(false);
    $configured = mockDriver(true, [['url' => 'https://example.com/page']], '# Content');

    $service = new WebScraperService([$unconfigured, $configured]);

    expect($service->discoverLinks('https://example.com'))->toBe([['url' => 'https://example.com/page']]);
    expect($service->scrape('https://example.com'))->toBe('# Content');
});

test('returns null when no drivers are configured', function () {
    $service = new WebScraperService([
        mockDriver(false),
        mockDriver(false),
    ]);

    expect($service->discoverLinks('https://example.com'))->toBeNull();
    expect($service->scrape('https://example.com'))->toBeNull();
});
