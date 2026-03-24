<?php

use App\Enums\ResumeSectionType;
use App\Jobs\GenerateResumeJob;
use App\Models\Resume;
use App\Models\ResumeSection;
use App\Models\ResumeSectionVariant;
use Illuminate\Support\Str;

it('returns silently when resume is deleted before execution', function () {
    $resume = Resume::factory()->create();
    $job = new GenerateResumeJob($resume);

    $resume->delete();

    // Should not throw — just return early
    $job->handle();

    expect(ResumeSection::where('resume_id', $resume->id)->count())->toBe(0);
});

it('truncates emphasis to 1000 characters', function () {
    $job = (new ReflectionClass(GenerateResumeJob::class))->newInstanceWithoutConstructor();

    $section = ResumeSection::factory()->create();

    $longEmphasis = str_repeat('A', 2000);

    $variants = [
        [
            'label' => 'Test Variant',
            'content' => 'Some content',
            'emphasis' => $longEmphasis,
        ],
    ];

    $method = new ReflectionMethod($job, 'createVariants');
    $result = $method->invoke($job, $section, ResumeSectionType::Summary, $variants, []);

    expect($result)->toBeInstanceOf(ResumeSectionVariant::class);
    expect(Str::length($result->emphasis))->toBeLessThanOrEqual(1003); // 1000 + '...'
});
