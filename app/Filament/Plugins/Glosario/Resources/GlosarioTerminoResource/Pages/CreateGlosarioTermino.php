<?php

namespace App\Filament\Plugins\Glosario\Resources\GlosarioTerminoResource\Pages;

use App\Filament\Plugins\Glosario\Resources\GlosarioTerminoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGlosarioTermino extends CreateRecord
{
    protected static string $resource = GlosarioTerminoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }
}
