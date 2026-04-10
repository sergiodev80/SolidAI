<?php

namespace App\Filament\Plugins\ColaboradoresAUsuarios;

use Filament\Contracts\Plugin;
use Filament\Panel;

class ColaboradoresAUsuarios implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'colaboradores-a-usuarios';
    }

    public function register(Panel $panel): void
    {
        // No registramos páginas ni recursos aquí
        // La página se accede directamente vía ruta /admin/colabtouser
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
