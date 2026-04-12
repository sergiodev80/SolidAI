<?php

namespace App\Filament\Resources\Sistema\PluginVersionResource\Pages;

use App\Filament\Resources\Sistema\PluginVersionResource;
use App\Models\PluginVersion;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListPluginVersions extends ListRecords
{
    protected static string $resource = PluginVersionResource::class;

    public ?string $plugin_filter = null;
    public bool $show_all = false;

    public function mount(): void
    {
        parent::mount();
        $this->plugin_filter = request()->query('plugin') ?? request()->query('plugin_slug');
        $this->show_all = (bool) request()->query('show_all');
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        // If plugin_filter is set, show all versions of that plugin
        if ($this->plugin_filter) {
            $query->where('plugin_slug', $this->plugin_filter);
            if (!$this->show_all) {
                $query->where('is_active', true);
            }
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        if ($this->plugin_filter) {
            $actions[] = Action::make('back')
                ->label('Volver')
                ->icon('heroicon-o-arrow-left')
                ->url(PluginVersionResource::getUrl('index'));
        }

        return $actions;
    }

    public function getTitle(): string
    {
        if ($this->plugin_filter) {
            $pluginName = PluginVersion::where('plugin_slug', $this->plugin_filter)
                ->first()?->plugin_name ?? ucfirst($this->plugin_filter);

            return "Historial de Versiones - {$pluginName}";
        }

        return 'Plugin Versions';
    }
}
