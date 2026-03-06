<?php

use App\Jobs\GenerateResumeJob;
use App\Models\Document;
use App\Models\GapAnalysis;
use App\Models\IdealCandidateProfile;
use App\Models\JobPosting;
use App\Models\Resume;
use App\Models\ResumeSection;
use App\Models\ResumeSectionVariant;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

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

test('update from preview page redirects back to preview', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->from("/resumes/{$resume->id}/preview")
        ->put("/resumes/{$resume->id}", [
            'template' => 'moderncv',
        ])
        ->assertRedirect("/resumes/{$resume->id}/preview");
});

test('update from show page redirects back to show', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->from("/resumes/{$resume->id}")
        ->put("/resumes/{$resume->id}", [
            'title' => 'New Title',
        ])
        ->assertRedirect("/resumes/{$resume->id}");
});

test('update saves header config', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->put("/resumes/{$resume->id}", [
            'header_config' => [
                'name_preference' => 'legal_name',
                'show_email' => true,
                'show_phone' => false,
            ],
        ])
        ->assertRedirect();

    $config = $resume->fresh()->header_config;
    expect($config['name_preference'])->toBe('legal_name');
    expect($config['show_phone'])->toBeFalse();
});

test('show passes globalHeaderConfig', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('globalHeaderConfig'));
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

test('index shows uploaded documents', function () {
    Document::factory()->create([
        'user_id' => $this->user->id,
        'filename' => 'my-resume.pdf',
        'metadata' => ['purpose' => 'resume_import'],
    ]);

    $this->actingAs($this->user)
        ->get('/resumes')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('resumes/index')
                ->has('uploadedDocuments', 1)
                ->where('uploadedDocuments.0.filename', 'my-resume.pdf')
        );
});

test('index does not show non-resume documents', function () {
    Document::factory()->create([
        'user_id' => $this->user->id,
        'metadata' => ['purpose' => 'other'],
    ]);

    $this->actingAs($this->user)
        ->get('/resumes')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('resumes/index')
                ->has('uploadedDocuments', 0)
        );
});

test('document download works for owner', function () {
    Storage::fake();
    Storage::put('resume-uploads/test.pdf', 'fake-pdf-content');

    $document = Document::factory()->create([
        'user_id' => $this->user->id,
        'filename' => 'test.pdf',
        'disk' => 'local',
        'path' => 'resume-uploads/test.pdf',
        'metadata' => ['purpose' => 'resume_import'],
    ]);

    $this->actingAs($this->user)
        ->get("/documents/{$document->id}/download")
        ->assertSuccessful();
});

test('document download returns 403 for non-owner', function () {
    $other = User::factory()->create();
    $document = Document::factory()->create([
        'user_id' => $other->id,
        'metadata' => ['purpose' => 'resume_import'],
    ]);

    $this->actingAs($this->user)
        ->get("/documents/{$document->id}/download")
        ->assertForbidden();
});

test('toggle section hides section', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id, 'is_hidden' => false]);

    $this->actingAs($this->user)
        ->put("/resumes/{$resume->id}/sections/{$section->id}/toggle")
        ->assertRedirect();

    expect($section->fresh()->is_hidden)->toBeTrue();
});

test('toggle section shows hidden section', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id, 'is_hidden' => true]);

    $this->actingAs($this->user)
        ->put("/resumes/{$resume->id}/sections/{$section->id}/toggle")
        ->assertRedirect();

    expect($section->fresh()->is_hidden)->toBeFalse();
});

test('toggle section returns 403 for other user', function () {
    $other = User::factory()->create();
    $resume = Resume::factory()->create(['user_id' => $other->id]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);

    $this->actingAs($this->user)
        ->put("/resumes/{$resume->id}/sections/{$section->id}/toggle")
        ->assertForbidden();
});

test('update section title', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id, 'title' => 'Old Title']);

    $this->actingAs($this->user)
        ->patch("/resumes/{$resume->id}/sections/{$section->id}", [
            'title' => 'New Title',
        ])
        ->assertRedirect();

    expect($section->fresh()->title)->toBe('New Title');
});

test('destroy section deletes section', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id, 'section_order' => [1, 2]]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);
    ResumeSectionVariant::factory()->count(2)->create(['resume_section_id' => $section->id]);

    $resume->update(['section_order' => [$section->id]]);

    $this->actingAs($this->user)
        ->delete("/resumes/{$resume->id}/sections/{$section->id}")
        ->assertRedirect();

    expect(ResumeSection::find($section->id))->toBeNull();
    expect(ResumeSectionVariant::where('resume_section_id', $section->id)->count())->toBe(0);
    expect($resume->fresh()->section_order)->not->toContain($section->id);
});

test('destroy section returns 403 for other user', function () {
    $other = User::factory()->create();
    $resume = Resume::factory()->create(['user_id' => $other->id]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);

    $this->actingAs($this->user)
        ->delete("/resumes/{$resume->id}/sections/{$section->id}")
        ->assertForbidden();
});
