<?php

namespace App\Console\Commands;

use App\Jobs\AnalyzeJobPostingJob;
use App\Jobs\FetchJobPostingUrlJob;
use App\Models\JobPosting;
use App\Notifications\JobPostingAnalyzed;
use Illuminate\Console\Command;

class RescrapeAndNotifyCommand extends Command
{
    protected $signature = 'posting:rescrape-notify
        {id : The job posting ID to rescrape and re-analyze}
        {--notify-only : Skip rescraping, just send the notification}';

    protected $description = 'Rescrape a job posting URL with the new drivers, re-analyze, and send the notification';

    public function handle(): int
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

        // Rescrape
        $this->info('Rescraping...');
        FetchJobPostingUrlJob::dispatchSync($posting);
        $posting->refresh();

        $this->line('  raw_text after scrape: '.(filled($posting->raw_text) ? strlen($posting->raw_text).' chars' : 'empty'));

        if (blank($posting->raw_text)) {
            $this->error('Scraping failed — no content extracted.');

            return self::FAILURE;
        }

        // Re-analyze
        $this->info('Analyzing...');
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
}
