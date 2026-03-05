<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class DocumentExtractorService
{
    public function extract(UploadedFile|string $file): string
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $extension = $file instanceof UploadedFile ? $file->getClientOriginalExtension() : pathinfo($file, PATHINFO_EXTENSION);

        return match (strtolower($extension)) {
            'pdf' => $this->extractPdf($path),
            'docx', 'doc' => $this->extractDocx($path),
            'txt' => file_get_contents($path),
            'json' => $this->extractJson($path),
            default => throw new \InvalidArgumentException("Unsupported file type: {$extension}"),
        };
    }

    protected function extractPdf(string $path): string
    {
        $parser = new PdfParser;
        $pdf = $parser->parseFile($path);

        return $pdf->getText();
    }

    protected function extractDocx(string $path): string
    {
        $phpWord = IOFactory::load($path);
        $text = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $text .= $this->extractElementText($element)."\n";
            }
        }

        return trim($text);
    }

    protected function extractElementText(mixed $element): string
    {
        if (method_exists($element, 'getText') && is_string($element->getText())) {
            return $element->getText();
        }

        if (method_exists($element, 'getElements')) {
            $parts = [];
            foreach ($element->getElements() as $child) {
                $parts[] = $this->extractElementText($child);
            }

            return implode('', $parts);
        }

        return '';
    }

    protected function extractJson(string $path): string
    {
        $raw = file_get_contents($path);
        $data = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON file: '.json_last_error_msg());
        }

        return $this->linkedInJsonToText($data);
    }

    protected function linkedInJsonToText(array $data): string
    {
        $lines = [];

        if (isset($data['basics'])) {
            $basics = $data['basics'];
            if (isset($basics['name'])) {
                $lines[] = $basics['name'];
            }
            if (isset($basics['label'])) {
                $lines[] = $basics['label'];
            }
            if (isset($basics['email'])) {
                $lines[] = 'Email: '.$basics['email'];
            }
            if (isset($basics['summary'])) {
                $lines[] = "\nSummary:\n".$basics['summary'];
            }
        }

        $positions = $data['positions'] ?? $data['work'] ?? $data['experience'] ?? [];
        if (! empty($positions)) {
            $lines[] = "\nExperience:";
            foreach ($positions as $position) {
                $company = $position['companyName'] ?? $position['company'] ?? $position['name'] ?? '';
                $title = $position['title'] ?? $position['position'] ?? '';
                $start = $position['startDate'] ?? $position['start'] ?? '';
                $end = $position['endDate'] ?? $position['end'] ?? 'Present';
                $desc = $position['description'] ?? $position['summary'] ?? '';

                $lines[] = "{$title} at {$company} ({$start} - {$end})";
                if ($desc) {
                    $lines[] = $desc;
                }
            }
        }

        $education = $data['education'] ?? $data['educations'] ?? [];
        if (! empty($education)) {
            $lines[] = "\nEducation:";
            foreach ($education as $edu) {
                $school = $edu['schoolName'] ?? $edu['institution'] ?? '';
                $degree = $edu['degreeName'] ?? $edu['studyType'] ?? $edu['degree'] ?? '';
                $field = $edu['fieldOfStudy'] ?? $edu['area'] ?? '';
                $lines[] = "{$degree} in {$field} - {$school}";
            }
        }

        $skills = $data['skills'] ?? [];
        if (! empty($skills)) {
            $lines[] = "\nSkills:";
            $skillNames = [];
            foreach ($skills as $skill) {
                $skillNames[] = is_string($skill) ? $skill : ($skill['name'] ?? '');
            }
            $lines[] = implode(', ', array_filter($skillNames));
        }

        $certifications = $data['certifications'] ?? [];
        if (! empty($certifications)) {
            $lines[] = "\nCertifications:";
            foreach ($certifications as $cert) {
                $name = $cert['name'] ?? $cert['title'] ?? '';
                $authority = $cert['authority'] ?? $cert['issuer'] ?? '';
                $lines[] = "{$name} - {$authority}";
            }
        }

        $projects = $data['projects'] ?? [];
        if (! empty($projects)) {
            $lines[] = "\nProjects:";
            foreach ($projects as $project) {
                $name = $project['name'] ?? $project['title'] ?? '';
                $desc = $project['description'] ?? '';
                $lines[] = $name;
                if ($desc) {
                    $lines[] = $desc;
                }
            }
        }

        return implode("\n", $lines);
    }
}
