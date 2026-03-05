<?php

namespace App\Enums;

enum ResumeTemplate: string
{
    case Classic = 'classic';
    case ModernCV = 'moderncv';
    case Sb2nov = 'sb2nov';
    case EngineeringResumes = 'engineeringresumes';
    case EngineeringClassic = 'engineeringclassic';

    public function label(): string
    {
        return match ($this) {
            self::Classic => 'Classic',
            self::ModernCV => 'Modern CV',
            self::Sb2nov => 'SB2Nov',
            self::EngineeringResumes => 'Engineering',
            self::EngineeringClassic => 'Engineering Classic',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Classic => 'Traditional single-column layout with clean typography',
            self::ModernCV => 'Two-column layout with accent colors and icons',
            self::Sb2nov => 'Compact single-column format popular in tech',
            self::EngineeringResumes => 'Dense, achievement-focused engineering layout',
            self::EngineeringClassic => 'Clean engineering format with clear section hierarchy',
        };
    }
}
