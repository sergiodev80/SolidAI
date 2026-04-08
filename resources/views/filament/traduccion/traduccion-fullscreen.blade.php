<x-filament-panels::page>

<style>
    /* Ocultar sidebar de Filament */
    [data-sidebar],
    .fi-sidebar,
    aside[data-sidebar] {
        display: none !important;
    }

    /* Expandir contenido a fullscreen */
    main,
    [role="main"] {
        margin-left: 0 !important;
    }
</style>

<div class="traduccion-wrapper" style="display: flex; flex-direction: column; height: calc(100vh - 80px); background: white; margin: -1.5rem -1.5rem 0 -1.5rem; padding: 0;">

    {{-- Header --}}
    <div style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb; background: #f9fafb; flex-shrink: 0;">
        <h1 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; margin: 0 0 0.5rem 0;">
            Traducción — {{ $asignacion->adjunto->nombre_archivo ?? '' }}
        </h1>
        <div style="font-size: 0.875rem; color: #6b7280;">
            <span style="margin-right: 1rem;">
                Páginas asignadas: <strong>{{ $asignacion->pag_inicio }} - {{ $asignacion->pag_fin }}</strong>
            </span>
            <span style="display: inline-block; padding: 0.25rem 0.75rem; background: #2563eb; color: white; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 600;">
                {{ $asignacion->estado }}
            </span>
        </div>
    </div>

    {{-- 3 Paneles --}}
    <div style="display: flex; flex: 1; overflow: hidden;">

        {{-- Panel Izquierdo: PDF Original --}}
        <div style="flex: 0 0 35%; display: flex; flex-direction: column; overflow: hidden; border-right: 1px solid #e5e7eb;">
            <div style="padding: 1rem; border-bottom: 1px solid #e5e7eb; background: #f9fafb; flex-shrink: 0;">
                <h2 style="font-size: 0.875rem; font-weight: 600; color: #374151; margin: 0;">
                    ORIGINAL ({{ $asignacion->adjunto->nombre_archivo ?? '' }})
                </h2>
            </div>
            <div style="flex: 1; overflow-y: auto; padding: 1rem;">
                <div style="color: #9ca3af; font-size: 0.875rem;">
                    📄 <strong>PDF Viewer (PDF.js)</strong>
                </div>
                <p style="color: #9ca3af; font-size: 0.875rem; margin-top: 1rem;">
                    El visor PDF se cargará aquí. Integración con PDF.js o similar.
                </p>
            </div>
        </div>

        {{-- Panel Central: Editor OnlyOffice --}}
        <div style="flex: 0 0 50%; display: flex; flex-direction: column; overflow: hidden; border-right: 1px solid #e5e7eb;">
            <div style="padding: 1rem; border-bottom: 1px solid #e5e7eb; background: #f9fafb; flex-shrink: 0;">
                <h2 style="font-size: 0.875rem; font-weight: 600; color: #374151; margin: 0;">
                    TRADUCCIÓN (V{{ $latestVersion ?? 1 }})
                </h2>
            </div>
            <div style="flex: 1; overflow-y: auto; padding: 1rem;">
                <div style="color: #9ca3af; font-size: 0.875rem;">
                    ✏️ <strong>Editor OnlyOffice</strong>
                </div>
                <p style="color: #9ca3af; font-size: 0.875rem; margin-top: 1rem;">
                    El documento editable se cargará en OnlyOffice con JWT.
                </p>
            </div>
        </div>

        {{-- Panel Derecho: Cambios --}}
        <div style="flex: 0 0 15%; display: flex; flex-direction: column; overflow: hidden; background: #fafafa;">
            <div style="padding: 1rem; border-bottom: 1px solid #e5e7eb; background: #f9fafb; flex-shrink: 0;">
                <h2 style="font-size: 0.875rem; font-weight: 600; color: #374151; margin: 0;">
                    CAMBIOS
                </h2>
            </div>
            <div style="flex: 1; overflow-y: auto; padding: 1rem;">
                <div style="font-size: 0.75rem; color: #6b7280; margin-bottom: 1rem;">
                    <strong style="color: #374151; display: block; font-size: 0.875rem;">Páginas</strong>
                    <span style="margin-top: 0.5rem; display: block;">{{ $asignacion->pag_inicio }} - {{ $asignacion->pag_fin }}</span>
                </div>
                <hr style="border: none; border-top: 1px solid #d1d5db; margin: 1rem 0;">
                <p style="color: #9ca3af; text-align: center; padding: 2rem 0; font-size: 0.75rem;">
                    Sin cambios aún
                </p>
            </div>
        </div>

    </div>

    {{-- Footer: Botones --}}
    <div style="padding: 1rem 1.5rem; border-top: 1px solid #e5e7eb; background: #f9fafb; display: flex; gap: 0.5rem; flex-shrink: 0;">
        <button
            onclick="alert('Guardar documento')"
            style="padding: 0.5rem 1rem; border: none; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; background: #2563eb; color: white;"
        >
            Guardar
        </button>
        <button
            onclick="alert('Justificar cambios')"
            style="padding: 0.5rem 1rem; border: none; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; background: #0891b2; color: white;"
        >
            Justificar Cambios
        </button>
        <button
            onclick="alert('Enviar para revisión')"
            style="padding: 0.5rem 1rem; border: none; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; background: #16a34a; color: white;"
        >
            Enviar para Revisión
        </button>
    </div>

</div>

</x-filament-panels::page>
