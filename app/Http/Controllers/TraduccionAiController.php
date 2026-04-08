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

            // Traducir con IA
            $rutaDocumentoAi = $this->traduccionAiService->obtenerDocumentoAi(
                $asignacion,
                $targetLanguage
            );

            if (!$rutaDocumentoAi) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al traducir documento con IA',
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

            Log::info("Traducción AI completada", [
                'id_asignacion' => $id_asignacion,
                'documento_ai' => $rutaDocumentoAi,
                'documento_v1' => $rutaV1,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Documento traducido exitosamente',
                'documentoV1' => $rutaV1,
            ]);
        } catch (\Exception $e) {
            Log::error("Error en traducción AI", [
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
