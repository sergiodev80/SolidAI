<?php

namespace App\Filament\Plugins\Traduccion\Pages;

use App\Models\PresupAdjAsignacion;
use App\Services\PermissionService;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Route;

class TraduccionPage extends Page
{
    protected static ?string $title = 'Traducción';
    protected static ?string $slug = 'traduccion/{id_asignacion}';

    public ?PresupAdjAsignacion $asignacion = null;
    public ?int $idAsignacion = null;
    public ?int $latestVersion = null;
    public $traductoresAsignados = [];

    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'documento' => $this->asignacion->adjunto,
            'traductoresAsignados' => $this->traductoresAsignados,
        ]);
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-document-text';
    }

    public function getView(): string
    {
        return 'filament.traduccion.traduccion-fullscreen';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Traducción';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false; // No mostrar en menú
    }

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::Full; // Usar ancho completo
    }

    public function mount(int $id_asignacion): void
    {
        // Validar acceso
        $permissionService = app(PermissionService::class);
        if (!$permissionService->canAccessAsignacion($id_asignacion)) {
            abort(403, 'No tienes permiso para acceder a esta asignación');
        }

        // Obtener asignación
        $this->asignacion = PresupAdjAsignacion::findOrFail($id_asignacion);
        $this->idAsignacion = $id_asignacion;

        // Obtener todos los traductores asignados al mismo documento
        $this->traductoresAsignados = PresupAdjAsignacion::where('adjunto_id', $this->asignacion->adjunto_id)
            ->distinct('login')
            ->get(['id', 'login'])
            ->pluck('login', 'id');

        // Obtener última versión (será obtenida en la vista)
    }

    public function getTitle(): string
    {
        return $this->asignacion ? 'Traducción - ' . $this->asignacion->adjunto?->nombre_archivo : 'Traducción';
    }
}
