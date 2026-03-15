<?php

use App\Enums\ResumeSectionType;
use App\Models\Resume;
use App\Models\ResumeSection;
use App\Models\ResumeSectionVariant;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    Queue::fake();
});

// Phase 1: Generation progress tracking

test('generate sets generation_status to pending', function () {
    $posting = \App\Models\JobPosting::factory()->analyzed()->create(['user_id' => $this->user->id]);
    $profile = \App\Models\IdealCandidateProfile::factory()->create(['job_posting_id' => $posting->id]);
    $analysis = \App\Models\GapAnalysis::factory()->create([
        'user_id' => $this->user->id,
        'ideal_candidate_profile_id' => $profile->id,
    ]);

    $this->actingAs($this->user)
        ->post("/gap-analyses/{$analysis->id}/resume")
        ->assertRedirect();

    $resume = Resume::first();
    expect($resume->generation_status)->toBe('pending');
});

test('resume is_generating accessor returns true for pending status', function () {
    $resume = Resume::factory()->create([
        'user_id' => $this->user->id,
        'generation_status' => 'pending',
    ]);

    expect($resume->is_generating)->toBeTrue();
});

test('resume is_generating accessor returns true for generating status', function () {
    $resume = Resume::factory()->create([
        'user_id' => $this->user->id,
        'generation_status' => 'generating',
    ]);

    expect($resume->is_generating)->toBeTrue();
});

test('resume is_generating accessor returns false for completed status', function () {
    $resume = Resume::factory()->create([
        'user_id' => $this->user->id,
        'generation_status' => 'completed',
    ]);

    expect($resume->is_generating)->toBeFalse();
});

test('resume is_generating accessor returns false for failed status', function () {
    $resume = Resume::factory()->create([
        'user_id' => $this->user->id,
        'generation_status' => 'failed',
    ]);

    expect($resume->is_generating)->toBeFalse();
});

test('resume is_generating accessor returns false for null status', function () {
    $resume = Resume::factory()->create([
        'user_id' => $this->user->id,
        'generation_status' => null,
    ]);

    expect($resume->is_generating)->toBeFalse();
});

test('resume factory has generating state', function () {
    $resume = Resume::factory()->generating()->create(['user_id' => $this->user->id]);

    expect($resume->generation_status)->toBe('generating');
    expect($resume->generation_progress)->toBeArray();
    expect($resume->generation_progress['total'])->toBe(5);
    expect($resume->generation_progress['completed'])->toBe(2);
    expect($resume->generation_progress['current_section'])->toBe('Skills');
});

test('show page includes generation status and progress', function () {
    $resume = Resume::factory()->generating()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('resumes/show')
                ->has('resume.generation_status')
                ->has('resume.generation_progress')
                ->has('resume.is_generating')
        );
});

// Phase 2: Content formatting fix

test('formatted_content normalizes escaped newlines', function () {
    $variant = ResumeSectionVariant::factory()->create([
        'content' => '**Google** — *Software Engineer*\n\n- Built systems\n- Led teams',
    ]);

    $formatted = $variant->formatted_content;

    expect($formatted)->not->toContain('\n');
    expect($formatted)->toContain('<strong>Google</strong>');
    expect($formatted)->toContain('<li>Built systems</li>');
});

test('formatted_content handles content without escaped newlines', function () {
    $content = "**Google** — *Software Engineer*\n\n- Built systems\n- Led teams";
    $variant = ResumeSectionVariant::factory()->create([
        'content' => $content,
    ]);

    $formatted = $variant->formatted_content;
    expect($formatted)->toContain('<strong>Google</strong>');
});

// Phase 4: Block editing

