<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $stats = [
            'experiences' => $user->experiences()->count(),
            'skills' => $user->skills()->count(),
            'accomplishments' => $user->experiences()->withCount('accomplishments')->get()->sum('accomplishments_count'),
            'applications' => $user->applications()->count(),
        ];

        $isNewUser = $stats['experiences'] === 0
            && $stats['skills'] === 0
            && $stats['accomplishments'] === 0
            && $stats['applications'] === 0
            && $user->jobPostings()->count() === 0;

        $recentApplications = $user->applications()
            ->with('jobPosting:id,title')
            ->latest()
            ->limit(5)
            ->get(['id', 'company', 'role', 'status', 'job_posting_id', 'created_at']);

        $recentExperiences = $user->experiences()
            ->latest('started_at')
            ->limit(3)
            ->get(['id', 'company', 'title', 'started_at', 'is_current']);

        $pipelineContinuation = null;
        if (! $isNewUser) {
            $pipelineContinuation = $this->getPipelineContinuation($user);
        }

        return Inertia::render('dashboard', [
            'stats' => $stats,
            'isNewUser' => $isNewUser,
            'recentApplications' => $recentApplications,
            'recentExperiences' => $recentExperiences,
            'pipelineContinuation' => $pipelineContinuation,
        ]);
    }

    /**
     * @return array{jobPosting: array{id: int, title: string|null, company: string|null}, nextStep: string, nextStepLabel: string, nextStepUrl: string}|null
     */
    private function getPipelineContinuation($user): ?array
    {
        $jobPosting = $user->jobPostings()
            ->whereDoesntHave('applications')
            ->latest()
            ->first(['id', 'title', 'company']);

        if (! $jobPosting) {
            return null;
        }

        $hasGapAnalysis = $user->gapAnalyses()
            ->whereHas('idealCandidateProfile', fn ($q) => $q->where('job_posting_id', $jobPosting->id))
            ->exists();

        $hasResume = $user->resumes()
            ->where('job_posting_id', $jobPosting->id)
            ->exists();

        if (! $hasGapAnalysis) {
            return [
                'jobPosting' => [
                    'id' => $jobPosting->id,
                    'title' => $jobPosting->title,
                    'company' => $jobPosting->company,
                ],
                'nextStep' => 'gap_analysis',
                'nextStepLabel' => 'Run Gap Analysis',
                'nextStepUrl' => "/job-postings/{$jobPosting->id}",
            ];
        }

        if (! $hasResume) {
            return [
                'jobPosting' => [
                    'id' => $jobPosting->id,
                    'title' => $jobPosting->title,
                    'company' => $jobPosting->company,
                ],
                'nextStep' => 'resume',
                'nextStepLabel' => 'Generate Resume',
                'nextStepUrl' => "/job-postings/{$jobPosting->id}",
            ];
        }

        return [
            'jobPosting' => [
                'id' => $jobPosting->id,
                'title' => $jobPosting->title,
                'company' => $jobPosting->company,
            ],
            'nextStep' => 'application',
            'nextStepLabel' => 'Create Application',
            'nextStepUrl' => '/applications/create',
        ];
    }
}
