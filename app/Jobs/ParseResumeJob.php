<?php

namespace App\Jobs;

use App\Ai\Agents\ResumeParser;
use App\Concerns\ConfiguresAiForUser;
use App\Enums\AiPurpose;
use App\Models\Document;
use App\Models\User;
use App\Notifications\ResumeUploadAnalyzed;
use App\Notifications\ResumeUploadFailed;
use App\Services\DocumentExtractorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ParseResumeJob implements ShouldQueue
{
    use ConfiguresAiForUser, Queueable;

    public int $timeout = 120;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public User $user,
        public Document $document,
    ) {}

    public function handle(DocumentExtractorService $extractor): void
    {
        $this->configureAiForUser($this->user, AiPurpose::ResumeParsing);
        $extension = pathinfo($this->document->path, PATHINFO_EXTENSION);
        $tempPath = tempnam(sys_get_temp_dir(), 'resume_').'.'.$extension;
        file_put_contents($tempPath, Storage::get($this->document->path));

        try {
            $text = $extractor->extract($tempPath);
        } finally {
            @unlink($tempPath);
        }

        $prompt = view('prompts.resume-parser', ['text' => $text])->render();
        $response = (new ResumeParser)->prompt($prompt);

        $parsedData = [
            'experiences' => $response['experiences'] ?? [],
            'accomplishments' => $response['accomplishments'] ?? [],
            'skills' => $response['skills'] ?? [],
            'education' => $response['education'] ?? [],
            'projects' => $response['projects'] ?? [],
            'urls' => $response['urls'] ?? [],
        ];

        $cacheKey = "resume-parse:{$this->document->id}";
        Cache::put($cacheKey, [
            'status' => 'completed',
            'data' => $parsedData,
        ], now()->addHour());

        $this->document->update([
            'parsed_data' => $parsedData,
            'metadata' => array_merge($this->document->metadata ?? [], [
                'parsed_at' => now()->toIso8601String(),
                'text_length' => mb_strlen($text),
            ]),
        ]);

        $this->chargeAiUsage($this->user, AiPurpose::ResumeParsing);

        $this->user->notify(new ResumeUploadAnalyzed($this->document));
    }

    public function failed(\Throwable $exception): void
    {
        $cacheKey = "resume-parse:{$this->document->id}";
        Cache::put($cacheKey, [
            'status' => 'failed',
            'error' => $exception->getMessage(),
        ], now()->addHour());

        $this->user->notify(new ResumeUploadFailed($this->document));
    }
}
