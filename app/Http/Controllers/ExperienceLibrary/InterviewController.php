<?php

namespace App\Http\Controllers\ExperienceLibrary;

use App\Ai\Agents\InterviewCoach;
use App\Http\Controllers\Controller;
use App\Services\ExperienceLibraryContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InterviewController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('experience-library/interview');
    }

    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'conversation_id' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $conversationId = $request->input('conversation_id');
        $experienceContext = ExperienceLibraryContextService::buildContext($user);
        $coach = new InterviewCoach(experienceContext: $experienceContext);

        if ($conversationId) {
            $response = $coach
                ->continue($conversationId, as: $user)
                ->prompt($request->input('message'));
        } else {
            $response = $coach
                ->forUser($user)
                ->prompt($request->input('message'));
        }

        return response()->json([
            'message' => $response->text,
            'conversation_id' => $response->conversationId,
        ]);
    }
}
