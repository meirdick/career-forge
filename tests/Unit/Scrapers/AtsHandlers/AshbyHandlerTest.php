<?php

use App\Services\Scrapers\AtsHandlers\AshbyHandler;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

test('canHandle matches jobs.ashbyhq.com', function () {
    $handler = new AshbyHandler;

    expect($handler->canHandle('https://jobs.ashbyhq.com/notion/abc-123'))->toBeTrue();
    expect($handler->canHandle('https://jobs.ashbyhq.com/company/posting'))->toBeTrue();
    expect($handler->canHandle('https://ashbyhq.com/jobs/123'))->toBeFalse();
    expect($handler->canHandle('https://example.com/jobs'))->toBeFalse();
});

test('extracts job data from ashby api', function () {
    Http::fake([
        'api.ashbyhq.com/posting-api/job-board/notion' => Http::response([
            'organizationName' => 'Notion',
            'jobs' => [
                [
                    'id' => 'abc-123',
                    'title' => 'Product Designer',
                    'location' => 'New York, NY',
                    'departmentName' => 'Design',
                    'employmentType' => 'Full-time',
                    'descriptionHtml' => '<p>Design beautiful products that millions of people use.</p>',
                    'compensationTierSummary' => '$130,000 - $180,000',
                ],
                [
                    'id' => 'other-456',
                    'title' => 'Other Role',
                ],
            ],
        ]),
    ]);

    $handler = new AshbyHandler;
    $result = $handler->extract('https://jobs.ashbyhq.com/notion/abc-123');

    expect($result)
        ->not->toBeNull()
        ->and($result['title'])->toBe('Product Designer')
        ->and($result['company'])->toBe('Notion')
        ->and($result['location'])->toBe('New York, NY')
        ->and($result['department'])->toBe('Design')
        ->and($result['employment_type'])->toBe('Full-time')
        ->and($result['compensation'])->toBe('$130,000 - $180,000');
});

test('returns null for invalid url pattern', function () {
    $handler = new AshbyHandler;

    expect($handler->extract('https://jobs.ashbyhq.com/'))->toBeNull();
});

test('returns null when job not found in api response', function () {
    Http::fake([
        'api.ashbyhq.com/*' => Http::response([
            'organizationName' => 'Company',
            'jobs' => [
                ['id' => 'other-job', 'title' => 'Other Role'],
            ],
        ]),
    ]);

    $handler = new AshbyHandler;
    $result = $handler->extract('https://jobs.ashbyhq.com/company/abc-123');

    expect($result)->toBeNull();
});

test('returns null when api returns 404', function () {
    Http::fake([
        'api.ashbyhq.com/*' => Http::response(null, 404),
    ]);

    $handler = new AshbyHandler;
    $result = $handler->extract('https://jobs.ashbyhq.com/company/abc-123');

    expect($result)->toBeNull();
});

test('finds job by external link match', function () {
    Http::fake([
        'api.ashbyhq.com/*' => Http::response([
            'organizationName' => 'Company',
            'jobs' => [
                [
                    'id' => 'different-id',
                    'title' => 'Found via Link',
                    'externalLink' => 'https://jobs.ashbyhq.com/company/abc-123',
                ],
            ],
        ]),
    ]);

    $handler = new AshbyHandler;
    $result = $handler->extract('https://jobs.ashbyhq.com/company/abc-123');

    expect($result)
        ->not->toBeNull()
        ->and($result['title'])->toBe('Found via Link');
});
