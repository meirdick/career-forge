<?php

namespace App\Jobs;

use App\Ai\Agents\ResumeParser;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentExtractorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ParseResumeJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 1;

    public function __construct(
        public User $user,
        public Document $document,
    ) {}

    public function handle(DocumentExtractorService $extractor): void
    {
        $path = Storage::disk($this->document->disk)->path($this->document->path);
        $text = $extractor->extract($path);

        $prompt = view('prompts.resume-parser', ['text' => $text])->render();
        $response = (new ResumeParser)->prompt($prompt);

        $cacheKey = "resume-parse:{$this->document->id}";
        Cache::put($cacheKey, [
            'status' => 'completed',
            'data' => [
                'experiences' => $response['experiences'] ?? [],
                'accomplishments' => $response['accomplishments'] ?? [],
                'skills' => $response['skills'] ?? [],
                'education' => $response['education'] ?? [],
                'projects' => $response['projects'] ?? [],
            ],
        ], now()->addHour());

        $this->document->update([
            'metadata' => array_merge($this->document->metadata ?? [], [
                'parsed_at' => now()->toIso8601String(),
                'text_length' => mb_strlen($text),
            ]),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $cacheKey = "resume-parse:{$this->document->id}";
        Cache::put($cacheKey, [
            'status' => 'failed',
            'error' => $exception->getMessage(),
        ], now()->addHour());
    }
}
