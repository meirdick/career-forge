<?php

use App\Contracts\WebScraperContract;
use App\Services\WebScraperService;

uses(Tests\TestCase::class);

function validJobContent(): string
{
    return <<<'MD'
    # Senior Software Engineer

    ## About Us

    Acme Corp is a leading technology company building innovative solutions for the modern workforce. Our team is growing fast.

    ## Responsibilities

    - Design and implement scalable microservices architecture
    - Lead code reviews and mentor junior engineers on the team
    - Collaborate with product managers to define technical requirements

    ## Requirements

    - 5+ years of professional software engineering experience
    - Strong proficiency in Python, Go, or similar languages
    - Experience with distributed systems and cloud platforms

    ## Benefits

    - Competitive salary range of $150,000 - $200,000 per year
    - Comprehensive health, dental, and vision insurance

    ## How to Apply

    Submit your resume and a brief cover letter explaining why you are interested in this role.
    MD;
}

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
    $primaryContent = validJobContent();
    $secondaryContent = validJobContent();

    $primary = mockDriver(true, [['url' => 'https://example.com/about']], $primaryContent);
    $secondary = mockDriver(true, [['url' => 'https://example.com/fallback']], $secondaryContent);

    $service = new WebScraperService([$primary, $secondary]);

    expect($service->discoverLinks('https://example.com'))->toBe([['url' => 'https://example.com/about']]);
    expect($service->scrape('https://example.com'))->toBe($primaryContent);

    $secondary->shouldNotHaveReceived('discoverLinks');
    $secondary->shouldNotHaveReceived('scrape');
});

test('falls back to secondary when primary fails', function () {
    $fallbackContent = validJobContent();

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
    $content = validJobContent();

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
    $secondary = mockDriver(true, null, validJobContent());

    $service = new WebScraperService([$primary, $secondary]);

    expect($service->scrape('https://example.com'))->toBe(validJobContent());
});

test('falls back when primary returns empty array for discoverLinks', function () {
    $primary = mockDriver(true, [], null);
    $secondary = mockDriver(true, [['url' => 'https://example.com/page']], null);

    $service = new WebScraperService([$primary, $secondary]);

    expect($service->discoverLinks('https://example.com'))->toBe([['url' => 'https://example.com/page']]);
});

test('falls back when primary returns short content below quality threshold', function () {
    $shortContent = 'nav only - cookie banner';
    $fullContent = validJobContent();

    $primary = mockDriver(true, null, $shortContent);
    $secondary = mockDriver(true, null, $fullContent);

    $service = new WebScraperService([$primary, $secondary]);

    expect($service->scrape('https://example.com'))->toBe($fullContent);
});

test('returns highest-scoring content when all drivers return insufficient content', function () {
    $lowQuality = "Skip to content\nSign in\nLoading...\nCopyright 2024";
    $betterQuality = "# Software Engineer\n\n## Requirements\n\nWe need someone with experience in distributed systems.\nThe ideal candidate has strong skills in Python or Go.\nThis role involves working with our platform team on challenging problems.";

    $primary = mockDriver(true, null, $lowQuality);
    $secondary = mockDriver(true, null, $betterQuality);

    $service = new WebScraperService([$primary, $secondary]);

    expect($service->scrape('https://example.com'))->toBe($betterQuality);
});

test('returns primary short content when secondary returns null', function () {
    $shortContent = 'nav only';

    $primary = mockDriver(true, null, $shortContent);
    $secondary = mockDriver(true, null, null);

    $service = new WebScraperService([$primary, $secondary]);

    expect($service->scrape('https://example.com'))->toBe($shortContent);
});

test('skips browser drivers when ats driver returns valid content', function () {
    $atsContent = validJobContent();

    $atsDriver = mockDriver(true, null, $atsContent);
    $jsonLdDriver = mockDriver(true, null, validJobContent());
    $cloudflare = mockDriver(true, null, validJobContent());
    $firecrawl = mockDriver(true, null, validJobContent());

    $service = new WebScraperService([$atsDriver, $jsonLdDriver, $cloudflare, $firecrawl]);

    expect($service->scrape('https://boards.greenhouse.io/company/jobs/123'))->toBe($atsContent);

    $jsonLdDriver->shouldNotHaveReceived('scrape');
    $cloudflare->shouldNotHaveReceived('scrape');
    $firecrawl->shouldNotHaveReceived('scrape');
});

test('falls back to jsonld when ats driver returns null', function () {
    $jsonLdContent = validJobContent();

    $atsDriver = mockDriver(true, null, null);
    $jsonLdDriver = mockDriver(true, null, $jsonLdContent);
    $cloudflare = mockDriver(true, null, validJobContent());

    $service = new WebScraperService([$atsDriver, $jsonLdDriver, $cloudflare]);

    expect($service->scrape('https://example.com/job'))->toBe($jsonLdContent);

    $cloudflare->shouldNotHaveReceived('scrape');
});

test('falls back when primary returns shell-only content above 200 chars', function () {
    $shellContent = "Skip to content\nSign in\nLoading...\n© 2024 Workday, Inc. All rights reserved.\n"
        ."Privacy\nTerms of Use\nCookie Preferences\nPowered by Workday\n"
        ."We use cookies to ensure you get the best experience.\nAccept All Cookies\n"
        ."Loading application content, please wait...\nIf this page does not load, please refresh.";
    $fullContent = validJobContent();

    $primary = mockDriver(true, null, $shellContent);
    $secondary = mockDriver(true, null, $fullContent);

    $service = new WebScraperService([$primary, $secondary]);

    // Shell content is above 200 chars but should still fail quality check
    expect(mb_strlen($shellContent))->toBeGreaterThan(200);
    expect($service->scrape('https://example.com'))->toBe($fullContent);
});
