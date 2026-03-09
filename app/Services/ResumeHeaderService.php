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
     * @return array{name: string, email: ?string, phone: ?string, location: ?string, linkedin_url: ?string, portfolio_links: list<array{url: string, label: string}>}
     */
    public function resolveHeader(Resume $resume): array
    {
        $resume->loadMissing('user.professionalIdentity', 'user.links');
        $user = $resume->user;

        $globalConfig = $user->professionalIdentity?->resume_header_config ?? [];
        $resumeConfig = $resume->header_config ?? [];

        $config = array_merge(self::defaults(), $globalConfig, $resumeConfig);

        $name = $config['name_preference'] === 'legal_name' && $user->legal_name
            ? $user->legal_name
            : ($user->name ?? 'Candidate');

        $portfolioLinks = [];
        if ($config['show_portfolio']) {
            $portfolioLinks = $user->links->map(fn ($link) => [
                'url' => $link->url,
                'label' => $link->displayUrl(),
            ])->values()->all();
        }

        return [
            'name' => $name,
            'email' => $config['show_email'] ? $user->email : null,
            'phone' => $config['show_phone'] ? $user->phone : null,
            'location' => $config['show_location'] ? $user->location : null,
            'linkedin_url' => $config['show_linkedin'] ? $user->linkedin_url : null,
            'portfolio_links' => $portfolioLinks,
        ];
    }
}
