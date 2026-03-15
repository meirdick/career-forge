<?php

use App\Enums\ResumeSectionType;
use App\Jobs\GenerateResumeJob;

it('strips DB metadata from library data', function () {
    $job = (new ReflectionClass(GenerateResumeJob::class))->newInstanceWithoutConstructor();

    $library = [
        'experiences' => [
            [
                'id' => 1,
                'user_id' => 5,
                'company' => 'Acme Corp',
                'title' => 'Engineer',
                'description' => 'Built things',
                'created_at' => '2025-01-01',
                'updated_at' => '2025-06-01',
                'accomplishments' => [
                    [
                        'id' => 10,
                        'experience_id' => 1,
                        'title' => 'Shipped feature',
                        'impact' => 'Increased revenue 20%',
                        'pivot' => ['skill_id' => 3],
                    ],
                ],
                'skills' => [
                    [
                        'id' => 3,
                        'user_id' => 5,
                        'name' => 'PHP',
                        'category' => 'language',
                        'pivot' => ['experience_id' => 1, 'skill_id' => 3],
                    ],
                ],
            ],
        ],
        'skills' => [
            ['id' => 3, 'user_id' => 5, 'name' => 'PHP', 'category' => 'language', 'created_at' => '2025-01-01', 'updated_at' => '2025-01-01'],
        ],
    ];

    $trimmed = (new ReflectionMethod($job, 'trimLibraryData'))->invoke($job, $library);

    // Top-level IDs stripped
    expect($trimmed['experiences'][0])->not->toHaveKey('id');
    expect($trimmed['experiences'][0])->not->toHaveKey('user_id');
    expect($trimmed['experiences'][0])->not->toHaveKey('created_at');
    expect($trimmed['experiences'][0])->not->toHaveKey('updated_at');

    // Content preserved
    expect($trimmed['experiences'][0]['company'])->toBe('Acme Corp');
    expect($trimmed['experiences'][0]['title'])->toBe('Engineer');
    expect($trimmed['experiences'][0]['description'])->toBe('Built things');

    // Nested IDs stripped
    expect($trimmed['experiences'][0]['accomplishments'][0])->not->toHaveKey('id');
    expect($trimmed['experiences'][0]['accomplishments'][0])->not->toHaveKey('experience_id');
    expect($trimmed['experiences'][0]['accomplishments'][0])->not->toHaveKey('pivot');
    expect($trimmed['experiences'][0]['accomplishments'][0]['title'])->toBe('Shipped feature');

    // Skills pivot stripped
    expect($trimmed['experiences'][0]['skills'][0])->not->toHaveKey('pivot');
    expect($trimmed['experiences'][0]['skills'][0])->not->toHaveKey('id');
    expect($trimmed['experiences'][0]['skills'][0]['name'])->toBe('PHP');

    // Top-level skills stripped
    expect($trimmed['skills'][0])->not->toHaveKey('id');
    expect($trimmed['skills'][0])->not->toHaveKey('user_id');
    expect($trimmed['skills'][0]['name'])->toBe('PHP');
});

it('subsets library data per section type', function () {
    $job = (new ReflectionClass(GenerateResumeJob::class))->newInstanceWithoutConstructor();

    $library = [
        'experiences' => [['company' => 'Acme', 'title' => 'Dev', 'skills' => [['name' => 'PHP']]]],
        'skills' => [['name' => 'PHP']],
        'education' => [['institution' => 'MIT']],
        'identity' => ['values' => 'test'],
        'certifications' => [['title' => 'AWS']],
        'publications' => [['title' => 'Paper']],
        'projects' => [['name' => 'OSS Tool']],
    ];

    $method = new ReflectionMethod($job, 'libraryForSection');

    // Summary gets everything
    $summary = $method->invoke($job, ResumeSectionType::Summary, $library);
    expect($summary)->toHaveKeys(['experiences', 'skills', 'education', 'identity']);

    // Experience gets experiences + identity only
    $exp = $method->invoke($job, ResumeSectionType::Experience, $library);
    expect($exp)->toHaveKeys(['experiences', 'identity']);
    expect($exp)->not->toHaveKey('skills');
    expect($exp)->not->toHaveKey('education');

    // Skills gets skills + slim experiences
    $skills = $method->invoke($job, ResumeSectionType::Skills, $library);
    expect($skills)->toHaveKeys(['skills', 'experiences']);
    expect($skills['experiences'][0])->toHaveKeys(['company', 'title', 'skills']);
    expect($skills['experiences'][0])->not->toHaveKey('description');

    // Education gets education only
    $edu = $method->invoke($job, ResumeSectionType::Education, $library);
    expect($edu)->toHaveKey('education');
    expect($edu)->not->toHaveKey('experiences');

    // Certifications gets certifications only
    $certs = $method->invoke($job, ResumeSectionType::Certifications, $library);
    expect($certs)->toHaveKey('certifications');
    expect($certs)->not->toHaveKey('experiences');

    // Publications gets publications only
    $pubs = $method->invoke($job, ResumeSectionType::Publications, $library);
    expect($pubs)->toHaveKey('publications');
    expect($pubs)->not->toHaveKey('experiences');

    // Projects gets projects + experiences (for dedup context)
    $proj = $method->invoke($job, ResumeSectionType::Projects, $library);
    expect($proj)->toHaveKeys(['projects', 'experiences']);
    expect($proj)->not->toHaveKey('skills');
});
