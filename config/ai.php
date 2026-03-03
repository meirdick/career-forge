<?php

return [

    'default_model' => env('AI_DEFAULT_MODEL', 'claude-sonnet'),

    'models' => [
        'resume_parsing' => env('AI_RESUME_PARSING_MODEL'),
        'job_analysis' => env('AI_JOB_ANALYSIS_MODEL'),
        'company_research' => env('AI_COMPANY_RESEARCH_MODEL'),
        'gap_analysis' => env('AI_GAP_ANALYSIS_MODEL'),
        'targeted_questions' => env('AI_TARGETED_QUESTIONS_MODEL'),
        'resume_generation' => env('AI_RESUME_GENERATION_MODEL'),
        'cover_letter' => env('AI_COVER_LETTER_MODEL'),
        'transparency_page' => env('AI_TRANSPARENCY_PAGE_MODEL'),
    ],

];
