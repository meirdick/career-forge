<?php

namespace App\Enums;

enum AiPurpose: string
{
    case ResumeParsing = 'resume_parsing';
    case JobAnalysis = 'job_analysis';
    case GapAnalysis = 'gap_analysis';
    case ResumeGeneration = 'resume_generation';
    case CoverLetter = 'cover_letter';
    case ChatMessage = 'chat_message';
    case ContentEnhance = 'content_enhance';
    case GapReframe = 'gap_reframe';
    case ExperienceExtract = 'experience_extract';
    case TransparencyPage = 'transparency_page';
    case LinkIndexing = 'link_indexing';
}
