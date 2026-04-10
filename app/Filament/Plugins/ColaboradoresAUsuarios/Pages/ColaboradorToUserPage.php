<?php

namespace App\Filament\Plugins\ColaboradoresAUsuarios\Pages;

use Filament\Pages\Page;

class ColaboradorToUserPage extends Page
{
    protected static string $view = 'filament.plugins.colaboradores-a-usuarios.colaborador-to-user-page';

    protected static ?string $title = 'Crear Acceso como Colaborador';

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    public static function getNavigationGroup(): ?string
    {
        return null; // No mostrar en sidebar
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false; // No registrar en navegación
    }

    public static function canAccess(): bool
    {
        return false; // No accesible por defecto
    }
}
