<?php

namespace App\Ai\Tools;

use App\Models\GapAnalysis;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class AcknowledgeGap implements Tool
{
    public function __construct(
        private GapAnalysis $gapAnalysis,
        private ToolActionLog $actionLog,
    ) {}

    public function description(): Stringable|string
    {
        return 'Acknowledge a gap as a genuine gap that the candidate cannot currently address. Use this when the user agrees a gap is real and wants to move on.';
    }

    public function handle(Request $request): Stringable|string
    {
        $gapArea = $request['gap_area'];
        $note = $request['note'] ?? '';

        $gap = collect($this->gapAnalysis->gaps)->firstWhere('area', $gapArea);

        if (! $gap) {
            return "Could not find a gap with area \"{$gapArea}\". Available gaps: ".collect($this->gapAnalysis->gaps)->pluck('area')->join(', ');
        }

        $this->gapAnalysis->setResolutionFor($gapArea, [
            'status' => 'acknowledged',
            'note' => $note,
        ]);

        $this->actionLog->record("Acknowledged gap: {$gapArea}");

        return "Successfully acknowledged the gap \"{$gapArea}\". This gap is now marked as acknowledged and won't count against the candidate's match score improvement.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'gap_area' => $schema->string()->required(),
            'note' => $schema->string(),
        ];
    }
}
