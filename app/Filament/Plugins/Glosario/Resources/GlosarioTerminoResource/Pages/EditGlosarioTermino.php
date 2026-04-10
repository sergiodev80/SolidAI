<?php

namespace App\Filament\Plugins\Glosario\Resources\GlosarioTerminoResource\Pages;

use App\Filament\Plugins\Glosario\Resources\GlosarioTerminoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGlosarioTermino extends EditRecord
{
    protected static string $resource = GlosarioTerminoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
