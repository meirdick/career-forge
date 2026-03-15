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

    /*
    |--------------------------------------------------------------------------
    | AI Access Gating
    |--------------------------------------------------------------------------
    |
    | Controls how AI features are gated for users. In 'selfhosted' mode,
    | all AI features work using server-configured API keys with no billing.
    | In 'gated' mode, users must bring their own key (BYOK) or purchase
    | credits. Users receive a signup bonus of credits to get started.
    |
    */

    'gating' => [
        'mode' => env('AI_GATING_MODE', 'selfhosted'),
        'credits_per_purchase' => 500,
        'purchase_price_cents' => 500,
        'signup_bonus' => (int) env('AI_SIGNUP_BONUS', 250),
        'referral_bonus' => (int) env('AI_REFERRAL_BONUS', 250),
        'promo_code' => env('AI_PROMO_CODE', null),
        'promo_code_credits' => (int) env('AI_PROMO_CODE_CREDITS', 0),
        'costs' => [
            'resume_parsing' => 50,
            'job_analysis' => 50,
            'gap_analysis' => 50,
            'resume_generation' => 100,
            'cover_letter' => 25,
            'chat_message' => 2,
            'content_enhance' => 2,
            'gap_reframe' => 15,
            'experience_extract' => 15,
            'transparency_page' => 100,
            'link_indexing' => 15,
        ],
    ],

    'default' => ['anthropic', 'gemini'],
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

    /*
    |--------------------------------------------------------------------------
    | Per-Purpose Provider Overrides
    |--------------------------------------------------------------------------
    |
    | Override the default provider and model for specific AI purposes.
    | When set, non-BYOK requests for that purpose use this provider
    | instead of the default. BYOK users always use their own key.
    | Leave null to use the default provider for that purpose.
    |
    */

    'purpose_providers' => [
        'chat_message' => [
            'provider' => env('AI_CHAT_PROVIDER'),
            'model' => env('AI_CHAT_MODEL'),
        ],
        'content_enhance' => [
            'provider' => env('AI_CONTENT_ENHANCE_PROVIDER'),
            'model' => env('AI_CONTENT_ENHANCE_MODEL'),
        ],
        'gap_reframe' => [
            'provider' => env('AI_GAP_REFRAME_PROVIDER'),
            'model' => env('AI_GAP_REFRAME_MODEL'),
        ],
        'link_indexing' => [
            'provider' => env('AI_LINK_INDEXING_PROVIDER'),
            'model' => env('AI_LINK_INDEXING_MODEL'),
        ],
        'resume_generation' => [
            'provider' => env('AI_RESUME_GENERATION_PROVIDER', 'gemini'),
            'model' => env('AI_RESUME_GENERATION_MODEL'),
        ],
    ],

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
            'url' => env('GEMINI_URL', 'https://generativelanguage.googleapis.com/v1beta/models'),
        ],

        'anthropic' => [
            'driver' => 'anthropic',
            'key' => env('ANTHROPIC_API_KEY'),
            'url' => env('ANTHROPIC_URL', 'https://api.anthropic.com/v1'),
            'version' => env('ANTHROPIC_API_VERSION', '2023-06-01'),
        ],

        'openai' => [
            'driver' => 'openai',
            'key' => env('OPENAI_API_KEY'),
            'url' => env('OPENAI_URL', 'https://api.openai.com/v1'),
        ],

        'groq' => [
            'driver' => 'groq',
            'key' => env('GROQ_API_KEY'),
            'url' => env('GROQ_URL', 'https://api.groq.com/openai/v1'),
        ],

        'cohere' => [
            'driver' => 'cohere',
            'key' => env('COHERE_API_KEY'),
        ],

        'jina' => [
            'driver' => 'jina',
            'key' => env('JINA_API_KEY'),
        ],

        'workers-ai' => [
            'driver' => 'openai',
            'key' => env('CLOUDFLARE_AI_API_KEY'),
            'url' => env('WORKERS_AI_URL', 'https://gateway.ai.cloudflare.com/v1/5759bb6ef591b078e5480bfd5a767856/laravel-ai/workers-ai/v1'),
        ],
    ],

];
