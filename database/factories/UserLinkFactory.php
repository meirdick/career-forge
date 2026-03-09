<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserLink>
 */
class UserLinkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'url' => fake()->url(),
            'label' => fake()->optional(0.5)->words(2, true),
            'type' => fake()->randomElement(['portfolio', 'github', 'website', 'other']),
            'sort_order' => 0,
        ];
    }

    public function portfolio(): static
    {
        return $this->state(fn () => ['type' => 'portfolio']);
    }

    public function github(): static
    {
        return $this->state(fn () => [
            'type' => 'github',
            'url' => 'https://github.com/'.fake()->userName(),
        ]);
    }
}
