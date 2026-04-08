<?php

namespace App\Http\Controllers;

use App\Models\PresupAdjAsignacion;
use App\Services\OnlyOfficeService;
use App\Services\DocumentVersioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OnlyOfficeCallbackController extends Controller
{
    /**
     * Maneja callbacks de OnlyOffice
     * POST /api/onlyoffice/callback
     *
     * Estados posibles en el callback:
     * 0 = No hay cambios
     * 1 = Documento está siendo editado
     * 2 = Documento listo para guardar
     * 3 = Error al editar documento
     * 4 = Documento cerrado sin guardar
     * 6 = Documento está siendo editado en versión colaborativa
     * 7 = Error de fusión de documentos
     */
    public function callback(Request $request): JsonResponse
    {
        try {
            // Obtener token del callback
            $token = $request->header('Authorization');
            if (!$token) {
                $token = $request->input('token');
            }

            // Validar token JWT (opcional si OnlyOffice tiene JWT habilitado)
            $onlyofficeService = app(OnlyOfficeService::class);
            if ($token && config('services.onlyoffice.jwt_secret')) {
                if (!$onlyofficeService->validateCallback($token)) {
                    Log::warning("Callback de OnlyOffice con token inválido");
                    return response()->json(['error' => 'Invalid token'], 401);
                }
            }

            // Obtener datos del callback
            $data = $request->json()->all();

            Log::info("Callback de OnlyOffice recibido", [
                'status' => $data['status'] ?? null,
                'key' => $data['key'] ?? null,
                'users' => $data['users'] ?? null,
            ]);

            // Procesar según el status
            $status = $data['status'] ?? 0;

            if ($status === 2) {
                // Documento listo para guardar
                return $this->handleDocumentReady($data);
            } elseif ($status === 3) {
                // Error al editar
                Log::error("Error al editar documento en OnlyOffice", $data);
                return response()->json(['error' => 'Document edit error'], 400);
            } elseif ($status === 4) {
                // Documento cerrado sin guardar
                Log::info("Documento cerrado sin guardar", ['key' => $data['key'] ?? null]);
                return response()->json(['error' => 0]);
            }

            // Para otros estados, retornar OK
            return response()->json(['error' => 0]);
        } catch (\Exception $e) {
            Log::error("Error en callback de OnlyOffice", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Maneja cuando el documento está listo para guardar
     */
    private function handleDocumentReady(array $data): JsonResponse
    {
        try {
            // El documento tiene cambios
            $downloadUrl = $data['url'] ?? null;
            if (!$downloadUrl) {
                Log::warning("Callback de OnlyOffice sin URL de descarga");
                return response()->json(['error' => 'No download URL provided'], 400);
            }

            // Obtener el documento descargado
            $documentContent = file_get_contents($downloadUrl);
            if ($documentContent === false) {
                Log::error("No se pudo descargar documento desde OnlyOffice");
                return response()->json(['error' => 'Failed to download document'], 500);
            }

            // Extraer información del documento desde el callback
            $key = $data['key'] ?? null;
            $users = $data['users'] ?? [];

            Log::info("Documento descargado de OnlyOffice y listo para guardar", [
                'key' => $key,
                'size' => strlen($documentContent),
                'users' => $users,
            ]);

            // Aquí iría la lógica para obtener el ID de asignación desde el key
            // Por ahora, solo guardar los cambios
            // TODO: Implementar mapeo de key a id_asignacion

            // Crear log de cambios
            $changeLog = [
                'usuarios_editaron' => $users,
                'timestamp_callback' => now()->toIso8601String(),
            ];

            // TODO: Guardar nueva versión usando DocumentVersioningService
            // $versioningService = app(DocumentVersioningService::class);
            // $versioningService->saveNewVersion($asignacion, $documentContent, $changeLog);

            return response()->json(['error' => 0]);
        } catch (\Exception $e) {
            Log::error("Error manejando documento listo en OnlyOffice", [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
