<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\Resume;
use App\Services\ResumeExportService;
use App\Services\ResumeHeaderService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ResumeExportController extends Controller
{
    public function preview(Request $request, Resume $resume, ResumeHeaderService $headerService): Response
    {
        abort_unless($resume->user_id === $request->user()->id, 403);

        $resume->load(['sections.selectedVariant', 'jobPosting']);

        return Inertia::render('resumes/preview', [
            'resume' => $resume,
            'contact' => $headerService->resolveHeader($resume),
        ]);
    }

    public function export(Request $request, Resume $resume, string $format, ResumeExportService $exporter): BinaryFileResponse
    {
        abort_unless($resume->user_id === $request->user()->id, 403);
        abort_unless(in_array($format, ['pdf', 'docx']), 404);

        $path = match ($format) {
            'pdf' => $exporter->toPdf($resume),
            'docx' => $exporter->toDocx($resume),
        };

        $resume->update([
            'exported_path' => $path,
            'exported_format' => $format,
        ]);

        $fullPath = storage_path('app/private/'.$path);
        $filename = str($resume->title)->slug().".{$format}";

        return response()->download($fullPath, $filename);
    }

    public function finalize(Request $request, Resume $resume): \Illuminate\Http\RedirectResponse
    {
        abort_unless($resume->user_id === $request->user()->id, 403);

        $resume->update(['is_finalized' => true]);

        $jobPosting = $resume->jobPosting;

        $application = $request->user()->applications()->create([
            'job_posting_id' => $jobPosting?->id,
            'resume_id' => $resume->id,
            'status' => ApplicationStatus::Draft,
            'company' => $jobPosting?->company ?? 'Unknown',
            'role' => $jobPosting?->title ?? $resume->title,
        ]);

        $application->statusChanges()->create([
            'to_status' => ApplicationStatus::Draft,
        ]);

        return to_route('applications.show', $application)
            ->with('success', 'Resume finalized and application created.');
    }
}
