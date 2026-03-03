<?php

use App\Ai\Agents\CoverLetterWriter;
use App\Models\Application;
use App\Models\JobPosting;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $jobPosting = JobPosting::factory()->analyzed()->create(['user_id' => $this->user->id]);
    $this->application = Application::factory()->create([
        'user_id' => $this->user->id,
        'job_posting_id' => $jobPosting->id,
    ]);
});

test('generate cover letter creates cover letter from AI', function () {
    CoverLetterWriter::fake(['Dear Hiring Manager, I am writing to express my interest...']);

    $this->actingAs($this->user)
        ->postJson("/applications/{$this->application->id}/generate-cover-letter")
        ->assertSuccessful()
        ->assertJsonStructure(['cover_letter']);

    expect($this->application->fresh()->cover_letter)->toContain('Dear Hiring Manager');
    CoverLetterWriter::assertPrompted(fn ($prompt) => str_contains($prompt->prompt, 'cover letter'));
});

test('generate cover letter returns 403 for other users', function () {
    $other = User::factory()->create();

    $this->actingAs($other)
        ->postJson("/applications/{$this->application->id}/generate-cover-letter")
        ->assertForbidden();
});

test('generate email creates submission email from AI', function () {
    CoverLetterWriter::fake(['Subject: Application for Senior Engineer...']);

    $this->actingAs($this->user)
        ->postJson("/applications/{$this->application->id}/generate-email")
        ->assertSuccessful()
        ->assertJsonStructure(['submission_email']);

    expect($this->application->fresh()->submission_email)->toContain('Application for Senior Engineer');
    CoverLetterWriter::assertPrompted(fn ($prompt) => str_contains($prompt->prompt, 'email'));
});

test('generate email returns 403 for other users', function () {
    $other = User::factory()->create();

    $this->actingAs($other)
        ->postJson("/applications/{$this->application->id}/generate-email")
        ->assertForbidden();
});
