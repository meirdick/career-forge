<?php

use App\Services\Scrapers\AtsApiDriver;
use App\Services\Scrapers\AtsHandlers\AtsHandler;

uses(Tests\TestCase::class);

test('is always configured', function () {
    $driver = new AtsApiDriver;

    expect($driver->isConfigured())->toBeTrue();
});

test('discoverLinks always returns null', function () {
    $driver = new AtsApiDriver;

    expect($driver->discoverLinks('https://boards.greenhouse.io/company/jobs/123'))->toBeNull();
});

test('returns formatted markdown when handler matches and extracts', function () {
    $handler = Mockery::mock(AtsHandler::class);
    $handler->shouldReceive('canHandle')->with('https://example.com/job/1')->andReturn(true);
    $handler->shouldReceive('extract')->with('https://example.com/job/1')->andReturn([
        'title' => 'Software Engineer',
        'company' => 'Acme Corp',
        'location' => 'Remote',
        'department' => 'Engineering',
        'employment_type' => 'Full-time',
        'compensation' => 'USD 120000 - 160000',
        'description' => 'Build amazing software with our team.',
        'sections' => [
            'Requirements' => '<ul><li>3+ years experience</li><li>Python skills</li></ul>',
        ],
    ]);

    $driver = new AtsApiDriver([$handler]);
    $result = $driver->scrape('https://example.com/job/1');

    expect($result)
        ->toContain('# Software Engineer')
        ->toContain('**Company:** Acme Corp')
        ->toContain('## Requirements');
});

test('returns null when no handler matches', function () {
    $handler = Mockery::mock(AtsHandler::class);
    $handler->shouldReceive('canHandle')->with('https://unknown.com/job/1')->andReturn(false);

    $driver = new AtsApiDriver([$handler]);

    expect($driver->scrape('https://unknown.com/job/1'))->toBeNull();
});

test('returns null when handler matches but extract fails', function () {
    $handler = Mockery::mock(AtsHandler::class);
    $handler->shouldReceive('canHandle')->with('https://example.com/job/1')->andReturn(true);
    $handler->shouldReceive('extract')->with('https://example.com/job/1')->andReturn(null);

    $driver = new AtsApiDriver([$handler]);

    expect($driver->scrape('https://example.com/job/1'))->toBeNull();
});

test('does not try other handlers after first match fails', function () {
    $first = Mockery::mock(AtsHandler::class);
    $first->shouldReceive('canHandle')->andReturn(true);
    $first->shouldReceive('extract')->andReturn(null);

    $second = Mockery::mock(AtsHandler::class);
    $second->shouldNotReceive('canHandle');
    $second->shouldNotReceive('extract');

    $driver = new AtsApiDriver([$first, $second]);

    expect($driver->scrape('https://example.com/job/1'))->toBeNull();
});

test('returns null when handler returns data with empty title', function () {
    $handler = Mockery::mock(AtsHandler::class);
    $handler->shouldReceive('canHandle')->andReturn(true);
    $handler->shouldReceive('extract')->andReturn([
        'title' => null,
        'company' => 'Acme',
        'location' => null,
        'department' => null,
        'employment_type' => null,
        'compensation' => null,
        'description' => null,
        'sections' => [],
    ]);

    $driver = new AtsApiDriver([$handler]);

    expect($driver->scrape('https://example.com/job/1'))->toBeNull();
});

test('uses default handlers when none provided', function () {
    $driver = new AtsApiDriver;

    // Greenhouse URL should be handled by the default GreenhouseHandler
    // We can't test the actual API call, but we can verify it doesn't crash
    expect($driver->scrape('https://example.com/not-an-ats'))->toBeNull();
});
