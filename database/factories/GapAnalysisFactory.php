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
                ['area' => 'PHP', 'evidence' => '8 years professional experience', 'relevance' => 'Core language requirement'],
                ['area' => 'Laravel', 'evidence' => 'Multiple production applications', 'relevance' => 'Framework expertise'],
            ],
            'gaps' => [
                ['area' => 'React', 'description' => 'No direct React experience listed', 'classification' => 'reframable', 'suggestion' => 'Vue.js experience transfers well to React'],
                ['area' => 'AWS', 'description' => 'No cloud platform experience documented', 'classification' => 'promptable', 'suggestion' => 'Have you used any cloud deployment services?'],
            ],
            'overall_match_score' => fake()->numberBetween(40, 95),
            'ai_summary' => fake()->optional(0.7)->paragraph(),
        ];
    }
}
