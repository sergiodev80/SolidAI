<?php

namespace App\Filament\Plugins\Colaboradores\Resources\ColaboradorResource\Pages;

use App\Filament\Plugins\Colaboradores\Resources\ColaboradorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\ViewAction;

class ListColaboradores extends ListRecords
{
    protected static string $resource = ColaboradorResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
