<x-filament-panels::page>

<style>
    /* Ocultar sidebar y titulo de Filament */
    [data-sidebar], .fi-sidebar, aside[data-sidebar],
    .fi-page-header, [class*="PageHeader"], h1.fi-page-title, .fi-header {
        display: none !important;
    }

    /* Expandir contenido a fullscreen */
    main, [role="main"] {
        margin-left: 0 !important;
    }
</style>

<div class="flex flex-col h-screen bg-white dark:bg-gray-900 -m-6">

    {{-- Header --}}
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex-shrink-0">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
            Traducción — {{ $asignacion->adjunto->nombre_archivo ?? '' }}
        </h1>
        <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
            <span>
                Páginas asignadas: <strong class="text-gray-900 dark:text-white">{{ $asignacion->pag_inicio }} - {{ $asignacion->pag_fin }}</strong>
            </span>
            <span class="inline-block px-3 py-1 bg-blue-500 text-white rounded text-xs font-semibold">
                {{ $asignacion->estado }}
            </span>
        </div>
    </div>

    {{-- 3 Paneles --}}
    <div class="flex flex-1 overflow-hidden">

        {{-- Panel Izquierdo: PDF Original --}}
        <div class="flex-[0_0_35%] flex flex-col overflow-hidden border-r border-gray-200 dark:border-gray-700">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex-shrink-0">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
                    ORIGINAL ({{ $asignacion->adjunto->nombre_archivo ?? '' }})
                </h2>
            </div>
            <div class="flex-1 overflow-y-auto p-4">
                <div class="text-gray-500 dark:text-gray-400 text-sm">
                    📄 <strong>PDF Viewer (PDF.js)</strong>
                </div>
                <p class="text-gray-500 dark:text-gray-400 text-sm mt-4">
                    El visor PDF se cargará aquí. Integración con PDF.js o similar.
                </p>
            </div>
        </div>

        {{-- Panel Central: Editor OnlyOffice --}}
        <div class="flex-[0_0_50%] flex flex-col overflow-hidden border-r border-gray-200 dark:border-gray-700">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex-shrink-0">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
                    TRADUCCIÓN (V{{ $latestVersion ?? 1 }})
                </h2>
            </div>
            <div class="flex-1 overflow-y-auto p-4">
                <div class="text-gray-500 dark:text-gray-400 text-sm">
                    ✏️ <strong>Editor OnlyOffice</strong>
                </div>
                <p class="text-gray-500 dark:text-gray-400 text-sm mt-4">
                    El documento editable se cargará en OnlyOffice con JWT.
                </p>
            </div>
        </div>

        {{-- Panel Derecho: Cambios --}}
        <div class="flex-[0_0_15%] flex flex-col overflow-hidden bg-gray-50 dark:bg-gray-800/50">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex-shrink-0">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
                    CAMBIOS
                </h2>
            </div>
            <div class="flex-1 overflow-y-auto p-4">
                <div class="text-xs text-gray-600 dark:text-gray-400 mb-4">
                    <strong class="block text-sm text-gray-900 dark:text-white">Páginas</strong>
                    <span class="block mt-1">{{ $asignacion->pag_inicio }} - {{ $asignacion->pag_fin }}</span>
                </div>
                <hr class="my-4 border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 dark:text-gray-400 text-center py-8">
                    Sin cambios aún
                </p>
            </div>
        </div>

    </div>

    {{-- Footer: Botones --}}
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex gap-2 flex-shrink-0">
        <button
            onclick="alert('Guardar documento')"
            class="px-4 py-2 bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 text-white rounded text-sm font-medium transition"
        >
            Guardar
        </button>
        <button
            onclick="alert('Justificar cambios')"
            class="px-4 py-2 bg-cyan-500 hover:bg-cyan-600 dark:bg-cyan-600 dark:hover:bg-cyan-700 text-white rounded text-sm font-medium transition"
        >
            Justificar Cambios
        </button>
        <button
            onclick="alert('Enviar para revisión')"
            class="px-4 py-2 bg-green-500 hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-700 text-white rounded text-sm font-medium transition"
        >
            Enviar para Revisión
        </button>
    </div>

</div>

</x-filament-panels::page>
