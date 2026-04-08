<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Traducción - {{ $asignacion->adjunto->nombre_archivo ?? '' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #ffffff;
            color: #111827;
        }

        body {
            display: flex;
            flex-direction: column;
        }

        .traduccion-wrapper {
            display: flex;
            flex-direction: column;
            height: 100vh;
            background: white;
        }

        /* Header */
        .traduccion-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
            flex-shrink: 0;
        }

        .traduccion-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .traduccion-header-meta {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .traduccion-header-meta span {
            margin-right: 1rem;
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

        /* Main container */
        .traduccion-container {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        /* Paneles */
        .panel {
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border-right: 1px solid #e5e7eb;
        }

        .panel-header {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
            flex-shrink: 0;
        }

        .panel-header h2 {
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin: 0;
        }

        .panel-content {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }

        /* Panel Izquierdo - Original */
        .panel-original {
            flex: 0 0 35%;
        }

        /* Panel Central - Traducción */
        .panel-traduccion {
            flex: 0 0 50%;
        }

        /* Panel Derecho - Cambios */
        .panel-cambios {
            flex: 0 0 15%;
            border-right: none;
            background: #fafafa;
        }

        /* Footer */
        .traduccion-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
            display: flex;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .btn {
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

        /* Placeholder text */
        .placeholder {
            color: #9ca3af;
            font-size: 0.875rem;
        }

        .pages-info {
            font-size: 0.75rem;
            color: #6b7280;
            margin-bottom: 1rem;
        }

        .pages-info strong {
            color: #374151;
            display: block;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>

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
            <div class="panel panel-original">
                <div class="panel-header">
                    <h2>ORIGINAL ({{ $asignacion->adjunto->nombre_archivo ?? '' }})</h2>
                </div>
                <div class="panel-content">
                    <div class="placeholder">
                        📄 <strong>PDF Viewer (PDF.js)</strong>
                    </div>
                    <p class="placeholder" style="margin-top: 1rem;">
                        El visor PDF se cargará aquí. Integración con PDF.js o similar.
                    </p>
                    <p class="placeholder" style="margin-top: 1rem;">
                        El PDF original está disponible en la carpeta de traducciones.
                    </p>
                </div>
            </div>

            {{-- Panel Central: Editor OnlyOffice --}}
            <div class="panel panel-traduccion">
                <div class="panel-header">
                    <h2>TRADUCCIÓN (V{{ $latestVersion ?? 1 }})</h2>
                </div>
                <div class="panel-content">
                    <div class="placeholder">
                        ✏️ <strong>Editor OnlyOffice</strong>
                    </div>
                    <p class="placeholder" style="margin-top: 1rem;">
                        El documento editable se cargará en OnlyOffice con JWT.
                    </p>
                    <p class="placeholder" style="margin-top: 1rem;">
                        Podrás editar libremente el contenido traducido automáticamente por Azure.
                    </p>
                </div>
            </div>

            {{-- Panel Derecho: Cambios y Justificaciones --}}
            <div class="panel panel-cambios">
                <div class="panel-header">
                    <h2>CAMBIOS</h2>
                </div>
                <div class="panel-content">
                    <div class="pages-info">
                        <strong>Páginas</strong>
                        {{ $asignacion->pag_inicio }} - {{ $asignacion->pag_fin }}
                    </div>

                    <hr style="border: none; border-top: 1px solid #d1d5db; margin: 1rem 0;">

                    <div id="cambios-lista" style="font-size: 0.75rem;">
                        <p class="placeholder" style="text-align: center; padding: 2rem 0;">
                            Sin cambios aún
                        </p>
                    </div>
                </div>
            </div>

        </div>

        {{-- Footer: Botones de acción --}}
        <div class="traduccion-footer">
            <button class="btn btn-primary" onclick="guardarDocumento()">
                Guardar
            </button>

            <button class="btn btn-info" onclick="compararVersiones()">
                Justificar Cambios
            </button>

            <button class="btn btn-success" onclick="enviarParaRevision()">
                Enviar para Revisión
            </button>
        </div>

    </div>

    {{-- Scripts --}}
    <script>
        function guardarDocumento() {
            alert('Guardar documento (implementar integración OnlyOffice)');
        }

        function compararVersiones() {
            fetch('{{ route("traduccion.comparar", ["id_asignacion" => $idAsignacion]) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    from_version: 1,
                    to_version: {{ $latestVersion ?? 1 }}
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Se encontraron ' + data.count + ' cambios');
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function enviarParaRevision() {
            if (confirm('¿Enviar este documento para revisión?')) {
                fetch('{{ route("traduccion.enviar-revision", ["id_asignacion" => $idAsignacion]) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                })
                .then(response => {
                    if (response.ok) {
                        alert('Documento enviado para revisión');
                        location.reload();
                    } else {
                        alert('Error enviando documento');
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }
    </script>

</body>
</html>
