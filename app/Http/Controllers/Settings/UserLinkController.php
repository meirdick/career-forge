<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UserLinkRequest;
use App\Models\UserLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserLinkController extends Controller
{
    public function store(UserLinkRequest $request): RedirectResponse
    {
        $request->user()->links()->create($request->validated());

        return back()->with('success', 'Link added.');
    }

    public function update(UserLinkRequest $request, UserLink $userLink): RedirectResponse
    {
        abort_unless($userLink->user_id === $request->user()->id, 403);

        $userLink->update($request->validated());

        return back()->with('success', 'Link updated.');
    }

    public function destroy(Request $request, UserLink $userLink): RedirectResponse
    {
        abort_unless($userLink->user_id === $request->user()->id, 403);

        $userLink->delete();

        return back()->with('success', 'Link removed.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        foreach ($validated['order'] as $index => $id) {
            $request->user()->links()->where('id', $id)->update(['sort_order' => $index]);
        }

        return back();
    }
}
