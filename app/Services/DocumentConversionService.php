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
     * Si el PDF es escaneado (sin texto extraible), retorna false para usar Azure OCR
     */
    private function convertPdfWithPdf2Docx(string $inputPath, string $outputPath): bool
    {
        try {
            // Usar Python para ejecutar pdf2docx
            $pythonScript = <<<'PYTHON'
import sys
from pdf2docx import Converter

input_pdf = sys.argv[1]
output_docx = sys.argv[2]

try:
    converter = Converter(input_pdf)
    converter.convert(output_docx)
    converter.close()

    import os
    if os.path.exists(output_docx):
        print("SUCCESS")
    else:
        print("ERROR: Output file not created")
        sys.exit(1)
except Exception as e:
    print(f"ERROR: {e}")
    sys.exit(1)
PYTHON;

            $tempScript = tempnam(sys_get_temp_dir(), 'pdf2docx_');
            file_put_contents($tempScript, $pythonScript);

            $process = Process::timeout(120)->run([
                'python3',
                $tempScript,
                $inputPath,
                $outputPath,
            ]);

            @unlink($tempScript);

            if ($process->successful() && file_exists($outputPath)) {
                // Verificar si el DOCX tiene texto suficiente
                if ($this->docxHasSufficientText($outputPath)) {
                    Log::info("PDF convertido exitosamente con pdf2docx (tiene texto)", [
                        'input' => $inputPath,
                        'output' => $outputPath,
                        'size' => filesize($outputPath),
                    ]);
                    return true;
                } else {
                    // PDF escaneado sin texto extraible - será procesado con Azure OCR
                    Log::info("PDF escaneado detectado (sin texto extraible), usando Azure OCR", [
                        'input' => $inputPath,
                    ]);
                    @unlink($outputPath);
                    return false;
                }
            }

            Log::warning("pdf2docx conversion failed", [
                'input' => $inputPath,
                'output' => $process->output(),
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

            // Llamar a Azure Doc Intelligence (API asincrónica)
            // 1. POST el documento para iniciar el análisis
            Log::info("Submitting PDF to Azure Doc Intelligence API", [
                'endpoint' => $this->docIntelligenceEndpoint,
                'file_size' => strlen($fileContent),
            ]);

            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->docIntelligenceKey,
                'Content-Type' => 'application/octet-stream',
            ])
            ->timeout(120)
            ->withBody($fileContent, 'application/octet-stream')
            ->post(
                $this->docIntelligenceEndpoint . '/documentintelligence/documentModels/prebuilt-read:analyze?api-version=2024-02-29-preview'
            );

            // Azure Doc Intelligence API es asincrónica y devuelve 202 Accepted
            // con un header Operation-Location para consultar el resultado
            if ($response->status() !== 202) {
                Log::error("Azure Doc Intelligence submit failed", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            // 2. Obtener la URL de la operación
            $operationLocation = $response->header('Operation-Location');
            if (!$operationLocation) {
                Log::error("No Operation-Location header from Azure", [
                    'headers' => $response->headers(),
                ]);
                return false;
            }

            Log::info("Document submitted, polling result", [
                'operation_location' => $operationLocation,
            ]);

            // 3. Polling para obtener los resultados
            $maxAttempts = 60; // 60 intentos máximo
            $attempts = 0;
            $resultJson = null;

            while ($attempts < $maxAttempts) {
                sleep(1); // Esperar 1 segundo entre intentos
                $attempts++;

                $pollResponse = Http::withHeaders([
                    'Ocp-Apim-Subscription-Key' => $this->docIntelligenceKey,
                ])
                ->timeout(30)
                ->get($operationLocation);

                if ($pollResponse->successful()) {
                    $result = $pollResponse->json();

                    // Verificar si el análisis está completo
                    if (isset($result['status']) && $result['status'] === 'succeeded') {
                        $resultJson = $result;
                        Log::info("Document analysis completed", [
                            'attempts' => $attempts,
                        ]);
                        break;
                    } elseif (isset($result['status']) && $result['status'] === 'failed') {
                        Log::error("Azure document analysis failed", [
                            'error' => $result['error'] ?? 'Unknown error',
                        ]);
                        return false;
                    }
                } else {
                    Log::warning("Poll request failed", [
                        'attempt' => $attempts,
                        'status' => $pollResponse->status(),
                    ]);
                }
            }

            if (!$resultJson) {
                Log::error("Azure document analysis timeout", [
                    'max_attempts' => $maxAttempts,
                ]);
                return false;
            }

            // En este punto, $resultJson contiene los resultados del análisis
            // Ya hemos obtenido el JSON correctamente vía polling

            // Extraer texto y crear DOCX
            $text = $this->extractTextFromDocIntelligenceResult($resultJson);

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

            // Llamar a Azure Doc Intelligence para OCR
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->docIntelligenceKey,
                'Content-Type' => 'application/octet-stream',
            ])
            ->timeout(120)
            ->withBody($imageContent, 'application/octet-stream')
            ->post(
                $this->docIntelligenceEndpoint . '/documentintelligence/documentModels/prebuilt-read:analyze?api-version=2024-02-29-preview'
            );

            // Azure Doc Intelligence API es asincrónica y devuelve 202 Accepted
            if ($response->status() !== 202) {
                Log::warning("Azure Doc Intelligence submit failed for image, usando fallback", [
                    'status' => $response->status(),
                ]);
                return $this->convertImageToDocxFallback($inputPath, $outputPath);
            }

            // Obtener Operation-Location para polling
            $operationLocation = $response->header('Operation-Location');
            if (!$operationLocation) {
                Log::warning("No Operation-Location header for image, usando fallback");
                return $this->convertImageToDocxFallback($inputPath, $outputPath);
            }

            // Polling para obtener resultados (igual que PDFs)
            $maxAttempts = 60;
            $attempts = 0;
            $resultJson = null;

            while ($attempts < $maxAttempts) {
                sleep(1);
                $attempts++;

                $pollResponse = Http::withHeaders([
                    'Ocp-Apim-Subscription-Key' => $this->docIntelligenceKey,
                ])
                ->timeout(30)
                ->get($operationLocation);

                if ($pollResponse->successful()) {
                    $result = $pollResponse->json();

                    if (isset($result['status']) && $result['status'] === 'succeeded') {
                        $resultJson = $result;
                        break;
                    } elseif (isset($result['status']) && $result['status'] === 'failed') {
                        Log::warning("Azure image analysis failed, usando fallback", [
                            'error' => $result['error'] ?? 'Unknown error',
                        ]);
                        return $this->convertImageToDocxFallback($inputPath, $outputPath);
                    }
                }
            }

            if (!$resultJson) {
                Log::warning("Azure image analysis timeout, using fallback");
                return $this->convertImageToDocxFallback($inputPath, $outputPath);
            }

            // Extraer texto desde el resultado (ya está parseado vía polling)
            $text = $this->extractTextFromDocIntelligenceResult($resultJson);

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
     * Extrae texto del resultado de Azure Doc Intelligence (prebuilt-document)
     * Mantiene mejor la estructura con párrafos, tablas, etc.
     */
    private function extractTextFromDocIntelligenceResult(array $result): string
    {
        try {
            $text = '';

            // Debug: Log la estructura del resultado
            Log::info("Azure Doc Intelligence result structure", [
                'has_analyzeResult' => isset($result['analyzeResult']),
                'result_keys' => array_keys($result),
            ]);

            if (!isset($result['analyzeResult'])) {
                Log::warning("No analyzeResult in response", ['result_keys' => array_keys($result)]);
                return '';
            }

            $analyzeResult = $result['analyzeResult'];

            // Opción 1: Usar content si está disponible (completo)
            if (isset($analyzeResult['content'])) {
                $text = $analyzeResult['content'];
                Log::info("Using analyzeResult.content", ['length' => strlen($text)]);
            }
            // Opción 2: Extraer desde paragraphs (prebuilt-document estructura)
            elseif (isset($analyzeResult['paragraphs'])) {
                Log::info("Using analyzeResult.paragraphs", ['count' => count($analyzeResult['paragraphs'])]);

                foreach ($analyzeResult['paragraphs'] as $paragraph) {
                    if (isset($paragraph['content'])) {
                        $text .= $paragraph['content'];
                        // Agregar salto de línea si hay espacio vertical significativo
                        if (isset($paragraph['boundingRegions'])) {
                            $text .= "\n";
                        }
                    }
                }
            }
            // Opción 3: Extraer desde tables si existen
            if (isset($analyzeResult['tables']) && !empty($text) === false) {
                Log::info("Processing tables", ['count' => count($analyzeResult['tables'])]);

                foreach ($analyzeResult['tables'] as $table) {
                    if (isset($table['cells'])) {
                        foreach ($table['cells'] as $cell) {
                            if (isset($cell['content'])) {
                                $text .= $cell['content'] . "\t";
                            }
                        }
                        $text .= "\n";
                    }
                }
            }

            if (empty($text)) {
                Log::warning("No content extracted from analyzeResult", [
                    'analyzeResult_keys' => array_keys($analyzeResult),
                ]);
                return '';
            }

            // Limpiar caracteres UTF-8 malformados
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
            $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

            $finalText = trim($text);
            Log::info("Text extracted from Azure", [
                'original_length' => strlen($text),
                'final_length' => strlen($finalText),
            ]);

            return $finalText;
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
            Log::info("Creating DOCX from text", [
                'text_length' => strlen($text),
                'output_path' => $outputPath,
            ]);

            if (empty($text)) {
                Log::error("Cannot create DOCX: text is empty", [
                    'output_path' => $outputPath,
                ]);
                return false;
            }

            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $section = $phpWord->addSection();

            // Dividir en párrafos
            $paragraphs = explode("\n", $text);
            Log::info("Processing paragraphs", ['count' => count($paragraphs)]);

            foreach ($paragraphs as $para) {
                if (trim($para) !== '') {
                    $section->addText($para);
                }
            }

            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($outputPath);

            if (file_exists($outputPath)) {
                Log::info("DOCX created successfully", [
                    'path' => $outputPath,
                    'size' => filesize($outputPath),
                ]);
                return true;
            } else {
                Log::error("DOCX file not created after save", [
                    'path' => $outputPath,
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Error creando DOCX desde texto", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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

    /**
     * Detecta si un DOCX tiene texto suficiente
     * Usa unzip para extraer content.xml de forma más confiable
     * Umbral: más de 100 caracteres de texto
     */
    private function docxHasSufficientText(string $docxPath): bool
    {
        try {
            if (!file_exists($docxPath)) {
                return false;
            }

            // Un DOCX es un ZIP, extraer content.xml
            $zipPath = $docxPath;
            $tempDir = sys_get_temp_dir() . '/' . uniqid('docx_');
            mkdir($tempDir);

            // Descomprimir DOCX
            $zip = new \ZipArchive();
            if ($zip->open($docxPath) !== true) {
                Log::warning("Cannot open DOCX as ZIP", ['path' => $docxPath]);
                return false;
            }

            // Extraer document.xml (contiene el contenido)
            $documentXml = $zip->getFromName('word/document.xml');
            $zip->close();
            rmdir($tempDir);

            if ($documentXml === false) {
                Log::warning("Cannot find document.xml in DOCX", ['path' => $docxPath]);
                return false;
            }

            // Buscar etiquetas de texto <w:t> en el XML
            // Los textos escaneados solo tienen imágenes y no tienen estas etiquetas
            preg_match_all('/<w:t[^>]*>([^<]*)<\/w:t>/u', $documentXml, $matches);

            $text = implode('', $matches[1]);
            $cleanText = trim($text);
            $textLength = strlen($cleanText);
            $hasText = $textLength > 100;

            Log::info("DOCX text detection", [
                'path' => $docxPath,
                'text_length' => $textLength,
                'has_sufficient_text' => $hasText,
                'sample' => substr($cleanText, 0, 100),
            ]);

            return $hasText;
        } catch (\Exception $e) {
            Log::warning("Error detectando texto en DOCX", [
                'path' => $docxPath,
                'error' => $e->getMessage(),
            ]);
            // Si hay error al detectar, asumir que no tiene texto suficiente
            return false;
        }
    }
}
