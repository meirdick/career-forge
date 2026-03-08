<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AiGatingService;
use App\Services\CreditService;
use App\Services\PolarService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function __construct(
        protected CreditService $creditService,
        protected AiGatingService $gatingService,
        protected PolarService $polarService,
    ) {}

    public function show(Request $request): Response
    {
        $user = $request->user();

        $this->reconcileOrders($user);

        return Inertia::render('settings/billing', [
            'balance' => $this->creditService->getBalance($user->fresh()),
            'transactions' => $user->creditTransactions()
                ->latest('created_at')
                ->limit(20)
                ->get(),
            'creditsPerPurchase' => config('ai.gating.credits_per_purchase'),
            'purchasePriceCents' => config('ai.gating.purchase_price_cents'),
            'accessMode' => $this->gatingService->resolveAccessMode($user)->value,
        ]);
    }

    public function checkout(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $checkoutUrl = $this->polarService->createCheckout($request->user());

        return Inertia::location($checkoutUrl);
    }

    public function success(Request $request): Response
    {
        $user = $request->user();

        $this->reconcileOrders($user);

        return Inertia::render('settings/billing', [
            'balance' => $this->creditService->getBalance($user->fresh()),
            'transactions' => $user->creditTransactions()
                ->latest('created_at')
                ->limit(20)
                ->get(),
            'creditsPerPurchase' => config('ai.gating.credits_per_purchase'),
            'purchasePriceCents' => config('ai.gating.purchase_price_cents'),
            'accessMode' => $this->gatingService->resolveAccessMode($user)->value,
            'purchaseSuccess' => $request->has('checkout_id'),
        ]);
    }

    public function redeemPromo(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate(['code' => 'required|string|max:50']);

        $transaction = $this->creditService->redeemPromoCode($request->user(), $request->input('code'));

        if (! $transaction) {
            return back()->withErrors(['code' => 'Invalid or already redeemed promo code.']);
        }

        return back()->with('success', "Redeemed {$transaction->amount} credits!");
    }

    /**
     * Reconcile any Polar orders that haven't been credited yet.
     */
    protected function reconcileOrders(User $user): void
    {
        $orders = $this->polarService->getOrdersForUser($user);

        $creditedOrderIds = $user->creditTransactions()
            ->whereNotNull('polar_order_id')
            ->pluck('polar_order_id')
            ->all();

        foreach ($orders as $order) {
            if (! in_array($order['id'], $creditedOrderIds)) {
                $this->creditService->purchaseCredits(
                    $user,
                    config('ai.gating.credits_per_purchase', 500),
                    $order['id'],
                );
            }
        }
    }
}
