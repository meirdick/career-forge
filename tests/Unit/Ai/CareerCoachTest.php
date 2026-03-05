<?php

use App\Ai\Agents\CareerCoach;
use App\Enums\ChatSessionMode;

test('general mode instructions contain coaching style', function () {
    $coach = new CareerCoach(mode: ChatSessionMode::General);
    $instructions = $coach->instructions();

    expect($instructions)
        ->toContain('career coach')
        ->toContain('action verbs')
        ->toContain('Extract Experiences');
});

test('general mode instructions do not contain job-specific sections', function () {
    $coach = new CareerCoach(mode: ChatSessionMode::General);
    $instructions = $coach->instructions();

    expect($instructions)
        ->not->toContain('TARGET JOB')
        ->not->toContain('GAP ANALYSIS');
});

test('job-specific mode includes job context', function () {
    $coach = new CareerCoach(
        jobContext: 'Job: Staff Engineer at BigTech',
        mode: ChatSessionMode::JobSpecific,
    );
    $instructions = $coach->instructions();

    expect($instructions)
        ->toContain('TARGET JOB')
        ->toContain('Staff Engineer at BigTech');
});

test('job-specific mode includes gap context when provided', function () {
    $coach = new CareerCoach(
        jobContext: 'Job: Engineer',
        gapContext: 'Gaps: No Kubernetes experience',
        mode: ChatSessionMode::JobSpecific,
    );
    $instructions = $coach->instructions();

    expect($instructions)
        ->toContain('GAP ANALYSIS')
        ->toContain('No Kubernetes experience');
});

test('experience context is included when provided', function () {
    $coach = new CareerCoach(
        experienceContext: '## Work Experience\n### Lead at TestCorp',
        mode: ChatSessionMode::General,
    );
    $instructions = $coach->instructions();

    expect($instructions)
        ->toContain('EXISTING EXPERIENCE')
        ->toContain('TestCorp');
});

test('empty job context is not included for job-specific mode', function () {
    $coach = new CareerCoach(
        jobContext: '',
        mode: ChatSessionMode::JobSpecific,
    );
    $instructions = $coach->instructions();

    expect($instructions)->not->toContain('TARGET JOB');
});