test('update blocks updates blocks array and reassembles content', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create([
        'resume_id' => $resume->id,
        'type' => ResumeSectionType::Experience,
    ]);
    $variant = ResumeSectionVariant::factory()->create([
        'resume_section_id' => $section->id,
        'blocks' => [
            ['key' => 'google', 'label' => 'Google', 'content' => '**Google** content', 'is_hidden' => false],
            ['key' => 'meta', 'label' => 'Meta', 'content' => '**Meta** content', 'is_hidden' => false],
        ],
        'content' => "**Google** content\n\n**Meta** content",
        'is_user_edited' => false,
    ]);

    $updatedBlocks = [
        ['key' => 'google', 'label' => 'Google', 'content' => '**Google** updated', 'is_hidden' => false],
        ['key' => 'meta', 'label' => 'Meta', 'content' => '**Meta** content', 'is_hidden' => false],
    ];

    $this->actingAs($this->user)
        ->patch("/resumes/{$resume->id}/variants/{$variant->id}/blocks", [
            'blocks' => $updatedBlocks,
        ])
        ->assertRedirect();

    $variant->refresh();
    expect($variant->blocks[0]['content'])->toBe('**Google** updated');
    expect($variant->is_user_edited)->toBeTrue();
    expect($variant->content)->toContain('**Google** updated');
    expect($variant->content)->toContain('**Meta** content');
});

test('hidden blocks are excluded from reassembled content', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create([
        'resume_id' => $resume->id,
        'type' => ResumeSectionType::Experience,
    ]);
    $variant = ResumeSectionVariant::factory()->create([
        'resume_section_id' => $section->id,
        'blocks' => [
            ['key' => 'google', 'label' => 'Google', 'content' => '**Google** content', 'is_hidden' => false],
            ['key' => 'meta', 'label' => 'Meta', 'content' => '**Meta** content', 'is_hidden' => false],
        ],
        'content' => "**Google** content\n\n**Meta** content",
    ]);

    $this->actingAs($this->user)
        ->patch("/resumes/{$resume->id}/variants/{$variant->id}/blocks", [
            'blocks' => [
                ['key' => 'google', 'label' => 'Google', 'content' => '**Google** content', 'is_hidden' => true],
                ['key' => 'meta', 'label' => 'Meta', 'content' => '**Meta** content', 'is_hidden' => false],
            ],
        ])
        ->assertRedirect();

    $variant->refresh();
    expect($variant->content)->not->toContain('**Google** content');
    expect($variant->content)->toContain('**Meta** content');
    expect($variant->blocks[0]['is_hidden'])->toBeTrue();
});

test('block reorder updates block order', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create([
        'resume_id' => $resume->id,
        'type' => ResumeSectionType::Experience,
    ]);
    $variant = ResumeSectionVariant::factory()->create([
        'resume_section_id' => $section->id,
        'blocks' => [
            ['key' => 'first', 'label' => 'First', 'content' => 'First content', 'is_hidden' => false],
            ['key' => 'second', 'label' => 'Second', 'content' => 'Second content', 'is_hidden' => false],
        ],
        'content' => "First content\n\nSecond content",
    ]);

    // Reorder: second before first
    $this->actingAs($this->user)
        ->patch("/resumes/{$resume->id}/variants/{$variant->id}/blocks", [
            'blocks' => [
                ['key' => 'second', 'label' => 'Second', 'content' => 'Second content', 'is_hidden' => false],
                ['key' => 'first', 'label' => 'First', 'content' => 'First content', 'is_hidden' => false],
            ],
        ])
        ->assertRedirect();

    $variant->refresh();
    expect($variant->blocks[0]['key'])->toBe('second');
    expect($variant->blocks[1]['key'])->toBe('first');
    expect($variant->content)->toBe("Second content\n\nFirst content");
});

