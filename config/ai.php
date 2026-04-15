<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI API Configuration
    |--------------------------------------------------------------------------
    */

    'api_url' => env('AI_API_URL', 'https://ai.hellozed.com/api/zedbot/chat'),

    'api_key' => env('AI_API_KEY', ''),

    'api_timeout' => env('AI_API_TIMEOUT', 30),

    'max_retries' => env('AI_MAX_RETRIES', 2),

    'system_prompt' => env('AI_SYSTEM_PROMPT', ''),

];
