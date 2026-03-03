<?php

namespace App\Http\Controllers\ExperienceLibrary;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExperienceLibrary\StoreEducationEntryRequest;
use App\Http\Requests\ExperienceLibrary\UpdateEducationEntryRequest;
use App\Models\EducationEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EducationEntryController extends Controller
{
    public function index(Request $request): Response
    {
        $entries = $request->user()
            ->educationEntries()
            ->orderBy('sort_order')
            ->orderByDesc('completed_at')
            ->get();

        return Inertia::render('experience-library/education', [
            'entries' => $entries,
        ]);
    }

    public function store(StoreEducationEntryRequest $request): RedirectResponse
    {
        $request->user()->educationEntries()->create($request->validated());

        return to_route('education.index')
            ->with('success', 'Education entry added.');
    }

    public function update(UpdateEducationEntryRequest $request, EducationEntry $educationEntry): RedirectResponse
    {
        abort_unless($educationEntry->user_id === $request->user()->id, 403);

        $educationEntry->update($request->validated());

        return to_route('education.index')
            ->with('success', 'Education entry updated.');
    }

    public function destroy(Request $request, EducationEntry $educationEntry): RedirectResponse
    {
        abort_unless($educationEntry->user_id === $request->user()->id, 403);

        $educationEntry->delete();

        return to_route('education.index')
            ->with('success', 'Education entry deleted.');
    }
}
