<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebScraperService
{
    public function scrape(string $url): ?string
    {
        $apiKey = config('services.firecrawl.api_key');
        $baseUrl = config('services.firecrawl.base_url');

        if (! $apiKey) {
            Log::warning('Firecrawl API key not configured, skipping URL scraping.');

            return null;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post("{$baseUrl}/v1/scrape", [
                    'url' => $url,
                    'formats' => ['markdown'],
                ]);

            if ($response->successful()) {
                return $response->json('data.markdown');
            }

            Log::warning('Firecrawl scrape failed', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (ConnectionException $e) {
            Log::warning('Firecrawl connection failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
