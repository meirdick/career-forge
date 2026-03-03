<?php

namespace Database\Factories;

use App\Enums\EducationType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EducationEntry>
 */
class EducationEntryFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(EducationType::cases());

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'institution' => fake()->company().' University',
            'title' => match ($type) {
                EducationType::Degree => fake()->randomElement(['Bachelor of Science', 'Master of Science', 'PhD']),
                EducationType::Certification => fake()->randomElement(['AWS Solutions Architect', 'PMP', 'Scrum Master']),
                default => fake()->sentence(3),
            },
            'field' => fake()->optional(0.7)->randomElement(['Computer Science', 'Engineering', 'Business', 'Mathematics']),
            'url' => fake()->optional(0.3)->url(),
            'description' => fake()->optional(0.5)->paragraph(),
            'started_at' => fake()->optional(0.8)->dateTimeBetween('-15 years', '-5 years'),
            'completed_at' => fake()->optional(0.7)->dateTimeBetween('-5 years', 'now'),
            'sort_order' => 0,
        ];
    }
}
