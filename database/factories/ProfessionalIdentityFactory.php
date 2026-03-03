<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProfessionalIdentity>
 */
class ProfessionalIdentityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'values' => fake()->optional(0.8)->paragraph(),
            'philosophy' => fake()->optional(0.7)->paragraph(),
            'passions' => fake()->optional(0.6)->paragraph(),
            'leadership_style' => fake()->optional(0.5)->sentence(),
            'collaboration_approach' => fake()->optional(0.5)->sentence(),
            'communication_style' => fake()->optional(0.5)->sentence(),
            'cultural_preferences' => fake()->optional(0.4)->paragraph(),
        ];
    }
}
