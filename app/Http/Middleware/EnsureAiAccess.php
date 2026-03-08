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
            $balance = app(CreditService::class)->getBalance($user);

            if ($request->header('X-Inertia')) {
                return redirect()->back()->with('ai_access_denied', [
                    'message' => 'AI access limit reached',
                    'access_mode' => $mode->value,
                    'purpose' => $purpose,
                    'cost' => $cost,
                    'balance' => $balance,
                ]);
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'AI access limit reached',
                    'access_mode' => $mode->value,
                    'purpose' => $purpose,
                    'cost' => $cost,
                    'balance' => $balance,
                ], 402);
            }

            abort(402, 'AI access limit reached. Please add an API key or purchase credits.');
        }

        return $next($request);
    }
}
