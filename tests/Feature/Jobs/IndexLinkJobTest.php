<?php

use App\Ai\Agents\LinkIndexer;
use App\Jobs\IndexLinkJob;
use App\Models\EvidenceEntry;
use App\Models\User;
use App\Services\WebScraperService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->entry = EvidenceEntry::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://github.com/testuser',
    ]);
});

test('job stores completed results in cache on success', function () {
    $this->mock(WebScraperService::class)
        ->shouldReceive('scrape')
        ->with('https://github.com/testuser')
        ->andReturn('# John Doe - Full Stack Developer');

    LinkIndexer::fake();

    (new IndexLinkJob($this->user, $this->entry))->handle(app(WebScraperService::class));

    $cached = Cache::get("evidence-index:{$this->entry->id}");
    expect($cached)
        ->status->toBe('completed')
        ->data->toBeArray()
        ->data->toHaveKeys(['skills', 'accomplishments', 'projects']);

    LinkIndexer::assertPrompted(fn ($prompt) => str_contains($prompt->prompt, 'web page content'));
});

test('job stores failed state in cache when scrape returns null', function () {
    $this->mock(WebScraperService::class)
        ->shouldReceive('scrape')
        ->andReturn(null);

    (new IndexLinkJob($this->user, $this->entry))->handle(app(WebScraperService::class));

    $cached = Cache::get("evidence-index:{$this->entry->id}");
    expect($cached)
        ->status->toBe('failed')
        ->error->toBe('Could not fetch URL content.');
});

test('job stores failed state in cache on exception', function () {
    $job = new IndexLinkJob($this->user, $this->entry);
    $job->failed(new RuntimeException('AI service unavailable'));

    $cached = Cache::get("evidence-index:{$this->entry->id}");
    expect($cached)
        ->status->toBe('failed')
        ->error->toBe('AI service unavailable');
});

test('job scrapes all selected pages and combines content', function () {
    $this->entry->update([
        'pages' => [
            'https://github.com/testuser/project-a',
            'https://github.com/testuser/project-b',
        ],
    ]);

    $mock = $this->mock(WebScraperService::class);
    $mock->shouldReceive('scrape')
        ->with('https://github.com/testuser')
        ->andReturn('# Main Profile');
    $mock->shouldReceive('scrape')
        ->with('https://github.com/testuser/project-a')
        ->andReturn('# Project A');
    $mock->shouldReceive('scrape')
        ->with('https://github.com/testuser/project-b')
        ->andReturn('# Project B');

    LinkIndexer::fake();

    (new IndexLinkJob($this->user, $this->entry))->handle(app(WebScraperService::class));

    $cached = Cache::get("evidence-index:{$this->entry->id}");
    expect($cached)->status->toBe('completed');

    LinkIndexer::assertPrompted(function ($prompt) {
        return str_contains($prompt->prompt, '# Main Profile')
            && str_contains($prompt->prompt, '# Project A')
            && str_contains($prompt->prompt, '# Project B');
    });
});

test('job succeeds when some pages fail to scrape', function () {
    $this->entry->update([
        'pages' => ['https://github.com/testuser/project-a'],
    ]);

    $mock = $this->mock(WebScraperService::class);
    $mock->shouldReceive('scrape')
        ->with('https://github.com/testuser')
        ->andReturn('# Main Profile');
    $mock->shouldReceive('scrape')
        ->with('https://github.com/testuser/project-a')
        ->andReturn(null);

    LinkIndexer::fake();

    (new IndexLinkJob($this->user, $this->entry))->handle(app(WebScraperService::class));

    $cached = Cache::get("evidence-index:{$this->entry->id}");
    expect($cached)->status->toBe('completed');

    LinkIndexer::assertPrompted(function ($prompt) {
        return str_contains($prompt->prompt, '# Main Profile')
            && ! str_contains($prompt->prompt, 'project-a');
    });
});
