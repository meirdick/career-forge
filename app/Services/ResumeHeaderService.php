<?php

namespace App\Services;

use App\Models\Resume;

class ResumeHeaderService
{
    /**
     * Default header configuration — show all fields using display name.
     *
     * @return array{name_preference: string, show_email: bool, show_phone: bool, show_location: bool, show_linkedin: bool, show_portfolio: bool}
     */
    public static function defaults(): array
    {
        return [
            'name_preference' => 'display_name',
            'show_email' => true,
            'show_phone' => true,
            'show_location' => true,
            'show_linkedin' => true,
            'show_portfolio' => true,
        ];
    }

    /**
     * Resolve the header for a resume by merging defaults → global config → per-resume config.
     *
     * @return array{name: string, email: ?string, phone: ?string, location: ?string, linkedin_url: ?string, portfolio_url: ?string}
     */
    public function resolveHeader(Resume $resume): array
    {
        $resume->loadMissing('user.professionalIdentity');
        $user = $resume->user;

        $globalConfig = $user->professionalIdentity?->resume_header_config ?? [];
        $resumeConfig = $resume->header_config ?? [];

        $config = array_merge(self::defaults(), $globalConfig, $resumeConfig);

        $name = $config['name_preference'] === 'legal_name' && $user->legal_name
            ? $user->legal_name
            : ($user->name ?? 'Candidate');

        return [
            'name' => $name,
            'email' => $config['show_email'] ? $user->email : null,
            'phone' => $config['show_phone'] ? $user->phone : null,
            'location' => $config['show_location'] ? $user->location : null,
            'linkedin_url' => $config['show_linkedin'] ? $user->linkedin_url : null,
            'portfolio_url' => $config['show_portfolio'] ? $user->portfolio_url : null,
        ];
    }
}
