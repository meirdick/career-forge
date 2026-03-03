<?php

namespace Database\Factories;

use App\Models\Experience;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Accomplishment>
 */
class AccomplishmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'experience_id' => Experience::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'impact' => fake()->optional(0.7)->sentence(),
            'sort_order' => 0,
        ];
    }

    public function standalone(): static
    {
        return $this->state(fn (array $attributes) => [
            'experience_id' => null,
        ]);
    }
}
