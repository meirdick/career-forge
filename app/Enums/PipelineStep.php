<?php

namespace App\Enums;

enum PipelineStep: string
{
    case JobPosting = 'job_posting';
    case GapAnalysis = 'gap_analysis';
    case ResumeBuilder = 'resume_builder';
    case Application = 'application';
}
