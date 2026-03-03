<?php

namespace App\Http\Controllers\ExperienceLibrary;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExperienceLibrary\StoreSkillRequest;
use App\Http\Requests\ExperienceLibrary\UpdateSkillRequest;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SkillController extends Controller
{
    public function index(Request $request): Response
    {
        $skills = $request->user()
            ->skills()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        return Inertia::render('experience-library/skills', [
            'skillsByCategory' => $skills,
        ]);
    }

    public function store(StoreSkillRequest $request): RedirectResponse
    {
        $request->user()->skills()->create($request->validated());

        return to_route('skills.index')
            ->with('success', 'Skill added.');
    }

    public function update(UpdateSkillRequest $request, Skill $skill): RedirectResponse
    {
        abort_unless($skill->user_id === $request->user()->id, 403);

        $skill->update($request->validated());

        return to_route('skills.index')
            ->with('success', 'Skill updated.');
    }

    public function destroy(Request $request, Skill $skill): RedirectResponse
    {
        abort_unless($skill->user_id === $request->user()->id, 403);

        $skill->delete();

        return to_route('skills.index')
            ->with('success', 'Skill deleted.');
    }
}
