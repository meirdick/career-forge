<?php

namespace App\Jobs;

use App\Ai\Agents\GapAnalyzer;
use App\Concerns\ConfiguresAiForUser;
use App\Enums\AiPurpose;
use App\Models\GapAnalysis;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PerformGapAnalysisJob implements ShouldQueue
{
    use ConfiguresAiForUser, Queueable;

    public int $timeout = 120;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public GapAnalysis $gapAnalysis,
    ) {}

    public function handle(): void
    {
        $profile = $this->gapAnalysis->idealCandidateProfile;
        $user = $this->gapAnalysis->user;

        $this->configureAiForUser($user, AiPurpose::GapAnalysis);

        $library = [
            'experiences' => $user->experiences()->with(['accomplishments', 'projects', 'skills'])->get()->toArray(),
            'skills' => $user->skills()->get()->toArray(),
            'education' => $user->educationEntries()->get()->toArray(),
            'identity' => $user->professionalIdentity?->toArray(),
        ];

        $prompt = view('prompts.gap-analysis', [
            'profile' => $profile->toArray(),
            'library' => $library,
        ])->render();

        $response = (new GapAnalyzer)->prompt($prompt);

        $this->gapAnalysis->update([
            'strengths' => $response['strengths'] ?? [],
            'gaps' => $response['gaps'] ?? [],
            'overall_match_score' => $response['overall_match_score'] ?? null,
            'ai_summary' => $response['summary'] ?? null,
        ]);

        $this->chargeAiUsage($user, AiPurpose::GapAnalysis);
    }
}
