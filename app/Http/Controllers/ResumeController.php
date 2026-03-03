<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateResumeJob;
use App\Models\GapAnalysis;
use App\Models\Resume;
use App\Models\ResumeSection;
use App\Models\ResumeSectionVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return Inertia::render('resumes/index', [
            'resumes' => $resumes,
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
        ]);

        GenerateResumeJob::dispatch($resume);

        return to_route('resumes.show', $resume)
            ->with('success', 'Resume generation started...');
    }

    public function show(Request $request, Resume $resume): Response
    {
        abort_unless($resume->user_id === $request->user()->id, 403);

        $resume->load(['sections.variants', 'sections.selectedVariant', 'jobPosting', 'gapAnalysis']);

        return Inertia::render('resumes/show', [
            'resume' => $resume,
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
        ]);

        $resume->update($request->only(['title', 'section_order', 'is_finalized']));

        if ($request->has('section_order')) {
            foreach ($request->input('section_order') as $index => $sectionId) {
                ResumeSection::where('id', $sectionId)
                    ->where('resume_id', $resume->id)
                    ->update(['sort_order' => $index]);
            }
        }

        return to_route('resumes.show', $resume)
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

    public function destroy(Request $request, Resume $resume): RedirectResponse
    {
        abort_unless($resume->user_id === $request->user()->id, 403);

        $resume->delete();

        return to_route('resumes.index')
            ->with('success', 'Resume deleted.');
    }
}
