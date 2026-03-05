<?php

namespace App\Services;

use App\Models\User;

class ExperienceImportService
{
    /**
     * Import extracted experience data into the user's experience library.
     *
     * @param  array{experiences?: array, skills?: array, accomplishments?: array, education?: array, projects?: array}  $data
     */
    public function import(User $user, array $data): void
    {
        $experienceMap = [];
        $nullish = static fn ($value) => in_array($value, [null, 'null', ''], true) ? null : $value;

        foreach ($data['experiences'] ?? [] as $index => $expData) {
            $experience = $user->experiences()->create([
                'company' => $expData['company'],
                'title' => $expData['title'],
                'location' => $nullish($expData['location'] ?? null),
                'started_at' => $expData['started_at'],
                'ended_at' => $nullish($expData['ended_at'] ?? null),
                'is_current' => $expData['is_current'] ?? false,
                'description' => $nullish($expData['description'] ?? null),
                'sort_order' => $index,
            ]);
            $experienceMap[$index] = $experience;
        }

        foreach ($data['skills'] ?? [] as $skillData) {
            $user->skills()->firstOrCreate(
                ['name' => $skillData['name']],
                ['category' => $skillData['category']],
            );
        }

        foreach ($data['accomplishments'] ?? [] as $accData) {
            $experienceId = isset($accData['experience_index'], $experienceMap[$accData['experience_index']])
                ? $experienceMap[$accData['experience_index']]->id
                : null;

            $user->accomplishments()->create([
                'experience_id' => $experienceId,
                'title' => $accData['title'],
                'description' => $accData['description'],
                'impact' => $nullish($accData['impact'] ?? null),
                'sort_order' => 0,
            ]);
        }

        foreach ($data['education'] ?? [] as $eduData) {
            $user->educationEntries()->create([
                'type' => $eduData['type'],
                'institution' => $eduData['institution'],
                'title' => $eduData['title'],
                'field' => $nullish($eduData['field'] ?? null),
                'completed_at' => $nullish($eduData['completed_at'] ?? null),
                'sort_order' => 0,
            ]);
        }

        foreach ($data['projects'] ?? [] as $projData) {
            $experienceId = isset($projData['experience_index'], $experienceMap[$projData['experience_index']])
                ? $experienceMap[$projData['experience_index']]->id
                : null;

            $user->projects()->create([
                'experience_id' => $experienceId,
                'name' => $projData['name'],
                'description' => $projData['description'],
                'role' => $nullish($projData['role'] ?? null),
                'outcome' => $nullish($projData['outcome'] ?? null),
                'sort_order' => 0,
            ]);
        }
    }
}
