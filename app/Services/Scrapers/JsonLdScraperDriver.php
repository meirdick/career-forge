<?php

namespace App\Services\Scrapers;

use App\Contracts\WebScraperContract;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JsonLdScraperDriver implements WebScraperContract
{
    public function isConfigured(): bool
    {
        return true;
    }

    /**
     * @return list<array{url: string}>|null
     */
    public function discoverLinks(string $url): ?array
    {
        return null;
    }

    public function scrape(string $url): ?string
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'text/html',
                    'User-Agent' => 'Mozilla/5.0 (compatible; CareerForge/1.0)',
                ])
                ->get($url);

            if (! $response->successful()) {
                Log::info('JSON-LD scraper HTTP request failed', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $html = $response->body();
            $jobPosting = $this->extractJobPosting($html);

            if ($jobPosting === null) {
                return null;
            }

            return JobPostingMarkdownFormatter::format([
                'title' => $jobPosting['title'] ?? ($jobPosting['name'] ?? null),
                'company' => $this->extractCompanyName($jobPosting),
                'location' => $this->extractLocation($jobPosting),
                'department' => $jobPosting['department'] ?? ($jobPosting['occupationalCategory'] ?? null),
                'employment_type' => $this->normalizeEmploymentType($jobPosting['employmentType'] ?? null),
                'compensation' => $this->extractCompensation($jobPosting),
                'description' => $jobPosting['description'] ?? null,
                'sections' => $this->extractSections($jobPosting),
            ]);
        } catch (ConnectionException $e) {
            Log::info('JSON-LD scraper connection failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function extractJobPosting(string $html): ?array
    {
        if (! preg_match_all('#<script[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#si', $html, $matches)) {
            return null;
        }

        foreach ($matches[1] as $jsonBlock) {
            $data = json_decode(trim($jsonBlock), true);

            if (! is_array($data)) {
                continue;
            }

            // Direct JobPosting
            if (($data['@type'] ?? '') === 'JobPosting') {
                return $data;
            }

            // Array of objects
            if (isset($data['@graph']) && is_array($data['@graph'])) {
                foreach ($data['@graph'] as $item) {
                    if (is_array($item) && ($item['@type'] ?? '') === 'JobPosting') {
                        return $item;
                    }
                }
            }

            // Top-level array
            if (array_is_list($data)) {
                foreach ($data as $item) {
                    if (is_array($item) && ($item['@type'] ?? '') === 'JobPosting') {
                        return $item;
                    }
                }
            }
        }

        return null;
    }

    private function extractCompanyName(array $jobPosting): ?string
    {
        $org = $jobPosting['hiringOrganization'] ?? null;

        if (is_string($org)) {
            return $org;
        }

        if (is_array($org)) {
            return $org['name'] ?? null;
        }

        return null;
    }

    private function extractLocation(array $jobPosting): ?string
    {
        $location = $jobPosting['jobLocation'] ?? null;

        if ($location === null) {
            return null;
        }

        if (is_string($location)) {
            return $location;
        }

        if (is_array($location)) {
            // Single location object
            if (isset($location['address'])) {
                return $this->formatAddress($location['address']);
            }

            // Array of locations
            if (array_is_list($location)) {
                $parts = [];

                foreach ($location as $loc) {
                    if (is_array($loc) && isset($loc['address'])) {
                        $formatted = $this->formatAddress($loc['address']);

                        if ($formatted !== null) {
                            $parts[] = $formatted;
                        }
                    }
                }

                return $parts !== [] ? implode('; ', $parts) : null;
            }
        }

        return null;
    }

    private function formatAddress(mixed $address): ?string
    {
        if (is_string($address)) {
            return $address;
        }

        if (! is_array($address)) {
            return null;
        }

        $parts = array_filter([
            $address['addressLocality'] ?? null,
            $address['addressRegion'] ?? null,
            $address['addressCountry'] ?? null,
        ]);

        return $parts !== [] ? implode(', ', $parts) : null;
    }

    private function normalizeEmploymentType(mixed $type): ?string
    {
        if ($type === null) {
            return null;
        }

        if (is_array($type)) {
            $type = $type[0] ?? null;
        }

        if (! is_string($type)) {
            return null;
        }

        return match (strtoupper($type)) {
            'FULL_TIME' => 'Full-time',
            'PART_TIME' => 'Part-time',
            'CONTRACT' => 'Contract',
            'TEMPORARY' => 'Temporary',
            'INTERN' => 'Internship',
            default => $type,
        };
    }

    private function extractCompensation(array $jobPosting): ?string
    {
        $salary = $jobPosting['baseSalary'] ?? ($jobPosting['estimatedSalary'] ?? null);

        if ($salary === null) {
            return null;
        }

        if (is_string($salary)) {
            return $salary;
        }

        if (! is_array($salary)) {
            return null;
        }

        $currency = $salary['currency'] ?? 'USD';
        $value = $salary['value'] ?? null;

        if (is_array($value)) {
            $min = $value['minValue'] ?? null;
            $max = $value['maxValue'] ?? null;
            $unit = $value['unitText'] ?? 'YEAR';

            if ($min !== null && $max !== null) {
                return "{$currency} {$min} - {$max} per {$unit}";
            }

            if ($min !== null || $max !== null) {
                return "{$currency} ".($min ?? $max)." per {$unit}";
            }
        }

        if (is_numeric($value)) {
            return "{$currency} {$value}";
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function extractSections(array $jobPosting): array
    {
        $sections = [];

        if (! empty($jobPosting['qualifications'])) {
            $sections['Qualifications'] = is_array($jobPosting['qualifications'])
                ? implode("\n", $jobPosting['qualifications'])
                : $jobPosting['qualifications'];
        }

        if (! empty($jobPosting['responsibilities'])) {
            $sections['Responsibilities'] = is_array($jobPosting['responsibilities'])
                ? implode("\n", $jobPosting['responsibilities'])
                : $jobPosting['responsibilities'];
        }

        if (! empty($jobPosting['skills'])) {
            $sections['Skills'] = is_array($jobPosting['skills'])
                ? implode("\n", $jobPosting['skills'])
                : $jobPosting['skills'];
        }

        if (! empty($jobPosting['educationRequirements'])) {
            $value = $jobPosting['educationRequirements'];
            $sections['Education'] = is_array($value)
                ? ($value['credentialCategory'] ?? implode("\n", $value))
                : $value;
        }

        if (! empty($jobPosting['experienceRequirements'])) {
            $value = $jobPosting['experienceRequirements'];
            $sections['Experience'] = is_array($value)
                ? implode("\n", $value)
                : $value;
        }

        if (! empty($jobPosting['jobBenefits'])) {
            $sections['Benefits'] = is_array($jobPosting['jobBenefits'])
                ? implode("\n", $jobPosting['jobBenefits'])
                : $jobPosting['jobBenefits'];
        }

        return $sections;
    }
}
