<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Resume>
 */
class ResumeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'gap_analysis_id' => null,
            'job_posting_id' => null,
            'title' => fake()->sentence(3),
            'section_order' => null,
            'is_finalized' => false,
            'template' => 'classic',
            'exported_path' => null,
            'exported_format' => null,
            'generation_status' => null,
            'generation_progress' => null,
        ];
    }

    public function generating(): static
    {
        return $this->state(fn (array $attributes) => [
            'generation_status' => 'generating',
            'generation_progress' => [
                'total' => 5,
                'completed' => 2,
                'current_section' => 'Skills',
                'expected_sections' => ['Summary', 'Experience', 'Skills', 'Education', 'Projects'],
            ],
        ]);
    }

    public function finalized(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_finalized' => true,
        ]);
    }
}
