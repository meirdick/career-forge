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
