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
use Filament\Notifications\Notification;
use Filament\Pages\Page;

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
                    ->default('traductor'),

                Select::make('login')
                    ->label('Usuario')
                    ->options(fn () => $this->usuarios)
                    ->native(false)
                    ->required()
                    ->placeholder('Buscar por nombre...'),

                TextInput::make('pag_inicio')
                    ->label('Página inicio')
                    ->numeric()
                    ->minValue(1)
                    ->required(),

                TextInput::make('pag_fin')
                    ->label('Página fin')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
            ])
            ->action(function (array $data, array $arguments): void {
                PresupAdjAsignacion::create([
                    'id_adjun'   => $arguments['id_adjun'],
                    'login'      => $data['login'],
                    'rol'        => $data['rol'],
                    'pag_inicio' => $data['pag_inicio'],
                    'pag_fin'    => $data['pag_fin'],
                    'estado'     => 'Asignado',
                    'created_at' => now(),
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
}
