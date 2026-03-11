<?php

namespace App\Jobs;

use App\Ai\Agents\LinkIndexer;
use App\Concerns\ConfiguresAiForUser;
use App\Enums\AiPurpose;
use App\Models\EvidenceEntry;
use App\Models\User;
use App\Services\WebScraperService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IndexLinkJob implements ShouldQueue
{
    use ConfiguresAiForUser, Queueable;

    public int $timeout = 120;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public User $user,
        public EvidenceEntry $evidenceEntry,
    ) {}

    public function handle(WebScraperService $scraper): void
    {
        $urls = $this->urlsToScrape();
        $allContent = [];

        foreach ($urls as $url) {
            $content = $scraper->scrape($url);

            if ($content) {
                $allContent[] = "## Source: {$url}\n\n{$content}";
            } else {
                Log::warning('Failed to scrape page during indexing', ['url' => $url]);
            }
        }

        if (empty($allContent)) {
            Cache::put($this->cacheKey(), [
                'status' => 'failed',
                'error' => 'Could not fetch URL content.',
            ], now()->addHour());

            return;
        }

        $combined = implode("\n\n---\n\n", $allContent);

        $this->configureAiForUser($this->user, AiPurpose::LinkIndexing);

        $response = (new LinkIndexer)->prompt("Analyze this web page content and extract professional information:\n\n{$combined}");

        Cache::put($this->cacheKey(), [
            'status' => 'completed',
            'data' => [
                'skills' => $response['skills'] ?? [],
                'accomplishments' => $response['accomplishments'] ?? [],
                'projects' => $response['projects'] ?? [],
            ],
        ], now()->addHour());

        $this->chargeAiUsage($this->user, AiPurpose::LinkIndexing);
    }

    /**
     * @return list<string>
     */
    private function urlsToScrape(): array
    {
        $urls = [$this->evidenceEntry->url];

        if (! empty($this->evidenceEntry->pages)) {
            $urls = array_merge($urls, $this->evidenceEntry->pages);
        }

        return array_values(array_unique($urls));
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
        return "evidence-index:{$this->evidenceEntry->id}";
    }
}
