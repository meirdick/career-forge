<?php

namespace App\Jobs;

use App\Models\JobPosting;
use App\Services\WebScraperService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

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

        if ($content) {
            $this->jobPosting->update(['raw_text' => $content]);

            AnalyzeJobPostingJob::dispatch($this->jobPosting);
        }
    }
}
