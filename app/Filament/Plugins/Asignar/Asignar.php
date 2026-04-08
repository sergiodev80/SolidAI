<?php

namespace App\Filament\Plugins\Asignar;

use App\Filament\Plugins\Asignar\Pages\AsignarPage;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;

class Asignar implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'asignar';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            AsignarPage::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        FilamentAsset::register([
            Css::make('asignar', base_path('resources/css/filament/asignar.css')),
        ], 'app/asignar');
    }
}
