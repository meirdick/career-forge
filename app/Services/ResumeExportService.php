<?php

namespace App\Services;

use App\Models\Resume;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
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

        // Try RenderCV first
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

        $header = $this->headerService->resolveHeader($resume);
        $template = $resume->template?->value ?? 'classic';

        $styles = $this->getDocxStyles($template);

        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName($styles['font']);
        $phpWord->setDefaultFontSize($styles['bodySize']);

        $phpWord->addTitleStyle(1, ['bold' => true, 'size' => $styles['titleSize'], 'color' => $styles['headingColor']]);
        $phpWord->addTitleStyle(2, ['bold' => true, 'size' => $styles['sectionSize'], 'color' => $styles['headingColor']]);

        $section = $phpWord->addSection();

        // Contact header
        $section->addText(
            $header['name'],
            ['bold' => true, 'size' => $styles['nameSize'], 'color' => $styles['headingColor']],
            ['alignment' => Jc::CENTER]
        );

        $contactParts = array_filter([
            $header['email'],
            $header['phone'],
            $header['location'],
            $header['linkedin_url'],
            $header['portfolio_url'],
        ]);

        if (! empty($contactParts)) {
            $section->addText(
                implode(' | ', $contactParts),
                ['size' => $styles['contactSize'], 'color' => '666666'],
                ['alignment' => Jc::CENTER]
            );
        }

        $section->addTextBreak();

        foreach ($resume->sections->sortBy('sort_order') as $resumeSection) {
            $section->addTitle($resumeSection->title, 2);

            if ($resumeSection->selectedVariant) {
                $this->addMarkdownContent($section, $resumeSection->selectedVariant->content, $styles);
            }

            $section->addTextBreak();
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

    private function toDomPdf(Resume $resume): string
    {
        $template = $resume->template?->value ?? 'classic';
        $header = $this->headerService->resolveHeader($resume);

        $pdf = Pdf::loadView('resumes.pdf', [
            'resume' => $resume,
            'user' => $resume->user,
            'header' => $header,
            'template' => $template,
        ]);

        $path = 'resumes/'.$resume->id.'.pdf';
        $fullPath = storage_path('app/private/'.$path);

        if (! is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        file_put_contents($fullPath, $pdf->output());

        return $path;
    }

    private function addMarkdownContent(\PhpOffice\PhpWord\Element\Section $section, string $content, array $styles): void
    {
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                continue;
            }

            // Bullet point
            if (preg_match('/^[-*]\s+(.+)$/', $trimmed, $matches)) {
                $this->addFormattedListItem($section, $matches[1], $styles);

                continue;
            }

            // Regular text with inline formatting
            $this->addFormattedText($section, $trimmed, $styles);
        }
    }

    private function addFormattedText(\PhpOffice\PhpWord\Element\Section $section, string $text, array $styles): void
    {
        $textRun = $section->addTextRun();
        $this->parseInlineFormatting($textRun, $text, $styles);
    }

    private function addFormattedListItem(\PhpOffice\PhpWord\Element\Section $section, string $text, array $styles): void
    {
        $listItemRun = $section->addListItemRun(0);
        $this->parseInlineFormatting($listItemRun, $text, $styles);
    }

    /**
     * @param  \PhpOffice\PhpWord\Element\TextRun|\PhpOffice\PhpWord\Element\ListItemRun  $container
     */
    private function parseInlineFormatting($container, string $text, array $styles): void
    {
        // Split by markdown bold and italic markers
        $parts = preg_split('/(\*\*[^*]+\*\*|\*[^*]+\*)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        foreach ($parts as $part) {
            if (preg_match('/^\*\*(.+)\*\*$/', $part, $matches)) {
                $container->addText($matches[1], ['bold' => true]);
            } elseif (preg_match('/^\*(.+)\*$/', $part, $matches)) {
                $container->addText($matches[1], ['italic' => true]);
            } else {
                $container->addText($part);
            }
        }
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
