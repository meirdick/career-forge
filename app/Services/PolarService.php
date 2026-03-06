<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class PolarService
{
    protected function baseUrl(): string
    {
        return config('services.polar.sandbox')
            ? 'https://sandbox-api.polar.sh/v1'
            : 'https://api.polar.sh/v1';
    }

    protected function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->withToken(config('services.polar.access_token'))
            ->acceptJson();
    }

    public function createCheckout(User $user): string
    {
        $response = $this->client()->post('/checkouts/', [
            'products' => [config('services.polar.credit_pack_product_id')],
            'metadata' => [
                'user_id' => (string) $user->id,
            ],
            'customer_email' => $user->email,
            'customer_name' => $user->name,
            'success_url' => route('billing.success').'?checkout_id={CHECKOUT_ID}',
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to create Polar checkout: '.$response->body());
        }

        return $response->json('url');
    }

    /**
     * Get a checkout session by ID to verify payment status.
     *
     * @return array{status: string, metadata: array, order_id: string|null}|null
     */
    public function getCheckout(string $checkoutId): ?array
    {
        $response = $this->client()->get("/checkouts/{$checkoutId}");

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        return [
            'status' => $data['status'] ?? 'unknown',
            'metadata' => $data['metadata'] ?? [],
            'order_id' => $data['order_id'] ?? null,
        ];
    }

    /**
     * Get all paid orders for a user by their user_id metadata.
     *
     * @return list<array{id: string, product_id: string, amount: int}>
     */
    public function getOrdersForUser(User $user): array
    {
        $response = $this->client()->get('/orders/', [
            'product_id' => config('services.polar.credit_pack_product_id'),
            'metadata' => ['user_id' => (string) $user->id],
            'sorting' => ['-created_at'],
            'limit' => 100,
        ]);

        if (! $response->successful()) {
            return [];
        }

        return collect($response->json('items', []))
            ->filter(fn (array $order) => $order['status'] === 'paid')
            ->map(fn (array $order) => [
                'id' => $order['id'],
                'product_id' => $order['product_id'],
                'amount' => $order['amount'],
            ])
            ->values()
            ->all();
    }

    /**
     * Verify a webhook signature using the Standard Webhooks specification.
     *
     * @see https://www.standardwebhooks.com/
     */
    public function verifyWebhookSignature(string $payload, string $webhookId, string $timestamp, string $signatureHeader): bool
    {
        $secret = config('services.polar.webhook_secret');

        if (! $secret) {
            return false;
        }

        // Secret is base64 encoded with whsec_ prefix
        $secretBytes = base64_decode(str_replace('whsec_', '', $secret));

        // Signed content: msg_id.timestamp.body
        $signedContent = "{$webhookId}.{$timestamp}.{$payload}";

        $computedSignature = base64_encode(
            hash_hmac('sha256', $signedContent, $secretBytes, true)
        );

        // Signature header can contain multiple space-delimited signatures (for key rotation)
        $signatures = explode(' ', $signatureHeader);

        foreach ($signatures as $sig) {
            // Each signature is prefixed with version: v1,<base64>
            $parts = explode(',', $sig, 2);
            if (count($parts) === 2 && $parts[0] === 'v1') {
                if (hash_equals($computedSignature, $parts[1])) {
                    return true;
                }
            }
        }

        return false;
    }
}
