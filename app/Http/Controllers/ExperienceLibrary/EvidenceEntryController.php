<?php

namespace App\Http\Controllers\ExperienceLibrary;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExperienceLibrary\StoreEvidenceEntryRequest;
use App\Http\Requests\ExperienceLibrary\UpdateEvidenceEntryRequest;
use App\Models\EvidenceEntry;
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
            ->with('success', 'Evidence entry added.');
    }

    public function update(UpdateEvidenceEntryRequest $request, EvidenceEntry $evidenceEntry): RedirectResponse
    {
        abort_unless($evidenceEntry->user_id === $request->user()->id, 403);

        $evidenceEntry->update($request->validated());

        return to_route('evidence.index')
            ->with('success', 'Evidence entry updated.');
    }

    public function destroy(Request $request, EvidenceEntry $evidenceEntry): RedirectResponse
    {
        abort_unless($evidenceEntry->user_id === $request->user()->id, 403);

        $evidenceEntry->delete();

        return to_route('evidence.index')
            ->with('success', 'Evidence entry deleted.');
    }
}
