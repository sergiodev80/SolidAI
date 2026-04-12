<?php

namespace App\Console\Commands;

use App\Services\PluginVersionService;
use Illuminate\Console\Command;

class RegisterPluginVersion extends Command
{
    protected $signature = 'plugin:register-version
        {plugin : Plugin slug (ej: traduccion)}
        {version : Nueva versión (ej: 1.0.1)}
        {--type=patch : Tipo de cambio (major, minor, patch)}
        {--description= : Descripción de cambios}
        {--changelog= : Detalle completo de cambios}
        {--github= : URL del repositorio GitHub}
        {--by= : Usuario que hace el cambio}';

    protected $description = 'Registra una nueva versión de un plugin';

    public function handle(PluginVersionService $service): int
    {
        $plugin = $this->argument('plugin');
        $version = $this->argument('version');
        $type = $this->option('type');
        $description = $this->option('description') ?? '';
        $changelog = $this->option('changelog') ?? '';
        $github = $this->option('github');
        $createdBy = $this->option('by') ?? auth()->user()?->name ?? 'CLI';

        // Validar tipo
        if (!in_array($type, ['major', 'minor', 'patch'])) {
            $this->error('Tipo inválido. Usa: major, minor, patch');
            return 1;
        }

        $this->info("Registrando versión {$version} del plugin {$plugin}...");

        $result = $service->registerVersion(
            pluginSlug: $plugin,
            pluginName: ucfirst($plugin),
            version: $version,
            versionType: $type,
            description: $description,
            changelog: $changelog,
            githubRepository: $github,
            createdBy: $createdBy,
        );

        if ($result) {
            $this->info("✅ Versión registrada exitosamente");
            $this->table(
                ['Plugin', 'Versión', 'Tipo', 'Creado por'],
                [[
                    $result->plugin_name,
                    $result->version,
                    $result->version_type,
                    $result->created_by,
                ]]
            );
            return 0;
        } else {
            $this->error('❌ Error al registrar la versión');
            return 1;
        }
    }
}
