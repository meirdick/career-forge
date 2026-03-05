<?php

use App\Services\WebScraperService;

test('detects linkedin.com as unsupported', function () {
    expect(WebScraperService::isUnsupportedUrl('https://linkedin.com/jobs/123'))->toBeTrue();
});

test('detects www.linkedin.com as unsupported', function () {
    expect(WebScraperService::isUnsupportedUrl('https://www.linkedin.com/jobs/view/456'))->toBeTrue();
});

test('detects subdomain.linkedin.com as unsupported', function () {
    expect(WebScraperService::isUnsupportedUrl('https://uk.linkedin.com/jobs/789'))->toBeTrue();
});

test('allows supported urls', function () {
    expect(WebScraperService::isUnsupportedUrl('https://example.com/jobs/123'))->toBeFalse();
    expect(WebScraperService::isUnsupportedUrl('https://greenhouse.io/jobs/456'))->toBeFalse();
    expect(WebScraperService::isUnsupportedUrl('https://notlinkedin.com/jobs/789'))->toBeFalse();
});

test('handles invalid urls gracefully', function () {
    expect(WebScraperService::isUnsupportedUrl('not-a-url'))->toBeFalse();
    expect(WebScraperService::isUnsupportedUrl(''))->toBeFalse();
});
