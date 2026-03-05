<?php

namespace App\Http\Controllers;

use App\Ai\Agents\CoverLetterWriter;
use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Http\JsonResponse;
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

    public function updateCoverLetter(Request $request, Application $application): JsonResponse
    {
        abort_unless($application->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'cover_letter' => 'required|string|max:50000',
        ]);

        $application->update(['cover_letter' => $validated['cover_letter']]);

        return response()->json(['success' => true]);
    }

    public function generateCoverLetter(Request $request, Application $application): JsonResponse
    {
        abort_unless($application->user_id === $request->user()->id, 403);

        $context = $this->buildWritingContext($request, $application);

        $response = (new CoverLetterWriter(context: $context))
            ->prompt('Write a cover letter for this position.');

        $application->update(['cover_letter' => $response->text]);

        return response()->json(['cover_letter' => $response->text]);
    }

    public function generateEmail(Request $request, Application $application): JsonResponse
    {
        abort_unless($application->user_id === $request->user()->id, 403);

        $context = $this->buildWritingContext($request, $application);
        $coverLetter = $application->cover_letter;

        $prompt = $coverLetter
            ? "Write a brief, professional submission email for this application. The cover letter is already written and will be attached. Keep the email body short — just introduce yourself, mention the role, and reference the attached cover letter and resume.\n\nCover letter:\n{$coverLetter}"
            : 'Write a professional submission email for this application. Include a brief introduction, mention the role, and highlight 2-3 key qualifications.';

        $response = (new CoverLetterWriter(context: $context))
            ->prompt($prompt);

        $application->update(['submission_email' => $response->text]);

        return response()->json(['submission_email' => $response->text]);
    }

    private function buildWritingContext(Request $request, Application $application): string
    {
        $application->load(['jobPosting', 'resume.sections.variants']);
        $user = $request->user();

        $contactInfo = collect([
            'Name' => $user->name,
            'Email' => $user->email,
            'Phone' => $user->phone,
            'Location' => $user->location,
            'LinkedIn' => $user->linkedin_url,
            'Portfolio' => $user->portfolio_url,
        ])->filter()->map(fn ($value, $label) => "{$label}: {$value}")->join("\n");

        $parts = ["Candidate Contact Information:\n{$contactInfo}"];

        $parts[] = "Company: {$application->company}";
        $parts[] = "Role: {$application->role}";

        if ($application->jobPosting) {
            $parts[] = "Job Posting:\n{$application->jobPosting->raw_text}";
        }

        if ($application->resume) {
            $sections = $application->resume->sections->map(function ($section) {
                $variant = $section->variants->firstWhere('is_selected', true) ?? $section->variants->first();

                return $variant ? "{$section->heading}:\n{$variant->content}" : null;
            })->filter()->join("\n\n");

            $parts[] = "Resume:\n{$sections}";
        }

        $identity = $user->professionalIdentity;
        if ($identity) {
            $identityParts = collect([
                'Values' => $identity->values,
                'Philosophy' => $identity->philosophy,
                'Passions' => $identity->passions,
                'Leadership Style' => $identity->leadership_style,
                'Collaboration Approach' => $identity->collaboration_approach,
                'Communication Style' => $identity->communication_style,
                'Cultural Preferences' => $identity->cultural_preferences,
            ])->filter()->map(fn ($value, $label) => "{$label}: {$value}")->join("\n");

            $parts[] = "Professional Identity:\n{$identityParts}";
        }

        return implode("\n\n", $parts);
    }

    public function destroy(Request $request, Application $application): RedirectResponse
    {
        abort_unless($application->user_id === $request->user()->id, 403);

        $application->delete();

        return to_route('applications.index')
            ->with('success', 'Application deleted.');
    }
}
