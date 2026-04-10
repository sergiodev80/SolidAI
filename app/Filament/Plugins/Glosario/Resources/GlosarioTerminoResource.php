<?php

namespace App\Filament\Plugins\Glosario\Resources;

use App\Filament\Plugins\Glosario\Resources\GlosarioTerminoResource\Pages;
use App\Models\GlosarioTermino;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GlosarioTerminoResource extends Resource
{
    protected static ?string $model = GlosarioTermino::class;

    protected static ?string $navigationLabel = 'Términos';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-book-open';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Glosario';
    }

    public static function getModelLabel(): string
    {
        return 'Término';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Términos';
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Tabs')->tabs([
                Tabs\Tab::make('Información')
                    ->schema([
                        Section::make('Término Original')->schema([
                            Grid::make(2)->schema([
                                TextInput::make('termino_original')
                                    ->label('Término')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('id_idiom_original')
                                    ->label('Idioma')
                                    ->relationship('idiomaOriginal', 'nombre_idiom')
                                    ->required()
                                    ->searchable(),
                            ]),
                        ]),
                        Section::make('Término Traducido')->schema([
                            Grid::make(2)->schema([
                                TextInput::make('termino_traducido')
                                    ->label('Término')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('id_idiom_traducido')
                                    ->label('Idioma')
                                    ->relationship('idiomaTraducido', 'nombre_idiom')
                                    ->required()
                                    ->searchable(),
                            ]),
                        ]),
                        Section::make('Clasificación')->schema([
                            Textarea::make('contexto')
                                ->label('Contexto')
                                ->maxLength(500)
                                ->columnSpanFull(),
                            Select::make('nivel')
                                ->label('Nivel')
                                ->options([
                                    'empresa' => 'Empresa',
                                    'categoria' => 'Categoría / Subcategoría',
                                    'cliente' => 'Cliente',
                                    'documento' => 'Documento',
                                ])
                                ->required()
                                ->live()
                                ->columnSpanFull(),
                            Select::make('glosario_categoria_id')
                                ->label('Categoría')
                                ->relationship('categoria', 'nombre')
                                ->searchable()
                                ->visible(fn(callable $get) => $get('nivel') === 'categoria')
                                ->required(fn(callable $get) => $get('nivel') === 'categoria'),
                            Select::make('cliente_id')
                                ->label('Cliente')
                                ->relationship('cliente', 'nombre')
                                ->searchable()
                                ->visible(fn(callable $get) => in_array($get('nivel'), ['cliente', 'documento']))
                                ->required(fn(callable $get) => in_array($get('nivel'), ['cliente', 'documento'])),
                        ]),
                    ]),
                Tabs\Tab::make('Aprobación')
                    ->schema([
                        Section::make('Estado de Aprobación')->schema([
                            Select::make('estado')
                                ->label('Estado')
                                ->options([
                                    'borrador' => 'Borrador',
                                    'propuesto' => 'Propuesto',
                                    'aprobado' => 'Aprobado',
                                    'rechazado' => 'Rechazado',
                                ])
                                ->required()
                                ->live(),
                            Hidden::make('created_by')
                                ->default(fn() => auth()->id()),
                            Grid::make(2)->schema([
                                Hidden::make('approved_by')
                                    ->visible(fn(callable $get) => $get('estado') === 'aprobado'),
                                Hidden::make('approved_at')
                                    ->visible(fn(callable $get) => $get('estado') === 'aprobado'),
                            ]),
                        ]),
                    ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('termino_original')
                    ->label('Término Original')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                TextColumn::make('idiomaOriginal.nombre_idiom')
                    ->label('Idioma Original')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('termino_traducido')
                    ->label('Término Traducido')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                TextColumn::make('idiomaTraducido.nombre_idiom')
                    ->label('Idioma Traducido')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('categoria.nombre')
                    ->label('Categoría')
                    ->searchable()
                    ->sortable()
                    ->limit(25),
                TextColumn::make('nivel')
                    ->label('Nivel')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'empresa' => 'Empresa',
                        'categoria' => 'Categoría',
                        'cliente' => 'Cliente',
                        'documento' => 'Documento',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'empresa' => 'info',
                        'categoria' => 'primary',
                        'cliente' => 'warning',
                        'documento' => 'success',
                    }),
                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->color(fn(string $state): string => match ($state) {
                        'borrador' => 'gray',
                        'propuesto' => 'warning',
                        'aprobado' => 'success',
                        'rechazado' => 'danger',
                    }),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'borrador' => 'Borrador',
                        'propuesto' => 'Propuesto',
                        'aprobado' => 'Aprobado',
                        'rechazado' => 'Rechazado',
                    ]),
                SelectFilter::make('nivel')
                    ->label('Nivel')
                    ->options([
                        'empresa' => 'Empresa',
                        'categoria' => 'Categoría',
                        'cliente' => 'Cliente',
                        'documento' => 'Documento',
                    ]),
                SelectFilter::make('cliente_id')
                    ->label('Cliente')
                    ->relationship('cliente', 'nombre')
                    ->preload(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make()
                    ->modal(),
                \Filament\Actions\Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn(GlosarioTermino $record) => $record->estado === 'propuesto')
                    ->action(function (GlosarioTermino $record) {
                        $record->update([
                            'estado' => 'aprobado',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                    }),
                \Filament\Actions\Action::make('reject')
                    ->label('Rechazar')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->visible(fn(GlosarioTermino $record) => $record->estado === 'propuesto')
                    ->action(function (GlosarioTermino $record) {
                        $record->update([
                            'estado' => 'rechazado',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                    }),
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
            'index'  => Pages\ListGlosarioTermino::route('/'),
        ];
    }
}
