<?php

namespace App\Services\Scrapers;

use League\HTMLToMarkdown\HtmlConverter;

class JobPostingMarkdownFormatter
{
    /**
     * Format structured job posting data into markdown content.
     *
     * @param  array{title: ?string, company: ?string, location: ?string, department: ?string, employment_type: ?string, compensation: ?string, description: ?string, sections: array<string, string>}  $data
     */
    public static function format(array $data): ?string
    {
        $lines = [];

        $title = trim($data['title'] ?? '');

        if ($title === '') {
            return null;
        }

        $lines[] = "# {$title}";
        $lines[] = '';

        $metadata = self::buildMetadata($data);

        if ($metadata !== []) {
            foreach ($metadata as $line) {
                $lines[] = $line;
            }

            $lines[] = '';
        }

        $description = self::htmlToMarkdown($data['description'] ?? '');

        if ($description !== '') {
            $lines[] = '## Description';
            $lines[] = '';
            $lines[] = $description;
            $lines[] = '';
        }

        foreach ($data['sections'] as $heading => $html) {
            $content = self::htmlToMarkdown($html);

            if ($content !== '') {
                $lines[] = "## {$heading}";
                $lines[] = '';
                $lines[] = $content;
                $lines[] = '';
            }
        }

        $result = trim(implode("\n", $lines));

        return $result !== '' ? $result : null;
    }

    /**
     * @param  array{company: ?string, location: ?string, department: ?string, employment_type: ?string, compensation: ?string}  $data
     * @return list<string>
     */
    private static function buildMetadata(array $data): array
    {
        $lines = [];

        $fields = [
            'Company' => $data['company'] ?? null,
            'Location' => $data['location'] ?? null,
            'Department' => $data['department'] ?? null,
            'Employment Type' => $data['employment_type'] ?? null,
            'Compensation' => $data['compensation'] ?? null,
        ];

        foreach ($fields as $label => $value) {
            if (filled($value)) {
                $lines[] = "**{$label}:** {$value}";
            }
        }

        return $lines;
    }

    private static function htmlToMarkdown(string $html): string
    {
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        // If it's already plain text (no HTML tags), return as-is
        if ($html === strip_tags($html)) {
            return $html;
        }

        $converter = new HtmlConverter([
            'strip_tags' => true,
            'hard_break' => true,
        ]);

        return trim($converter->convert($html));
    }
}
