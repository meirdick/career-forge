<?php

namespace App\Ai\Agents;

use App\Ai\Concerns\FailsOverOnBillingErrors;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Stringable;

#[MaxTokens(1024)]
#[Temperature(0.4)]
#[Timeout(30)]
class GapReframer implements Agent, HasStructuredOutput
{
    use FailsOverOnBillingErrors;

    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
        You are an expert career reframing specialist. Given a gap identified in a job application analysis and a candidate's existing experience, you suggest how to reframe or reposition the existing experience to better address the gap.

        Your reframe should:
        1. Use strong, specific language that directly addresses the gap requirement
        2. Highlight transferable skills and relevant parallels
        3. Quantify impact where possible
        4. Be honest — don't fabricate experience, but do surface legitimate connections
        5. Be concise — one to three sentences that could replace or supplement the original description
        INSTRUCTIONS;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'reframed_content' => $schema->string()->required(),
            'rationale' => $schema->string()->required(),
        ];
    }
}
