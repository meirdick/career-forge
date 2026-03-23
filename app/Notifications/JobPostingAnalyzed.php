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
        $profile = $this->jobPosting->idealCandidateProfile;

        return (new MailMessage)
            ->subject("Job Analysis Complete: {$title} at {$company}")
            ->replyTo('careerforge@meirdick.com', 'CareerForge')
            ->markdown('mail.job-posting-analyzed', [
                'title' => $title,
                'company' => $company,
                'location' => $this->jobPosting->location,
                'seniority' => $this->jobPosting->seniority_level,
                'remote' => $this->jobPosting->remote_policy,
                'compensation' => $this->jobPosting->compensation,
                'summary' => $profile?->candidate_summary,
                'experience' => $profile?->experience_profile,
                'culturalFit' => $profile?->cultural_fit,
                'redFlags' => $profile?->red_flags ?? [],
                'url' => route('job-postings.show', $this->jobPosting),
            ]);
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
