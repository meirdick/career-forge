<?php

namespace App\Ai\Tools;

use App\Jobs\PerformGapAnalysisJob;
use App\Models\GapAnalysis;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class TriggerReanalysis implements Tool
{
    public function __construct(
        private GapAnalysis $gapAnalysis,
        private ToolActionLog $actionLog,
    ) {}

    public function description(): Stringable|string
    {
        return 'Trigger a re-analysis of the gap analysis. Use this when the user has resolved several gaps and wants to see their updated match score. This dispatches a background job.';
    }

    public function handle(Request $request): Stringable|string
    {
        $this->gapAnalysis->update([
            'previous_match_score' => $this->gapAnalysis->overall_match_score,
            'strengths' => [],
            'gaps' => [],
            'overall_match_score' => null,
            'ai_summary' => null,
        ]);

        PerformGapAnalysisJob::dispatch($this->gapAnalysis);

        $this->actionLog->record('Triggered gap re-analysis');

        return 'Re-analysis has been started. The page will update once the analysis is complete. The previous match score has been saved for comparison.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
