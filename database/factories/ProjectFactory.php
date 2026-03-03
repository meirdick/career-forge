<?php

namespace Database\Factories;

use App\Models\Experience;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'experience_id' => Experience::factory(),
            'name' => fake()->catchPhrase(),
            'description' => fake()->paragraph(),
            'role' => fake()->optional(0.7)->jobTitle(),
            'url' => fake()->optional(0.4)->url(),
            'scale' => fake()->optional(0.5)->randomElement(['Small', 'Medium', 'Large', 'Enterprise']),
            'outcome' => fake()->optional(0.6)->sentence(),
            'started_at' => fake()->optional(0.7)->dateTimeBetween('-5 years', '-1 year'),
            'ended_at' => fake()->optional(0.5)->dateTimeBetween('-1 year', 'now'),
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
