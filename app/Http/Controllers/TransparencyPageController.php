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
                'authorship_statement' => '',
                'research_summary' => '',
                'ideal_profile_summary' => '',
                'section_decisions' => [],
                'is_published' => false,
            ]);
        }

        $application->load(['jobPosting', 'resume']);

        return Inertia::render('transparency/show', [
            'application' => $application,
            'page' => $page,
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

    public function publicPage(TransparencyPage $transparencyPage): Response
    {
        abort_unless($transparencyPage->is_published, 404);

        return Inertia::render('transparency/public', [
            'page' => $transparencyPage,
        ]);
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
