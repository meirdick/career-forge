<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AiInteraction>
 */
class AiInteractionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'interactable_id' => null,
            'interactable_type' => null,
            'purpose' => fake()->randomElement(['resume_parsing', 'job_analysis', 'gap_analysis', 'resume_generation', 'cover_letter']),
            'model_used' => fake()->randomElement(['claude-sonnet', 'claude-haiku', 'gpt-4o']),
            'prompt_summary' => fake()->sentence(),
            'input_tokens' => fake()->numberBetween(100, 5000),
            'output_tokens' => fake()->numberBetween(50, 3000),
            'duration_ms' => fake()->numberBetween(500, 15000),
        ];
    }
}
