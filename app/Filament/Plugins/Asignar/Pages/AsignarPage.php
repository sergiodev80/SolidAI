<?php

namespace App\Filament\Plugins\Asignar\Pages;

use App\Models\PresupAdj;
use App\Models\PresupAdjAsignacion;
use App\Models\Presupuesto;
use App\Models\SeccUser;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class AsignarPage extends Page
{
    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-user-group';
    }

    public function getView(): string
    {
        return 'filament.asignar.asignar-page';
    }
    protected static ?string $title = 'Asignar';
    protected static ?string $navigationLabel = 'Asignar';
    protected static ?string $slug = 'asignar/{id_presup}';

    public ?Presupuesto $presupuesto = null;
    public int $idPresup = 0;
    public array $usuarios = [];

    public static function getNavigationGroup(): ?string
    {
        return 'Asignar';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(int $id_presup): void
    {
        $this->idPresup = $id_presup;
        $this->presupuesto = Presupuesto::where('id_pres', $id_presup)->firstOrFail();
        $this->usuarios = SeccUser::orderBy('name')->pluck('name', 'login')->toArray();
    }

    public function getDocumentos()
    {
        return PresupAdj::where('id_presup', $this->idPresup)
            ->with(['asignaciones.usuario'])
            ->get();
    }

    public function asignarAction(): Action
    {
        // Obtener idiomas del ERP
        $idiomas = DB::connection('erp')
            ->table('idiomas')
            ->pluck('nombre_idiom', 'id_idiom')
            ->toArray();

        return Action::make('asignar')
            ->modalHeading(fn (array $arguments) => 'Añadir asignación — ' . ($arguments['nombre'] ?? ''))
            ->modalWidth('lg')
            ->form([
                Radio::make('rol')
                    ->label('Rol')
                    ->options([
                        'traductor' => 'Traductor',
                        'revisor'   => 'Revisor',
                    ])
                    ->inline()
                    ->required()
                    ->default('traductor')
                    ->columnSpanFull(),

                Select::make('login')
                    ->label('Usuario')
                    ->options(fn () => $this->usuarios)
                    ->native(false)
                    ->required()
                    ->placeholder('Buscar por nombre...')
                    ->columnSpanFull(),

                Select::make('id_idiom_original')
                    ->label('Idioma original')
                    ->options($idiomas)
                    ->native(false)
                    ->required()
                    ->placeholder('Seleccionar idioma original...')
                    ->columnSpan(1),

                Select::make('id_idiom')
                    ->label('Idioma de traducción')
                    ->options($idiomas)
                    ->native(false)
                    ->required()
                    ->placeholder('Seleccionar idioma de traducción...')
                    ->columnSpan(1),

                TextInput::make('pag_inicio')
                    ->label('Página inicio')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->columnSpan(1),

                TextInput::make('pag_fin')
                    ->label('Página fin')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->columnSpan(1),
            ])
            ->action(function (array $data, array $arguments): void {
                PresupAdjAsignacion::create([
                    'id_adjun'          => $arguments['id_adjun'],
                    'login'             => $data['login'],
                    'rol'               => $data['rol'],
                    'pag_inicio'        => $data['pag_inicio'],
                    'pag_fin'           => $data['pag_fin'],
                    'id_idiom_original' => $data['id_idiom_original'],
                    'id_idiom'          => $data['id_idiom'],
                    'estado'            => 'Asignado',
                    'created_at'        => now(),
                ]);

                Notification::make()
                    ->success()
                    ->title('Asignación guardada')
                    ->send();
            });
    }

    public function eliminarAsignacion(int $id): void
    {
        PresupAdjAsignacion::where('id', $id)->delete();

        Notification::make()
            ->success()
            ->title('Asignación eliminada')
            ->send();
    }

    public function previsualizarAction(): Action
    {
        return Action::make('previsualizarAction')
            ->label(fn (array $arguments) => $arguments['nombre'] . ' (' . ($arguments['paginas'] ?? '?') . ' pág.)')
            ->color('info')
            ->icon('heroicon-o-document')
            ->modalHeading(fn (array $arguments) => 'Previsualizar — ' . ($arguments['nombre'] ?? ''))
            ->modalWidth('7xl')
            ->form(fn (array $arguments) => [
                ViewField::make('preview')
                    ->view('filament.asignar.preview-modal')
                    ->viewData([
                        'filename' => $arguments['filename'] ?? '',
                        'nombre' => $arguments['nombre'] ?? '',
                        'onlyofficeUrl' => config('app.onlyoffice_url'),
                        'onlyofficeJwtSecret' => config('app.onlyoffice_jwt_secret'),
                    ])
                    ->extraAttributes(['class' => 'fi-fo-group-footer-ctn-item-wrapper']),
            ])
            ->modalSubmitAction(false)
            ->modalCloseButton(true)
            ->modalCancelActionLabel('Cerrar');
    }

    /**
     * Determina el tipo de archivo según su extensión
     */
    private function getFileType(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf' => 'pdf',
            'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp' => 'image',
            'doc', 'docx' => 'word',
            default => 'unknown',
        };
    }
}
