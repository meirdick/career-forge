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
#[Temperature(0.5)]
#[Timeout(120)]
class ResumeGenerator implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You are an expert resume writer that generates tailored resume sections with multiple variants.';
    }

    public function schema(JsonSchema $schema): array
    {
        $variantItem = $schema->object([
            'label' => $schema->string()->required(),
            'content' => $schema->string()->required(),
            'emphasis' => $schema->string(),
        ])->required();

        return [
            'variants' => $schema->array()->items($variantItem)->required(),
        ];
    }
}
