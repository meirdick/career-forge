<?php

use App\Ai\Tools\AcknowledgeGap;
use App\Ai\Tools\AnswerGap;
use App\Ai\Tools\ToolActionLog;
use App\Ai\Tools\TriggerReanalysis;
use App\Models\GapAnalysis;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actionLog = new ToolActionLog;

    $this->gapAnalysis = GapAnalysis::factory()->create([
        'user_id' => $this->user->id,
        'gaps' => [
            ['area' => 'React', 'description' => 'No direct React experience', 'classification' => 'reframable', 'suggestion' => 'Vue.js transfers'],
            ['area' => 'AWS', 'description' => 'No cloud experience', 'classification' => 'promptable', 'suggestion' => 'Describe deployments'],
            ['area' => 'Kubernetes', 'description' => 'No container orchestration', 'classification' => 'genuine', 'suggestion' => 'Consider learning'],
        ],
    ]);
});

test('AcknowledgeGap tool marks a gap as acknowledged', function () {
    $tool = new AcknowledgeGap($this->gapAnalysis, $this->actionLog);
    $result = $tool->handle(new Request(['gap_area' => 'Kubernetes', 'note' => 'Will learn later']));

    expect($result)->toContain('acknowledged');

    $this->gapAnalysis->refresh();
    $resolution = $this->gapAnalysis->getResolutionFor('Kubernetes');

    expect($resolution)->not->toBeNull()
        ->and($resolution['status'])->toBe('acknowledged')
        ->and($resolution['note'])->toBe('Will learn later');

    expect($this->actionLog->hasActions())->toBeTrue()
        ->and($this->actionLog->actions())->toContain('Acknowledged gap: Kubernetes');
});

test('AcknowledgeGap returns error for non-existent gap', function () {
    $tool = new AcknowledgeGap($this->gapAnalysis, $this->actionLog);
    $result = $tool->handle(new Request(['gap_area' => 'NonExistent']));

    expect($result)->toContain('Could not find')
        ->and($this->actionLog->hasActions())->toBeFalse();
});

test('AnswerGap tool resolves a gap with an accomplishment', function () {
    $tool = new AnswerGap($this->user, $this->gapAnalysis, $this->actionLog);
    $result = $tool->handle(new Request([
        'gap_area' => 'AWS',
        'answer' => 'Deployed applications to DigitalOcean with CI/CD pipelines',
    ]));

    expect($result)->toContain('resolved');

    $this->gapAnalysis->refresh();
    $resolution = $this->gapAnalysis->getResolutionFor('AWS');

    expect($resolution)->not->toBeNull()
        ->and($resolution['status'])->toBe('resolved')
        ->and($resolution['answer'])->toBe('Deployed applications to DigitalOcean with CI/CD pipelines');

    expect($this->user->accomplishments()->count())->toBe(1);
    expect($this->user->accomplishments()->first()->title)->toBe('AWS');

    expect($this->actionLog->actions())->toContain('Resolved gap: AWS');
});

test('AnswerGap returns error for non-existent gap', function () {
    $tool = new AnswerGap($this->user, $this->gapAnalysis, $this->actionLog);
    $result = $tool->handle(new Request([
        'gap_area' => 'NonExistent',
        'answer' => 'Some answer',
    ]));

    expect($result)->toContain('Could not find')
        ->and($this->user->accomplishments()->count())->toBe(0);
});

test('TriggerReanalysis dispatches job and saves previous score', function () {
    Queue::fake();

    $originalScore = $this->gapAnalysis->overall_match_score;

    $tool = new TriggerReanalysis($this->gapAnalysis, $this->actionLog);
    $result = $tool->handle(new Request([]));

    expect($result)->toContain('Re-analysis');

    $this->gapAnalysis->refresh();

    expect($this->gapAnalysis->previous_match_score)->toBe($originalScore)
        ->and($this->gapAnalysis->overall_match_score)->toBeNull()
        ->and($this->gapAnalysis->strengths)->toBe([])
        ->and($this->gapAnalysis->gaps)->toBe([]);

    Queue::assertPushed(\App\Jobs\PerformGapAnalysisJob::class);
    expect($this->actionLog->actions())->toContain('Triggered gap re-analysis');
});

test('ToolActionLog tracks multiple actions', function () {
    $log = new ToolActionLog;

    expect($log->hasActions())->toBeFalse()
        ->and($log->actions())->toBe([]);

    $log->record('Action 1');
    $log->record('Action 2');

    expect($log->hasActions())->toBeTrue()
        ->and($log->actions())->toBe(['Action 1', 'Action 2']);
});
