<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkStoreJobPostingRequest;
use App\Http\Requests\StoreJobPostingRequest;
use App\Http\Requests\UpdateJobPostingRequest;
use App\Jobs\AnalyzeJobPostingJob;
use App\Jobs\FetchJobPostingUrlJob;
use App\Models\JobPosting;
use App\Rules\SupportedScrapingUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobPostingController extends Controller
{
    public function index(Request $request): Response
    {
        $postings = $request->user()
            ->jobPostings()
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('job-postings/index', [
            'postings' => $postings,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('job-postings/create');
    }

    public function store(StoreJobPostingRequest $request): RedirectResponse
    {
        $posting = $request->user()->jobPostings()->create($request->validated());

        if ($posting->url && ! $posting->raw_text) {
            FetchJobPostingUrlJob::dispatch($posting);

            return to_route('job-postings.show', $posting)
                ->with('success', 'Job posting created. Fetching content from URL...');
        }

        AnalyzeJobPostingJob::dispatch($posting);

        return to_route('job-postings.show', $posting)
            ->with('success', 'Job posting created. Analysis in progress...');
    }

    public function quickStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048', new SupportedScrapingUrl],
        ]);

        $posting = $request->user()->jobPostings()->create($validated);

        FetchJobPostingUrlJob::dispatch($posting);

        return to_route('job-postings.index')
            ->with('success', 'Job posting created. Fetching content from URL...');
    }

    public function bulkStore(BulkStoreJobPostingRequest $request): RedirectResponse
    {
        $urls = $request->validated('urls');

        foreach ($urls as $url) {
            $posting = $request->user()->jobPostings()->create(['url' => $url]);
            FetchJobPostingUrlJob::dispatch($posting);
        }

        $count = count($urls);

        return to_route('job-postings.index')
            ->with('success', "{$count} job posting(s) created. Fetching content from URLs...");
    }

    public function show(Request $request, JobPosting $jobPosting): Response
    {
        abort_unless($jobPosting->user_id === $request->user()->id, 403);

        $jobPosting->load('idealCandidateProfile');

        $latestGapAnalysis = $jobPosting->idealCandidateProfile
            ?->gapAnalyses()
            ->latest()
            ->first(['id', 'overall_match_score', 'created_at']);

        return Inertia::render('job-postings/show', [
            'posting' => $jobPosting,
            'latestGapAnalysis' => $latestGapAnalysis,
        ]);
    }

    public function edit(Request $request, JobPosting $jobPosting): Response
    {
        abort_unless($jobPosting->user_id === $request->user()->id, 403);

        return Inertia::render('job-postings/edit', [
            'posting' => $jobPosting,
        ]);
    }

    public function update(UpdateJobPostingRequest $request, JobPosting $jobPosting): RedirectResponse
    {
        abort_unless($jobPosting->user_id === $request->user()->id, 403);

        $rawTextChanged = $jobPosting->raw_text !== $request->validated('raw_text');

        $jobPosting->update($request->validated());

        if ($rawTextChanged && filled($jobPosting->raw_text)) {
            $jobPosting->update(['analyzed_at' => null]);
            AnalyzeJobPostingJob::dispatch($jobPosting);
        }

        return to_route('job-postings.show', $jobPosting)
            ->with('success', 'Job posting updated.');
    }

    public function destroy(Request $request, JobPosting $jobPosting): RedirectResponse
    {
        abort_unless($jobPosting->user_id === $request->user()->id, 403);

        $jobPosting->delete();

        return to_route('job-postings.index')
            ->with('success', 'Job posting deleted.');
    }

    public function updateProfile(Request $request, JobPosting $jobPosting): RedirectResponse
    {
        abort_unless($jobPosting->user_id === $request->user()->id, 403);
        abort_unless($jobPosting->idealCandidateProfile !== null, 404);

        $validated = $request->validate([
            'required_skills' => 'nullable|array',
            'preferred_skills' => 'nullable|array',
            'experience_profile' => 'nullable|array',
            'cultural_fit' => 'nullable|array',
            'language_guidance' => 'nullable|array',
            'red_flags' => 'nullable|array',
        ]);

        $jobPosting->idealCandidateProfile->update([
            ...$validated,
            'is_user_edited' => true,
        ]);

        return to_route('job-postings.show', $jobPosting)
            ->with('success', 'Ideal candidate profile updated.');
    }

    public function reanalyze(Request $request, JobPosting $jobPosting): RedirectResponse
    {
        abort_unless($jobPosting->user_id === $request->user()->id, 403);

        $jobPosting->update(['analyzed_at' => null]);
        AnalyzeJobPostingJob::dispatch($jobPosting);

        return to_route('job-postings.show', $jobPosting)
            ->with('success', 'Re-analysis started...');
    }
}
