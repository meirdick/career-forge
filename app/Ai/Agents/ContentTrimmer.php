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

#[MaxTokens(16384)]
#[Temperature(0.2)]
#[Timeout(60)]
class ContentTrimmer implements Agent, HasStructuredOutput
{
    use FailsOverOnBillingErrors;

    public function instructions(): Stringable|string
    {
        return 'You are a resume content editor. Your job is to shorten resume section content to fit within a page limit. '
            .'Preserve the most impactful information. Remove the weakest bullet points, shorten verbose descriptions, '
            .'and condense where possible. Keep the same markdown formatting. Never fabricate new content — only trim.';
    }

    public function schema(JsonSchema $schema): array
    {
        $sectionItem = $schema->object([
            'section_id' => $schema->integer()->required(),
            'content' => $schema->string()->required(),
        ])->required();

        return [
            'sections' => $schema->array()->items($sectionItem)->required(),
        ];
    }
}
