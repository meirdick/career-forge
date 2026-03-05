<?php

use App\Ai\Agents\InterviewCoach;
use App\Enums\SkillCategory;
use App\Models\Experience;
use App\Models\Skill;
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

test('chat injects experience context into interview coach', function () {
    Experience::factory()->create([
        'user_id' => $this->user->id,
        'company' => 'ContextCorp',
        'title' => 'Staff Engineer',
    ]);

    Skill::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Rust',
        'category' => SkillCategory::Technical,
    ]);

    InterviewCoach::fake(['Great, I can see you worked at ContextCorp!']);

    $this->actingAs($this->user)
        ->postJson('/interview', [
            'message' => 'Can you see my experience?',
        ])
        ->assertSuccessful();

    InterviewCoach::assertPrompted(function ($prompt) {
        $instructions = $prompt->agent->instructions();

        return str_contains($instructions, 'ContextCorp')
            && str_contains($instructions, 'Staff Engineer')
            && str_contains($instructions, 'Rust');
    });
});
