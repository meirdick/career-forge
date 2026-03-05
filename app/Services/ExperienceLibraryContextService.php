<?php

namespace App\Services;

use App\Models\User;

class ExperienceLibraryContextService
{
    public static function buildContext(User $user): string
    {
        $user->load([
            'experiences.accomplishments',
            'experiences.projects',
            'experiences.skills',
            'skills',
            'educationEntries',
            'professionalIdentity',
            'evidenceEntries',
        ]);

        $sections = array_filter([
            self::formatExperiences($user),
            self::formatSkills($user),
            self::formatEducation($user),
            self::formatProfessionalIdentity($user),
            self::formatEvidence($user),
        ]);

        if (empty($sections)) {
            return '';
        }

        return "=== USER'S EXPERIENCE LIBRARY ===\n\n".implode("\n\n", $sections);
    }

    private static function formatExperiences(User $user): string
    {
        if ($user->experiences->isEmpty()) {
            return '';
        }

        $lines = ['## Work Experience'];

        foreach ($user->experiences as $experience) {
            $period = $experience->started_at?->format('M Y') ?? '?';
            $period .= ' - '.($experience->is_current ? 'Present' : ($experience->ended_at?->format('M Y') ?? '?'));

            $lines[] = "\n### {$experience->title} at {$experience->company}";
            $lines[] = "Period: {$period}";

            if ($experience->location) {
                $lines[] = "Location: {$experience->location}";
            }

            if ($experience->team_size) {
                $lines[] = "Team size: {$experience->team_size}";
            }

            if ($experience->description) {
                $lines[] = $experience->description;
            }

            if ($experience->accomplishments->isNotEmpty()) {
                $lines[] = 'Accomplishments:';
                foreach ($experience->accomplishments as $accomplishment) {
                    $line = "- {$accomplishment->title}";
                    if ($accomplishment->impact) {
                        $line .= " (Impact: {$accomplishment->impact})";
                    }
                    $lines[] = $line;
                }
            }

            if ($experience->projects->isNotEmpty()) {
                $lines[] = 'Projects:';
                foreach ($experience->projects as $project) {
                    $line = "- {$project->name}";
                    if ($project->role) {
                        $line .= " (Role: {$project->role})";
                    }
                    if ($project->outcome) {
                        $line .= " - {$project->outcome}";
                    }
                    $lines[] = $line;
                }
            }

            if ($experience->skills->isNotEmpty()) {
                $skillNames = $experience->skills->pluck('name')->join(', ');
                $lines[] = "Skills used: {$skillNames}";
            }
        }

        return implode("\n", $lines);
    }

    private static function formatSkills(User $user): string
    {
        if ($user->skills->isEmpty()) {
            return '';
        }

        $lines = ['## Skills'];

        $grouped = $user->skills->groupBy(fn ($skill) => $skill->category?->value ?? 'other');

        foreach ($grouped as $category => $skills) {
            $label = ucfirst($category);
            $skillList = $skills->map(function ($skill) {
                $entry = $skill->name;
                if ($skill->proficiency) {
                    $entry .= " ({$skill->proficiency->value})";
                }

                return $entry;
            })->join(', ');

            $lines[] = "{$label}: {$skillList}";
        }

        return implode("\n", $lines);
    }

    private static function formatEducation(User $user): string
    {
        if ($user->educationEntries->isEmpty()) {
            return '';
        }

        $lines = ['## Education & Credentials'];

        foreach ($user->educationEntries as $entry) {
            $line = "- {$entry->title}";
            if ($entry->field) {
                $line .= " in {$entry->field}";
            }
            if ($entry->institution) {
                $line .= " ({$entry->institution})";
            }
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    private static function formatProfessionalIdentity(User $user): string
    {
        if (! $user->professionalIdentity) {
            return '';
        }

        $identity = $user->professionalIdentity;
        $lines = ['## Professional Identity'];

        $fields = [
            'values' => 'Values',
            'philosophy' => 'Philosophy',
            'passions' => 'Passions',
            'leadership_style' => 'Leadership style',
            'collaboration_approach' => 'Collaboration approach',
            'communication_style' => 'Communication style',
            'cultural_preferences' => 'Cultural preferences',
        ];

        foreach ($fields as $field => $label) {
            if ($identity->{$field}) {
                $lines[] = "{$label}: {$identity->{$field}}";
            }
        }

        if (count($lines) === 1) {
            return '';
        }

        return implode("\n", $lines);
    }

    private static function formatEvidence(User $user): string
    {
        if ($user->evidenceEntries->isEmpty()) {
            return '';
        }

        $lines = ['## Evidence & Portfolio'];

        foreach ($user->evidenceEntries as $entry) {
            $line = "- [{$entry->type}] {$entry->title}";
            if ($entry->url) {
                $line .= " ({$entry->url})";
            }
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }
}
