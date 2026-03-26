<?php

namespace App\Jobs;

use App\Ai\Agents\LinkIndexer;
use App\Concerns\ConfiguresAiForUser;
use App\Enums\AiPurpose;
use App\Models\EvidenceEntry;
use App\Models\User;
use App\Services\ParseQualityValidator;
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

        $prompt = "Analyze this web page content and extract professional information:\n\n{$combined}";
        $response = (new LinkIndexer)->prompt($prompt);

        $parsedData = [
            'skills' => $response['skills'] ?? [],
            'accomplishments' => $response['accomplishments'] ?? [],
            'projects' => $response['projects'] ?? [],
        ];

        $inputLength = mb_strlen($combined);
        $validator = new ParseQualityValidator;
        $quality = $validator->validateLinkIndex($parsedData, $inputLength);
        $attempts = 1;

        if (! $quality->passed && ! $quality->inputTooShort && $quality->score >= 0.2) {
            Log::info('Link index quality below threshold, retrying with enhanced prompt', [
                'evidence_entry_id' => $this->evidenceEntry->id,
                'score' => $quality->score,
                'failed_rules' => $quality->failedRules,
            ]);

            $enhancedPrompt = $prompt."\n\n".$quality->retryHint;
            $retryResponse = (new LinkIndexer)->prompt($enhancedPrompt);
            $retryData = [
                'skills' => $retryResponse['skills'] ?? [],
                'accomplishments' => $retryResponse['accomplishments'] ?? [],
                'projects' => $retryResponse['projects'] ?? [],
            ];
            $retryQuality = $validator->validateLinkIndex($retryData, $inputLength);
            $attempts = 2;

            if ($retryQuality->score > $quality->score) {
                $parsedData = $retryData;
                $quality = $retryQuality;
            }
        }

        Cache::put($this->cacheKey(), [
            'status' => 'completed',
            'data' => $parsedData,
            'parse_quality_score' => $quality->score,
            'parse_attempts' => $attempts,
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
