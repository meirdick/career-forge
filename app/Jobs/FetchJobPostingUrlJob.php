<?php

namespace App\Jobs;

use App\Models\JobPosting;
use App\Notifications\JobPostingScrapeFailed;
use App\Services\Scrapers\ContentQualityAnalyzer;
use App\Services\WebScraperService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class FetchJobPostingUrlJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public JobPosting $jobPosting) {}

    public function handle(WebScraperService $scraper): void
    {
        if (! $this->jobPosting->url || $this->jobPosting->raw_text) {
            return;
        }

        if (WebScraperService::isUnsupportedUrl($this->jobPosting->url)) {
            Log::warning('Skipping fetch for unsupported URL', [
                'job_posting_id' => $this->jobPosting->id,
                'url' => $this->jobPosting->url,
            ]);

            return;
        }

        $content = $scraper->scrape($this->jobPosting->url);

        if (! $content) {
            Log::warning('Scrape returned no content', [
                'job_posting_id' => $this->jobPosting->id,
                'url' => $this->jobPosting->url,
            ]);

            $this->populateFromUrl();

            $this->jobPosting->user->notify(
                new JobPostingScrapeFailed($this->jobPosting, 'No content could be extracted from the page.')
            );

            return;
        }

        $quality = ContentQualityAnalyzer::analyze($content);

        if (! $quality->isValid) {
            Log::warning('Scraped content failed quality check', [
                'job_posting_id' => $this->jobPosting->id,
                'url' => $this->jobPosting->url,
                'quality_score' => "{$quality->score}/{$quality->maxScore}",
                'failing_signals' => array_keys(array_filter($quality->signals, fn (bool $passed) => ! $passed)),
                'content_preview' => mb_substr($content, 0, 200),
            ]);

            $this->populateFromUrl();

            $this->jobPosting->user->notify(
                new JobPostingScrapeFailed(
                    $this->jobPosting,
                    'The page appeared to load but only returned navigation and boilerplate content, not the actual job description.'
                )
            );

            return;
        }

        $this->jobPosting->update(['raw_text' => $content]);

        AnalyzeJobPostingJob::dispatch($this->jobPosting);
    }

    /**
     * Extract whatever metadata we can from the URL slug (title, location)
     * so the posting isn't completely empty even when scraping fails.
     */
    private function populateFromUrl(): void
    {
        $url = $this->jobPosting->url;

        if (! $url) {
            return;
        }

        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $segments = array_filter(explode('/', $path));
        $slug = end($segments) ?: '';

        // Strip trailing IDs (e.g. "_55006", "-12345")
        $slug = preg_replace('/[_-]\d+$/', '', $slug);

        if (blank($slug)) {
            return;
        }

        // Convert URL slug to readable title: "Senior-Director--Lead-Product-Manager---Norton-360" → "Senior Director Lead Product Manager Norton 360"
        $title = str_replace(['-', '_'], ' ', $slug);
        $title = preg_replace('/\s{2,}/', ' ', $title);
        $title = mb_convert_case(trim($title), MB_CASE_TITLE);

        $updates = [];

        if (blank($this->jobPosting->title) && filled($title)) {
            $updates['title'] = $title;
        }

        if ($updates !== []) {
            $this->jobPosting->update($updates);
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('FetchJobPostingUrlJob failed after all retries', [
            'job_posting_id' => $this->jobPosting->id,
            'url' => $this->jobPosting->url,
            'error' => $exception->getMessage(),
        ]);

        $this->jobPosting->user->notify(
            new JobPostingScrapeFailed(
                $this->jobPosting,
                'All attempts to fetch the job posting failed. Please paste the job description manually.'
            )
        );
    }
}
