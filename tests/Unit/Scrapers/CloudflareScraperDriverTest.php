<?php

use App\Services\Scrapers\CloudflareScraperDriver;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

function realisticJobMarkdown(): string
{
    return <<<'MD'
    # Senior Software Engineer

    ## About Us

    Acme Corp is a leading technology company building innovative solutions for the modern workforce.

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

test('scrape returns immediately when Phase 1 returns sufficient content', function () {
    $sufficientContent = realisticJobMarkdown();

    Http::fake([
        'api.cloudflare.com/*' => Http::response([
            'result' => $sufficientContent,
        ]),
    ]);

    $driver = new CloudflareScraperDriver;

    expect($driver->scrape('https://example.com'))->toBe($sufficientContent);

    Http::assertSentCount(1);

    Http::assertSent(function ($request) {
        $body = $request->data();

        return $body['gotoOptions']['waitUntil'] === 'domcontentloaded'
            && $body['rejectResourceTypes'] === ['image', 'font', 'media', 'stylesheet'];
    });
});

test('scrape falls through to Phase 2 when Phase 1 returns shell content', function () {
    $shortContent = "Skip to content\nSign in\nLoading...\n© 2024 Workday, Inc. All rights reserved.";
    $fullContent = realisticJobMarkdown();

    Http::fakeSequence('api.cloudflare.com/*')
        ->push(['result' => $shortContent])
        ->push(['result' => $fullContent]);

    $driver = new CloudflareScraperDriver;

    expect($driver->scrape('https://example.com'))->toBe($fullContent);

    Http::assertSentCount(2);

    $requests = Http::recorded();

    // Phase 1 request
    expect($requests[0][0]->data()['gotoOptions']['waitUntil'])->toBe('domcontentloaded');
    expect($requests[0][0]->data()['rejectResourceTypes'])->toBe(['image', 'font', 'media', 'stylesheet']);

    // Phase 2 request — waitForSelector h1, keeps stylesheets
    expect($requests[1][0]->data()['gotoOptions']['waitUntil'])->toBe('domcontentloaded');
    expect($requests[1][0]->data()['waitForSelector'])->toBe(['selector' => 'h1', 'timeout' => 30000]);
    expect($requests[1][0]->data()['rejectResourceTypes'])->toBe(['image', 'font', 'media']);
});

test('scrape returns null when both phases return empty', function () {
    Http::fakeSequence('api.cloudflare.com/*')
        ->push(['result' => ''])
        ->push(['result' => '']);

    $driver = new CloudflareScraperDriver;

    expect($driver->scrape('https://example.com'))->toBeNull();

    Http::assertSentCount(2);
});

test('scrape returns Phase 2 short content when Phase 1 is empty and Phase 2 is short', function () {
    $shortContent = 'short';

    Http::fakeSequence('api.cloudflare.com/*')
        ->push(['result' => ''])
        ->push(['result' => $shortContent]);

    $driver = new CloudflareScraperDriver;

    expect($driver->scrape('https://example.com'))->toBe($shortContent);
});

test('scrape returns null on HTTP failure in both phases', function () {
    Http::fakeSequence('api.cloudflare.com/*')
        ->push([], 500)
        ->push([], 500);

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

test('discoverLinks uses networkidle0 wait strategy with no cache', function () {
    Http::fake([
        'api.cloudflare.com/*' => Http::response(['result' => []]),
    ]);

    $driver = new CloudflareScraperDriver;
    $driver->discoverLinks('https://example.com');

    Http::assertSent(function ($request) {
        $body = $request->data();

        return $body['gotoOptions']['waitUntil'] === 'networkidle0'
            && $body['rejectResourceTypes'] === ['image', 'font', 'media'];
    });
});

test('discoverLinks returns null on HTTP failure', function () {
    Http::fake([
        'api.cloudflare.com/*' => Http::response([], 500),
    ]);

    $driver = new CloudflareScraperDriver;

    expect($driver->discoverLinks('https://example.com'))->toBeNull();
});
