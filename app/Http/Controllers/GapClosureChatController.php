<?php

namespace App\Http\Controllers;

use App\Ai\Agents\GapClosureCoach;
use App\Models\GapAnalysis;
use App\Services\ExperienceLibraryContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GapClosureChatController extends Controller
{
    public function chat(Request $request, GapAnalysis $gapAnalysis): JsonResponse
    {
        abort_unless($gapAnalysis->user_id === $request->user()->id, 403);

        $request->validate([
            'message' => 'required|string|max:5000',
            'conversation_id' => 'nullable|string',
        ]);

        $gapContext = $this->buildGapContext($gapAnalysis);
        $experienceContext = ExperienceLibraryContextService::buildContext($request->user());
        $coach = new GapClosureCoach(gapContext: $gapContext, experienceContext: $experienceContext);

        $conversationId = $request->input('conversation_id');
        $user = $request->user();

        if ($conversationId) {
            $response = $coach->continue($conversationId, as: $user)
                ->prompt($request->input('message'));
        } else {
            $response = $coach->forUser($user)
                ->prompt($request->input('message'));
        }

        return response()->json([
            'message' => $response->text,
            'conversation_id' => $response->conversationId,
        ]);
    }

    public function save(Request $request, GapAnalysis $gapAnalysis): JsonResponse
    {
        abort_unless($gapAnalysis->user_id === $request->user()->id, 403);

        $request->validate([
            'entries' => 'required|array|min:1',
            'entries.*.type' => 'required|in:skill,accomplishment,experience_detail',
            'entries.*.data' => 'required|array',
            'entries.*.data.experience_id' => 'nullable|integer|exists:experiences,id',
            'entries.*.data.proficiency' => 'nullable|string|in:beginner,intermediate,advanced,expert',
            'entries.*.data.ai_inferred_proficiency' => 'nullable|string|in:beginner,intermediate,advanced,expert',
        ]);

        $user = $request->user();
        $created = ['accomplishment_ids' => [], 'skill_ids' => []];

        foreach ($request->input('entries') as $entry) {
            $experienceId = isset($entry['data']['experience_id'])
                ? $user->experiences()->where('id', $entry['data']['experience_id'])->value('id')
                : null;

            match ($entry['type']) {
                'skill' => (function () use ($user, $entry, $gapAnalysis, &$created) {
                    $skill = $user->skills()->firstOrCreate(
                        ['name' => $entry['data']['name']],
                        [
                            'category' => $entry['data']['category'] ?? 'technical',
                            'ai_inferred_proficiency' => $entry['data']['ai_inferred_proficiency'] ?? null,
                            'source_type' => 'gap_analysis',
                            'source_id' => $gapAnalysis->id,
                        ],
                    );

                    if (isset($entry['data']['ai_inferred_proficiency']) && ! $skill->ai_inferred_proficiency) {
                        $skill->update(['ai_inferred_proficiency' => $entry['data']['ai_inferred_proficiency']]);
                    }

                    $created['skill_ids'][] = $skill->id;
                })(),
                'accomplishment' => (function () use ($user, $entry, $experienceId, $gapAnalysis, &$created) {
                    $accomplishment = $user->accomplishments()->create([
                        'title' => $entry['data']['title'],
                        'description' => $entry['data']['description'],
                        'impact' => $entry['data']['impact'] ?? null,
                        'experience_id' => $experienceId,
                        'sort_order' => 0,
                        'source_type' => 'gap_analysis',
                        'source_id' => $gapAnalysis->id,
                    ]);

                    $created['accomplishment_ids'][] = $accomplishment->id;
                })(),
                'experience_detail' => null,
            };
        }

        return response()->json(['success' => true, 'created' => $created]);
    }

    private function buildGapContext(GapAnalysis $gapAnalysis): string
    {
        $gapAnalysis->load('idealCandidateProfile.jobPosting');

        $jobPosting = $gapAnalysis->idealCandidateProfile->jobPosting;
        $context = "Job: {$jobPosting->title} at {$jobPosting->company}\n\n";

        if ($gapAnalysis->overall_match_score) {
            $context .= "Match Score: {$gapAnalysis->overall_match_score}%\n\n";
        }

        if (! empty($gapAnalysis->gaps)) {
            $context .= "Gaps identified:\n";
            foreach ($gapAnalysis->gaps as $gap) {
                $context .= "- [{$gap['classification']}] {$gap['area']}: {$gap['description']}\n";
                $context .= "  Suggestion: {$gap['suggestion']}\n";
            }
        }

        if (! empty($gapAnalysis->strengths)) {
            $context .= "\nStrengths identified:\n";
            foreach ($gapAnalysis->strengths as $strength) {
                $context .= "- {$strength['area']}: {$strength['evidence']}\n";
            }
        }

        return $context;
    }
}
