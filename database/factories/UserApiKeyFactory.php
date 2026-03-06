<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserApiKey>
 */
class UserApiKeyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => fake()->randomElement(['anthropic', 'openai', 'gemini', 'groq']),
            'encrypted_key' => 'sk-test-'.fake()->sha256(),
            'is_active' => false,
            'validated_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'validated_at' => now(),
        ]);
    }

    public function anthropic(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'anthropic',
        ]);
    }

    public function openai(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'openai',
        ]);
    }
}
