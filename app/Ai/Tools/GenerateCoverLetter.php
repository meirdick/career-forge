<?php

namespace App\Ai\Tools;

use App\Ai\Agents\CoverLetterWriter;
use App\Models\Application;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GenerateCoverLetter implements Tool
{
    public function __construct(
        private User $user,
        private Application $application,
        private ToolActionLog $actionLog,
    ) {}

    public function description(): Stringable|string
    {
        return 'Generate a new cover letter for the application using AI. Optionally accepts extra instructions to guide the generation. The generated cover letter is saved to the application automatically.';
    }

    public function handle(Request $request): Stringable|string
    {
        $context = $this->buildWritingContext();
        $instructions = $request['instructions'] ?? '';

        $prompt = 'Write a cover letter for this position.';

        if ($instructions !== '') {
            $prompt .= " Additional guidance: {$instructions}";
        }

        $response = (new CoverLetterWriter(context: $context))
            ->prompt($prompt);

        $this->application->update(['cover_letter' => $response->text]);

        $this->actionLog->record('Generated cover letter');

        return "Cover letter generated and saved:\n\n{$response->text}";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'instructions' => $schema->string(),
        ];
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

        if ($this->application->resume) {
            $sections = $this->application->resume->sections->map(function ($section) {
                $variant = $section->variants->firstWhere('is_selected', true) ?? $section->variants->first();

                return $variant ? "{$section->heading}:\n{$variant->content}" : null;
            })->filter()->join("\n\n");

            $parts[] = "Resume:\n{$sections}";
        }

        return implode("\n\n", $parts);
    }
}
