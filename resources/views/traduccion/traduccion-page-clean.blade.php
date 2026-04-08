<div class="traduccion-wrapper" style="display: flex; flex-direction: column; height: 100vh; background: white; margin: 0; padding: 0;">

    {{-- Header --}}
    <div class="traduccion-header" style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb; background: #f9fafb; flex-shrink: 0;">
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
                <p style="color: #9ca3af; font-size: 0.875rem; margin-top: 1rem;">
                    El PDF original está disponible en la carpeta de traducciones.
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
                <p style="color: #9ca3af; font-size: 0.875rem; margin-top: 1rem;">
                    Podrás editar libremente el contenido traducido automáticamente por Azure.
                </p>
            </div>
        </div>

        {{-- Panel Derecho: Cambios y Justificaciones --}}
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

                <div id="cambios-lista" style="font-size: 0.75rem;">
                    <p style="color: #9ca3af; text-align: center; padding: 2rem 0;">
                        Sin cambios aún
                    </p>
                </div>
            </div>
        </div>

    </div>

    {{-- Footer: Botones de acción --}}
    <div style="padding: 1rem 1.5rem; border-top: 1px solid #e5e7eb; background: #f9fafb; display: flex; gap: 0.5rem; flex-shrink: 0;">
        <button
            onclick="guardarDocumento()"
            style="padding: 0.5rem 1rem; border: none; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; background: #2563eb; color: white; transition: all 0.2s;"
            onmouseover="this.style.background='#1d4ed8'"
            onmouseout="this.style.background='#2563eb'"
        >
            Guardar
        </button>

        <button
            onclick="compararVersiones()"
            style="padding: 0.5rem 1rem; border: none; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; background: #0891b2; color: white; transition: all 0.2s;"
            onmouseover="this.style.background='#0e7490'"
            onmouseout="this.style.background='#0891b2'"
        >
            Justificar Cambios
        </button>

        <button
            onclick="enviarParaRevision()"
            style="padding: 0.5rem 1rem; border: none; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; background: #16a34a; color: white; transition: all 0.2s;"
            onmouseover="this.style.background='#15803d'"
            onmouseout="this.style.background='#16a34a'"
        >
            Enviar para Revisión
        </button>
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

</div>
