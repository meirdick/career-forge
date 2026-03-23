<?php

use App\Services\Scrapers\AtsHandlers\LeverHandler;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

test('canHandle matches jobs.lever.co', function () {
    $handler = new LeverHandler;

    expect($handler->canHandle('https://jobs.lever.co/cloudflare/abc-123-def'))->toBeTrue();
    expect($handler->canHandle('https://jobs.lever.co/company/posting-id'))->toBeTrue();
    expect($handler->canHandle('https://lever.co/jobs/123'))->toBeFalse();
    expect($handler->canHandle('https://example.com/jobs'))->toBeFalse();
});

test('extracts job data from lever api', function () {
    Http::fake([
        'api.lever.co/v0/postings/cloudflare/abc-123-def' => Http::response([
            'text' => 'Backend Engineer',
            'descriptionPlain' => 'Join our distributed systems team.',
            'categories' => [
                'company' => 'Cloudflare',
                'location' => 'Austin, TX',
                'department' => 'Engineering',
                'commitment' => 'Full-time',
            ],
            'lists' => [
                [
                    'text' => 'What You Will Do',
                    'content' => '<li>Design APIs</li><li>Build microservices</li>',
                ],
                [
                    'text' => 'Requirements',
                    'content' => '<li>3+ years Go or Rust</li>',
                ],
            ],
            'salaryRange' => [
                'min' => 150000,
                'max' => 200000,
                'currency' => 'USD',
                'interval' => 'per year',
            ],
        ]),
    ]);

    $handler = new LeverHandler;
    $result = $handler->extract('https://jobs.lever.co/cloudflare/abc-123-def');

    expect($result)
        ->not->toBeNull()
        ->and($result['title'])->toBe('Backend Engineer')
        ->and($result['company'])->toBe('Cloudflare')
        ->and($result['location'])->toBe('Austin, TX')
        ->and($result['department'])->toBe('Engineering')
        ->and($result['employment_type'])->toBe('Full-time')
        ->and($result['compensation'])->toBe('USD 150000 - 200000 per year')
        ->and($result['sections'])->toHaveKey('What You Will Do')
        ->and($result['sections'])->toHaveKey('Requirements');
});

test('returns null for invalid url pattern', function () {
    $handler = new LeverHandler;

    expect($handler->extract('https://jobs.lever.co/'))->toBeNull();
});

test('returns null when api returns 404', function () {
    Http::fake([
        'api.lever.co/*' => Http::response(null, 404),
    ]);

    $handler = new LeverHandler;
    $result = $handler->extract('https://jobs.lever.co/company/abc-123');

    expect($result)->toBeNull();
});

test('returns null when api response has no text field', function () {
    Http::fake([
        'api.lever.co/*' => Http::response([
            'id' => 'abc-123',
        ]),
    ]);

    $handler = new LeverHandler;
    $result = $handler->extract('https://jobs.lever.co/company/abc-123');

    expect($result)->toBeNull();
});

test('handles missing salary range gracefully', function () {
    Http::fake([
        'api.lever.co/*' => Http::response([
            'text' => 'Engineer',
            'categories' => ['company' => 'Acme'],
            'lists' => [],
        ]),
    ]);

    $handler = new LeverHandler;
    $result = $handler->extract('https://jobs.lever.co/company/abc-123');

    expect($result)
        ->not->toBeNull()
        ->and($result['compensation'])->toBeNull();
});

test('includes additional information section', function () {
    Http::fake([
        'api.lever.co/*' => Http::response([
            'text' => 'Engineer',
            'categories' => [],
            'lists' => [],
            'additional' => '<p>Equal opportunity employer.</p>',
        ]),
    ]);

    $handler = new LeverHandler;
    $result = $handler->extract('https://jobs.lever.co/company/abc-123');

    expect($result['sections'])->toHaveKey('Additional Information');
});
