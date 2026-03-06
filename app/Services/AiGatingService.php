<?php

namespace App\Services;

use App\Enums\AiAccessMode;
use App\Enums\AiPurpose;
use App\Models\UsageLimit;
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

        if ($this->creditService->getBalance($user) > 0) {
            return AiAccessMode::Credits;
        }

        return AiAccessMode::FreeTier;
    }

    public function canPerformAction(User $user, AiPurpose $purpose): bool
    {
        $mode = $this->resolveAccessMode($user);

        return match ($mode) {
            AiAccessMode::Selfhosted => true,
            AiAccessMode::Byok => true,
            AiAccessMode::Credits => $this->hasEnoughCredits($user, $purpose),
            AiAccessMode::FreeTier => $this->isWithinFreeTierLimits($user, $purpose),
        };
    }

    public function configureRuntimeProvider(User $user): void
    {
        $mode = $this->resolveAccessMode($user);

        if ($mode !== AiAccessMode::Byok) {
            return;
        }

        $apiKey = $user->activeApiKey;

        if (! $apiKey) {
            return;
        }

        config([
            'ai.providers.byok' => [
                'driver' => $apiKey->provider,
                'key' => $apiKey->encrypted_key,
            ],
            'ai.default' => 'byok',
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

    public function incrementFreeTierUsage(User $user, AiPurpose $purpose): void
    {
        $mode = $this->resolveAccessMode($user);

        if ($mode !== AiAccessMode::FreeTier) {
            return;
        }

        $usageLimit = $user->usageLimit ?? UsageLimit::create(['user_id' => $user->id]);

        match ($purpose) {
            AiPurpose::JobAnalysis => $usageLimit->increment('job_postings_used'),
            AiPurpose::ResumeParsing => $usageLimit->increment('documents_used'),
            default => null,
        };
    }

    /**
     * @return array{job_postings_used: int, job_postings_limit: int, documents_used: int, documents_limit: int}
     */
    public function getFreeTierUsage(User $user): array
    {
        $usageLimit = $user->usageLimit;

        return [
            'job_postings_used' => $usageLimit?->job_postings_used ?? 0,
            'job_postings_limit' => config('ai.gating.free_tier.job_postings'),
            'documents_used' => $usageLimit?->documents_used ?? 0,
            'documents_limit' => config('ai.gating.free_tier.document_uploads'),
        ];
    }

    protected function hasEnoughCredits(User $user, AiPurpose $purpose): bool
    {
        $cost = $this->creditService->getCostForPurpose($purpose);

        return $this->creditService->getBalance($user) >= $cost;
    }

    protected function isWithinFreeTierLimits(User $user, AiPurpose $purpose): bool
    {
        $usageLimit = $user->usageLimit;

        return match ($purpose) {
            AiPurpose::JobAnalysis => ($usageLimit?->job_postings_used ?? 0) < config('ai.gating.free_tier.job_postings'),
            AiPurpose::ResumeParsing => ($usageLimit?->documents_used ?? 0) < config('ai.gating.free_tier.document_uploads'),
            default => false,
        };
    }
}
