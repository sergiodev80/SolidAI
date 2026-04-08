<?php

namespace App\Services;

use App\Models\PresupAdjAsignacion;
use Illuminate\Support\Facades\Log;

class DocumentVersioningService
{
    /**
     * Guarda una nueva versión del documento
     * Cuando OnlyOffice notifica cambios, crea V2, V3, etc.
     */
    public function saveNewVersion(
        PresupAdjAsignacion $asignacion,
        string $documentContent,
        ?array $changeLog = null
    ): ?string {
        try {
            $presupuesto = $asignacion->adjunto->presupuesto;
            if (!$presupuesto) {
                return null;
            }

            $directorioAsignacion = "archivos/traducciones/{$presupuesto->id_pres}/{$asignacion->id}";
            $rutaAsignacion = public_path($directorioAsignacion);

            // Crear directorio si no existe
            if (!is_dir($rutaAsignacion)) {
                mkdir($rutaAsignacion, 0755, true);
            }

            // Encontrar el siguiente número de versión
            $nextVersion = $this->getNextVersionNumber($rutaAsignacion);
            $nombreArchivo = "documento_V{$nextVersion}.docx";
            $rutaCompleta = $rutaAsignacion . '/' . $nombreArchivo;

            // Guardar documento
            if (file_put_contents($rutaCompleta, $documentContent) === false) {
                Log::error("No se pudo guardar nueva versión", [
                    'id_asignacion' => $asignacion->id,
                    'version' => $nextVersion,
                ]);
                return null;
            }

            // Guardar log de cambios si se proporciona
            if ($changeLog) {
                $this->saveChangeLog($rutaAsignacion, $nextVersion, $changeLog);
            }

            Log::info("Nueva versión guardada", [
                'id_asignacion' => $asignacion->id,
                'version' => $nextVersion,
                'ruta' => $rutaCompleta,
            ]);

            return "/{$directorioAsignacion}/{$nombreArchivo}";
        } catch (\Exception $e) {
            Log::error("Error guardando nueva versión", [
                'id_asignacion' => $asignacion->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Obtiene el siguiente número de versión
     */
    private function getNextVersionNumber(string $directorio): int
    {
        $version = 1;

        if (!is_dir($directorio)) {
            return $version;
        }

        $archivos = glob("{$directorio}/documento_V*.docx");
        foreach ($archivos as $archivo) {
            if (preg_match('/documento_V(\d+)\.docx/', basename($archivo), $matches)) {
                $num = (int) $matches[1];
                if ($num >= $version) {
                    $version = $num + 1;
                }
            }
        }

        return $version;
    }

    /**
     * Guarda un log de cambios
     */
    private function saveChangeLog(string $directorio, int $version, array $changeLog): void
    {
        try {
            $logFile = $directorio . "/cambios_V{$version}.json";

            $data = [
                'version' => $version,
                'timestamp' => now()->toIso8601String(),
                'usuario' => auth()->user()?->name ?? 'Sistema',
                'cambios' => $changeLog,
            ];

            file_put_contents($logFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } catch (\Exception $e) {
            Log::warning("No se pudo guardar log de cambios", [
                'version' => $version,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Obtiene todas las versiones de un documento
     */
    public function getAllVersions(PresupAdjAsignacion $asignacion): array
    {
        try {
            $presupuesto = $asignacion->adjunto->presupuesto;
            if (!$presupuesto) {
                return [];
            }

            $directorio = public_path("archivos/traducciones/{$presupuesto->id_pres}/{$asignacion->id}");

            if (!is_dir($directorio)) {
                return [];
            }

            $versiones = [];
            $archivos = glob("{$directorio}/documento_V*.docx");

            foreach ($archivos as $archivo) {
                if (preg_match('/documento_V(\d+)\.docx/', basename($archivo), $matches)) {
                    $version = (int) $matches[1];
                    $logFile = $directorio . "/cambios_V{$version}.json";

                    $versiones[] = [
                        'version' => $version,
                        'archivo' => basename($archivo),
                        'ruta' => "/archivos/traducciones/{$presupuesto->id_pres}/{$asignacion->id}/" . basename($archivo),
                        'tamaño' => filesize($archivo),
                        'fecha' => filemtime($archivo),
                        'cambios' => file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : null,
                    ];
                }
            }

            // Ordenar por versión descendente
            usort($versiones, fn($a, $b) => $b['version'] <=> $a['version']);

            return $versiones;
        } catch (\Exception $e) {
            Log::error("Error obteniendo versiones", [
                'id_asignacion' => $asignacion->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Compara dos versiones y detecta cambios
     */
    public function compareVersions(
        PresupAdjAsignacion $asignacion,
        int $versionFrom,
        int $versionTo
    ): ?array {
        try {
            $presupuesto = $asignacion->adjunto->presupuesto;
            if (!$presupuesto) {
                return null;
            }

            $directorio = public_path("archivos/traducciones/{$presupuesto->id_pres}/{$asignacion->id}");

            $archivoFrom = "{$directorio}/documento_V{$versionFrom}.docx";
            $archivoTo = "{$directorio}/documento_V{$versionTo}.docx";

            if (!file_exists($archivoFrom) || !file_exists($archivoTo)) {
                Log::warning("Versiones no encontradas para comparación", [
                    'from' => $versionFrom,
                    'to' => $versionTo,
                ]);
                return null;
            }

            // Aquí iría la lógica de comparación de DOCX
            // Por ahora, retornar información básica
            return [
                'version_from' => $versionFrom,
                'version_to' => $versionTo,
                'tamaño_from' => filesize($archivoFrom),
                'tamaño_to' => filesize($archivoTo),
                'diferencia_bytes' => filesize($archivoTo) - filesize($archivoFrom),
            ];
        } catch (\Exception $e) {
            Log::error("Error comparando versiones", [
                'id_asignacion' => $asignacion->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
