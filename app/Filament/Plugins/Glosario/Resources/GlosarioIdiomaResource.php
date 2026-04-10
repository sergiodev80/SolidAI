<?php

namespace App\Filament\Plugins\Glosario\Resources;

use App\Filament\Plugins\Glosario\Resources\GlosarioIdiomaResource\Pages;
use App\Models\Idioma;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GlosarioIdiomaResource extends Resource
{
    protected static ?string $model = Idioma::class;

    protected static ?string $navigationLabel = 'Idiomas';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-language';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Glosario';
    }

    public static function getModelLabel(): string
    {
        return 'Idioma';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Idiomas';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Información del Idioma')->schema([
                TextInput::make('cod_idiom')
                    ->label('Código')
                    ->disabled(),
                TextInput::make('nombre_idiom')
                    ->label('Nombre')
                    ->disabled(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cod_idiom')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nombre_idiom')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('terminos_count')
                    ->label('Términos en Glosario')
                    ->counts('terminos')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Sin acciones de edición/eliminación (tabla externa)
            ])
            ->bulkActions([
                // Sin acciones bulk (tabla externa)
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListGlosarioIdioma::route('/'),
        ];
    }
}
