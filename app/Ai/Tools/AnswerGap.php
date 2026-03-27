<?php

namespace App\Ai\Tools;

use App\Models\Experience;
use App\Models\GapAnalysis;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class AnswerGap implements Tool
{
    public function __construct(
        private User $user,
        private GapAnalysis $gapAnalysis,
        private ToolActionLog $actionLog,
    ) {}

    public function description(): Stringable|string
    {
        return 'Record an answer or evidence that addresses a gap. Use this when the user provides an accomplishment, experience, or evidence that directly addresses a gap. Creates an accomplishment record and marks the gap as resolved. Include the experience_id of the most relevant experience from the library when possible, and list any skill names demonstrated by this accomplishment.';
    }

    public function handle(Request $request): Stringable|string
    {
        $gapArea = $request['gap_area'];
        $answer = $request['answer'];
        $experienceId = $request['experience_id'] ?? null;
        $skillNames = $request['skill_names'] ?? [];
        $inferredProficiency = $request['inferred_proficiency'] ?? null;

        $gap = collect($this->gapAnalysis->gaps)->firstWhere('area', $gapArea);

        if (! $gap) {
            return "Could not find a gap with area \"{$gapArea}\". Available gaps: ".collect($this->gapAnalysis->gaps)->pluck('area')->join(', ');
        }

        if ($experienceId) {
            $experience = Experience::where('id', $experienceId)
                ->where('user_id', $this->user->id)
                ->first();

            if (! $experience) {
                $experienceId = null;
            }
        }

        $accomplishment = $this->user->accomplishments()->create([
            'title' => $gap['area'],
            'description' => $answer,
            'experience_id' => $experienceId,
            'sort_order' => 0,
            'source_type' => 'gap_analysis',
            'source_id' => $this->gapAnalysis->id,
        ]);

        $createdSkillIds = [];
        foreach ($skillNames as $skillName) {
            $skill = $this->user->skills()->firstOrCreate(
                ['name' => $skillName],
                [
                    'category' => 'technical',
                    'ai_inferred_proficiency' => $inferredProficiency,
                    'source_type' => 'gap_analysis',
                    'source_id' => $this->gapAnalysis->id,
                ],
            );

            if ($inferredProficiency && ! $skill->ai_inferred_proficiency) {
                $skill->update(['ai_inferred_proficiency' => $inferredProficiency]);
            }

            $createdSkillIds[] = $skill->id;
        }

        if ($createdSkillIds) {
            $accomplishment->skills()->syncWithoutDetaching($createdSkillIds);
        }

        $this->gapAnalysis->setResolutionFor($gapArea, [
            'status' => 'resolved',
            'answer' => $answer,
            'accomplishment_id' => $accomplishment->id,
        ]);

        $experienceLabel = $experienceId && isset($experience) ? " (linked to {$experience->title} at {$experience->company})" : '';
        $skillsLabel = $skillNames ? ' with skills: '.implode(', ', $skillNames) : '';
        $this->actionLog->record("Resolved gap: {$gapArea}{$experienceLabel}{$skillsLabel}");

        return "Successfully resolved the gap \"{$gapArea}\". The answer has been recorded as an accomplishment{$experienceLabel}{$skillsLabel} and the gap is now marked as resolved.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'gap_area' => $schema->string()->required(),
            'answer' => $schema->string()->required(),
            'experience_id' => $schema->integer()->nullable(),
            'skill_names' => $schema->array(items: $schema->string())->nullable(),
            'inferred_proficiency' => $schema->enum(['beginner', 'intermediate', 'advanced', 'expert'])->nullable(),
        ];
    }
}
