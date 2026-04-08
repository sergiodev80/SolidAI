<x-filament-panels::page>

<!-- PDF.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>pdfjsWorker = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';</script>

<style>
    /* Ocultar sidebar, titulo de Filament, encabezado y topbar */
    [data-sidebar], .fi-sidebar, aside[data-sidebar],
    .fi-page-header, [class*="PageHeader"], h1.fi-page-title, .fi-header,
    .fi-topbar {
        display: none !important;
    }

    /* Expandir contenido a fullscreen */
    main, [role="main"] {
        margin: 0 !important;
        padding: 0 !important;
        height: 100vh;
        overflow: hidden;
    }

    .fi-page-header-main-ctn {
        margin: 0 !important;
        padding: 0 !important;
    }

    .traduccion-wrapper {
        display: flex;
        flex-direction: column;
        height: 100vh;
        width: 100%;
        background: white;
        margin: 0;
        padding: 0;
    }

    .traduccion-header {
        display: none;
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
        border-right: 1px solid #e5e7eb;
    }

    .traduccion-panel-header {
        padding: 1rem;
        border-bottom: 1px solid #e5e7eb;
        background: #f9fafb;
        flex-shrink: 0;
    }

    .traduccion-panel-header h2 {
        font-size: 0.875rem;
        font-weight: 600;
        color: #374151;
        margin: 0;
    }

    .traduccion-panel-content {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
        background: #ffffff;
        color: #6b7280;
        font-size: 0.875rem;
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

    .traduccion-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid #e5e7eb;
        background: #f9fafb;
        display: flex;
        gap: 0.5rem;
        flex-shrink: 0;
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

    .page-meta strong {
        color: #374151;
        font-weight: 600;
    }

    .cambios-divider {
        border: none;
        border-top: 1px solid #e5e7eb;
        margin: 1rem 0;
    }

    .traductores-select {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        color: #374151;
        background: white;
        cursor: pointer;
        margin-bottom: 1rem;
    }

    .traductores-select:hover {
        border-color: #d1d5db;
        background: #f9fafb;
    }

    .estado-label {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        background: #dbeafe;
        color: #1e40af;
        border-radius: 0.375rem;
        font-size: 0.75rem;
        font-weight: 600;
        margin-top: 0.5rem;
    }
</style>

<div class="traduccion-wrapper">

    {{-- 3 Paneles --}}
    <div class="traduccion-container">

        {{-- Panel Izquierdo: PDF Original --}}
        <div class="traduccion-panel panel-original">
            <div class="traduccion-panel-header">
                <h2>ORIGINAL ({{ $asignacion->adjunto->nombre_archivo ?? '' }})</h2>
            </div>
            <div class="traduccion-panel-content" id="pdf-viewer-container">
                @if($pdfOriginalUrl)
                    <div id="pdf-controls" style="margin-bottom: 1rem; display: flex; gap: 0.5rem; align-items: center;">
                        <button id="prev-page" style="padding: 0.5rem 1rem; border: 1px solid #e5e7eb; background: white; cursor: pointer; border-radius: 0.375rem;">← Anterior</button>
                        <span id="page-info" style="font-size: 0.875rem; color: #6b7280; flex: 1; text-align: center;">Página <span id="current-page">1</span> de <span id="total-pages">0</span></span>
                        <button id="next-page" style="padding: 0.5rem 1rem; border: 1px solid #e5e7eb; background: white; cursor: pointer; border-radius: 0.375rem;">Siguiente →</button>
                        <input type="range" id="zoom-slider" min="50" max="200" value="100" style="width: 100px; cursor: pointer;">
                    </div>
                    <div id="pdf-canvas-container" style="flex: 1; overflow: auto; border: 1px solid #e5e7eb; background: #f9fafb;">
                        <canvas id="pdf-canvas" style="display: block; margin: 0 auto;"></canvas>
                    </div>
                @else
                    <p style="color: #ef4444; text-align: center; padding: 2rem;">
                        No se pudo cargar el documento PDF
                    </p>
                @endif
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
                {{-- Select de Traductores --}}
                <select class="traductores-select" onchange="window.location.href='/admin/traduccion/' + this.value">
                    @foreach($traductoresAsignados as $id => $login)
                        <option value="{{ $id }}" @if($id === $asignacion->id) selected @endif>
                            {{ $login }}
                        </option>
                    @endforeach
                </select>

                {{-- Información de Páginas y Estado --}}
                <div class="page-meta">
                    <strong>Páginas</strong>
                    <span style="display: block; margin-top: 0.5rem;">{{ $asignacion->pag_inicio }} - {{ $asignacion->pag_fin }}</span>
                </div>

                <div style="margin-top: 0.5rem;">
                    <span class="estado-label">{{ $asignacion->estado }}</span>
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

<script>
    @if($pdfOriginalUrl)
    // Configurar PDF.js
    const pdfjsLib = window['pdfjs-dist/build/pdf'];
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    let pdfDoc = null;
    let currentPage = 1;
    let totalPages = 0;
    let zoom = 100;

    const pdfUrl = '{{ $pdfOriginalUrl }}';
    const canvas = document.getElementById('pdf-canvas');
    const ctx = canvas.getContext('2d');
    const currentPageSpan = document.getElementById('current-page');
    const totalPagesSpan = document.getElementById('total-pages');
    const prevPageBtn = document.getElementById('prev-page');
    const nextPageBtn = document.getElementById('next-page');
    const zoomSlider = document.getElementById('zoom-slider');

    // Cargar PDF
    pdfjsLib.getDocument(pdfUrl).promise.then(pdf => {
        pdfDoc = pdf;
        totalPages = pdf.numPages;
        totalPagesSpan.textContent = totalPages;
        renderPage(currentPage);
    }).catch(err => {
        console.error('Error al cargar PDF:', err);
        document.getElementById('pdf-viewer-container').innerHTML = '<p style="color: #ef4444; text-align: center; padding: 2rem;">Error al cargar el PDF</p>';
    });

    // Renderizar página
    function renderPage(pageNum) {
        if (pdfDoc === null) return;

        if (pageNum < 1) pageNum = 1;
        if (pageNum > totalPages) pageNum = totalPages;

        currentPage = pageNum;
        currentPageSpan.textContent = currentPage;

        pdfDoc.getPage(pageNum).then(page => {
            const scale = zoom / 100;
            const viewport = page.getViewport({ scale });

            canvas.width = viewport.width;
            canvas.height = viewport.height;

            const renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };

            page.render(renderContext).promise.then(() => {
                console.log(`Página ${pageNum} renderizada`);
            }).catch(err => {
                console.error('Error renderizando página:', err);
            });
        });
    }

    // Eventos
    prevPageBtn.addEventListener('click', () => renderPage(currentPage - 1));
    nextPageBtn.addEventListener('click', () => renderPage(currentPage + 1));

    zoomSlider.addEventListener('input', (e) => {
        zoom = parseInt(e.target.value);
        renderPage(currentPage);
    });

    // Navegación con teclado
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') renderPage(currentPage - 1);
        if (e.key === 'ArrowRight') renderPage(currentPage + 1);
    });
    @endif
</script>

</x-filament-panels::page>
