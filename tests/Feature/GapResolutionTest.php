<?php

use App\Models\Experience;
use App\Models\GapAnalysis;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->gapAnalysis = GapAnalysis::factory()->create([
        'user_id' => $this->user->id,
        'gaps' => [
            ['area' => 'React', 'description' => 'No direct React experience', 'classification' => 'reframable', 'suggestion' => 'Vue.js transfers'],
            ['area' => 'AWS', 'description' => 'No cloud experience', 'classification' => 'promptable', 'suggestion' => 'Describe your deployments'],
            ['area' => 'Kubernetes', 'description' => 'No container orchestration', 'classification' => 'genuine', 'suggestion' => 'Consider learning'],
        ],
    ]);

    $this->experience = Experience::factory()->create([
        'user_id' => $this->user->id,
        'description' => 'Built Vue.js applications',
    ]);
});

test('can acknowledge a genuine gap', function () {
    $this->postJson(
        "/gap-analyses/{$this->gapAnalysis->id}/resolve/Kubernetes/acknowledge"
    )->assertOk();

    $this->gapAnalysis->refresh();
    $resolution = $this->gapAnalysis->getResolutionFor('Kubernetes');

    expect($resolution)->not->toBeNull()
        ->and($resolution['status'])->toBe('acknowledged');
});

test('can answer a promptable gap', function () {
    $this->postJson(
        "/gap-analyses/{$this->gapAnalysis->id}/resolve/AWS/answer",
        ['answer' => 'I deployed applications to DigitalOcean and managed Linux servers']
    )->assertOk();

    $this->gapAnalysis->refresh();
    $resolution = $this->gapAnalysis->getResolutionFor('AWS');

    expect($resolution)->not->toBeNull()
        ->and($resolution['status'])->toBe('resolved')
        ->and($resolution['answer'])->toBe('I deployed applications to DigitalOcean and managed Linux servers');
});

test('answer requires text', function () {
    $this->postJson(
        "/gap-analyses/{$this->gapAnalysis->id}/resolve/AWS/answer",
        ['answer' => '']
    )->assertUnprocessable();
});

test('cannot resolve gaps for another user gap analysis', function () {
    $otherUser = User::factory()->create();
    $otherAnalysis = GapAnalysis::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $this->postJson(
        "/gap-analyses/{$otherAnalysis->id}/resolve/React/acknowledge"
    )->assertForbidden();
});

test('cannot resolve a gap that does not exist', function () {
    $this->postJson(
        "/gap-analyses/{$this->gapAnalysis->id}/resolve/NonExistent/acknowledge"
    )->assertNotFound();
});

test('can reanalyze a gap analysis', function () {
    Queue::fake();

    $originalScore = $this->gapAnalysis->overall_match_score;

    $this->post("/gap-analyses/{$this->gapAnalysis->id}/reanalyze")
        ->assertRedirect();

    $this->gapAnalysis->refresh();

    expect($this->gapAnalysis->previous_match_score)->toBe($originalScore)
        ->and($this->gapAnalysis->overall_match_score)->toBeNull()
        ->and($this->gapAnalysis->strengths)->toBe([])
        ->and($this->gapAnalysis->gaps)->toBe([]);

    Queue::assertPushed(\App\Jobs\PerformGapAnalysisJob::class);
});

test('resolved gap count helper works correctly', function () {
    expect($this->gapAnalysis->resolvedGapCount())->toBe(0);

    $this->gapAnalysis->setResolutionFor('Kubernetes', [
        'status' => 'acknowledged',
    ]);
    expect($this->gapAnalysis->resolvedGapCount())->toBe(1);

    $this->gapAnalysis->setResolutionFor('AWS', [
        'status' => 'resolved',
        'answer' => 'Some answer',
    ]);
    expect($this->gapAnalysis->resolvedGapCount())->toBe(2);
});

test('show page includes experiences', function () {
    $this->get("/gap-analyses/{$this->gapAnalysis->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('gap-analyses/show')
            ->has('experiences')
            ->has('gapAnalysis')
        );
});
