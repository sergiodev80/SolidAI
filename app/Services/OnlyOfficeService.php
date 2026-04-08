<?php

namespace App\Services;

use Firebase\JWT\JWT;

class OnlyOfficeService
{
    /**
     * Genera JWT para autentificación con OnlyOffice
     */
    public static function generateJwt(array $payload): string
    {
        $secret = config('traduccion.onlyoffice.jwt_secret');

        return JWT::encode($payload, $secret, 'HS256');
    }

    /**
     * Construye configuración para OnlyOffice
     */
    public static function getEditorConfig(
        string $documentUrl,
        string $documentTitle,
        string $userId,
        string $userName,
        bool $canEdit = true
    ): array {
        $config = [
            'document' => [
                'fileType' => 'docx',
                'key' => uniqid() . time(),
                'title' => $documentTitle,
                'url' => $documentUrl,
            ],
            'documentType' => 'text',
            'editorConfig' => [
                'mode' => $canEdit ? 'edit' : 'view',
                'callbackUrl' => route('onlyoffice.callback'),
                'user' => [
                    'id' => $userId,
                    'name' => $userName,
                ],
            ],
        ];

        // Generar JWT del documento
        $config['token'] = self::generateJwt($config);

        return $config;
    }
}
