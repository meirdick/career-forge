<?php

use App\Enums\ResumeSectionType;
use App\Models\Resume;
use App\Models\ResumeSection;
use App\Models\ResumeSectionVariant;
use App\Models\User;

// Resume
test('resume factory creates valid model', function () {
    $resume = Resume::factory()->create();

    expect($resume)->toBeInstanceOf(Resume::class)
        ->and($resume->title)->toBeString()
        ->and($resume->user)->toBeInstanceOf(User::class);
});

test('resume has many sections', function () {
    $resume = Resume::factory()->create();
    ResumeSection::factory()->count(4)->create(['resume_id' => $resume->id]);

    expect($resume->sections)->toHaveCount(4);
});

test('resume sections are ordered by sort_order', function () {
    $resume = Resume::factory()->create();
    ResumeSection::factory()->create(['resume_id' => $resume->id, 'sort_order' => 2]);
    ResumeSection::factory()->create(['resume_id' => $resume->id, 'sort_order' => 0]);
    ResumeSection::factory()->create(['resume_id' => $resume->id, 'sort_order' => 1]);

    $sortOrders = $resume->sections->pluck('sort_order')->all();

    expect($sortOrders)->toBe([0, 1, 2]);
});

test('resume finalized state', function () {
    $resume = Resume::factory()->finalized()->create();

    expect($resume->is_finalized)->toBeTrue();
});

test('user has many resumes', function () {
    $user = User::factory()->create();
    Resume::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->resumes)->toHaveCount(3);
});

// Resume Section
test('resume section factory creates valid model', function () {
    $section = ResumeSection::factory()->create();

    expect($section)->toBeInstanceOf(ResumeSection::class)
        ->and($section->type)->toBeInstanceOf(ResumeSectionType::class);
});

test('resume section has many variants', function () {
    $section = ResumeSection::factory()->create();
    ResumeSectionVariant::factory()->count(3)->create(['resume_section_id' => $section->id]);

    expect($section->variants)->toHaveCount(3);
});

test('resume section can select a variant', function () {
    $section = ResumeSection::factory()->create();
    $variant = ResumeSectionVariant::factory()->create(['resume_section_id' => $section->id]);

    $section->update(['selected_variant_id' => $variant->id]);

    expect($section->fresh()->selectedVariant->id)->toBe($variant->id);
});

test('deleting resume cascades to sections and variants', function () {
    $resume = Resume::factory()->create();
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);
    $variant = ResumeSectionVariant::factory()->create(['resume_section_id' => $section->id]);

    $resume->delete();

    expect(ResumeSection::find($section->id))->toBeNull()
        ->and(ResumeSectionVariant::find($variant->id))->toBeNull();
});

// Resume Section Variant
test('resume section variant factory creates valid model', function () {
    $variant = ResumeSectionVariant::factory()->create();

    expect($variant)->toBeInstanceOf(ResumeSectionVariant::class)
        ->and($variant->content)->toBeString()
        ->and($variant->label)->toBeString();
});

test('resume section variant casts booleans', function () {
    $variant = ResumeSectionVariant::factory()->create([
        'is_ai_generated' => true,
        'is_user_edited' => false,
    ]);

    expect($variant->is_ai_generated)->toBeTrue()
        ->and($variant->is_user_edited)->toBeFalse();
});
