<?php

namespace App\Filament\Plugins\Presupuestos\Resources\PresupuestoResource\Pages;

use App\Filament\Plugins\Presupuestos\Resources\PresupuestoResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListPresupuestos extends ListRecords
{
    protected static string $resource = PresupuestoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getFooter(): ?View
    {
        return view('filament.presupuestos.modal-documentos-global');
    }
}
