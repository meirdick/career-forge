<?php

use App\Ai\Tools\ToolActionLog;
use App\Ai\Tools\UpdateApplicationStatus;
use App\Ai\Tools\UpdateCoverLetter;
use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\User;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actionLog = new ToolActionLog;

    $this->application = Application::factory()->draft()->create([
        'user_id' => $this->user->id,
    ]);
});

test('UpdateCoverLetter saves cover letter text', function () {
    $tool = new UpdateCoverLetter($this->application, $this->actionLog);
    $result = $tool->handle(new Request([
        'cover_letter' => 'Dear Hiring Manager, I am excited to apply...',
    ]));

    expect($result)->toContain('Successfully updated');

    $this->application->refresh();
    expect($this->application->cover_letter)->toBe('Dear Hiring Manager, I am excited to apply...');
    expect($this->actionLog->actions())->toContain('Updated cover letter');
});

test('UpdateApplicationStatus changes status and creates status change record', function () {
    $tool = new UpdateApplicationStatus($this->application, $this->actionLog);
    $result = $tool->handle(new Request([
        'status' => 'applied',
        'notes' => 'Submitted via email',
    ]));

    expect($result)->toContain('draft')
        ->and($result)->toContain('applied');

    $this->application->refresh();
    expect($this->application->status)->toBe(ApplicationStatus::Applied)
        ->and($this->application->applied_at)->not->toBeNull();

    $statusChange = $this->application->statusChanges()->latest()->first();
    expect($statusChange->from_status)->toBe(ApplicationStatus::Draft)
        ->and($statusChange->to_status)->toBe(ApplicationStatus::Applied)
        ->and($statusChange->notes)->toBe('Submitted via email');
});

test('UpdateApplicationStatus returns error for invalid status', function () {
    $tool = new UpdateApplicationStatus($this->application, $this->actionLog);
    $result = $tool->handle(new Request([
        'status' => 'invalid_status',
    ]));

    expect($result)->toContain('Invalid status')
        ->and($this->actionLog->hasActions())->toBeFalse();
});
