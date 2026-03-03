<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Experience>
 */
class ExperienceFactory extends Factory
{
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-10 years', '-1 year');
        $isCurrent = fake()->boolean(20);

        return [
            'user_id' => User::factory(),
            'company' => fake()->company(),
            'title' => fake()->jobTitle(),
            'location' => fake()->city().', '.fake()->stateAbbr(),
            'started_at' => $startedAt,
            'ended_at' => $isCurrent ? null : fake()->dateTimeBetween($startedAt, 'now'),
            'is_current' => $isCurrent,
            'description' => fake()->paragraph(),
            'reporting_to' => fake()->optional(0.6)->name(),
            'team_size' => fake()->optional(0.5)->numberBetween(2, 50),
            'reason_for_leaving' => $isCurrent ? null : fake()->optional(0.7)->sentence(),
            'sort_order' => 0,
        ];
    }

    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_current' => true,
            'ended_at' => null,
            'reason_for_leaving' => null,
        ]);
    }
}
