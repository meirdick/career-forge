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
use App\Services\ParseQualityValidator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Responses\AgentResponse;

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

        $parsedData = $this->extractParsedData($response);
        $inputLength = mb_strlen($text);
        $attempts = 1;

        $validator = new ParseQualityValidator;
        $quality = $validator->validateResumeParse($parsedData, $inputLength);

        if (! $quality->passed && ! $quality->inputTooShort && $quality->score >= 0.2) {
            Log::info('Resume parse quality below threshold, retrying with enhanced prompt', [
                'document_id' => $this->document->id,
                'score' => $quality->score,
                'failed_rules' => $quality->failedRules,
            ]);

            $enhancedPrompt = $prompt."\n\n".$quality->retryHint;
            $retryResponse = (new ResumeParser)->prompt($enhancedPrompt);
            $retryData = $this->extractParsedData($retryResponse);
            $retryQuality = $validator->validateResumeParse($retryData, $inputLength);
            $attempts = 2;

            if ($retryQuality->score > $quality->score) {
                $parsedData = $retryData;
                $quality = $retryQuality;
            }
        }

        $cacheKey = "resume-parse:{$this->document->id}";
        Cache::put($cacheKey, [
            'status' => 'completed',
            'data' => $parsedData,
        ], now()->addHour());

        $this->document->update([
            'parsed_data' => $parsedData,
            'metadata' => array_merge($this->document->metadata ?? [], [
                'parsed_at' => now()->toIso8601String(),
                'text_length' => $inputLength,
                'parse_quality_score' => $quality->score,
                'parse_attempts' => $attempts,
            ]),
        ]);

        $this->chargeAiUsage($this->user, AiPurpose::ResumeParsing);

        $this->user->notify(new ResumeUploadAnalyzed($this->document));
    }

    /**
     * @return array{experiences: array, accomplishments: array, skills: array, education: array, projects: array, urls: array}
     */
    private function extractParsedData(AgentResponse $response): array
    {
        return [
            'experiences' => $response['experiences'] ?? [],
            'accomplishments' => $response['accomplishments'] ?? [],
            'skills' => $response['skills'] ?? [],
            'education' => $response['education'] ?? [],
            'projects' => $response['projects'] ?? [],
            'urls' => $response['urls'] ?? [],
        ];
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
