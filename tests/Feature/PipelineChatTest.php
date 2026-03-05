<?php

use App\Ai\Agents\CareerCoach;
use App\Ai\Agents\GapClosureCoach;
use App\Enums\PipelineStep;
use App\Models\ChatSession;
use App\Models\GapAnalysis;
use App\Models\JobPosting;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('resolve creates a new pipeline chat session', function () {
    $jobPosting = JobPosting::factory()->create(['user_id' => $this->user->id]);

    $response = $this->postJson('/pipeline-chat/resolve', [
        'step' => 'job_posting',
        'pipeline_key' => "job_posting:{$jobPosting->id}",
    ]);

    $response->assertOk()
        ->assertJsonStructure(['session_id', 'messages']);

    $session = ChatSession::find($response->json('session_id'));
    expect($session)->not->toBeNull()
        ->and($session->step)->toBe(PipelineStep::JobPosting)
        ->and($session->pipeline_key)->toBe("job_posting:{$jobPosting->id}")
        ->and($session->user_id)->toBe($this->user->id);
});

test('resolve returns existing session for same pipeline key', function () {
    $session = ChatSession::factory()->create([
        'user_id' => $this->user->id,
        'step' => PipelineStep::GapAnalysis,
        'pipeline_key' => 'gap_analysis:42',
    ]);

    $response = $this->postJson('/pipeline-chat/resolve', [
        'step' => 'gap_analysis',
        'pipeline_key' => 'gap_analysis:42',
    ]);

    $response->assertOk();
    expect($response->json('session_id'))->toBe($session->id);
});

test('resolve validates required fields', function () {
    $this->postJson('/pipeline-chat/resolve', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['step', 'pipeline_key']);
});

test('resolve validates step values', function () {
    $this->postJson('/pipeline-chat/resolve', [
        'step' => 'invalid_step',
        'pipeline_key' => 'test:1',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['step']);
});

test('cannot chat on another user session', function () {
    $otherUser = User::factory()->create();
    $session = ChatSession::factory()->create([
        'user_id' => $otherUser->id,
        'step' => PipelineStep::JobPosting,
        'pipeline_key' => 'job_posting:1',
    ]);

    $this->postJson("/pipeline-chat/{$session->id}/chat", [
        'message' => 'Hello',
    ])->assertForbidden();
});

test('chat response includes tool_actions for gap analysis step', function () {
    GapClosureCoach::fake(['I can help with your gaps.']);

    $gapAnalysis = GapAnalysis::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $session = ChatSession::factory()->create([
        'user_id' => $this->user->id,
        'step' => PipelineStep::GapAnalysis,
        'pipeline_key' => "gap_analysis:{$gapAnalysis->id}",
    ]);

    $response = $this->postJson("/pipeline-chat/{$session->id}/chat", [
        'message' => 'Help me with my gaps',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['message', 'conversation_id', 'tool_actions']);

    expect($response->json('tool_actions'))->toBeArray();
});

test('chat response includes tool_actions for career coach steps', function () {
    CareerCoach::fake(['Here is some career advice.']);

    $jobPosting = JobPosting::factory()->create(['user_id' => $this->user->id]);

    $session = ChatSession::factory()->create([
        'user_id' => $this->user->id,
        'step' => PipelineStep::JobPosting,
        'pipeline_key' => "job_posting:{$jobPosting->id}",
    ]);

    $response = $this->postJson("/pipeline-chat/{$session->id}/chat", [
        'message' => 'Tell me about this role',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['message', 'conversation_id', 'tool_actions']);

    expect($response->json('tool_actions'))->toBeArray();
});
