<?php

use App\Enums\EducationType;
use App\Models\EducationEntry;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('index displays education entries', function () {
    EducationEntry::factory()->count(3)->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get('/education')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('experience-library/education')
                ->has('entries', 3)
        );
});

test('store creates education entry', function () {
    $this->actingAs($this->user)->post('/education', [
        'type' => 'degree',
        'institution' => 'MIT',
        'title' => 'BS Computer Science',
        'field' => 'Computer Science',
        'sort_order' => 0,
    ])->assertRedirect('/education');

    expect(EducationEntry::first())
        ->institution->toBe('MIT')
        ->title->toBe('BS Computer Science')
        ->type->toBe(EducationType::Degree)
        ->user_id->toBe($this->user->id);
});

test('store validates required fields', function () {
    $this->actingAs($this->user)
        ->post('/education', [])
        ->assertSessionHasErrors(['type', 'institution', 'title']);
});

test('store validates type must be valid enum', function () {
    $this->actingAs($this->user)
        ->post('/education', [
            'type' => 'invalid',
            'institution' => 'Test',
            'title' => 'Test',
            'sort_order' => 0,
        ])
        ->assertSessionHasErrors('type');
});

test('update modifies education entry', function () {
    $entry = EducationEntry::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->put("/education/{$entry->id}", [
            'type' => 'certification',
            'institution' => 'Updated Institution',
            'title' => 'Updated Title',
            'sort_order' => 0,
        ])
        ->assertRedirect('/education');

    expect($entry->fresh())
        ->institution->toBe('Updated Institution')
        ->type->toBe(EducationType::Certification);
});

test('update returns 403 for other users entry', function () {
    $other = User::factory()->create();
    $entry = EducationEntry::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->put("/education/{$entry->id}", [
            'type' => 'degree',
            'institution' => 'Hacked',
            'title' => 'Hacked',
            'sort_order' => 0,
        ])
        ->assertForbidden();
});

test('destroy deletes education entry', function () {
    $entry = EducationEntry::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->delete("/education/{$entry->id}")
        ->assertRedirect('/education');

    expect(EducationEntry::find($entry->id))->toBeNull();
});

test('destroy returns 403 for other users entry', function () {
    $other = User::factory()->create();
    $entry = EducationEntry::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->delete("/education/{$entry->id}")
        ->assertForbidden();
});
