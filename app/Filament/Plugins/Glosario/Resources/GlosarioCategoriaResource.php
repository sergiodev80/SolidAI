<?php

namespace App\Filament\Plugins\Glosario\Resources;

use App\Filament\Plugins\Glosario\Resources\GlosarioCategoriaResource\Pages;
use App\Models\GlosarioCategoria;
use App\Models\Cliente;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GlosarioCategoriaResource extends Resource
{
    protected static ?string $model = GlosarioCategoria::class;

    protected static ?string $navigationLabel = 'Categorías';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-tag';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Glosario';
    }

    public static function getModelLabel(): string
    {
        return 'Categoría';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Categorías';
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Información de la Categoría')->schema([
                TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                Textarea::make('descripcion')
                    ->label('Descripción')
                    ->maxLength(500)
                    ->columnSpanFull(),
                Select::make('parent_id')
                    ->label('Categoría Padre *Si es padre dejar vacío')
                    ->relationship('parent', 'nombre')
                    ->searchable()
                    ->preload()
                    ->nullable(),
            ])->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('parent.nombre')
                    ->label('Categoría Padre')
                    ->searchable(),
                TextColumn::make('terminos_count')
                    ->label('Términos')
                    ->counts('terminos'),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                \Filament\Actions\EditAction::make()
                    ->modal(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListGlosarioCategoria::route('/'),
        ];
    }
}
