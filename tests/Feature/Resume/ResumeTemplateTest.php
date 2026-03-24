<?php

use App\Enums\ResumeSectionType;
use App\Enums\ResumeTemplate;
use App\Models\Resume;
use App\Models\ResumeSection;
use App\Models\ResumeSectionVariant;
use App\Models\User;
use App\Models\UserLink;
use App\Services\RenderCvService;

beforeEach(function () {
    $this->user = User::factory()->create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '555-0100',
        'location' => 'San Francisco, CA',
        'linkedin_url' => 'https://linkedin.com/in/janedoe',
    ]);
    UserLink::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://janedoe.dev',
        'type' => 'portfolio',
    ]);
});

// --- Template Enum ---

test('resume template enum has all expected cases', function () {
    $cases = ResumeTemplate::cases();

    expect($cases)->toHaveCount(5);
    expect(array_map(fn ($c) => $c->value, $cases))->toBe([
        'classic', 'moderncv', 'sb2nov', 'engineeringresumes', 'engineeringclassic',
    ]);
});

test('resume template enum provides labels', function () {
    expect(ResumeTemplate::Classic->label())->toBe('Classic');
    expect(ResumeTemplate::ModernCV->label())->toBe('Modern CV');
    expect(ResumeTemplate::Sb2nov->label())->toBe('SB2Nov');
    expect(ResumeTemplate::EngineeringResumes->label())->toBe('Engineering');
    expect(ResumeTemplate::EngineeringClassic->label())->toBe('Engineering Classic');
});

test('resume template enum provides descriptions', function () {
    expect(ResumeTemplate::Classic->description())->toBeString()->not->toBeEmpty();
});

// --- Model ---

test('resume model casts template to enum', function () {
    $resume = Resume::factory()->create([
        'user_id' => $this->user->id,
        'template' => 'moderncv',
    ]);

    expect($resume->template)->toBe(ResumeTemplate::ModernCV);
});

test('resume factory defaults to classic template', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);

    expect($resume->template)->toBe(ResumeTemplate::Classic);
});

// --- Controller: template validation ---

test('update accepts valid template', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->put("/resumes/{$resume->id}", ['template' => 'moderncv'])
        ->assertRedirect();

    expect($resume->fresh()->template)->toBe(ResumeTemplate::ModernCV);
});

test('update rejects invalid template', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->put("/resumes/{$resume->id}", ['template' => 'nonexistent'])
        ->assertSessionHasErrors('template');
});

// --- Preview: contact data ---

test('preview includes contact data', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create(['resume_id' => $resume->id]);
    $variant = ResumeSectionVariant::factory()->create(['resume_section_id' => $section->id]);
    $section->update(['selected_variant_id' => $variant->id]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}/preview")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('resumes/preview')
                ->has('contact')
                ->where('contact.name', 'Jane Doe')
                ->where('contact.email', 'jane@example.com')
                ->where('contact.phone', '555-0100')
        );
});

// --- formattedContent accessor ---

test('resume section variant has formatted content accessor', function () {
    $variant = ResumeSectionVariant::factory()->create([
        'content' => "**Bold text** and *italic text*\n\n- Bullet point",
    ]);

    $formatted = $variant->formatted_content;

    expect($formatted)->toContain('<strong>Bold text</strong>');
    expect($formatted)->toContain('<em>italic text</em>');
    expect($formatted)->toContain('<li>Bullet point</li>');
});

test('formatted content handles plain text gracefully', function () {
    $variant = ResumeSectionVariant::factory()->create([
        'content' => 'Simple plain text content',
    ]);

    $formatted = $variant->formatted_content;

    expect($formatted)->toContain('Simple plain text content');
});

// --- RenderCvService YAML building ---

test('render cv service builds valid yaml', function () {
    $resume = Resume::factory()->create([
        'user_id' => $this->user->id,
        'template' => 'classic',
    ]);
    $section = ResumeSection::factory()->create([
        'resume_id' => $resume->id,
        'type' => ResumeSectionType::Summary,
        'title' => 'Summary',
    ]);
    $variant = ResumeSectionVariant::factory()->create([
        'resume_section_id' => $section->id,
        'content' => 'Experienced software engineer with 10+ years.',
    ]);
    $section->update(['selected_variant_id' => $variant->id]);

    $service = app(RenderCvService::class);
    $yaml = $service->buildYaml($resume->fresh());

    expect($yaml)->toContain('Jane Doe');
    expect($yaml)->toContain('jane@example.com');
    expect($yaml)->toContain('classic');
    expect($yaml)->toContain('Summary');
});

