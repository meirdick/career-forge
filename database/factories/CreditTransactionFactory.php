<?php

namespace Database\Factories;

use App\Enums\CreditTransactionType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CreditTransaction>
 */
class CreditTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => CreditTransactionType::Purchase,
            'amount' => 500,
            'balance_after' => 500,
            'description' => 'Credit purchase',
            'ai_interaction_id' => null,
            'polar_order_id' => null,
            'metadata' => null,
            'created_at' => now(),
        ];
    }

    public function consumption(int $amount = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CreditTransactionType::Consumption,
            'amount' => -$amount,
            'description' => 'AI usage',
        ]);
    }
}
