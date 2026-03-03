<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TransparencyPage>
 */
class TransparencyPageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'application_id' => Application::factory(),
            'slug' => Str::slug(fake()->unique()->sentence(3)),
            'authorship_statement' => fake()->paragraph(),
            'research_summary' => fake()->paragraph(),
            'ideal_profile_summary' => fake()->paragraph(),
            'section_decisions' => [
                ['section' => 'Summary', 'variant' => 'Balanced', 'reason' => 'Best represents experience'],
                ['section' => 'Skills', 'variant' => 'Technical', 'reason' => 'Matches job requirements'],
            ],
            'tool_description' => fake()->optional(0.6)->paragraph(),
            'repository_url' => fake()->optional(0.4)->url(),
            'is_published' => false,
            'content_html' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'content_html' => '<h1>AI Transparency</h1><p>'.fake()->paragraph().'</p>',
        ]);
    }
}
