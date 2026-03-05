<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

#[MaxTokens(8192)]
#[Temperature(0.2)]
#[Timeout(120)]
class GapAnalyzer implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You are an expert career gap analyst that compares candidate profiles against ideal candidate profiles.';
    }

    public function schema(JsonSchema $schema): array
    {
        $strengthItem = $schema->object([
            'area' => $schema->string()->required(),
            'evidence' => $schema->string()->required(),
            'relevance' => $schema->string()->required(),
        ])->required();

        $gapItem = $schema->object([
            'area' => $schema->string()->required(),
            'description' => $schema->string()->required(),
            'classification' => $schema->string()->required(),
            'suggestion' => $schema->string()->required(),
        ])->required();

        return [
            'strengths' => $schema->array()->items($strengthItem)->required(),
            'gaps' => $schema->array()->items($gapItem)->required(),
            'overall_match_score' => $schema->integer()->min(0)->max(100)->required(),
            'summary' => $schema->string()->required(),
        ];
    }
}
