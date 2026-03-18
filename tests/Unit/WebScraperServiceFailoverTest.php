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
    $primaryContent = str_repeat('Primary content. ', 20);
    $secondaryContent = str_repeat('Secondary content. ', 20);

    $primary = mockDriver(true, [['url' => 'https://example.com/about']], $primaryContent);
    $secondary = mockDriver(true, [['url' => 'https://example.com/fallback']], $secondaryContent);

    $service = new WebScraperService([$primary, $secondary]);

    expect($service->discoverLinks('https://example.com'))->toBe([['url' => 'https://example.com/about']]);
    expect($service->scrape('https://example.com'))->toBe($primaryContent);

    $secondary->shouldNotHaveReceived('discoverLinks');
    $secondary->shouldNotHaveReceived('scrape');
});

test('falls back to secondary when primary fails', function () {
    $fallbackContent = str_repeat('Fallback content. ', 20);

    $primary = mockDriver(true, null, null);
    $secondary = mockDriver(true, [['url' => 'https://example.com/fallback']], $fallbackContent);

    $service = new WebScraperService([$primary, $secondary]);

    expect($service->discoverLinks('https://example.com'))->toBe([['url' => 'https://example.com/fallback']]);
    expect($service->scrape('https://example.com'))->toBe($fallbackContent);
});

test('returns null when all drivers fail', function () {
    $primary = mockDriver(true, null, null);
    $secondary = mockDriver(true, null, null);

    $service = new WebScraperService([$primary, $secondary]);

    expect($service->discoverLinks('https://example.com'))->toBeNull();
    expect($service->scrape('https://example.com'))->toBeNull();
});

test('skips unconfigured drivers', function () {
    $content = str_repeat('Content here. ', 20);

    $unconfigured = mockDriver(false);
    $configured = mockDriver(true, [['url' => 'https://example.com/page']], $content);

    $service = new WebScraperService([$unconfigured, $configured]);

    expect($service->discoverLinks('https://example.com'))->toBe([['url' => 'https://example.com/page']]);
    expect($service->scrape('https://example.com'))->toBe($content);
});

test('returns null when no drivers are configured', function () {
    $service = new WebScraperService([
        mockDriver(false),
        mockDriver(false),
    ]);

    expect($service->discoverLinks('https://example.com'))->toBeNull();
    expect($service->scrape('https://example.com'))->toBeNull();
});

test('falls back when primary returns empty string for scrape', function () {
    $primary = mockDriver(true, null, '');
    $secondary = mockDriver(true, null, str_repeat('x', 200));

    $service = new WebScraperService([$primary, $secondary]);

    expect($service->scrape('https://example.com'))->toBe(str_repeat('x', 200));
});

test('falls back when primary returns empty array for discoverLinks', function () {
    $primary = mockDriver(true, [], null);
    $secondary = mockDriver(true, [['url' => 'https://example.com/page']], null);

    $service = new WebScraperService([$primary, $secondary]);

    expect($service->discoverLinks('https://example.com'))->toBe([['url' => 'https://example.com/page']]);
});

test('falls back when primary returns short content below quality threshold', function () {
    $shortContent = 'nav only - cookie banner';
    $fullContent = str_repeat('Full job posting content. ', 20);

    $primary = mockDriver(true, null, $shortContent);
    $secondary = mockDriver(true, null, $fullContent);

    $service = new WebScraperService([$primary, $secondary]);

    expect($service->scrape('https://example.com'))->toBe($fullContent);
});

test('returns longest short content when all drivers return insufficient content', function () {
    $short = 'short';
    $longer = str_repeat('a', 100);

    $primary = mockDriver(true, null, $short);
    $secondary = mockDriver(true, null, $longer);

    $service = new WebScraperService([$primary, $secondary]);

    expect($service->scrape('https://example.com'))->toBe($longer);
});

test('returns primary short content when secondary returns null', function () {
    $shortContent = 'nav only';

    $primary = mockDriver(true, null, $shortContent);
    $secondary = mockDriver(true, null, null);

    $service = new WebScraperService([$primary, $secondary]);

    expect($service->scrape('https://example.com'))->toBe($shortContent);
});
