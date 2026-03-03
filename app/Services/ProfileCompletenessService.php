<?php

namespace App\Services;

use App\Models\User;

class ProfileCompletenessService
{
    /**
     * @return array{score: int, items: array<string, bool>}
     */
    public function calculate(User $user): array
    {
        $items = [
            'Has experiences' => $user->experiences()->exists(),
            'Has accomplishments' => $user->accomplishments()->exists(),
            'Has skills (5+)' => $user->skills()->count() >= 5,
            'Has education' => $user->educationEntries()->exists(),
            'Has professional identity' => $user->professionalIdentity !== null,
            'Has evidence entries' => $user->evidenceEntries()->exists(),
            'Has projects' => $user->projects()->exists(),
        ];

        $completed = count(array_filter($items));
        $total = count($items);
        $score = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        return [
            'score' => $score,
            'items' => $items,
        ];
    }
}
