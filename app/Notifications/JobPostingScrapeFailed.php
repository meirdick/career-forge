<?php

namespace App\Notifications;

use App\Models\JobPosting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JobPostingScrapeFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public JobPosting $jobPosting,
        public string $reason = 'The page content could not be extracted.',
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
        $url = $this->jobPosting->url ?? 'Unknown URL';

        return (new MailMessage)
            ->subject('Job Posting Could Not Be Scraped')
            ->greeting('We were unable to fetch the job posting.')
            ->line("**URL:** {$url}")
            ->line($this->reason)
            ->line('You can paste the job description text directly instead.')
            ->action('Edit Job Posting', route('job-postings.show', $this->jobPosting))
            ->line('Open the posting and paste the full job description into the text field.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'job_posting_id' => $this->jobPosting->id,
            'url' => $this->jobPosting->url,
            'reason' => $this->reason,
        ];
    }
}
