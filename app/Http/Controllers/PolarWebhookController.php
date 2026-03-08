<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CreditService;
use App\Services\PolarService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PolarWebhookController extends Controller
{
    public function __construct(
        protected CreditService $creditService,
        protected PolarService $polarService,
    ) {}

    public function handle(Request $request): Response
    {
        $payload = $request->getContent();

        // Verify Standard Webhooks signature (required by default, disable via POLAR_WEBHOOK_VERIFY=false for local dev)
        if (config('services.polar.webhook_verify', true)) {
            $webhookSecret = config('services.polar.webhook_secret');

            if (! $webhookSecret) {
                Log::error('Polar webhook: POLAR_WEBHOOK_SECRET is not configured');

                return response('Webhook secret not configured', 500);
            }

            $webhookId = $request->header('webhook-id', '');
            $timestamp = $request->header('webhook-timestamp', '');
            $signature = $request->header('webhook-signature', '');

            if (! $this->polarService->verifyWebhookSignature($payload, $webhookId, $timestamp, $signature)) {
                Log::warning('Polar webhook: invalid signature');

                return response('Invalid signature', 403);
            }
        }

        $event = $request->input('type');

        if ($event !== 'order.created') {
            return response('', 200);
        }

        $data = $request->input('data', []);
        $metadata = $data['metadata'] ?? [];
        $userId = $metadata['user_id'] ?? null;

        if (! $userId) {
            Log::warning('Polar webhook: missing user_id in metadata', ['data' => $data]);

            return response('', 200);
        }

        $user = User::find($userId);

        if (! $user) {
            Log::warning('Polar webhook: user not found', ['user_id' => $userId]);

            return response('', 200);
        }

        $orderId = $data['id'] ?? null;
        $creditsAmount = config('ai.gating.credits_per_purchase', 500);

        $this->creditService->purchaseCredits($user, $creditsAmount, $orderId);

        Log::info('Polar webhook: credits purchased', [
            'user_id' => $userId,
            'credits' => $creditsAmount,
            'order_id' => $orderId,
        ]);

        return response('', 200);
    }
}
