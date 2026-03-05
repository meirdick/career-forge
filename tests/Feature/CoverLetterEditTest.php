<?php

use App\Models\Application;
use App\Models\User;

test('can update cover letter text', function () {
    $user = User::factory()->create();
    $application = Application::factory()->create([
        'user_id' => $user->id,
        'cover_letter' => 'Original cover letter',
    ]);

    $this->actingAs($user)
        ->putJson("/applications/{$application->id}/cover-letter", [
            'cover_letter' => 'Updated cover letter with new content',
        ])
        ->assertOk();

    $application->refresh();
    expect($application->cover_letter)->toBe('Updated cover letter with new content');
});

test('cover letter update requires text', function () {
    $user = User::factory()->create();
    $application = Application::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->putJson("/applications/{$application->id}/cover-letter", [
            'cover_letter' => '',
        ])
        ->assertUnprocessable();
});

test('cannot update cover letter for other user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $application = Application::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->putJson("/applications/{$application->id}/cover-letter", [
            'cover_letter' => 'Should not work',
        ])
        ->assertForbidden();
});
