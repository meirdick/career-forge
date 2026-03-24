<?php

namespace App\Http\Controllers;

use App\Ai\Agents\CoverLetterWriter;
use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Services\CoverLetterContextBuilder;
use App\Services\ResumeExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApplicationController extends Controller
{
    public function __construct(
        private CoverLetterContextBuilder $contextBuilder,
        private ResumeExportService $exportService,
    ) {}

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

        $context = $this->contextBuilder->build($request->user(), $application);

        $response = (new CoverLetterWriter(context: $context))
            ->prompt('Write a cover letter for this position.');

        $application->update(['cover_letter' => $response->text]);

        return response()->json(['cover_letter' => $response->text]);
    }

    public function generateEmail(Request $request, Application $application): JsonResponse
    {
        abort_unless($application->user_id === $request->user()->id, 403);

        $context = $this->contextBuilder->build($request->user(), $application);
        $coverLetter = $application->cover_letter;

        $prompt = $coverLetter
            ? "Write a brief, professional submission email for this application. The cover letter is already written and will be attached. Keep the email body short — just introduce yourself, mention the role, and reference the attached cover letter and resume.\n\nCover letter:\n{$coverLetter}"
            : 'Write a professional submission email for this application. Include a brief introduction, mention the role, and highlight 2-3 key qualifications.';

        $response = (new CoverLetterWriter(context: $context))
            ->prompt($prompt);

        $application->update(['submission_email' => $response->text]);

        return response()->json(['submission_email' => $response->text]);
    }

    public function exportCoverLetter(Request $request, Application $application, string $format): BinaryFileResponse
    {
        abort_unless($application->user_id === $request->user()->id, 403);
        abort_unless(in_array($format, ['pdf', 'docx']), 404);
        abort_unless($application->cover_letter, 404);

        $path = $format === 'pdf'
            ? $this->exportService->coverLetterToPdf($application)
            : $this->exportService->coverLetterToDocx($application);

        $fullPath = storage_path('app/private/'.$path);
        $filename = 'Cover_Letter_'.str_replace(' ', '_', $application->company).'.'.$format;

        return response()->download($fullPath, $filename);
    }

    public function destroy(Request $request, Application $application): RedirectResponse
    {
        abort_unless($application->user_id === $request->user()->id, 403);

        $application->delete();

        return to_route('applications.index')
            ->with('success', 'Application deleted.');
    }
}
