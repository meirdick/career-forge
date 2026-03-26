<?php

namespace App\Services;

use App\Ai\Agents\ContentTrimmer;
use App\Enums\ResumeSectionType;
use App\Models\Application;
use App\Models\Resume;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\Element\ListItemRun;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class ResumeExportService
{
    public function __construct(
        private RenderCvService $renderCvService,
        private ResumeHeaderService $headerService,
    ) {}

    public function toPdf(Resume $resume): string
    {
        $resume->load(['sections.selectedVariant', 'user', 'jobPosting']);

        // Try Worker-based polisher first (best quality)
        if ($this->workerIsAvailable()) {
            try {
                return $this->toWorkerExport($resume, 'pdf');
            } catch (\Throwable $e) {
                Log::warning('Worker PDF failed, falling back', ['error' => $e->getMessage()]);
            }
        }

        // Try RenderCV second
        if ($this->renderCvService->isAvailable()) {
            try {
                return $this->renderCvService->generatePdf($resume);
            } catch (\Throwable $e) {
                Log::warning('RenderCV failed, falling back to DomPDF', ['error' => $e->getMessage()]);
            }
        }

        return $this->toDomPdf($resume);
    }

    public function toDocx(Resume $resume): string
    {
        $resume->load(['sections.selectedVariant', 'user.professionalIdentity', 'jobPosting']);

        // Try Worker-based polisher first (best quality)
        if ($this->workerIsAvailable()) {
            try {
                return $this->toWorkerExport($resume, 'docx');
            } catch (\Throwable $e) {
                Log::warning('Worker DOCX failed, falling back to PhpWord', ['error' => $e->getMessage()]);
            }
        }

        $header = $this->headerService->resolveHeader($resume);
        $template = $resume->template?->value ?? 'classic';

        $styles = $this->getDocxStyles($template);

        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName($styles['font']);
        $phpWord->setDefaultFontSize($styles['bodySize']);

        // Set OOXML compatibility to Word 2013+
        $phpWord->getCompatibility()->setOoxmlVersion(15);

        // Set document properties
        $docInfo = $phpWord->getDocInfo();
        $docInfo->setCreator('CareerForge');
        $docInfo->setTitle($this->sanitizeForXml($header['name'] ?? 'Resume'));

        $phpWord->addTitleStyle(1, [
            'bold' => true,
            'size' => $styles['titleSize'],
            'color' => $styles['headingColor'],
        ], [
            'keepNext' => true,
        ]);
        $phpWord->addTitleStyle(2, [
            'bold' => true,
            'size' => $styles['sectionSize'],
            'color' => $styles['headingColor'],
        ], [
            'keepNext' => true,
            'borderBottomSize' => 6,
            'borderBottomColor' => $styles['headingColor'],
            'spaceAfter' => 60,
        ]);

        $section = $phpWord->addSection([
            'pageSizeW' => 12240,
            'pageSizeH' => 15840,
            'marginTop' => 720,
            'marginBottom' => 720,
            'marginLeft' => 1440,
            'marginRight' => 1440,
        ]);

        // Contact header
        $section->addText(
            $this->sanitizeForXml($header['name']),
            ['bold' => true, 'size' => $styles['nameSize'], 'color' => $styles['headingColor']],
            ['alignment' => Jc::CENTER]
        );

        $contactParts = array_filter([
            $header['email'],
            $header['phone'],
            $header['location'],
            $header['linkedin_url'],
            ...array_map(fn ($link) => $link['label'], $header['portfolio_links']),
        ]);

        if (! empty($contactParts)) {
            $section->addText(
                $this->sanitizeForXml(implode(' | ', $contactParts)),
                ['size' => $styles['contactSize'], 'color' => '666666'],
                ['alignment' => Jc::CENTER]
            );
        }

        $section->addTextBreak();

        foreach ($resume->sections->where('is_hidden', false)->sortBy('sort_order') as $resumeSection) {
            if (! $resumeSection->selectedVariant || trim($resumeSection->selectedVariant->content) === '') {
                continue;
            }

            $section->addTitle($this->sanitizeForXml($resumeSection->title), 2);

            $variant = $resumeSection->selectedVariant;
            $content = ($resumeSection->display_mode === 'compact' && $variant->compact_content)
                ? $variant->compact_content
                : $variant->content;
            $this->addMarkdownContent($section, $content, $styles);

            $section->addTextBreak();
        }

        if ($resume->show_transparency && $resume->transparency_text) {
            $section->addTextBreak();
            $section->addText(
                $this->sanitizeForXml($resume->transparency_text),
                ['size' => 8, 'color' => '999999', 'italic' => true],
                ['alignment' => Jc::CENTER]
            );
        }

        $path = 'resumes/'.$resume->id.'.docx';
        $fullPath = storage_path('app/private/'.$path);

        if (! is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($fullPath);

        return $path;
    }

    private function workerIsAvailable(): bool
    {
        return config('services.resume_worker.enabled')
            && filled(config('services.resume_worker.url'))
            && filled(config('services.resume_worker.secret'));
    }

    private function toWorkerExport(Resume $resume, string $format): string
    {
        $pageLimit = $resume->page_limit ?? 1;
        $template = $resume->template?->value ?? 'classic';
        $header = $this->headerService->resolveHeader($resume);

        $html = view('resumes.pdf', [
            'resume' => $resume,
            'user' => $resume->user,
            'header' => $header,
            'template' => $template,
            'fontSizeAdjust' => 0,
            'spacingAdjust' => 0,
            'marginAdjust' => 0,
            'sectionOverrides' => [],
            'contentOverrides' => [],
            'hiddenSections' => [],
        ])->render();

        $sections = $resume->sections
            ->where('is_hidden', false)
            ->sortBy('sort_order')
            ->filter(fn ($s) => $s->selectedVariant && trim($s->selectedVariant->content) !== '')
            ->map(fn ($s) => [
                'id' => $s->id,
                'title' => $s->title,
                'type' => $s->type?->value ?? 'custom',
                'content' => $s->selectedVariant->content,
                'compactContent' => $s->selectedVariant->compact_content,
                'cssSelector' => "[data-section-id='{$s->id}']",
            ])
            ->values()
            ->all();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.resume_worker.secret'),
        ])
            ->timeout(config('services.resume_worker.timeout'))
            ->post(config('services.resume_worker.url').'/polish', [
                'html' => $html,
                'sections' => $sections,
                'pageLimit' => $pageLimit,
                'format' => $format,
                'paper' => ['width' => '8.5in', 'height' => '11in'],
                'meta' => [
                    'resumeId' => $resume->id,
                    'template' => $template,
                    'jobTitle' => $resume->jobPosting?->title,
                    'company' => $resume->jobPosting?->company,
                    'requiredSkills' => $resume->jobPosting?->idealCandidateProfile
                        ?->required_skills
                        ? collect($resume->jobPosting->idealCandidateProfile->required_skills)
                            ->pluck('name')
                            ->take(10)
                            ->all()
                        : [],
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Worker returned '.$response->status().': '.$response->body());
        }

        $extension = $format === 'docx' ? 'docx' : 'pdf';
        $path = "resumes/{$resume->id}.{$extension}";
        $fullPath = storage_path('app/private/'.$path);

        if (! is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        file_put_contents($fullPath, $response->body());

        return $path;
    }

    private function toDomPdf(Resume $resume): string
    {
        $pageLimit = $resume->page_limit ?? 1;
        $template = $resume->template?->value ?? 'classic';
        $header = $this->headerService->resolveHeader($resume);

        $adjustments = [
            'fontSizeAdjust' => 0,
            'spacingAdjust' => 0,
            'marginAdjust' => 0,
        ];

        $sectionOverrides = [];
        $contentOverrides = [];
        $hiddenSections = [];
        $llmTrimAttempted = false;

        $output = null;
        $pageCount = 0;

        for ($iteration = 0; $iteration < 6; $iteration++) {
            $pdf = Pdf::loadView('resumes.pdf', [
                'resume' => $resume,
                'user' => $resume->user,
                'header' => $header,
                'template' => $template,
                'fontSizeAdjust' => $adjustments['fontSizeAdjust'],
                'spacingAdjust' => $adjustments['spacingAdjust'],
                'marginAdjust' => $adjustments['marginAdjust'],
                'sectionOverrides' => $sectionOverrides,
                'contentOverrides' => $contentOverrides,
                'hiddenSections' => $hiddenSections,
            ])->setPaper('letter');

            $output = $pdf->output();
            $pageCount = $pdf->getDomPDF()->getCanvas()->get_page_count();

            if ($pageCount <= $pageLimit) {
                break;
            }

            // Step 1: Switch sections to compact mode
            if ($iteration === 0) {
                foreach ($resume->sections as $section) {
                    if ($section->selectedVariant?->compact_content && $section->display_mode !== 'compact') {
                        $sectionOverrides[$section->id] = 'compact';
                    }
                }

                continue;
            }

            // Step 2: Reduce font size and spacing
            if ($iteration === 1) {
                $adjustments['fontSizeAdjust'] = -0.5;
                $adjustments['spacingAdjust'] = -2;

                continue;
            }

            // Step 3: LLM content trimming (one attempt only)
            if ($iteration === 2 && ! $llmTrimAttempted) {
                $llmTrimAttempted = true;

                // Undo compact overrides so LLM trims from full content
                $sectionOverrides = [];
                $contentOverrides = $this->trimContentWithLlm($resume, $pageCount, $pageLimit);

                if (! empty($contentOverrides)) {
                    continue;
                }
            }

            // Step 4: Hide lowest-priority sections
            if ($iteration === 3) {
                $hidePriority = [
                    ResumeSectionType::Projects,
                    ResumeSectionType::Certifications,
                    ResumeSectionType::Publications,
                ];

                foreach ($hidePriority as $type) {
                    foreach ($resume->sections as $section) {
                        if ($section->type === $type && ! $section->is_hidden) {
                            $hiddenSections[] = $section->id;
                        }
                    }
                }

                continue;
            }

            // Step 5: Reduce margins
            if ($iteration === 4) {
                $adjustments['marginAdjust'] = -0.05;

                continue;
            }
        }

        if ($pageCount > $pageLimit) {
            Log::warning('Resume PDF exceeds page limit after fitting', [
                'resume_id' => $resume->id,
                'page_count' => $pageCount,
                'page_limit' => $pageLimit,
            ]);
        }

        $path = 'resumes/'.$resume->id.'.pdf';
        $fullPath = storage_path('app/private/'.$path);

        if (! is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        file_put_contents($fullPath, $output);

        return $path;
    }

    public function coverLetterToPdf(Application $application): string
    {
        $application->loadMissing(['user', 'resume']);

        $header = $application->resume
            ? $this->headerService->resolveHeader($application->resume)
            : $this->buildBasicHeader($application->user);

        $pdf = Pdf::loadView('cover-letters.pdf', [
            'application' => $application,
            'header' => $header,
            'coverLetter' => $application->cover_letter,
        ]);

        $path = 'cover-letters/'.$application->id.'.pdf';
        $fullPath = storage_path('app/private/'.$path);

        if (! is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        file_put_contents($fullPath, $pdf->output());

        return $path;
    }

    public function coverLetterToDocx(Application $application): string
    {
        $application->loadMissing(['user.professionalIdentity', 'resume']);

        $header = $application->resume
            ? $this->headerService->resolveHeader($application->resume)
            : $this->buildBasicHeader($application->user);

        $template = $application->resume?->template?->value ?? 'classic';
        $styles = $this->getDocxStyles($template);

        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName($styles['font']);
        $phpWord->setDefaultFontSize($styles['bodySize']);

        $section = $phpWord->addSection([
            'pageSizeW' => 12240,
            'pageSizeH' => 15840,
            'marginTop' => 1440,
            'marginBottom' => 1440,
            'marginLeft' => 1440,
            'marginRight' => 1440,
        ]);

        // Contact header
        $section->addText(
            $this->sanitizeForXml($header['name']),
            ['bold' => true, 'size' => $styles['nameSize'], 'color' => $styles['headingColor']],
            ['alignment' => Jc::CENTER]
        );

        $contactParts = array_filter([
            $header['email'],
            $header['phone'],
            $header['location'],
            $header['linkedin_url'] ?? null,
            ...array_map(fn ($link) => $link['label'], $header['portfolio_links'] ?? []),
        ]);

        if (! empty($contactParts)) {
            $section->addText(
                $this->sanitizeForXml(implode(' | ', $contactParts)),
                ['size' => $styles['contactSize'], 'color' => '666666'],
                ['alignment' => Jc::CENTER]
            );
        }

        $section->addTextBreak(2);

        // Cover letter body
        $this->addMarkdownContent($section, $application->cover_letter, $styles);

        $path = 'cover-letters/'.$application->id.'.docx';
        $fullPath = storage_path('app/private/'.$path);

        if (! is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($fullPath);

        return $path;
    }

    /**
     * Ask the LLM to selectively trim section content to reduce page count.
     *
     * @return array<int, string> Section ID => trimmed content
     */
    private function trimContentWithLlm(Resume $resume, int $currentPages, int $targetPages): array
    {
        $sectionsToTrim = [];

        foreach ($resume->sections->where('is_hidden', false)->sortByDesc('sort_order') as $section) {
            if (! $section->selectedVariant || trim($section->selectedVariant->content) === '') {
                continue;
            }

            $content = $section->selectedVariant->content;

            // Only trim sections with substantial content (> 3 lines)
            if (substr_count($content, "\n") < 3) {
                continue;
            }

            $sectionsToTrim[] = [
                'section_id' => $section->id,
                'title' => $section->title,
                'type' => $section->type?->value ?? 'unknown',
                'content' => $content,
            ];
        }

        if (empty($sectionsToTrim)) {
            return [];
        }

        $overflowPages = $currentPages - $targetPages;
        $reductionPercent = min(40, $overflowPages * 25);

        $sectionList = collect($sectionsToTrim)->map(function (array $s) {
            return "### Section: {$s['title']} (ID: {$s['section_id']}, type: {$s['type']})\n{$s['content']}";
        })->implode("\n\n---\n\n");

        $prompt = "The resume below overflows by {$overflowPages} page(s). "
            ."Reduce the total content by approximately {$reductionPercent}%. "
            .'Remove the weakest bullet points, shorten verbose descriptions, and condense where possible. '
            .'Prioritize keeping the most impactful and relevant information. '
            ."Return each section with its section_id and the trimmed content.\n\n"
            .$sectionList;

        try {
            $response = (new ContentTrimmer)->prompt($prompt);
            $trimmedSections = $response['sections'] ?? [];

            $overrides = [];
            foreach ($trimmedSections as $trimmed) {
                $sectionId = $trimmed['section_id'] ?? null;
                $content = $trimmed['content'] ?? '';

                if ($sectionId && $content !== '') {
                    $overrides[(int) $sectionId] = $content;
                }
            }

            return $overrides;
        } catch (\Throwable $e) {
            Log::warning('LLM content trimming failed, skipping', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return array{name: string, email: ?string, phone: ?string, location: ?string, linkedin_url: ?string, portfolio_links: array<int, array{url: string, label: string}>}
     */
    private function buildBasicHeader(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? null,
            'location' => $user->location ?? null,
            'linkedin_url' => $user->linkedin_url ?? null,
            'portfolio_links' => [],
        ];
    }

    private function addMarkdownContent(Section $section, string $content, array $styles): void
    {
        $content = str_replace(['\\n', '\\r'], ["\n", "\r"], $content);
        $content = $this->joinCrossLineFormatting($content);
        $content = $this->sanitizeForXml($content);
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                continue;
            }

            // Horizontal rule
            if (preg_match('/^[-*_]{3,}$/', $trimmed)) {
                $section->addText('', [], ['borderBottomSize' => 2, 'borderBottomColor' => 'CCCCCC', 'spaceAfter' => 120]);

                continue;
            }

            // Heading lines (### Heading)
            if (preg_match('/^(#{1,6})\s+(.+)$/', $trimmed, $matches)) {
                $headingText = $this->stripInlineFormatting($this->stripMarkdownLinks($matches[2]));
                $section->addTitle($headingText, 2);

                continue;
            }

            // Nested bullet point (2+ leading spaces)
            if (preg_match('/^(\s{2,})[-*]\s+(.+)$/', $line, $matches)) {
                $depth = min((int) floor(strlen($matches[1]) / 2), 3);
                $this->addFormattedListItem($section, $this->stripMarkdownLinks($matches[2]), $styles, $depth);

                continue;
            }

            // Top-level bullet point
            if (preg_match('/^[-*]\s+(.+)$/', $trimmed, $matches)) {
                $this->addFormattedListItem($section, $this->stripMarkdownLinks($matches[1]), $styles);

                continue;
            }

            // Numbered list (1. item)
            if (preg_match('/^\d+\.\s+(.+)$/', $trimmed, $matches)) {
                $this->addFormattedListItem($section, $this->stripMarkdownLinks($matches[1]), $styles);

                continue;
            }

            // Regular text with inline formatting
            $this->addFormattedText($section, $this->stripMarkdownLinks($trimmed), $styles);
        }
    }

    private function addFormattedText(Section $section, string $text, array $styles): void
    {
        $textRun = $section->addTextRun([]);
        $this->parseInlineFormatting($textRun, $text, $styles);
    }

    private function addFormattedListItem(Section $section, string $text, array $styles, int $depth = 0): void
    {
        $listItemRun = $section->addListItemRun($depth, null, ['keepLines' => true]);
        $this->parseInlineFormatting($listItemRun, $text, $styles);
    }

    /**
     * @param  TextRun|ListItemRun  $container
     */
    private function parseInlineFormatting($container, string $text, array $styles): void
    {
        // Split by markdown bold and italic markers
        $parts = preg_split('/(\*\*[^*]+\*\*|\*[^*]+\*)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        if ($parts === false) {
            $container->addText($text, ['size' => $styles['bodySize']]);

            return;
        }

        foreach ($parts as $part) {
            if (preg_match('/^\*\*(.+)\*\*$/', $part, $matches)) {
                $container->addText($matches[1], ['bold' => true, 'size' => $styles['bodySize']]);
            } elseif (preg_match('/^\*(.+)\*$/', $part, $matches)) {
                $container->addText($matches[1], ['italic' => true, 'size' => $styles['bodySize']]);
            } else {
                $container->addText($part, ['size' => $styles['bodySize']]);
            }
        }
    }

    /**
     * Join bold/italic markers that span across line breaks back into single lines.
     */
    private function joinCrossLineFormatting(string $text): string
    {
        // Join **bold that spans\nmultiple lines** back into one line
        $text = preg_replace_callback('/\*\*([^*]*?\n[^*]*?)\*\*/', function ($matches) {
            return '**'.str_replace("\n", ' ', $matches[1]).'**';
        }, $text) ?? $text;

        // Join *italic that spans\nmultiple lines* back into one line
        return preg_replace_callback('/(?<!\*)\*(?!\*)([^*]*?\n[^*]*?)\*(?!\*)/', function ($matches) {
            return '*'.str_replace("\n", ' ', $matches[1]).'*';
        }, $text) ?? $text;
    }

    /**
     * Strip characters that are invalid in XML 1.0.
     */
    private function sanitizeForXml(string $text): string
    {
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text) ?? $text;
    }

    /**
     * Convert markdown link syntax to plain text.
     */
    private function stripMarkdownLinks(string $text): string
    {
        return preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text) ?? $text;
    }

    /**
     * Remove inline bold/italic markers from text.
     */
    private function stripInlineFormatting(string $text): string
    {
        $text = preg_replace('/\*\*([^*]+)\*\*/', '$1', $text) ?? $text;

        return preg_replace('/\*([^*]+)\*/', '$1', $text) ?? $text;
    }

    /**
     * @return array{font: string, bodySize: int, titleSize: int, nameSize: int, sectionSize: int, contactSize: int, headingColor: string}
     */
    private function getDocxStyles(string $template): array
    {
        return match ($template) {
            'moderncv' => [
                'font' => 'Calibri',
                'bodySize' => 10,
                'titleSize' => 20,
                'nameSize' => 24,
                'sectionSize' => 13,
                'contactSize' => 9,
                'headingColor' => '2E74B5',
            ],
            'engineeringresumes', 'engineeringclassic' => [
                'font' => 'Times New Roman',
                'bodySize' => 10,
                'titleSize' => 18,
                'nameSize' => 22,
                'sectionSize' => 12,
                'contactSize' => 9,
                'headingColor' => '000000',
            ],
            default => [
                'font' => 'Calibri',
                'bodySize' => 11,
                'titleSize' => 20,
                'nameSize' => 24,
                'sectionSize' => 14,
                'contactSize' => 9,
                'headingColor' => '333333',
            ],
        };
    }
}
