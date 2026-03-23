<?php

use App\Jobs\AnalyzeJobPostingJob;
use App\Jobs\FetchJobPostingUrlJob;
use App\Models\JobPosting;
use App\Models\User;
use App\Notifications\JobPostingScrapeFailed;
use App\Services\WebScraperService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->jobPosting = JobPosting::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com/job/123',
        'raw_text' => null,
        'analyzed_at' => null,
    ]);
});

function qualityJobContent(): string
{
    return <<<'MD'
    # Senior Software Engineer

    ## About Us

    Acme Corp is a leading technology company building innovative solutions for the modern workforce.

    ## Responsibilities

    - Design and implement scalable microservices architecture
    - Lead code reviews and mentor junior engineers on the team
    - Collaborate with product managers to define technical requirements

    ## Requirements

    - 5+ years of professional software engineering experience
    - Strong proficiency in Python, Go, or similar languages
    - Experience with distributed systems and cloud platforms

    ## Benefits

    - Competitive salary range of $150,000 - $200,000 per year
    - Comprehensive health, dental, and vision insurance

    ## How to Apply

    Submit your resume and a brief cover letter explaining why you are interested in this role.
    MD;
}

test('stores content and dispatches analyze job when scrape returns quality content', function () {
    Queue::fake([AnalyzeJobPostingJob::class]);

    $scraper = Mockery::mock(WebScraperService::class);
    $scraper->shouldReceive('scrape')->andReturn(qualityJobContent());
    app()->instance(WebScraperService::class, $scraper);

    (new FetchJobPostingUrlJob($this->jobPosting))->handle($scraper);

    $this->jobPosting->refresh();
    expect($this->jobPosting->raw_text)->toBe(qualityJobContent());

    Queue::assertPushed(AnalyzeJobPostingJob::class);
});

test('sends failure notification when scrape returns null', function () {
    Notification::fake();

    $scraper = Mockery::mock(WebScraperService::class);
    $scraper->shouldReceive('scrape')->andReturn(null);

    (new FetchJobPostingUrlJob($this->jobPosting))->handle($scraper);

    $this->jobPosting->refresh();
    expect($this->jobPosting->raw_text)->toBeNull();

    Notification::assertSentTo($this->user, JobPostingScrapeFailed::class);
});

test('sends failure notification when scrape returns shell content', function () {
    Notification::fake();
    Queue::fake([AnalyzeJobPostingJob::class]);

    $shellContent = "Skip to content\nSign in\nLoading...\n© 2024 Workday, Inc. All rights reserved.\n"
        ."Privacy\nTerms of Use\nCookie Preferences\nPowered by Workday\n"
        ."We use cookies to ensure you get the best experience.\nAccept All Cookies\n"
        ."Loading application content, please wait...\nIf this page does not load, please refresh.";

    $scraper = Mockery::mock(WebScraperService::class);
    $scraper->shouldReceive('scrape')->andReturn($shellContent);

    (new FetchJobPostingUrlJob($this->jobPosting))->handle($scraper);

    $this->jobPosting->refresh();
    expect($this->jobPosting->raw_text)->toBeNull();

    Notification::assertSentTo($this->user, JobPostingScrapeFailed::class);
    Queue::assertNotPushed(AnalyzeJobPostingJob::class);
});

test('sends failure notification on final retry failure', function () {
    Notification::fake();

    $job = new FetchJobPostingUrlJob($this->jobPosting);
    $job->failed(new RuntimeException('Connection timed out'));

    Notification::assertSentTo($this->user, JobPostingScrapeFailed::class);
});

test('skips when job posting already has raw_text', function () {
    $this->jobPosting->update(['raw_text' => 'existing content']);

    $scraper = Mockery::mock(WebScraperService::class);
    $scraper->shouldNotReceive('scrape');

    (new FetchJobPostingUrlJob($this->jobPosting))->handle($scraper);
});

test('skips when job posting has no url', function () {
    $this->jobPosting->update(['url' => null]);

    $scraper = Mockery::mock(WebScraperService::class);
    $scraper->shouldNotReceive('scrape');

    (new FetchJobPostingUrlJob($this->jobPosting))->handle($scraper);
});

test('populates title from URL slug when scrape fails quality check', function () {
    Notification::fake();

    $jobPosting = JobPosting::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://gen.wd1.myworkdayjobs.com/en-US/careers/job/USA---California-Mountain-View/Senior-Director--Lead-Product-Manager---Norton-360_55006',
        'title' => null,
        'raw_text' => null,
    ]);

    $shellContent = "Skip to content\nSign in\nLoading...\n© 2024 Workday, Inc. All rights reserved.\n"
        ."Privacy\nTerms of Use\nCookie Preferences\nPowered by Workday\n"
        ."We use cookies to ensure you get the best experience.\nAccept All Cookies\n"
        ."Loading application content, please wait...\nIf this page does not load, please refresh.";

    $scraper = Mockery::mock(WebScraperService::class);
    $scraper->shouldReceive('scrape')->andReturn($shellContent);

    (new FetchJobPostingUrlJob($jobPosting))->handle($scraper);

    $jobPosting->refresh();
    expect($jobPosting->title)->toBe('Senior Director Lead Product Manager Norton 360');
});

test('does not overwrite existing title when populating from URL', function () {
    Notification::fake();

    $jobPosting = JobPosting::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com/jobs/software-engineer_123',
        'title' => 'My Custom Title',
        'raw_text' => null,
    ]);

    $scraper = Mockery::mock(WebScraperService::class);
    $scraper->shouldReceive('scrape')->andReturn(null);

    (new FetchJobPostingUrlJob($jobPosting))->handle($scraper);

    $jobPosting->refresh();
    expect($jobPosting->title)->toBe('My Custom Title');
});
