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
        $query = $request->user()->experiences();

        if ($search = $request->input('search')) {
            $scoutResults = Experience::search($search)
                ->where('user_id', $request->user()->id)
                ->get()
                ->pluck('id');
            $query->whereIn('id', $scoutResults);
        }

        if ($skillId = $request->input('skill_id')) {
            $query->whereHas('skills', fn ($q) => $q->where('skills.id', $skillId));
        }

        if ($tagId = $request->input('tag_id')) {
            $query->whereHas('tags', fn ($q) => $q->where('tags.id', $tagId));
        }

        if ($from = $request->input('from')) {
            $query->where('started_at', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->where(function ($q) use ($to) {
                $q->whereNull('ended_at')->orWhere('ended_at', '<=', $to);
            });
        }

        $experiences = $query
            ->with(['accomplishments', 'projects', 'skills'])
            ->orderBy('is_current', 'desc')
            ->orderByDesc('started_at')
            ->get();

        $skills = $request->user()->skills()->orderBy('name')->get(['id', 'name']);
        $tags = $request->user()->tags()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('experience-library/index', [
            'experiences' => $experiences,
            'skills' => $skills,
            'tags' => $tags,
            'filters' => [
                'search' => $request->input('search', ''),
                'skill_id' => $request->input('skill_id', ''),
                'tag_id' => $request->input('tag_id', ''),
                'from' => $request->input('from', ''),
                'to' => $request->input('to', ''),
            ],
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

        $tags = $request->user()->tags()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('experience-library/experiences/show', [
            'experience' => $experience,
            'tags' => $tags,
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
