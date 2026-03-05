<?php

namespace App\Http\Controllers;

use App\Ai\Agents\CareerCoach;
use App\Ai\Agents\ExperienceExtractor;
use App\Enums\ChatSessionMode;
use App\Http\Requests\ChatMessageRequest;
use App\Http\Requests\CommitExtractionRequest;
use App\Http\Requests\StoreChatSessionRequest;
use App\Models\ChatSession;
use App\Services\ExperienceImportService;
use App\Services\ExperienceLibraryContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ChatSessionController extends Controller
{
    public function index(Request $request): Response
    {
        $sessions = $request->user()->chatSessions()
            ->with('jobPosting:id,title,company')
            ->latest('updated_at')
            ->get()
            ->map(fn (ChatSession $session) => [
                'id' => $session->id,
                'title' => $session->title,
                'mode' => $session->mode->value,
                'status' => $session->status->value,
                'has_conversation' => $session->conversation_id !== null,
                'job_posting' => $session->jobPosting ? [
                    'id' => $session->jobPosting->id,
                    'title' => $session->jobPosting->title,
                    'company' => $session->jobPosting->company,
                ] : null,
                'updated_at' => $session->updated_at->diffForHumans(),
            ]);

        $jobPostings = $request->user()->jobPostings()
            ->select('id', 'title', 'company')
            ->latest()
            ->get();

        return Inertia::render('career-chat/index', [
            'sessions' => $sessions,
            'jobPostings' => $jobPostings,
        ]);
    }

    public function store(StoreChatSessionRequest $request): RedirectResponse
    {
        $mode = $request->input('mode', 'general');
        $jobPostingId = $mode === 'job_specific' ? $request->input('job_posting_id') : null;

        $title = $request->input('title');

        if (! $title && $jobPostingId) {
            $jobPosting = $request->user()->jobPostings()->find($jobPostingId);
            $title = $jobPosting ? "Chat: {$jobPosting->title} at {$jobPosting->company}" : 'New Chat';
        }

        $session = $request->user()->chatSessions()->create([
            'title' => $title ?: 'New Chat',
            'mode' => $mode,
            'job_posting_id' => $jobPostingId,
        ]);

        return to_route('career-chat.show', $session);
    }

    public function show(Request $request, ChatSession $chatSession): Response
    {
        abort_unless($chatSession->user_id === $request->user()->id, 403);

        $messages = [];

        if ($chatSession->conversation_id) {
            $messages = DB::table('agent_conversation_messages')
                ->where('conversation_id', $chatSession->conversation_id)
                ->whereIn('role', ['user', 'assistant'])
                ->orderBy('created_at')
                ->get(['role', 'content', 'created_at'])
                ->map(fn ($msg) => [
                    'role' => $msg->role,
                    'content' => $msg->content,
                ])
                ->values()
                ->all();
        }

        $chatSession->load('jobPosting:id,title,company');

        $sessions = $request->user()->chatSessions()
            ->with('jobPosting:id,title,company')
            ->latest('updated_at')
            ->get()
            ->map(fn (ChatSession $s) => [
                'id' => $s->id,
                'title' => $s->title,
                'mode' => $s->mode->value,
                'status' => $s->status->value,
                'has_conversation' => $s->conversation_id !== null,
                'job_posting' => $s->jobPosting ? [
                    'id' => $s->jobPosting->id,
                    'title' => $s->jobPosting->title,
                    'company' => $s->jobPosting->company,
                ] : null,
                'updated_at' => $s->updated_at->diffForHumans(),
            ]);

        return Inertia::render('career-chat/show', [
            'chatSession' => [
                'id' => $chatSession->id,
                'title' => $chatSession->title,
                'mode' => $chatSession->mode->value,
                'status' => $chatSession->status->value,
                'job_posting' => $chatSession->jobPosting ? [
                    'id' => $chatSession->jobPosting->id,
                    'title' => $chatSession->jobPosting->title,
                    'company' => $chatSession->jobPosting->company,
                ] : null,
            ],
            'messages' => $messages,
            'sessions' => $sessions,
        ]);
    }

    public function chat(ChatMessageRequest $request, ChatSession $chatSession): JsonResponse
    {
        abort_unless($chatSession->user_id === $request->user()->id, 403);

        $user = $request->user();
        $experienceContext = ExperienceLibraryContextService::buildContext($user);

        $jobContext = '';
        $gapContext = '';

        if ($chatSession->mode === ChatSessionMode::JobSpecific && $chatSession->job_posting_id) {
            $jobPosting = $chatSession->jobPosting()->with('idealCandidateProfile')->first();

            if ($jobPosting) {
                $jobContext = "Job: {$jobPosting->title} at {$jobPosting->company}\n";
                if ($jobPosting->location) {
                    $jobContext .= "Location: {$jobPosting->location}\n";
                }
                if ($jobPosting->parsed_data) {
                    $jobContext .= "Requirements:\n".json_encode($jobPosting->parsed_data, JSON_PRETTY_PRINT);
                }

                if ($jobPosting->idealCandidateProfile) {
                    $gapAnalysis = $user->gapAnalyses()
                        ->where('ideal_candidate_profile_id', $jobPosting->idealCandidateProfile->id)
                        ->latest()
                        ->first();

                    if ($gapAnalysis) {
                        $gapContext = $this->buildGapContext($gapAnalysis);
                    }
                }
            }
        }

        $coach = new CareerCoach(
            experienceContext: $experienceContext,
            jobContext: $jobContext,
            gapContext: $gapContext,
            mode: $chatSession->mode,
        );

        if ($chatSession->conversation_id) {
            $response = $coach->continue($chatSession->conversation_id, as: $user)
                ->prompt($request->input('message'));
        } else {
            $response = $coach->forUser($user)
                ->prompt($request->input('message'));

            $chatSession->update(['conversation_id' => $response->conversationId]);
        }

        $chatSession->touch();

        return response()->json([
            'message' => $response->text,
            'conversation_id' => $response->conversationId,
        ]);
    }

    public function extract(Request $request, ChatSession $chatSession): JsonResponse
    {
        abort_unless($chatSession->user_id === $request->user()->id, 403);
        abort_unless($chatSession->conversation_id !== null, 422, 'No conversation to extract from.');

        $messages = DB::table('agent_conversation_messages')
            ->where('conversation_id', $chatSession->conversation_id)
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at')
            ->get(['role', 'content']);

        $transcript = $messages->map(fn ($msg) => ucfirst($msg->role).": {$msg->content}")->implode("\n\n");

        $existingContext = ExperienceLibraryContextService::buildContext($request->user());

        $extractor = new ExperienceExtractor;
        $response = $extractor->prompt(
            view('prompts.experience-extractor', [
                'transcript' => $transcript,
                'existingContext' => $existingContext,
            ])->render()
        );

        return response()->json($response->toArray());
    }

    public function commit(CommitExtractionRequest $request, ChatSession $chatSession, ExperienceImportService $importService): RedirectResponse
    {
        abort_unless($chatSession->user_id === $request->user()->id, 403);

        $stats = $importService->import($request->user(), $request->only([
            'experiences', 'accomplishments', 'skills', 'education', 'projects',
        ]));

        return to_route('career-chat.show', $chatSession)
            ->with('success', ExperienceImportService::buildImportMessage($stats));
    }

    public function update(Request $request, ChatSession $chatSession): RedirectResponse
    {
        abort_unless($chatSession->user_id === $request->user()->id, 403);

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:active,archived',
        ]);

        $chatSession->update($request->only(['title', 'status']));

        return back();
    }

    private function buildGapContext(\App\Models\GapAnalysis $gapAnalysis): string
    {
        $context = '';

        if ($gapAnalysis->overall_match_score) {
            $context .= "Match Score: {$gapAnalysis->overall_match_score}%\n\n";
        }

        if (! empty($gapAnalysis->gaps)) {
            $context .= "Gaps identified:\n";
            foreach ($gapAnalysis->gaps as $gap) {
                $context .= "- [{$gap['classification']}] {$gap['area']}: {$gap['description']}\n";
                if (isset($gap['suggestion'])) {
                    $context .= "  Suggestion: {$gap['suggestion']}\n";
                }
            }
        }

        if (! empty($gapAnalysis->strengths)) {
            $context .= "\nStrengths:\n";
            foreach ($gapAnalysis->strengths as $strength) {
                $context .= "- {$strength['area']}: {$strength['evidence']}\n";
            }
        }

        return $context;
    }
}
