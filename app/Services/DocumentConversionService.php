<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class DocumentConversionService
{
    /**
     * Convierte un documento a DOCX
     * Soporta: PDF, DOC, JPG, JPEG, PNG
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
                'pdf', 'doc', 'docm' => $this->convertWithLibreOffice($inputPath, $outputPath),
                'jpg', 'jpeg', 'png', 'bmp' => $this->convertImageToDocx($inputPath, $outputPath),
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
     * Convierte documento usando LibreOffice (para PDF, DOC, etc)
     */
    private function convertWithLibreOffice(string $inputPath, string $outputPath): bool
    {
        try {
            $outputDir = dirname($outputPath);

            // Comando libreoffice para convertir
            $process = Process::run([
                'libreoffice',
                '--headless',
                '--convert-to', 'docx',
                '--outdir', $outputDir,
                $inputPath,
            ], timeout: 60);

            if (!$process->successful()) {
                Log::error("LibreOffice conversion failed", [
                    'input' => $inputPath,
                    'error' => $process->errorOutput(),
                ]);
                return false;
            }

            // LibreOffice crea el archivo con el mismo nombre pero extensión .docx
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
     * Convierte imagen a DOCX
     * Inserta la imagen en un documento DOCX vacío
     */
    private function convertImageToDocx(string $inputPath, string $outputPath): bool
    {
        try {
            // Crear un documento DOCX con la imagen
            // Usamos PhpWord para crear un DOCX con la imagen incrustada
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $section = $phpWord->addSection();

            // Obtener dimensiones de la imagen
            $imageSize = getimagesize($inputPath);
            if (!$imageSize) {
                Log::warning("No se pueden obtener dimensiones de la imagen: {$inputPath}");
                return false;
            }

            // Calcular tamaño para que quepa en la página (A4: 210mm x 297mm)
            $maxWidth = 19; // cm
            $width = min($maxWidth, $imageSize[0] / 37.8); // Convertir px a cm

            // Insertar imagen
            $section->addImage($inputPath, [
                'width' => $width,
                'height' => null, // Mantener proporción
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            ]);

            // Guardar documento
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($outputPath);

            return file_exists($outputPath);
        } catch (\Exception $e) {
            Log::error("Image to DOCX conversion error", [
                'input' => $inputPath,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
