<?php

use App\Jobs\IndexLinkJob;
use App\Models\EvidenceEntry;
use App\Models\Skill;
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

test('import results creates skills, accomplishments, and projects', function () {
    Cache::put("evidence-index:{$this->entry->id}", [
        'status' => 'completed',
        'data' => [
            'skills' => [['name' => 'PHP', 'category' => 'technical']],
            'accomplishments' => [['title' => 'Built API', 'description' => 'Built a REST API']],
            'projects' => [['name' => 'My Project', 'description' => 'A cool project']],
        ],
    ]);

    $this->actingAs($this->user)
        ->post("/evidence/{$this->entry->id}/import-results")
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($this->user->skills()->where('name', 'PHP')->exists())->toBeTrue();
    expect($this->user->accomplishments()->where('title', 'Built API')->exists())->toBeTrue();
    expect($this->user->projects()->where('name', 'My Project')->exists())->toBeTrue();
});

test('import results updates cache status to imported and preserves data', function () {
    $data = [
        'skills' => [['name' => 'Laravel', 'category' => 'technical']],
        'accomplishments' => [],
        'projects' => [],
    ];

    Cache::put("evidence-index:{$this->entry->id}", [
        'status' => 'completed',
        'data' => $data,
    ]);

    $this->actingAs($this->user)
        ->post("/evidence/{$this->entry->id}/import-results");

    $cached = Cache::get("evidence-index:{$this->entry->id}");
    expect($cached)->status->toBe('imported')
        ->and($cached)->data->toBe($data);
});

test('import results returns 422 when no completed results', function () {
    $this->actingAs($this->user)
        ->post("/evidence/{$this->entry->id}/import-results")
        ->assertStatus(422);
});

test('import results returns 422 for processing status', function () {
    Cache::put("evidence-index:{$this->entry->id}", [
        'status' => 'processing',
    ]);

    $this->actingAs($this->user)
        ->post("/evidence/{$this->entry->id}/import-results")
        ->assertStatus(422);
});

test('import results returns 403 for other users evidence', function () {
    $other = User::factory()->create();

    Cache::put("evidence-index:{$this->entry->id}", [
        'status' => 'completed',
        'data' => ['skills' => [], 'accomplishments' => [], 'projects' => []],
    ]);

    $this->actingAs($other)
        ->post("/evidence/{$this->entry->id}/import-results")
        ->assertForbidden();
});

test('import results skips duplicate skills on re-import', function () {
    Skill::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'PHP',
        'category' => 'technical',
    ]);

    Cache::put("evidence-index:{$this->entry->id}", [
        'status' => 'completed',
        'data' => [
            'skills' => [['name' => 'PHP', 'category' => 'technical'], ['name' => 'React', 'category' => 'technical']],
            'accomplishments' => [],
            'projects' => [],
        ],
    ]);

    $this->actingAs($this->user)
        ->post("/evidence/{$this->entry->id}/import-results")
        ->assertRedirect();

    expect($this->user->skills()->where('name', 'PHP')->count())->toBe(1);
    expect($this->user->skills()->where('name', 'React')->exists())->toBeTrue();
});

test('index page shows imported status in props', function () {
    Cache::put("evidence-index:{$this->entry->id}", [
        'status' => 'imported',
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
                ->where("indexResults.{$this->entry->id}.status", 'imported')
                ->has("indexResults.{$this->entry->id}.data.skills", 1)
        );
});
