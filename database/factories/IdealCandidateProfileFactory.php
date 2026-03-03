<?php

namespace Database\Factories;

use App\Models\JobPosting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IdealCandidateProfile>
 */
class IdealCandidateProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'job_posting_id' => JobPosting::factory(),
            'required_skills' => [
                ['name' => 'PHP', 'years' => 5],
                ['name' => 'Laravel', 'years' => 3],
                ['name' => 'SQL', 'years' => 3],
            ],
            'preferred_skills' => [
                ['name' => 'React', 'years' => 2],
                ['name' => 'Docker', 'years' => 1],
            ],
            'experience_profile' => [
                'minimum_years' => 5,
                'preferred_years' => 8,
                'key_areas' => ['Backend Development', 'API Design'],
            ],
            'cultural_fit' => [
                'values' => ['Innovation', 'Collaboration'],
                'work_style' => 'Agile',
            ],
            'language_guidance' => [
                'tone' => 'Professional but personable',
                'keywords' => ['scalable', 'high-performance', 'team player'],
            ],
            'red_flags' => [
                'Job hopping without growth',
                'No experience with version control',
            ],
            'company_research' => null,
            'industry_standards' => null,
            'is_user_edited' => false,
        ];
    }
}
