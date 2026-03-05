<?php

namespace App\Services;

use App\Enums\ResumeSectionType;
use App\Models\Resume;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class RenderCvService
{
    private string $rendercvPath;

    public function __construct()
    {
        $path = config('services.rendercv.path') ?? 'rendercv';

        // Resolve relative paths from the application base path
        if ($path !== 'rendercv' && ! str_starts_with($path, '/')) {
            $path = base_path($path);
        }

        $this->rendercvPath = $path;
    }

    public function isAvailable(): bool
    {
        $process = new Process([$this->rendercvPath, '--version']);
        $process->run();

        return $process->isSuccessful();
    }

    public function generatePdf(Resume $resume): string
    {
        $resume->loadMissing(['sections.selectedVariant', 'user', 'jobPosting']);

        $tempDir = sys_get_temp_dir().'/rendercv_'.uniqid();
        mkdir($tempDir, 0755, true);

        try {
            $yamlContent = $this->buildYaml($resume);
            $inputPath = $tempDir.'/input.yaml';
            file_put_contents($inputPath, $yamlContent);

            $process = new Process(
                [$this->rendercvPath, 'render', $inputPath, '--pdf-path', 'output.pdf', '--dont-generate-png', '--dont-generate-markdown', '--dont-generate-html', '--dont-generate-typst'],
                timeout: 60,
            );
            $process->run();

            if (! $process->isSuccessful()) {
                Log::error('RenderCV failed', [
                    'exit_code' => $process->getExitCode(),
                    'stderr' => $process->getErrorOutput(),
                    'stdout' => $process->getOutput(),
                ]);

                throw new \RuntimeException('RenderCV render failed: '.$process->getErrorOutput());
            }

            // Find the generated PDF
            $pdfPath = $this->findGeneratedPdf($tempDir);

            if (! $pdfPath) {
                throw new \RuntimeException('RenderCV did not produce a PDF file');
            }

            $storagePath = 'resumes/'.$resume->id.'.pdf';
            $fullPath = storage_path('app/private/'.$storagePath);

            if (! is_dir(dirname($fullPath))) {
                mkdir(dirname($fullPath), 0755, true);
            }

            copy($pdfPath, $fullPath);

            return $storagePath;
        } finally {
            $this->cleanupDirectory($tempDir);
        }
    }

    public function buildYaml(Resume $resume): string
    {
        $resume->loadMissing(['sections.selectedVariant', 'user', 'jobPosting']);

        $user = $resume->user;

        $cv = [
            'name' => $user->name ?? 'Candidate',
        ];

        if ($user->location) {
            $cv['location'] = $user->location;
        }

        if ($user->email) {
            $cv['email'] = $user->email;
        }

        if ($user->phone) {
            $cv['phone'] = $user->phone;
        }

        if ($user->portfolio_url) {
            $cv['website'] = $user->portfolio_url;
        }

        if ($user->linkedin_url) {
            $username = $this->extractLinkedInUsername($user->linkedin_url);
            if ($username) {
                $cv['social_networks'] = [
                    ['network' => 'LinkedIn', 'username' => $username],
                ];
            }
        }

        $sections = [];

        foreach ($resume->sections->sortBy('sort_order') as $section) {
            if (! $section->selectedVariant) {
                continue;
            }

            $content = $section->selectedVariant->content;
            $type = $section->type;
            $sectionKey = $section->title;

            $sections[$sectionKey] = $this->parseVariantContent($content, $type);
        }

        if (! empty($sections)) {
            $cv['sections'] = $sections;
        }

        $data = [
            'cv' => $cv,
            'design' => [
                'theme' => $resume->template?->value ?? 'classic',
            ],
        ];

        return Yaml::dump($data, 10, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
    }

    /**
     * @return array<int, mixed>
     */
    public function parseVariantContent(string $content, ?ResumeSectionType $sectionType): array
    {
        return match ($sectionType) {
            ResumeSectionType::Experience => $this->parseExperienceContent($content),
            ResumeSectionType::Education => $this->parseEducationContent($content),
            ResumeSectionType::Skills => $this->parseSkillsContent($content),
            ResumeSectionType::Summary => [$content],
            ResumeSectionType::Projects => $this->parseProjectsContent($content),
            default => $this->parseGenericContent($content),
        };
    }

    /**
     * @return array<int, array{company: string, position: string, start_date?: string, end_date?: string, location?: string, highlights?: list<string>}>
     */
    private function parseExperienceContent(string $content): array
    {
        $entries = [];
        $currentEntry = null;
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                continue;
            }

            // Match "**Company** — *Title*" or "**Company** - *Title*"
            if (preg_match('/^\*\*(.+?)\*\*\s*[\x{2014}\x{2013}\-]\s*\*(.+?)\*$/u', $trimmed, $matches)) {
                if ($currentEntry) {
                    $entries[] = $currentEntry;
                }
                $currentEntry = [
                    'company' => trim($matches[1]),
                    'position' => trim($matches[2]),
                    'highlights' => [],
                ];

                continue;
            }

            // Match date lines: "*Start – End* | Location" or "*Start – End*"
            if ($currentEntry && preg_match('/^\*(.+?)\*(?:\s*\|\s*(.+))?$/', $trimmed, $matches)) {
                $dateStr = trim($matches[1]);
                if (isset($matches[2])) {
                    $currentEntry['location'] = trim($matches[2]);
                }
                $dates = preg_split('/\s*[\x{2014}\x{2013}\-]\s*/u', $dateStr, 2);
                if (count($dates) >= 1) {
                    $currentEntry['start_date'] = trim($dates[0]);
                }
                if (count($dates) >= 2) {
                    $currentEntry['end_date'] = trim($dates[1]);
                }

                continue;
            }

            // Match bullet points
            if ($currentEntry && preg_match('/^[-*]\s+(.+)$/', $trimmed, $matches)) {
                $currentEntry['highlights'][] = $this->stripMarkdownFormatting($matches[1]);

                continue;
            }

            // If no current entry, this might be a plain-text format — start a new entry
            if (! $currentEntry && $trimmed !== '') {
                $currentEntry = [
                    'company' => $trimmed,
                    'position' => '',
                    'highlights' => [],
                ];
            }
        }

        if ($currentEntry) {
            $entries[] = $currentEntry;
        }

        // If no structured entries were found, return as generic text
        if (empty($entries)) {
            return [$content];
        }

        return $entries;
    }

    /**
     * @return array<int, array{institution: string, area: string, start_date?: string, end_date?: string, highlights?: list<string>}>
     */
    private function parseEducationContent(string $content): array
    {
        $entries = [];
        $currentEntry = null;
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                continue;
            }

            // Match "**Degree** — *Institution*"
            if (preg_match('/^\*\*(.+?)\*\*\s*[\x{2014}\x{2013}\-]\s*\*(.+?)\*$/u', $trimmed, $matches)) {
                if ($currentEntry) {
                    $entries[] = $currentEntry;
                }
                $currentEntry = [
                    'institution' => trim($matches[2]),
                    'area' => trim($matches[1]),
                    'highlights' => [],
                ];

                continue;
            }

            // Match date lines
            if ($currentEntry && preg_match('/^\*(.+?)\*$/', $trimmed, $matches)) {
                $dateStr = trim($matches[1]);
                $dates = preg_split('/\s*[\x{2014}\x{2013}\-]\s*/u', $dateStr, 2);
                if (count($dates) >= 1) {
                    $currentEntry['start_date'] = trim($dates[0]);
                }
                if (count($dates) >= 2) {
                    $currentEntry['end_date'] = trim($dates[1]);
                }

                continue;
            }

            // Match bullet points
            if ($currentEntry && preg_match('/^[-*]\s+(.+)$/', $trimmed, $matches)) {
                $currentEntry['highlights'][] = $this->stripMarkdownFormatting($matches[1]);

                continue;
            }
        }

        if ($currentEntry) {
            $entries[] = $currentEntry;
        }

        if (empty($entries)) {
            return [$content];
        }

        return $entries;
    }

    /**
     * @return list<string>
     */
    private function parseSkillsContent(string $content): array
    {
        $lines = explode("\n", $content);
        $skills = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            // Strip markdown formatting: "**Category:** Skill1, Skill2" → "Category: Skill1, Skill2"
            $cleaned = $this->stripMarkdownFormatting($trimmed);

            // Remove bullet prefix
            $cleaned = preg_replace('/^[-*]\s+/', '', $cleaned);

            if ($cleaned !== '') {
                $skills[] = $cleaned;
            }
        }

        return $skills ?: [$content];
    }

    /**
     * @return array<int, array{name: string, highlights?: list<string>}|string>
     */
    private function parseProjectsContent(string $content): array
    {
        $entries = [];
        $currentEntry = null;
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                continue;
            }

            // Match "**Project Name**"
            if (preg_match('/^\*\*(.+?)\*\*$/', $trimmed, $matches)) {
                if ($currentEntry) {
                    $entries[] = $currentEntry;
                }
                $currentEntry = [
                    'name' => trim($matches[1]),
                    'highlights' => [],
                ];

                continue;
            }

            // Match bullet points
            if ($currentEntry && preg_match('/^[-*]\s+(.+)$/', $trimmed, $matches)) {
                $currentEntry['highlights'][] = $this->stripMarkdownFormatting($matches[1]);

                continue;
            }

            // Description text for current project
            if ($currentEntry && ! str_starts_with($trimmed, '**')) {
                $currentEntry['highlights'][] = $this->stripMarkdownFormatting($trimmed);
            }
        }

        if ($currentEntry) {
            $entries[] = $currentEntry;
        }

        if (empty($entries)) {
            return [$content];
        }

        return $entries;
    }

    /**
     * @return list<string>
     */
    private function parseGenericContent(string $content): array
    {
        $lines = explode("\n", $content);
        $result = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '') {
                $cleaned = preg_replace('/^[-*]\s+/', '', $trimmed);
                $result[] = $this->stripMarkdownFormatting($cleaned);
            }
        }

        return $result ?: [$content];
    }

    private function stripMarkdownFormatting(string $text): string
    {
        // Remove bold
        $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text);
        // Remove italic
        $text = preg_replace('/\*(.+?)\*/', '$1', $text);

        return $text;
    }

    private function extractLinkedInUsername(string $url): ?string
    {
        if (preg_match('#linkedin\.com/in/([^/?]+)#', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function findGeneratedPdf(string $dir): ?string
    {
        // Check the direct output path first
        $directPdf = $dir.'/output.pdf';
        if (file_exists($directPdf)) {
            return $directPdf;
        }

        // RenderCV creates output in a rendercv_output subdirectory
        $outputDir = $dir.'/rendercv_output';
        if (is_dir($outputDir)) {
            $files = glob($outputDir.'/*.pdf');
            if (! empty($files)) {
                return $files[0];
            }
        }

        // Scan all subdirectories
        $files = glob($dir.'/**/*.pdf') ?: glob($dir.'/*/*.pdf');
        if (! empty($files)) {
            return $files[0];
        }

        return null;
    }

    private function cleanupDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($dir);
    }
}
