<?php

namespace App\Http\Controllers\ExperienceLibrary;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExperienceLibrary\StoreProjectRequest;
use App\Http\Requests\ExperienceLibrary\UpdateProjectRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = $request->user()->projects()->create($request->validated());

        if ($request->has('skill_ids')) {
            $project->skills()->sync($request->input('skill_ids', []));
        }

        $route = $project->experience_id
            ? to_route('experiences.show', $project->experience_id)
            : to_route('experience-library.index');

        return $route->with('success', 'Project created.');
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        abort_unless($project->user_id === $request->user()->id, 403);

        $project->update($request->validated());

        if ($request->has('skill_ids')) {
            $project->skills()->sync($request->input('skill_ids', []));
        }

        $route = $project->experience_id
            ? to_route('experiences.show', $project->experience_id)
            : to_route('experience-library.index');

        return $route->with('success', 'Project updated.');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        abort_unless($project->user_id === $request->user()->id, 403);

        $experienceId = $project->experience_id;
        $project->delete();

        $route = $experienceId
            ? to_route('experiences.show', $experienceId)
            : to_route('experience-library.index');

        return $route->with('success', 'Project deleted.');
    }
}
