<?php

namespace App\Console\Commands;

use App\Jobs\AnalyzeJobPostingJob;
use App\Models\JobPosting;
use App\Notifications\JobPostingAnalyzed;
use App\Services\Scrapers\ContentQualityAnalyzer;
use App\Services\WebScraperService;
use Illuminate\Console\Command;

class RescrapeAndNotifyCommand extends Command
{
    protected $signature = 'posting:rescrape-notify
        {id : The job posting ID to rescrape and re-analyze}
        {--notify-only : Skip rescraping, just send the notification}
        {--dry-run : Scrape and show results without saving or notifying}';

    protected $description = 'Rescrape a job posting URL with the new drivers, re-analyze, and send the notification';

    public function handle(WebScraperService $scraper): int
    {
        $posting = JobPosting::with(['user', 'idealCandidateProfile'])->find($this->argument('id'));

        if (! $posting) {
            $this->error('Job posting not found.');

            return self::FAILURE;
        }

        $this->info("Job Posting #{$posting->id}");
        $this->line("  Title: {$posting->title}");
        $this->line("  Company: {$posting->company}");
        $this->line("  URL: {$posting->url}");
        $this->line("  User: {$posting->user->name} ({$posting->user->email})");
        $this->line('  Analyzed: '.($posting->analyzed_at ?? 'never'));
        $this->line('  raw_text: '.(filled($posting->raw_text) ? strlen($posting->raw_text).' chars' : 'empty'));

        if ($this->option('notify-only')) {
            if (! $posting->analyzed_at) {
                $this->error('Cannot notify — posting has not been analyzed yet.');

                return self::FAILURE;
            }

            $posting->user->notifyNow(new JobPostingAnalyzed($posting));
            $this->info("Notification sent to {$posting->user->email}");

            return self::SUCCESS;
        }

        // Scrape fresh — bypass FetchJobPostingUrlJob which skips when raw_text exists
        $this->info('Scraping fresh...');
        $content = $scraper->scrape($posting->url);

        if (blank($content)) {
            $this->error('Scraping failed — no content returned.');

            return self::FAILURE;
        }

        $quality = ContentQualityAnalyzer::analyze($content);
        $this->line("  Scrape length: {$this->contentLength($content)} chars");
        $this->line("  Quality: {$quality->score}/{$quality->maxScore}");
        $this->line('  Signals: '.$this->formatSignals($quality->signals));

        if (! $quality->isValid) {
            $this->error("Scrape failed quality check ({$quality->score}/{$quality->maxScore}). Not sending.");

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run — not saving or notifying.');
            $this->line(substr($content, 0, 500).'...');

            return self::SUCCESS;
        }

        // Save and analyze
        $posting->update(['raw_text' => $content]);
        $this->info('Content saved. Analyzing...');

        AnalyzeJobPostingJob::dispatchSync($posting);
        $posting->refresh();

        $this->info("Analysis complete. Notification sent to {$posting->user->email}");
        $this->line("  Title: {$posting->title}");
        $this->line("  Company: {$posting->company}");
        $this->line("  Location: {$posting->location}");
        $this->line("  Seniority: {$posting->seniority_level}");
        $this->line("  Compensation: {$posting->compensation}");

        return self::SUCCESS;
    }

    private function contentLength(string $content): int
    {
        return mb_strlen(trim($content));
    }

    /**
     * @param  array<string, bool>  $signals
     */
    private function formatSignals(array $signals): string
    {
        $parts = [];

        foreach ($signals as $name => $passed) {
            $parts[] = ($passed ? '+' : '-').$name;
        }

        return implode(' ', $parts);
    }
}
