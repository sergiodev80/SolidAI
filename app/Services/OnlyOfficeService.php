<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Firebase\JWT\JWT;

class OnlyOfficeService
{
    private ?string $url = null;
    private ?string $jwtSecret = null;

    public function __construct()
    {
        $this->url = config('services.onlyoffice.url');
        $this->jwtSecret = config('services.onlyoffice.jwt_secret');
    }

    /**
     * Genera un token JWT para OnlyOffice
     */
    public function generateToken(array $payload): string
    {
        try {
            if (!$this->jwtSecret) {
                Log::warning("OnlyOffice JWT secret no configurado");
                return '';
            }

            return JWT::encode($payload, $this->jwtSecret, 'HS256');
        } catch (\Exception $e) {
            Log::error("Error generando JWT para OnlyOffice", [
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * Crea la configuración para inicializar OnlyOffice
     */
    public function createEditorConfig(
        string $documentUrl,
        string $documentTitle,
        ?string $userId = null,
        ?string $userName = null,
        string $mode = 'edit'
    ): array
    {
        try {
            $callbackUrl = route('onlyoffice.callback');
        } catch (\Exception $e) {
            $callbackUrl = '/api/onlyoffice/callback';
            Log::warning("No se pudo obtener ruta de callback, usando default", [
                'error' => $e->getMessage(),
            ]);
        }

        $config = [
            'document' => [
                'fileType' => 'docx',
                'key' => md5($documentUrl . time()),
                'title' => $documentTitle,
                'url' => $documentUrl,
            ],
            'documentType' => 'text',
            'editorConfig' => [
                'mode' => $mode,
                'callbackUrl' => $callbackUrl,
                'user' => [
                    'id' => $userId ?? auth()->id() ?? 'anonymous',
                    'name' => $userName ?? auth()->user()->name ?? 'Usuario',
                ],
            ],
            'height' => '100%',
            'width' => '100%',
        ];

        // Generar JWT si está configurado
        if ($this->jwtSecret) {
            $jwtPayload = [
                'document' => $config['document'],
                'documentType' => $config['documentType'],
                'editorConfig' => $config['editorConfig'],
            ];

            $config['token'] = $this->generateToken($jwtPayload);
        }

        return $config;
    }

    /**
     * Valida un callback de OnlyOffice
     */
    public function validateCallback(string $token): bool
    {
        try {
            if (!$this->jwtSecret) {
                return false;
            }

            JWT::decode($token, $this->jwtSecret, ['HS256']);
            return true;
        } catch (\Exception $e) {
            Log::warning("Error validando callback de OnlyOffice", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Obtiene la URL base de OnlyOffice
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Verifica si OnlyOffice está configurado
     */
    public function isConfigured(): bool
    {
        return !empty($this->url) && !empty($this->jwtSecret);
    }
}
