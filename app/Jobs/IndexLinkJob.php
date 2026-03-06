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

class IndexLinkJob implements ShouldQueue
{
    use ConfiguresAiForUser, Queueable;

    public int $timeout = 120;

    public int $tries = 1;

    public function __construct(
        public User $user,
        public EvidenceEntry $evidenceEntry,
    ) {}

    public function handle(WebScraperService $scraper): void
    {
        $content = $scraper->scrape($this->evidenceEntry->url);

        if (! $content) {
            Cache::put($this->cacheKey(), [
                'status' => 'failed',
                'error' => 'Could not fetch URL content.',
            ], now()->addHour());

            return;
        }

        $this->configureAiForUser($this->user, AiPurpose::LinkIndexing);

        $response = (new LinkIndexer)->prompt("Analyze this web page content and extract professional information:\n\n{$content}");

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
