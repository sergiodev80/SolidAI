<?php

namespace App\Filament\Plugins\Glosario\Widgets;

use App\Models\GlosarioTermino;
use App\Models\GlosarioCategoria;
use App\Models\Idioma;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GlosarioStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        try {
            $idiomas = Idioma::count();
        } catch (\Exception $e) {
            $idiomas = 0;
        }

        return [
            Stat::make('Total de Términos', GlosarioTermino::count())
                ->description('En toda la base de datos')
                ->color('success')
                ->icon('heroicon-o-book-open'),

            Stat::make('Términos Aprobados', GlosarioTermino::aprobados()->count())
                ->description('Listos para usar')
                ->color('info')
                ->icon('heroicon-o-check-circle'),

            Stat::make('Términos Pendientes', GlosarioTermino::pendientes()->count())
                ->description('Esperando revisión')
                ->color('warning')
                ->icon('heroicon-o-clock'),

            Stat::make('Categorías', GlosarioCategoria::count())
                ->description('De todas los niveles')
                ->color('info')
                ->icon('heroicon-o-tag'),

            Stat::make('Idiomas', $idiomas)
                ->description('Configurados')
                ->color('success')
                ->icon('heroicon-o-language'),

            Stat::make('Total de Usos', GlosarioTermino::sum('usos') ?? 0)
                ->description('En traducciones')
                ->color('success')
                ->icon('heroicon-o-arrow-trending-up'),
        ];
    }
}
