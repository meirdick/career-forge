<?php

namespace App\Http\Controllers\ExperienceLibrary;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExperienceLibrary\UpdateProfessionalIdentityRequest;
use App\Services\ResumeHeaderService;
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
            'user' => [
                'name' => $request->user()->name,
                'legal_name' => $request->user()->legal_name,
            ],
            'resumeHeaderConfig' => $identity?->resume_header_config ?? ResumeHeaderService::defaults(),
        ]);
    }

    public function update(UpdateProfessionalIdentityRequest $request): RedirectResponse
    {
        $request->user()->professionalIdentity()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $request->safe()->except(['legal_name']),
        );

        if ($request->has('legal_name')) {
            $request->user()->update(['legal_name' => $request->input('legal_name')]);
        }

        return to_route('identity.edit')
            ->with('success', 'Professional identity updated.');
    }
}
