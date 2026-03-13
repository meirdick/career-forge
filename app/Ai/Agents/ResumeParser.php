<?php

namespace App\Ai\Agents;

use App\Ai\Concerns\FailsOverOnBillingErrors;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Stringable;

#[MaxTokens(45000)]
#[Temperature(0.1)]
#[Timeout(120)]
class ResumeParser implements Agent, HasStructuredOutput
{
    use FailsOverOnBillingErrors;

    public function instructions(): Stringable|string
    {
        return view('prompts.resume-parser', ['text' => ''])->render();
    }

    public function schema(JsonSchema $schema): array
    {
        $experienceSchema = $schema->object([
            'company' => $schema->string()->required(),
            'title' => $schema->string()->required(),
            'location' => $schema->string(),
            'started_at' => $schema->string()->required(),
            'ended_at' => $schema->string(),
            'is_current' => $schema->boolean()->required(),
            'description' => $schema->string(),
        ]);

        $accomplishmentSchema = $schema->object([
            'title' => $schema->string()->required(),
            'description' => $schema->string()->required(),
            'impact' => $schema->string(),
            'experience_index' => $schema->integer(),
        ]);

        $skillSchema = $schema->object([
            'name' => $schema->string()->required(),
            'category' => $schema->string()->required(),
        ]);

        $educationSchema = $schema->object([
            'type' => $schema->string()->required(),
            'institution' => $schema->string()->required(),
            'title' => $schema->string()->required(),
            'field' => $schema->string(),
            'completed_at' => $schema->string(),
        ]);

        $projectSchema = $schema->object([
            'name' => $schema->string()->required(),
            'description' => $schema->string()->required(),
            'role' => $schema->string(),
            'outcome' => $schema->string(),
            'experience_index' => $schema->integer(),
        ]);

        $urlSchema = $schema->object([
            'url' => $schema->string()->required(),
            'type' => $schema->string()->required(),
            'label' => $schema->string(),
        ]);

        return [
            'experiences' => $schema->array()->items($experienceSchema)->required(),
            'accomplishments' => $schema->array()->items($accomplishmentSchema)->required(),
            'skills' => $schema->array()->items($skillSchema)->required(),
            'education' => $schema->array()->items($educationSchema)->required(),
            'projects' => $schema->array()->items($projectSchema)->required(),
            'urls' => $schema->array()->items($urlSchema)->required(),
        ];
    }
}
