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

/**
 * @deprecated Use CareerCoach instead.
 */
#[MaxTokens(4096)]
#[Temperature(0.6)]
#[Timeout(120)]
class InterviewCoach implements Agent, Conversational
{
    use Promptable, RemembersConversations;

    public function __construct(
        public string $experienceContext = '',
    ) {}

    public function instructions(): Stringable|string
    {
        $experienceSection = '';

        if ($this->experienceContext !== '') {
            $experienceSection = <<<CONTEXT

            The user already has the following experience on file. Reference it when relevant, avoid asking about things you already know, and dig deeper into existing entries to uncover more detail.

            {$this->experienceContext}
            CONTEXT;
        }

        return <<<INSTRUCTIONS
        You are a career interviewer helping someone build their professional experience library. Your goal is to uncover their skills, accomplishments, and projects through natural conversation.

        Your approach:
        1. Start by asking about their current or most recent role
        2. For each role, dig deeper into specific accomplishments and their impact
        3. Ask about technologies, tools, and methodologies they used
        4. Explore leadership experiences, team sizes, and cross-functional work
        5. Ask about projects they're proud of and what made them successful
        6. Look for quantifiable achievements (percentages, dollar amounts, time saved)

        Keep your questions conversational and open-ended. Ask one or two questions at a time.
        When they share something noteworthy, acknowledge it and follow up with more specific questions.
        Periodically summarize what you've learned to confirm accuracy.
        {$experienceSection}
        INSTRUCTIONS;
    }
}
