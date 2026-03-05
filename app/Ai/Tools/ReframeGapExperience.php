<?php

namespace App\Ai\Tools;

use App\Ai\Agents\GapReframer;
use App\Models\Experience;
use App\Models\GapAnalysis;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ReframeGapExperience implements Tool
{
    public function __construct(
        private User $user,
        private GapAnalysis $gapAnalysis,
        private ToolActionLog $actionLog,
    ) {}

    public function description(): Stringable|string
    {
        return 'Suggest a reframe of an existing experience to better address a gap. Use this when the user has relevant experience that could be repositioned to address a gap. The suggestion will be saved for the user to review.';
    }

    public function handle(Request $request): Stringable|string
    {
        $gapArea = $request['gap_area'];
        $experienceId = $request['experience_id'];

        $gap = collect($this->gapAnalysis->gaps)->firstWhere('area', $gapArea);

        if (! $gap) {
            return "Could not find a gap with area \"{$gapArea}\". Available gaps: ".collect($this->gapAnalysis->gaps)->pluck('area')->join(', ');
        }

        $experience = Experience::where('id', $experienceId)
            ->where('user_id', $this->user->id)
            ->first();

        if (! $experience) {
            return "Could not find experience with ID {$experienceId} belonging to the current user.";
        }

        $this->gapAnalysis->load('idealCandidateProfile.jobPosting');
        $jobPosting = $this->gapAnalysis->idealCandidateProfile->jobPosting;

        $prompt = view('prompts.gap-reframe', [
            'gap' => $gap,
            'experience' => $experience,
            'jobTitle' => $jobPosting->title,
            'company' => $jobPosting->company,
        ])->render();

        $response = (new GapReframer)->prompt($prompt);

        $this->gapAnalysis->setResolutionFor($gapArea, [
            'status' => 'pending_review',
            'experience_id' => $experience->id,
            'reframe_original' => $experience->description,
            'reframe_suggestion' => $response['reframed_content'],
            'rationale' => $response['rationale'],
        ]);

        $this->actionLog->record("Suggested reframe for gap: {$gapArea}");

        return "Reframe suggestion for \"{$gapArea}\":\n\nSuggested reframe: {$response['reframed_content']}\n\nRationale: {$response['rationale']}\n\nThis suggestion has been saved for the user to review in the gap action card.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'gap_area' => $schema->string()->required(),
            'experience_id' => $schema->integer()->required(),
        ];
    }
}
