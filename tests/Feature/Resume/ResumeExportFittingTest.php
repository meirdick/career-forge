<?php

use App\Enums\ResumeSectionType;
use App\Models\Resume;
use App\Models\ResumeSection;
use App\Models\ResumeSectionVariant;
use App\Models\User;
use App\Services\ResumeExportService;
use App\Services\ResumeHeaderService;
use Smalot\PdfParser\Parser;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('pdf export respects page_limit default of 1', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);
    $variant = ResumeSectionVariant::factory()->create([
        'resume_section_id' => $section->id,
        'content' => "- Bullet point\n- Another bullet",
    ]);
    $section->update(['selected_variant_id' => $variant->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}/export/pdf")
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');

    $fullPath = storage_path('app/private/resumes/'.$resume->id.'.pdf');
    $parser = new Parser;
    $pdf = $parser->parseFile($fullPath);

    expect(count($pdf->getPages()))->toBeLessThanOrEqual(1);
});

test('pdf fitting loop reduces pages with compact mode', function () {
    $resume = Resume::factory()->create([
        'user_id' => $this->user->id,
        'page_limit' => 1,
    ]);

    // Create multiple sections with long content to force overflow
    foreach (['Summary', 'Experience', 'Skills', 'Education'] as $i => $title) {
        $section = ResumeSection::factory()->create([
            'resume_id' => $resume->id,
            'title' => $title,
            'sort_order' => $i,
            'type' => match ($title) {
                'Summary' => ResumeSectionType::Summary,
                'Experience' => ResumeSectionType::Experience,
                'Skills' => ResumeSectionType::Skills,
                'Education' => ResumeSectionType::Education,
            },
        ]);
        $variant = ResumeSectionVariant::factory()->create([
            'resume_section_id' => $section->id,
            'content' => str_repeat("- A detailed bullet point about accomplishments and achievements in this role\n", 15),
            'compact_content' => 'Condensed version of the section',
        ]);
        $section->update(['selected_variant_id' => $variant->id]);
    }

    $service = app(ResumeExportService::class);
    $path = $service->toPdf($resume);

    $fullPath = storage_path('app/private/'.$path);
    expect(file_exists($fullPath))->toBeTrue();

    $parser = new Parser;
    $pdf = $parser->parseFile($fullPath);

    // With fitting, should be constrained to 1 page (or close)
    expect(count($pdf->getPages()))->toBeLessThanOrEqual(2);
});

test('pdf export works with page_limit of 2', function () {
    $resume = Resume::factory()->create([
        'user_id' => $this->user->id,
        'page_limit' => 2,
    ]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);
    $variant = ResumeSectionVariant::factory()->create([
        'resume_section_id' => $section->id,
        'content' => str_repeat("- A bullet point\n", 10),
    ]);
    $section->update(['selected_variant_id' => $variant->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}/export/pdf")
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');
});

test('pdf export passes fitting variables to all templates', function (string $template) {
    $resume = Resume::factory()->create([
        'user_id' => $this->user->id,
        'template' => $template,
    ]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);
    $variant = ResumeSectionVariant::factory()->create(['resume_section_id' => $section->id]);
    $section->update(['selected_variant_id' => $variant->id]);

    $resume->load(['sections.selectedVariant', 'user', 'jobPosting']);
    $header = app(ResumeHeaderService::class)->resolveHeader($resume);

    // Verify template renders without errors when fitting variables are provided
    $html = view('resumes.pdf', [
        'resume' => $resume,
        'user' => $resume->user,
        'header' => $header,
        'template' => $template,
        'fontSizeAdjust' => -0.5,
        'spacingAdjust' => -2,
        'marginAdjust' => -0.05,
        'sectionOverrides' => [],
        'contentOverrides' => [],
        'hiddenSections' => [],
    ])->render();

    expect($html)->toBeString()->not->toBeEmpty();
})->with(['classic', 'moderncv', 'sb2nov', 'engineeringresumes', 'engineeringclassic']);

test('pdf template respects hiddenSections', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section1 = ResumeSection::factory()->create([
        'resume_id' => $resume->id,
        'title' => 'Visible',
    ]);
    $section2 = ResumeSection::factory()->create([
        'resume_id' => $resume->id,
        'title' => 'HiddenByFitting',
    ]);
    $v1 = ResumeSectionVariant::factory()->create(['resume_section_id' => $section1->id]);
    $v2 = ResumeSectionVariant::factory()->create(['resume_section_id' => $section2->id]);
    $section1->update(['selected_variant_id' => $v1->id]);
    $section2->update(['selected_variant_id' => $v2->id]);

    $resume->load(['sections.selectedVariant', 'user', 'jobPosting']);
    $header = app(ResumeHeaderService::class)->resolveHeader($resume);

    $html = view('resumes.pdf', [
        'resume' => $resume,
        'user' => $resume->user,
        'header' => $header,
        'template' => 'classic',
        'fontSizeAdjust' => 0,
        'spacingAdjust' => 0,
        'marginAdjust' => 0,
        'sectionOverrides' => [],
        'contentOverrides' => [],
        'hiddenSections' => [$section2->id],
    ])->render();

    expect($html)
        ->toContain('Visible')
        ->not->toContain('HiddenByFitting');
});

