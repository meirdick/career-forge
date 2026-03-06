<?php

use App\Models\EvidenceEntry;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('index displays evidence entries', function () {
    EvidenceEntry::factory()->count(3)->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get('/evidence')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('experience-library/evidence')
                ->has('entries', 3)
        );
});

test('store creates evidence entry', function () {
    $this->actingAs($this->user)->post('/evidence', [
        'type' => 'portfolio',
        'title' => 'GitHub Profile',
        'url' => 'https://github.com/example',
        'description' => 'My open source contributions',
    ])->assertRedirect('/evidence');

    expect(EvidenceEntry::first())
        ->title->toBe('GitHub Profile')
        ->type->toBe('portfolio')
        ->user_id->toBe($this->user->id);
});

test('store validates required fields', function () {
    $this->actingAs($this->user)
        ->post('/evidence', [])
        ->assertSessionHasErrors(['type', 'title']);
});

test('update modifies evidence entry', function () {
    $entry = EvidenceEntry::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->put("/evidence/{$entry->id}", [
            'type' => 'article',
            'title' => 'Updated Title',
        ])
        ->assertRedirect('/evidence');

    expect($entry->fresh())
        ->title->toBe('Updated Title')
        ->type->toBe('article');
});

test('update returns 403 for other users entry', function () {
    $other = User::factory()->create();
    $entry = EvidenceEntry::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->put("/evidence/{$entry->id}", [
            'type' => 'portfolio',
            'title' => 'Hacked',
        ])
        ->assertForbidden();
});

test('destroy deletes evidence entry', function () {
    $entry = EvidenceEntry::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->delete("/evidence/{$entry->id}")
        ->assertRedirect('/evidence');

    expect(EvidenceEntry::find($entry->id))->toBeNull();
});

test('destroy returns 403 for other users entry', function () {
    $other = User::factory()->create();
    $entry = EvidenceEntry::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->delete("/evidence/{$entry->id}")
        ->assertForbidden();
});

test('store normalizes url without protocol', function () {
    $this->actingAs($this->user)->post('/evidence', [
        'type' => 'portfolio',
        'title' => 'My Site',
        'url' => 'www.example.com',
    ])->assertRedirect('/evidence');

    expect(EvidenceEntry::first()->url)->toBe('https://www.example.com');
});

test('store preserves url with existing protocol', function () {
    $this->actingAs($this->user)->post('/evidence', [
        'type' => 'portfolio',
        'title' => 'My Site',
        'url' => 'http://example.com',
    ])->assertRedirect('/evidence');

    expect(EvidenceEntry::first()->url)->toBe('http://example.com');
});
