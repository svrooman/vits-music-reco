<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'spotify' => [
        'api_url' => env('SPOTIFY_API_URL', 'https://api.spotify.com/v1'),
        'client_id' => env('SPOTIFY_CLIENT_ID'),
        'client_secret' => env('SPOTIFY_CLIENT_SECRET'),
        'redirect_uri' => env('SPOTIFY_REDIRECT_URI'),
    ],

    'tidal' => [
        'api_url' => env('TIDAL_API_URL', 'https://openapi.tidal.com'),
        'client_id' => env('TIDAL_CLIENT_ID'),
        'client_secret' => env('TIDAL_CLIENT_SECRET'),
        'redirect_uri' => env('TIDAL_REDIRECT_URI'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],

    'claude' => [
        'api_key' => env('CLAUDE_API_KEY'),
    ],

    'local_library' => [
        'api_url' => env('LOCAL_LIBRARY_API_URL', 'http://minipc.local:8888'),
        'rag_api_url' => env('LOCAL_LIBRARY_RAG_API_URL', 'http://minipc.local:8889'),
    ],

    'ai' => [
        'provider' => env('AI_PROVIDER', 'claude'), // claude or openai
    ],

];