test('pdf template uses compact content when section override is set', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create([
        'resume_id' => $resume->id,
        'title' => 'Experience',
    ]);
    $variant = ResumeSectionVariant::factory()->create([
        'resume_section_id' => $section->id,
        'content' => 'Full version with lots of detail',
        'compact_content' => 'Compact version',
    ]);
    $section->update(['selected_variant_id' => $variant->id]);

    $resume->load(['sections.selectedVariant', 'user', 'jobPosting']);
    $header = app(ResumeHeaderService::class)->resolveHeader($resume);

    $html = view('resumes.pdf', [
        'resume' => $resume,
        'user' => $resume->user,
        'header' => $header,
        'template' => 'classic',
        'fontSizeAdjust' => 0,
        'spacingAdjust' => 0,
        'marginAdjust' => 0,
        'sectionOverrides' => [$section->id => 'compact'],
        'contentOverrides' => [],
        'hiddenSections' => [],
    ])->render();

    expect($html)
        ->toContain('Compact version')
        ->not->toContain('Full version with lots of detail');
});

test('pdf template uses content override when provided', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create([
        'resume_id' => $resume->id,
        'title' => 'Experience',
    ]);
    $variant = ResumeSectionVariant::factory()->create([
        'resume_section_id' => $section->id,
        'content' => 'Original full content with many bullets',
        'compact_content' => 'Compact version',
    ]);
    $section->update(['selected_variant_id' => $variant->id]);

    $resume->load(['sections.selectedVariant', 'user', 'jobPosting']);
    $header = app(ResumeHeaderService::class)->resolveHeader($resume);

    $html = view('resumes.pdf', [
        'resume' => $resume,
        'user' => $resume->user,
        'header' => $header,
        'template' => 'classic',
        'fontSizeAdjust' => 0,
        'spacingAdjust' => 0,
        'marginAdjust' => 0,
        'sectionOverrides' => [],
        'contentOverrides' => [$section->id => 'LLM trimmed content here'],
        'hiddenSections' => [],
    ])->render();

    expect($html)
        ->toContain('LLM trimmed content here')
        ->not->toContain('Original full content with many bullets')
        ->not->toContain('Compact version');
});

test('content override takes priority over compact override', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create([
        'resume_id' => $resume->id,
        'title' => 'Skills',
    ]);
    $variant = ResumeSectionVariant::factory()->create([
        'resume_section_id' => $section->id,
        'content' => 'Original content',
        'compact_content' => 'Compact content',
    ]);
    $section->update(['selected_variant_id' => $variant->id]);

    $resume->load(['sections.selectedVariant', 'user', 'jobPosting']);
    $header = app(ResumeHeaderService::class)->resolveHeader($resume);

    $html = view('resumes.pdf', [
        'resume' => $resume,
        'user' => $resume->user,
        'header' => $header,
        'template' => 'classic',
        'fontSizeAdjust' => 0,
        'spacingAdjust' => 0,
        'marginAdjust' => 0,
        'sectionOverrides' => [$section->id => 'compact'],
        'contentOverrides' => [$section->id => 'Trimmed by LLM'],
        'hiddenSections' => [],
    ])->render();

    expect($html)
        ->toContain('Trimmed by LLM')
        ->not->toContain('Original content')
        ->not->toContain('Compact content');
});
