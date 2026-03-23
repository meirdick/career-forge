<?php

namespace App\Services\Scrapers\AtsHandlers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LeverHandler implements AtsHandler
{
    public function canHandle(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $host === 'jobs.lever.co';
    }

    /**
     * @return array{title: ?string, company: ?string, location: ?string, department: ?string, employment_type: ?string, compensation: ?string, description: ?string, sections: array<string, string>}|null
     */
    public function extract(string $url): ?array
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        if (! preg_match('#^/([^/]+)/([a-f0-9-]+)#', $path, $matches)) {
            Log::warning('Lever URL did not match expected pattern', ['url' => $url]);

            return null;
        }

        $company = $matches[1];
        $postingId = $matches[2];

        try {
            $response = Http::timeout(10)
                ->get("https://api.lever.co/v0/postings/{$company}/{$postingId}");

            if (! $response->successful()) {
                Log::warning('Lever API request failed', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $data = $response->json();

            if (! is_array($data) || ! isset($data['text'])) {
                return null;
            }

            $sections = [];
            $description = $data['descriptionPlain'] ?? ($data['description'] ?? null);

            foreach ($data['lists'] ?? [] as $list) {
                $heading = $list['text'] ?? '';
                $content = $list['content'] ?? '';

                if ($heading !== '' && $content !== '') {
                    $sections[$heading] = $content;
                }
            }

            if (! empty($data['additional'])) {
                $sections['Additional Information'] = $data['additional'];
            }

            $compensation = $this->extractCompensation($data);

            return [
                'title' => $data['text'],
                'company' => $data['categories']['company'] ?? $company,
                'location' => $data['categories']['location'] ?? null,
                'department' => $data['categories']['department'] ?? null,
                'employment_type' => $data['categories']['commitment'] ?? null,
                'compensation' => $compensation,
                'description' => $description,
                'sections' => $sections,
            ];
        } catch (ConnectionException $e) {
            Log::warning('Lever API connection failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function extractCompensation(array $data): ?string
    {
        $range = $data['salaryRange'] ?? null;

        if ($range === null) {
            return null;
        }

        $min = $range['min'] ?? null;
        $max = $range['max'] ?? null;
        $currency = $range['currency'] ?? 'USD';
        $interval = $range['interval'] ?? 'per year';

        if ($min === null && $max === null) {
            return null;
        }

        if ($min !== null && $max !== null) {
            return "{$currency} {$min} - {$max} {$interval}";
        }

        return "{$currency} ".($min ?? $max)." {$interval}";
    }
}
