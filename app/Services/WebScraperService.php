<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebScraperService
{
    /** @var list<string> */
    private const array UNSUPPORTED_DOMAINS = [
        'linkedin.com',
    ];

    public static function isUnsupportedUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! $host) {
            return false;
        }

        $host = strtolower($host);

        foreach (self::UNSUPPORTED_DOMAINS as $domain) {
            if ($host === $domain || str_ends_with($host, ".{$domain}")) {
                return true;
            }
        }

        return false;
    }

    /**
     * Discover child pages from a URL using Firecrawl's /map endpoint.
     *
     * @return list<array{url: string}>|null
     */
    public function discoverLinks(string $url): ?array
    {
        $apiKey = config('services.firecrawl.api_key');
        $baseUrl = config('services.firecrawl.base_url');

        if (! $apiKey) {
            Log::warning('Firecrawl API key not configured, skipping URL discovery.');

            return null;
        }

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

            // Filter to same-domain links only, exclude the source URL itself
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
