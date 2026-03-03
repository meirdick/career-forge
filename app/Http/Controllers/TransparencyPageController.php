<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\TransparencyPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TransparencyPageController extends Controller
{
    public function show(Request $request, Application $application): Response
    {
        abort_unless($application->user_id === $request->user()->id, 403);

        $page = $application->transparencyPage;

        if (! $page) {
            $page = $application->transparencyPage()->create([
                'user_id' => $request->user()->id,
                'slug' => Str::slug($application->company.'-'.$application->role.'-'.Str::random(6)),
                'authorship_statement' => 'This resume was created with the assistance of CareerForge, an AI-powered career tools application. All content is based on my actual professional experience and has been reviewed and approved by me.',
                'research_summary' => $this->buildResearchSummary($application),
                'ideal_profile_summary' => $this->buildProfileSummary($application),
                'section_decisions' => $this->buildSectionDecisions($application),
                'tool_description' => 'CareerForge — an open-source, AI-powered career management tool that helps job seekers organize their experience, analyze job postings, and generate tailored resumes with full transparency.',
                'is_published' => false,
            ]);
        }

        $application->load(['jobPosting', 'resume']);

        $viewCount = $page->views()->count();
        $recentViews = $page->views()
            ->orderByDesc('viewed_at')
            ->limit(10)
            ->get(['viewed_at', 'referer']);

        return Inertia::render('transparency/show', [
            'application' => $application,
            'page' => $page,
            'viewCount' => $viewCount,
            'recentViews' => $recentViews,
        ]);
    }

    public function update(Request $request, Application $application): RedirectResponse
    {
        abort_unless($application->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'authorship_statement' => 'nullable|string',
            'research_summary' => 'nullable|string',
            'ideal_profile_summary' => 'nullable|string',
            'section_decisions' => 'nullable|array',
            'tool_description' => 'nullable|string',
            'repository_url' => 'nullable|url|max:255',
        ]);

        $application->transparencyPage->update($validated);

        return to_route('transparency.show', $application)
            ->with('success', 'Transparency page updated.');
    }

    public function publish(Request $request, Application $application): RedirectResponse
    {
        abort_unless($application->user_id === $request->user()->id, 403);

        $page = $application->transparencyPage;
        abort_unless($page !== null, 404);

        $page->update([
            'is_published' => true,
            'content_html' => $this->renderHtml($page),
        ]);

        return to_route('transparency.show', $application)
            ->with('success', 'Transparency page published.');
    }

    public function publicPage(Request $request, TransparencyPage $transparencyPage): Response
    {
        abort_unless($transparencyPage->is_published, 404);

        $transparencyPage->views()->create([
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),
            'viewed_at' => now(),
        ]);

        return Inertia::render('transparency/public', [
            'page' => $transparencyPage,
        ]);
    }

    private function buildResearchSummary(Application $application): string
    {
        $jobPosting = $application->jobPosting;
        if (! $jobPosting) {
            return '';
        }

        $parts = ["Applied for {$jobPosting->title} at {$jobPosting->company}."];
        if ($jobPosting->location) {
            $parts[] = "Location: {$jobPosting->location}.";
        }
        if ($jobPosting->seniority_level) {
            $parts[] = "Level: {$jobPosting->seniority_level}.";
        }
        if ($jobPosting->analyzed_at) {
            $parts[] = 'AI-assisted analysis of job requirements was performed to identify key qualifications and match criteria.';
        }

        return implode(' ', $parts);
    }

    private function buildProfileSummary(Application $application): string
    {
        $resume = $application->resume;
        if (! $resume) {
            return '';
        }

        $gapAnalysis = $resume->gapAnalysis;
        if (! $gapAnalysis) {
            return 'Resume was generated to match the job posting requirements.';
        }

        $parts = [];
        if ($gapAnalysis->overall_match_score) {
            $parts[] = "Overall match score: {$gapAnalysis->overall_match_score}%.";
        }
        if (! empty($gapAnalysis->strengths)) {
            $count = count($gapAnalysis->strengths);
            $parts[] = "{$count} key strength(s) identified.";
        }
        if (! empty($gapAnalysis->gaps)) {
            $count = count($gapAnalysis->gaps);
            $parts[] = "{$count} gap(s) identified and addressed in the resume.";
        }

        return implode(' ', $parts);
    }

    /**
     * @return array<int, array{section: string, variant: string, reason: string}>
     */
    private function buildSectionDecisions(Application $application): array
    {
        $resume = $application->resume;
        if (! $resume) {
            return [];
        }

        $resume->load('sections.selectedVariant');
        $decisions = [];

        foreach ($resume->sections as $section) {
            $variant = $section->selectedVariant;
            if (! $variant) {
                continue;
            }

            $decisions[] = [
                'section' => $section->title,
                'variant' => $variant->label,
                'reason' => $variant->is_user_edited ? 'User edited the AI-generated content' : ($variant->is_ai_generated ? 'AI-generated, reviewed and approved' : 'User-authored content'),
            ];
        }

        return $decisions;
    }

    private function renderHtml(TransparencyPage $page): string
    {
        $html = '<h1>AI Transparency Statement</h1>';
        $html .= '<h2>Authorship Statement</h2><p>'.e($page->authorship_statement).'</p>';
        $html .= '<h2>Research Summary</h2><p>'.e($page->research_summary).'</p>';
        $html .= '<h2>Ideal Candidate Profile Summary</h2><p>'.e($page->ideal_profile_summary).'</p>';

        if (! empty($page->section_decisions)) {
            $html .= '<h2>Section Decisions</h2><ul>';
            foreach ($page->section_decisions as $decision) {
                $section = e($decision['section'] ?? '');
                $variant = e($decision['variant'] ?? '');
                $reason = e($decision['reason'] ?? '');
                $html .= "<li><strong>{$section}</strong> - {$variant}: {$reason}</li>";
            }
            $html .= '</ul>';
        }

        if ($page->tool_description) {
            $html .= '<h2>Tools Used</h2><p>'.e($page->tool_description).'</p>';
        }

        if ($page->repository_url) {
            $html .= '<p>Repository: <a href="'.e($page->repository_url).'">'.e($page->repository_url).'</a></p>';
        }

        return $html;
    }
}
