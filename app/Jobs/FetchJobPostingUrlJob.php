<?php

namespace App\Jobs;

use App\Models\JobPosting;
use App\Services\WebScraperService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FetchJobPostingUrlJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public JobPosting $jobPosting) {}

    public function handle(WebScraperService $scraper): void
    {
        if (! $this->jobPosting->url || $this->jobPosting->raw_text) {
            return;
        }

        $content = $scraper->scrape($this->jobPosting->url);

        if ($content) {
            $this->jobPosting->update(['raw_text' => $content]);

            AnalyzeJobPostingJob::dispatch($this->jobPosting);
        }
    }
}
