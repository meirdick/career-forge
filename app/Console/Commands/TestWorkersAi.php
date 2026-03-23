<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Tool;

class TestWorkersAi extends Command
{
    protected $signature = 'test:workers-ai {--test= : Run a specific test (text, structured, embeddings, tools, stream, agent, reasoning-agent, all)}';

    protected $description = 'Test Workers AI provider through prism-workers-ai package';

    private string $model = 'workers-ai/@cf/meta/llama-3.3-70b-instruct-fp8-fast';

    private string $embeddingModel = 'workers-ai/@cf/baai/bge-large-en-v1.5';

    public function handle(): int
    {
        $test = $this->option('test') ?? 'all';

        $this->info('Testing Workers AI provider (meirdick/prism-workers-ai)');
        $this->info('URL: '.config('prism.providers.workers-ai.url'));
        $this->newLine();

        $results = [];

        if ($test === 'all' || $test === 'text') {
            $results['text'] = $this->testText();
        }
        if ($test === 'all' || $test === 'structured') {
            $results['structured'] = $this->testStructured();
        }
        if ($test === 'all' || $test === 'embeddings') {
            $results['embeddings'] = $this->testEmbeddings();
        }
        if ($test === 'all' || $test === 'tools') {
            $results['tools'] = $this->testTools();
        }
        if ($test === 'all' || $test === 'stream') {
            $results['stream'] = $this->testStream();
        }
        if ($test === 'all' || $test === 'agent') {
            $results['agent'] = $this->testAgent();
        }
        if ($test === 'all' || $test === 'reasoning-agent') {
            $results['reasoning-agent'] = $this->testReasoningAgent();
        }

        $this->newLine();
        $this->info('=== Results ===');
        foreach ($results as $name => $passed) {
            $this->line(($passed ? '  PASS' : '  FAIL')." $name");
        }

        $failed = count(array_filter($results, fn ($r) => ! $r));

        return $failed > 0 ? 1 : 0;
    }

    private function testText(): bool
    {
        $this->info('--- Test: Text Generation ---');

        try {
            $response = Prism::text()
                ->using('workers-ai', $this->model)
                ->withSystemPrompt('You are a helpful assistant. Be very brief.')
                ->withPrompt('What is 2 + 2? Reply with just the number.')
                ->asText();

            $this->line("  Response: {$response->text}");
            $this->line("  Finish reason: {$response->finishReason->name}");
            $this->line("  Prompt tokens: {$response->usage->promptTokens}");
            $this->line("  Completion tokens: {$response->usage->completionTokens}");
            $this->line("  Model: {$response->meta->model}");

            $pass = ! empty($response->text) && $response->finishReason === FinishReason::Stop;
            $this->line($pass ? '  PASS' : '  FAIL - Expected non-empty text with Stop finish reason');

            return $pass;
        } catch (\Throwable $e) {
            $this->error("  ERROR: {$e->getMessage()}");

            return false;
        }
    }

    private function testStructured(): bool
    {
        $this->info('--- Test: Structured Output ---');

        try {
            $response = Prism::structured()
                ->using('workers-ai', $this->model)
                ->withSchema(new ObjectSchema(
                    name: 'classification',
                    description: 'Intent classification result',
                    properties: [
                        new StringSchema('intent', 'The detected intent'),
                        new NumberSchema('confidence', 'Confidence score between 0 and 1'),
                    ],
                    requiredFields: ['intent', 'confidence'],
                ))
                ->withPrompt('Classify this message: "Hello, how are you?"')
                ->generate();

            $this->line("  Text: {$response->text}");
            $this->line('  Structured: '.json_encode($response->structured));
            $this->line("  Finish reason: {$response->finishReason->name}");

            $pass = is_array($response->structured)
                && isset($response->structured['intent'])
                && isset($response->structured['confidence']);
            $this->line($pass ? '  PASS' : '  FAIL - Missing intent/confidence in structured output');

            return $pass;
        } catch (\Throwable $e) {
            $this->error("  ERROR: {$e->getMessage()}");

            return false;
        }
    }

    private function testEmbeddings(): bool
    {
        $this->info('--- Test: Embeddings ---');

        try {
            $response = Prism::embeddings()
                ->using('workers-ai', $this->embeddingModel)
                ->fromInput('Hello world, this is a test of embeddings.')
                ->generate();

            $count = count($response->embeddings);
            $dims = count($response->embeddings[0]->embedding);
            $this->line("  Embeddings count: {$count}");
            $this->line("  Dimensions: {$dims}");
            $this->line("  Usage tokens: {$response->usage->tokens}");
            $this->line('  First 5 values: '.json_encode(array_slice($response->embeddings[0]->embedding, 0, 5)));

            $pass = $count === 1 && $dims > 0;
            $this->line($pass ? '  PASS' : '  FAIL - Expected 1 embedding with >0 dimensions');

            return $pass;
        } catch (\Throwable $e) {
            $this->error("  ERROR: {$e->getMessage()}");

            return false;
        }
    }

