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

test('sends failure notification and sets analyzed_at when scrape returns null', function () {
    Notification::fake();

    $scraper = Mockery::mock(WebScraperService::class);
    $scraper->shouldReceive('scrape')->andReturn(null);

    (new FetchJobPostingUrlJob($this->jobPosting))->handle($scraper);

    $this->jobPosting->refresh();
    expect($this->jobPosting->raw_text)->toBeNull()
        ->and($this->jobPosting->analyzed_at)->not->toBeNull();

    Notification::assertSentTo($this->user, JobPostingScrapeFailed::class);
});

test('sends failure notification and sets analyzed_at when scrape returns shell content', function () {
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
    expect($this->jobPosting->raw_text)->toBeNull()
        ->and($this->jobPosting->analyzed_at)->not->toBeNull();

    Notification::assertSentTo($this->user, JobPostingScrapeFailed::class);
    Queue::assertNotPushed(AnalyzeJobPostingJob::class);
});

test('sends failure notification and sets analyzed_at on final retry failure', function () {
    Notification::fake();

    $job = new FetchJobPostingUrlJob($this->jobPosting);
    $job->failed(new RuntimeException('Connection timed out'));

    $this->jobPosting->refresh();
    expect($this->jobPosting->analyzed_at)->not->toBeNull();

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

test('extracts title and company from partial scrape content even when quality fails', function () {
    Notification::fake();
    Queue::fake([AnalyzeJobPostingJob::class]);

    $jobPosting = JobPosting::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com/job/123',
        'title' => null,
        'company' => null,
        'raw_text' => null,
    ]);

    $partialContent = "# Staff Product Designer\n\n**Company:** Figma\n**Location:** San Francisco, CA\n\nSign in\nLoading...";

    $scraper = Mockery::mock(WebScraperService::class);
    $scraper->shouldReceive('scrape')->andReturn($partialContent);

    (new FetchJobPostingUrlJob($jobPosting))->handle($scraper);

    $jobPosting->refresh();
    expect($jobPosting->title)->toBe('Staff Product Designer')
        ->and($jobPosting->company)->toBe('Figma')
        ->and($jobPosting->location)->toBe('San Francisco, CA')
        ->and($jobPosting->raw_text)->toBeNull();

    Queue::assertNotPushed(AnalyzeJobPostingJob::class);
    Notification::assertSentTo($jobPosting->user, JobPostingScrapeFailed::class);
});

test('populates title from URL slug immediately before scraping', function () {
    Queue::fake([AnalyzeJobPostingJob::class]);

    $jobPosting = JobPosting::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://boards.greenhouse.io/company/jobs/senior-engineer_456',
        'title' => null,
        'raw_text' => null,
    ]);

    $scraper = Mockery::mock(WebScraperService::class);
    $scraper->shouldReceive('scrape')->andReturn(qualityJobContent());

    (new FetchJobPostingUrlJob($jobPosting))->handle($scraper);

    $jobPosting->refresh();
    // Quality content has "# Senior Software Engineer" which overrides the URL slug
    expect($jobPosting->title)->toBe('Senior Software Engineer');
});

test('content metadata overrides url slug title', function () {
    Queue::fake([AnalyzeJobPostingJob::class]);

    $jobPosting = JobPosting::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com/jobs/some-slug_123',
        'title' => null,
        'company' => null,
        'raw_text' => null,
    ]);

    $content = "# Actual Job Title\n\n**Company:** Real Company\n\n## About Us\n\n"
        ."We are a leading technology company building innovative solutions.\n\n"
        ."## Responsibilities\n\n- Design scalable systems\n- Lead code reviews and mentor engineers\n"
        ."- Collaborate with product managers on requirements\n\n"
        ."## Requirements\n\n- 5+ years of experience in software engineering\n"
        ."- Strong skills in Python, Go, or similar languages\n"
        ."- Experience with distributed systems and cloud platforms\n\n"
        ."## Benefits\n\n- Salary range of \$150,000 - \$200,000 per year\n"
        ."- Health, dental, and vision insurance coverage\n\n"
        ."## How to Apply\n\nSubmit your resume and cover letter for this role.";

    $scraper = Mockery::mock(WebScraperService::class);
    $scraper->shouldReceive('scrape')->andReturn($content);

    (new FetchJobPostingUrlJob($jobPosting))->handle($scraper);

    $jobPosting->refresh();
    // URL slug set "Some Slug", but content heading "Actual Job Title" overwrites it
    expect($jobPosting->title)->toBe('Actual Job Title')
        ->and($jobPosting->company)->toBe('Real Company');
});
