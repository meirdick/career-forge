<?php

namespace App\Jobs;

use App\Ai\Agents\JobAnalyzer;
use App\Models\JobPosting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AnalyzeJobPostingJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 1;

    public function __construct(
        public JobPosting $jobPosting,
    ) {}

    public function handle(): void
    {
        $prompt = view('prompts.job-analysis', ['text' => $this->jobPosting->raw_text])->render();
        $response = (new JobAnalyzer)->prompt($prompt);

        $this->jobPosting->update([
            'title' => $this->jobPosting->title ?? ($response['title'] ?? null),
            'company' => $this->jobPosting->company ?? ($response['company'] ?? null),
            'location' => $this->jobPosting->location ?? ($response['location'] ?? null),
            'seniority_level' => $response['seniority_level'] ?? null,
            'compensation' => $response['compensation'] ?? null,
            'remote_policy' => $response['remote_policy'] ?? null,
            'parsed_data' => $response->toArray(),
            'analyzed_at' => now(),
        ]);

        $this->jobPosting->idealCandidateProfile()->updateOrCreate(
            ['job_posting_id' => $this->jobPosting->id],
            [
                'required_skills' => $response['required_skills'] ?? [],
                'preferred_skills' => $response['preferred_skills'] ?? [],
                'experience_profile' => $response['experience_profile'] ?? [],
                'cultural_fit' => $response['cultural_fit'] ?? [],
                'language_guidance' => $response['language_guidance'] ?? [],
                'red_flags' => $response['red_flags'] ?? [],
                'company_research' => $response['company_research'] ?? [],
                'candidate_summary' => $response['candidate_summary'] ?? null,
                'is_user_edited' => false,
            ],
        );
    }
}
