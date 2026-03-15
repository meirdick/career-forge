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
            $override = config("ai.purpose_providers.{$purpose->value}");

            if (! empty($override['provider'])) {
                config(['ai.default' => $override['provider']]);

                if (! empty($override['model'])) {
                    config(['ai.default_model' => $override['model']]);
                }
            }
        }
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
