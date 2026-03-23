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
        $profile = $this->jobPosting->idealCandidateProfile;

        $message = (new MailMessage)
            ->subject("Job Analysis Complete: {$title} at {$company}")
            ->greeting('Your job posting has been analyzed!')
            ->line("**{$title}** at **{$company}**".($location ? " — {$location}" : ''));

        $message->line($this->formatDetailsLine());

        if ($profile?->candidate_summary) {
            $message->line('---');
            $message->line("**What they're looking for**");
            $message->line($profile->candidate_summary);
        }

        if (filled($profile?->required_skills)) {
            $skillNames = collect($profile->required_skills)
                ->map(fn (array $skill) => $skill['name'].($skill['years'] ?? 0 ? " ({$skill['years']}+ yrs)" : ''))
                ->implode(' · ');

            $message->line("**Must-have skills:** {$skillNames}");
        }

        if (filled($profile?->preferred_skills)) {
            $bonusNames = collect($profile->preferred_skills)
                ->pluck('name')
                ->implode(' · ');

            $message->line("**Nice-to-have:** {$bonusNames}");
        }

        if (filled($profile?->red_flags)) {
            $flags = collect($profile->red_flags)
                ->take(3)
                ->implode(' · ');

            $message->line("**Red flags to avoid:** {$flags}");
        }

        return $message
            ->action('View Full Analysis', route('job-postings.show', $this->jobPosting))
            ->line('Generate a gap analysis and tailored resume to see how you match up.');
    }

    private function formatDetailsLine(): string
    {
        $parts = array_filter([
            $this->jobPosting->seniority_level,
            $this->jobPosting->remote_policy,
            $this->jobPosting->compensation,
        ]);

        if ($parts === []) {
            return '';
        }

        return implode(' · ', $parts);
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
