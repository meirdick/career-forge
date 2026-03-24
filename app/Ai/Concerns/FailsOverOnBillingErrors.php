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
            $providerKey = $provider;
            $provider = Ai::textProviderFor($this, $provider);

            $model ??= $this->getDefaultModelFor($provider);

            Log::debug('AI provider call', [
                'agent' => static::class,
                'provider_key' => $providerKey,
                'provider_class' => $provider::class,
                'driver' => method_exists($provider, 'driver') ? $provider->driver() : 'unknown',
                'model' => $model,
                'ai_default' => config('ai.default'),
            ]);

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
                sleep(2);

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

                if ($this->isBillingError($e) || $this->isTokenLimitError($e) || $this->isTransientServerError($e)) {
                    Log::warning('AI provider error, failing over', [
                        'agent' => static::class,
                        'provider' => $provider::class,
                        'model' => $model,
                        'error_type' => $this->isBillingError($e) ? 'billing' : ($this->isTokenLimitError($e) ? 'token_limit' : 'server_error'),
                        'error' => $e->getMessage(),
                    ]);

                    event(new AgentFailedOver($this, $provider, $model, $e));

                    continue;
                }

                throw $e;
            } catch (\Throwable $e) {
                // Catch unexpected errors (e.g. TypeError from malformed provider responses)
                // so the failover chain can try the next provider
                $lastException = $e instanceof AiException ? $e : new AiException($e->getMessage(), $e->getCode(), $e);

                Log::warning('AI provider unexpected error, failing over', [
                    'agent' => static::class,
                    'provider' => $provider::class,
                    'model' => $model,
                    'error_class' => $e::class,
                    'error' => $e->getMessage(),
                ]);

                event(new AgentFailedOver($this, $provider, $model, $lastException));

                continue;
            }
        }

        $default = config('ai.default');

        if ($lastException && ($default === 'byok' || $default === ['byok'])) {
            throw new AiException(
                "Your API key encountered an error: {$lastException->getMessage()}. Please check your API key in Settings.",
                $lastException->getCode(),
                $lastException,
            );
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

    private function isTokenLimitError(AiException $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'token limit')
            || str_contains($message, 'output was truncated')
            || str_contains($message, 'max_tokens')
            || str_contains($message, 'maximum.*tokens');
    }

    private function isTransientServerError(AiException $e): bool
    {
        return $e->getCode() >= 500 && $e->getCode() < 600;
    }
}
