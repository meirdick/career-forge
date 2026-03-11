<?php

namespace App\Http\Controllers\ExperienceLibrary;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExperienceLibrary\StoreEvidenceEntryRequest;
use App\Http\Requests\ExperienceLibrary\UpdateEvidenceEntryRequest;
use App\Jobs\DiscoverPortfolioLinksJob;
use App\Jobs\IndexLinkJob;
use App\Models\EvidenceEntry;
use App\Services\ExperienceImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        $indexResults = [];
        $discoverResults = [];
        foreach ($entries as $entry) {
            if ($entry->url) {
                $cached = Cache::get("evidence-index:{$entry->id}");
                if ($cached) {
                    $indexResults[$entry->id] = $cached;
                }
                $discoverCached = Cache::get("evidence-discover:{$entry->id}");
                if ($discoverCached) {
                    $discoverResults[$entry->id] = $discoverCached;
                }
            }
        }

        return Inertia::render('experience-library/evidence', [
            'entries' => $entries,
            'indexResults' => $indexResults,
            'discoverResults' => $discoverResults,
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

    public function indexLink(Request $request, EvidenceEntry $evidenceEntry): RedirectResponse
    {
        abort_unless($evidenceEntry->user_id === $request->user()->id, 403);
        abort_unless($evidenceEntry->url, 422, 'Link has no URL.');

        Cache::put("evidence-index:{$evidenceEntry->id}", [
            'status' => 'processing',
        ], now()->addHour());

        IndexLinkJob::dispatch($request->user(), $evidenceEntry);

        return back();
    }

    public function importResults(Request $request, EvidenceEntry $evidenceEntry, ExperienceImportService $importService): RedirectResponse
    {
        abort_unless($evidenceEntry->user_id === $request->user()->id, 403);

        $cached = Cache::get("evidence-index:{$evidenceEntry->id}");
        abort_unless($cached && $cached['status'] === 'completed' && isset($cached['data']), 422, 'No completed index results to import.');

        $stats = $importService->import($request->user(), $cached['data']);

        Cache::put("evidence-index:{$evidenceEntry->id}", [
            'status' => 'imported',
            'data' => $cached['data'],
        ], now()->addYear());

        return back()->with('success', ExperienceImportService::buildImportMessage($stats));
    }

    public function discoverLinks(Request $request, EvidenceEntry $evidenceEntry): RedirectResponse
    {
        abort_unless($evidenceEntry->user_id === $request->user()->id, 403);
        abort_unless($evidenceEntry->url, 422, 'Link has no URL.');

        Cache::put("evidence-discover:{$evidenceEntry->id}", [
            'status' => 'processing',
        ], now()->addHour());

        DiscoverPortfolioLinksJob::dispatch($evidenceEntry);

        return back();
    }

    public function saveSelectedPages(Request $request, EvidenceEntry $evidenceEntry): RedirectResponse
    {
        abort_unless($evidenceEntry->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'urls' => ['required', 'array', 'min:1'],
            'urls.*' => ['required', 'url'],
        ]);

        $evidenceEntry->update(['pages' => $validated['urls']]);

        $count = count($validated['urls']);

        return back()->with('success', "{$count} page(s) saved for indexing.");
    }

    public function destroy(Request $request, EvidenceEntry $evidenceEntry): RedirectResponse
    {
        abort_unless($evidenceEntry->user_id === $request->user()->id, 403);

        $evidenceEntry->delete();

        return to_route('evidence.index')
            ->with('success', 'Link deleted.');
    }
}
