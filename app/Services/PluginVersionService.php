<?php

namespace App\Services;

use App\Models\PluginVersion;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class PluginVersionService
{
    /**
     * Registro de una nueva versión de plugin
     */
    public function registerVersion(
        string $pluginSlug,
        string $pluginName,
        string $version,
        string $versionType = 'patch',
        string $description = '',
        string $changelog = '',
        ?string $githubRepository = null,
        ?string $createdBy = null,
    ): ?PluginVersion {
        try {
            // Obtener versión anterior activa
            $previousActive = PluginVersion::where('plugin_slug', $pluginSlug)
                ->where('is_active', true)
                ->latest('created_at')
                ->first();

            $previousVersion = $previousActive?->version;

            // Desactivar versión anterior ANTES de crear la nueva (para evitar violación del índice único)
            if ($previousActive) {
                $previousActive->update(['is_active' => false]);
            }

            // Crear backup del plugin actual
            $backupPath = $this->backupPlugin($pluginSlug, $version);

            // Crear registro de versión
            $pluginVersion = PluginVersion::create([
                'plugin_slug' => $pluginSlug,
                'plugin_name' => $pluginName,
                'version' => $version,
                'version_type' => $versionType,
                'description' => $description,
                'changelog' => $changelog,
                'github_repository' => $githubRepository,
                'file_path' => $backupPath,
                'file_size' => $backupPath ? filesize($backupPath) : null,
                'file_hash' => $backupPath ? hash_file('sha256', $backupPath) : null,
                'previous_version' => $previousVersion,
                'created_by' => $createdBy ?? auth()->user()?->name ?? 'System',
                'is_active' => true,
                'is_stable' => $versionType !== 'patch', // Solo versiones minor/major son estables
                'released_at' => now(),
            ]);

            Log::info("Plugin version registered", [
                'plugin_slug' => $pluginSlug,
                'version' => $version,
                'version_type' => $versionType,
            ]);

            return $pluginVersion;
        } catch (\Exception $e) {
            Log::error("Error registering plugin version", [
                'plugin_slug' => $pluginSlug,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Obtiene el nombre correcto del directorio del plugin
     * Busca en app/Filament/Plugins el directorio que coincida (case-insensitive)
     */
    private function getPluginDirectoryName(string $pluginSlug): string
    {
        $pluginsPath = base_path("app/Filament/Plugins");

        if (!is_dir($pluginsPath)) {
            return ucfirst($pluginSlug);
        }

        $directories = array_map('basename', glob("{$pluginsPath}/*", GLOB_ONLYDIR));

        foreach ($directories as $dir) {
            if (strtolower($dir) === strtolower($pluginSlug)) {
                return $dir; // Retorna el nombre exacto del directorio
            }
        }

        return ucfirst($pluginSlug); // Fallback si no encuentra
    }

    /**
     * Crea un backup del plugin
     */
    private function backupPlugin(string $pluginSlug, string $version): ?string
    {
        try {
            // Obtener el nombre correcto del directorio del plugin
            $pluginDir = $this->getPluginDirectoryName($pluginSlug);
            $sourceDir = base_path("app/Filament/Plugins/{$pluginDir}");

            if (!is_dir($sourceDir)) {
                Log::warning("Plugin directory not found", ['plugin' => $pluginSlug, 'path' => $sourceDir]);
                return null;
            }

            // Crear directorio de backups
            $backupDir = storage_path("plugin-backups/{$pluginSlug}");
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Crear archivo ZIP
            $backupFileName = "{$pluginSlug}-v{$version}-" . now()->format('YmdHis') . '.zip';
            $backupPath = "{$backupDir}/{$backupFileName}";

            $this->zipDirectory($sourceDir, $backupPath);

            return $backupPath;
        } catch (\Exception $e) {
            Log::error("Error backing up plugin", [
                'plugin_slug' => $pluginSlug,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Crea un ZIP de un directorio con estructura correcta
     */
    private function zipDirectory(string $source, string $destination): bool
    {
        $zip = new \ZipArchive();

        if (!$zip->open($destination, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
            return false;
        }

        // Obtener el nombre del directorio del plugin (ej: 'Presupuestos')
        $pluginDirName = basename($source);

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($source) + 1);
                // Agregar el nombre del directorio del plugin al inicio del path
                $archivePath = $pluginDirName . '/' . $relativePath;
                $zip->addFile($filePath, $archivePath);
            }
        }

        return $zip->close();
    }

    /**
     * Restaura una versión anterior del plugin
     */
    public function restoreVersion(int $versionId): bool
    {
        try {
            $version = PluginVersion::findOrFail($versionId);

            if (!$version->file_path || !file_exists($version->file_path)) {
                Log::error("Backup file not found", ['version_id' => $versionId]);
                return false;
            }

            // Extraer backup
            $pluginDirName = $this->getPluginDirectoryName($version->plugin_slug);
            $pluginDir = base_path("app/Filament/Plugins/{$pluginDirName}");

            // Eliminar directorio actual
            if (is_dir($pluginDir)) {
                File::deleteDirectory($pluginDir);
            }

            // Extraer archivo
            $zip = new \ZipArchive();
            if ($zip->open($version->file_path) === true) {
                $zip->extractTo(base_path("app/Filament/Plugins"));
                $zip->close();
            } else {
                Log::error("Failed to extract backup", ['file' => $version->file_path]);
                return false;
            }

            // Actualizar versión activa - Desactivar todas EXCEPTO la que se restaura
            PluginVersion::where('plugin_slug', $version->plugin_slug)
                ->where('id', '!=', $version->id)
                ->update(['is_active' => false]);

            $version->update(['is_active' => true]);

            Log::info("Plugin version restored", [
                'plugin_slug' => $version->plugin_slug,
                'version' => $version->version,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Error restoring plugin version", [
                'version_id' => $versionId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Obtiene todas las versiones de un plugin
     */
    public function getVersionHistory(string $pluginSlug)
    {
        return PluginVersion::forPlugin($pluginSlug)->get();
    }

    /**
     * Descarga un backup de versión
     */
    public function downloadVersion(int $versionId)
    {
        $version = PluginVersion::findOrFail($versionId);

        if (!$version->file_path || !file_exists($version->file_path)) {
            throw new \Exception("Backup file not found");
        }

        return response()->download($version->file_path);
    }

    /**
     * Crea un backup del plugin actual para versioning.
     * Se llama automáticamente cuando se edita metadata en Filament.
     */
    public function createPluginBackup(string $pluginSlug, string $version): ?string
    {
        try {
            $pluginDir = $this->getPluginDirectoryName($pluginSlug);
            $sourceDir = base_path("app/Filament/Plugins/{$pluginDir}");

            if (!is_dir($sourceDir)) {
                Log::warning("Plugin directory not found for backup", [
                    'plugin' => $pluginSlug,
                    'source_dir' => $sourceDir,
                ]);
                return null;
            }

            // Crear directorio de backups si no existe
            $backupDir = storage_path("plugin-backups/{$pluginSlug}");
            if (!is_dir($backupDir)) {
                @mkdir($backupDir, 0755, true);
            }

            // Crear archivo ZIP con timestamp único
            $timestamp = now()->format('YmdHis');
            $backupFileName = "{$pluginSlug}-v{$version}-{$timestamp}.zip";
            $backupPath = "{$backupDir}/{$backupFileName}";

            // Crear el ZIP
            if ($this->zipDirectory($sourceDir, $backupPath)) {
                Log::info("Plugin backup created successfully", [
                    'plugin_slug' => $pluginSlug,
                    'version' => $version,
                    'backup_path' => $backupPath,
                    'file_size' => filesize($backupPath),
                ]);

                return $backupPath;
            } else {
                Log::error("Failed to create backup ZIP", [
                    'plugin_slug' => $pluginSlug,
                    'backup_path' => $backupPath,
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error("Error creating plugin backup", [
                'plugin_slug' => $pluginSlug,
                'version' => $version,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
