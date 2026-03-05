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

#[MaxTokens(8192)]
#[Temperature(0.2)]
#[Timeout(120)]
class JobAnalyzer implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You are an expert job posting analyst that creates detailed Ideal Candidate Profiles.';
    }

    public function schema(JsonSchema $schema): array
    {
        $skillItem = $schema->object([
            'name' => $schema->string()->required(),
            'importance' => $schema->string()->required(),
            'category' => $schema->string()->required(),
        ])->required();

        return [
            'title' => $schema->string()->required(),
            'company' => $schema->string(),
            'location' => $schema->string(),
            'seniority_level' => $schema->string(),
            'compensation' => $schema->string(),
            'remote_policy' => $schema->string(),
            'required_skills' => $schema->array()->items($skillItem)->required(),
            'preferred_skills' => $schema->array()->items($skillItem)->required(),
            'experience_profile' => $schema->object([
                'years_minimum' => $schema->integer(),
                'years_preferred' => $schema->integer(),
                'industries' => $schema->array()->items($schema->string()),
                'project_types' => $schema->array()->items($schema->string()),
                'leadership_expectations' => $schema->string(),
            ])->required(),
            'cultural_fit' => $schema->object([
                'values' => $schema->array()->items($schema->string()),
                'team_dynamics' => $schema->string(),
                'work_style' => $schema->string(),
            ])->required(),
            'language_guidance' => $schema->object([
                'key_terms' => $schema->array()->items($schema->string()),
                'tone' => $schema->string(),
                'phrases_to_mirror' => $schema->array()->items($schema->string()),
            ])->required(),
            'red_flags' => $schema->array()->items($schema->string())->required(),
            'company_research' => $schema->object([
                'industry' => $schema->string(),
                'size_indicators' => $schema->string(),
                'growth_stage' => $schema->string(),
                'notable_details' => $schema->array()->items($schema->string()),
            ]),
        ];
    }
}
