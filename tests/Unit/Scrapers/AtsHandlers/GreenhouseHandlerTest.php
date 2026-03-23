<?php

use App\Services\Scrapers\AtsHandlers\GreenhouseHandler;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

test('canHandle matches boards.greenhouse.io', function () {
    $handler = new GreenhouseHandler;

    expect($handler->canHandle('https://boards.greenhouse.io/figma/jobs/5232157004'))->toBeTrue();
    expect($handler->canHandle('https://boards.greenhouse.io/company/jobs/123'))->toBeTrue();
    expect($handler->canHandle('https://example.com/jobs/123'))->toBeFalse();
    expect($handler->canHandle('https://greenhouse.io/jobs/123'))->toBeFalse();
});

test('extracts job data from greenhouse api', function () {
    Http::fake([
        'boards-api.greenhouse.io/v1/boards/figma/jobs/5232157004' => Http::response([
            'title' => 'Senior Software Engineer',
            'location' => ['name' => 'San Francisco, CA'],
            'company' => ['name' => 'Figma'],
            'departments' => [['name' => 'Engineering']],
            'content' => '<h3>About the Role</h3><p>Join our team to build amazing design tools.</p><h3>Requirements</h3><ul><li>5+ years of experience</li></ul>',
        ]),
    ]);

    $handler = new GreenhouseHandler;
    $result = $handler->extract('https://boards.greenhouse.io/figma/jobs/5232157004');

    expect($result)
        ->not->toBeNull()
        ->and($result['title'])->toBe('Senior Software Engineer')
        ->and($result['company'])->toBe('Figma')
        ->and($result['location'])->toBe('San Francisco, CA')
        ->and($result['department'])->toBe('Engineering')
        ->and($result['sections'])->toHaveKey('About the Role')
        ->and($result['sections'])->toHaveKey('Requirements');
});

test('returns null for invalid url pattern', function () {
    $handler = new GreenhouseHandler;

    expect($handler->extract('https://boards.greenhouse.io/invalid-path'))->toBeNull();
});

test('returns null when api returns 404', function () {
    Http::fake([
        'boards-api.greenhouse.io/*' => Http::response(null, 404),
    ]);

    $handler = new GreenhouseHandler;
    $result = $handler->extract('https://boards.greenhouse.io/company/jobs/999');

    expect($result)->toBeNull();
});

test('returns null when api response has no title', function () {
    Http::fake([
        'boards-api.greenhouse.io/*' => Http::response([
            'id' => 123,
            'content' => 'some content',
        ]),
    ]);

    $handler = new GreenhouseHandler;
    $result = $handler->extract('https://boards.greenhouse.io/company/jobs/123');

    expect($result)->toBeNull();
});

test('handles greenhouse api connection failure', function () {
    Http::fake([
        'boards-api.greenhouse.io/*' => Http::response(null, 500),
    ]);

    $handler = new GreenhouseHandler;
    $result = $handler->extract('https://boards.greenhouse.io/company/jobs/123');

    expect($result)->toBeNull();
});

test('extracts sections from bold paragraph pattern', function () {
    Http::fake([
        'boards-api.greenhouse.io/*' => Http::response([
            'title' => 'Engineer',
            'content' => '<p>About the role.</p><p><strong>What You\'ll Do:</strong></p><ul><li>Build things</li></ul><p><strong>Requirements:</strong></p><ul><li>5 years exp</li></ul>',
        ]),
    ]);

    $handler = new GreenhouseHandler;
    $result = $handler->extract('https://boards.greenhouse.io/company/jobs/123');

    expect($result)->not->toBeNull()
        ->and($result['sections'])->not->toBeEmpty();
});
