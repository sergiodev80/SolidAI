<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Http;

class DocumentConversionService
{
    private ?string $docIntelligenceEndpoint = null;
    private ?string $docIntelligenceKey = null;

    public function __construct()
    {
        $this->docIntelligenceEndpoint = config('services.azure_doc_intelligence.endpoint');
        $this->docIntelligenceKey = config('services.azure_doc_intelligence.key');
    }

    /**
     * Convierte un documento a DOCX
     * - PDF con texto: Usar Python pdf2docx
     * - PDF escaneado/imagen: Usar Azure Doc Intelligence
     * - Imágenes: Usar Azure Doc Intelligence
     */
    public function convertToDocx(string $inputPath, string $outputPath): bool
    {
        try {
            if (!file_exists($inputPath)) {
                Log::warning("Archivo no encontrado para conversión: {$inputPath}");
                return false;
            }

            $ext = strtolower(pathinfo($inputPath, PATHINFO_EXTENSION));

            // Si ya es DOCX, solo copiar
            if ($ext === 'docx') {
                return copy($inputPath, $outputPath);
            }

            // Crear directorio de salida si no existe
            $outputDir = dirname($outputPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            // Convertir según el tipo
            return match ($ext) {
                'pdf' => $this->convertPdfToDocx($inputPath, $outputPath),
                'jpg', 'jpeg', 'png', 'bmp' => $this->convertImageToDocx($inputPath, $outputPath),
                'doc', 'docm' => $this->convertWithLibreOffice($inputPath, $outputPath),
                default => false,
            };
        } catch (\Exception $e) {
            Log::error("Error convertiendo documento a DOCX", [
                'input' => $inputPath,
                'output' => $outputPath,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Convierte PDF a DOCX
     * Primero intenta con pdf2docx (para PDF con texto)
     * Si falla, usa Azure Doc Intelligence (para PDF escaneado)
     */
    private function convertPdfToDocx(string $inputPath, string $outputPath): bool
    {
        // Intentar primero con pdf2docx (más rápido y económico)
        if ($this->convertPdfWithPdf2Docx($inputPath, $outputPath)) {
            return true;
        }

        Log::info("pdf2docx no pudo convertir, intentando con Azure Doc Intelligence");

        // Si pdf2docx falla, usar Azure Doc Intelligence (para PDF escaneado)
        return $this->convertPdfWithDocIntelligence($inputPath, $outputPath);
    }

    /**
     * Convierte PDF con texto usando pdf2docx (Python)
     */
    private function convertPdfWithPdf2Docx(string $inputPath, string $outputPath): bool
    {
        try {
            // Verificar si pdf2docx está instalado
            $process = Process::timeout(5)->run(['which', 'pdf2docx']);
            if (!$process->successful()) {
                // pdf2docx no está disponible, fallar rápido sin intentar instalar
                // (la instalación en contexto web no funcionará de todas formas)
                Log::info("pdf2docx no está instalado, intentando fallback a Azure Doc Intelligence");
                return false;
            }

            // Convertir PDF a DOCX
            $command = [
                'pdf2docx',
                'convert',
                $inputPath,
                $outputPath,
            ];

            $process = Process::timeout(60)->run($command);

            if ($process->successful() && file_exists($outputPath)) {
                Log::info("PDF convertido exitosamente con pdf2docx", [
                    'input' => $inputPath,
                    'output' => $outputPath,
                ]);
                return true;
            }

            Log::warning("pdf2docx conversion failed", [
                'input' => $inputPath,
                'error' => $process->errorOutput(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::warning("pdf2docx conversion exception", [
                'input' => $inputPath,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Convierte PDF escaneado/imagen con Azure Doc Intelligence
     */
    private function convertPdfWithDocIntelligence(string $inputPath, string $outputPath): bool
    {
        try {
            if (!$this->docIntelligenceEndpoint || !$this->docIntelligenceKey) {
                Log::warning("Azure Doc Intelligence no está configurado");
                return false;
            }

            // Leer archivo
            $fileContent = file_get_contents($inputPath);
            if ($fileContent === false) {
                Log::error("No se pudo leer archivo PDF");
                return false;
            }

            // Llamar a Azure Doc Intelligence
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->docIntelligenceKey,
                'Content-Type' => 'application/octet-stream',
            ])
            ->timeout(120)
            ->post(
                $this->docIntelligenceEndpoint . '/documentintelligence/documentModels/prebuilt-document:analyze?api-version=2024-02-29-preview',
                $fileContent
            );

            if (!$response->successful()) {
                Log::error("Azure Doc Intelligence error", [
                    'status' => $response->status(),
                    'error' => $response->body(),
                ]);
                return false;
            }

            // Obtener resultados - manejar UTF-8 malformado
            $responseBody = $response->body();

            // Limpiar caracteres UTF-8 malformados antes de parsear JSON
            $responseBody = mb_convert_encoding($responseBody, 'UTF-8', 'UTF-8');
            $responseBody = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $responseBody);

            $result = json_decode($responseBody, true);
            if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
                Log::error("JSON parse error from Azure response", [
                    'input' => $inputPath,
                    'error' => json_last_error_msg(),
                    'body_sample' => substr($responseBody, 0, 500),
                ]);
                return false;
            }

            // Extraer texto y crear DOCX
            $text = $this->extractTextFromDocIntelligenceResult($result);

            // Crear documento DOCX con el texto extraído
            return $this->createDocxFromText($text, $outputPath);
        } catch (\Exception $e) {
            Log::error("Azure Doc Intelligence conversion error", [
                'input' => $inputPath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Convierte imagen a DOCX usando Azure Doc Intelligence
     */
    private function convertImageToDocx(string $inputPath, string $outputPath): bool
    {
        try {
            if (!$this->docIntelligenceEndpoint || !$this->docIntelligenceKey) {
                Log::warning("Azure Doc Intelligence no está configurado, usando fallback");
                return $this->convertImageToDocxFallback($inputPath, $outputPath);
            }

            // Leer imagen
            $imageContent = file_get_contents($inputPath);
            if ($imageContent === false) {
                Log::error("No se pudo leer archivo de imagen");
                return false;
            }

            // Llamar a Azure Doc Intelligence
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->docIntelligenceKey,
                'Content-Type' => 'application/octet-stream',
            ])
            ->timeout(120)
            ->post(
                $this->docIntelligenceEndpoint . '/documentintelligence/documentModels/prebuilt-document:analyze?api-version=2024-02-29-preview',
                $imageContent
            );

            if (!$response->successful()) {
                Log::warning("Azure Doc Intelligence failed, usando fallback", [
                    'status' => $response->status(),
                ]);
                return $this->convertImageToDocxFallback($inputPath, $outputPath);
            }

            // Extraer texto - manejar UTF-8 malformado
            $responseBody = $response->body();

            // Limpiar caracteres UTF-8 malformados antes de parsear JSON
            $responseBody = mb_convert_encoding($responseBody, 'UTF-8', 'UTF-8');
            $responseBody = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $responseBody);

            try {
                $result = json_decode($responseBody, true);
                if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
                    Log::warning("JSON parse error from Azure in image conversion, using fallback", [
                        'error' => json_last_error_msg(),
                    ]);
                    return $this->convertImageToDocxFallback($inputPath, $outputPath);
                }
            } catch (\Exception $e) {
                Log::warning("JSON parsing exception in image conversion, using fallback", [
                    'error' => $e->getMessage(),
                ]);
                return $this->convertImageToDocxFallback($inputPath, $outputPath);
            }

            $text = $this->extractTextFromDocIntelligenceResult($result);

            // Crear DOCX con texto e imagen
            return $this->createDocxFromTextAndImage($text, $inputPath, $outputPath);
        } catch (\Exception $e) {
            Log::warning("Azure Doc Intelligence image conversion failed, usando fallback", [
                'error' => $e->getMessage(),
            ]);
            return $this->convertImageToDocxFallback($inputPath, $outputPath);
        }
    }

    /**
     * Fallback: Insertar imagen en DOCX sin OCR
     */
    private function convertImageToDocxFallback(string $inputPath, string $outputPath): bool
    {
        try {
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $section = $phpWord->addSection();

            $imageSize = getimagesize($inputPath);
            if (!$imageSize) {
                Log::warning("No se pueden obtener dimensiones de la imagen");
                return false;
            }

            $maxWidth = 19;
            $width = min($maxWidth, $imageSize[0] / 37.8);

            $section->addImage($inputPath, [
                'width' => $width,
                'height' => null,
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            ]);

            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($outputPath);

            return file_exists($outputPath);
        } catch (\Exception $e) {
            Log::error("Fallback image conversion error", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Extrae texto del resultado de Azure Doc Intelligence
     */
    private function extractTextFromDocIntelligenceResult(array $result): string
    {
        try {
            $text = '';

            if (isset($result['analyzeResult']['content'])) {
                $text = $result['analyzeResult']['content'];
            } elseif (isset($result['analyzeResult']['paragraphs'])) {
                foreach ($result['analyzeResult']['paragraphs'] as $paragraph) {
                    if (isset($paragraph['content'])) {
                        $text .= $paragraph['content'] . "\n";
                    }
                }
            }

            // Limpiar caracteres UTF-8 malformados
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
            $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

            return trim($text);
        } catch (\Exception $e) {
            Log::warning("Error extrayendo texto de Doc Intelligence", [
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * Crea DOCX desde texto
     */
    private function createDocxFromText(string $text, string $outputPath): bool
    {
        try {
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $section = $phpWord->addSection();

            // Dividir en párrafos
            $paragraphs = explode("\n", $text);
            foreach ($paragraphs as $para) {
                if (trim($para) !== '') {
                    $section->addText($para);
                }
            }

            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($outputPath);

            return file_exists($outputPath);
        } catch (\Exception $e) {
            Log::error("Error creando DOCX desde texto", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Crea DOCX desde texto e imagen
     */
    private function createDocxFromTextAndImage(string $text, string $imagePath, string $outputPath): bool
    {
        try {
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $section = $phpWord->addSection();

            // Insertar imagen
            $imageSize = getimagesize($imagePath);
            if ($imageSize) {
                $maxWidth = 19;
                $width = min($maxWidth, $imageSize[0] / 37.8);

                $section->addImage($imagePath, [
                    'width' => $width,
                    'height' => null,
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                ]);

                $section->addTextBreak();
            }

            // Insertar texto extraído
            $paragraphs = explode("\n", $text);
            foreach ($paragraphs as $para) {
                if (trim($para) !== '') {
                    $section->addText($para);
                }
            }

            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($outputPath);

            return file_exists($outputPath);
        } catch (\Exception $e) {
            Log::error("Error creando DOCX desde texto e imagen", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Convierte documento usando LibreOffice (para DOC, DOCM)
     */
    private function convertWithLibreOffice(string $inputPath, string $outputPath): bool
    {
        try {
            $outputDir = dirname($outputPath);

            $process = Process::timeout(60)->run([
                'libreoffice',
                '--headless',
                '--convert-to', 'docx',
                '--outdir', $outputDir,
                $inputPath,
            ]);

            if (!$process->successful()) {
                Log::error("LibreOffice conversion failed", [
                    'input' => $inputPath,
                    'error' => $process->errorOutput(),
                ]);
                return false;
            }

            $generatedFile = $outputDir . '/' . pathinfo($inputPath, PATHINFO_FILENAME) . '.docx';

            if (file_exists($generatedFile) && $generatedFile !== $outputPath) {
                return rename($generatedFile, $outputPath);
            }

            return file_exists($outputPath);
        } catch (\Exception $e) {
            Log::error("LibreOffice conversion exception", [
                'input' => $inputPath,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
