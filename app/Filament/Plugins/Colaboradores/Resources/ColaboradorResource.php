<?php

namespace App\Filament\Plugins\Colaboradores\Resources;

use App\Filament\Plugins\Colaboradores\Resources\ColaboradorResource\Pages;
use App\Models\SeccUser;
use BackedEnum;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ColaboradorResource extends Resource
{
    protected static ?string $model = SeccUser::class;
    protected static ?string $slug = 'colaboradores';

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-users';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Colaboradores';
    }

    public static function getModelLabel(): string
    {
        return 'Colaborador';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Colaboradores';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('login')
                    ->label('Login')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\IconColumn::make('active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('trad')
                    ->label('Trad.')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Rol')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('puesto')
                    ->label('Puesto')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('active')
                    ->label('Activo')
                    ->options([
                        '1' => 'Activo',
                        '0' => 'Inactivo',
                    ]),
                Tables\Filters\SelectFilter::make('trad')
                    ->label('Traductor')
                    ->options([
                        '1' => 'Sí',
                        '0' => 'No',
                    ]),
                Tables\Filters\SelectFilter::make('role')
                    ->label('Rol')
                    ->options(fn () => SeccUser::query()
                        ->distinct()
                        ->orderBy('role')
                        ->pluck('role', 'role')
                        ->filter()
                        ->toArray()
                    ),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->defaultSort('name')
            ->striped()
            ->paginated([25, 50, 100]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información General')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('login')
                            ->label('Login'),

                        TextEntry::make('name')
                            ->label('Nombre'),

                        TextEntry::make('email')
                            ->label('Email'),

                        TextEntry::make('email_corp')
                            ->label('Email Corporativo'),

                        IconEntry::make('active')
                            ->label('Activo')
                            ->boolean(),

                        IconEntry::make('trad')
                            ->label('Traductor')
                            ->boolean(),

                        TextEntry::make('role')
                            ->label('Rol')
                            ->badge(),

                        TextEntry::make('puesto')
                            ->label('Puesto'),

                        TextEntry::make('fechaNac')
                            ->label('Fecha de Nacimiento'),

                        TextEntry::make('phone')
                            ->label('Teléfono'),

                        TextEntry::make('cel_corp')
                            ->label('Celular Corporativo'),

                        TextEntry::make('DIV')
                            ->label('División'),
                    ]),

                Section::make('Datos como Traductor')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('CI_trad')
                            ->label('CI'),

                        TextEntry::make('nro_mat_trad')
                            ->label('Nro. Matrícula'),

                        TextEntry::make('Fecha_n_trad')
                            ->label('Fecha Nacimiento (Trad.)'),

                        TextEntry::make('direc_trad')
                            ->label('Dirección'),

                        TextEntry::make('id_idioma')
                            ->label('Idioma'),
                    ]),

                Section::make('Disponibilidad')
                    ->columns(4)
                    ->schema([
                        IconEntry::make('disp_lun_trad')
                            ->label('Lunes')
                            ->boolean(),

                        IconEntry::make('disp_mar_trad')
                            ->label('Martes')
                            ->boolean(),

                        IconEntry::make('disp_mie_trad')
                            ->label('Miércoles')
                            ->boolean(),

                        IconEntry::make('disp_jue_trad')
                            ->label('Jueves')
                            ->boolean(),

                        IconEntry::make('disp_vie_trad')
                            ->label('Viernes')
                            ->boolean(),

                        IconEntry::make('disp_sab_trad')
                            ->label('Sábado')
                            ->boolean(),

                        IconEntry::make('disp_dom_trad')
                            ->label('Domingo')
                            ->boolean(),
                    ]),

                Section::make('Datos Bancarios')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('banco_id')
                            ->label('Banco'),

                        TextEntry::make('nro_cuenta_trad')
                            ->label('Nro. Cuenta'),

                        TextEntry::make('tipo_cue_trad')
                            ->label('Tipo de Cuenta'),

                        TextEntry::make('tit_cue_trad')
                            ->label('Titular de Cuenta'),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListColaboradores::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
