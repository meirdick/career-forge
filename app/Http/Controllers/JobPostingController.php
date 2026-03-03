<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobPostingRequest;
use App\Http\Requests\UpdateJobPostingRequest;
use App\Jobs\AnalyzeJobPostingJob;
use App\Models\JobPosting;
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

        AnalyzeJobPostingJob::dispatch($posting);

        return to_route('job-postings.show', $posting)
            ->with('success', 'Job posting created. Analysis in progress...');
    }

    public function show(Request $request, JobPosting $jobPosting): Response
    {
        abort_unless($jobPosting->user_id === $request->user()->id, 403);

        $jobPosting->load('idealCandidateProfile');

        return Inertia::render('job-postings/show', [
            'posting' => $jobPosting,
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

        $jobPosting->update($request->validated());

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

    public function reanalyze(Request $request, JobPosting $jobPosting): RedirectResponse
    {
        abort_unless($jobPosting->user_id === $request->user()->id, 403);

        $jobPosting->update(['analyzed_at' => null]);
        AnalyzeJobPostingJob::dispatch($jobPosting);

        return to_route('job-postings.show', $jobPosting)
            ->with('success', 'Re-analysis started...');
    }
}
