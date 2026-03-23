<?php

namespace App\Services\Scrapers\AtsHandlers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AshbyHandler implements AtsHandler
{
    public function canHandle(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $host === 'jobs.ashbyhq.com';
    }

    /**
     * @return array{title: ?string, company: ?string, location: ?string, department: ?string, employment_type: ?string, compensation: ?string, description: ?string, sections: array<string, string>}|null
     */
    public function extract(string $url): ?array
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        if (! preg_match('#^/([^/]+)/([a-f0-9-]+)#', $path, $matches)) {
            Log::warning('Ashby URL did not match expected pattern', ['url' => $url]);

            return null;
        }

        $company = $matches[1];
        $postingId = $matches[2];

        try {
            $response = Http::timeout(10)
                ->post("https://api.ashbyhq.com/posting-api/job-board/{$company}", [
                    'operationName' => 'ApiJobBoardWithTeams',
                ]);

            if (! $response->successful()) {
                Log::warning('Ashby API request failed', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $data = $response->json();
            $job = $this->findJob($data, $postingId);

            if ($job === null) {
                Log::warning('Ashby job not found in API response', [
                    'url' => $url,
                    'posting_id' => $postingId,
                ]);

                return null;
            }

            return [
                'title' => $job['title'] ?? null,
                'company' => $data['organizationName'] ?? $company,
                'location' => $job['location'] ?? null,
                'department' => $job['departmentName'] ?? ($job['department'] ?? null),
                'employment_type' => $job['employmentType'] ?? null,
                'compensation' => $this->extractCompensation($job),
                'description' => $job['descriptionHtml'] ?? ($job['description'] ?? null),
                'sections' => [],
            ];
        } catch (ConnectionException $e) {
            Log::warning('Ashby API connection failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function findJob(array $data, string $postingId): ?array
    {
        $jobs = $data['jobs'] ?? [];

        foreach ($jobs as $job) {
            $jobId = $job['id'] ?? '';
            $externalLink = $job['externalLink'] ?? '';

            if ($jobId === $postingId || str_contains($externalLink, $postingId)) {
                return $job;
            }
        }

        return null;
    }

    private function extractCompensation(array $job): ?string
    {
        $comp = $job['compensation'] ?? ($job['compensationTierSummary'] ?? null);

        if (is_string($comp) && $comp !== '') {
            return $comp;
        }

        return null;
    }
}
