<?php

namespace Database\Factories;

use App\Enums\SkillCategory;
use App\Enums\SkillProficiency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Skill>
 */
class SkillFactory extends Factory
{
    private static int $counter = 0;

    private static array $technicalSkills = ['PHP', 'JavaScript', 'Python', 'TypeScript', 'SQL', 'React', 'Laravel', 'Node.js', 'Docker', 'AWS'];

    private static array $softSkills = ['Leadership', 'Communication', 'Problem Solving', 'Teamwork', 'Time Management', 'Adaptability', 'Critical Thinking'];

    private static array $toolSkills = ['Git', 'Jira', 'Figma', 'VS Code', 'Postman', 'Slack', 'Notion', 'Confluence'];

    public function definition(): array
    {
        $category = fake()->randomElement(SkillCategory::cases());
        $suffix = self::$counter++;

        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(match ($category) {
                SkillCategory::Technical => self::$technicalSkills,
                SkillCategory::Soft => self::$softSkills,
                SkillCategory::Tool => self::$toolSkills,
                default => [ucfirst(fake()->word())],
            }).' '.$suffix,
            'category' => $category,
            'proficiency' => fake()->optional(0.8)->randomElement(SkillProficiency::cases()),
            'ai_inferred_proficiency' => null,
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }
}
