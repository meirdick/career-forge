<?php

namespace App\Ai\Agents;

use App\Ai\Concerns\FailsOverOnBillingErrors;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Stringable;

#[MaxTokens(16384)]
#[Temperature(0.5)]
#[Timeout(120)]
class SectionResumeGenerator implements Agent, HasProviderOptions, HasStructuredOutput
{
    use FailsOverOnBillingErrors;

    /**
     * Shared context rendered once and cached across section calls.
     */
    private string $sharedContext = '';

    public function withSharedContext(string $context): static
    {
        $this->sharedContext = $context;

        return $this;
    }

    public function instructions(): Stringable|string
    {
        $base = 'You are an expert resume writer that generates tailored resume sections with multiple variants. '
            .'Format all content using markdown: **bold** for company/degree names, *italic* for titles/dates, '
            .'and - for bullet points. Follow the exact formatting patterns specified in the prompt.';

        if ($this->sharedContext !== '') {
            return $base."\n\n".$this->sharedContext;
        }

        return $base;
    }

    /**
     * Enable Anthropic prompt caching on the system prompt so shared context
     * is cached across sequential per-section calls (~90% input cost savings).
     */
    public function providerOptions(Lab|string $provider): array
    {
        if ($provider === Lab::Anthropic || $provider === 'anthropic') {
            return [
                'cache_control' => ['type' => 'ephemeral'],
            ];
        }

        return [];
    }

    public function schema(JsonSchema $schema): array
    {
        $blockItem = $schema->object([
            'label' => $schema->string()->required(),
            'content' => $schema->string()->required(),
        ])->required();

        $variantItem = $schema->object([
            'label' => $schema->string()->required(),
            'content' => $schema->string(),
            'compact_content' => $schema->string(),
            'blocks' => $schema->array()->items($blockItem),
            'emphasis' => $schema->string(),
        ])->required();

        return [
            'variants' => $schema->array()->items($variantItem)->required(),
        ];
    }
}
