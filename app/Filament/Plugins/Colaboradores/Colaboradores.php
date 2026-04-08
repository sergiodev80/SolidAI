<?php

namespace App\Filament\Plugins\Colaboradores;

use App\Filament\Plugins\Colaboradores\Resources\ColaboradorResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class Colaboradores implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'colaboradores';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            ColaboradorResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
