<?php

namespace App\Ai\Agents;

use App\Ai\Tools\EditResumeSection;
use App\Ai\Tools\GenerateCoverLetter;
use App\Ai\Tools\GenerateSubmissionEmail;
use App\Ai\Tools\ReorderResumeSections;
use App\Ai\Tools\SelectResumeVariant;
use App\Ai\Tools\ToolActionLog;
use App\Ai\Tools\UpdateApplicationStatus;
use App\Ai\Tools\UpdateCandidateProfileField;
use App\Ai\Tools\UpdateCandidateProfileSkills;
use App\Ai\Tools\UpdateCoverLetter;
use App\Enums\ChatSessionMode;
use App\Models\Application;
use App\Models\IdealCandidateProfile;
use App\Models\Resume;
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
#[Temperature(0.6)]
#[Timeout(120)]
class CareerCoach implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(
        public string $experienceContext = '',
        public string $jobContext = '',
        public string $gapContext = '',
        public ChatSessionMode $mode = ChatSessionMode::General,
        public string $stepObjective = '',
        public ?User $user = null,
        public ?Resume $resume = null,
        public ?Application $application = null,
        public ?IdealCandidateProfile $profile = null,
        public ?ToolActionLog $actionLog = null,
    ) {}

    public function tools(): iterable
    {
        if (! $this->actionLog) {
            return [];
        }

        $tools = [];

        if ($this->resume) {
            $tools[] = new EditResumeSection($this->resume, $this->actionLog);
            $tools[] = new SelectResumeVariant($this->resume, $this->actionLog);
            $tools[] = new ReorderResumeSections($this->resume, $this->actionLog);
        }

        if ($this->application && $this->user) {
            $tools[] = new UpdateCoverLetter($this->application, $this->actionLog);
            $tools[] = new GenerateCoverLetter($this->user, $this->application, $this->actionLog);
            $tools[] = new GenerateSubmissionEmail($this->user, $this->application, $this->actionLog);
            $tools[] = new UpdateApplicationStatus($this->application, $this->actionLog);
        }

        if ($this->profile) {
            $tools[] = new UpdateCandidateProfileSkills($this->profile, $this->actionLog);
            $tools[] = new UpdateCandidateProfileField($this->profile, $this->actionLog);
        }

        return $tools;
    }

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

        if ($this->stepObjective !== '') {
            $sections[] = <<<CONTEXT
            === YOUR OBJECTIVE ===
            {$this->stepObjective}
            CONTEXT;
        }

        if ($this->actionLog) {
            $sections[] = $this->toolInstructions();
        }

        return implode("\n\n", $sections);
    }

    private function toolInstructions(): string
    {
        $parts = [];

        if ($this->resume) {
            $parts[] = <<<'TOOLS'
            Resume tools: You can directly edit resume sections — improve phrasing, add quantification, strengthen action verbs. You can also switch between section variants and reorder sections.
            - When you suggest improved content, offer to apply it directly
            - Always show the user what you plan to change before making the edit
            TOOLS;
        }

        if ($this->application) {
            $parts[] = <<<'TOOLS'
            Application tools: You can generate and edit cover letters, create submission emails, and update application status.
            - Proactively offer to generate a cover letter if one hasn't been written
            - When editing the cover letter, propose specific changes and apply them
            TOOLS;
        }

        if ($this->profile) {
            $parts[] = <<<'TOOLS'
            Candidate profile tools: You can update the ideal candidate profile — add/remove required/preferred skills, and update experience profile, cultural fit, language guidance, and red flags.
            - Help the user understand the role and proactively suggest profile refinements
            TOOLS;
        }

        if (empty($parts)) {
            return '';
        }

        return "=== ACTIONS YOU CAN TAKE ===\n".implode("\n\n", $parts)."\n\nAlways confirm what you're about to do before calling a tool. After making changes, summarize what was done and suggest next steps.";
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
