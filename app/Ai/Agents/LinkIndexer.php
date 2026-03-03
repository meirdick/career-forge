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

#[MaxTokens(4096)]
#[Temperature(0.3)]
#[Timeout(120)]
class LinkIndexer implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
        You are a professional profile analyst. Given the content of a web page (GitHub profile, portfolio, article, etc.),
        extract professionally relevant information that could be added to someone's experience library.

        Extract:
        - Skills mentioned or demonstrated
        - Accomplishments or projects described
        - Any quantifiable achievements

        Only extract information that is clearly present in the content. Do not invent or infer beyond what's stated.
        If no relevant professional information can be extracted, return empty arrays.
        INSTRUCTIONS;
    }

    public function schema(JsonSchema $schema): array
    {
        $skillItem = $schema->object([
            'name' => $schema->string()->required(),
            'category' => $schema->string()->required(),
        ]);

        $accomplishmentItem = $schema->object([
            'title' => $schema->string()->required(),
            'description' => $schema->string()->required(),
            'impact' => $schema->string(),
        ]);

        $projectItem = $schema->object([
            'name' => $schema->string()->required(),
            'description' => $schema->string()->required(),
            'role' => $schema->string(),
        ]);

        return [
            'skills' => $schema->array($skillItem)->required(),
            'accomplishments' => $schema->array($accomplishmentItem)->required(),
            'projects' => $schema->array($projectItem)->required(),
        ];
    }
}
