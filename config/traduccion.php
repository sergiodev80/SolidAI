<?php

return [
    // Azure
    'azure' => [
        'doc_intelligence' => [
            'endpoint' => env('AZURE_DOC_INTELLIGENCE_ENDPOINT'),
            'api_key' => env('AZURE_DOC_INTELLIGENCE_KEY'),
        ],
        'translator' => [
            'endpoint' => env('AZURE_TRANSLATOR_ENDPOINT'),
            'api_key' => env('AZURE_TRANSLATOR_KEY'),
            'region' => env('AZURE_TRANSLATOR_REGION', 'centralus'),
        ],
    ],

    // FTP
    'ftp' => [
        'host' => env('FTP_HOST'),
        'username' => env('FTP_USERNAME'),
        'password' => env('FTP_PASSWORD'),
        'port' => env('FTP_PORT', 21),
        'root' => env('FTP_ROOT', '/'),
    ],

    // Local storage
    'storage' => [
        'path' => 'archivos/traducciones',
        'max_versions' => 5,
    ],

    // OnlyOffice
    'onlyoffice' => [
        'url' => env('ONLYOFFICE_URL'),
        'jwt_secret' => env('ONLYOFFICE_JWT_SECRET'),
        'theme' => env('ONLYOFFICE_THEME', 'light'), // light o dark
    ],
];
