<?php

use App\Jobs\IndexLinkJob;
use App\Models\EvidenceEntry;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->entry = EvidenceEntry::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://github.com/testuser',
    ]);
    Queue::fake();
});

test('index link dispatches job and redirects', function () {
    $this->actingAs($this->user)
        ->post("/evidence/{$this->entry->id}/index-link")
        ->assertRedirect();

    Queue::assertPushed(IndexLinkJob::class, function ($job) {
        return $job->evidenceEntry->id === $this->entry->id
            && $job->user->id === $this->user->id;
    });

    $cached = Cache::get("evidence-index:{$this->entry->id}");
    expect($cached)->status->toBe('processing');
});

test('index link returns 403 for other users evidence', function () {
    $other = User::factory()->create();

    $this->actingAs($other)
        ->post("/evidence/{$this->entry->id}/index-link")
        ->assertForbidden();

    Queue::assertNothingPushed();
});

test('index passes cached index results as prop', function () {
    Cache::put("evidence-index:{$this->entry->id}", [
        'status' => 'completed',
        'data' => [
            'skills' => [['name' => 'PHP', 'category' => 'technical']],
            'accomplishments' => [],
            'projects' => [],
        ],
    ]);

    $this->actingAs($this->user)
        ->get('/evidence')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('experience-library/evidence')
                ->has("indexResults.{$this->entry->id}")
                ->where("indexResults.{$this->entry->id}.status", 'completed')
                ->has("indexResults.{$this->entry->id}.data.skills", 1)
        );
});

test('index does not include results for entries without urls', function () {
    $noUrlEntry = EvidenceEntry::factory()->create([
        'user_id' => $this->user->id,
        'url' => null,
    ]);

    Cache::put("evidence-index:{$noUrlEntry->id}", [
        'status' => 'completed',
        'data' => ['skills' => [], 'accomplishments' => [], 'projects' => []],
    ]);

    $this->actingAs($this->user)
        ->get('/evidence')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->missing("indexResults.{$noUrlEntry->id}")
        );
});
