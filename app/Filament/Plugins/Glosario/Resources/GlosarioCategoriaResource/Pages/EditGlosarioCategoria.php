<?php

namespace App\Filament\Plugins\Glosario\Resources\GlosarioCategoriaResource\Pages;

use App\Filament\Plugins\Glosario\Resources\GlosarioCategoriaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGlosarioCategoria extends EditRecord
{
    protected static string $resource = GlosarioCategoriaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
