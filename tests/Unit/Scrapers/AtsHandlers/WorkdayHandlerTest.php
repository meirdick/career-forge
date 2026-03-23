<?php

use App\Services\Scrapers\AtsHandlers\WorkdayHandler;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

test('canHandle matches myworkdayjobs.com subdomains', function () {
    $handler = new WorkdayHandler;

    expect($handler->canHandle('https://gen.wd1.myworkdayjobs.com/en-US/careers/job/USA/Engineer_55006'))->toBeTrue();
    expect($handler->canHandle('https://company.myworkdayjobs.com/en-US/site/job/location/title_123'))->toBeTrue();
    expect($handler->canHandle('https://myworkdayjobs.com/en-US/careers/job/test'))->toBeFalse();
    expect($handler->canHandle('https://example.com/workday'))->toBeFalse();
});

test('extracts job data from workday api', function () {
    Http::fake([
        'gen.wd1.myworkdayjobs.com/wday/cxs/en-US/careers/job/USA-California/Senior-Engineer_55006' => Http::response([
            'jobPostingInfo' => [
                'title' => 'Senior Engineer',
                'company' => 'NortonLifeLock',
                'location' => 'Mountain View, CA',
                'timeType' => 'Full time',
                'jobDescription' => '<p>We are looking for a talented engineer.</p>',
                'jobPostingAdditionalData' => [
                    [
                        'label' => 'Requirements',
                        'content' => '<ul><li>5+ years experience</li></ul>',
                    ],
                    [
                        'label' => 'Benefits',
                        'content' => '<p>Great benefits package.</p>',
                    ],
                ],
            ],
        ]),
    ]);

    $handler = new WorkdayHandler;
    $result = $handler->extract('https://gen.wd1.myworkdayjobs.com/en-US/careers/job/USA-California/Senior-Engineer_55006');

    expect($result)
        ->not->toBeNull()
        ->and($result['title'])->toBe('Senior Engineer')
        ->and($result['company'])->toBe('NortonLifeLock')
        ->and($result['location'])->toBe('Mountain View, CA')
        ->and($result['employment_type'])->toBe('Full time')
        ->and($result['sections'])->toHaveKey('Requirements')
        ->and($result['sections'])->toHaveKey('Benefits');
});

test('returns null for invalid url pattern', function () {
    $handler = new WorkdayHandler;

    expect($handler->extract('https://gen.wd1.myworkdayjobs.com/invalid'))->toBeNull();
});

test('returns null when api returns no jobPostingInfo', function () {
    Http::fake([
        'gen.wd1.myworkdayjobs.com/*' => Http::response([
            'otherData' => 'test',
        ]),
    ]);

    $handler = new WorkdayHandler;
    $result = $handler->extract('https://gen.wd1.myworkdayjobs.com/en-US/careers/job/USA/Title_123');

    expect($result)->toBeNull();
});

test('returns null when api returns 404', function () {
    Http::fake([
        'gen.wd1.myworkdayjobs.com/*' => Http::response(null, 404),
    ]);

    $handler = new WorkdayHandler;
    $result = $handler->extract('https://gen.wd1.myworkdayjobs.com/en-US/careers/job/USA/Title_123');

    expect($result)->toBeNull();
});

test('constructs correct api url from parts', function () {
    Http::fake([
        'company.myworkdayjobs.com/wday/cxs/fr-FR/external/job/Paris/Ingenieur_42' => Http::response([
            'jobPostingInfo' => [
                'title' => 'Ingénieur',
                'jobDescription' => '<p>Description du poste.</p>',
            ],
        ]),
    ]);

    $handler = new WorkdayHandler;
    $result = $handler->extract('https://company.myworkdayjobs.com/fr-FR/external/job/Paris/Ingenieur_42');

    expect($result)
        ->not->toBeNull()
        ->and($result['title'])->toBe('Ingénieur');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://company.myworkdayjobs.com/wday/cxs/fr-FR/external/job/Paris/Ingenieur_42';
    });
});

test('handles nested job path segments', function () {
    Http::fake([
        'gen.wd1.myworkdayjobs.com/wday/cxs/en-US/careers/job/USA---California-Mountain-View/Senior-Director_55006' => Http::response([
            'jobPostingInfo' => [
                'title' => 'Senior Director',
                'location' => 'Mountain View, CA',
                'jobDescription' => '<p>Lead our engineering team.</p>',
            ],
        ]),
    ]);

    $handler = new WorkdayHandler;
    $result = $handler->extract('https://gen.wd1.myworkdayjobs.com/en-US/careers/job/USA---California-Mountain-View/Senior-Director_55006');

    expect($result)
        ->not->toBeNull()
        ->and($result['title'])->toBe('Senior Director');
});
