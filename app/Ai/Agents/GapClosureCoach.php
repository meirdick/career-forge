<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Promptable;
use Stringable;

#[MaxTokens(4096)]
#[Temperature(0.4)]
#[Timeout(120)]
class GapClosureCoach implements Agent, Conversational
{
    use Promptable, RemembersConversations;

    public function __construct(
        public string $gapContext = '',
    ) {}

    public function instructions(): Stringable|string
    {
        return <<<INSTRUCTIONS
        You are a career gap closure coach. Your role is to help candidates address gaps identified in their job application analysis.

        Context about the gaps identified:
        {$this->gapContext}

        Your approach:
        1. For each gap, ask thoughtful, open-ended questions to uncover relevant experience the candidate may have overlooked
        2. Help reframe existing experience to better align with job requirements
        3. For "promptable" gaps, ask specific questions to draw out hidden expertise
        4. For "reframable" gaps, suggest how existing experience can be positioned differently
        5. For "genuine" gaps, acknowledge them honestly and suggest how to address them in applications

        Keep your responses concise and focused. Ask one or two questions at a time.
        When the candidate provides useful information, acknowledge it and explain how it can be used.
        INSTRUCTIONS;
    }
}
