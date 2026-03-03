<?php

use App\Ai\Agents\GapClosureCoach;
use App\Models\GapAnalysis;
use App\Models\IdealCandidateProfile;
use App\Models\JobPosting;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $jobPosting = JobPosting::factory()->analyzed()->create(['user_id' => $this->user->id]);
    $profile = IdealCandidateProfile::factory()->create(['job_posting_id' => $jobPosting->id]);
    $this->gapAnalysis = GapAnalysis::factory()->create([
        'user_id' => $this->user->id,
        'ideal_candidate_profile_id' => $profile->id,
        'gaps' => [
            ['area' => 'Kubernetes', 'description' => 'No K8s experience', 'classification' => 'promptable', 'suggestion' => 'Ask about Docker experience'],
        ],
        'strengths' => [
            ['area' => 'PHP', 'evidence' => '10 years experience', 'relevance' => 'Core skill'],
        ],
    ]);
});

test('chat returns response from gap closure coach', function () {
    GapClosureCoach::fake(['I can help you address that Kubernetes gap. Tell me about your Docker experience.']);

    $this->actingAs($this->user)
        ->postJson("/gap-analyses/{$this->gapAnalysis->id}/chat", [
            'message' => 'Help me address my gaps',
        ])
        ->assertSuccessful()
        ->assertJsonStructure(['message', 'conversation_id']);

    GapClosureCoach::assertPrompted('Help me address my gaps');
});

test('chat returns 403 for other users gap analysis', function () {
    $other = User::factory()->create();

    $this->actingAs($other)
        ->postJson("/gap-analyses/{$this->gapAnalysis->id}/chat", [
            'message' => 'test',
        ])
        ->assertForbidden();
});

test('chat validates message is required', function () {
    $this->actingAs($this->user)
        ->postJson("/gap-analyses/{$this->gapAnalysis->id}/chat", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('message');
});

test('save creates new skills in experience library', function () {
    $this->actingAs($this->user)
        ->postJson("/gap-analyses/{$this->gapAnalysis->id}/save-entries", [
            'entries' => [
                ['type' => 'skill', 'data' => ['name' => 'Docker', 'category' => 'technical']],
            ],
        ])
        ->assertSuccessful();

    expect($this->user->skills()->where('name', 'Docker')->exists())->toBeTrue();
});

test('save creates new accomplishments in experience library', function () {
    $this->actingAs($this->user)
        ->postJson("/gap-analyses/{$this->gapAnalysis->id}/save-entries", [
            'entries' => [
                ['type' => 'accomplishment', 'data' => [
                    'title' => 'Containerized microservices',
                    'description' => 'Deployed 5 services to Docker Swarm',
                ]],
            ],
        ])
        ->assertSuccessful();

    expect($this->user->accomplishments()->where('title', 'Containerized microservices')->exists())->toBeTrue();
});

test('save returns 403 for other users gap analysis', function () {
    $other = User::factory()->create();

    $this->actingAs($other)
        ->postJson("/gap-analyses/{$this->gapAnalysis->id}/save-entries", [
            'entries' => [['type' => 'skill', 'data' => ['name' => 'Test']]],
        ])
        ->assertForbidden();
});
