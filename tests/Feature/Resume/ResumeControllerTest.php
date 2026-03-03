<?php

use App\Jobs\GenerateResumeJob;
use App\Models\GapAnalysis;
use App\Models\IdealCandidateProfile;
use App\Models\JobPosting;
use App\Models\Resume;
use App\Models\ResumeSection;
use App\Models\ResumeSectionVariant;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    Queue::fake();
});

test('guest cannot access resume pages', function () {
    $this->get('/resumes')->assertRedirect('/login');
    $this->get('/resumes/1')->assertRedirect('/login');
});

test('index displays resumes', function () {
    Resume::factory()->count(3)->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get('/resumes')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('resumes/index')
                ->has('resumes', 3)
        );
});

test('generate creates resume from gap analysis and dispatches job', function () {
    $posting = JobPosting::factory()->analyzed()->create(['user_id' => $this->user->id]);
    $profile = IdealCandidateProfile::factory()->create(['job_posting_id' => $posting->id]);
    $analysis = GapAnalysis::factory()->create([
        'user_id' => $this->user->id,
        'ideal_candidate_profile_id' => $profile->id,
    ]);

    $this->actingAs($this->user)
        ->post("/gap-analyses/{$analysis->id}/resume")
        ->assertRedirect();

    $resume = Resume::first();
    expect($resume)
        ->user_id->toBe($this->user->id)
        ->gap_analysis_id->toBe($analysis->id)
        ->job_posting_id->toBe($posting->id)
        ->is_finalized->toBeFalse();

    Queue::assertPushed(GenerateResumeJob::class, function ($job) use ($resume) {
        return $job->resume->id === $resume->id;
    });
});

test('generate returns 403 for other users gap analysis', function () {
    $other = User::factory()->create();
    $posting = JobPosting::factory()->analyzed()->create(['user_id' => $other->id]);
    $profile = IdealCandidateProfile::factory()->create(['job_posting_id' => $posting->id]);
    $analysis = GapAnalysis::factory()->create([
        'user_id' => $other->id,
        'ideal_candidate_profile_id' => $profile->id,
    ]);

    $this->actingAs($this->user)
        ->post("/gap-analyses/{$analysis->id}/resume")
        ->assertForbidden();
});

test('show displays resume with sections and variants', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);
    ResumeSectionVariant::factory()->count(2)->create(['resume_section_id' => $section->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('resumes/show')
                ->has('resume')
                ->has('resume.sections', 1)
                ->has('resume.sections.0.variants', 2)
        );
});

test('show returns 403 for other users resume', function () {
    $other = User::factory()->create();
    $resume = Resume::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}")
        ->assertForbidden();
});

test('update modifies resume', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->put("/resumes/{$resume->id}", [
            'title' => 'Updated Resume Title',
        ])
        ->assertRedirect();

    expect($resume->fresh()->title)->toBe('Updated Resume Title');
});

test('update can finalize resume', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->put("/resumes/{$resume->id}", [
            'is_finalized' => true,
        ])
        ->assertRedirect();

    expect($resume->fresh()->is_finalized)->toBeTrue();
});

test('select variant updates section', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);
    $variant = ResumeSectionVariant::factory()->create(['resume_section_id' => $section->id]);

    $this->actingAs($this->user)
        ->put("/resumes/{$resume->id}/sections/{$section->id}", [
            'variant_id' => $variant->id,
        ])
        ->assertRedirect();

    expect($section->fresh()->selected_variant_id)->toBe($variant->id);
});

test('select variant validates variant exists', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);

    $this->actingAs($this->user)
        ->put("/resumes/{$resume->id}/sections/{$section->id}", [
            'variant_id' => 99999,
        ])
        ->assertSessionHasErrors('variant_id');
});

test('edit variant updates content and marks as user edited', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);
    $variant = ResumeSectionVariant::factory()->create([
        'resume_section_id' => $section->id,
        'is_user_edited' => false,
    ]);

    $this->actingAs($this->user)
        ->put("/resumes/{$resume->id}/variants/{$variant->id}", [
            'content' => 'Updated variant content',
        ])
        ->assertRedirect();

    expect($variant->fresh())
        ->content->toBe('Updated variant content')
        ->is_user_edited->toBeTrue();
});

test('edit variant validates content is required', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);
    $variant = ResumeSectionVariant::factory()->create(['resume_section_id' => $section->id]);

    $this->actingAs($this->user)
        ->put("/resumes/{$resume->id}/variants/{$variant->id}", [])
        ->assertSessionHasErrors('content');
});

test('destroy deletes resume', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->delete("/resumes/{$resume->id}")
        ->assertRedirect('/resumes');

    expect(Resume::find($resume->id))->toBeNull();
});

test('destroy returns 403 for other users resume', function () {
    $other = User::factory()->create();
    $resume = Resume::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->delete("/resumes/{$resume->id}")
        ->assertForbidden();
});
