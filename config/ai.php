<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider Names
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the AI providers below should be the
    | default for AI operations when no explicit provider is provided
    | for the operation. This should be any provider defined below.
    |
    */

    'default' => 'anthropic',
    'default_for_images' => 'gemini',
    'default_for_audio' => 'openai',
    'default_for_transcription' => 'openai',
    'default_for_embeddings' => 'gemini',
    'default_for_reranking' => 'cohere',

    /*
    |--------------------------------------------------------------------------
    | Application Model Mapping
    |--------------------------------------------------------------------------
    |
    | Per-feature model overrides for CareerForge AI tasks. When null,
    | the agent's default model (or provider default) is used.
    |
    */

    'default_model' => env('AI_DEFAULT_MODEL', 'claude-sonnet-4-6'),

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

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Below you may configure caching strategies for AI related operations
    | such as embedding generation. You are free to adjust these values
    | based on your application's available caching stores and needs.
    |
    */

    'caching' => [
        'embeddings' => [
            'cache' => false,
            'store' => env('CACHE_STORE', 'database'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Providers
    |--------------------------------------------------------------------------
    |
    | Below are each of your AI providers defined for this application. Each
    | represents an AI provider and API key combination which can be used
    | to perform tasks like text, image, and audio creation via agents.
    |
    */

    'providers' => [
        'gemini' => [
            'driver' => 'gemini',
            'key' => env('GEMINI_API_KEY'),
        ],

        'anthropic' => [
            'driver' => 'anthropic',
            'key' => env('ANTHROPIC_API_KEY'),
        ],

        'openai' => [
            'driver' => 'openai',
            'key' => env('OPENAI_API_KEY'),
        ],

        'groq' => [
            'driver' => 'groq',
            'key' => env('GROQ_API_KEY'),
        ],

        'cohere' => [
            'driver' => 'cohere',
            'key' => env('COHERE_API_KEY'),
        ],

        'jina' => [
            'driver' => 'jina',
            'key' => env('JINA_API_KEY'),
        ],
    ],

];
