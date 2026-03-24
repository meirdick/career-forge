<?php

namespace App\Services;

use App\Models\Application;
use App\Models\User;

class CoverLetterContextBuilder
{
    public function build(User $user, Application $application): string
    {
        $application->loadMissing(['jobPosting', 'resume.sections.selectedVariant']);
        $user->loadMissing(['professionalIdentity', 'links']);

        $contactInfo = collect([
            'Name' => $user->name,
            'Email' => $user->email,
            'Phone' => $user->phone,
            'Location' => $user->location,
            'LinkedIn' => $user->linkedin_url,
            'Portfolio' => $user->portfolio_url,
        ])->filter()->map(fn ($value, $label) => "{$label}: {$value}");

        foreach ($user->links as $link) {
            $contactInfo->push(ucfirst($link->type).': '.$link->url);
        }

        $parts = ["Candidate Contact Information:\n".$contactInfo->join("\n")];

        $parts[] = "Company: {$application->company}";
        $parts[] = "Role: {$application->role}";

        if ($application->jobPosting) {
            $parts[] = "Job Posting:\n{$application->jobPosting->raw_text}";
        }

        if ($application->resume) {
            $sections = $application->resume->sections->map(function ($section) {
                $variant = $section->selectedVariant ?? $section->variants->first();

                return $variant ? "{$section->title}:\n{$variant->content}" : null;
            })->filter()->join("\n\n");

            $parts[] = "Resume:\n{$sections}";
        }

        $identity = $user->professionalIdentity;
        if ($identity) {
            $identityParts = collect([
                'Values' => $identity->values,
                'Philosophy' => $identity->philosophy,
                'Passions' => $identity->passions,
                'Leadership Style' => $identity->leadership_style,
                'Collaboration Approach' => $identity->collaboration_approach,
                'Communication Style' => $identity->communication_style,
                'Cultural Preferences' => $identity->cultural_preferences,
            ])->filter()->map(fn ($value, $label) => "{$label}: {$value}")->join("\n");

            $parts[] = "Professional Identity:\n{$identityParts}";
        }

        return implode("\n\n", $parts);
    }
}
