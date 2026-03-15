<?php

namespace Database\Factories;

use App\Models\ResumeSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ResumeSectionVariant>
 */
class ResumeSectionVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'resume_section_id' => ResumeSection::factory(),
            'label' => fake()->randomElement(['Conservative', 'Balanced', 'Aggressive']),
            'content' => fake()->paragraphs(2, true),
            'emphasis' => fake()->optional(0.5)->randomElement(['technical', 'leadership', 'impact']),
            'is_ai_generated' => fake()->boolean(70),
            'is_user_edited' => false,
            'sort_order' => 0,
            'blocks' => null,
        ];
    }
}
