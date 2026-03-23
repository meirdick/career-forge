<?php

use App\Models\ProfessionalIdentity;
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

test('export pdf renders for each template style', function (string $template) {
    $resume = Resume::factory()->create([
        'user_id' => $this->user->id,
        'template' => $template,
    ]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);
    $variant = ResumeSectionVariant::factory()->create(['resume_section_id' => $section->id]);
    $section->update(['selected_variant_id' => $variant->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}/export/pdf")
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');
})->with(['classic', 'moderncv', 'sb2nov', 'engineeringresumes', 'engineeringclassic']);

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

test('finalize marks resume as finalized and creates application', function () {
    $jobPosting = \App\Models\JobPosting::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Senior Developer',
        'company' => 'Acme Corp',
    ]);
    $resume = Resume::factory()->create([
        'user_id' => $this->user->id,
        'job_posting_id' => $jobPosting->id,
    ]);

    $this->actingAs($this->user)
        ->post("/resumes/{$resume->id}/finalize")
        ->assertRedirect();

    expect($resume->fresh()->is_finalized)->toBeTrue();

    $application = $this->user->applications()->latest()->first();
    expect($application)
        ->not->toBeNull()
        ->company->toBe('Acme Corp')
        ->role->toBe('Senior Developer')
        ->resume_id->toBe($resume->id)
        ->job_posting_id->toBe($jobPosting->id)
        ->status->toBe(\App\Enums\ApplicationStatus::Draft);

    expect($application->statusChanges)->toHaveCount(1);
});

test('finalize creates application without job posting', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->post("/resumes/{$resume->id}/finalize")
        ->assertRedirect();

    expect($resume->fresh()->is_finalized)->toBeTrue();

    $application = $this->user->applications()->latest()->first();
    expect($application)
        ->not->toBeNull()
        ->company->toBe('Unknown')
        ->job_posting_id->toBeNull();
});

test('finalize returns 403 for other users resume', function () {
    $other = User::factory()->create();
    $resume = Resume::factory()->create(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->post("/resumes/{$resume->id}/finalize")
        ->assertForbidden();
});

test('preview uses resolved header from ResumeHeaderService', function () {
    $this->user->update(['legal_name' => 'Jane Marie Doe']);
    ProfessionalIdentity::factory()->create([
        'user_id' => $this->user->id,
        'resume_header_config' => [
            'name_preference' => 'legal_name',
            'show_phone' => false,
        ],
    ]);

    $resume = Resume::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}/preview")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->where('contact.name', 'Jane Marie Doe')
                ->where('contact.phone', null)
        );
});

test('export pdf uses resolved header', function () {
    $this->user->update(['legal_name' => 'Legal Name Test']);
    ProfessionalIdentity::factory()->create([
        'user_id' => $this->user->id,
        'resume_header_config' => ['name_preference' => 'legal_name'],
    ]);

    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);
    $variant = ResumeSectionVariant::factory()->create(['resume_section_id' => $section->id]);
    $section->update(['selected_variant_id' => $variant->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}/export/pdf")
        ->assertSuccessful();
});

test('export docx uses resolved header', function () {
    $this->user->update(['legal_name' => 'Legal Name DOCX']);
    ProfessionalIdentity::factory()->create([
        'user_id' => $this->user->id,
        'resume_header_config' => ['name_preference' => 'legal_name'],
    ]);

    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);
    $variant = ResumeSectionVariant::factory()->create(['resume_section_id' => $section->id]);
    $section->update(['selected_variant_id' => $variant->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}/export/docx")
        ->assertSuccessful();
});

test('docx handles markdown links', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);
    $variant = ResumeSectionVariant::factory()->create([
        'resume_section_id' => $section->id,
        'content' => 'Check out [My Portfolio](https://example.com) for details.',
    ]);
    $section->update(['selected_variant_id' => $variant->id]);

    $response = $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}/export/docx")
        ->assertSuccessful();

    $fullPath = storage_path('app/private/resumes/'.$resume->id.'.docx');
    expect(file_exists($fullPath))->toBeTrue();

    $zip = new \ZipArchive;
    expect($zip->open($fullPath))->toBe(true);
    $zip->close();
});

