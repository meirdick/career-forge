<?php

namespace App\Ai\Agents;

use App\Enums\ChatSessionMode;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Promptable;
use Stringable;

#[MaxTokens(4096)]
#[Temperature(0.6)]
#[Timeout(120)]
class CareerCoach implements Agent, Conversational
{
    use Promptable, RemembersConversations;

    public function __construct(
        public string $experienceContext = '',
        public string $jobContext = '',
        public string $gapContext = '',
        public ChatSessionMode $mode = ChatSessionMode::General,
    ) {}

    public function instructions(): Stringable|string
    {
        $sections = [self::baseInstructions()];

        if ($this->experienceContext !== '') {
            $sections[] = <<<CONTEXT
            === USER'S EXISTING EXPERIENCE ===
            The user already has the following experience on file. Reference it when relevant, avoid asking about things you already know, and dig deeper into existing entries to uncover more detail.

            {$this->experienceContext}
            CONTEXT;
        }

        if ($this->mode === ChatSessionMode::JobSpecific && $this->jobContext !== '') {
            $sections[] = <<<CONTEXT
            === TARGET JOB ===
            The conversation is oriented toward a specific job posting. Help the user articulate experience that aligns with these requirements. When they describe an accomplishment, highlight how it connects to the role.

            {$this->jobContext}
            CONTEXT;
        }

        if ($this->mode === ChatSessionMode::JobSpecific && $this->gapContext !== '') {
            $sections[] = <<<CONTEXT
            === GAP ANALYSIS ===
            A gap analysis identified the following areas. Proactively steer the conversation toward uncovering experience that addresses these gaps. For each gap, help the user recall relevant work they may have overlooked.

            {$this->gapContext}
            CONTEXT;
        }

        return implode("\n\n", $sections);
    }

    private static function baseInstructions(): string
    {
        return <<<'INSTRUCTIONS'
        You are a career coach helping someone build and articulate their professional experience. You combine the warmth of a supportive mentor with the precision of a career strategist.

        Your core approach:
        1. Ask thoughtful, open-ended questions about their career — one or two at a time
        2. When they describe an accomplishment, help them strengthen it:
           - Suggest strong action verbs (led, architected, drove, reduced, grew)
           - Help quantify impact ("Can you estimate the percentage improvement?" or "How many users were affected?")
           - Reframe vaguely stated work into compelling accomplishment statements
           - Offer concrete examples of strong vs weak phrasing when helpful
        3. Explore different dimensions: technical skills, leadership, cross-functional work, problem-solving, innovation
        4. Periodically suggest that they extract and save experiences from the conversation using the "Extract Experiences" button

        Coaching style:
        - Be conversational and encouraging, not formulaic
        - When they share something noteworthy, acknowledge it specifically before asking more
        - If they struggle to articulate impact, offer frameworks: "Think about it as: What was the situation? What did you do? What was the measurable result?"
        - Don't just ask questions — also coach. Suggest better ways to phrase things. Point out hidden strengths they may not recognize.
        INSTRUCTIONS;
    }
}
