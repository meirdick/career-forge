<?php

namespace App\Http\Controllers;

use App\Ai\Agents\CareerCoach;
use App\Ai\Agents\GapClosureCoach;
use App\Ai\Tools\ToolActionLog;
use App\Enums\ChatSessionMode;
use App\Enums\PipelineStep;
use App\Http\Requests\ChatMessageRequest;
use App\Models\ChatSession;
use App\Services\ExperienceLibraryContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PipelineChatController extends Controller
{
    public function resolve(Request $request): JsonResponse
    {
        $request->validate([
            'step' => 'required|string|in:job_posting,gap_analysis,resume_builder,application',
            'pipeline_key' => 'required|string|max:255',
        ]);

        $user = $request->user();
        $pipelineKey = $request->input('pipeline_key');
        $step = PipelineStep::from($request->input('step'));

        $session = $user->chatSessions()
            ->where('pipeline_key', $pipelineKey)
            ->first();

        if (! $session) {
            $session = $user->chatSessions()->create([
                'title' => $this->titleForStep($step, $pipelineKey),
                'mode' => ChatSessionMode::JobSpecific,
                'step' => $step,
                'pipeline_key' => $pipelineKey,
            ]);
        }

        $messages = [];

        if ($session->conversation_id) {
            $messages = DB::table('agent_conversation_messages')
                ->where('conversation_id', $session->conversation_id)
                ->whereIn('role', ['user', 'assistant'])
                ->orderBy('created_at')
                ->get(['role', 'content'])
                ->map(fn ($msg) => [
                    'role' => $msg->role,
                    'content' => $msg->content,
                ])
                ->values()
                ->all();
        }

        return response()->json([
            'session_id' => $session->id,
            'messages' => $messages,
        ]);
    }

    public function chat(ChatMessageRequest $request, ChatSession $chatSession): JsonResponse
    {
        abort_unless($chatSession->user_id === $request->user()->id, 403);

        $user = $request->user();
        $step = $chatSession->step;
        $experienceContext = ExperienceLibraryContextService::buildContext($user);
        $actionLog = new ToolActionLog;

        if ($step === PipelineStep::GapAnalysis) {
            return $this->chatWithGapCoach($request, $chatSession, $user, $experienceContext, $actionLog);
        }

        $jobContext = $this->buildJobContext($chatSession);
        $gapContext = $this->buildGapContext($chatSession, $user);
        $entities = $this->resolveEntities($chatSession, $user);

        $coach = new CareerCoach(
            experienceContext: $experienceContext,
            jobContext: $jobContext,
            gapContext: $gapContext,
            mode: ChatSessionMode::JobSpecific,
            stepObjective: self::objectiveForStep($step),
            user: $user,
            resume: $entities['resume'] ?? null,
            application: $entities['application'] ?? null,
            profile: $entities['profile'] ?? null,
            actionLog: $actionLog,
        );

        $response = $this->sendMessage($coach, $chatSession, $user, $request->input('message'));

        return response()->json([
            'message' => $response->text,
            'conversation_id' => $response->conversationId,
            'tool_actions' => $actionLog->actions(),
        ]);
    }

    private function chatWithGapCoach(ChatMessageRequest $request, ChatSession $chatSession, $user, string $experienceContext, ToolActionLog $actionLog): JsonResponse
    {
        $gapContext = $this->buildGapContext($chatSession, $user);
        $gapAnalysis = $this->resolveGapAnalysis($chatSession, $user);

        $coach = new GapClosureCoach(
            gapContext: $gapContext,
            experienceContext: $experienceContext,
            user: $user,
            gapAnalysis: $gapAnalysis,
            actionLog: $actionLog,
        );

        $response = $this->sendMessage($coach, $chatSession, $user, $request->input('message'));

        return response()->json([
            'message' => $response->text,
            'conversation_id' => $response->conversationId,
            'tool_actions' => $actionLog->actions(),
        ]);
    }

    /**
     * @return array{resume?: \App\Models\Resume, application?: \App\Models\Application, profile?: \App\Models\IdealCandidateProfile}
     */
    private function resolveEntities(ChatSession $chatSession, $user): array
    {
        $step = $chatSession->step;
        $id = $this->extractIdFromPipelineKey($chatSession->pipeline_key);

        if (! $id) {
            return [];
        }

        return match ($step) {
            PipelineStep::JobPosting => $this->resolveJobPostingEntities($user, $id),
            PipelineStep::ResumeBuilder => $this->resolveResumeEntities($user, $id),
            PipelineStep::Application => $this->resolveApplicationEntities($user, $id),
            default => [],
        };
    }

    private function resolveJobPostingEntities($user, int $jobPostingId): array
    {
        $jobPosting = $user->jobPostings()->with('idealCandidateProfile')->find($jobPostingId);

        if (! $jobPosting?->idealCandidateProfile) {
            return [];
        }

        return ['profile' => $jobPosting->idealCandidateProfile];
    }

    private function resolveResumeEntities($user, int $jobPostingId): array
    {
        $resume = $user->resumes()
            ->where('job_posting_id', $jobPostingId)
            ->with('sections.variants')
            ->latest()
            ->first();

        return array_filter(['resume' => $resume]);
    }

    private function resolveApplicationEntities($user, int $jobPostingId): array
    {
        $application = $user->applications()
            ->where('job_posting_id', $jobPostingId)
            ->with(['jobPosting', 'resume'])
            ->latest()
            ->first();

        return array_filter(['application' => $application]);
    }

    private function resolveGapAnalysis(ChatSession $chatSession, $user): ?\App\Models\GapAnalysis
    {
        $id = $this->extractIdFromPipelineKey($chatSession->pipeline_key);

        if (! $id) {
            return null;
        }

        return $user->gapAnalyses()->with('idealCandidateProfile.jobPosting')->find($id);
    }

    private function sendMessage($agent, ChatSession $chatSession, $user, string $message): mixed
    {
        if ($chatSession->conversation_id) {
            $response = $agent->continue($chatSession->conversation_id, as: $user)
                ->prompt($message);
        } else {
            $response = $agent->forUser($user)
                ->prompt($message);

            $chatSession->update(['conversation_id' => $response->conversationId]);
        }

        $chatSession->touch();

        return $response;
    }

    private function buildJobContext(ChatSession $chatSession): string
    {
        $pipelineKey = $chatSession->pipeline_key;

        if (! $pipelineKey) {
            return '';
        }

        $jobPostingId = $this->extractIdFromPipelineKey($pipelineKey);

        if (! $jobPostingId) {
            return '';
        }

        $jobPosting = $chatSession->user->jobPostings()
            ->with('idealCandidateProfile')
            ->find($jobPostingId);

        if (! $jobPosting) {
            return '';
        }

        $context = "Job: {$jobPosting->title} at {$jobPosting->company}\n";

        if ($jobPosting->location) {
            $context .= "Location: {$jobPosting->location}\n";
        }

        if ($jobPosting->parsed_data) {
            $context .= "Requirements:\n".json_encode($jobPosting->parsed_data, JSON_PRETTY_PRINT);
        }

        return $context;
    }

    private function buildGapContext(ChatSession $chatSession, $user): string
    {
        $pipelineKey = $chatSession->pipeline_key;

        if (! $pipelineKey) {
            return '';
        }

        $id = $this->extractIdFromPipelineKey($pipelineKey);

        if (! $id) {
            return '';
        }

        $step = $chatSession->step;

        if ($step === PipelineStep::GapAnalysis) {
            $gapAnalysis = $user->gapAnalyses()->with('idealCandidateProfile.jobPosting')->find($id);
        } else {
            $jobPostingId = $id;
            $jobPosting = $user->jobPostings()->with('idealCandidateProfile')->find($jobPostingId);

            if (! $jobPosting?->idealCandidateProfile) {
                return '';
            }

            $gapAnalysis = $user->gapAnalyses()
                ->where('ideal_candidate_profile_id', $jobPosting->idealCandidateProfile->id)
                ->latest()
                ->first();
        }

        if (! $gapAnalysis) {
            return '';
        }

        $context = '';

        if ($gapAnalysis->overall_match_score) {
            $context .= "Match Score: {$gapAnalysis->overall_match_score}%\n\n";
        }

        if (! empty($gapAnalysis->gaps)) {
            $context .= "Gaps identified:\n";
            foreach ($gapAnalysis->gaps as $gap) {
                $context .= "- [{$gap['classification']}] {$gap['area']}: {$gap['description']}\n";
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

    private function extractIdFromPipelineKey(string $pipelineKey): ?int
    {
        $parts = explode(':', $pipelineKey);

        return isset($parts[1]) ? (int) $parts[1] : null;
    }

    private function titleForStep(PipelineStep $step, string $pipelineKey): string
    {
        return match ($step) {
            PipelineStep::JobPosting => 'Job Posting Assistant',
            PipelineStep::GapAnalysis => 'Gap Analysis Coach',
            PipelineStep::ResumeBuilder => 'Resume Assistant',
            PipelineStep::Application => 'Application Assistant',
        };
    }

    private static function objectiveForStep(?PipelineStep $step): string
    {
        return match ($step) {
            PipelineStep::JobPosting => 'Help the user understand this role and how their background connects to the requirements. Highlight relevant experience and suggest angles they might explore. You can update the ideal candidate profile to refine skill requirements and other profile fields.',
            PipelineStep::ResumeBuilder => 'Help the user strengthen their resume language and improve phrasing. Suggest stronger action verbs, better quantification, and clearer impact statements for their resume sections. You can directly edit resume sections, switch variants, and reorder sections.',
            PipelineStep::Application => 'Help the user prepare their application — refine their cover letter, develop interview talking points, and build confidence for this specific role. You can generate and edit cover letters, create submission emails, and update application status.',
            default => '',
        };
    }
}
