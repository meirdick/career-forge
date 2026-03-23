<?php

namespace App\Services\Scrapers\AtsHandlers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WorkdayHandler implements AtsHandler
{
    public function canHandle(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if ($host === null) {
            return false;
        }

        return str_ends_with(strtolower($host), '.myworkdayjobs.com');
    }

    /**
     * @return array{title: ?string, company: ?string, location: ?string, department: ?string, employment_type: ?string, compensation: ?string, description: ?string, sections: array<string, string>}|null
     */
    public function extract(string $url): ?array
    {
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        if ($host === null) {
            return null;
        }

        // Path: /{locale}/{site}/job/{rest}
        if (! preg_match('#^/([^/]+)/([^/]+)/job/(.+)$#', $path, $matches)) {
            Log::warning('Workday URL did not match expected pattern', ['url' => $url]);

            return null;
        }

        $locale = $matches[1];
        $site = $matches[2];
        $jobPath = $matches[3];

        $apiUrl = "https://{$host}/wday/cxs/{$locale}/{$site}/job/{$jobPath}";

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => 'Mozilla/5.0 (compatible; CareerForge/1.0)',
                ])
                ->post($apiUrl, []);

            if (! $response->successful()) {
                Log::warning('Workday API request failed', [
                    'url' => $url,
                    'api_url' => $apiUrl,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $data = $response->json();
            $jobPostingInfo = $data['jobPostingInfo'] ?? [];

            if ($jobPostingInfo === []) {
                Log::warning('Workday API returned no jobPostingInfo', ['url' => $url]);

                return null;
            }

            $sections = $this->extractSections($jobPostingInfo);

            return [
                'title' => $jobPostingInfo['title'] ?? null,
                'company' => $jobPostingInfo['company'] ?? null,
                'location' => $jobPostingInfo['location'] ?? null,
                'department' => null,
                'employment_type' => $jobPostingInfo['timeType'] ?? null,
                'compensation' => null,
                'description' => $jobPostingInfo['jobDescription'] ?? null,
                'sections' => $sections,
            ];
        } catch (ConnectionException $e) {
            Log::warning('Workday API connection failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array<string, string>
     */
    private function extractSections(array $jobPostingInfo): array
    {
        $sections = [];

        if (! empty($jobPostingInfo['jobPostingAdditionalData'])) {
            foreach ($jobPostingInfo['jobPostingAdditionalData'] as $section) {
                $label = $section['label'] ?? '';
                $content = $section['content'] ?? '';

                if ($label !== '' && $content !== '') {
                    $sections[$label] = $content;
                }
            }
        }

        if (! empty($jobPostingInfo['videoUrl'])) {
            $sections['Video'] = $jobPostingInfo['videoUrl'];
        }

        return $sections;
    }
}
