<?php

namespace App\Filament\Plugins\Glosario\Resources\GlosarioTerminoResource\Pages;

use App\Filament\Plugins\Glosario\Resources\GlosarioTerminoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGlosarioTermino extends ListRecords
{
    protected static string $resource = GlosarioTerminoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
