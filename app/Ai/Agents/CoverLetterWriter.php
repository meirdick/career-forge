<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

#[MaxTokens(4096)]
#[Temperature(0.5)]
#[Timeout(120)]
class CoverLetterWriter implements Agent
{
    use Promptable;

    public function __construct(
        public string $context = '',
    ) {}

    public function instructions(): Stringable|string
    {
        return <<<INSTRUCTIONS
        You are an expert cover letter writer. Write professional, compelling cover letters that:

        1. Are tailored to the specific job posting and company
        2. Highlight relevant experience and accomplishments from the candidate's resume
        3. Address key requirements from the job posting
        4. Maintain a professional but personable tone
        5. Are concise — typically 3-4 paragraphs
        6. Start with a strong opening that shows genuine interest
        7. End with a clear call to action

        Context about the candidate and position:
        {$this->context}

        Output ONLY the cover letter text — no subject lines, no "Dear Hiring Manager" unless appropriate, no meta-commentary.
        INSTRUCTIONS;
    }
}
