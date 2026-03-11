<?php

use App\Jobs\AnalyzeJobPostingJob;
use App\Jobs\DiscoverPortfolioLinksJob;
use App\Jobs\FetchJobPostingUrlJob;
use App\Jobs\GenerateResumeJob;
use App\Jobs\IndexLinkJob;
use App\Jobs\ParseResumeJob;
use App\Jobs\PerformGapAnalysisJob;
use App\Models\EvidenceEntry;
use App\Models\GapAnalysis;
use App\Models\JobPosting;
use App\Models\Resume;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->jobPosting = JobPosting::factory()->create(['user_id' => $this->user->id]);
    $this->gapAnalysis = GapAnalysis::factory()->create(['user_id' => $this->user->id]);
    $this->resume = Resume::factory()->create(['user_id' => $this->user->id, 'gap_analysis_id' => $this->gapAnalysis->id]);
    $this->evidenceEntry = EvidenceEntry::factory()->create(['user_id' => $this->user->id]);
    $this->document = $this->user->documents()->create([
        'filename' => 'test.pdf',
        'disk' => 'local',
        'path' => 'test.pdf',
        'mime_type' => 'application/pdf',
        'size' => 1024,
    ]);
});

test('AnalyzeJobPostingJob has retry config', function () {
    $job = new AnalyzeJobPostingJob($this->jobPosting);

    expect($job->tries)->toBe(3)
        ->and($job->backoff)->toBeGreaterThan(0);
});

test('PerformGapAnalysisJob has retry config', function () {
    $job = new PerformGapAnalysisJob($this->gapAnalysis);

    expect($job->tries)->toBe(3)
        ->and($job->backoff)->toBeGreaterThan(0);
});

test('GenerateResumeJob has retry config', function () {
    $job = new GenerateResumeJob($this->resume);

    expect($job->tries)->toBe(3)
        ->and($job->backoff)->toBeGreaterThan(0);
});

test('ParseResumeJob has retry config', function () {
    $job = new ParseResumeJob($this->user, $this->document);

    expect($job->tries)->toBe(3)
        ->and($job->backoff)->toBeGreaterThan(0);
});

test('IndexLinkJob has retry config', function () {
    $job = new IndexLinkJob($this->user, $this->evidenceEntry);

    expect($job->tries)->toBe(3)
        ->and($job->backoff)->toBeGreaterThan(0);
});

test('FetchJobPostingUrlJob has retry config', function () {
    $job = new FetchJobPostingUrlJob($this->jobPosting);

    expect($job->tries)->toBe(3)
        ->and($job->backoff)->toBeGreaterThan(0);
});

test('DiscoverPortfolioLinksJob has retry config', function () {
    $job = new DiscoverPortfolioLinksJob($this->evidenceEntry);

    expect($job->tries)->toBe(3)
        ->and($job->backoff)->toBeGreaterThan(0);
});