test('update blocks returns 403 for other user', function () {
    $other = User::factory()->create();
    $resume = Resume::factory()->create(['user_id' => $other->id]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);
    $variant = ResumeSectionVariant::factory()->create([
        'resume_section_id' => $section->id,
        'blocks' => [['key' => 'a', 'label' => 'A', 'content' => 'Content', 'is_hidden' => false]],
    ]);

    $this->actingAs($this->user)
        ->patch("/resumes/{$resume->id}/variants/{$variant->id}/blocks", [
            'blocks' => [['key' => 'a', 'label' => 'A', 'content' => 'Changed', 'is_hidden' => false]],
        ])
        ->assertForbidden();
});

test('update blocks validates input', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);
    $variant = ResumeSectionVariant::factory()->create([
        'resume_section_id' => $section->id,
        'blocks' => [],
    ]);

    $this->actingAs($this->user)
        ->patch("/resumes/{$resume->id}/variants/{$variant->id}/blocks", [])
        ->assertSessionHasErrors('blocks');
});

// Phase 4g: Projects dedup

test('reassembleContent only includes visible blocks', function () {
    $variant = new ResumeSectionVariant;
    $variant->blocks = [
        ['key' => 'a', 'label' => 'A', 'content' => 'Visible content', 'is_hidden' => false],
        ['key' => 'b', 'label' => 'B', 'content' => 'Hidden content', 'is_hidden' => true],
        ['key' => 'c', 'label' => 'C', 'content' => 'Also visible', 'is_hidden' => false],
    ];

    $variant->reassembleContent();

    expect($variant->content)->toBe("Visible content\n\nAlso visible");
});

test('reassembleContent does nothing when blocks is null', function () {
    $variant = new ResumeSectionVariant;
    $variant->content = 'Original content';
    $variant->blocks = null;

    $variant->reassembleContent();

    expect($variant->content)->toBe('Original content');
});

// Transparency text

test('update resume saves transparency text and show_transparency', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->put("/resumes/{$resume->id}", [
            'transparency_text' => 'Created with AI assistance. Details: https://example.com/transparency',
            'show_transparency' => true,
        ])
        ->assertRedirect();

    $resume->refresh();
    expect($resume->transparency_text)->toBe('Created with AI assistance. Details: https://example.com/transparency');
    expect($resume->show_transparency)->toBeTrue();
});

test('transparency can be toggled off', function () {
    $resume = Resume::factory()->create([
        'user_id' => $this->user->id,
        'show_transparency' => true,
        'transparency_text' => 'Some text',
    ]);

    $this->actingAs($this->user)
        ->put("/resumes/{$resume->id}", [
            'show_transparency' => false,
        ])
        ->assertRedirect();

    $resume->refresh();
    expect($resume->show_transparency)->toBeFalse();
});

test('transparency_text validates as string with max length', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->put("/resumes/{$resume->id}", [
            'transparency_text' => str_repeat('a', 501),
        ])
        ->assertSessionHasErrors('transparency_text');
});

// Display mode

test('update section saves display_mode', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create([
        'resume_id' => $resume->id,
        'type' => ResumeSectionType::Experience,
    ]);

    $this->actingAs($this->user)
        ->patch("/resumes/{$resume->id}/sections/{$section->id}", [
            'title' => $section->title,
            'display_mode' => 'compact',
        ])
        ->assertRedirect();

    $section->refresh();
    expect($section->display_mode)->toBe('compact');
});

test('display_mode defaults to expanded for new sections', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create([
        'resume_id' => $resume->id,
    ]);

    $section->refresh();
    expect($section->display_mode)->toBe('expanded');
});

test('display_mode validates allowed values', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create([
        'resume_id' => $resume->id,
    ]);

    $this->actingAs($this->user)
        ->patch("/resumes/{$resume->id}/sections/{$section->id}", [
            'title' => $section->title,
            'display_mode' => 'invalid',
        ])
        ->assertSessionHasErrors('display_mode');
});

// Preview page includes header config

test('preview page includes globalHeaderConfig', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    ResumeSection::factory()->create(['resume_id' => $resume->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}/preview")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('resumes/preview')
                ->has('globalHeaderConfig')
        );
});
