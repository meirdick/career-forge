<?php

namespace App\Services\Scrapers;

use App\Contracts\WebScraperContract;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareScraperDriver implements WebScraperContract
{
    private const int MIN_CONTENT_LENGTH = 200;

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
                ->timeout(50)
                ->post("{$baseUrl}/accounts/{$accountId}/browser-rendering/links", [
                    'url' => $url,
                    'gotoOptions' => ['waitUntil' => 'networkidle0', 'timeout' => 45000],
                    'rejectResourceTypes' => ['image', 'font', 'media'],
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
        // Phase 1: Fast scrape for static pages
        $result = $this->requestMarkdown($url, [
            'gotoOptions' => ['waitUntil' => 'domcontentloaded', 'timeout' => 10000],
            'rejectResourceTypes' => ['image', 'font', 'media', 'stylesheet'],
        ], 15);

        if ($this->isContentSufficient($result)) {
            return $result;
        }

        // Phase 2: SPA-aware scrape — wait for an h1 to appear (signals JS has rendered)
        Log::info('Cloudflare scrape Phase 1 insufficient, trying Phase 2 with waitForSelector', [
            'url' => $url,
            'phase1_length' => $result ? mb_strlen(trim($result)) : 0,
        ]);

        $result = $this->requestMarkdown($url, [
            'gotoOptions' => ['waitUntil' => 'domcontentloaded', 'timeout' => 60000],
            'waitForSelector' => ['selector' => 'h1', 'timeout' => 30000],
            'rejectResourceTypes' => ['image', 'font', 'media'],
        ], 65);

        if (filled($result)) {
            return $result;
        }

        return null;
    }

    private function requestMarkdown(string $url, array $options, int $timeout): ?string
    {
        $accountId = config('services.cloudflare_browser.account_id');
        $baseUrl = config('services.cloudflare_browser.base_url');

        try {
            $response = Http::withToken(config('services.cloudflare_browser.api_token'))
                ->timeout($timeout)
                ->post("{$baseUrl}/accounts/{$accountId}/browser-rendering/markdown", array_merge(
                    ['url' => $url],
                    $options,
                ));

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

    private function isContentSufficient(?string $content): bool
    {
        return filled($content) && mb_strlen(trim($content)) >= self::MIN_CONTENT_LENGTH;
    }
}
