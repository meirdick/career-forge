<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Application>
 */
class ApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'job_posting_id' => JobPosting::factory(),
            'resume_id' => null,
            'status' => fake()->randomElement(ApplicationStatus::cases()),
            'applied_at' => fake()->optional(0.7)->dateTimeBetween('-3 months', 'now'),
            'company' => fake()->company(),
            'role' => fake()->jobTitle(),
            'notes' => fake()->optional(0.5)->paragraph(),
            'cover_letter' => fake()->optional(0.3)->paragraphs(3, true),
            'submission_email' => fake()->optional(0.4)->safeEmail(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ApplicationStatus::Draft,
            'applied_at' => null,
        ]);
    }

    public function applied(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ApplicationStatus::Applied,
            'applied_at' => now(),
        ]);
    }
}
