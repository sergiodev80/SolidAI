<x-filament-panels::page>

<!-- PDF.js desde CDN más estable -->
<script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js"></script>

<!-- OnlyOffice API -->
<script src="{{ config('services.onlyoffice.url') }}web-apps/apps/api/documents/api.js"></script>

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

    @keyframes progress {
        0% { width: 0%; }
        50% { width: 70%; }
        100% { width: 100%; }
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
            <div class="traduccion-panel-header" style="display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <h2>TRADUCCIÓN (V{{ $latestVersion ?? 1 }})</h2>
                    @if($targetLanguage && $documentoTraducido)
                        <span style="padding: 0.25rem 0.75rem; background: #e0e7ff; color: #3730a3; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 600;">
                            @php
                                $langNames = [
                                    'es' => 'ESPAÑOL',
                                    'en' => 'INGLÉS',
                                    'pt' => 'PORTUGUÉS',
                                    'fr' => 'FRANCÉS',
                                    'de' => 'ALEMÁN',
                                    'it' => 'ITALIANO',
                                    'ja' => 'JAPONÉS',
                                    'zh' => 'CHINO',
                                    'ru' => 'RUSO',
                                    'ar' => 'ÁRABE',
                                ];
                            @endphp
                            {{ $langNames[$targetLanguage] ?? strtoupper($targetLanguage) }}
                        </span>
                    @endif
                </div>
                @if($documentoTraducido && $latestVersion === 1)
                    <button id="btn-traducir-ia" type="button" style="padding: 0.5rem 1rem; background: #2563eb; color: white; border: none; border-radius: 0.375rem; font-weight: 600; cursor: pointer; font-size: 0.875rem;">
                        🤖 Traducir con IA
                    </button>
                @elseif($documentoTraducido && $latestVersion === 2)
                    <span style="padding: 0.5rem 1rem; background: #10b981; color: white; border-radius: 0.375rem; font-weight: 600; font-size: 0.875rem;">
                        ✓ Traducido
                    </span>
                @endif
            </div>
            <div class="traduccion-panel-content">
                @if($documentoTraducido)
                    <div id="onlyoffice-container" style="width: 100%; height: 100%;"></div>
                @else
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; gap: 1.5rem;">
                        <div style="text-align: center;">
                            <p style="margin: 0; color: #6b7280; font-size: 0.875rem; margin-bottom: 0.5rem;">
                                Este documento aún no ha sido extraído
                            </p>
                            <button id="btn-extraer-documento" type="button" style="padding: 0.75rem 1.5rem; background: #10b981; color: white; border: none; border-radius: 0.375rem; font-weight: 600; cursor: pointer; font-size: 0.875rem;">
                                📄 Extraer documento
                            </button>
                        </div>
                        <div id="extraccion-progress" style="display: none; text-align: center;">
                            <p style="margin: 0; color: #6b7280; font-size: 0.875rem;">
                                Extrayendo documento... Por favor espere
                            </p>
                            <div style="margin-top: 1rem; width: 200px; height: 4px; background: #e5e7eb; border-radius: 2px; overflow: hidden;">
                                <div style="height: 100%; background: #3b82f6; animation: progress 2s infinite; width: 30%;"></div>
                            </div>
                        </div>
                    </div>
                @endif
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

                {{-- Idiomas --}}
                @php
                    $langNames = [
                        1 => 'Español',
                        2 => 'Inglés',
                        3 => 'Portugués',
                        4 => 'Francés',
                        5 => 'Alemán',
                        6 => 'Italiano',
                        7 => 'Japonés',
                        8 => 'Chino',
                        9 => 'Ruso',
                        10 => 'Árabe',
                    ];
                @endphp

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.875rem;">Idioma Original</label>
                    <select id="select-idioma-original" class="traductores-select" style="margin-bottom: 0;">
                        @foreach($langNames as $id => $name)
                            <option value="{{ $id }}" @if($id === $asignacion->id_idiom_original) selected @endif>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.875rem;">Idioma a Traducir</label>
                    <select id="select-idioma-traducir" class="traductores-select" style="margin-bottom: 0;">
                        @foreach($langNames as $id => $name)
                            <option value="{{ $id }}" @if($id === $asignacion->id_idiom) selected @endif>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <button id="btn-guardar-idiomas" type="button" style="width: 100%; padding: 0.5rem; background: #10b981; color: white; border: none; border-radius: 0.375rem; font-weight: 600; cursor: pointer; font-size: 0.875rem; margin-bottom: 1rem;">
                    ✓ Guardar Idiomas
                </button>

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
    // Esperar a que la librería esté disponible
    if (typeof pdfjsLib !== 'undefined') {
        // Configurar PDF.js
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js';
    }

    let pdfDoc = null;
    let currentPage = 1;
    let totalPages = 0;
    let zoom = 100;

    const pdfUrl = '{{ trim($pdfOriginalUrl ?? '') }}';
    const canvas = document.getElementById('pdf-canvas');
    const ctx = canvas ? canvas.getContext('2d') : null;
    const currentPageSpan = document.getElementById('current-page');
    const totalPagesSpan = document.getElementById('total-pages');
    const prevPageBtn = document.getElementById('prev-page');
    const nextPageBtn = document.getElementById('next-page');
    const zoomSlider = document.getElementById('zoom-slider');

    function renderPage(pageNum) {
        if (!pdfDoc || !ctx) return;

        if (pageNum < 1) pageNum = 1;
        if (pageNum > totalPages) pageNum = totalPages;

        currentPage = pageNum;
        currentPageSpan.textContent = currentPage;

        pdfDoc.getPage(pageNum).then(page => {
            const scale = zoom / 100;
            const viewport = page.getViewport({ scale });

            canvas.width = viewport.width;
            canvas.height = viewport.height;

            page.render({
                canvasContext: ctx,
                viewport: viewport
            }).promise.catch(err => console.error('Error renderizando:', err));
        }).catch(err => console.error('Error obteniendo página:', err));
    }

    // Cargar PDF
    if (typeof pdfjsLib !== 'undefined') {
        pdfjsLib.getDocument(pdfUrl).promise.then(pdf => {
            pdfDoc = pdf;
            totalPages = pdf.numPages;
            totalPagesSpan.textContent = totalPages;
            renderPage(currentPage);
        }).catch(err => {
            console.error('Error al cargar PDF:', err);
            const container = document.getElementById('pdf-viewer-container');
            if (container) {
                container.innerHTML = '<p style="color: #ef4444; text-align: center; padding: 2rem;">Error al cargar el PDF: ' + err.message + '</p>';
            }
        });
    } else {
        console.error('PDF.js no está cargado');
    }

    // Eventos
    if (prevPageBtn) prevPageBtn.addEventListener('click', () => renderPage(currentPage - 1));
    if (nextPageBtn) nextPageBtn.addEventListener('click', () => renderPage(currentPage + 1));
    if (zoomSlider) zoomSlider.addEventListener('input', (e) => {
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

{{-- Script para Traducción AI y OnlyOffice --}}
<script>
    @if(!$documentoTraducido)
    // Botón para extraer documento (sin traducción)
    document.getElementById('btn-extraer-documento')?.addEventListener('click', async function() {
        const btn = this;
        const progress = document.getElementById('extraccion-progress');

        btn.style.display = 'none';
        progress.style.display = 'block';

        try {
            const response = await fetch('/admin/traduccion/extraer-documento/{{ $asignacion->id }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
            });

            const data = await response.json();

            if (data.success) {
                // Recargar página para mostrar OnlyOffice
                window.location.reload();
            } else {
                alert('Error: ' + (data.message || 'Error desconocido'));
                btn.style.display = 'block';
                progress.style.display = 'none';
            }
        } catch (error) {
            console.error('Error extrayendo:', error);
            alert('Error al extraer documento: ' + error.message);
            btn.style.display = 'block';
            progress.style.display = 'none';
        }
    });
    @else
    // Mapeo de idioma ID a código Azure
    const langCodeMap = {
        1: 'es',  // Español
        2: 'en',  // Inglés
        3: 'pt',  // Portugués
        4: 'fr',  // Francés
        5: 'de',  // Alemán
        6: 'it',  // Italiano
        7: 'ja',  // Japonés
        8: 'zh',  // Chino
        9: 'ru',  // Ruso
        10: 'ar', // Árabe
    };

    // Botón para traducir con IA
    document.getElementById('btn-traducir-ia')?.addEventListener('click', async function() {
        const btn = this;
        btn.disabled = true;
        btn.style.opacity = '0.6';
        btn.textContent = '⏳ Traduciendo...';

        // Obtener idioma seleccionado del selector
        const idiomaSelectorElement = document.getElementById('select-idioma-traducir');
        const idiomId = idiomaSelectorElement ? idiomaSelectorElement.value : '2';
        const targetLanguageCode = langCodeMap[idiomId] || 'es';

        try {
            const response = await fetch('/admin/traduccion/traducir-ai/{{ $asignacion->id }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    targetLanguage: targetLanguageCode
                })
            });

            const data = await response.json();

            if (data.success) {
                // Recargar página para mostrar documento traducido
                window.location.reload();
            } else {
                alert('Error: ' + (data.message || 'Error desconocido'));
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.textContent = '🤖 Traducir con IA';
            }
        } catch (error) {
            console.error('Error traduciendo:', error);
            alert('Error al traducir documento: ' + error.message);
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.textContent = '🤖 Traducir con IA';
        }
    });

    // Botón para guardar los idiomas seleccionados
    document.getElementById('btn-guardar-idiomas')?.addEventListener('click', async function() {
        const btn = this;
        const idiomaOriginalSelect = document.getElementById('select-idioma-original');
        const idiomaTraducirSelect = document.getElementById('select-idioma-traducir');

        const idiomaOriginal = idiomaOriginalSelect ? idiomaOriginalSelect.value : null;
        const idiomaTraducir = idiomaTraducirSelect ? idiomaTraducirSelect.value : null;

        if (!idiomaOriginal || !idiomaTraducir) {
            alert('Por favor selecciona ambos idiomas');
            return;
        }

        btn.disabled = true;
        btn.style.opacity = '0.6';
        const originalText = btn.textContent;
        btn.textContent = '⏳ Guardando...';

        try {
            const response = await fetch('/admin/traduccion/guardar-idiomas/{{ $asignacion->id }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    id_idiom_original: idiomaOriginal,
                    id_idiom: idiomaTraducir
                })
            });

            const data = await response.json();

            if (data.success) {
                btn.textContent = '✓ Guardado';
                btn.style.background = '#059669';
                setTimeout(() => {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    btn.textContent = originalText;
                    btn.style.background = '#10b981';
                }, 2000);
            } else {
                alert('Error: ' + (data.message || 'Error desconocido'));
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.textContent = originalText;
            }
        } catch (error) {
            console.error('Error guardando idiomas:', error);
            alert('Error al guardar: ' + error.message);
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.textContent = originalText;
        }
    });

    // Inicializar OnlyOffice si el documento ya está traducido
    document.addEventListener('DOMContentLoaded', function() {
        const config = {
            "document": {
                "fileType": "docx",
                "key": "{{ md5(trim($documentoV1Url ?? '') . time()) }}",
                "title": "Documento Traducción",
                "url": "{{ trim($documentoV1Url ?? '') }}"
            },
            "documentType": "word",
            "editorConfig": {
                "mode": "edit",
                "user": {
                    "id": "{{ auth()->id() }}",
                    "name": "{{ auth()->user()->name ?? 'Usuario' }}"
                }
            },
            "height": "100%",
            "width": "100%"
        };

        console.log('Inicializando OnlyOffice con:', config);

        if (typeof DocsAPI !== 'undefined') {
            try {
                new DocsAPI.DocEditor('onlyoffice-container', config);
            } catch (error) {
                console.error('Error inicializando OnlyOffice:', error);
                document.getElementById('onlyoffice-container').innerHTML = '<p style="color: #ef4444;">Error al inicializar OnlyOffice: ' + error.message + '</p>';
            }
        } else {
            console.error('OnlyOffice API (DocsAPI) no está disponible');
            document.getElementById('onlyoffice-container').innerHTML = '<p style="color: #ef4444;">OnlyOffice API no cargó. Verifica la URL: {{ config("services.onlyoffice.url") }}</p>';
        }
    });
    @endif
</script>

</x-filament-panels::page>
