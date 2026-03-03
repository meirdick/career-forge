<?php

namespace App\Http\Controllers\ExperienceLibrary;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExperienceLibrary\StoreExperienceRequest;
use App\Http\Requests\ExperienceLibrary\UpdateExperienceRequest;
use App\Models\Experience;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExperienceController extends Controller
{
    public function index(Request $request): Response
    {
        $experiences = $request->user()
            ->experiences()
            ->with(['accomplishments', 'projects', 'skills'])
            ->orderBy('is_current', 'desc')
            ->orderByDesc('started_at')
            ->get();

        return Inertia::render('experience-library/index', [
            'experiences' => $experiences,
        ]);
    }

    public function create(Request $request): Response
    {
        $skills = $request->user()->skills()->orderBy('name')->get();

        return Inertia::render('experience-library/experiences/create', [
            'skills' => $skills,
        ]);
    }

    public function store(StoreExperienceRequest $request): RedirectResponse
    {
        $experience = $request->user()->experiences()->create($request->validated());

        if ($request->has('skill_ids')) {
            $experience->skills()->sync($request->input('skill_ids', []));
        }

        return to_route('experiences.show', $experience)
            ->with('success', 'Experience created.');
    }

    public function show(Request $request, Experience $experience): Response
    {
        abort_unless($experience->user_id === $request->user()->id, 403);

        $experience->load(['accomplishments.skills', 'projects.skills', 'skills', 'tags', 'documents']);

        return Inertia::render('experience-library/experiences/show', [
            'experience' => $experience,
        ]);
    }

    public function edit(Request $request, Experience $experience): Response
    {
        abort_unless($experience->user_id === $request->user()->id, 403);

        $experience->load('skills');
        $skills = $request->user()->skills()->orderBy('name')->get();

        return Inertia::render('experience-library/experiences/edit', [
            'experience' => $experience,
            'skills' => $skills,
        ]);
    }

    public function update(UpdateExperienceRequest $request, Experience $experience): RedirectResponse
    {
        abort_unless($experience->user_id === $request->user()->id, 403);

        $experience->update($request->validated());

        if ($request->has('skill_ids')) {
            $experience->skills()->sync($request->input('skill_ids', []));
        }

        return to_route('experiences.show', $experience)
            ->with('success', 'Experience updated.');
    }

    public function destroy(Request $request, Experience $experience): RedirectResponse
    {
        abort_unless($experience->user_id === $request->user()->id, 403);

        $experience->delete();

        return to_route('experience-library.index')
            ->with('success', 'Experience deleted.');
    }
}
