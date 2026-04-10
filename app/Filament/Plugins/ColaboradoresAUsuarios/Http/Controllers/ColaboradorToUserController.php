<?php

namespace App\Filament\Plugins\ColaboradoresAUsuarios\Http\Controllers;

use App\Filament\Plugins\ColaboradoresAUsuarios\Services\ColaboradorService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ColaboradorToUserController extends Controller
{
    public function __construct(
        private ColaboradorService $service
    ) {}

    /**
     * Endpoint para crear acceso de colaborador
     * POST /admin/colabtouser/crear-acceso
     */
    public function crearAcceso(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $resultado = $this->service->crearAcceso($validated['email']);

        return response()->json($resultado);
    }
}
