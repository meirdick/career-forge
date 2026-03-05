<?php

namespace App\Ai\Agents;

use App\Ai\Tools\AcknowledgeGap;
use App\Ai\Tools\AnswerGap;
use App\Ai\Tools\ReframeGapExperience;
use App\Ai\Tools\ToolActionLog;
use App\Ai\Tools\TriggerReanalysis;
use App\Models\GapAnalysis;
use App\Models\User;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

#[MaxTokens(4096)]
#[MaxSteps(5)]
#[Temperature(0.4)]
#[Timeout(120)]
class GapClosureCoach implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(
        public string $gapContext = '',
        public string $experienceContext = '',
        public ?User $user = null,
        public ?GapAnalysis $gapAnalysis = null,
        public ?ToolActionLog $actionLog = null,
    ) {}

    public function tools(): iterable
    {
        if (! $this->user || ! $this->gapAnalysis || ! $this->actionLog) {
            return [];
        }

        return [
            new AcknowledgeGap($this->gapAnalysis, $this->actionLog),
            new AnswerGap($this->user, $this->gapAnalysis, $this->actionLog),
            new ReframeGapExperience($this->user, $this->gapAnalysis, $this->actionLog),
            new TriggerReanalysis($this->gapAnalysis, $this->actionLog),
        ];
    }

    public function instructions(): Stringable|string
    {
        $experienceSection = '';

        if ($this->experienceContext !== '') {
            $experienceSection = <<<CONTEXT

            The user's full experience library is below. Use it to identify existing experience that addresses gaps, suggest reframing of existing entries, and avoid asking about things you already know.

            {$this->experienceContext}
            CONTEXT;
        }

        $toolInstructions = '';

        if ($this->user && $this->gapAnalysis) {
            $toolInstructions = <<<'TOOLS'

            === ACTIONS YOU CAN TAKE ===
            You have tools to directly resolve gaps. Use them proactively:

            - When the user provides evidence or accomplishments that address a gap, offer to record it immediately using the answer tool. Write a clear, concise accomplishment statement based on what they shared.
            - When the user agrees a gap is genuine and wants to move on, acknowledge it directly.
            - When you identify existing experience that could be reframed to address a gap, suggest a reframe and use the reframe tool with the experience ID.
            - After the user has resolved several gaps, offer to trigger a re-analysis to see their updated match score.

            Always confirm what you're about to do before calling a tool. After making changes, summarize what was done and suggest next steps.
            TOOLS;
        }

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
        {$experienceSection}
        {$toolInstructions}
        INSTRUCTIONS;
    }
}
