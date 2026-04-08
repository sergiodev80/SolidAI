<?php

namespace App\Http\Controllers;

use App\Models\PresupAdjAsignacion;
use App\Services\PermissionService;
use App\Services\AzureTranslationService;
use App\Services\DocumentVersionService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class TraduccionController extends Controller
{
    private PermissionService $permissionService;
    private AzureTranslationService $azureService;
    private DocumentVersionService $versionService;

    public function __construct(
        PermissionService $permissionService,
        AzureTranslationService $azureService,
        DocumentVersionService $versionService
    ) {
        $this->permissionService = $permissionService;
        $this->azureService = $azureService;
        $this->versionService = $versionService;
    }

    /**
     * Mostrar página de traducción
     */
    public function show(int $id_asignacion): View
    {
        // Validar acceso
        if (!$this->permissionService->canAccessAsignacion($id_asignacion)) {
            abort(403, 'No tienes permiso para acceder a esta asignación');
        }

        // Obtener asignación
        $asignacion = PresupAdjAsignacion::findOrFail($id_asignacion);

        // Obtener documento adjunto
        $documento = $asignacion->adjunto;
        if (!$documento) {
            abort(404, 'Documento no encontrado');
        }

        // Crear carpeta si no existe
        $this->versionService->createAsignacionFolder($id_asignacion);

        // Obtener última versión disponible
        $latestVersion = $this->versionService->getLatestVersion($id_asignacion);

        // Si no existe V1, procesar con Azure
        if (!$latestVersion) {
            try {
                // Obtener idiomas del ERP
                $sourceLanguage = $this->getLanguageCode($asignacion->id_idiom_original);
                $targetLanguage = $this->getLanguageCode($asignacion->id_idiom);

                // Procesar documento
                $wordPath = $this->azureService->processDocumentFullFlow(
                    $documento->nombre_archivo,
                    $sourceLanguage,
                    $targetLanguage,
                    'documento_V1.docx'
                );

                // Guardar como V1
                $this->versionService->saveNewVersion($id_asignacion, $wordPath);

                // Guardar metadata
                $this->versionService->saveMetadata($id_asignacion, [
                    'id_asignacion' => $id_asignacion,
                    'id_adjun' => $documento->id_adjun,
                    'nombre_archivo_original' => $documento->nombre_archivo,
                    'traductor' => $asignacion->login,
                    'id_idiom_original' => $asignacion->id_idiom_original,
                    'id_idiom_destino' => $asignacion->id_idiom,
                    'pag_inicio' => $asignacion->pag_inicio,
                    'pag_fin' => $asignacion->pag_fin,
                    'fecha_inicio' => now()->toIso8601String(),
                    'version_actual' => 'V1',
                ]);

                $latestVersion = 1;
            } catch (\Exception $e) {
                abort(500, 'Error procesando documento: ' . $e->getMessage());
            }
        }

        // Actualizar estado a "En Traducción"
        if ($asignacion->estado === 'Asignado') {
            $asignacion->update(['estado' => 'En Traducción']);
        }

        return view('traduccion.page', [
            'asignacion' => $asignacion,
            'documento' => $documento,
            'idAsignacion' => $id_asignacion,
            'latestVersion' => $latestVersion,
            'onlyofficeUrl' => config('traduccion.onlyoffice.url'),
        ]);
    }

    /**
     * Obtiene código de idioma para Azure Translator
     */
    private function getLanguageCode(int $idIdiom): string
    {
        $languageMap = [
            1 => 'en',  // Inglés
            2 => 'es',  // Español
            3 => 'fr',  // Francés
            4 => 'de',  // Alemán
            5 => 'pt',  // Portugués
            // Agregar más idiomas según necesidad
        ];

        return $languageMap[$idIdiom] ?? 'en';
    }

    /**
     * Guardar documento editado
     */
    public function guardar(Request $request, int $id_asignacion)
    {
        if (!$this->permissionService->canAccessAsignacion($id_asignacion)) {
            abort(403);
        }

        $asignacion = PresupAdjAsignacion::findOrFail($id_asignacion);

        try {
            // Obtener documento editado desde OnlyOffice (vía webhook o POST)
            // Por ahora, asumimos que OnlyOffice lo maneja automáticamente
            // En versión completa, implementar callback de OnlyOffice

            return response()->json(['success' => true, 'message' => 'Documento guardado']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Comparar versiones y detectar cambios
     */
    public function comparar(Request $request, int $id_asignacion)
    {
        if (!$this->permissionService->canAccessAsignacion($id_asignacion)) {
            abort(403);
        }

        try {
            $fromVersion = $request->input('from_version', 1);
            $toVersion = $request->input('to_version');

            if (!$toVersion) {
                $toVersion = $this->versionService->getLatestVersion($id_asignacion);
            }

            if (!$fromVersion || !$toVersion || $fromVersion >= $toVersion) {
                return response()->json(['error' => 'Versiones inválidas'], 400);
            }

            // Comparar versiones
            $changes = $this->versionService->compareVersions($id_asignacion, $fromVersion, $toVersion);

            // Guardar cambios
            $usuario = PermissionService::getUserLogin();
            $this->versionService->saveChanges($id_asignacion, $fromVersion, $toVersion, $changes, $usuario);

            return response()->json([
                'success' => true,
                'changes' => $changes,
                'count' => count($changes),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Enviar para revisión
     */
    public function enviarRevision(Request $request, int $id_asignacion): RedirectResponse
    {
        if (!$this->permissionService->canAccessAsignacion($id_asignacion)) {
            abort(403);
        }

        $asignacion = PresupAdjAsignacion::findOrFail($id_asignacion);

        try {
            // Cambiar estado a "En Revisión"
            $asignacion->update(['estado' => 'En Revisión']);

            return redirect()
                ->back()
                ->with('success', 'Documento enviado para revisión');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error enviando para revisión: ' . $e->getMessage());
        }
    }
}
