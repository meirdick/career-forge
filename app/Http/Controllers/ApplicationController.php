<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends Controller
{
    public function index(Request $request): Response
    {
        $applications = $request->user()
            ->applications()
            ->with(['jobPosting', 'resume'])
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('applications/index', [
            'applications' => $applications,
        ]);
    }

    public function create(Request $request): Response
    {
        $resumes = $request->user()
            ->resumes()
            ->where('is_finalized', true)
            ->with('jobPosting')
            ->get();

        return Inertia::render('applications/create', [
            'resumes' => $resumes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'resume_id' => 'nullable|exists:resumes,id',
            'job_posting_id' => 'nullable|exists:job_postings,id',
            'company' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'cover_letter' => 'nullable|string',
            'submission_email' => 'nullable|email|max:255',
        ]);

        $application = $request->user()->applications()->create([
            ...$validated,
            'status' => ApplicationStatus::Draft,
        ]);

        $application->statusChanges()->create([
            'to_status' => ApplicationStatus::Draft,
        ]);

        return to_route('applications.show', $application)
            ->with('success', 'Application created.');
    }

    public function show(Request $request, Application $application): Response
    {
        abort_unless($application->user_id === $request->user()->id, 403);

        $application->load(['jobPosting', 'resume', 'applicationNotes', 'statusChanges', 'transparencyPage']);

        return Inertia::render('applications/show', [
            'application' => $application,
        ]);
    }

    public function edit(Request $request, Application $application): Response
    {
        abort_unless($application->user_id === $request->user()->id, 403);

        return Inertia::render('applications/edit', [
            'application' => $application,
        ]);
    }

    public function update(Request $request, Application $application): RedirectResponse
    {
        abort_unless($application->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'company' => 'sometimes|string|max:255',
            'role' => 'sometimes|string|max:255',
            'notes' => 'nullable|string',
            'cover_letter' => 'nullable|string',
            'submission_email' => 'nullable|email|max:255',
        ]);

        $application->update($validated);

        return to_route('applications.show', $application)
            ->with('success', 'Application updated.');
    }

    public function updateStatus(Request $request, Application $application): RedirectResponse
    {
        abort_unless($application->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(ApplicationStatus::class)],
            'notes' => 'nullable|string',
        ]);

        $oldStatus = $application->status;
        $newStatus = ApplicationStatus::from($validated['status']);

        $application->update([
            'status' => $newStatus,
            'applied_at' => $newStatus === ApplicationStatus::Applied && ! $application->applied_at ? now() : $application->applied_at,
        ]);

        $application->statusChanges()->create([
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'notes' => $validated['notes'] ?? null,
        ]);

        return to_route('applications.show', $application)
            ->with('success', 'Status updated.');
    }

    public function destroy(Request $request, Application $application): RedirectResponse
    {
        abort_unless($application->user_id === $request->user()->id, 403);

        $application->delete();

        return to_route('applications.index')
            ->with('success', 'Application deleted.');
    }
}
