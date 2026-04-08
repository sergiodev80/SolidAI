<?php

namespace App\Filament\Plugins\Presupuestos;

use App\Filament\Plugins\Presupuestos\Resources\PresupuestoResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class Presupuestos implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'presupuestos';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            PresupuestoResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
