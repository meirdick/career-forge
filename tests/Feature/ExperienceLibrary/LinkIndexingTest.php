<?php

use App\Ai\Agents\LinkIndexer;
use App\Models\EvidenceEntry;
use App\Models\User;
use App\Services\WebScraperService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->entry = EvidenceEntry::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://github.com/testuser',
    ]);
});

test('index link returns extracted professional info', function () {
    $this->mock(WebScraperService::class)
        ->shouldReceive('scrape')
        ->with('https://github.com/testuser')
        ->andReturn('# John Doe - Full Stack Developer\nBuilt a microservices platform handling 1M requests/day.');

    LinkIndexer::fake();

    $this->actingAs($this->user)
        ->postJson("/evidence/{$this->entry->id}/index-link")
        ->assertSuccessful()
        ->assertJsonStructure(['skills', 'accomplishments', 'projects']);

    LinkIndexer::assertPrompted(fn ($prompt) => str_contains($prompt->prompt, 'web page content'));
});

test('index link returns 403 for other users evidence', function () {
    $other = User::factory()->create();

    $this->actingAs($other)
        ->postJson("/evidence/{$this->entry->id}/index-link")
        ->assertForbidden();
});

test('index link returns 422 when URL cannot be fetched', function () {
    $this->mock(WebScraperService::class)
        ->shouldReceive('scrape')
        ->andReturn(null);

    $this->actingAs($this->user)
        ->postJson("/evidence/{$this->entry->id}/index-link")
        ->assertUnprocessable();
});
