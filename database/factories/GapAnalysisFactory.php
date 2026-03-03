<?php

namespace Database\Factories;

use App\Models\IdealCandidateProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GapAnalysis>
 */
class GapAnalysisFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'ideal_candidate_profile_id' => IdealCandidateProfile::factory(),
            'strengths' => [
                ['skill' => 'PHP', 'evidence' => '8 years professional experience'],
                ['skill' => 'Laravel', 'evidence' => 'Multiple production applications'],
            ],
            'gaps' => [
                ['skill' => 'React', 'classification' => 'reframable', 'notes' => 'Has Vue.js experience which transfers'],
                ['skill' => 'AWS', 'classification' => 'promptable', 'notes' => 'Used cloud services but not specifically AWS'],
            ],
            'overall_match_score' => fake()->numberBetween(40, 95),
            'ai_summary' => fake()->optional(0.7)->paragraph(),
            'is_finalized' => false,
        ];
    }

    public function finalized(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_finalized' => true,
        ]);
    }
}
