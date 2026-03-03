<?php

use App\Models\Experience;
use App\Models\Tag;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('index shows tags with usage counts', function () {
    $tag = Tag::factory()->create(['user_id' => $this->user->id]);
    $experience = Experience::factory()->create(['user_id' => $this->user->id]);
    $experience->tags()->attach($tag);

    $this->actingAs($this->user)
        ->get('/tags')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('experience-library/tags')
            ->has('tags', 1)
            ->where('tags.0.name', $tag->name)
            ->where('tags.0.experiences_count', 1)
        );
});

test('store creates a new tag', function () {
    $this->actingAs($this->user)
        ->post('/tags', ['name' => 'leadership'])
        ->assertRedirect();

    expect($this->user->tags()->where('name', 'leadership')->exists())->toBeTrue();
});

test('store validates unique name per user', function () {
    Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'leadership']);

    $this->actingAs($this->user)
        ->post('/tags', ['name' => 'leadership'])
        ->assertSessionHasErrors('name');
});

test('update renames a tag', function () {
    $tag = Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'old']);

    $this->actingAs($this->user)
        ->put("/tags/{$tag->id}", ['name' => 'new'])
        ->assertRedirect();

    expect($tag->fresh()->name)->toBe('new');
});

test('update returns 403 for other users tag', function () {
    $tag = Tag::factory()->create();

    $this->actingAs($this->user)
        ->put("/tags/{$tag->id}", ['name' => 'test'])
        ->assertForbidden();
});

test('destroy deletes a tag', function () {
    $tag = Tag::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->delete("/tags/{$tag->id}")
        ->assertRedirect();

    expect(Tag::find($tag->id))->toBeNull();
});

test('toggle attaches tag to experience', function () {
    $tag = Tag::factory()->create(['user_id' => $this->user->id]);
    $experience = Experience::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->post('/tags/toggle', [
            'tag_id' => $tag->id,
            'taggable_id' => $experience->id,
            'taggable_type' => 'experience',
        ])
        ->assertRedirect();

    expect($experience->tags()->where('tags.id', $tag->id)->exists())->toBeTrue();
});

test('toggle detaches tag from experience', function () {
    $tag = Tag::factory()->create(['user_id' => $this->user->id]);
    $experience = Experience::factory()->create(['user_id' => $this->user->id]);
    $experience->tags()->attach($tag);

    $this->actingAs($this->user)
        ->post('/tags/toggle', [
            'tag_id' => $tag->id,
            'taggable_id' => $experience->id,
            'taggable_type' => 'experience',
        ])
        ->assertRedirect();

    expect($experience->tags()->where('tags.id', $tag->id)->exists())->toBeFalse();
});
