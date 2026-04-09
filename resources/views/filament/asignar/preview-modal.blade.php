@php
    $filename = $filename ?? '';
    $nombre = $nombre ?? '';
    $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
    $ftpUrl = route('ftp-file.stream', ['filename' => $filename]);
    $pdfBlobUrl = route('ftp-pdf.blob', ['filename' => $filename]);

    $fileType = match ($ext) {
        'pdf' => 'pdf',
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp' => 'image',
        'doc', 'docx' => 'word',
        default => 'unknown',
    };
@endphp

<style>
    .preview-container {
        width: calc(100% + 3rem);
        margin-left: -1.5rem;
        margin-right: -1.5rem;
        margin-bottom: -1.5rem;
    }
    .preview-pdf-wrapper {
        width: 100%;
        height: 650px;
        overflow: hidden;
        background: #f5f5f5;
    }
    .preview-pdf-wrapper object {
        width: 100% !important;
        height: 100% !important;
        display: block;
    }
    .preview-iframe {
        width: 100%;
        height: 650px;
        border: none;
    }
</style>

<div class="preview-container">
    @if ($fileType === 'pdf')
        {{-- PDF: Mostrar con object tag (navegador nativo) --}}
        <div class="preview-pdf-wrapper">
            <object
                data="{{ $pdfBlobUrl }}"
                type="application/pdf"
            >
                <p style="padding: 20px; color: #dc2626;">
                    Su navegador no puede mostrar PDFs.
                    <a href="{{ $pdfBlobUrl }}" download style="color: #2563eb; text-decoration: underline;">
                        Descargar PDF →
                    </a>
                </p>
            </object>
        </div>

    @elseif ($fileType === 'image')
        {{-- Imagen: Mostrar con img --}}
        <div class="p-6">
            <img
                src="{{ $ftpUrl }}"
                alt="{{ $nombre }}"
                class="w-full max-h-96 object-contain rounded-lg border border-gray-200 dark:border-gray-700"
            />
        </div>

    @elseif ($fileType === 'word')
        {{-- Word: Usar OnlyOffice Document Server --}}
        <div
            id="onlyoffice-editor"
            class="preview-iframe"
        ></div>

        <script>
            // Configuración para OnlyOffice
            const docConfig = {
                document: {
                    fileType: '{{ $ext }}',
                    key: '{{ md5($filename) }}',
                    title: '{{ $nombre }}',
                    url: '{{ $ftpUrl }}',
                },
                documentType: 'word',
                editorConfig: {
                    mode: 'view',
                    lang: 'es',
                    user: {
                        id: '{{ Auth::id() }}',
                        name: '{{ Auth::user()->name ?? 'Usuario' }}',
                    },
                },
                height: '500px',
            };

            // Cargar OnlyOffice Document Server
            @if ($onlyofficeUrl)
                const script = document.createElement('script');
                script.src = '{{ $onlyofficeUrl }}/web-apps/apps/api/documents/api.js';
                script.onload = function () {
                    new DocsAPI.DocEditor('onlyoffice-editor', docConfig);
                };
                document.head.appendChild(script);
            @else
                document.getElementById('onlyoffice-editor').innerHTML =
                    '<p class="text-red-600 dark:text-red-400">OnlyOffice no está configurado</p>';
            @endif
        </script>

    @else
        {{-- Tipo de archivo no soportado --}}
        <div class="p-6">
            <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/20">
                <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                    Tipo de archivo no soportado
                </p>
                <p class="text-xs text-yellow-700 dark:text-yellow-300">
                    Solo se pueden previsualizar archivos PDF, imágenes y documentos Word.
                </p>
                <a
                    href="{{ $ftpUrl }}"
                    download="{{ $nombre }}"
                    class="mt-2 inline-block text-sm font-semibold text-yellow-700 hover:text-yellow-900 dark:text-yellow-300 dark:hover:text-yellow-100"
                >
                    Descargar archivo →
                </a>
            </div>
        </div>
    @endif
</div>
