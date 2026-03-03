<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApplicationNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApplicationNoteController extends Controller
{
    public function store(Request $request, Application $application): RedirectResponse
    {
        abort_unless($application->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $application->applicationNotes()->create($validated);

        return to_route('applications.show', $application)
            ->with('success', 'Note added.');
    }

    public function update(Request $request, Application $application, ApplicationNote $note): RedirectResponse
    {
        abort_unless($application->user_id === $request->user()->id, 403);
        abort_unless($note->application_id === $application->id, 404);

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $note->update($validated);

        return to_route('applications.show', $application)
            ->with('success', 'Note updated.');
    }

    public function destroy(Request $request, Application $application, ApplicationNote $note): RedirectResponse
    {
        abort_unless($application->user_id === $request->user()->id, 403);
        abort_unless($note->application_id === $application->id, 404);

        $note->delete();

        return to_route('applications.show', $application)
            ->with('success', 'Note deleted.');
    }
}
