<?php

namespace App\Services;

use App\Enums\AiAccessMode;
use App\Enums\AiPurpose;
use App\Models\User;

class AiGatingService
{
    public function __construct(
        protected CreditService $creditService,
    ) {}

    public function resolveAccessMode(User $user): AiAccessMode
    {
        if (config('ai.gating.mode') === 'selfhosted') {
            return AiAccessMode::Selfhosted;
        }

        if ($user->activeApiKey()->exists()) {
            return AiAccessMode::Byok;
        }

        return AiAccessMode::Credits;
    }

    public function canPerformAction(User $user, AiPurpose $purpose): bool
    {
        $mode = $this->resolveAccessMode($user);

        return match ($mode) {
            AiAccessMode::Selfhosted => true,
            AiAccessMode::Byok => true,
            AiAccessMode::Credits => $this->hasEnoughCredits($user, $purpose),
        };
    }

    public function configureRuntimeProvider(User $user, ?AiPurpose $purpose = null): void
    {
        // Always reset to original defaults to prevent config leakage between queue jobs
        $this->resetToDefaults();

        $mode = $this->resolveAccessMode($user);

        if ($mode === AiAccessMode::Byok) {
            $apiKey = $user->activeApiKey;

            if ($apiKey) {
                config([
                    'ai.providers.byok' => [
                        'driver' => $apiKey->provider,
                        'key' => $apiKey->encrypted_key,
                    ],
                    'ai.default' => 'byok',
                ]);
            }

            return;
        }

        // For non-BYOK users, apply per-purpose provider/model overrides
        if ($purpose) {
            $providers = $this->resolvePurposeProviders($purpose);

            if (! empty($providers)) {
                config(['ai.default' => $providers]);
            }

            $override = config("ai.purpose_providers.{$purpose->value}");

            if (! empty($override['model'])) {
                config(['ai.default_model' => $override['model']]);
            }
        }
    }

    /**
     * Parse the comma-separated provider chain for a given purpose.
     *
     * @return string[]
     */
    protected function resolvePurposeProviders(?AiPurpose $purpose): array
    {
        if (! $purpose) {
            return [];
        }

        $override = config("ai.purpose_providers.{$purpose->value}");

        if (empty($override['providers'])) {
            return [];
        }

        return is_array($override['providers'])
            ? $override['providers']
            : array_map('trim', explode(',', $override['providers']));
    }

    protected function resetToDefaults(): void
    {
        static $originalDefault = null;
        static $originalDefaultModel = null;

        if ($originalDefault === null) {
            $originalDefault = config('ai.default');
            $originalDefaultModel = config('ai.default_model');
        }

        config([
            'ai.default' => $originalDefault,
            'ai.default_model' => $originalDefaultModel,
        ]);

        // Remove BYOK provider config to prevent leakage between queue jobs
        if (config()->has('ai.providers.byok')) {
            config(['ai.providers.byok' => null]);
        }
    }

    public function chargeCredits(User $user, AiPurpose $purpose, ?int $aiInteractionId = null): void
    {
        $mode = $this->resolveAccessMode($user);

        if ($mode !== AiAccessMode::Credits) {
            return;
        }

        $cost = $this->creditService->getCostForPurpose($purpose);

        if ($cost <= 0) {
            return;
        }

        $this->creditService->consumeCredits(
            $user,
            $cost,
            "AI usage: {$purpose->value}",
            $aiInteractionId,
        );
    }

    protected function hasEnoughCredits(User $user, AiPurpose $purpose): bool
    {
        $cost = $this->creditService->getCostForPurpose($purpose);

        return $this->creditService->getBalance($user) >= $cost;
    }
}
