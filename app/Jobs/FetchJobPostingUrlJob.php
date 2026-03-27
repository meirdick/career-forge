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

        // Immediately populate from URL slug so the UI shows something
        $this->populateFromUrl();

        $content = $scraper->scrape($this->jobPosting->url);

        if (! $content) {
            Log::warning('Scrape returned no content', [
                'job_posting_id' => $this->jobPosting->id,
                'url' => $this->jobPosting->url,
            ]);

            $this->jobPosting->update(['analyzed_at' => now()]);

            $this->jobPosting->user->notify(
                new JobPostingScrapeFailed($this->jobPosting, 'No content could be extracted from the page.')
            );

            return;
        }

        // Even if quality fails, extract whatever metadata we can from the content
        $this->populateFromContent($content);

        $quality = ContentQualityAnalyzer::analyze($content);

        if (! $quality->isValid) {
            Log::warning('Scraped content failed quality check', [
                'job_posting_id' => $this->jobPosting->id,
                'url' => $this->jobPosting->url,
                'quality_score' => "{$quality->score}/{$quality->maxScore}",
                'failing_signals' => array_keys(array_filter($quality->signals, fn (bool $passed) => ! $passed)),
                'content_preview' => mb_substr($content, 0, 200),
            ]);

            $this->jobPosting->update(['analyzed_at' => now()]);

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
     * Extract title from the URL slug so the UI has something immediately.
     */
    private function populateFromUrl(): void
    {
        $path = parse_url($this->jobPosting->url, PHP_URL_PATH) ?? '';
        $segments = array_filter(explode('/', $path));
        $slug = end($segments) ?: '';

        $slug = preg_replace('/[_-]\d+$/', '', $slug);

        if (blank($slug)) {
            return;
        }

        $title = str_replace(['-', '_'], ' ', $slug);
        $title = preg_replace('/\s{2,}/', ' ', $title);
        $title = mb_convert_case(trim($title), MB_CASE_TITLE);

        if (blank($this->jobPosting->title) && filled($title)) {
            $this->jobPosting->update(['title' => $title]);
        }
    }

    /**
     * Extract metadata from scraped markdown content — title from the first
     * heading, company from a **Company:** line. Content metadata is more
     * accurate than URL slugs, so it overwrites any slug-derived values.
     */
    private function populateFromContent(string $content): void
    {
        $updates = [];

        // Title from first markdown heading: "# Senior Director, Lead PM"
        if (preg_match('/^#\s+(.+)$/m', $content, $m)) {
            $updates['title'] = trim($m[1]);
        }

        // Company from **Company:** metadata line (our formatter writes this pattern)
        if (preg_match('/\*\*Company:\*\*\s*(.+)$/m', $content, $m)) {
            $updates['company'] = trim($m[1]);
        }

        // Location from **Location:** metadata line
        if (preg_match('/\*\*Location:\*\*\s*(.+)$/m', $content, $m)) {
            $updates['location'] = trim($m[1]);
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

        $this->jobPosting->update(['analyzed_at' => now()]);

        $this->jobPosting->user->notify(
            new JobPostingScrapeFailed(
                $this->jobPosting,
                'All attempts to fetch the job posting failed. Please paste the job description manually.'
            )
        );
    }
}
