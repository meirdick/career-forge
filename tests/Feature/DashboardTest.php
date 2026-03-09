<?php

use App\Models\GapAnalysis;
use App\Models\IdealCandidateProfile;
use App\Models\JobPosting;
use App\Models\Resume;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('new user sees onboarding state', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard')
        ->where('isNewUser', true)
        ->where('pipelineContinuation', null)
    );
});

test('user with data sees normal dashboard', function () {
    $user = User::factory()->create();
    $user->experiences()->create([
        'company' => 'Acme Corp',
        'title' => 'Engineer',
        'started_at' => now()->subYear(),
    ]);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard')
        ->where('isNewUser', false)
    );
});

test('pipeline continuation suggests gap analysis as next step', function () {
    $user = User::factory()->create();
    $jobPosting = JobPosting::factory()->create([
        'user_id' => $user->id,
        'title' => 'Senior Dev',
        'company' => 'Test Corp',
    ]);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard')
        ->has('pipelineContinuation', fn ($continuation) => $continuation
            ->where('nextStep', 'gap_analysis')
            ->where('nextStepLabel', 'Run Gap Analysis')
            ->where('nextStepUrl', "/job-postings/{$jobPosting->id}")
            ->where('currentStepLabel', 'View Job Posting')
            ->where('currentStepUrl', "/job-postings/{$jobPosting->id}")
            ->has('jobPosting')
            ->etc()
        )
    );
});

test('pipeline continuation suggests resume as next step when gap analysis exists', function () {
    $user = User::factory()->create();
    $jobPosting = JobPosting::factory()->create(['user_id' => $user->id]);
    $icp = IdealCandidateProfile::factory()->create(['job_posting_id' => $jobPosting->id]);
    $gapAnalysis = GapAnalysis::factory()->create([
        'user_id' => $user->id,
        'ideal_candidate_profile_id' => $icp->id,
    ]);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard')
        ->has('pipelineContinuation', fn ($continuation) => $continuation
            ->where('nextStep', 'resume')
            ->where('nextStepLabel', 'Generate Resume')
            ->where('nextStepUrl', "/gap-analyses/{$gapAnalysis->id}")
            ->where('currentStepLabel', 'View Gap Analysis')
            ->where('currentStepUrl', "/gap-analyses/{$gapAnalysis->id}")
            ->etc()
        )
    );
});

test('pipeline continuation suggests application as next step when resume exists', function () {
    $user = User::factory()->create();
    $jobPosting = JobPosting::factory()->create(['user_id' => $user->id]);
    $icp = IdealCandidateProfile::factory()->create(['job_posting_id' => $jobPosting->id]);
    GapAnalysis::factory()->create([
        'user_id' => $user->id,
        'ideal_candidate_profile_id' => $icp->id,
    ]);
    $resume = Resume::factory()->create([
        'user_id' => $user->id,
        'job_posting_id' => $jobPosting->id,
    ]);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard')
        ->has('pipelineContinuation', fn ($continuation) => $continuation
            ->where('nextStep', 'application')
            ->where('nextStepLabel', 'Create Application')
            ->where('nextStepUrl', '/applications/create')
            ->where('currentStepLabel', 'View Resume')
            ->where('currentStepUrl', "/resumes/{$resume->id}")
            ->etc()
        )
    );
});
