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
            'is_finalized' => false,
        ]);

        PerformGapAnalysisJob::dispatch($gapAnalysis);

        return to_route('gap-analyses.show', $gapAnalysis)
            ->with('success', 'Gap analysis started...');
    }

    public function show(Request $request, GapAnalysis $gapAnalysis): Response
    {
        abort_unless($gapAnalysis->user_id === $request->user()->id, 403);

        $gapAnalysis->load('idealCandidateProfile.jobPosting');

        return Inertia::render('gap-analyses/show', [
            'gapAnalysis' => $gapAnalysis,
        ]);
    }

    public function finalize(Request $request, GapAnalysis $gapAnalysis): RedirectResponse
    {
        abort_unless($gapAnalysis->user_id === $request->user()->id, 403);

        $gapAnalysis->update(['is_finalized' => true]);

        return to_route('gap-analyses.show', $gapAnalysis)
            ->with('success', 'Gap analysis finalized.');
    }
}
