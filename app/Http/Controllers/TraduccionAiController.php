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

            // Obtener idioma destino
            $targetLanguage = request('targetLanguage', 'es');

            // Traducir documento AI ya extraído con Azure Translator
            $rutaDocumentoTraducido = $this->traduccionAiService->traducirDocumentoExtraido(
                $asignacion,
                $targetLanguage
            );

            if (!$rutaDocumentoTraducido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al traducir documento con Azure Translator. Verifica que la suscripción tenga cuota disponible o intenta más tarde.',
                ], 500);
            }

            // Renombrar documento_traducido.docx a documento_traducido_{idioma}.docx
            $presupuesto = $asignacion->adjunto->presupuesto;
            $idDocumento = $asignacion->adjunto->id_adjun;
            $directorioAi = public_path("archivos/traduccion-ai/{$presupuesto->id_pres}/{$idDocumento}");

            $rutaTraducidaActual = $directorioAi . '/documento_traducido.docx';
            $rutaTraducidaNueva = $directorioAi . "/documento_traducido_{$targetLanguage}.docx";

            if (file_exists($rutaTraducidaActual) && !file_exists($rutaTraducidaNueva)) {
                rename($rutaTraducidaActual, $rutaTraducidaNueva);
            }

            // Crear copia V1 para la asignación con nombre idioma
            $rutaV1 = $this->traduccionAiService->crearCopiaParaAsignacion(
                $asignacion,
                "/archivos/traduccion-ai/{$presupuesto->id_pres}/{$idDocumento}/documento_traducido_{$targetLanguage}.docx",
                $targetLanguage
            );

            if (!$rutaV1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear copia para asignación',
                ], 500);
            }

            Log::info("Traducción completada", [
                'id_asignacion' => $id_asignacion,
                'documento_v1' => $rutaV1,
                'idioma_destino' => $targetLanguage,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Documento traducido exitosamente',
                'documentoV1' => $rutaV1,
            ]);
        } catch (\Exception $e) {
            Log::error("Error en traducción", [
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
     * Guarda los idiomas seleccionados en la asignación
     * POST /admin/traduccion/guardar-idiomas/{id_asignacion}
     */
    public function guardarIdiomas(int $id_asignacion): JsonResponse
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

            // Obtener idiomas del request
            $idIdiomOriginal = request('id_idiom_original');
            $idIdiom = request('id_idiom');

            if (!$idIdiomOriginal || !$idIdiom) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ambos idiomas son requeridos',
                ], 422);
            }

            // Actualizar asignación
            $asignacion->update([
                'id_idiom_original' => $idIdiomOriginal,
                'id_idiom' => $idIdiom,
            ]);

            Log::info("Idiomas actualizados en asignación", [
                'id_asignacion' => $id_asignacion,
                'id_idiom_original' => $idIdiomOriginal,
                'id_idiom' => $idIdiom,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Idiomas guardados exitosamente',
            ]);
        } catch (\Exception $e) {
            Log::error("Error guardando idiomas", [
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
     * Elimina el archivo traducido (V2)
     * POST /admin/traduccion/eliminar-traduccion/{id_asignacion}
     */
    public function eliminarTraduccion(int $id_asignacion): JsonResponse
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
            $presupuesto = $asignacion->adjunto->presupuesto;

            if (!$presupuesto) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener el presupuesto',
                ], 500);
            }

            // Eliminar V2
            $rutaV2 = public_path("archivos/traducciones/{$presupuesto->id_pres}/{$id_asignacion}/documento_V2.docx");
            if (file_exists($rutaV2)) {
                @unlink($rutaV2);
                Log::info("Archivo V2 eliminado", [
                    'id_asignacion' => $id_asignacion,
                    'ruta' => $rutaV2,
                ]);
            }

            // Opcionalmente, también eliminar el documento_ai traducido
            $directorioAi = public_path("archivos/traduccion-ai/{$presupuesto->id_pres}/{$asignacion->id_adjun}");
            if (is_dir($directorioAi)) {
                $archivos = glob("{$directorioAi}/*traducido*");
                foreach ($archivos as $archivo) {
                    if (is_file($archivo)) {
                        @unlink($archivo);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Traducción eliminada exitosamente',
            ]);
        } catch (\Exception $e) {
            Log::error("Error eliminando traducción", [
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
