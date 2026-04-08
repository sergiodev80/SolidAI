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

    /* Variables de colores para light/dark */
    :root {
        --color-bg-light: #ffffff;
        --color-bg-light-secondary: #f9fafb;
        --color-bg-dark: #111827;
        --color-bg-dark-secondary: #1f2937;
        --color-text-light: #111827;
        --color-text-light-secondary: #6b7280;
        --color-text-dark: #f3f4f6;
        --color-text-dark-secondary: #9ca3af;
        --color-border-light: #e5e7eb;
        --color-border-dark: #374151;
    }

    .traduccion-wrapper {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 80px);
        background: white;
        margin: -1.5rem -1.5rem 0 -1.5rem;
        padding: 0;
    }

    @media (prefers-color-scheme: dark) {
        .traduccion-wrapper {
            background: #111827;
        }
    }

    .traduccion-header {
        padding: 1.5rem;
        border-bottom: 1px solid;
        border-color: #e5e7eb;
        background: #f9fafb;
        flex-shrink: 0;
    }

    @media (prefers-color-scheme: dark) {
        .traduccion-header {
            background: #1f2937;
            border-color: #374151;
        }
    }

    .traduccion-header h1 {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0 0 0.5rem 0;
        color: #111827;
    }

    @media (prefers-color-scheme: dark) {
        .traduccion-header h1 {
            color: #f3f4f6;
        }
    }

    .traduccion-header-meta {
        font-size: 0.875rem;
        color: #6b7280;
    }

    @media (prefers-color-scheme: dark) {
        .traduccion-header-meta {
            color: #9ca3af;
        }
    }

    .traduccion-header-meta span {
        margin-right: 1rem;
    }

    .traduccion-container {
        display: flex;
        flex: 1;
        overflow: hidden;
    }

    .traduccion-panel {
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border-right: 1px solid;
        border-color: #e5e7eb;
    }

    @media (prefers-color-scheme: dark) {
        .traduccion-panel {
            border-color: #374151;
        }
    }

    .traduccion-panel-header {
        padding: 1rem;
        border-bottom: 1px solid;
        border-color: #e5e7eb;
        background: #f9fafb;
        flex-shrink: 0;
    }

    @media (prefers-color-scheme: dark) {
        .traduccion-panel-header {
            background: #1f2937;
            border-color: #374151;
        }
    }

    .traduccion-panel-header h2 {
        font-size: 0.875rem;
        font-weight: 600;
        color: #374151;
        margin: 0;
    }

    @media (prefers-color-scheme: dark) {
        .traduccion-panel-header h2 {
            color: #e5e7eb;
        }
    }

    .traduccion-panel-content {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
        background: #ffffff;
        color: #6b7280;
        font-size: 0.875rem;
    }

    @media (prefers-color-scheme: dark) {
        .traduccion-panel-content {
            background: #111827;
            color: #d1d5db;
        }
    }

    .panel-original {
        flex: 0 0 35%;
    }

    .panel-traduccion {
        flex: 0 0 50%;
    }

    .panel-cambios {
        flex: 0 0 15%;
        border-right: none;
        background: #fafafa;
    }

    @media (prefers-color-scheme: dark) {
        .panel-cambios {
            background: #0f172a;
        }
    }

    .traduccion-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid;
        border-color: #e5e7eb;
        background: #f9fafb;
        display: flex;
        gap: 0.5rem;
        flex-shrink: 0;
    }

    @media (prefers-color-scheme: dark) {
        .traduccion-footer {
            background: #1f2937;
            border-color: #374151;
        }
    }

    .traduccion-btn {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-primary {
        background: #2563eb;
        color: white;
    }

    .btn-primary:hover {
        background: #1d4ed8;
    }

    .btn-info {
        background: #0891b2;
        color: white;
    }

    .btn-info:hover {
        background: #0e7490;
    }

    .btn-success {
        background: #16a34a;
        color: white;
    }

    .btn-success:hover {
        background: #15803d;
    }

    .estado-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        background: #2563eb;
        color: white;
        border-radius: 0.375rem;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .page-meta {
        font-size: 0.75rem;
        color: #6b7280;
    }

    @media (prefers-color-scheme: dark) {
        .page-meta {
            color: #9ca3af;
        }
    }

    .page-meta strong {
        color: #374151;
        font-weight: 600;
    }

    @media (prefers-color-scheme: dark) {
        .page-meta strong {
            color: #e5e7eb;
        }
    }

    .cambios-divider {
        border: none;
        border-top: 1px solid #e5e7eb;
        margin: 1rem 0;
    }

    @media (prefers-color-scheme: dark) {
        .cambios-divider {
            border-top-color: #374151;
        }
    }
</style>

<div class="traduccion-wrapper">

    {{-- Header --}}
    <div class="traduccion-header">
        <h1>Traducción — {{ $asignacion->adjunto->nombre_archivo ?? '' }}</h1>
        <div class="traduccion-header-meta">
            <span>Páginas asignadas: <strong>{{ $asignacion->pag_inicio }} - {{ $asignacion->pag_fin }}</strong></span>
            <span class="estado-badge">{{ $asignacion->estado }}</span>
        </div>
    </div>

    {{-- 3 Paneles --}}
    <div class="traduccion-container">

        {{-- Panel Izquierdo: PDF Original --}}
        <div class="traduccion-panel panel-original">
            <div class="traduccion-panel-header">
                <h2>ORIGINAL ({{ $asignacion->adjunto->nombre_archivo ?? '' }})</h2>
            </div>
            <div class="traduccion-panel-content">
                <div style="margin-bottom: 1rem;">
                    📄 <strong>PDF Viewer (PDF.js)</strong>
                </div>
                <p style="margin-top: 1rem;">
                    El visor PDF se cargará aquí. Integración con PDF.js o similar.
                </p>
            </div>
        </div>

        {{-- Panel Central: Editor OnlyOffice --}}
        <div class="traduccion-panel panel-traduccion">
            <div class="traduccion-panel-header">
                <h2>TRADUCCIÓN (V{{ $latestVersion ?? 1 }})</h2>
            </div>
            <div class="traduccion-panel-content">
                <div style="margin-bottom: 1rem;">
                    ✏️ <strong>Editor OnlyOffice</strong>
                </div>
                <p style="margin-top: 1rem;">
                    El documento editable se cargará en OnlyOffice con JWT.
                </p>
            </div>
        </div>

        {{-- Panel Derecho: Cambios --}}
        <div class="traduccion-panel panel-cambios">
            <div class="traduccion-panel-header">
                <h2>CAMBIOS</h2>
            </div>
            <div class="traduccion-panel-content">
                <div class="page-meta" style="margin-bottom: 1rem;">
                    <strong>Páginas</strong>
                    <span style="display: block; margin-top: 0.5rem;">{{ $asignacion->pag_inicio }} - {{ $asignacion->pag_fin }}</span>
                </div>
                <hr class="cambios-divider">
                <p style="text-align: center; padding: 2rem 0;">
                    Sin cambios aún
                </p>
            </div>
        </div>

    </div>

    {{-- Footer: Botones --}}
    <div class="traduccion-footer">
        <button class="traduccion-btn btn-primary" onclick="alert('Guardar documento')">
            Guardar
        </button>
        <button class="traduccion-btn btn-info" onclick="alert('Justificar cambios')">
            Justificar Cambios
        </button>
        <button class="traduccion-btn btn-success" onclick="alert('Enviar para revisión')">
            Enviar para Revisión
        </button>
    </div>

</div>

</x-filament-panels::page>
