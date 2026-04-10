<?php

namespace App\Filament\Plugins\Glosario\Resources\GlosarioIdiomaResource\Pages;

use App\Filament\Plugins\Glosario\Resources\GlosarioIdiomaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGlosarioIdioma extends EditRecord
{
    protected static string $resource = GlosarioIdiomaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
