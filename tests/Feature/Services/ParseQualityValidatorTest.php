<?php

use App\Services\ParseQualityValidator;

beforeEach(function () {
    $this->validator = new ParseQualityValidator;
});

// --- Resume Parse Validation ---

test('resume parse passes with complete data', function () {
    $result = $this->validator->validateResumeParse([
        'experiences' => [
            ['company' => 'Acme Corp', 'title' => 'Engineer'],
            ['company' => 'Beta Inc', 'title' => 'Senior Engineer'],
        ],
        'skills' => [
            ['name' => 'PHP'], ['name' => 'Laravel'], ['name' => 'React'],
        ],
        'accomplishments' => [
            ['title' => 'Led migration', 'description' => 'Migrated to microservices'],
        ],
        'projects' => [],
    ], 600);

    expect($result)
        ->passed->toBeTrue()
        ->score->toBe(1.0)
        ->failedRules->toBeEmpty()
        ->inputTooShort->toBeFalse();
});

test('resume parse fails with empty experiences and missing skills', function () {
    $result = $this->validator->validateResumeParse([
        'experiences' => [],
        'skills' => [['name' => 'PHP']],
        'accomplishments' => [['title' => 'Test', 'description' => 'Test']],
        'projects' => [],
    ], 600);

    expect($result)
        ->passed->toBeFalse()
        ->failedRules->toContain('has_experiences')
        ->failedRules->toContain('has_skills')
        ->failedRules->toContain('sufficient_experiences_for_input');
});

test('resume parse fails with missing skills', function () {
    $result = $this->validator->validateResumeParse([
        'experiences' => [
            ['company' => 'Acme', 'title' => 'Dev'],
            ['company' => 'Beta', 'title' => 'Dev'],
        ],
        'skills' => [['name' => 'PHP']],
        'accomplishments' => [['title' => 'Test', 'description' => 'Test']],
        'projects' => [],
    ], 600);

    expect($result)
        ->failedRules->toContain('has_skills')
        ->retryHint->toContain('skills');
});

test('resume parse marks short input as inputTooShort', function () {
    $result = $this->validator->validateResumeParse([
        'experiences' => [],
        'skills' => [],
        'accomplishments' => [],
        'projects' => [],
    ], 50);

    expect($result)
        ->passed->toBeFalse()
        ->inputTooShort->toBeTrue()
        ->retryHint->toBe('');
});

test('resume parse flags experiences missing company or title', function () {
    $result = $this->validator->validateResumeParse([
        'experiences' => [
            ['company' => '', 'title' => 'Engineer'],
            ['company' => 'Acme', 'title' => 'Dev'],
        ],
        'skills' => [['name' => 'A'], ['name' => 'B'], ['name' => 'C']],
        'accomplishments' => [['title' => 'T', 'description' => 'D']],
        'projects' => [],
    ], 600);

    expect($result->failedRules)->toContain('experiences_have_required_fields');
});

test('resume parse does not require 2 experiences for short input', function () {
    $result = $this->validator->validateResumeParse([
        'experiences' => [['company' => 'Acme', 'title' => 'Dev']],
        'skills' => [['name' => 'A'], ['name' => 'B'], ['name' => 'C']],
        'accomplishments' => [['title' => 'T', 'description' => 'D']],
        'projects' => [],
    ], 300);

    expect($result)->passed->toBeTrue();
});

// --- Job Analysis Validation ---

test('job analysis passes with complete data', function () {
    $result = $this->validator->validateJobAnalysis([
        'title' => 'Senior Engineer',
        'required_skills' => [
            ['name' => 'PHP'], ['name' => 'Laravel'],
        ],
        'preferred_skills' => [['name' => 'React']],
        'candidate_summary' => 'A senior engineer with 5+ years of experience in backend development and team leadership capabilities.',
        'experience_profile' => ['min_years' => 5],
        'language_guidance' => ['key_terms' => ['scalable', 'microservices']],
    ], 500);

    expect($result)
        ->passed->toBeTrue()
        ->score->toBe(1.0)
        ->inputTooShort->toBeFalse();
});

test('job analysis fails with missing title', function () {
    $result = $this->validator->validateJobAnalysis([
        'title' => '',
        'required_skills' => [['name' => 'PHP']],
        'preferred_skills' => [['name' => 'React'], ['name' => 'Node']],
        'candidate_summary' => 'A senior engineer with 5+ years of experience in backend development and team leadership.',
        'experience_profile' => ['min_years' => 5],
        'language_guidance' => ['key_terms' => ['scalable']],
    ], 500);

    expect($result->failedRules)->toContain('has_title');
});

test('job analysis marks short input as inputTooShort', function () {
    $result = $this->validator->validateJobAnalysis([
        'title' => '',
        'required_skills' => [],
    ], 100);

    expect($result)
        ->passed->toBeFalse()
        ->inputTooShort->toBeTrue();
});

test('job analysis requires more skills for longer input', function () {
    $result = $this->validator->validateJobAnalysis([
        'title' => 'Engineer',
        'required_skills' => [['name' => 'PHP']],
        'preferred_skills' => [],
        'candidate_summary' => 'A senior engineer with 5+ years of experience in backend development and team leadership.',
        'experience_profile' => ['min_years' => 3],
        'language_guidance' => ['key_terms' => ['agile']],
    ], 500);

    expect($result->failedRules)->toContain('sufficient_skills_for_input');
});

// --- Link Index Validation ---

test('link index passes with extracted content', function () {
    $result = $this->validator->validateLinkIndex([
        'skills' => [['name' => 'Python']],
        'accomplishments' => [
            ['title' => 'Built API', 'description' => 'Designed and built a REST API serving 10k requests/sec'],
        ],
        'projects' => [],
    ], 1000);

    expect($result)
        ->passed->toBeTrue()
        ->inputTooShort->toBeFalse();
});

test('link index fails with empty results from substantial input', function () {
    $result = $this->validator->validateLinkIndex([
        'skills' => [],
        'accomplishments' => [],
        'projects' => [],
    ], 1000);

    expect($result)
        ->passed->toBeFalse()
        ->score->toBe(0.0)
        ->failedRules->toContain('has_any_content')
        ->failedRules->toContain('has_meaningful_description')
        ->retryHint->toContain('skills, accomplishments, or projects');
});

test('link index marks short input with empty results as inputTooShort', function () {
    $result = $this->validator->validateLinkIndex([
        'skills' => [],
        'accomplishments' => [],
        'projects' => [],
    ], 100);

    expect($result)
        ->passed->toBeFalse()
        ->inputTooShort->toBeTrue();
});

test('link index fails when descriptions are too short', function () {
    $result = $this->validator->validateLinkIndex([
        'skills' => [],
        'accomplishments' => [['title' => 'Thing', 'description' => 'Short']],
        'projects' => [],
    ], 1000);

    expect($result->failedRules)->toContain('has_meaningful_description');
});

// --- Retry Hint ---

test('retry hint includes all failed rule descriptions', function () {
    $result = $this->validator->validateResumeParse([
        'experiences' => [],
        'skills' => [],
        'accomplishments' => [],
        'projects' => [],
    ], 600);

    expect($result->retryHint)
        ->toContain('work experiences')
        ->toContain('skills')
        ->toContain('accomplishments or projects')
        ->toContain('re-analyze');
});
