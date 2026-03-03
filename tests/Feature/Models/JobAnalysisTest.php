<?php

use App\Models\GapAnalysis;
use App\Models\IdealCandidateProfile;
use App\Models\JobPosting;
use App\Models\User;

// Job Posting
test('job posting factory creates valid model', function () {
    $posting = JobPosting::factory()->create();

    expect($posting)->toBeInstanceOf(JobPosting::class)
        ->and($posting->raw_text)->toBeString()
        ->and($posting->user)->toBeInstanceOf(User::class);
});

test('job posting has one ideal candidate profile', function () {
    $posting = JobPosting::factory()->create();
    IdealCandidateProfile::factory()->create(['job_posting_id' => $posting->id]);

    expect($posting->idealCandidateProfile)->toBeInstanceOf(IdealCandidateProfile::class);
});

test('job posting analyzed state sets analyzed_at', function () {
    $posting = JobPosting::factory()->analyzed()->create();

    expect($posting->analyzed_at)->not->toBeNull();
});

test('job posting casts parsed_data as array', function () {
    $posting = JobPosting::factory()->create([
        'parsed_data' => ['requirements' => ['PHP', 'Laravel']],
    ]);

    expect($posting->parsed_data)->toBeArray()
        ->and($posting->parsed_data['requirements'])->toContain('PHP');
});

test('user has many job postings', function () {
    $user = User::factory()->create();
    JobPosting::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->jobPostings)->toHaveCount(3);
});

// Ideal Candidate Profile
test('ideal candidate profile factory creates valid model', function () {
    $profile = IdealCandidateProfile::factory()->create();

    expect($profile)->toBeInstanceOf(IdealCandidateProfile::class)
        ->and($profile->required_skills)->toBeArray()
        ->and($profile->preferred_skills)->toBeArray();
});

test('ideal candidate profile belongs to job posting', function () {
    $posting = JobPosting::factory()->create();
    $profile = IdealCandidateProfile::factory()->create(['job_posting_id' => $posting->id]);

    expect($profile->jobPosting->id)->toBe($posting->id);
});

test('ideal candidate profile casts json fields', function () {
    $profile = IdealCandidateProfile::factory()->create();

    expect($profile->required_skills)->toBeArray()
        ->and($profile->preferred_skills)->toBeArray()
        ->and($profile->experience_profile)->toBeArray()
        ->and($profile->cultural_fit)->toBeArray()
        ->and($profile->language_guidance)->toBeArray()
        ->and($profile->red_flags)->toBeArray();
});

test('ideal candidate profile has many gap analyses', function () {
    $profile = IdealCandidateProfile::factory()->create();
    GapAnalysis::factory()->count(2)->create(['ideal_candidate_profile_id' => $profile->id]);

    expect($profile->gapAnalyses)->toHaveCount(2);
});

test('deleting job posting cascades to ideal candidate profile', function () {
    $posting = JobPosting::factory()->create();
    $profile = IdealCandidateProfile::factory()->create(['job_posting_id' => $posting->id]);

    $posting->delete();

    expect(IdealCandidateProfile::find($profile->id))->toBeNull();
});

// Gap Analysis
test('gap analysis factory creates valid model', function () {
    $analysis = GapAnalysis::factory()->create();

    expect($analysis)->toBeInstanceOf(GapAnalysis::class)
        ->and($analysis->strengths)->toBeArray()
        ->and($analysis->gaps)->toBeArray();
});

test('gap analysis belongs to user and ideal candidate profile', function () {
    $user = User::factory()->create();
    $profile = IdealCandidateProfile::factory()->create();
    $analysis = GapAnalysis::factory()->create([
        'user_id' => $user->id,
        'ideal_candidate_profile_id' => $profile->id,
    ]);

    expect($analysis->user->id)->toBe($user->id)
        ->and($analysis->idealCandidateProfile->id)->toBe($profile->id);
});

test('gap analysis finalized state', function () {
    $analysis = GapAnalysis::factory()->finalized()->create();

    expect($analysis->is_finalized)->toBeTrue();
});

test('user has many gap analyses', function () {
    $user = User::factory()->create();
    GapAnalysis::factory()->count(2)->create(['user_id' => $user->id]);

    expect($user->gapAnalyses)->toHaveCount(2);
});
