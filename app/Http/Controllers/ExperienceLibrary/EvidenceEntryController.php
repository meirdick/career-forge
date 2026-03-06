<?php

namespace App\Http\Controllers\ExperienceLibrary;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExperienceLibrary\StoreEvidenceEntryRequest;
use App\Http\Requests\ExperienceLibrary\UpdateEvidenceEntryRequest;
use App\Jobs\IndexLinkJob;
use App\Models\EvidenceEntry;
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
        foreach ($entries as $entry) {
            if ($entry->url) {
                $cached = Cache::get("evidence-index:{$entry->id}");
                if ($cached) {
                    $indexResults[$entry->id] = $cached;
                }
            }
        }

        return Inertia::render('experience-library/evidence', [
            'entries' => $entries,
            'indexResults' => $indexResults,
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

    public function destroy(Request $request, EvidenceEntry $evidenceEntry): RedirectResponse
    {
        abort_unless($evidenceEntry->user_id === $request->user()->id, 403);

        $evidenceEntry->delete();

        return to_route('evidence.index')
            ->with('success', 'Link deleted.');
    }
}
