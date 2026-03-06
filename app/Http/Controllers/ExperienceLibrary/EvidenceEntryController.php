<?php

namespace App\Http\Controllers\ExperienceLibrary;

use App\Ai\Agents\LinkIndexer;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExperienceLibrary\StoreEvidenceEntryRequest;
use App\Http\Requests\ExperienceLibrary\UpdateEvidenceEntryRequest;
use App\Models\EvidenceEntry;
use App\Services\WebScraperService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EvidenceEntryController extends Controller
{
    public function index(Request $request): Response
    {
        $entries = $request->user()
            ->evidenceEntries()
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('experience-library/evidence', [
            'entries' => $entries,
        ]);
    }

    public function store(StoreEvidenceEntryRequest $request): RedirectResponse
    {
        $request->user()->evidenceEntries()->create($request->validated());

        return to_route('evidence.index')
            ->with('success', 'Link added.');
    }

    public function update(UpdateEvidenceEntryRequest $request, EvidenceEntry $evidenceEntry): RedirectResponse
    {
        abort_unless($evidenceEntry->user_id === $request->user()->id, 403);

        $evidenceEntry->update($request->validated());

        return to_route('evidence.index')
            ->with('success', 'Link updated.');
    }

    public function indexLink(Request $request, EvidenceEntry $evidenceEntry, WebScraperService $scraper): JsonResponse
    {
        abort_unless($evidenceEntry->user_id === $request->user()->id, 403);
        abort_unless($evidenceEntry->url, 422, 'Link has no URL.');

        $content = $scraper->scrape($evidenceEntry->url);

        if (! $content) {
            return response()->json(['error' => 'Could not fetch URL content.'], 422);
        }

        $response = (new LinkIndexer)->prompt("Analyze this web page content and extract professional information:\n\n{$content}");

        return response()->json([
            'skills' => $response['skills'] ?? [],
            'accomplishments' => $response['accomplishments'] ?? [],
            'projects' => $response['projects'] ?? [],
        ]);
    }

    public function destroy(Request $request, EvidenceEntry $evidenceEntry): RedirectResponse
    {
        abort_unless($evidenceEntry->user_id === $request->user()->id, 403);

        $evidenceEntry->delete();

        return to_route('evidence.index')
            ->with('success', 'Link deleted.');
    }
}
