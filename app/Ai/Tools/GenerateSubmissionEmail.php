<?php

namespace App\Ai\Tools;

use App\Ai\Agents\CoverLetterWriter;
use App\Models\Application;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GenerateSubmissionEmail implements Tool
{
    public function __construct(
        private User $user,
        private Application $application,
        private ToolActionLog $actionLog,
    ) {}

    public function description(): Stringable|string
    {
        return 'Generate a professional submission email for the application. If a cover letter exists, the email will reference it as an attachment. The generated email is saved to the application automatically.';
    }

    public function handle(Request $request): Stringable|string
    {
        $context = $this->buildWritingContext();
        $coverLetter = $this->application->cover_letter;

        $prompt = $coverLetter
            ? "Write a brief, professional submission email for this application. The cover letter is already written and will be attached. Keep the email body short — just introduce yourself, mention the role, and reference the attached cover letter and resume.\n\nCover letter:\n{$coverLetter}"
            : 'Write a professional submission email for this application. Include a brief introduction, mention the role, and highlight 2-3 key qualifications.';

        $response = (new CoverLetterWriter(context: $context))
            ->prompt($prompt);

        $this->application->update(['submission_email' => $response->text]);

        $this->actionLog->record('Generated submission email');

        return "Submission email generated and saved:\n\n{$response->text}";
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    private function buildWritingContext(): string
    {
        $this->application->load(['jobPosting', 'resume.sections.variants']);

        $this->user->loadMissing('links');

        $contactInfo = collect([
            'Name' => $this->user->name,
            'Email' => $this->user->email,
            'Phone' => $this->user->phone,
            'Location' => $this->user->location,
            'LinkedIn' => $this->user->linkedin_url,
        ])->filter()->map(fn ($value, $label) => "{$label}: {$value}");

        foreach ($this->user->links as $link) {
            $contactInfo->push(ucfirst($link->type).': '.$link->url);
        }

        $contactInfo = $contactInfo->join("\n");

        $parts = ["Candidate Contact Information:\n{$contactInfo}"];
        $parts[] = "Company: {$this->application->company}";
        $parts[] = "Role: {$this->application->role}";

        if ($this->application->jobPosting) {
            $parts[] = "Job Posting:\n{$this->application->jobPosting->raw_text}";
        }

        return implode("\n\n", $parts);
    }
}
