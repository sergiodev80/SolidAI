<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use Exception;

class AzureTranslationService
{
    private ?string $ftpHost;
    private ?string $ftpUsername;
    private ?string $ftpPassword;
    private int $ftpPort;
    private ?string $docIntelligenceEndpoint;
    private ?string $docIntelligenceKey;
    private ?string $translatorEndpoint;
    private ?string $translatorKey;
    private ?string $translatorRegion;

    public function __construct()
    {
        $this->ftpHost = config('traduccion.ftp.host');
        $this->ftpUsername = config('traduccion.ftp.username');
        $this->ftpPassword = config('traduccion.ftp.password');
        $this->ftpPort = config('traduccion.ftp.port', 21);

        $this->docIntelligenceEndpoint = config('traduccion.azure.doc_intelligence.endpoint');
        $this->docIntelligenceKey = config('traduccion.azure.doc_intelligence.api_key');
        $this->translatorEndpoint = config('traduccion.azure.translator.endpoint');
        $this->translatorKey = config('traduccion.azure.translator.api_key');
        $this->translatorRegion = config('traduccion.azure.translator.region', 'centralus');
    }

    /**
     * Descarga archivo desde FTP
     * @param string $remoteFilename Ruta del archivo en FTP
     * @return string Ruta local temporal del archivo descargado
     */
    public function downloadFromFtp(string $remoteFilename): string
    {
        try {
            $connection = ftp_connect($this->ftpHost, $this->ftpPort, 30);
            if (!$connection) {
                throw new Exception('No se pudo conectar al servidor FTP');
            }

            $login = ftp_login($connection, $this->ftpUsername, $this->ftpPassword);
            if (!$login) {
                throw new Exception('Fallo la autenticación FTP');
            }

            ftp_pasv($connection, true);

            $localPath = storage_path('app/temp/' . basename($remoteFilename));
            $dir = dirname($localPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $downloaded = ftp_get($connection, $localPath, $remoteFilename, FTP_BINARY);
            ftp_close($connection);

            if (!$downloaded) {
                throw new Exception('No se pudo descargar el archivo desde FTP');
            }

            return $localPath;
        } catch (Exception $e) {
            throw new Exception('Error descargando archivo FTP: ' . $e->getMessage());
        }
    }

    /**
     * Extrae texto del PDF usando Azure Doc Intelligence
     * @param string $pdfPath Ruta local del PDF
     * @return string Texto extraído
     */
    public function extractTextFromPdf(string $pdfPath): string
    {
        try {
            $fileContents = file_get_contents($pdfPath);

            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->docIntelligenceKey,
                'Content-Type' => 'application/octet-stream',
            ])->post(
                $this->docIntelligenceEndpoint . '/documentintelligence:analyze?api-version=2024-02-29-preview&model-id=prebuilt-read',
                $fileContents
            );

            if (!$response->successful()) {
                throw new Exception('Error en Doc Intelligence: ' . $response->body());
            }

            $operationLocation = $response->header('Operation-Location');
            if (!$operationLocation) {
                throw new Exception('No se recibió Operation-Location de Doc Intelligence');
            }

            // Poll hasta que esté ready
            $extractedText = $this->pollDocIntelligenceResult($operationLocation);

            return $extractedText;
        } catch (Exception $e) {
            throw new Exception('Error extrayendo texto con Doc Intelligence: ' . $e->getMessage());
        }
    }

    /**
     * Poll de resultados de Doc Intelligence
     */
    private function pollDocIntelligenceResult(string $operationLocation, int $maxAttempts = 60): string
    {
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->docIntelligenceKey,
            ])->get($operationLocation);

            if ($response->json('status') === 'succeeded') {
                $pages = $response->json('analyzeResult.pages', []);
                $extractedText = '';
                foreach ($pages as $page) {
                    if (isset($page['lines'])) {
                        foreach ($page['lines'] as $line) {
                            $extractedText .= ($line['content'] ?? '') . "\n";
                        }
                    }
                }
                return $extractedText;
            }

            if ($response->json('status') === 'failed') {
                throw new Exception('Doc Intelligence falló: ' . json_encode($response->json('analyzeResult.errors')));
            }

            sleep(1); // Esperar 1 segundo antes de reintentar
        }

        throw new Exception('Timeout esperando resultado de Doc Intelligence');
    }

    /**
     * Traduce texto usando Azure Translator
     * @param string $text Texto a traducir
     * @param string $targetLanguage Código del idioma destino (ej: 'es', 'en')
     * @return string Texto traducido
     */
    public function translateText(string $text, string $targetLanguage): string
    {
        try {
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->translatorKey,
                'Ocp-Apim-Subscription-Region' => $this->translatorRegion,
            ])->post(
                $this->translatorEndpoint . '/translate?api-version=3.0&targetLanguage=' . urlencode($targetLanguage),
                [['Text' => $text]]
            );

            if (!$response->successful()) {
                throw new Exception('Error en Azure Translator: ' . $response->body());
            }

            return $response->json('0.translations.0.text', '');
        } catch (Exception $e) {
            throw new Exception('Error traduciendo con Azure Translator: ' . $e->getMessage());
        }
    }

    /**
     * Convierte texto traducido a documento Word
     * @param string $content Contenido traducido
     * @param string $filename Nombre del archivo Word a generar
     * @return string Ruta del archivo Word generado
     */
    public function createWordDocument(string $content, string $filename): string
    {
        try {
            $phpWord = new PhpWord();
            $section = $phpWord->addSection();

            // Dividir contenido en párrafos
            $paragraphs = array_filter(explode("\n", $content));
            foreach ($paragraphs as $paragraph) {
                if (!empty(trim($paragraph))) {
                    $section->addText($paragraph);
                }
            }

            $outputPath = storage_path('app/temp/' . $filename);
            $dir = dirname($outputPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $phpWord->save($outputPath);

            return $outputPath;
        } catch (Exception $e) {
            throw new Exception('Error creando documento Word: ' . $e->getMessage());
        }
    }

    /**
     * Orquesta todo el flujo: descarga FTP → Doc Intelligence → Translator → Word
     */
    public function processDocumentFullFlow(
        string $ftpPath,
        string $sourceLanguageCode,
        string $targetLanguageCode,
        string $outputFilename
    ): string {
        // 1. Descargar desde FTP
        $localPdfPath = $this->downloadFromFtp($ftpPath);

        try {
            // 2. Extraer texto con Doc Intelligence
            $extractedText = $this->extractTextFromPdf($localPdfPath);

            // 3. Traducir con Azure Translator
            $translatedText = $this->translateText($extractedText, $targetLanguageCode);

            // 4. Crear documento Word
            $wordPath = $this->createWordDocument($translatedText, $outputFilename);

            // Limpiar PDF temporal
            @unlink($localPdfPath);

            return $wordPath;
        } catch (Exception $e) {
            @unlink($localPdfPath);
            throw $e;
        }
    }
}
