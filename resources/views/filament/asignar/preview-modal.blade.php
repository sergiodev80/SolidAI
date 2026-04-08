@php
    $filename = $filename ?? '';
    $nombre = $nombre ?? '';
    $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
    $ftpUrl = route('ftp-file.stream', ['filename' => $filename]);

    $fileType = match ($ext) {
        'pdf' => 'pdf',
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp' => 'image',
        'doc', 'docx' => 'word',
        default => 'unknown',
    };
@endphp

<div class="space-y-4">
    @if ($fileType === 'pdf')
        {{-- PDF: Mostrar con iframe --}}
        <iframe
            src="{{ $ftpUrl }}"
            class="w-full rounded-lg border border-gray-200 dark:border-gray-700"
            style="height: 500px; min-height: 500px;"
            title="PDF Preview"
        ></iframe>

    @elseif ($fileType === 'image')
        {{-- Imagen: Mostrar con img --}}
        <img
            src="{{ $ftpUrl }}"
            alt="{{ $nombre }}"
            class="w-full max-h-96 object-contain rounded-lg border border-gray-200 dark:border-gray-700"
        />

    @elseif ($fileType === 'word')
        {{-- Word: Usar OnlyOffice Document Server --}}
        <div
            id="onlyoffice-editor"
            class="rounded-lg border border-gray-200 dark:border-gray-700"
            style="height: 500px;"
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
    @endif
</div>
