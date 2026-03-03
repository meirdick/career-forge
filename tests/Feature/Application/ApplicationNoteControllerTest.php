<?php

use App\Models\Application;
use App\Models\ApplicationNote;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->application = Application::factory()->create(['user_id' => $this->user->id]);
});

test('store creates a note', function () {
    $this->actingAs($this->user)
        ->post("/applications/{$this->application->id}/notes", [
            'content' => 'Follow up next week',
        ])
        ->assertRedirect();

    expect($this->application->applicationNotes)->toHaveCount(1);
    expect($this->application->applicationNotes->first()->content)->toBe('Follow up next week');
});

test('store validates content is required', function () {
    $this->actingAs($this->user)
        ->post("/applications/{$this->application->id}/notes", [])
        ->assertSessionHasErrors('content');
});

test('store returns 403 for other users application', function () {
    $other = User::factory()->create();
    $application = Application::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->post("/applications/{$application->id}/notes", [
            'content' => 'Test note',
        ])
        ->assertForbidden();
});

test('update modifies note content', function () {
    $note = ApplicationNote::factory()->create(['application_id' => $this->application->id]);

    $this->actingAs($this->user)
        ->put("/applications/{$this->application->id}/notes/{$note->id}", [
            'content' => 'Updated note',
        ])
        ->assertRedirect();

    expect($note->fresh()->content)->toBe('Updated note');
});

test('destroy deletes note', function () {
    $note = ApplicationNote::factory()->create(['application_id' => $this->application->id]);

    $this->actingAs($this->user)
        ->delete("/applications/{$this->application->id}/notes/{$note->id}")
        ->assertRedirect();

    expect(ApplicationNote::find($note->id))->toBeNull();
});
