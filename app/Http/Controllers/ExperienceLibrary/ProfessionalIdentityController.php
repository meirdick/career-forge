<?php

namespace App\Http\Controllers\ExperienceLibrary;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExperienceLibrary\UpdateProfessionalIdentityRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfessionalIdentityController extends Controller
{
    public function edit(Request $request): Response
    {
        $identity = $request->user()->professionalIdentity;

        return Inertia::render('experience-library/identity', [
            'identity' => $identity,
        ]);
    }

    public function update(UpdateProfessionalIdentityRequest $request): RedirectResponse
    {
        $request->user()->professionalIdentity()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $request->validated(),
        );

        return to_route('identity.edit')
            ->with('success', 'Professional identity updated.');
    }
}
