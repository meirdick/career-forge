<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class ExperienceImportService
{
    /**
     * Import extracted experience data into the user's experience library with intelligent merging.
     *
     * @param  array{experiences?: array, skills?: array, accomplishments?: array, education?: array, projects?: array}  $data
     * @return array{created: int, merged: int, skipped: int}
     */
    public function import(User $user, array $data): array
    {
        $stats = ['created' => 0, 'merged' => 0, 'skipped' => 0];
        $experienceMap = [];
        $nullish = static fn ($value) => in_array($value, [null, 'null', ''], true) ? null : $value;

        $user->load(['experiences', 'skills', 'accomplishments', 'educationEntries', 'projects']);

        foreach ($data['experiences'] ?? [] as $index => $expData) {
            $existing = $user->experiences
                ->first(function ($e) use ($expData) {
                    if (mb_strtolower($e->company) !== mb_strtolower($expData['company'])) {
                        return false;
                    }
                    if (mb_strtolower($e->title) !== mb_strtolower($expData['title'])) {
                        return false;
                    }

                    return $this->datesOverlap(
                        $e->started_at,
                        $e->ended_at,
                        $expData['started_at'] ?? null,
                        $expData['ended_at'] ?? null,
                    );
                });

            if ($existing) {
                $filled = $this->fillBlanks($existing, [
                    'location' => $nullish($expData['location'] ?? null),
                    'description' => $nullish($expData['description'] ?? null),
                ]);

                $experienceMap[$index] = $existing;
                $stats[$filled ? 'merged' : 'skipped']++;
            } else {
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
                $stats['created']++;
            }
        }

        foreach ($data['skills'] ?? [] as $skillData) {
            $existing = $user->skills
                ->first(fn ($s) => mb_strtolower($s->name) === mb_strtolower($skillData['name']));

            if ($existing) {
                $stats['skipped']++;
            } else {
                $user->skills()->create([
                    'name' => $skillData['name'],
                    'category' => $skillData['category'],
                ]);
                $stats['created']++;
            }
        }

        foreach ($data['accomplishments'] ?? [] as $accData) {
            $experienceId = isset($accData['experience_index'], $experienceMap[$accData['experience_index']])
                ? $experienceMap[$accData['experience_index']]->id
                : null;

            $existing = $user->accomplishments
                ->first(function ($a) use ($accData, $experienceId) {
                    if (mb_strtolower($a->title) !== mb_strtolower($accData['title'])) {
                        return false;
                    }

                    return $a->experience_id === $experienceId;
                });

            if ($existing) {
                $filled = $this->fillBlanks($existing, [
                    'description' => $accData['description'] ?? null,
                    'impact' => $nullish($accData['impact'] ?? null),
                ]);
                $stats[$filled ? 'merged' : 'skipped']++;
            } else {
                $user->accomplishments()->create([
                    'experience_id' => $experienceId,
                    'title' => $accData['title'],
                    'description' => $accData['description'],
                    'impact' => $nullish($accData['impact'] ?? null),
                    'sort_order' => 0,
                ]);
                $stats['created']++;
            }
        }

        foreach ($data['education'] ?? [] as $eduData) {
            $existing = $user->educationEntries
                ->first(function ($e) use ($eduData) {
                    return mb_strtolower($e->institution) === mb_strtolower($eduData['institution'])
                        && mb_strtolower($e->title) === mb_strtolower($eduData['title']);
                });

            if ($existing) {
                $filled = $this->fillBlanks($existing, [
                    'field' => $nullish($eduData['field'] ?? null),
                    'completed_at' => $nullish($eduData['completed_at'] ?? null),
                ]);
                $stats[$filled ? 'merged' : 'skipped']++;
            } else {
                $user->educationEntries()->create([
                    'type' => $eduData['type'],
                    'institution' => $eduData['institution'],
                    'title' => $eduData['title'],
                    'field' => $nullish($eduData['field'] ?? null),
                    'completed_at' => $nullish($eduData['completed_at'] ?? null),
                    'sort_order' => 0,
                ]);
                $stats['created']++;
            }
        }

        foreach ($data['projects'] ?? [] as $projData) {
            $experienceId = isset($projData['experience_index'], $experienceMap[$projData['experience_index']])
                ? $experienceMap[$projData['experience_index']]->id
                : null;

            $existing = $user->projects
                ->first(fn ($p) => mb_strtolower($p->name) === mb_strtolower($projData['name']));

            if ($existing) {
                $filled = $this->fillBlanks($existing, [
                    'description' => $projData['description'] ?? null,
                    'role' => $nullish($projData['role'] ?? null),
                    'outcome' => $nullish($projData['outcome'] ?? null),
                    'experience_id' => $experienceId,
                ]);
                $stats[$filled ? 'merged' : 'skipped']++;
            } else {
                $user->projects()->create([
                    'experience_id' => $experienceId,
                    'name' => $projData['name'],
                    'description' => $projData['description'],
                    'role' => $nullish($projData['role'] ?? null),
                    'outcome' => $nullish($projData['outcome'] ?? null),
                    'sort_order' => 0,
                ]);
                $stats['created']++;
            }
        }

        return $stats;
    }

    public static function buildImportMessage(array $stats): string
    {
        $parts = [];

        if ($stats['created'] > 0) {
            $parts[] = "{$stats['created']} new ".($stats['created'] === 1 ? 'item' : 'items').' added';
        }

        if ($stats['merged'] > 0) {
            $parts[] = "{$stats['merged']} ".($stats['merged'] === 1 ? 'item' : 'items').' updated';
        }

        if ($stats['skipped'] > 0) {
            $parts[] = "{$stats['skipped']} duplicate".($stats['skipped'] === 1 ? '' : 's').' skipped';
        }

        if (empty($parts)) {
            return 'No items to import.';
        }

        return 'Import complete: '.implode(', ', $parts).'.';
    }

    /**
     * Fill blank (null) fields on a model without overwriting existing values.
     */
    private function fillBlanks(mixed $model, array $values): bool
    {
        $updated = false;

        foreach ($values as $field => $value) {
            if ($value !== null && ($model->{$field} === null || $model->{$field} === '')) {
                $model->{$field} = $value;
                $updated = true;
            }
        }

        if ($updated) {
            $model->save();
        }

        return $updated;
    }

    /**
     * Check if two date ranges overlap (with 90-day tolerance for approximate dates).
     */
    private function datesOverlap(mixed $startA, mixed $endA, ?string $startB, ?string $endB): bool
    {
        try {
            $a1 = Carbon::parse($startA);
            $b1 = Carbon::parse($startB);
        } catch (\Exception) {
            return false;
        }

        // If starts are within 90 days, consider overlapping (handles approximated dates)
        return $a1->diffInDays($b1) <= 90;
    }
}
