<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AzureDocumentTranslationService
{
    private ?string $endpoint = null;
    private ?string $apiKey = null;
    private ?string $region = null;
    private string $apiVersion = '2024-05-01';

    public function __construct()
    {
        $this->endpoint = config('services.azure_translator.endpoint');
        $this->apiKey = config('services.azure_translator.key');
        $this->region = config('services.azure_translator.region');
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
            $fileName = basename($filePath);

            // URL del endpoint sincrónico
            $url = $this->endpoint . "/translator/document:translate?api-version={$this->apiVersion}";

            // Parámetros query
            $params = [
                'targetLanguage' => $targetLanguage,
            ];

            if ($sourceLanguage !== 'auto') {
                $params['sourceLanguage'] = $sourceLanguage;
            }

            // Construir URL con parámetros
            $url .= '&' . http_build_query($params);

            Log::info("Enviando documento a Azure Translator", [
                'url' => $url,
                'archivo' => $fileName,
                'tamaño' => strlen($fileContent),
                'idioma_destino' => $targetLanguage,
                'idioma_origen' => $sourceLanguage,
                'region' => $this->region,
            ]);

            // Hacer solicitud con multipart/form-data (requerido por Azure)
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->apiKey,
                'Ocp-Apim-Subscription-Region' => $this->region,
            ])
            ->timeout(120)
            ->attach('Document', $fileContent, $fileName)
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

            // Establecer el idioma en el documento traducido
            $this->setDocumentLanguage($translatedPath, $targetLanguage);

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
     * Establece el idioma en un documento DOCX
     * Mapea códigos de idioma de Azure a códigos de región de Office
     */
    private function setDocumentLanguage(string $docxPath, string $languageCode): bool
    {
        try {
            if (!file_exists($docxPath) || strtolower(pathinfo($docxPath, PATHINFO_EXTENSION)) !== 'docx') {
                return false;
            }

            // Mapeo de códigos de Azure a códigos de región de Office
            $languageMap = [
                'es' => 'es-ES',  // Español
                'en' => 'en-US',  // Inglés
                'pt' => 'pt-BR',  // Portugués
                'fr' => 'fr-FR',  // Francés
                'de' => 'de-DE',  // Alemán
                'it' => 'it-IT',  // Italiano
                'ja' => 'ja-JP',  // Japonés
                'zh' => 'zh-CN',  // Chino
                'ru' => 'ru-RU',  // Ruso
                'ar' => 'ar-SA',  // Árabe
            ];

            $languageTag = $languageMap[$languageCode] ?? 'es-ES';

            // Abrir DOCX como ZIP
            $zip = new \ZipArchive();
            if ($zip->open($docxPath) !== true) {
                Log::warning("No se pudo abrir DOCX para establecer idioma", [
                    'path' => $docxPath,
                ]);
                return false;
            }

            // Leer document.xml
            $documentXml = $zip->getFromName('word/document.xml');
            if (!$documentXml) {
                $zip->close();
                return false;
            }

            // Parsear XML
            $dom = new \DOMDocument();
            $dom->loadXML($documentXml);

            // Encontrar o crear el elemento rPr (run properties) en el cuerpo del documento
            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

            // Establecer idioma en todos los párrafos
            $paragraphs = $xpath->query('//w:p');
            foreach ($paragraphs as $paragraph) {
                $pPr = $xpath->query('w:pPr', $paragraph)->item(0);
                if (!$pPr) {
                    $pPr = $dom->createElement('w:pPr');
                    $paragraph->insertBefore($pPr, $paragraph->firstChild);
                }

                // Establecer idioma en propiedades de párrafo
                $rPr = $xpath->query('w:rPr', $pPr)->item(0);
                if (!$rPr) {
                    $rPr = $dom->createElement('w:rPr');
                    $pPr->appendChild($rPr);
                }

                $lang = $xpath->query('w:lang', $rPr)->item(0);
                if (!$lang) {
                    $lang = $dom->createElement('w:lang');
                    $rPr->appendChild($lang);
                }
                $lang->setAttribute('w:val', $languageTag);
            }

            // Guardar documento.xml actualizado
            $zip->addFromString('word/document.xml', $dom->saveXML());
            $zip->close();

            Log::info("Idioma establecido en documento DOCX", [
                'path' => $docxPath,
                'language' => $languageTag,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::warning("Error estableciendo idioma en DOCX", [
                'path' => $docxPath,
                'error' => $e->getMessage(),
            ]);
            return false;
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
