<?php

namespace App\Http\Controllers;

use App\Models\PluginVersion;
use App\Services\PluginVersionService;
use Filament\Notifications\Notification;

class PluginVersionController extends Controller
{
    /**
     * Ver historial de versiones de un plugin
     */
    public function history(string $plugin_slug)
    {
        $versions = PluginVersion::where('plugin_slug', $plugin_slug)
            ->orderBy('created_at', 'desc')
            ->get();

        $plugin_name = $versions->first()?->plugin_name ?? $plugin_slug;

        return view('filament.plugin-version-controller.history', compact('versions', 'plugin_name', 'plugin_slug'));
    }

    /**
     * Descargar un backup de versión
     */
    public function download(int $versionId)
    {
        $version = PluginVersion::findOrFail($versionId);

        if (!$version->file_path || !file_exists($version->file_path)) {
            Notification::make()
                ->title('Error')
                ->body('El archivo de backup no existe')
                ->danger()
                ->send();

            return redirect()->back();
        }

        return response()->download($version->file_path);
    }

    /**
     * Restaurar una versión anterior
     */
    public function restore(int $versionId)
    {
        $version = PluginVersion::findOrFail($versionId);

        if (!$version->file_path || !file_exists($version->file_path)) {
            Notification::make()
                ->title('Error')
                ->body('El archivo de backup no existe')
                ->danger()
                ->send();

            return redirect()->back();
        }

        $service = app(PluginVersionService::class);
        if ($service->restoreVersion($version->id)) {
            Notification::make()
                ->title('Éxito')
                ->body("Plugin {$version->plugin_name} restaurado a versión {$version->version}")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Error')
                ->body('No se pudo restaurar el plugin')
                ->danger()
                ->send();
        }

        return redirect()->back();
    }
}
