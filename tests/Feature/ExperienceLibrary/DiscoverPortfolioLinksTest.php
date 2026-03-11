<?php

use App\Jobs\DiscoverPortfolioLinksJob;
use App\Models\EvidenceEntry;
use App\Models\User;
use App\Services\WebScraperService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('discover links dispatches job and sets processing cache', function () {
    Queue::fake();

    $entry = EvidenceEntry::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com',
    ]);

    $this->actingAs($this->user)
        ->post(route('evidence.discover-links', $entry))
        ->assertRedirect();

    Queue::assertPushed(DiscoverPortfolioLinksJob::class, function ($job) use ($entry) {
        return $job->evidenceEntry->id === $entry->id;
    });

    expect(Cache::get("evidence-discover:{$entry->id}")['status'])->toBe('processing');
});

test('discover links returns 403 for other users entry', function () {
    $other = User::factory()->create();
    $entry = EvidenceEntry::factory()->create([
        'user_id' => $other->id,
        'url' => 'https://example.com',
    ]);

    $this->actingAs($this->user)
        ->post(route('evidence.discover-links', $entry))
        ->assertForbidden();
});

test('discover links returns 422 for entry without url', function () {
    $entry = EvidenceEntry::factory()->create([
        'user_id' => $this->user->id,
        'url' => null,
    ]);

    $this->actingAs($this->user)
        ->post(route('evidence.discover-links', $entry))
        ->assertStatus(422);
});

test('save selected pages stores urls on evidence entry', function () {
    $entry = EvidenceEntry::factory()->create([
        'user_id' => $this->user->id,
        'type' => 'portfolio',
        'url' => 'https://example.com',
    ]);

    $this->actingAs($this->user)
        ->post(route('evidence.save-pages', $entry), [
            'urls' => [
                'https://example.com/projects/app-one',
                'https://example.com/projects/app-two',
            ],
        ])
        ->assertRedirect();

    $entry->refresh();
    expect($entry->pages)->toBe([
        'https://example.com/projects/app-one',
        'https://example.com/projects/app-two',
    ]);
    expect($this->user->evidenceEntries()->count())->toBe(1);
});

test('save selected pages validates urls', function () {
    $entry = EvidenceEntry::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com',
    ]);

    $this->actingAs($this->user)
        ->post(route('evidence.save-pages', $entry), ['urls' => []])
        ->assertSessionHasErrors(['urls']);
});

test('save selected pages returns 403 for other users entry', function () {
    $other = User::factory()->create();
    $entry = EvidenceEntry::factory()->create([
        'user_id' => $other->id,
        'url' => 'https://example.com',
    ]);

    $this->actingAs($this->user)
        ->post(route('evidence.save-pages', $entry), [
            'urls' => ['https://example.com/page'],
        ])
        ->assertForbidden();
});

test('discover portfolio links job caches discovered links', function () {
    $entry = EvidenceEntry::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com',
    ]);

    $mockScraper = Mockery::mock(WebScraperService::class);
    $mockScraper->shouldReceive('discoverLinks')
        ->with('https://example.com')
        ->once()
        ->andReturn([
            ['url' => 'https://example.com/about'],
            ['url' => 'https://example.com/projects'],
        ]);

    app()->instance(WebScraperService::class, $mockScraper);

    $job = new DiscoverPortfolioLinksJob($entry);
    $job->handle($mockScraper);

    $cached = Cache::get("evidence-discover:{$entry->id}");
    expect($cached['status'])->toBe('completed');
    expect($cached['links'])->toHaveCount(2);
});

test('discover portfolio links job caches failure when null returned', function () {
    $entry = EvidenceEntry::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com',
    ]);

    $mockScraper = Mockery::mock(WebScraperService::class);
    $mockScraper->shouldReceive('discoverLinks')
        ->with('https://example.com')
        ->once()
        ->andReturn(null);

    $job = new DiscoverPortfolioLinksJob($entry);
    $job->handle($mockScraper);

    $cached = Cache::get("evidence-discover:{$entry->id}");
    expect($cached['status'])->toBe('failed');
});