test('docx handles headers in content', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);
    $variant = ResumeSectionVariant::factory()->create([
        'resume_section_id' => $section->id,
        'content' => "### Company Name\n- Built features\n- Led team",
    ]);
    $section->update(['selected_variant_id' => $variant->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}/export/docx")
        ->assertSuccessful();

    $fullPath = storage_path('app/private/resumes/'.$resume->id.'.docx');
    $zip = new \ZipArchive;
    expect($zip->open($fullPath))->toBe(true);
    $zip->close();
});

test('docx handles invalid xml characters', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);
    $variant = ResumeSectionVariant::factory()->create([
        'resume_section_id' => $section->id,
        'content' => "Some text with \x00 null \x08 backspace \x0B vertical tab chars",
    ]);
    $section->update(['selected_variant_id' => $variant->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}/export/docx")
        ->assertSuccessful();

    $fullPath = storage_path('app/private/resumes/'.$resume->id.'.docx');
    $zip = new \ZipArchive;
    expect($zip->open($fullPath))->toBe(true);
    $zip->close();
});

test('docx handles numbered lists', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);
    $variant = ResumeSectionVariant::factory()->create([
        'resume_section_id' => $section->id,
        'content' => "1. First item\n2. Second item\n3. Third item",
    ]);
    $section->update(['selected_variant_id' => $variant->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}/export/docx")
        ->assertSuccessful();

    $fullPath = storage_path('app/private/resumes/'.$resume->id.'.docx');
    $zip = new \ZipArchive;
    expect($zip->open($fullPath))->toBe(true);
    $zip->close();
});

test('docx generates valid zip for each template', function (string $template) {
    $resume = Resume::factory()->create([
        'user_id' => $this->user->id,
        'template' => $template,
    ]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);
    $variant = ResumeSectionVariant::factory()->create(['resume_section_id' => $section->id]);
    $section->update(['selected_variant_id' => $variant->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}/export/docx")
        ->assertSuccessful();

    $fullPath = storage_path('app/private/resumes/'.$resume->id.'.docx');
    $zip = new \ZipArchive;
    expect($zip->open($fullPath))->toBe(true);
    $zip->close();
})->with(['classic', 'moderncv', 'sb2nov', 'engineeringresumes', 'engineeringclassic']);

test('hidden sections excluded from pdf export', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $visibleSection = ResumeSection::factory()->create([
        'resume_id' => $resume->id,
        'title' => 'Visible Section',
        'is_hidden' => false,
    ]);
    $hiddenSection = ResumeSection::factory()->create([
        'resume_id' => $resume->id,
        'title' => 'Hidden Section',
        'is_hidden' => true,
    ]);
    $v1 = ResumeSectionVariant::factory()->create(['resume_section_id' => $visibleSection->id]);
    $v2 = ResumeSectionVariant::factory()->create(['resume_section_id' => $hiddenSection->id]);
    $visibleSection->update(['selected_variant_id' => $v1->id]);
    $hiddenSection->update(['selected_variant_id' => $v2->id]);

    // Verify rendered Blade view excludes hidden sections
    $resume->load(['sections.selectedVariant', 'user', 'jobPosting']);
    $header = app(\App\Services\ResumeHeaderService::class)->resolveHeader($resume);
    $html = view('resumes.pdf', [
        'resume' => $resume,
        'user' => $resume->user,
        'header' => $header,
        'template' => $resume->template?->value ?? 'classic',
    ])->render();

    expect($html)->toContain('Visible Section');
    expect($html)->not->toContain('Hidden Section');
});

test('hidden sections excluded from docx export', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $visibleSection = ResumeSection::factory()->create([
        'resume_id' => $resume->id,
        'title' => 'Visible Section',
        'is_hidden' => false,
    ]);
    $hiddenSection = ResumeSection::factory()->create([
        'resume_id' => $resume->id,
        'title' => 'Hidden Section',
        'is_hidden' => true,
    ]);
    $v1 = ResumeSectionVariant::factory()->create(['resume_section_id' => $visibleSection->id]);
    $v2 = ResumeSectionVariant::factory()->create(['resume_section_id' => $hiddenSection->id]);
    $visibleSection->update(['selected_variant_id' => $v1->id]);
    $hiddenSection->update(['selected_variant_id' => $v2->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}/export/docx")
        ->assertSuccessful();

    $fullPath = storage_path('app/private/resumes/'.$resume->id.'.docx');
    $zip = new \ZipArchive;
    $zip->open($fullPath);
    $xmlContent = $zip->getFromName('word/document.xml');
    $zip->close();

    expect($xmlContent)->toContain('Visible Section');
    expect($xmlContent)->not->toContain('Hidden Section');
});

