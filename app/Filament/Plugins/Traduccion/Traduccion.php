<?php

namespace App\Filament\Plugins\Traduccion;

use Filament\Contracts\Plugin;
use Filament\Panel;

class Traduccion implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'traduccion';
    }

    public function register(Panel $panel): void
    {
        // La página de traducción NO se registra aquí
        // Se accede vía ruta HTTP pura: /traduccion/{id_asignacion}
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
