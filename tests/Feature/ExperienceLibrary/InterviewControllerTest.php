<?php

use App\Ai\Agents\InterviewCoach;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('index renders interview page', function () {
    $this->actingAs($this->user)
        ->get('/interview')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('experience-library/interview'));
});

test('chat returns response from interview coach', function () {
    InterviewCoach::fake(["That's great! Tell me about your current role. What's your job title and what does a typical day look like?"]);

    $this->actingAs($this->user)
        ->postJson('/interview', [
            'message' => "Hi! I'd like to build out my professional experience library.",
        ])
        ->assertSuccessful()
        ->assertJsonStructure(['message', 'conversation_id']);

    InterviewCoach::assertPrompted(fn ($prompt) => str_contains($prompt->prompt, 'experience library'));
});

test('chat validates message is required', function () {
    $this->actingAs($this->user)
        ->postJson('/interview', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('message');
});

test('chat requires authentication', function () {
    $this->postJson('/interview', ['message' => 'test'])
        ->assertUnauthorized();
});
