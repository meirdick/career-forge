<?php

namespace App\Http\Middleware;

use App\Enums\AiPurpose;
use App\Services\AiGatingService;
use App\Services\CreditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAiAccess
{
    public function __construct(
        protected AiGatingService $gatingService,
    ) {}

    public function handle(Request $request, Closure $next, string $purpose): Response
    {
        if (config('ai.gating.mode') === 'selfhosted') {
            return $next($request);
        }

        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $aiPurpose = AiPurpose::from($purpose);

        if (! $this->gatingService->canPerformAction($user, $aiPurpose)) {
            $mode = $this->gatingService->resolveAccessMode($user);
            $cost = app(CreditService::class)->getCostForPurpose($aiPurpose);

            if ($request->wantsJson() || $request->header('X-Inertia')) {
                return response()->json([
                    'message' => 'AI access limit reached',
                    'access_mode' => $mode->value,
                    'purpose' => $purpose,
                    'cost' => $cost,
                    'balance' => app(CreditService::class)->getBalance($user),
                    'free_tier_usage' => $this->gatingService->getFreeTierUsage($user),
                ], 402);
            }

            abort(402, 'AI access limit reached. Please add an API key or purchase credits.');
        }

        return $next($request);
    }
}
