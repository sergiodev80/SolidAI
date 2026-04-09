<?php

namespace App\Filament\Plugins\Traduccion\Pages;

use App\Models\PresupAdjAsignacion;
use App\Services\PermissionService;
use App\Services\PdfOriginalService;
use App\Services\TraduccionAiService;
use App\Services\OnlyOfficeService;
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
    public ?string $pdfOriginalUrl = null;
    public ?string $documentoV1Url = null;
    public bool $documentoTraducido = false;
    public string $targetLanguage = 'es';

    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'documento' => $this->asignacion->adjunto,
            'traductoresAsignados' => $this->traductoresAsignados,
            'pdfOriginalUrl' => $this->pdfOriginalUrl,
            'documentoV1Url' => $this->documentoV1Url,
            'documentoTraducido' => $this->documentoTraducido,
            'targetLanguage' => $this->targetLanguage,
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
        $this->traductoresAsignados = PresupAdjAsignacion::where('id_adjun', $this->asignacion->id_adjun)
            ->distinct('login')
            ->get(['id', 'login'])
            ->pluck('login', 'id');

        // Obtener o descargar PDF original (URL absoluta para PDF.js)
        $pdfService = app(PdfOriginalService::class);
        $pdfRelativeUrl = $pdfService->obtenerPdfOriginal($this->asignacion);
        $this->pdfOriginalUrl = $pdfRelativeUrl ? config('app.url') . ltrim($pdfRelativeUrl, '/') : null;

        // Obtener documento V1 o V2 si existe (traducción para esta asignación)
        $presupuesto = $this->asignacion->adjunto->presupuesto;
        if ($presupuesto) {
            $dirVersiones = public_path("archivos/traducciones/{$presupuesto->id_pres}/{$this->asignacion->id}");

            // Detectar versión más reciente (V2 > V1)
            if (is_dir($dirVersiones)) {
                if (file_exists("{$dirVersiones}/documento_V2.docx")) {
                    $this->documentoV1Url = config('app.url') . "archivos/traducciones/{$presupuesto->id_pres}/{$this->asignacion->id}/documento_V2.docx";
                    $this->latestVersion = 2;
                    $this->documentoTraducido = true;
                } elseif (file_exists("{$dirVersiones}/documento_V1.docx")) {
                    $this->documentoV1Url = config('app.url') . "archivos/traducciones/{$presupuesto->id_pres}/{$this->asignacion->id}/documento_V1.docx";
                    $this->latestVersion = 1;
                    $this->documentoTraducido = true;
                }
            }
        }

        // Obtener idioma destino del asignación
        if ($this->asignacion->id_idiom) {
            $this->targetLanguage = $this->getLanguageCode($this->asignacion->id_idiom);
        }
    }

    /**
     * Mapea ID de idioma a código de Azure Translator
     */
    private function getLanguageCode(int $idIdiom): string
    {
        return match ($idIdiom) {
            1 => 'es',      // Español
            2 => 'en',      // Inglés
            3 => 'pt',      // Portugués
            4 => 'fr',      // Francés
            5 => 'de',      // Alemán
            6 => 'it',      // Italiano
            7 => 'ja',      // Japonés
            8 => 'zh',      // Chino
            9 => 'ru',      // Ruso
            10 => 'ar',     // Árabe
            default => 'es',
        };
    }

    public function getTitle(): string
    {
        return $this->asignacion ? 'Traducción - ' . $this->asignacion->adjunto?->nombre_archivo : 'Traducción';
    }
}
