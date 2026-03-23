<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResumeUploadFailed extends Notification implements ShouldQueue
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

        return (new MailMessage)
            ->subject("Resume Parse Issue: {$filename}")
            ->replyTo('careerforge@meirdick.com', 'CareerForge')
            ->markdown('mail.resume-upload-failed', [
                'filename' => $filename,
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
