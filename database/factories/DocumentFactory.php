<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        $filename = fake()->word().'.'.fake()->randomElement(['pdf', 'docx', 'txt']);

        return [
            'user_id' => User::factory(),
            'documentable_id' => null,
            'documentable_type' => null,
            'filename' => $filename,
            'disk' => config('filesystems.default'),
            'path' => 'documents/'.$filename,
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(10000, 5000000),
            'metadata' => null,
        ];
    }

    public function resumeImport(): static
    {
        return $this->state(fn () => [
            'metadata' => ['purpose' => 'resume_import'],
        ]);
    }

    public function parsed(): static
    {
        return $this->resumeImport()->state(fn () => [
            'metadata' => [
                'purpose' => 'resume_import',
                'parsed_at' => now()->toIso8601String(),
                'text_length' => 4000,
            ],
            'parsed_data' => [
                'experiences' => [
                    ['company' => 'Acme Corp', 'title' => 'Senior Engineer', 'location' => 'San Francisco, CA', 'started_at' => '2020-01-01', 'ended_at' => null, 'is_current' => true, 'description' => 'Built scalable systems.'],
                    ['company' => 'StartupCo', 'title' => 'Engineer', 'location' => 'New York, NY', 'started_at' => '2017-06-01', 'ended_at' => '2019-12-31', 'is_current' => false, 'description' => 'Full-stack development.'],
                ],
                'skills' => [
                    ['name' => 'PHP', 'category' => 'technical'],
                    ['name' => 'Laravel', 'category' => 'technical'],
                    ['name' => 'React', 'category' => 'technical'],
                    ['name' => 'Leadership', 'category' => 'soft'],
                ],
                'accomplishments' => [
                    ['title' => 'Reduced latency by 50%', 'description' => 'Optimized database queries.', 'impact' => '50% latency reduction', 'experience_index' => 0],
                ],
                'education' => [
                    ['type' => 'degree', 'institution' => 'MIT', 'title' => 'B.S. Computer Science', 'field' => 'Computer Science', 'completed_at' => '2017-05-15'],
                ],
                'projects' => [],
                'urls' => [
                    ['url' => 'https://linkedin.com/in/test', 'type' => 'linkedin', 'label' => 'LinkedIn'],
                ],
            ],
        ]);
    }
}
