<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CreditBalance>
 */
class CreditBalanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'balance' => 0,
            'lifetime_purchased' => 0,
            'lifetime_consumed' => 0,
        ];
    }

    public function withBalance(int $balance): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => $balance,
            'lifetime_purchased' => $balance,
        ]);
    }
}
