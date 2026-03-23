<?php

namespace App\Services\Scrapers\AtsHandlers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GreenhouseHandler implements AtsHandler
{
    public function canHandle(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $host === 'boards.greenhouse.io';
    }

    /**
     * @return array{title: ?string, company: ?string, location: ?string, department: ?string, employment_type: ?string, compensation: ?string, description: ?string, sections: array<string, string>}|null
     */
    public function extract(string $url): ?array
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        if (! preg_match('#^/([^/]+)/jobs/(\d+)#', $path, $matches)) {
            Log::warning('Greenhouse URL did not match expected pattern', ['url' => $url]);

            return null;
        }

        $board = $matches[1];
        $jobId = $matches[2];

        try {
            $response = Http::timeout(10)
                ->get("https://boards-api.greenhouse.io/v1/boards/{$board}/jobs/{$jobId}");

            if (! $response->successful()) {
                Log::warning('Greenhouse API request failed', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $data = $response->json();

            if (! is_array($data) || ! isset($data['title'])) {
                return null;
            }

            $location = $data['location']['name'] ?? null;

            $sections = [];

            if (! empty($data['content'])) {
                $sections = $this->extractSections($data['content']);
            }

            return [
                'title' => $data['title'],
                'company' => $data['company']['name'] ?? null,
                'location' => $location,
                'department' => $this->extractDepartment($data),
                'employment_type' => null,
                'compensation' => null,
                'description' => $sections === [] ? ($data['content'] ?? null) : null,
                'sections' => $sections,
            ];
        } catch (ConnectionException $e) {
            Log::warning('Greenhouse API connection failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array<string, string>
     */
    private function extractSections(string $html): array
    {
        // Greenhouse wraps sections in: <p>&nbsp;</p><p><strong>Heading:</strong></p><content>
        // Or uses heading tags like <h2>, <h3>, etc.
        $sections = [];

        // Try splitting by heading tags first
        $parts = preg_split('#<h[2-6][^>]*>(.*?)</h[2-6]>#i', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts !== false && count($parts) > 2) {
            // First element is content before any heading (might be description)
            $preface = trim(strip_tags($parts[0]));

            if ($preface !== '') {
                $sections['About'] = $parts[0];
            }

            for ($i = 1; $i < count($parts) - 1; $i += 2) {
                $heading = trim(strip_tags($parts[$i]));
                $content = trim($parts[$i + 1]);

                if ($heading !== '' && $content !== '') {
                    $sections[rtrim($heading, ':')] = $content;
                }
            }

            if ($sections !== []) {
                return $sections;
            }
        }

        // Try splitting by bold paragraphs (common Greenhouse pattern)
        $parts = preg_split('#<p[^>]*>\s*<strong>(.*?)</strong>\s*</p>#i', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts !== false && count($parts) > 2) {
            $preface = trim(strip_tags($parts[0]));

            if ($preface !== '') {
                $sections['About'] = $parts[0];
            }

            for ($i = 1; $i < count($parts) - 1; $i += 2) {
                $heading = trim(strip_tags($parts[$i]));
                $content = trim($parts[$i + 1]);

                if ($heading !== '' && $content !== '') {
                    $sections[rtrim($heading, ':')] = $content;
                }
            }

            if ($sections !== []) {
                return $sections;
            }
        }

        return [];
    }

    private function extractDepartment(array $data): ?string
    {
        $departments = $data['departments'] ?? [];

        if ($departments === []) {
            return null;
        }

        return $departments[0]['name'] ?? null;
    }
}
