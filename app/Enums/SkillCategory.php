<?php

namespace App\Enums;

enum SkillCategory: string
{
    case Technical = 'technical';
    case Domain = 'domain';
    case Soft = 'soft';
    case Tool = 'tool';
    case Methodology = 'methodology';
}
