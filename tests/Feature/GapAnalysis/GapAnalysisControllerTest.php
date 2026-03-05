<?php

use App\Jobs\PerformGapAnalysisJob;
use App\Models\GapAnalysis;
use App\Models\IdealCandidateProfile;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    Queue::fake();
});

test('guest cannot access gap analysis pages', function () {
    $this->get('/gap-analyses/1')->assertRedirect('/login');
    $this->post('/job-postings/1/gap-analysis')->assertRedirect('/login');
});

test('store creates gap analysis and dispatches job', function () {
    $posting = JobPosting::factory()->analyzed()->create(['user_id' => $this->user->id]);
    IdealCandidateProfile::factory()->create(['job_posting_id' => $posting->id]);

    $this->actingAs($this->user)
        ->post("/job-postings/{$posting->id}/gap-analysis")
        ->assertRedirect();

    $analysis = GapAnalysis::first();
    expect($analysis)
        ->user_id->toBe($this->user->id)
        ->ideal_candidate_profile_id->toBe($posting->idealCandidateProfile->id);

    Queue::assertPushed(PerformGapAnalysisJob::class, function ($job) use ($analysis) {
        return $job->gapAnalysis->id === $analysis->id;
    });
});

test('store returns 422 when posting has no profile', function () {
    $posting = JobPosting::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->post("/job-postings/{$posting->id}/gap-analysis")
        ->assertStatus(422);
});

test('store returns 403 for other users posting', function () {
    $other = User::factory()->create();
    $posting = JobPosting::factory()->analyzed()->create(['user_id' => $other->id]);
    IdealCandidateProfile::factory()->create(['job_posting_id' => $posting->id]);

    $this->actingAs($this->user)
        ->post("/job-postings/{$posting->id}/gap-analysis")
        ->assertForbidden();
});

test('show displays gap analysis', function () {
    $posting = JobPosting::factory()->analyzed()->create(['user_id' => $this->user->id]);
    $profile = IdealCandidateProfile::factory()->create(['job_posting_id' => $posting->id]);
    $analysis = GapAnalysis::factory()->create([
        'user_id' => $this->user->id,
        'ideal_candidate_profile_id' => $profile->id,
    ]);

    $this->actingAs($this->user)
        ->get("/gap-analyses/{$analysis->id}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('gap-analyses/show')
                ->has('gapAnalysis')
                ->where('gapAnalysis.id', $analysis->id)
        );
});

test('show returns 403 for other users analysis', function () {
    $other = User::factory()->create();
    $posting = JobPosting::factory()->analyzed()->create(['user_id' => $other->id]);
    $profile = IdealCandidateProfile::factory()->create(['job_posting_id' => $posting->id]);
    $analysis = GapAnalysis::factory()->create([
        'user_id' => $other->id,
        'ideal_candidate_profile_id' => $profile->id,
    ]);

    $this->actingAs($this->user)
        ->get("/gap-analyses/{$analysis->id}")
        ->assertForbidden();
});
