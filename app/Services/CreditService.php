<?php

namespace App\Services;

use App\Enums\AiPurpose;
use App\Enums\CreditTransactionType;
use App\Models\CreditBalance;
use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreditService
{
    public function purchaseCredits(User $user, int $amount, ?string $polarOrderId = null): CreditTransaction
    {
        return DB::transaction(function () use ($user, $amount, $polarOrderId) {
            $balance = CreditBalance::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'lifetime_purchased' => 0, 'lifetime_consumed' => 0],
            );

            $balance->increment('balance', $amount);
            $balance->increment('lifetime_purchased', $amount);

            return CreditTransaction::create([
                'user_id' => $user->id,
                'type' => CreditTransactionType::Purchase,
                'amount' => $amount,
                'balance_after' => $balance->fresh()->balance,
                'description' => "Purchased {$amount} credits",
                'polar_order_id' => $polarOrderId,
                'created_at' => now(),
            ]);
        });
    }

    public function consumeCredits(User $user, int $amount, string $description, ?int $aiInteractionId = null): CreditTransaction
    {
        return DB::transaction(function () use ($user, $amount, $description, $aiInteractionId) {
            $balance = $user->creditBalance;

            if (! $balance || $balance->balance < $amount) {
                throw new \RuntimeException('Insufficient credits');
            }

            $balance->decrement('balance', $amount);
            $balance->increment('lifetime_consumed', $amount);

            return CreditTransaction::create([
                'user_id' => $user->id,
                'type' => CreditTransactionType::Consumption,
                'amount' => -$amount,
                'balance_after' => $balance->fresh()->balance,
                'description' => $description,
                'ai_interaction_id' => $aiInteractionId,
                'created_at' => now(),
            ]);
        });
    }

    public function grantSignupBonus(User $user): ?CreditTransaction
    {
        $amount = config('ai.gating.signup_bonus', 0);

        if ($amount <= 0) {
            return null;
        }

        $alreadyGranted = $user->creditTransactions()
            ->where('type', CreditTransactionType::Bonus)
            ->where('description', 'Signup bonus')
            ->exists();

        if ($alreadyGranted) {
            return null;
        }

        return $this->addBonus($user, $amount, 'Signup bonus');
    }

    public function grantReferralBonus(User $referrer, User $referred): ?CreditTransaction
    {
        $amount = config('ai.gating.referral_bonus', 0);

        if ($amount <= 0) {
            return null;
        }

        $alreadyGranted = $referrer->creditTransactions()
            ->where('type', CreditTransactionType::Bonus)
            ->whereJsonContains('metadata->referral_user_id', $referred->id)
            ->exists();

        if ($alreadyGranted) {
            return null;
        }

        return $this->addBonus($referrer, $amount, 'Referral bonus', ['referral_user_id' => $referred->id]);
    }

    public function redeemPromoCode(User $user, string $code): ?CreditTransaction
    {
        $code = strtoupper(trim($code));
        $validCode = strtoupper(config('ai.gating.promo_code', ''));
        $credits = config('ai.gating.promo_code_credits', 0);

        if ($code !== $validCode || $credits <= 0) {
            return null;
        }

        $alreadyRedeemed = $user->creditTransactions()
            ->where('type', CreditTransactionType::Bonus)
            ->whereJsonContains('metadata->promo_code', $code)
            ->exists();

        if ($alreadyRedeemed) {
            return null;
        }

        return $this->addBonus($user, $credits, "Promo code: {$code}", ['promo_code' => $code]);
    }

    public function addBonus(User $user, int $amount, string $description, ?array $metadata = null): CreditTransaction
    {
        return DB::transaction(function () use ($user, $amount, $description, $metadata) {
            $balance = CreditBalance::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'lifetime_purchased' => 0, 'lifetime_consumed' => 0],
            );

            $balance->increment('balance', $amount);

            return CreditTransaction::create([
                'user_id' => $user->id,
                'type' => CreditTransactionType::Bonus,
                'amount' => $amount,
                'balance_after' => $balance->fresh()->balance,
                'description' => $description,
                'metadata' => $metadata,
                'created_at' => now(),
            ]);
        });
    }

    public function getBalance(User $user): int
    {
        return $user->creditBalance?->balance ?? 0;
    }

    public function getCostForPurpose(AiPurpose $purpose): int
    {
        return config("ai.gating.costs.{$purpose->value}", 0);
    }
}
