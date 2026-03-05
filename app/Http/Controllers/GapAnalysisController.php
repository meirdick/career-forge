<?php

namespace App\Http\Controllers;

use App\Jobs\PerformGapAnalysisJob;
use App\Models\GapAnalysis;
use App\Models\JobPosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GapAnalysisController extends Controller
{
    public function store(Request $request, JobPosting $jobPosting): RedirectResponse
    {
        abort_unless($jobPosting->user_id === $request->user()->id, 403);
        abort_unless($jobPosting->idealCandidateProfile !== null, 422, 'Job posting has not been analyzed yet.');

        $gapAnalysis = $request->user()->gapAnalyses()->create([
            'ideal_candidate_profile_id' => $jobPosting->idealCandidateProfile->id,
            'strengths' => [],
            'gaps' => [],
        ]);

        PerformGapAnalysisJob::dispatch($gapAnalysis);

        return to_route('gap-analyses.show', $gapAnalysis)
            ->with('success', 'Gap analysis started...');
    }

    public function show(Request $request, GapAnalysis $gapAnalysis): Response
    {
        abort_unless($gapAnalysis->user_id === $request->user()->id, 403);

        $gapAnalysis->load('idealCandidateProfile.jobPosting');

        $experiences = $request->user()
            ->experiences()
            ->with(['accomplishments', 'skills'])
            ->orderBy('started_at', 'desc')
            ->get();

        return Inertia::render('gap-analyses/show', [
            'gapAnalysis' => $gapAnalysis,
            'experiences' => $experiences,
        ]);
    }

    public function reanalyze(Request $request, GapAnalysis $gapAnalysis): RedirectResponse
    {
        abort_unless($gapAnalysis->user_id === $request->user()->id, 403);

        $gapAnalysis->update([
            'previous_match_score' => $gapAnalysis->overall_match_score,
            'strengths' => [],
            'gaps' => [],
            'overall_match_score' => null,
            'ai_summary' => null,
        ]);

        PerformGapAnalysisJob::dispatch($gapAnalysis);

        return to_route('gap-analyses.show', $gapAnalysis)
            ->with('success', 'Re-analyzing your profile...');
    }
}
