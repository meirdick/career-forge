<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobPosting>
 */
class JobPostingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'url' => fake()->optional(0.6)->url(),
            'raw_text' => fake()->paragraphs(5, true),
            'title' => fake()->jobTitle(),
            'company' => fake()->company(),
            'location' => fake()->city().', '.fake()->stateAbbr(),
            'seniority_level' => fake()->optional(0.7)->randomElement(['Junior', 'Mid', 'Senior', 'Lead', 'Staff', 'Principal']),
            'compensation' => fake()->optional(0.5)->randomElement(['$80k-$100k', '$100k-$130k', '$130k-$170k', '$170k-$220k']),
            'remote_policy' => fake()->optional(0.6)->randomElement(['Remote', 'Hybrid', 'On-site']),
            'parsed_data' => null,
            'analyzed_at' => null,
        ];
    }

    public function analyzed(): static
    {
        return $this->state(fn (array $attributes) => [
            'analyzed_at' => now(),
        ]);
    }
}
