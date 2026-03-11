<?php

namespace App\Services;

use App\Contracts\WebScraperContract;
use Illuminate\Support\Facades\Log;

class WebScraperService
{
    /** @var list<string> */
    private const array UNSUPPORTED_DOMAINS = [
        'linkedin.com',
    ];

    /**
     * @param  list<WebScraperContract>  $drivers
     */
    public function __construct(
        private array $drivers,
    ) {}

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
     * Discover child pages from a URL, trying each configured driver in order.
     *
     * @return list<array{url: string}>|null
     */
    public function discoverLinks(string $url): ?array
    {
        foreach ($this->drivers as $driver) {
            if (! $driver->isConfigured()) {
                continue;
            }

            $result = $driver->discoverLinks($url);

            if ($result !== null) {
                return $result;
            }

            Log::warning('Scraper driver failed for discoverLinks, trying next driver', [
                'driver' => $driver::class,
                'url' => $url,
            ]);
        }

        return null;
    }

    /**
     * Scrape a URL to markdown, trying each configured driver in order.
     */
    public function scrape(string $url): ?string
    {
        foreach ($this->drivers as $driver) {
            if (! $driver->isConfigured()) {
                continue;
            }

            $result = $driver->scrape($url);

            if ($result !== null) {
                return $result;
            }

            Log::warning('Scraper driver failed for scrape, trying next driver', [
                'driver' => $driver::class,
                'url' => $url,
            ]);
        }

        return null;
    }
}
