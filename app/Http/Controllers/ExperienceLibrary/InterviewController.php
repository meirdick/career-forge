<?php

namespace App\Http\Controllers\ExperienceLibrary;

use App\Ai\Agents\InterviewCoach;
use App\Http\Controllers\Controller;
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

        if ($conversationId) {
            $response = (new InterviewCoach)
                ->continue($conversationId, as: $user)
                ->prompt($request->input('message'));
        } else {
            $response = (new InterviewCoach)
                ->forUser($user)
                ->prompt($request->input('message'));
        }

        return response()->json([
            'message' => $response->text,
            'conversation_id' => $response->conversationId,
        ]);
    }
}
