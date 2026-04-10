<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GlosarioTermino;
use App\Models\GlosarioCategoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GlosarioController extends Controller
{
    /**
     * Buscar términos en el glosario
     */
    public function buscar(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'idioma_id' => 'nullable|integer',
            'categoria_id' => 'nullable|integer',
            'nivel' => 'nullable|in:empresa,cliente,documento',
            'cliente_id' => 'nullable|integer',
            'estado' => 'nullable|in:borrador,propuesto,aprobado,rechazado',
        ]);

        $query = GlosarioTermino::with(['idioma', 'categoria', 'cliente']);

        // Búsqueda por término o definición
        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($q_builder) use ($q) {
                $q_builder->where('termino', 'like', "%{$q}%")
                    ->orWhere('definicion', 'like', "%{$q}%");
            });
        }

        // Filtros
        if ($request->filled('idioma_id')) {
            $query->where('id_idiom', $request->input('idioma_id'));
        }

        if ($request->filled('categoria_id')) {
            $query->where('glosario_categoria_id', $request->input('categoria_id'));
        }

        if ($request->filled('nivel')) {
            $query->where('nivel', $request->input('nivel'));
        }

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->input('cliente_id'));
        }

        // Solo mostrar términos aprobados por defecto, a menos que sea admin
        if (!auth()->user() || !auth()->user()->hasRole('admin')) {
            $query->where('estado', 'aprobado');
        } else if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        $terminos = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $terminos->items(),
            'pagination' => [
                'total' => $terminos->total(),
                'per_page' => $terminos->perPage(),
                'current_page' => $terminos->currentPage(),
                'last_page' => $terminos->lastPage(),
            ],
        ]);
    }

    /**
     * Obtener términos relacionados para Azure Translator
     */
    public function paraTraduccion(Request $request): JsonResponse
    {
        $request->validate([
            'cliente_id' => 'required|integer',
            'documento_id' => 'nullable|integer',
            'idioma_origen_id' => 'required|integer',
            'idioma_destino_id' => 'required|integer',
        ]);

        $clienteId = $request->input('cliente_id');
        $documentoId = $request->input('documento_id');
        $idiomaDestinoId = $request->input('idioma_destino_id');

        // Obtener términos aprobados para este cliente/documento
        $query = GlosarioTermino::aprobados()
            ->where('id_idiom', $idiomaDestinoId)
            ->where(function ($q) use ($clienteId, $documentoId) {
                // Nivel empresa: accesible para todos
                $q->where('nivel', 'empresa')
                    // Nivel cliente: solo para este cliente
                    ->orWhere(function ($q2) use ($clienteId) {
                        $q2->where('nivel', 'cliente')
                            ->where('cliente_id', $clienteId);
                    })
                    // Nivel documento: solo para este documento
                    ->orWhere(function ($q2) use ($documentoId) {
                        if ($documentoId) {
                            $q2->where('nivel', 'documento')
                                ->where('documento_id', $documentoId);
                        }
                    });
            });

        $terminos = $query->get(['id', 'termino', 'definicion', 'categoria_id'])
            ->groupBy('categoria_id')
            ->map(function ($items, $categoriaId) {
                $categoria = GlosarioCategoria::find($categoriaId);
                return [
                    'categoria' => $categoria?->nombre ?? 'Sin categoría',
                    'terminos' => $items->map(function ($term) {
                        return [
                            'term' => $term->termino,
                            'definition' => $term->definicion,
                        ];
                    })->values()->toArray(),
                ];
            })->values()->toArray();

        return response()->json([
            'success' => true,
            'glossaries' => $terminos,
        ]);
    }

    /**
     * Incrementar contador de uso
     */
    public function registrarUso(Request $request): JsonResponse
    {
        $request->validate([
            'termino_id' => 'required|integer|exists:glosario_terminos,id',
        ]);

        $termino = GlosarioTermino::findOrFail($request->input('termino_id'));
        $termino->increment('usos');

        return response()->json([
            'success' => true,
            'message' => 'Uso registrado',
        ]);
    }

    /**
     * Exportar glosario para un documento
     */
    public function exportar(Request $request): JsonResponse
    {
        $request->validate([
            'cliente_id' => 'required|integer',
            'documento_id' => 'nullable|integer',
            'idioma_id' => 'required|integer',
            'formato' => 'nullable|in:json,csv,xlsx',
        ]);

        $clienteId = $request->input('cliente_id');
        $documentoId = $request->input('documento_id');
        $idiomaId = $request->input('idioma_id');
        $formato = $request->input('formato', 'json');

        $query = GlosarioTermino::aprobados()
            ->where('id_idiom', $idiomaId)
            ->where(function ($q) use ($clienteId, $documentoId) {
                $q->where('nivel', 'empresa')
                    ->orWhere(function ($q2) use ($clienteId) {
                        $q2->where('nivel', 'cliente')
                            ->where('cliente_id', $clienteId);
                    })
                    ->orWhere(function ($q2) use ($documentoId) {
                        if ($documentoId) {
                            $q2->where('nivel', 'documento')
                                ->where('documento_id', $documentoId);
                        }
                    });
            })
            ->with('categoria')
            ->get();

        if ($formato === 'json') {
            return response()->json([
                'success' => true,
                'data' => $query->groupBy('categoria.nombre')->map(function ($items) {
                    return $items->map(fn($t) => [
                        'term' => $t->termino,
                        'definition' => $t->definicion,
                    ])->values();
                })->toArray(),
            ]);
        }

        // Los otros formatos se pueden implementar luego con librerías como maatwebsite/excel
        return response()->json([
            'success' => false,
            'message' => "Formato {$formato} no implementado aún",
        ], 501);
    }
}
