<?php

namespace App\Http\Middleware;

use App\Services\AiGatingService;
use App\Services\CreditService;
use App\Services\ProfileCompletenessService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $gatingEnabled = config('ai.gating.mode') !== 'selfhosted';

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'profileCompleteness' => $user
                ? app(ProfileCompletenessService::class)->calculate($user)['score']
                : null,
            'aiAccess' => $user && $gatingEnabled ? [
                'mode' => app(AiGatingService::class)->resolveAccessMode($user)->value,
                'credits' => app(CreditService::class)->getBalance($user),
                'gatingEnabled' => true,
                'freeTierUsage' => app(AiGatingService::class)->getFreeTierUsage($user),
                'hasApiKey' => (bool) $user->activeApiKey,
            ] : [
                'gatingEnabled' => false,
            ],
        ];
    }
}
