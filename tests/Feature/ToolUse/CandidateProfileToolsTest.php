<?php

use App\Ai\Tools\ToolActionLog;
use App\Ai\Tools\UpdateCandidateProfileField;
use App\Ai\Tools\UpdateCandidateProfileSkills;
use App\Models\IdealCandidateProfile;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    $this->actionLog = new ToolActionLog;

    $this->profile = IdealCandidateProfile::factory()->create([
        'required_skills' => ['PHP', 'Laravel', 'SQL'],
        'preferred_skills' => ['React', 'Docker'],
        'is_user_edited' => false,
    ]);
});

test('UpdateCandidateProfileSkills adds required skills', function () {
    $tool = new UpdateCandidateProfileSkills($this->profile, $this->actionLog);
    $result = $tool->handle(new Request([
        'action' => 'add',
        'skill_type' => 'required',
        'skills' => ['TypeScript', 'Redis'],
    ]));

    expect($result)->toContain('Added');

    $this->profile->refresh();
    expect($this->profile->required_skills)->toContain('TypeScript')
        ->and($this->profile->required_skills)->toContain('Redis')
        ->and($this->profile->required_skills)->toContain('PHP')
        ->and($this->profile->is_user_edited)->toBeTrue();
});

test('UpdateCandidateProfileSkills removes preferred skills case-insensitively', function () {
    $tool = new UpdateCandidateProfileSkills($this->profile, $this->actionLog);
    $result = $tool->handle(new Request([
        'action' => 'remove',
        'skill_type' => 'preferred',
        'skills' => ['react'],
    ]));

    expect($result)->toContain('Removed');

    $this->profile->refresh();
    expect($this->profile->preferred_skills)->not->toContain('React')
        ->and($this->profile->preferred_skills)->toContain('Docker');
});

test('UpdateCandidateProfileSkills rejects invalid action', function () {
    $tool = new UpdateCandidateProfileSkills($this->profile, $this->actionLog);
    $result = $tool->handle(new Request([
        'action' => 'invalid',
        'skill_type' => 'required',
        'skills' => ['PHP'],
    ]));

    expect($result)->toContain('Invalid action')
        ->and($this->actionLog->hasActions())->toBeFalse();
});

test('UpdateCandidateProfileField updates allowed fields', function () {
    $tool = new UpdateCandidateProfileField($this->profile, $this->actionLog);
    $result = $tool->handle(new Request([
        'field' => 'red_flags',
        'value' => ['No version control', 'Unexplained employment gaps'],
    ]));

    expect($result)->toContain('Successfully updated');

    $this->profile->refresh();
    expect($this->profile->red_flags)->toBe(['No version control', 'Unexplained employment gaps'])
        ->and($this->profile->is_user_edited)->toBeTrue();
});

test('UpdateCandidateProfileField rejects disallowed fields', function () {
    $tool = new UpdateCandidateProfileField($this->profile, $this->actionLog);
    $result = $tool->handle(new Request([
        'field' => 'required_skills',
        'value' => ['hack'],
    ]));

    expect($result)->toContain('Invalid field')
        ->and($this->actionLog->hasActions())->toBeFalse();
});
