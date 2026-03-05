<?php

namespace App\Ai\Tools;

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
        return 'Record an answer or evidence that addresses a gap. Use this when the user provides an accomplishment, experience, or evidence that directly addresses a gap. Creates an accomplishment record and marks the gap as resolved.';
    }

    public function handle(Request $request): Stringable|string
    {
        $gapArea = $request['gap_area'];
        $answer = $request['answer'];

        $gap = collect($this->gapAnalysis->gaps)->firstWhere('area', $gapArea);

        if (! $gap) {
            return "Could not find a gap with area \"{$gapArea}\". Available gaps: ".collect($this->gapAnalysis->gaps)->pluck('area')->join(', ');
        }

        $this->user->accomplishments()->create([
            'title' => $gap['area'],
            'description' => $answer,
            'sort_order' => 0,
        ]);

        $this->gapAnalysis->setResolutionFor($gapArea, [
            'status' => 'resolved',
            'answer' => $answer,
        ]);

        $this->actionLog->record("Resolved gap: {$gapArea}");

        return "Successfully resolved the gap \"{$gapArea}\". The answer has been recorded as an accomplishment and the gap is now marked as resolved.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'gap_area' => $schema->string()->required(),
            'answer' => $schema->string()->required(),
        ];
    }
}
