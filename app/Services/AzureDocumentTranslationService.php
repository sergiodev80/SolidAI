<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AzureDocumentTranslationService
{
    private ?string $endpoint = null;
    private ?string $apiKey = null;
    private string $apiVersion = '2024-05-01';

    public function __construct()
    {
        $this->endpoint = config('services.azure_translator.endpoint');
        $this->apiKey = config('services.azure_translator.key');
    }

    /**
     * Traduce un documento DOCX usando Azure Document Translation (sincrónico)
     *
     * @param string $filePath Ruta local del archivo DOCX
     * @param string $targetLanguage Código de idioma destino (ej: 'es', 'en', 'pt')
     * @return string|null Ruta del archivo traducido o null si falla
     */
    public function translateDocument(string $filePath, string $targetLanguage, string $sourceLanguage = 'auto'): ?string
    {
        try {
            if (!$this->endpoint || !$this->apiKey) {
                Log::warning("Azure Translator no configurado");
                return null;
            }

            if (!file_exists($filePath)) {
                Log::warning("Archivo no encontrado para traducir: {$filePath}");
                return null;
            }

            // Leer contenido del archivo
            $fileContent = file_get_contents($filePath);
            if ($fileContent === false) {
                Log::error("No se pudo leer archivo: {$filePath}");
                return null;
            }

            // Obtener extensión
            $ext = pathinfo($filePath, PATHINFO_EXTENSION);

            // URL del endpoint sincrónico
            $url = $this->endpoint . "/translator/document:translate?api-version={$this->apiVersion}";

            // Headers
            $headers = [
                'Ocp-Apim-Subscription-Key' => $this->apiKey,
                'Content-Type' => $this->getMimeType($ext),
            ];

            // Parámetros query
            $params = [
                'targetLanguage' => $targetLanguage,
            ];

            if ($sourceLanguage !== 'auto') {
                $params['sourceLanguage'] = $sourceLanguage;
            }

            // Construir URL con parámetros
            $url .= '&' . http_build_query($params);

            // Hacer solicitud con contenido binario
            $response = Http::withHeaders($headers)
                ->timeout(120)
                ->withBody($fileContent, $this->getMimeType($ext))
                ->post($url);

            if (!$response->successful()) {
                Log::error("Azure Document Translation error", [
                    'status' => $response->status(),
                    'error' => $response->body(),
                    'file' => $filePath,
                ]);
                return null;
            }

            // Guardar archivo traducido
            $translatedPath = dirname($filePath) . '/documento_traducido.' . $ext;

            if (file_put_contents($translatedPath, $response->body()) === false) {
                Log::error("No se pudo guardar archivo traducido: {$translatedPath}");
                return null;
            }

            Log::info("Documento traducido exitosamente", [
                'original' => $filePath,
                'traducido' => $translatedPath,
                'idioma_destino' => $targetLanguage,
            ]);

            return $translatedPath;
        } catch (\Exception $e) {
            Log::error("Error en traducción de documento", [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Obtiene el MIME type según la extensión
     */
    private function getMimeType(string $ext): string
    {
        return match (strtolower($ext)) {
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'doc' => 'application/msword',
            'pdf' => 'application/pdf',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain',
            'html' => 'text/html',
            'csv' => 'text/csv',
            default => 'application/octet-stream',
        };
    }
}
