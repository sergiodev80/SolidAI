<?php

namespace App\Filament\Resources\Sistema;

use App\Models\PluginVersion;
use App\Services\PluginVersionService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;

class PluginVersionResource extends Resource
{
    protected static ?string $model = PluginVersion::class;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-cog-6-tooth';
    }

    public static function getNavigationLabel(): string
    {
        return 'Versiones de Plugins';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Sistema';
    }

    public static function getNavigationSort(): ?int
    {
        return 100;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Editar Versión')
                ->schema([
                    Textarea::make('description')
                        ->label('Descripción')
                        ->rows(3),

                    Textarea::make('changelog')
                        ->label('Changelog')
                        ->rows(6)
                        ->helperText('Usa saltos de línea para separar puntos'),

                    Textarea::make('notes')
                        ->label('Notas Internas')
                        ->rows(3),

                    Toggle::make('is_stable')
                        ->label('Marcar como Estable'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $plugin_slug = request()->query('plugin_slug');
                $show_all = request()->query('show_all');

                if ($show_all && $plugin_slug) {
                    // Mostrar todas las versiones del plugin seleccionado
                    return $query->where('plugin_slug', $plugin_slug);
                } else {
                    // Mostrar solo versiones activas
                    return $query->where('is_active', true);
                }
            })
            ->striped()
            ->columns([
                TextColumn::make('plugin_name')
                    ->label('Plugin')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('version')
                    ->label('Versión')
                    ->sortable()
                    ->copyable(),

                BadgeColumn::make('version_type')
                    ->label('Tipo')
                    ->color(fn (string $state): string => match($state) {
                        'major' => 'danger',
                        'minor' => 'warning',
                        'patch' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'major' => '🔴 Mayor',
                        'minor' => '🟡 Menor',
                        'patch' => '🟢 Parche',
                        default => $state,
                    }),

                BadgeColumn::make('is_active')
                    ->label('Estado')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Activa' : 'Inactiva'),

                BadgeColumn::make('is_stable')
                    ->label('Estable')
                    ->color(fn (bool $state): string => $state ? 'success' : 'warning')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Sí' : 'No'),

                TextColumn::make('created_by')
                    ->label('Creado por')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('file_size')
                    ->label('Tamaño')
                    ->formatStateUsing(function (?int $state): string {
                        if (!$state) return 'N/A';
                        $mb = $state / 1024 / 1024;
                        if ($mb >= 1) {
                            return round($mb, 2) . ' MB';
                        }
                        return round($state / 1024, 2) . ' KB';
                    }),

                TextColumn::make('github_tag')
                    ->label('GitHub')
                    ->formatStateUsing(fn (?string $state): string => $state ? '✓ ' . $state : '✗ Sin publicar')
                    ->color(fn (?string $state): string => $state ? 'success' : 'danger'),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square')
                    ->modal()
                    ->modalHeading('Editar Versión'),

                Actions\Action::make('versions')
                    ->label('Versiones')
                    ->icon('heroicon-o-archive-box')
                    ->color('info')
                    ->url(fn (PluginVersion $record): string => PluginVersionResource::getUrl('index') . '?plugin_slug=' . $record->plugin_slug . '&show_all=1'),

                Actions\Action::make('restore')
                    ->label('Restaurar')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (PluginVersion $record): bool => !$record->is_active && $record->file_path && file_exists($record->file_path))
                    ->requiresConfirmation()
                    ->modalHeading(fn (PluginVersion $record): string => "Restaurar versión {$record->version}")
                    ->modalDescription(fn (PluginVersion $record): string => "¿Estás seguro? Esto desactivará la versión actual y activará la versión {$record->version}.")
                    ->modalButton('Restaurar')
                    ->url(fn (PluginVersion $record): string => route('filament.restore-plugin-version', $record->id))
                    ->openUrlInNewTab(false),

                Actions\Action::make('download')
                    ->label('')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->visible(fn (PluginVersion $record): bool => $record->file_path && file_exists($record->file_path))
                    ->action(function (PluginVersion $record) {
                        $service = app(PluginVersionService::class);
                        return $service->downloadVersion($record->id);
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => PluginVersionResource\Pages\ListPluginVersions::route('/'),
        ];
    }
}
