<?php

namespace App\Observers;

use App\Models\PluginVersion;
use App\Services\PluginVersionService;
use Illuminate\Support\Facades\Log;

class PluginVersionObserver
{
    /**
     * Handle the PluginVersion "updated" event.
     */
    public function updated(PluginVersion $pluginVersion): void
    {
        // Si se actualiza la versión activa con cambios en metadata
        if ($pluginVersion->is_active && $pluginVersion->isDirty(['description', 'changelog', 'notes', 'is_stable'])) {
            try {
                $service = app(PluginVersionService::class);
                $backupPath = $service->createPluginBackup($pluginVersion->plugin_slug, $pluginVersion->version);

                if ($backupPath) {
                    Log::info("Backup created for version metadata update", [
                        'plugin_slug' => $pluginVersion->plugin_slug,
                        'version' => $pluginVersion->version,
                        'backup_path' => $backupPath,
                        'dirty_fields' => $pluginVersion->getDirty(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Error creating backup on version update", [
                    'plugin_slug' => $pluginVersion->plugin_slug,
                    'version' => $pluginVersion->version,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
