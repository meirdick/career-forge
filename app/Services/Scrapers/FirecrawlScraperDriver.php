<?php

namespace App\Services\Scrapers;

use App\Contracts\WebScraperContract;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirecrawlScraperDriver implements WebScraperContract
{
    public function isConfigured(): bool
    {
        return filled(config('services.firecrawl.api_key'));
    }

    /**
     * @return list<array{url: string}>|null
     */
    public function discoverLinks(string $url): ?array
    {
        $apiKey = config('services.firecrawl.api_key');
        $baseUrl = config('services.firecrawl.base_url');

        try {
            $response = Http::withToken($apiKey)
                ->timeout(15)
                ->post("{$baseUrl}/v1/map", [
                    'url' => $url,
                ]);

            if (! $response->successful() || ! $response->json('success')) {
                Log::warning('Firecrawl map failed', [
                    'url' => $url,
                    'status' => $response->status(),
                    'error' => $response->json('error'),
                ]);

                return null;
            }

            $links = $response->json('links', []);
            $sourceHost = parse_url($url, PHP_URL_HOST);

            return collect($links)
                ->filter(function (string $link) use ($sourceHost, $url) {
                    $host = parse_url($link, PHP_URL_HOST);

                    return $host === $sourceHost && rtrim($link, '/') !== rtrim($url, '/');
                })
                ->map(fn (string $link) => ['url' => $link])
                ->values()
                ->all();
        } catch (ConnectionException $e) {
            Log::warning('Firecrawl map connection failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function scrape(string $url): ?string
    {
        $apiKey = config('services.firecrawl.api_key');
        $baseUrl = config('services.firecrawl.base_url');

        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post("{$baseUrl}/v1/scrape", [
                    'url' => $url,
                    'formats' => ['markdown'],
                ]);

            if ($response->successful() && $response->json('success')) {
                return $response->json('data.markdown');
            }

            Log::warning('Firecrawl scrape failed', [
                'url' => $url,
                'status' => $response->status(),
                'error' => $response->json('error'),
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