test('render cv service extracts linkedin username', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);

    $service = app(RenderCvService::class);
    $yaml = $service->buildYaml($resume);

    expect($yaml)->toContain('janedoe');
    expect($yaml)->toContain('LinkedIn');
});

// --- Content parsing ---

test('render cv service parses experience content', function () {
    $service = app(RenderCvService::class);

    $content = "**Acme Corp** — *Senior Developer*\n*2020 – 2024* | San Francisco\n- Led team of 5 engineers\n- Increased test coverage by 40%";

    $result = $service->parseVariantContent(trim($content), ResumeSectionType::Experience);

    expect($result)->toHaveCount(1);
    expect($result[0]['company'])->toBe('Acme Corp');
    expect($result[0]['position'])->toBe('Senior Developer');
    expect($result[0]['start_date'])->toBe('2020');
    expect($result[0]['end_date'])->toBe('2024');
    expect($result[0]['location'])->toBe('San Francisco');
    expect($result[0]['highlights'])->toHaveCount(2);
});

test('render cv service parses education content', function () {
    $service = app(RenderCvService::class);

    $content = "**B.S. Computer Science** — *MIT*\n*2016 – 2020*\n- Dean's List";

    $result = $service->parseVariantContent(trim($content), ResumeSectionType::Education);

    expect($result)->toHaveCount(1);
    expect($result[0]['institution'])->toBe('MIT');
    expect($result[0]['area'])->toBe('B.S. Computer Science');
    expect($result[0]['highlights'])->toContain("Dean's List");
});

test('render cv service parses skills content', function () {
    $service = app(RenderCvService::class);

    $content = "**Languages:** Python, TypeScript, Go\n**Frameworks:** React, Laravel";

    $result = $service->parseVariantContent($content, ResumeSectionType::Skills);

    expect($result)->toHaveCount(2);
    expect($result[0])->toContain('Languages:');
    expect($result[1])->toContain('Frameworks:');
});

test('render cv service returns summary as single string', function () {
    $service = app(RenderCvService::class);

    $content = 'Experienced engineer with strong backend skills.';

    $result = $service->parseVariantContent($content, ResumeSectionType::Summary);

    expect($result)->toHaveCount(1);
    expect($result[0])->toBe($content);
});

// --- Empty section filtering ---

test('render cv service skips sections with no selected variant', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    ResumeSection::factory()->create([
        'resume_id' => $resume->id,
        'type' => ResumeSectionType::Summary,
        'title' => 'Summary',
        'selected_variant_id' => null,
    ]);

    $service = app(RenderCvService::class);
    $yaml = $service->buildYaml($resume->fresh());

    expect($yaml)->not->toContain('Summary');
});

test('render cv service skips sections with empty content', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $section = ResumeSection::factory()->create([
        'resume_id' => $resume->id,
        'type' => ResumeSectionType::Skills,
        'title' => 'Skills',
    ]);
    $variant = ResumeSectionVariant::factory()->create([
        'resume_section_id' => $section->id,
        'content' => '   ',
    ]);
    $section->update(['selected_variant_id' => $variant->id]);

    $service = app(RenderCvService::class);
    $yaml = $service->buildYaml($resume->fresh());

    expect($yaml)->not->toContain('Skills');
});

test('preview excludes sections with no selected variant', function () {
    $resume = Resume::factory()->create(['user_id' => $this->user->id]);
    $populatedSection = ResumeSection::factory()->create([
        'resume_id' => $resume->id,
        'type' => ResumeSectionType::Summary,
        'title' => 'Summary',
        'sort_order' => 1,
    ]);
    $variant = ResumeSectionVariant::factory()->create([
        'resume_section_id' => $populatedSection->id,
        'content' => 'I am a software engineer.',
    ]);
    $populatedSection->update(['selected_variant_id' => $variant->id]);

    ResumeSection::factory()->create([
        'resume_id' => $resume->id,
        'type' => ResumeSectionType::Education,
        'title' => 'Education',
        'selected_variant_id' => null,
        'sort_order' => 2,
    ]);

    $this->actingAs($this->user)
        ->get("/resumes/{$resume->id}/preview")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('resumes/preview')
                ->has('resume.sections', 2)
        );
});
