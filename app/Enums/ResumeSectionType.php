<?php

namespace App\Enums;

enum ResumeSectionType: string
{
    case Summary = 'summary';
    case Experience = 'experience';
    case Skills = 'skills';
    case Education = 'education';
    case Projects = 'projects';
    case Publications = 'publications';
    case Certifications = 'certifications';
    case Custom = 'custom';
}
