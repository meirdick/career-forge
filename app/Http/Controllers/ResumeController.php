<?php

namespace App\Http\Controllers;

use App\Enums\ResumeTemplate;
use App\Jobs\GenerateResumeJob;
use App\Models\GapAnalysis;
use App\Models\Resume;
use App\Models\ResumeSection;
use App\Models\ResumeSectionVariant;
use App\Services\ResumeHeaderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ResumeController extends Controller
{
    public function index(Request $request): Response
    {
        $resumes = $request->user()
            ->resumes()
            ->with('jobPosting')
            ->orderByDesc('created_at')
            ->get();

        $uploadedDocuments = $request->user()
            ->documents()
            ->where('metadata->purpose', 'resume_import')
            ->latest()
            ->get()
            ->map(fn ($doc) => [
                'id' => $doc->id,
                'filename' => $doc->filename,
                'size' => $doc->size,
                'mime_type' => $doc->mime_type,
                'created_at' => $doc->created_at->toIso8601String(),
                'download_url' => route('documents.download', $doc),
            ]);

        return Inertia::render('resumes/index', [
            'resumes' => $resumes,
            'uploadedDocuments' => $uploadedDocuments,
        ]);
    }

    public function generate(Request $request, GapAnalysis $gapAnalysis): RedirectResponse
    {
        abort_unless($gapAnalysis->user_id === $request->user()->id, 403);

        $profile = $gapAnalysis->idealCandidateProfile;
        $jobPosting = $profile->jobPosting;

        $resume = $request->user()->resumes()->create([
            'gap_analysis_id' => $gapAnalysis->id,
            'job_posting_id' => $jobPosting->id,
            'title' => ($jobPosting->title ?? 'Resume').' - '.now()->format('M j, Y'),
            'section_order' => [],
            'is_finalized' => false,
            'generation_status' => 'pending',
        ]);

        GenerateResumeJob::dispatch($resume);

        return to_route('resumes.show', $resume)
            ->with('success', 'Resume generation started...');
    }

    public function show(Request $request, Resume $resume): Response
    {
        abort_unless($resume->user_id === $request->user()->id, 403);

        $resume->load(['sections.variants', 'sections.selectedVariant', 'jobPosting', 'gapAnalysis', 'user.professionalIdentity']);

        $globalConfig = $request->user()->professionalIdentity?->resume_header_config ?? ResumeHeaderService::defaults();

        return Inertia::render('resumes/show', [
            'resume' => $resume,
            'globalHeaderConfig' => $globalConfig,
        ]);
    }

    public function update(Request $request, Resume $resume): RedirectResponse
    {
        abort_unless($resume->user_id === $request->user()->id, 403);

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'section_order' => 'sometimes|array',
            'section_order.*' => 'integer|exists:resume_sections,id',
            'is_finalized' => 'sometimes|boolean',
            'template' => ['sometimes', Rule::enum(ResumeTemplate::class)],
            'header_config' => 'sometimes|nullable|array',
            'header_config.name_preference' => 'sometimes|in:display_name,legal_name',
            'header_config.show_email' => 'sometimes|boolean',
            'header_config.show_phone' => 'sometimes|boolean',
            'header_config.show_location' => 'sometimes|boolean',
            'header_config.show_linkedin' => 'sometimes|boolean',
            'header_config.show_portfolio' => 'sometimes|boolean',
            'transparency_text' => 'sometimes|nullable|string|max:500',
            'show_transparency' => 'sometimes|boolean',
        ]);

        $resume->update($request->only(['title', 'section_order', 'is_finalized', 'template', 'header_config', 'transparency_text', 'show_transparency']));

        if ($request->has('section_order')) {
            foreach ($request->input('section_order') as $index => $sectionId) {
                ResumeSection::where('id', $sectionId)
                    ->where('resume_id', $resume->id)
                    ->update(['sort_order' => $index]);
            }
        }

        return back()
            ->with('success', 'Resume updated.');
    }

    public function selectVariant(Request $request, Resume $resume, ResumeSection $resumeSection): RedirectResponse
    {
        abort_unless($resume->user_id === $request->user()->id, 403);
        abort_unless($resumeSection->resume_id === $resume->id, 404);

        $request->validate(['variant_id' => 'required|exists:resume_section_variants,id']);

        $resumeSection->update(['selected_variant_id' => $request->input('variant_id')]);

        return to_route('resumes.show', $resume)
            ->with('success', 'Variant selected.');
    }

    public function editVariant(Request $request, Resume $resume, ResumeSectionVariant $resumeSectionVariant): RedirectResponse
    {
        abort_unless($resume->user_id === $request->user()->id, 403);

        $request->validate(['content' => 'required|string']);

        $resumeSectionVariant->update([
            'content' => $request->input('content'),
            'is_user_edited' => true,
        ]);

        return to_route('resumes.show', $resume)
            ->with('success', 'Variant updated.');
    }

    public function toggleSection(Request $request, Resume $resume, ResumeSection $resumeSection): RedirectResponse
    {
        abort_unless($resume->user_id === $request->user()->id, 403);
        abort_unless($resumeSection->resume_id === $resume->id, 404);

        $resumeSection->update(['is_hidden' => ! $resumeSection->is_hidden]);

        return back();
    }

    public function updateSection(Request $request, Resume $resume, ResumeSection $resumeSection): RedirectResponse
    {
        abort_unless($resume->user_id === $request->user()->id, 403);
        abort_unless($resumeSection->resume_id === $resume->id, 404);

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'display_mode' => 'sometimes|in:compact,expanded',
        ]);

        $resumeSection->update($request->only(['title', 'display_mode']));

        return back();
    }

    public function destroySection(Request $request, Resume $resume, ResumeSection $resumeSection): RedirectResponse
    {
        abort_unless($resume->user_id === $request->user()->id, 403);
        abort_unless($resumeSection->resume_id === $resume->id, 404);

        $sectionOrder = $resume->section_order ?? [];
        $sectionOrder = array_values(array_filter($sectionOrder, fn ($id) => $id !== $resumeSection->id));
        $resume->update(['section_order' => $sectionOrder]);

        $resumeSection->variants()->delete();
        $resumeSection->delete();

        return back();
    }

    public function updateBlocks(Request $request, Resume $resume, ResumeSectionVariant $resumeSectionVariant): RedirectResponse
    {
        abort_unless($resume->user_id === $request->user()->id, 403);

        $request->validate([
            'blocks' => 'required|array',
            'blocks.*.key' => 'required|string',
            'blocks.*.label' => 'required|string',
            'blocks.*.content' => 'required|string',
            'blocks.*.is_hidden' => 'required|boolean',
        ]);

        $resumeSectionVariant->update([
            'blocks' => $request->input('blocks'),
            'is_user_edited' => true,
        ]);

        $resumeSectionVariant->reassembleContent();
        $resumeSectionVariant->save();

        return back();
    }

    public function regenerate(Request $request, Resume $resume): RedirectResponse
    {
        abort_unless($resume->user_id === $request->user()->id, 403);
        abort_unless($resume->generation_status === 'failed', 409);

        $resume->sections()->each(function ($section) {
            $section->variants()->delete();
            $section->delete();
        });

        $resume->update([
            'generation_status' => 'pending',
            'generation_progress' => null,
            'section_order' => [],
        ]);

        GenerateResumeJob::dispatch($resume);

        return back()->with('success', 'Resume generation restarted...');
    }

    public function destroy(Request $request, Resume $resume): RedirectResponse
    {
        abort_unless($resume->user_id === $request->user()->id, 403);

        $resume->delete();

        return to_route('resumes.index')
            ->with('success', 'Resume deleted.');
    }
}
