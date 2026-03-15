<?php

namespace App\Ai\Concerns;

use Closure;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Ai;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Events\AgentFailedOver;
use Laravel\Ai\Exceptions\AiException;
use Laravel\Ai\Exceptions\FailoverableException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Laravel\Ai\Promptable;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Providers\Provider;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StreamableAgentResponse;

/**
 * Drop-in replacement for the Promptable trait that extends failover
 * to also catch provider billing/credit errors (HTTP 400).
 *
 * Use this trait INSTEAD of Promptable in agent classes.
 */
trait FailsOverOnBillingErrors
{
    use Promptable {
        Promptable::prompt as private basePrompt;
        Promptable::stream as private baseStream;
    }

    public function prompt(
        string $prompt,
        array $attachments = [],
        Lab|array|string|null $provider = null,
        ?string $model = null,
        ?int $timeout = null): AgentResponse
    {
        return $this->withBillingFailover(
            fn (Provider $provider, string $model) => $provider->prompt(
                new AgentPrompt($this, $prompt, $attachments, $provider, $model, $this->getTimeout($timeout))
            ),
            $provider,
            $model,
        );
    }

    public function stream(
        string $prompt,
        array $attachments = [],
        Lab|array|string|null $provider = null,
        ?string $model = null,
        ?int $timeout = null): StreamableAgentResponse
    {
        return $this->withBillingFailover(
            fn (Provider $provider, string $model) => $provider->stream(
                new AgentPrompt($this, $prompt, $attachments, $provider, $model, $this->getTimeout($timeout))
            ),
            $provider,
            $model,
        );
    }

    private function withBillingFailover(Closure $callback, Lab|array|string|null $provider, ?string $model): mixed
    {
        $providers = $this->getProvidersAndModels($provider, $model);
        $lastException = null;

        foreach ($providers as $provider => $model) {
            $provider = Ai::textProviderFor($this, $provider);

            $model ??= $this->getDefaultModelFor($provider);

            try {
                return $callback($provider, $model);
            } catch (RateLimitedException $e) {
                Log::warning('AI provider rate limited, retrying with backoff', [
                    'agent' => static::class,
                    'provider' => $provider::class,
                    'model' => $model,
                    'message' => $e->getMessage(),
                ]);

                // Retry once after a short backoff before failing over
                sleep(5);

                try {
                    return $callback($provider, $model);
                } catch (\Throwable) {
                    // Retry failed, fall through to failover
                }

                $lastException = $e;
                event(new AgentFailedOver($this, $provider, $model, $e));

                continue;
            } catch (FailoverableException $e) {
                $lastException = $e;

                Log::warning('AI provider failover triggered', [
                    'agent' => static::class,
                    'provider' => $provider::class,
                    'model' => $model,
                    'error' => $e->getMessage(),
                ]);

                event(new AgentFailedOver($this, $provider, $model, $e));

                continue;
            } catch (AiException $e) {
                $lastException = $e;

                if ($this->isBillingError($e)) {
                    Log::warning('AI provider billing error, failing over', [
                        'agent' => static::class,
                        'provider' => $provider::class,
                        'model' => $model,
                        'error' => $e->getMessage(),
                    ]);

                    event(new AgentFailedOver($this, $provider, $model, $e));

                    continue;
                }

                throw $e;
            }
        }

        throw $lastException ?? new AiException('No providers available for failover.');
    }

    private function isBillingError(AiException $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'credit balance')
            || str_contains($message, 'insufficient')
            || str_contains($message, 'billing')
            || str_contains($message, 'quota exceeded')
            || str_contains($message, 'exceeded your current quota');
    }
}
