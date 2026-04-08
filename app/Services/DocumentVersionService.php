<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use Exception;

class DocumentVersionService
{
    private string $basePath;
    private int $maxVersions;

    public function __construct()
    {
        $this->basePath = config('traduccion.storage.path');
        $this->maxVersions = config('traduccion.storage.max_versions', 5);
    }

    /**
     * Obtiene la ruta base para una asignación
     */
    public function getAsignacionPath(int $idAsignacion): string
    {
        return public_path($this->basePath . '/' . $idAsignacion);
    }

    /**
     * Crea la carpeta de una asignación
     */
    public function createAsignacionFolder(int $idAsignacion): void
    {
        $path = $this->getAsignacionPath($idAsignacion);
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Obtiene la versión más reciente de un documento
     */
    public function getLatestVersion(int $idAsignacion): ?int
    {
        $path = $this->getAsignacionPath($idAsignacion);
        if (!is_dir($path)) {
            return null;
        }

        $files = glob($path . '/documento_V*.docx');
        if (empty($files)) {
            return null;
        }

        // Obtener el número máximo de versión
        $versions = [];
        foreach ($files as $file) {
            if (preg_match('/documento_V(\d+)\.docx/', basename($file), $matches)) {
                $versions[] = (int)$matches[1];
            }
        }

        return !empty($versions) ? max($versions) : null;
    }

    /**
     * Obtiene la ruta de un archivo de versión específica
     */
    public function getVersionPath(int $idAsignacion, int $version): string
    {
        return $this->getAsignacionPath($idAsignacion) . '/documento_V' . $version . '.docx';
    }

    /**
     * Guarda una nueva versión del documento
     * @param int $idAsignacion ID de la asignación
     * @param string $sourcePath Ruta del archivo a guardar
     * @return int Número de versión creada
     */
    public function saveNewVersion(int $idAsignacion, string $sourcePath): int
    {
        $this->createAsignacionFolder($idAsignacion);

        $latestVersion = $this->getLatestVersion($idAsignacion) ?? 0;
        $newVersion = $latestVersion + 1;

        $destinationPath = $this->getVersionPath($idAsignacion, $newVersion);
        copy($sourcePath, $destinationPath);

        // Limpiar versiones antiguas si excede máximo
        $this->cleanOldVersions($idAsignacion);

        return $newVersion;
    }

    /**
     * Obtiene el contenido de texto de un documento Word
     */
    public function getDocumentContent(string $docxPath): string
    {
        try {
            if (!file_exists($docxPath)) {
                throw new Exception('Archivo no encontrado: ' . $docxPath);
            }

            $phpWord = IOFactory::load($docxPath);
            $fullText = '';

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $fullText .= $element->getText() . "\n";
                    }
                }
            }

