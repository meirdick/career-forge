<?php

namespace App\Jobs;

use App\Models\EvidenceEntry;
use App\Services\WebScraperService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class DiscoverPortfolioLinksJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 30;

    public int $tries = 1;

    public function __construct(
        public EvidenceEntry $evidenceEntry,
    ) {}

    public function handle(WebScraperService $scraper): void
    {
        $links = $scraper->discoverLinks($this->evidenceEntry->url);

        if ($links === null) {
            Cache::put($this->cacheKey(), [
                'status' => 'failed',
                'error' => 'Could not discover pages from this URL.',
            ], now()->addHour());

            return;
        }

        Cache::put($this->cacheKey(), [
            'status' => 'completed',
            'links' => $links,
        ], now()->addHour());
    }

    public function failed(\Throwable $exception): void
    {
        Cache::put($this->cacheKey(), [
            'status' => 'failed',
            'error' => $exception->getMessage(),
        ], now()->addHour());
    }

    private function cacheKey(): string
    {
        return "evidence-discover:{$this->evidenceEntry->id}";
    }
}
