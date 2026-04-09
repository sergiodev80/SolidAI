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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'azure_doc_intelligence' => [
        'endpoint' => env('AZURE_DOC_INTELLIGENCE_ENDPOINT'),
        'key' => env('AZURE_DOC_INTELLIGENCE_KEY'),
    ],

    'azure_translator' => [
        'endpoint' => env('AZURE_TRANSLATOR_ENDPOINT', 'https://api.cognitive.microsofttranslator.com'),
        'key' => env('AZURE_TRANSLATOR_KEY'),
        'region' => env('AZURE_TRANSLATOR_REGION', 'eastus'),
    ],

    'onlyoffice' => [
        'url' => env('ONLYOFFICE_URL', 'https://ds.onlyoffice.com'),
        'jwt_secret' => env('ONLYOFFICE_JWT_SECRET'),
        'jwt_header' => env('ONLYOFFICE_JWT_HEADER', 'Authorization'),
    ],

];
