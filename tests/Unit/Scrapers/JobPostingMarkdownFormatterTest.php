<?php

use App\Services\Scrapers\JobPostingMarkdownFormatter;

test('formats complete job posting data into markdown', function () {
    $data = [
        'title' => 'Senior Software Engineer',
        'company' => 'Acme Corp',
        'location' => 'San Francisco, CA',
        'department' => 'Engineering',
        'employment_type' => 'Full-time',
        'compensation' => 'USD 150000 - 200000 per year',
        'description' => '<p>We are looking for a talented engineer to join our team.</p>',
        'sections' => [
            'Requirements' => '<ul><li>5+ years experience</li><li>Python proficiency</li></ul>',
            'Benefits' => '<p>Comprehensive health insurance and 401k matching.</p>',
        ],
    ];

    $result = JobPostingMarkdownFormatter::format($data);

    expect($result)
        ->toContain('# Senior Software Engineer')
        ->toContain('**Company:** Acme Corp')
        ->toContain('**Location:** San Francisco, CA')
        ->toContain('**Department:** Engineering')
        ->toContain('**Employment Type:** Full-time')
        ->toContain('**Compensation:** USD 150000 - 200000 per year')
        ->toContain('## Description')
        ->toContain('## Requirements')
        ->toContain('## Benefits');
});

test('returns null when title is missing', function () {
    $data = [
        'title' => null,
        'company' => 'Acme Corp',
        'location' => null,
        'department' => null,
        'employment_type' => null,
        'compensation' => null,
        'description' => null,
        'sections' => [],
    ];

    expect(JobPostingMarkdownFormatter::format($data))->toBeNull();
});

test('returns null when title is empty string', function () {
    $data = [
        'title' => '  ',
        'company' => 'Acme Corp',
        'location' => null,
        'department' => null,
        'employment_type' => null,
        'compensation' => null,
        'description' => null,
        'sections' => [],
    ];

    expect(JobPostingMarkdownFormatter::format($data))->toBeNull();
});

test('omits null metadata fields', function () {
    $data = [
        'title' => 'Engineer',
        'company' => 'Acme Corp',
        'location' => null,
        'department' => null,
        'employment_type' => null,
        'compensation' => null,
        'description' => 'A great role.',
        'sections' => [],
    ];

    $result = JobPostingMarkdownFormatter::format($data);

    expect($result)
        ->toContain('**Company:** Acme Corp')
        ->not->toContain('**Location:**')
        ->not->toContain('**Department:**');
});

test('converts html description to markdown', function () {
    $data = [
        'title' => 'Engineer',
        'company' => null,
        'location' => null,
        'department' => null,
        'employment_type' => null,
        'compensation' => null,
        'description' => '<h3>About Us</h3><p>We build <strong>great</strong> software.</p>',
        'sections' => [],
    ];

    $result = JobPostingMarkdownFormatter::format($data);

    expect($result)
        ->toContain('**great**')
        ->not->toContain('<strong>');
});

test('handles plain text description without html', function () {
    $data = [
        'title' => 'Engineer',
        'company' => null,
        'location' => null,
        'department' => null,
        'employment_type' => null,
        'compensation' => null,
        'description' => 'We build great software.',
        'sections' => [],
    ];

    $result = JobPostingMarkdownFormatter::format($data);

    expect($result)->toContain('We build great software.');
});

test('skips empty sections', function () {
    $data = [
        'title' => 'Engineer',
        'company' => null,
        'location' => null,
        'department' => null,
        'employment_type' => null,
        'compensation' => null,
        'description' => null,
        'sections' => [
            'Requirements' => '<p>Must have 5 years experience.</p>',
            'Empty' => '',
            'Benefits' => '<p>Health insurance.</p>',
        ],
    ];

    $result = JobPostingMarkdownFormatter::format($data);

    expect($result)
        ->toContain('## Requirements')
        ->toContain('## Benefits')
        ->not->toContain('## Empty');
});
