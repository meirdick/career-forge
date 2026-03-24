<?php

use App\Models\Application;
use App\Models\User;

test('can export cover letter as pdf', function () {
    $user = User::factory()->create();
    $application = Application::factory()->create([
        'user_id' => $user->id,
        'cover_letter' => "Dear Hiring Manager,\n\nI am writing to express my interest in the position.\n\nSincerely,\nJohn Doe",
    ]);

    $this->actingAs($user)
        ->get("/applications/{$application->id}/cover-letter/export/pdf")
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('can export cover letter as docx', function () {
    $user = User::factory()->create();
    $application = Application::factory()->create([
        'user_id' => $user->id,
        'cover_letter' => "Dear Hiring Manager,\n\nI am writing to express my interest in the position.\n\nSincerely,\nJohn Doe",
    ]);

    $response = $this->actingAs($user)
        ->get("/applications/{$application->id}/cover-letter/export/docx")
        ->assertOk();

    expect($response->headers->get('content-disposition'))->toContain('.docx');
});

test('cannot export cover letter for other user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $application = Application::factory()->create([
        'user_id' => $otherUser->id,
        'cover_letter' => 'Some cover letter text',
    ]);

    $this->actingAs($user)
        ->get("/applications/{$application->id}/cover-letter/export/pdf")
        ->assertForbidden();
});

test('cannot export empty cover letter', function () {
    $user = User::factory()->create();
    $application = Application::factory()->create([
        'user_id' => $user->id,
        'cover_letter' => null,
    ]);

    $this->actingAs($user)
        ->get("/applications/{$application->id}/cover-letter/export/pdf")
        ->assertNotFound();
});
