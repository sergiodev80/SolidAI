<?php

namespace App\Filament\Plugins\Glosario\Resources\GlosarioCategoriaResource\Pages;

use App\Filament\Plugins\Glosario\Resources\GlosarioCategoriaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGlosarioCategoria extends ListRecords
{
    protected static string $resource = GlosarioCategoriaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
