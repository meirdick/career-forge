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

#[MaxTokens(2048)]
#[Temperature(0.5)]
#[Timeout(30)]
class ContentEnhancer implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(public string $sectionType) {}

    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
        You are an expert career content editor. Your job is to improve the wording of career-related content to be more compelling and professional.

        Guidelines:
        - Use strong action verbs (led, architected, spearheaded, optimized, delivered).
        - Add impact language and quantification suggestions where appropriate.
        - Keep the meaning accurate — never fabricate details, dates, company names, or proper nouns.
        - If the original text mentions vague outcomes, suggest a placeholder like "[X]%" to prompt the user to fill in real numbers.
        - Keep descriptions concise but impactful.
        - Return ALL fields from the input, enhanced where possible. Fields that don't need improvement should be returned unchanged.
        PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return match ($this->sectionType) {
            'experience' => [
                'title' => $schema->string()->required(),
                'description' => $schema->string(),
                'location' => $schema->string(),
            ],
            'accomplishment' => [
                'title' => $schema->string()->required(),
                'description' => $schema->string()->required(),
                'impact' => $schema->string(),
            ],
            'education' => [
                'title' => $schema->string()->required(),
                'field' => $schema->string(),
            ],
            'project' => [
                'name' => $schema->string()->required(),
                'description' => $schema->string()->required(),
                'role' => $schema->string(),
                'outcome' => $schema->string(),
            ],
            default => [
                'content' => $schema->string()->required(),
            ],
        };
    }
}
