<?php

namespace App\Filament\Plugins\Presupuestos\Resources;

use App\Filament\Plugins\Presupuestos\Resources\PresupuestoResource\Pages;
use App\Models\Presupuesto;
use App\Models\PresupAdj;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PresupuestoResource extends Resource
{
    protected static ?string $model = Presupuesto::class;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Presupuestos';
    }

    public static function getModelLabel(): string
    {
        return 'Presupuesto';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Presupuestos';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id_pres')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\ViewColumn::make('acciones_btn')
                    ->label('Acciones')
                    ->view('filament.presupuestos.columna-acciones'),

                Tables\Columns\ViewColumn::make('documentos_btn')
                    ->label('Documentos')
                    ->view('filament.presupuestos.columna-documentos'),

                Tables\Columns\TextColumn::make('nomb_pres')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('plazo_ent')
                    ->label('Plazo de Entrega')
                    ->sortable(),

                Tables\Columns\TextColumn::make('procesado_por')
                    ->label('Procesado por')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('estado_pres')
                    ->label('Estado')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('procesoEst.proc_estado')
                    ->label('Estado Proceso')
                    ->badge()
                    ->color(fn (Presupuesto $record): string => match($record->estado_proc) {
                        0 => 'warning',    // Amarillo
                        1 => 'info',       // Azul
                        2 => 'success',    // Verde
                        default => 'gray',
                    })
                    ->sortable('estado_proc')
                    ->searchable('estado_proc'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado_pres')
                    ->label('Estado')
                    ->options(fn () => Presupuesto::query()
                        ->distinct()
                        ->orderBy('estado_pres')
                        ->pluck('estado_pres', 'estado_pres')
                        ->filter()
                        ->toArray()
                    ),
                Tables\Filters\SelectFilter::make('estado_proc')
                    ->label('Estado Proceso')
                    ->options(fn () => Presupuesto::query()
                        ->distinct()
                        ->orderBy('estado_proc')
                        ->pluck('estado_proc', 'estado_proc')
                        ->filter()
                        ->toArray()
                    ),
            ])
            ->defaultSort('id_pres', 'desc')
            ->striped()
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPresupuestos::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
