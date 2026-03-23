<?php

namespace App\Notifications;

use App\Models\JobPosting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JobPostingAnalyzed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public JobPosting $jobPosting,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $title = $this->jobPosting->title ?? 'Untitled Position';
        $company = $this->jobPosting->company ?? 'Unknown Company';
        $location = $this->jobPosting->location;
        $summary = $this->jobPosting->idealCandidateProfile?->candidate_summary;

        $message = (new MailMessage)
            ->subject("Job Analysis Complete: {$title} at {$company}")
            ->greeting('Your job posting has been analyzed!')
            ->line("**{$title}** at **{$company}**".($location ? " — {$location}" : ''));

        if ($summary) {
            $message->line($summary);
        }

        return $message
            ->action('View Job Posting', route('job-postings.show', $this->jobPosting))
            ->line('You can now generate a gap analysis and tailored resume for this position.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'job_posting_id' => $this->jobPosting->id,
            'title' => $this->jobPosting->title,
            'company' => $this->jobPosting->company,
        ];
    }
}