            return $fullText;
        } catch (Exception $e) {
            throw new Exception('Error leyendo documento Word: ' . $e->getMessage());
        }
    }

    /**
     * Compara dos versiones y detecta cambios
     * Devuelve array de cambios detectados
     */
    public function compareVersions(int $idAsignacion, int $fromVersion, int $toVersion): array
    {
        $fromPath = $this->getVersionPath($idAsignacion, $fromVersion);
        $toPath = $this->getVersionPath($idAsignacion, $toVersion);

        if (!file_exists($fromPath) || !file_exists($toPath)) {
            throw new Exception('Una o ambas versiones no existen');
        }

        $fromContent = $this->getDocumentContent($fromPath);
        $toContent = $this->getDocumentContent($toPath);

        // Detectar cambios a nivel palabra, línea y párrafo
        return $this->detectChanges($fromContent, $toContent, $fromVersion, $toVersion);
    }

    /**
     * Detecta cambios entre dos textos
     */
    private function detectChanges(string $fromText, string $toText, int $fromVersion, int $toVersion): array
    {
        $changes = [];

        // Split por párrafos
        $fromParagraphs = array_filter(explode("\n", $fromText));
        $toParagraphs = array_filter(explode("\n", $toText));

        $maxParagraphs = max(count($fromParagraphs), count($toParagraphs));

        for ($i = 0; $i < $maxParagraphs; $i++) {
            $fromPara = trim($fromParagraphs[$i] ?? '');
            $toPara = trim($toParagraphs[$i] ?? '');

            if ($fromPara !== $toPara) {
                // Párrafo cambió - detectar cambios a nivel palabra y línea
                $changeId = uniqid('cambio_');
                $changes[] = [
                    'id' => $changeId,
                    'tipo' => 'parrafo',
                    'original' => $fromPara,
                    'nueva' => $toPara,
                    'posicion' => [
                        'pagina' => ceil(($i + 1) / 50), // Estimación
                        'parrafo' => $i + 1,
                    ],
                    'justificacion' => '',
                    'estado' => 'pendiente',
                ];

                // Detectar cambios a nivel palabra dentro del párrafo
                $this->detectWordLevelChanges($fromPara, $toPara, $i, $changes);
            }
        }

        return $changes;
    }

    /**
     * Detecta cambios a nivel palabra
     */
    private function detectWordLevelChanges(string $fromPara, string $toPara, int $paragraphIndex, array &$changes): void
    {
        $fromWords = str_word_count($fromPara, 1);
        $toWords = str_word_count($toPara, 1);

        // Simple word-by-word comparison
        $maxWords = max(count($fromWords), count($toWords));
        for ($i = 0; $i < $maxWords; $i++) {
            $fromWord = $fromWords[$i] ?? '';
            $toWord = $toWords[$i] ?? '';

            if ($fromWord !== $toWord && !empty($fromWord) && !empty($toWord)) {
                $changeId = uniqid('cambio_');
                $changes[] = [
                    'id' => $changeId,
                    'tipo' => 'palabra',
                    'original' => $fromWord,
                    'nueva' => $toWord,
                    'posicion' => [
                        'pagina' => ceil(($paragraphIndex + 1) / 50),
                        'parrafo' => $paragraphIndex + 1,
                        'palabra' => $i + 1,
                    ],
                    'justificacion' => '',
                    'estado' => 'pendiente',
                ];
            }
        }
    }

    /**
     * Guarda cambios en archivo JSON
     */
    public function saveChanges(int $idAsignacion, int $fromVersion, int $toVersion, array $changes, string $usuario = ''): void
    {
        $changesPath = $this->getAsignacionPath($idAsignacion) . '/cambios_V' . $fromVersion . '_V' . $toVersion . '.json';

        $data = [
            'comparacion' => 'V' . $fromVersion . '_V' . $toVersion,
            'cambios' => $changes,
            'estadisticas' => $this->calculateStats($changes),
            'fecha_comparacion' => now()->toIso8601String(),
            'usuario' => $usuario,
        ];

        file_put_contents($changesPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Calcula estadísticas de cambios
     */
    private function calculateStats(array $changes): array
    {
        $stats = [
            'total_cambios' => count($changes),
            'palabras_cambiadas' => 0,
            'lineas_cambiadas' => 0,
            'parrafos_cambiados' => 0,
            'justificadas' => 0,
            'pendientes' => 0,
        ];

        foreach ($changes as $change) {
            if ($change['tipo'] === 'palabra') {
                $stats['palabras_cambiadas']++;
            } elseif ($change['tipo'] === 'linea') {
                $stats['lineas_cambiadas']++;
            } elseif ($change['tipo'] === 'parrafo') {
                $stats['parrafos_cambiados']++;
            }

            if ($change['estado'] === 'justificado') {
                $stats['justificadas']++;
            } else {
                $stats['pendientes']++;
            }
        }

        return $stats;
    }

    /**
     * Limpia versiones antiguas
     */
    private function cleanOldVersions(int $idAsignacion): void
    {
        $path = $this->getAsignacionPath($idAsignacion);
        $files = glob($path . '/documento_V*.docx');

        if (count($files) > $this->maxVersions) {
            // Obtener versiones ordenadas
            $versions = [];
            foreach ($files as $file) {
                if (preg_match('/documento_V(\d+)\.docx/', basename($file), $matches)) {
                    $versions[(int)$matches[1]] = $file;
                }
            }

            // Eliminar las versiones más antiguas
            ksort($versions);
            $versionsToDelete = array_slice($versions, 0, count($versions) - $this->maxVersions);
            foreach ($versionsToDelete as $file) {
                @unlink($file);
            }
        }
    }

    /**
     * Guarda metadata de la asignación
     */
    public function saveMetadata(int $idAsignacion, array $metadata): void
    {
        $metadataPath = $this->getAsignacionPath($idAsignacion) . '/metadata.json';
        file_put_contents($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Obtiene metadata de la asignación
     */
    public function getMetadata(int $idAsignacion): ?array
    {
        $metadataPath = $this->getAsignacionPath($idAsignacion) . '/metadata.json';
        if (!file_exists($metadataPath)) {
            return null;
        }
        return json_decode(file_get_contents($metadataPath), true);
    }
}
