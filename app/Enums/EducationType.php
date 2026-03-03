<?php

namespace App\Enums;

enum EducationType: string
{
    case Degree = 'degree';
    case Certification = 'certification';
    case License = 'license';
    case Course = 'course';
    case Workshop = 'workshop';
    case Publication = 'publication';
    case Patent = 'patent';
    case SpeakingEngagement = 'speaking_engagement';
}
