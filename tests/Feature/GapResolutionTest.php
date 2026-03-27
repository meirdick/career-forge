<?php

use App\Jobs\PerformGapAnalysisJob;
use App\Models\Accomplishment;
use App\Models\Experience;
use App\Models\GapAnalysis;
use App\Models\Skill;
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

    Queue::assertPushed(PerformGapAnalysisJob::class);
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
            ->has('libraryAdditions')
        );
});

test('answer creates accomplishment with source tracking', function () {
    $this->postJson(
        "/gap-analyses/{$this->gapAnalysis->id}/resolve/AWS/answer",
        ['answer' => 'I deployed to DigitalOcean']
    )->assertOk();

    $accomplishment = $this->user->accomplishments()->latest()->first();

    expect($accomplishment)->not->toBeNull()
        ->and($accomplishment->title)->toBe('AWS')
        ->and($accomplishment->source_type)->toBe('gap_analysis')
        ->and($accomplishment->source_id)->toBe($this->gapAnalysis->id);

    $this->gapAnalysis->refresh();
    $resolution = $this->gapAnalysis->getResolutionFor('AWS');

    expect($resolution['accomplishment_id'])->toBe($accomplishment->id);
});

test('show page includes library additions from gap resolution', function () {
    Accomplishment::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'AWS Deployment',
        'source_type' => 'gap_analysis',
        'source_id' => $this->gapAnalysis->id,
    ]);

    Skill::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Docker',
        'source_type' => 'gap_analysis',
        'source_id' => $this->gapAnalysis->id,
    ]);

    $this->get("/gap-analyses/{$this->gapAnalysis->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('gap-analyses/show')
            ->has('libraryAdditions.accomplishments', 1)
            ->has('libraryAdditions.skills', 1)
        );
});

test('organize endpoint links accomplishment to experience', function () {
    $accomplishment = Accomplishment::factory()->create([
        'user_id' => $this->user->id,
        'source_type' => 'gap_analysis',
        'source_id' => $this->gapAnalysis->id,
    ]);

    $this->postJson("/gap-analyses/{$this->gapAnalysis->id}/organize", [
        'updates' => [
            [
                'type' => 'accomplishment',
                'id' => $accomplishment->id,
                'experience_id' => $this->experience->id,
            ],
        ],
    ])->assertOk();

    $accomplishment->refresh();
    expect($accomplishment->experience_id)->toBe($this->experience->id);
});

test('organize endpoint sets skill proficiency', function () {
    $skill = Skill::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Kubernetes',
        'source_type' => 'gap_analysis',
        'source_id' => $this->gapAnalysis->id,
        'proficiency' => null,
    ]);

    $this->postJson("/gap-analyses/{$this->gapAnalysis->id}/organize", [
        'updates' => [
            [
                'type' => 'skill',
                'id' => $skill->id,
                'proficiency' => 'intermediate',
            ],
        ],
    ])->assertOk();

    $skill->refresh();
    expect($skill->proficiency->value)->toBe('intermediate');
});

test('organize rejects experience belonging to another user', function () {
    $otherUser = User::factory()->create();
    $otherExperience = Experience::factory()->create(['user_id' => $otherUser->id]);

    $accomplishment = Accomplishment::factory()->create([
        'user_id' => $this->user->id,
        'source_type' => 'gap_analysis',
        'source_id' => $this->gapAnalysis->id,
    ]);

    $this->postJson("/gap-analyses/{$this->gapAnalysis->id}/organize", [
        'updates' => [
            [
                'type' => 'accomplishment',
                'id' => $accomplishment->id,
                'experience_id' => $otherExperience->id,
            ],
        ],
    ])->assertStatus(422);
});

test('organize rejects gap analysis belonging to another user', function () {
    $otherUser = User::factory()->create();
    $otherAnalysis = GapAnalysis::factory()->create(['user_id' => $otherUser->id]);

    $this->postJson("/gap-analyses/{$otherAnalysis->id}/organize", [
        'updates' => [
            ['type' => 'skill', 'id' => 1, 'proficiency' => 'beginner'],
        ],
    ])->assertForbidden();
});

test('save entries creates items with source tracking', function () {
    $this->postJson("/gap-analyses/{$this->gapAnalysis->id}/save-entries", [
        'entries' => [
            [
                'type' => 'skill',
                'data' => [
                    'name' => 'Terraform',
                    'category' => 'technical',
                    'ai_inferred_proficiency' => 'intermediate',
                ],
            ],
            [
                'type' => 'accomplishment',
                'data' => [
                    'title' => 'Cloud Migration',
                    'description' => 'Led migration to AWS',
                    'experience_id' => $this->experience->id,
                ],
            ],
        ],
    ])->assertOk()->assertJsonStructure(['success', 'created']);

    $skill = $this->user->skills()->where('name', 'Terraform')->first();
    expect($skill)->not->toBeNull()
        ->and($skill->source_type)->toBe('gap_analysis')
        ->and($skill->source_id)->toBe($this->gapAnalysis->id)
        ->and($skill->ai_inferred_proficiency->value)->toBe('intermediate');

    $accomplishment = $this->user->accomplishments()->where('title', 'Cloud Migration')->first();
    expect($accomplishment)->not->toBeNull()
        ->and($accomplishment->source_type)->toBe('gap_analysis')
        ->and($accomplishment->source_id)->toBe($this->gapAnalysis->id)
        ->and($accomplishment->experience_id)->toBe($this->experience->id);
});
