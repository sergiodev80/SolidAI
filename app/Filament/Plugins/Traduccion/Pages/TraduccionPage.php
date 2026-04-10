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
    public bool $documentoEstaTraducido = false; // true si es documento_{idioma}_V1.docx
    public string $targetLanguage = 'es';
    public bool $esRevisor = false;
    public ?int $idAsignacionRevisor = null;

    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'asignacion' => $this->asignacion,
            'documento' => $this->asignacion->adjunto,
            'traductoresAsignados' => $this->traductoresAsignados,
            'pdfOriginalUrl' => $this->pdfOriginalUrl,
            'documentoV1Url' => $this->documentoV1Url,
            'documentoTraducido' => $this->documentoTraducido,
            'documentoEstaTraducido' => $this->documentoEstaTraducido,
            'targetLanguage' => $this->targetLanguage,
            'latestVersion' => $this->latestVersion,
            'esRevisor' => $this->esRevisor,
            'idAsignacionRevisor' => $this->idAsignacionRevisor,
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

        // Obtener todos los traductores asignados al mismo documento (excluir revisores)
        $this->traductoresAsignados = PresupAdjAsignacion::where('id_adjun', $this->asignacion->id_adjun)
            ->where('rol', 'traductor')
            ->distinct('login')
            ->get(['id', 'login'])
            ->pluck('login', 'id');

        // Obtener idioma destino del asignación (antes de detectar documentos)
        if ($this->asignacion->id_idiom) {
            $this->targetLanguage = $this->getLanguageCode($this->asignacion->id_idiom);
        }

        // Obtener o descargar PDF original (URL absoluta para PDF.js)
        $pdfService = app(PdfOriginalService::class);
        $pdfRelativeUrl = $pdfService->obtenerPdfOriginal($this->asignacion);
        $this->pdfOriginalUrl = $pdfRelativeUrl ? config('app.url') . ltrim($pdfRelativeUrl, '/') : null;

        // Obtener documento V1 si existe (traducción para esta asignación)
        $presupuesto = $this->asignacion->adjunto->presupuesto;
        if ($presupuesto) {
            $dirVersiones = public_path("archivos/traducciones/{$presupuesto->id_pres}/{$this->asignacion->id}");

            if (is_dir($dirVersiones)) {
                // Buscar primero documento_{idioma}_V1.docx (traducción con idioma actual)
                if ($this->targetLanguage) {
                    $rutaTraducida = "{$dirVersiones}/documento_{$this->targetLanguage}_V1.docx";
                    if (file_exists($rutaTraducida)) {
                        $this->documentoV1Url = config('app.url') . "archivos/traducciones/{$presupuesto->id_pres}/{$this->asignacion->id}/documento_{$this->targetLanguage}_V1.docx";
                        $this->latestVersion = 1;
                        $this->documentoTraducido = true;
                        $this->documentoEstaTraducido = true; // Marca que está traducido
                    }
                }

                // Si no existe traducción, buscar documento_V1.docx (extracción sin traducir)
                if (!$this->documentoTraducido && file_exists("{$dirVersiones}/documento_V1.docx")) {
                    $this->documentoV1Url = config('app.url') . "archivos/traducciones/{$presupuesto->id_pres}/{$this->asignacion->id}/documento_V1.docx";
                    $this->latestVersion = 1;
                    $this->documentoTraducido = true;
                    $this->documentoEstaTraducido = false; // Sin traducir
                }
            }
        }

        // Detectar si el usuario actual es revisor de este documento
        $userLogin = $permissionService->getUserLogin();
        if ($userLogin && $this->asignacion->rol !== 'revisor') {
            // Si accedió a través de una asignación de revisor, marcar como tal
            $asignacionRevisor = PresupAdjAsignacion::where('id_adjun', $this->asignacion->id_adjun)
                ->where('login', $userLogin)
                ->where('rol', 'revisor')
                ->first();

            if ($asignacionRevisor) {
                $this->esRevisor = true;
                $this->idAsignacionRevisor = $asignacionRevisor->id;
            }
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
