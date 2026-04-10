<?php

namespace App\Filament\Plugins\Glosario\Resources\GlosarioIdiomaResource\Pages;

use App\Filament\Plugins\Glosario\Resources\GlosarioIdiomaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGlosarioIdioma extends ListRecords
{
    protected static string $resource = GlosarioIdiomaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