    private function testTools(): bool
    {
        $this->info('--- Test: Tool Calling ---');

        try {
            $tool = (new Tool)
                ->as('get_weather')
                ->for('Get the current weather for a location')
                ->withStringParameter('location', 'The city name')
                ->using(fn (string $location) => "72°F and sunny in {$location}");

            $response = Prism::text()
                ->using('workers-ai', $this->model)
                ->withSystemPrompt('You are a helpful assistant. Use the provided tools to answer questions.')
                ->withTools([$tool])
                ->withMaxSteps(3)
                ->withClientOptions(['timeout' => 120])
                ->withPrompt('What is the weather in San Francisco?')
                ->asText();

            $this->line("  Response: {$response->text}");
            $this->line("  Finish reason: {$response->finishReason->name}");
            $this->line("  Steps: {$response->steps->count()}");
            $this->line('  Tool calls: '.count($response->toolCalls));
            $this->line('  Tool results: '.count($response->toolResults));

            if (! empty($response->toolResults)) {
                $this->line('  Tool result: '.(is_string($response->toolResults[0]->result) ? $response->toolResults[0]->result : json_encode($response->toolResults[0]->result)));
            }

            // Verify the tool was called and returned results
            $pass = count($response->toolResults) > 0
                && $response->steps->count() > 1;
            $this->line($pass ? '  PASS' : '  FAIL - Expected tool calls with results');

            return $pass;
        } catch (\Throwable $e) {
            if ($e instanceof \Illuminate\Http\Client\RequestException) {
                $this->error("  HTTP {$e->response->status()}: {$e->response->body()}");
            }
            $this->error("  ERROR: {$e->getMessage()}");

            return false;
        }
    }

    private function testStream(): bool
    {
        $this->info('--- Test: Streaming ---');

        try {
            $stream = Prism::text()
                ->using('workers-ai', $this->model)
                ->withPrompt('Count from 1 to 5, one number per line.')
                ->asStream();

            $chunks = 0;
            $fullText = '';

            foreach ($stream as $event) {
                if ($event instanceof \Prism\Prism\Streaming\Events\TextDeltaEvent) {
                    $chunks++;
                    $fullText .= $event->delta;
                }
            }

            $this->line("  Chunks received: {$chunks}");
            $this->line('  Full text: '.substr(str_replace("\n", ' ', $fullText), 0, 100));

            $pass = $chunks > 1 && str_contains($fullText, '1') && str_contains($fullText, '5');
            $this->line($pass ? '  PASS' : '  FAIL - Expected multiple chunks with numbers 1-5');

            return $pass;
        } catch (\Throwable $e) {
            $this->error("  ERROR: {$e->getMessage()}");

            return false;
        }
    }

    private function testAgent(): bool
    {
        $this->info('--- Test: Laravel AI SDK agent() ---');

        try {
            $response = \Laravel\Ai\agent(instructions: 'You are a helpful assistant. Be very brief.')
                ->prompt(
                    'What is 3 + 3? Reply with just the number.',
                    provider: 'workers-ai',
                    model: 'workers-ai/@cf/meta/llama-3.3-70b-instruct-fp8-fast',
                );

            $text = $response->text;
            $this->line("  Response text: {$text}");

            $pass = ! empty($text);
            $this->line($pass ? '  PASS' : '  FAIL - Expected non-empty text from agent');

            return $pass;
        } catch (\Throwable $e) {
            $this->error("  ERROR: {$e->getMessage()}");

            return false;
        }
    }

    private function testReasoningAgent(): bool
    {
        $this->info('--- Test: Laravel AI SDK agent() with Kimi K2.5 ---');

        try {
            $response = \Laravel\Ai\agent(instructions: 'You are a helpful math tutor. Show your work briefly.')
                ->prompt(
                    'What is 25 * 4?',
                    provider: 'workers-ai',
                    model: 'workers-ai/@cf/moonshotai/kimi-k2.5',
                );

            $text = $response->text;
            $this->line("  Response text: {$text}");

            $thinking = $response->steps[0]->additionalContent['thinking'] ?? null;
            $hasThinking = is_string($thinking) && $thinking !== '';

            if ($hasThinking) {
                $this->line('  Thinking: '.substr(str_replace("\n", ' ', $thinking), 0, 150).'...');
            } else {
                $this->line('  Thinking: (none — additionalContent not passed through)');
            }

            $pass = ! empty($text) && $hasThinking && str_contains($text, '100');
            $this->line($pass ? '  PASS' : '  FAIL - Expected text with thinking content containing 100');

            return $pass;
        } catch (\Throwable $e) {
            $this->error("  ERROR: {$e->getMessage()}");

            return false;
        }
    }
}
