<?php

use App\Services\Scrapers\JsonLdScraperDriver;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

test('is always configured', function () {
    $driver = new JsonLdScraperDriver;

    expect($driver->isConfigured())->toBeTrue();
});

test('discoverLinks always returns null', function () {
    $driver = new JsonLdScraperDriver;

    expect($driver->discoverLinks('https://example.com'))->toBeNull();
});

test('extracts job posting from json-ld script tag', function () {
    $jsonLd = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'JobPosting',
        'title' => 'Frontend Developer',
        'description' => '<p>Build beautiful user interfaces with our talented team of designers and engineers.</p>',
        'hiringOrganization' => [
            'name' => 'TechCo',
        ],
        'jobLocation' => [
            'address' => [
                'addressLocality' => 'New York',
                'addressRegion' => 'NY',
                'addressCountry' => 'US',
            ],
        ],
        'employmentType' => 'FULL_TIME',
        'baseSalary' => [
            'currency' => 'USD',
            'value' => [
                'minValue' => 100000,
                'maxValue' => 150000,
                'unitText' => 'YEAR',
            ],
        ],
    ]);

    Http::fake([
        'https://careers.techco.com/job/123' => Http::response(
            '<html><head><script type="application/ld+json">'.$jsonLd.'</script></head><body></body></html>'
        ),
    ]);

    $driver = new JsonLdScraperDriver;
    $result = $driver->scrape('https://careers.techco.com/job/123');

    expect($result)
        ->toContain('# Frontend Developer')
        ->toContain('**Company:** TechCo')
        ->toContain('New York, NY, US')
        ->toContain('Full-time')
        ->toContain('USD 100000 - 150000 per YEAR');
});

test('returns null when page has no json-ld', function () {
    Http::fake([
        'https://example.com/job' => Http::response('<html><body><h1>Job</h1></body></html>'),
    ]);

    $driver = new JsonLdScraperDriver;

    expect($driver->scrape('https://example.com/job'))->toBeNull();
});

test('returns null when json-ld has no JobPosting type', function () {
    $jsonLd = json_encode([
        '@type' => 'Organization',
        'name' => 'TechCo',
    ]);

    Http::fake([
        'https://example.com/job' => Http::response(
            '<html><head><script type="application/ld+json">'.$jsonLd.'</script></head></html>'
        ),
    ]);

    $driver = new JsonLdScraperDriver;

    expect($driver->scrape('https://example.com/job'))->toBeNull();
});

test('finds JobPosting in @graph array', function () {
    $jsonLd = json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            ['@type' => 'Organization', 'name' => 'Corp'],
            [
                '@type' => 'JobPosting',
                'title' => 'Data Scientist',
                'description' => 'Analyze data and build models for production systems at scale.',
            ],
        ],
    ]);

    Http::fake([
        'https://example.com/job' => Http::response(
            '<html><head><script type="application/ld+json">'.$jsonLd.'</script></head></html>'
        ),
    ]);

    $driver = new JsonLdScraperDriver;
    $result = $driver->scrape('https://example.com/job');

    expect($result)->toContain('# Data Scientist');
});

test('finds JobPosting in top-level array', function () {
    $jsonLd = json_encode([
        ['@type' => 'BreadcrumbList'],
        [
            '@type' => 'JobPosting',
            'title' => 'DevOps Engineer',
            'description' => 'Manage infrastructure and deployment pipelines.',
        ],
    ]);

    Http::fake([
        'https://example.com/job' => Http::response(
            '<html><head><script type="application/ld+json">'.$jsonLd.'</script></head></html>'
        ),
    ]);

    $driver = new JsonLdScraperDriver;
    $result = $driver->scrape('https://example.com/job');

    expect($result)->toContain('# DevOps Engineer');
});

test('returns null when http request fails', function () {
    Http::fake([
        'https://example.com/job' => Http::response(null, 500),
    ]);

    $driver = new JsonLdScraperDriver;

    expect($driver->scrape('https://example.com/job'))->toBeNull();
});

test('extracts qualifications and responsibilities sections', function () {
    $jsonLd = json_encode([
        '@type' => 'JobPosting',
        'title' => 'Engineer',
        'description' => '<p>Join our team of talented engineers building the future of technology.</p>',
        'qualifications' => 'BS in Computer Science or equivalent experience required.',
        'responsibilities' => 'Design and implement scalable backend services for our platform.',
    ]);

    Http::fake([
        'https://example.com/job' => Http::response(
            '<html><head><script type="application/ld+json">'.$jsonLd.'</script></head></html>'
        ),
    ]);

    $driver = new JsonLdScraperDriver;
    $result = $driver->scrape('https://example.com/job');

    expect($result)
        ->toContain('## Qualifications')
        ->toContain('## Responsibilities');
});

test('normalizes employment type values', function () {
    $jsonLd = json_encode([
        '@type' => 'JobPosting',
        'title' => 'Intern',
        'description' => 'Summer internship program for students pursuing computer science degrees.',
        'employmentType' => 'INTERN',
    ]);

    Http::fake([
        'https://example.com/job' => Http::response(
            '<html><head><script type="application/ld+json">'.$jsonLd.'</script></head></html>'
        ),
    ]);

    $driver = new JsonLdScraperDriver;
    $result = $driver->scrape('https://example.com/job');

    expect($result)->toContain('**Employment Type:** Internship');
});

test('handles string hiring organization', function () {
    $jsonLd = json_encode([
        '@type' => 'JobPosting',
        'title' => 'Manager',
        'description' => 'Lead a team of engineers building innovative products and solutions.',
        'hiringOrganization' => 'SimpleCo',
    ]);

    Http::fake([
        'https://example.com/job' => Http::response(
            '<html><head><script type="application/ld+json">'.$jsonLd.'</script></head></html>'
        ),
    ]);

    $driver = new JsonLdScraperDriver;
    $result = $driver->scrape('https://example.com/job');

    expect($result)->toContain('**Company:** SimpleCo');
});
