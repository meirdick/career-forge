<?php

namespace App\Concerns;

use App\Enums\AiPurpose;
use App\Models\User;
use App\Services\AiGatingService;

trait ConfiguresAiForUser
{
    protected function configureAiForUser(User $user, AiPurpose $purpose): void
    {
        $gatingService = app(AiGatingService::class);

        if (! $gatingService->canPerformAction($user, $purpose)) {
            throw new \RuntimeException("User {$user->id} cannot perform AI action: {$purpose->value}");
        }

        $gatingService->configureRuntimeProvider($user, $purpose);
    }

    protected function chargeAiUsage(User $user, AiPurpose $purpose, ?int $aiInteractionId = null): void
    {
        $gatingService = app(AiGatingService::class);

        $gatingService->chargeCredits($user, $purpose, $aiInteractionId);
    }
}
