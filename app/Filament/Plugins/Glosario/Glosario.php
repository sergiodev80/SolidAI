<?php

namespace App\Filament\Plugins\Glosario;

use Filament\Contracts\Plugin;
use Filament\Panel;
use App\Filament\Plugins\Glosario\Resources\GlosarioTerminoResource;
use App\Filament\Plugins\Glosario\Resources\GlosarioCategoriaResource;
use App\Filament\Plugins\Glosario\Resources\GlosarioIdiomaResource;
use App\Filament\Plugins\Glosario\Widgets\GlosarioStatsWidget;

class Glosario implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'glosario';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                GlosarioCategoriaResource::class,
                GlosarioTerminoResource::class,
            ])
            ->widgets([
                GlosarioStatsWidget::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