test('docx contains section titles and formatted content', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create([
        'resume_id' => $resume->id,
        'title' => 'Work Experience',
    ]);
    $variant = ResumeSectionVariant::factory()->create([
        'resume_section_id' => $section->id,
        'content' => "### Acme Corp\n*Jan 2020 – Present*\n- **Led** a team of engineers\n- Built scalable systems",
    ]);
    $section->update(['selected_variant_id' => $variant->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}/export/docx")
        ->assertSuccessful();

    $fullPath = storage_path('app/private/resumes/'.$resume->id.'.docx');
    $zip = new \ZipArchive;
    $zip->open($fullPath);
    $xmlContent = $zip->getFromName('word/document.xml');
    $zip->close();

    expect($xmlContent)
        ->toContain('Work Experience')
        ->toContain('Acme Corp')
        ->toContain('Jan 2020')
        ->toContain('Led')
        ->toContain('Built scalable systems');
});

test('docx contains bold and italic xml tags', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);
    $variant = ResumeSectionVariant::factory()->create([
        'resume_section_id' => $section->id,
        'content' => '**Bold text** and *italic text*',
    ]);
    $section->update(['selected_variant_id' => $variant->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}/export/docx")
        ->assertSuccessful();

    $fullPath = storage_path('app/private/resumes/'.$resume->id.'.docx');
    $zip = new \ZipArchive;
    $zip->open($fullPath);
    $xmlContent = $zip->getFromName('word/document.xml');
    $zip->close();

    // Verify bold tag exists (w:b or w:b/)
    expect($xmlContent)->toMatch('/<w:b[\s\/]/')
        ->and($xmlContent)->toContain('Bold text');

    // Verify italic tag exists (w:i or w:i/)
    expect($xmlContent)->toMatch('/<w:i[\s\/]/')
        ->and($xmlContent)->toContain('italic text');
});

test('docx has us letter page size', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);
    $variant = ResumeSectionVariant::factory()->create(['resume_section_id' => $section->id]);
    $section->update(['selected_variant_id' => $variant->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}/export/docx")
        ->assertSuccessful();

    $fullPath = storage_path('app/private/resumes/'.$resume->id.'.docx');
    $zip = new \ZipArchive;
    $zip->open($fullPath);
    $xmlContent = $zip->getFromName('word/document.xml');
    $zip->close();

    // US Letter in twips: 12240 x 15840
    expect($xmlContent)->toContain('w:w="12240"')
        ->and($xmlContent)->toContain('w:h="15840"');
});

test('docx document xml is well-formed', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);
    $variant = ResumeSectionVariant::factory()->create([
        'resume_section_id' => $section->id,
        'content' => "### Company\n**Bold** and *italic*\n- List item one\n- List item two\n1. Numbered one\n2. Numbered two",
    ]);
    $section->update(['selected_variant_id' => $variant->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}/export/docx")
        ->assertSuccessful();

    $fullPath = storage_path('app/private/resumes/'.$resume->id.'.docx');
    $zip = new \ZipArchive;
    $zip->open($fullPath);
    $xmlContent = $zip->getFromName('word/document.xml');
    $zip->close();

    // Verify the XML is well-formed by loading it
    $doc = new \DOMDocument;
    $result = $doc->loadXML($xmlContent);
    expect($result)->toBeTrue();
});

test('pdf export has letter page size', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);
    $variant = ResumeSectionVariant::factory()->create(['resume_section_id' => $section->id]);
    $section->update(['selected_variant_id' => $variant->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}/export/pdf")
        ->assertSuccessful();

    $fullPath = storage_path('app/private/resumes/'.$resume->id.'.pdf');
    expect(file_exists($fullPath))->toBeTrue();

    $parser = new \Smalot\PdfParser\Parser;
    $pdf = $parser->parseFile($fullPath);
    $pages = $pdf->getPages();

    expect($pages)->not->toBeEmpty();

    $details = $pages[0]->getDetails();
    // US Letter: 612 x 792 points
    if (isset($details['MediaBox'])) {
        $width = (float) $details['MediaBox'][2];
        $height = (float) $details['MediaBox'][3];
        expect($width)->toBeBetween(610, 614)
            ->and($height)->toBeBetween(790, 794);
    }
});
