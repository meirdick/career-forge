<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EvidenceEntry>
 */
class EvidenceEntryFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(['portfolio', 'repository', 'article', 'review', 'testimonial', 'other']);

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'title' => fake()->sentence(3),
            'url' => fake()->optional(0.7)->url(),
            'description' => fake()->optional(0.6)->paragraph(),
            'content' => fake()->optional(0.4)->paragraphs(2, true),
        ];
    }
}
