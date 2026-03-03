<?php

namespace Database\Factories;

use App\Enums\ResumeSectionType;
use App\Models\Resume;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ResumeSection>
 */
class ResumeSectionFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(ResumeSectionType::cases());

        return [
            'resume_id' => Resume::factory(),
            'type' => $type,
            'title' => $type->value === 'custom' ? fake()->sentence(2) : ucfirst($type->value),
            'sort_order' => 0,
            'selected_variant_id' => null,
        ];
    }
}
