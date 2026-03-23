<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResumeUploadAnalyzed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Document $document,
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
        $filename = $this->document->filename;
        $data = $this->document->parsed_data ?? [];

        $counts = [
            'experiences' => count($data['experiences'] ?? []),
            'skills' => count($data['skills'] ?? []),
            'accomplishments' => count($data['accomplishments'] ?? []),
            'education' => count($data['education'] ?? []),
            'projects' => count($data['projects'] ?? []),
        ];

        $latestRole = null;
        $latestCompany = null;
        if (! empty($data['experiences'])) {
            $latest = $data['experiences'][0];
            $latestRole = $latest['title'] ?? null;
            $latestCompany = $latest['company'] ?? null;
        }

        $topSkills = array_slice(
            array_map(fn (array $s) => $s['name'], $data['skills'] ?? []),
            0,
            6
        );

        return (new MailMessage)
            ->subject("Resume Parsed: {$filename}")
            ->replyTo('careerforge@meirdick.com', 'CareerForge')
            ->markdown('mail.resume-upload-analyzed', [
                'filename' => $filename,
                'counts' => $counts,
                'latestRole' => $latestRole,
                'latestCompany' => $latestCompany,
                'topSkills' => $topSkills,
                'url' => route('resume-upload.review', $this->document),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'document_id' => $this->document->id,
            'filename' => $this->document->filename,
        ];
    }
}
