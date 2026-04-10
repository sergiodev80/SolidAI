<?php

namespace App\Filament\Plugins\Shield;

use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class Shield implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'shield';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            RoleResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
