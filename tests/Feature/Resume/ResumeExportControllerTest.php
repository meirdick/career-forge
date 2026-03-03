<?php

use App\Models\Resume;
use App\Models\ResumeSection;
use App\Models\ResumeSectionVariant;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('preview displays resume preview', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);
    ResumeSectionVariant::factory()->create(['resume_section_id' => $section->id]);
    $section->update(['selected_variant_id' => $section->variants->first()->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}/preview")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('resumes/preview')
                ->has('resume')
        );
});

test('preview returns 403 for other users resume', function () {
    $other = User::factory()->create();
    $resume = Resume::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}/preview")
        ->assertForbidden();
});

test('export pdf downloads file', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);
    $variant = ResumeSectionVariant::factory()->create(['resume_section_id' => $section->id]);
    $section->update(['selected_variant_id' => $variant->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}/export/pdf")
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');

    expect($resume->fresh())
        ->exported_path->not->toBeNull()
        ->exported_format->toBe('pdf');
});

test('export docx downloads file', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);
    $variant = ResumeSectionVariant::factory()->create(['resume_section_id' => $section->id]);
    $section->update(['selected_variant_id' => $variant->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}/export/docx")
        ->assertSuccessful();

    expect($resume->fresh())
        ->exported_path->not->toBeNull()
        ->exported_format->toBe('docx');
});

test('export returns 404 for invalid format', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}/export/txt")
        ->assertNotFound();
});

test('export returns 403 for other users resume', function () {
    $other = User::factory()->create();
    $resume = Resume::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}/export/pdf")
        ->assertForbidden();
});

test('finalize marks resume as finalized', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->post("/resumes/{$resume->id}/finalize")
        ->assertRedirect();

    expect($resume->fresh()->is_finalized)->toBeTrue();
});

test('finalize returns 403 for other users resume', function () {
    $other = User::factory()->create();
    $resume = Resume::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->post("/resumes/{$resume->id}/finalize")
        ->assertForbidden();
});
