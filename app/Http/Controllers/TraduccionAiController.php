<?php

namespace App\Http\Controllers;

use App\Models\PresupAdjAsignacion;
use App\Services\PermissionService;
use App\Services\TraduccionAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TraduccionAiController extends Controller
{
    public function __construct(
        private TraduccionAiService $traduccionAiService,
        private PermissionService $permissionService
    ) {}

    /**
     * Extrae documento sin traducir
     * POST /admin/traduccion/extraer-documento/{id_asignacion}
     */
    public function extraerDocumento(int $id_asignacion): JsonResponse
    {
        try {
            // Validar acceso
            if (!$this->permissionService->canAccessAsignacion($id_asignacion)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para acceder a esta asignación',
                ], 403);
            }

            // Obtener asignación
            $asignacion = PresupAdjAsignacion::findOrFail($id_asignacion);

            // Extraer documento sin traducir (usar 'es' como idioma dummy)
            $rutaDocumentoAi = $this->traduccionAiService->extraerDocumentoSinTraducir(
                $asignacion
            );

            if (!$rutaDocumentoAi) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al extraer documento',
                ], 500);
            }

            // Crear copia para la asignación
            $rutaV1 = $this->traduccionAiService->crearCopiaParaAsignacion(
                $asignacion,
                $rutaDocumentoAi
            );

            if (!$rutaV1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear copia para asignación',
                ], 500);
            }

            Log::info("Documento extraído exitosamente", [
                'id_asignacion' => $id_asignacion,
                'documento_ai' => $rutaDocumentoAi,
                'documento_v1' => $rutaV1,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Documento extraído exitosamente',
                'documentoV1' => $rutaV1,
            ]);
        } catch (\Exception $e) {
            Log::error("Error al extraer documento", [
                'id_asignacion' => $id_asignacion,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Inicia la traducción AI del documento
     * POST /admin/traduccion/traducir-ai/{id_asignacion}
     */
    public function traducir(int $id_asignacion): JsonResponse
    {
        try {
            // Validar acceso
            if (!$this->permissionService->canAccessAsignacion($id_asignacion)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para acceder a esta asignación',
                ], 403);
            }

            // Obtener asignación
            $asignacion = PresupAdjAsignacion::findOrFail($id_asignacion);

            // Obtener idioma destino (debe venir en request)
            $targetLanguage = request('targetLanguage', 'es');

            // Traducir documento AI ya extraído con Azure Translator
            $rutaDocumentoTraducido = $this->traduccionAiService->traducirDocumentoExtraido(
                $asignacion,
                $targetLanguage
            );

            // Si la traducción falla, usar V1 como fallback
            if (!$rutaDocumentoTraducido) {
                Log::warning("Traducción con Azure falló, usando V1 como fallback", [
                    'id_asignacion' => $id_asignacion,
                ]);

                // Obtener ruta de V1 como fallback
                $presupuesto = $asignacion->adjunto->presupuesto;
                if ($presupuesto) {
                    $rutaV1Fallback = "archivos/traducciones/{$presupuesto->id_pres}/{$id_asignacion}/documento_V1.docx";
                    $rutaV1FullPath = public_path($rutaV1Fallback);

                    if (file_exists($rutaV1FullPath)) {
                        $rutaDocumentoTraducido = "/{$rutaV1Fallback}";
                    }
                }

                // Si aún no hay documento, retornar error
                if (!$rutaDocumentoTraducido) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error al traducir documento con Azure. No hay documento V1 para usar como fallback.',
                    ], 500);
                }
            }

            // Crear versión V2 (traducida o fallback) para la asignación
            $rutaV2 = $this->traduccionAiService->crearVersionV2(
                $asignacion,
                $rutaDocumentoTraducido
            );

            if (!$rutaV2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear versión V2',
                ], 500);
            }

            Log::info("V2 completada", [
                'id_asignacion' => $id_asignacion,
                'documento_origen' => $rutaDocumentoTraducido,
                'version_v2' => $rutaV2,
                'idioma_destino' => $targetLanguage,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Versión V2 creada exitosamente',
                'documentoV2' => $rutaV2,
            ]);
        } catch (\Exception $e) {
            Log::error("Error en traducción con Azure", [
                'id_asignacion' => $id_asignacion,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
            ], 500);
        }
    }
}
