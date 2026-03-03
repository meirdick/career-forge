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
#[Temperature(0.1)]
#[Timeout(120)]
class ResumeParser implements Agent, HasStructuredOutput
{
    use Promptable;

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
        ])->required();

        $accomplishmentSchema = $schema->object([
            'title' => $schema->string()->required(),
            'description' => $schema->string()->required(),
            'impact' => $schema->string(),
            'experience_index' => $schema->integer(),
        ])->required();

        $skillSchema = $schema->object([
            'name' => $schema->string()->required(),
            'category' => $schema->string()->required(),
        ])->required();

        $educationSchema = $schema->object([
            'type' => $schema->string()->required(),
            'institution' => $schema->string()->required(),
            'title' => $schema->string()->required(),
            'field' => $schema->string(),
            'completed_at' => $schema->string(),
        ])->required();

        $projectSchema = $schema->object([
            'name' => $schema->string()->required(),
            'description' => $schema->string()->required(),
            'role' => $schema->string(),
            'outcome' => $schema->string(),
            'experience_index' => $schema->integer(),
        ])->required();

        return [
            'experiences' => $schema->array($experienceSchema)->required(),
            'accomplishments' => $schema->array($accomplishmentSchema)->required(),
            'skills' => $schema->array($skillSchema)->required(),
            'education' => $schema->array($educationSchema)->required(),
            'projects' => $schema->array($projectSchema)->required(),
        ];
    }
}
