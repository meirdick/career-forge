<?php

use App\Models\AiInteraction;
use App\Models\Document;
use App\Models\Experience;
use App\Models\JobPosting;
use App\Models\Tag;
use App\Models\User;

// Tags
test('tag factory creates valid model', function () {
    $tag = Tag::factory()->create();

    expect($tag)->toBeInstanceOf(Tag::class)
        ->and($tag->name)->toBeString();
});

test('tag has unique constraint per user and name', function () {
    $user = User::factory()->create();
    Tag::factory()->create(['user_id' => $user->id, 'name' => 'backend']);

    expect(fn () => Tag::factory()->create(['user_id' => $user->id, 'name' => 'backend']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

test('different users can have same tag name', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Tag::factory()->create(['user_id' => $user1->id, 'name' => 'backend']);
    $tag2 = Tag::factory()->create(['user_id' => $user2->id, 'name' => 'backend']);

    expect($tag2)->toBeInstanceOf(Tag::class);
});

test('tag can be morphed to experience', function () {
    $user = User::factory()->create();
    $experience = Experience::factory()->create(['user_id' => $user->id]);
    $tag = Tag::factory()->create(['user_id' => $user->id]);

    $experience->tags()->attach($tag->id);

    expect($experience->tags)->toHaveCount(1)
        ->and($tag->experiences)->toHaveCount(1);
});

test('user has many tags', function () {
    $user = User::factory()->create();
    Tag::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->tags)->toHaveCount(3);
});

// Documents
test('document factory creates valid model', function () {
    $document = Document::factory()->create();

    expect($document)->toBeInstanceOf(Document::class)
        ->and($document->filename)->toBeString()
        ->and($document->path)->toBeString();
});

test('document can be polymorphically attached to experience', function () {
    $experience = Experience::factory()->create();
    $document = Document::factory()->create([
        'user_id' => $experience->user_id,
        'documentable_id' => $experience->id,
        'documentable_type' => Experience::class,
    ]);

    expect($experience->documents)->toHaveCount(1)
        ->and($document->documentable->id)->toBe($experience->id);
});

test('document casts metadata as array', function () {
    $document = Document::factory()->create([
        'metadata' => ['pages' => 5, 'author' => 'Test'],
    ]);

    expect($document->metadata)->toBeArray()
        ->and($document->metadata['pages'])->toBe(5);
});

// AI Interaction
test('ai interaction factory creates valid model', function () {
    $interaction = AiInteraction::factory()->create();

    expect($interaction)->toBeInstanceOf(AiInteraction::class)
        ->and($interaction->purpose)->toBeString()
        ->and($interaction->model_used)->toBeString();
});

test('ai interaction can be polymorphically attached', function () {
    $jobPosting = JobPosting::factory()->create();
    $interaction = AiInteraction::factory()->create([
        'user_id' => $jobPosting->user_id,
        'interactable_id' => $jobPosting->id,
        'interactable_type' => JobPosting::class,
    ]);

    expect($interaction->interactable->id)->toBe($jobPosting->id);
});

test('user has many ai interactions', function () {
    $user = User::factory()->create();
    AiInteraction::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->aiInteractions)->toHaveCount(3);
});

// User cascade delete
test('deleting user cascades to all owned models', function () {
    $user = User::factory()->create();
    $experience = Experience::factory()->create(['user_id' => $user->id]);
    $skill = \App\Models\Skill::factory()->create(['user_id' => $user->id]);
    $tag = Tag::factory()->create(['user_id' => $user->id]);
    $jobPosting = JobPosting::factory()->create(['user_id' => $user->id]);

    $user->delete();

    expect(Experience::find($experience->id))->toBeNull()
        ->and(\App\Models\Skill::find($skill->id))->toBeNull()
        ->and(Tag::find($tag->id))->toBeNull()
        ->and(JobPosting::find($jobPosting->id))->toBeNull();
});
