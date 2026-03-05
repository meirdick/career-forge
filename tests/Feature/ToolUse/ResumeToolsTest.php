<?php

use App\Ai\Tools\EditResumeSection;
use App\Ai\Tools\ReorderResumeSections;
use App\Ai\Tools\SelectResumeVariant;
use App\Ai\Tools\ToolActionLog;
use App\Models\Resume;
use App\Models\ResumeSection;
use App\Models\ResumeSectionVariant;
use App\Models\User;
use Laravel\Ai\Tools\Request;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actionLog = new ToolActionLog;

    $this->resume = Resume::factory()->create(['user_id' => $this->user->id]);

    $this->section = ResumeSection::factory()->create([
        'resume_id' => $this->resume->id,
        'title' => 'Professional Summary',
        'sort_order' => 0,
    ]);

    $this->variant = ResumeSectionVariant::factory()->create([
        'resume_section_id' => $this->section->id,
        'label' => 'Balanced',
        'content' => 'Original summary content',
    ]);

    $this->section->update(['selected_variant_id' => $this->variant->id]);
});

test('EditResumeSection updates variant content', function () {
    $tool = new EditResumeSection($this->resume, $this->actionLog);
    $result = $tool->handle(new Request([
        'section_title' => 'Professional Summary',
        'new_content' => 'Improved summary with quantified results',
    ]));

    expect($result)->toContain('Successfully updated');

    $this->variant->refresh();
    expect($this->variant->content)->toBe('Improved summary with quantified results')
        ->and($this->variant->is_user_edited)->toBeTrue();

    expect($this->actionLog->actions())->toContain('Edited resume section: Professional Summary');
});

test('EditResumeSection handles case-insensitive title match', function () {
    $tool = new EditResumeSection($this->resume, $this->actionLog);
    $result = $tool->handle(new Request([
        'section_title' => 'professional summary',
        'new_content' => 'New content',
    ]));

    expect($result)->toContain('Successfully updated');
});

test('EditResumeSection returns error for non-existent section', function () {
    $tool = new EditResumeSection($this->resume, $this->actionLog);
    $result = $tool->handle(new Request([
        'section_title' => 'NonExistent Section',
        'new_content' => 'Content',
    ]));

    expect($result)->toContain('Could not find section')
        ->and($this->actionLog->hasActions())->toBeFalse();
});

test('SelectResumeVariant changes selected variant', function () {
    $otherVariant = ResumeSectionVariant::factory()->create([
        'resume_section_id' => $this->section->id,
        'label' => 'Aggressive',
        'content' => 'Bold, results-driven content',
    ]);

    $tool = new SelectResumeVariant($this->resume, $this->actionLog);
    $result = $tool->handle(new Request([
        'section_title' => 'Professional Summary',
        'variant_label' => 'Aggressive',
    ]));

    expect($result)->toContain('Successfully selected');

    $this->section->refresh();
    expect($this->section->selected_variant_id)->toBe($otherVariant->id);
    expect($this->actionLog->actions())->toHaveCount(1);
});

test('SelectResumeVariant returns error for non-existent variant', function () {
    $tool = new SelectResumeVariant($this->resume, $this->actionLog);
    $result = $tool->handle(new Request([
        'section_title' => 'Professional Summary',
        'variant_label' => 'NonExistent',
    ]));

    expect($result)->toContain('Could not find variant');
});

test('ReorderResumeSections updates sort order', function () {
    $section2 = ResumeSection::factory()->create([
        'resume_id' => $this->resume->id,
        'title' => 'Work Experience',
        'sort_order' => 1,
    ]);

    $tool = new ReorderResumeSections($this->resume, $this->actionLog);
    $result = $tool->handle(new Request([
        'section_order' => ['Work Experience', 'Professional Summary'],
    ]));

    expect($result)->toContain('Successfully reordered');

    $section2->refresh();
    $this->section->refresh();

    expect($section2->sort_order)->toBe(0)
        ->and($this->section->sort_order)->toBe(1);

    expect($this->actionLog->actions())->toContain('Reordered resume sections');
});

test('ReorderResumeSections returns error for non-existent section title', function () {
    $tool = new ReorderResumeSections($this->resume, $this->actionLog);
    $result = $tool->handle(new Request([
        'section_order' => ['NonExistent', 'Professional Summary'],
    ]));

    expect($result)->toContain('Could not find section');
});
