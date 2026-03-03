<?php

namespace App\Http\Controllers\ExperienceLibrary;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExperienceLibrary\StoreAccomplishmentRequest;
use App\Http\Requests\ExperienceLibrary\UpdateAccomplishmentRequest;
use App\Models\Accomplishment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccomplishmentController extends Controller
{
    public function store(StoreAccomplishmentRequest $request): RedirectResponse
    {
        $accomplishment = $request->user()->accomplishments()->create($request->validated());

        if ($request->has('skill_ids')) {
            $accomplishment->skills()->sync($request->input('skill_ids', []));
        }

        $route = $accomplishment->experience_id
            ? to_route('experiences.show', $accomplishment->experience_id)
            : to_route('experience-library.index');

        return $route->with('success', 'Accomplishment created.');
    }

    public function update(UpdateAccomplishmentRequest $request, Accomplishment $accomplishment): RedirectResponse
    {
        abort_unless($accomplishment->user_id === $request->user()->id, 403);

        $accomplishment->update($request->validated());

        if ($request->has('skill_ids')) {
            $accomplishment->skills()->sync($request->input('skill_ids', []));
        }

        $route = $accomplishment->experience_id
            ? to_route('experiences.show', $accomplishment->experience_id)
            : to_route('experience-library.index');

        return $route->with('success', 'Accomplishment updated.');
    }

    public function destroy(Request $request, Accomplishment $accomplishment): RedirectResponse
    {
        abort_unless($accomplishment->user_id === $request->user()->id, 403);

        $experienceId = $accomplishment->experience_id;
        $accomplishment->delete();

        $route = $experienceId
            ? to_route('experiences.show', $experienceId)
            : to_route('experience-library.index');

        return $route->with('success', 'Accomplishment deleted.');
    }
}
