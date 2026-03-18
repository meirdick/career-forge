<?php

namespace App\Services\Scrapers;

use App\Contracts\WebScraperContract;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareScraperDriver implements WebScraperContract
{
    public function isConfigured(): bool
    {
        return filled(config('services.cloudflare_browser.account_id'))
            && filled(config('services.cloudflare_browser.api_token'));
    }

    /**
     * @return list<array{url: string}>|null
     */
    public function discoverLinks(string $url): ?array
    {
        $accountId = config('services.cloudflare_browser.account_id');
        $baseUrl = config('services.cloudflare_browser.base_url');

        try {
            $response = Http::withToken(config('services.cloudflare_browser.api_token'))
                ->timeout(15)
                ->post("{$baseUrl}/accounts/{$accountId}/browser-rendering/links", [
                    'url' => $url,
                ]);

            if (! $response->successful()) {
                Log::warning('Cloudflare Browser Rendering links failed', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $links = $response->json('result', []);
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
            Log::warning('Cloudflare Browser Rendering links connection failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function scrape(string $url): ?string
    {
        $accountId = config('services.cloudflare_browser.account_id');
        $baseUrl = config('services.cloudflare_browser.base_url');

        try {
            $response = Http::withToken(config('services.cloudflare_browser.api_token'))
                ->timeout(30)
                ->post("{$baseUrl}/accounts/{$accountId}/browser-rendering/markdown", [
                    'url' => $url,
                ]);

            if ($response->successful() && filled($response->json('result'))) {
                return $response->json('result');
            }

            Log::warning('Cloudflare Browser Rendering scrape failed', [
                'url' => $url,
                'status' => $response->status(),
            ]);

            return null;
        } catch (ConnectionException $e) {
            Log::warning('Cloudflare Browser Rendering scrape connection failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
