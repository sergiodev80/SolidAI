<?php

namespace App\Services;

use App\Models\PresupAdjAsignacion;
use Illuminate\Support\Facades\Log;

class TraduccionAiService
{
    private DocumentConversionService $conversionService;
    private AzureDocumentTranslationService $translationService;
    private PdfOriginalService $pdfService;

    public function __construct(
        DocumentConversionService $conversionService,
        AzureDocumentTranslationService $translationService,
        PdfOriginalService $pdfService
    ) {
        $this->conversionService = $conversionService;
        $this->translationService = $translationService;
        $this->pdfService = $pdfService;
    }

    /**
     * Obtiene el documento traducido por IA
     * Si no existe, lo crea traduciendo el original con Azure
     *
     * @param PresupAdjAsignacion $asignacion
     * @param string $targetLanguageCode Código de idioma destino (ej: 'es', 'en')
     * @return string|null Ruta del documento AI traducido o null si falla
     */
    public function obtenerDocumentoAi(PresupAdjAsignacion $asignacion, string $targetLanguageCode): ?string
    {
        try {
            $adjunto = $asignacion->adjunto;
            $presupuesto = $adjunto->presupuesto;

            if (!$presupuesto) {
                return null;
            }

            $idPresupuesto = $presupuesto->id_pres;
            $idDocumento = $adjunto->id_adjun;

            // Directorio para documento AI
            $directorioAi = "archivos/traduccion-ai/{$idPresupuesto}/{$idDocumento}";
            $rutaAi = public_path($directorioAi);

            // Verificar si ya existe documento AI traducido
            if (is_dir($rutaAi)) {
                $archivos = glob("{$rutaAi}/*");
                foreach ($archivos as $archivo) {
                    if (is_file($archivo) && preg_match('/documento_ai\.(docx|pdf|png|jpg|jpeg)$/i', $archivo)) {
                        return "/{$directorioAi}/" . basename($archivo);
                    }
                }
            }

            // Si no existe, crear directorio
            if (!is_dir($rutaAi)) {
                mkdir($rutaAi, 0755, true);
            }

            // 1. Obtener documento original
            $pdfOriginalPath = $this->pdfService->obtenerPdfOriginal($asignacion);
            if (!$pdfOriginalPath) {
                Log::warning("No se pudo obtener documento original", [
                    'id_asignacion' => $asignacion->id,
                ]);
                return null;
            }

            $rutaOriginal = public_path($pdfOriginalPath);

            // 2. Convertir a DOCX (si es necesario)
            $rutaDocxTemp = $rutaAi . '/documento_temp.docx';
            if (!$this->conversionService->convertToDocx($rutaOriginal, $rutaDocxTemp)) {
                Log::error("No se pudo convertir documento a DOCX", [
                    'id_asignacion' => $asignacion->id,
                    'original' => $rutaOriginal,
                ]);
                return null;
            }

            // 3. Traducir con Azure (con fallback si falla)
            $rutaAiDocx = $rutaAi . '/documento_ai.docx';
            $rutaTrad = $this->translationService->translateDocument(
                $rutaDocxTemp,
                $targetLanguageCode
            );

            if (!$rutaTrad) {
                Log::warning("No se pudo traducir documento con Azure, usando DOCX sin traducir como fallback", [
                    'id_asignacion' => $asignacion->id,
                ]);

                // Fallback: usar el DOCX extraído sin traducir
                if (!copy($rutaDocxTemp, $rutaAiDocx)) {
                    Log::error("No se pudo copiar DOCX sin traducir", [
                        'from' => $rutaDocxTemp,
                        'to' => $rutaAiDocx,
                    ]);
                    @unlink($rutaDocxTemp);
                    return null;
                }
            } else {
                // Traducción exitosa: mover archivo traducido al lugar correcto
                if (!rename($rutaTrad, $rutaAiDocx)) {
                    Log::error("No se pudo mover documento traducido", [
                        'from' => $rutaTrad,
                        'to' => $rutaAiDocx,
                    ]);
                    @unlink($rutaDocxTemp);
                    return null;
                }
            }

            // Limpiar archivo temporal
            @unlink($rutaDocxTemp);

            Log::info("Documento AI traducido exitosamente", [
                'id_asignacion' => $asignacion->id,
                'ruta_ai' => $rutaAiDocx,
            ]);

            return "/{$directorioAi}/documento_ai.docx";
        } catch (\Exception $e) {
            Log::error("Error en TraduccionAiService", [
                'id_asignacion' => $asignacion->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Extrae documento sin traducir (solo convierte PDF a DOCX)
     *
     * @param PresupAdjAsignacion $asignacion
     * @return string|null Ruta del documento extraído o null si falla
     */
    public function extraerDocumentoSinTraducir(PresupAdjAsignacion $asignacion): ?string
    {
        try {
            $adjunto = $asignacion->adjunto;
            $presupuesto = $adjunto->presupuesto;

            if (!$presupuesto) {
                return null;
            }

            $idPresupuesto = $presupuesto->id_pres;
            $idDocumento = $adjunto->id_adjun;

            // Directorio para documento AI
            $directorioAi = "archivos/traduccion-ai/{$idPresupuesto}/{$idDocumento}";
            $rutaAi = public_path($directorioAi);

            // Verificar si ya existe documento extraído
            if (is_dir($rutaAi)) {
                $archivos = glob("{$rutaAi}/*");
                foreach ($archivos as $archivo) {
                    if (is_file($archivo) && preg_match('/documento_ai\.(docx|pdf|png|jpg|jpeg)$/i', $archivo)) {
                        return "/{$directorioAi}/" . basename($archivo);
                    }
                }
            }

            // Si no existe, crear directorio
            if (!is_dir($rutaAi)) {
                mkdir($rutaAi, 0755, true);
            }

            // 1. Obtener documento original
            $pdfOriginalPath = $this->pdfService->obtenerPdfOriginal($asignacion);
            if (!$pdfOriginalPath) {
                Log::warning("No se pudo obtener documento original", [
                    'id_asignacion' => $asignacion->id,
                ]);
                return null;
            }

            $rutaOriginal = public_path($pdfOriginalPath);

            // 2. Convertir a DOCX (sin traducción)
            $rutaDocxTemp = $rutaAi . '/documento_temp.docx';
            if (!$this->conversionService->convertToDocx($rutaOriginal, $rutaDocxTemp)) {
                Log::error("No se pudo convertir documento a DOCX", [
                    'id_asignacion' => $asignacion->id,
                    'original' => $rutaOriginal,
                ]);
                return null;
            }

            // 3. Guardar como documento_ai.docx (sin traducir)
            $rutaAiDocx = $rutaAi . '/documento_ai.docx';
            if (!rename($rutaDocxTemp, $rutaAiDocx)) {
                Log::error("No se pudo mover documento extraído", [
                    'from' => $rutaDocxTemp,
                    'to' => $rutaAiDocx,
                ]);
                @unlink($rutaDocxTemp);
                return null;
            }

            Log::info("Documento extraído exitosamente (sin traducir)", [
                'id_asignacion' => $asignacion->id,
                'ruta_ai' => $rutaAiDocx,
            ]);

            return "/{$directorioAi}/documento_ai.docx";
        } catch (\Exception $e) {
            Log::error("Error en extracción de documento", [
                'id_asignacion' => $asignacion->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Crea una copia del documento AI para la asignación del traductor
     *
     * @param PresupAdjAsignacion $asignacion
     * @param string $rutaDocumentoAi Ruta del documento AI
     * @return string|null Ruta del documento V1 o null si falla
     */
    public function crearCopiaParaAsignacion(PresupAdjAsignacion $asignacion, string $rutaDocumentoAi): ?string
    {
        try {
            $presupuesto = $asignacion->adjunto->presupuesto;
            if (!$presupuesto) {
                return null;
            }

            $idPresupuesto = $presupuesto->id_pres;
            $idAsignacion = $asignacion->id;

            // Directorio para asignación
            $directorioAsignacion = "archivos/traducciones/{$idPresupuesto}/{$idAsignacion}";
            $rutaAsignacion = public_path($directorioAsignacion);

            // Crear directorio si no existe
            if (!is_dir($rutaAsignacion)) {
                mkdir($rutaAsignacion, 0755, true);
            }

            // Verificar si ya existe documento_V1
            $rutaV1 = $rutaAsignacion . '/documento_V1.docx';
            if (file_exists($rutaV1)) {
                return "/{$directorioAsignacion}/documento_V1.docx";
            }

            // Copiar documento AI como V1
            $rutaAiLocal = public_path($rutaDocumentoAi);
            if (!file_exists($rutaAiLocal)) {
                Log::warning("Documento AI no encontrado: {$rutaAiLocal}");
                return null;
            }

            if (!copy($rutaAiLocal, $rutaV1)) {
                Log::error("No se pudo copiar documento AI para asignación", [
                    'from' => $rutaAiLocal,
                    'to' => $rutaV1,
                ]);
                return null;
            }

            Log::info("Copia V1 creada para asignación", [
                'id_asignacion' => $asignacion->id,
                'ruta_v1' => $rutaV1,
            ]);

            return "/{$directorioAsignacion}/documento_V1.docx";
        } catch (\Exception $e) {
            Log::error("Error creando copia para asignación", [
                'id_asignacion' => $asignacion->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
