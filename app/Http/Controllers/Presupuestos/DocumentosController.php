<?php

namespace App\Http\Controllers\Presupuestos;

use App\Http\Controllers\Controller;
use App\Models\PresupAdj;
use Illuminate\Http\JsonResponse;

class DocumentosController extends Controller
{
    public function lista(int $presupuestoId): JsonResponse
    {
        $adjuntos = PresupAdj::where('id_presup', $presupuestoId)
            ->get(['id_adjun', 'adjun_adjun'])
            ->map(fn ($adj) => [
                'id'     => $adj->id_adjun,
                'nombre' => $adj->adjun_adjun,
                'ext'    => strtolower(pathinfo($adj->adjun_adjun, PATHINFO_EXTENSION)),
            ]);

        return response()->json([
            'adjuntos' => $adjuntos,
            'total'    => $adjuntos->count(),
        ]);
    }
}
