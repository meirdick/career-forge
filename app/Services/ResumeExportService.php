<?php

namespace App\Services;

use App\Models\Resume;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class ResumeExportService
{
    public function toPdf(Resume $resume): string
    {
        $resume->load(['sections.selectedVariant', 'jobPosting']);

        $pdf = Pdf::loadView('resumes.pdf', [
            'resume' => $resume,
        ]);

        $path = 'resumes/'.$resume->id.'.pdf';
        $fullPath = storage_path('app/private/'.$path);

        if (! is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        file_put_contents($fullPath, $pdf->output());

        return $path;
    }

    public function toDocx(Resume $resume): string
    {
        $resume->load(['sections.selectedVariant', 'jobPosting']);

        $phpWord = new PhpWord;

        $phpWord->addTitleStyle(1, ['bold' => true, 'size' => 20]);
        $phpWord->addTitleStyle(2, ['bold' => true, 'size' => 14]);

        $section = $phpWord->addSection();

        $section->addTitle($resume->title, 1);

        foreach ($resume->sections->sortBy('sort_order') as $resumeSection) {
            $section->addTitle($resumeSection->title, 2);

            if ($resumeSection->selectedVariant) {
                $content = $resumeSection->selectedVariant->content;
                foreach (explode("\n", $content) as $line) {
                    $trimmed = trim($line);
                    if ($trimmed !== '') {
                        $section->addText($trimmed);
                    }
                }
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
}
