<x-filament-panels::page>

<div class="traduccion-container" style="display: flex; flex-direction: column; height: 100vh; overflow: hidden;">

    {{-- Header con info de asignación --}}
    <div style="padding: 1rem; border-bottom: 1px solid #e5e7eb; background: #f9fafb;">
        <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0;">
            Traducción — {{ $asignacion->adjunto->nombre_archivo ?? '' }}
        </h1>
        <p style="color: #6b7280; margin: 0.5rem 0 0 0; font-size: 0.875rem;">
            Páginas asignadas: {{ $asignacion->pag_inicio }} - {{ $asignacion->pag_fin }}
            · Estado: <span style="color: #2563eb; font-weight: 600;">{{ $asignacion->estado }}</span>
        </p>
    </div>

    {{-- 3 Paneles --}}
    <div class="paneles-container" style="display: flex; gap: 0; flex: 1; overflow: hidden;">

        {{-- Panel Izquierdo: PDF Original --}}
        <div style="flex: 0 0 35%; border-right: 1px solid #e5e7eb; overflow-y: auto; padding: 1rem;">
            <div style="margin-bottom: 1rem;">
                <h2 style="font-size: 0.875rem; font-weight: 600; color: #374151; margin: 0 0 1rem 0;">
                    ORIGINAL ({{ $asignacion->adjunto->nombre_archivo ?? '' }})
                </h2>
            </div>
            @include('filament.traduccion.panel-original', [
                'asignacion' => $asignacion,
                'documento' => $documento ?? null
            ])
        </div>

        {{-- Panel Central: Editor OnlyOffice --}}
        <div style="flex: 0 0 50%; border-right: 1px solid #e5e7eb; overflow-y: auto;">
            <div style="padding: 1rem; border-bottom: 1px solid #e5e7eb;">
                <h2 style="font-size: 0.875rem; font-weight: 600; color: #374151; margin: 0;">
                    TRADUCCIÓN (V{{ $latestVersion ?? 1 }})
                </h2>
            </div>
            @include('filament.traduccion.panel-traduccion', [
                'asignacion' => $asignacion,
                'idAsignacion' => $idAsignacion,
                'latestVersion' => $latestVersion ?? 1,
                'onlyofficeUrl' => $onlyofficeUrl ?? ''
            ])
        </div>

        {{-- Panel Derecho: Cambios y Justificaciones --}}
        <div style="flex: 0 0 15%; overflow-y: auto; padding: 1rem; background: #fafafa;">
            <div style="margin-bottom: 1rem;">
                <h2 style="font-size: 0.875rem; font-weight: 600; color: #374151; margin: 0 0 1rem 0;">
                    CAMBIOS
                </h2>
            </div>
            @include('filament.traduccion.panel-cambios', [
                'asignacion' => $asignacion,
                'idAsignacion' => $idAsignacion,
            ])
        </div>

    </div>

    {{-- Footer: Botones de acción --}}
    <div style="padding: 1rem; border-top: 1px solid #e5e7eb; background: #f9fafb; display: flex; gap: 0.5rem;">
        <button
            class="fi-btn fi-btn-size-md fi-rounded-md fi-btn-color-primary"
            onclick="guardarDocumento()"
            style="padding: 0.5rem 1rem; background: #2563eb; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem; font-weight: 500;"
        >
            Guardar
        </button>

        <button
            class="fi-btn fi-btn-size-md fi-rounded-md fi-btn-color-info"
            onclick="compararVersiones()"
            style="padding: 0.5rem 1rem; background: #0891b2; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem; font-weight: 500;"
        >
            Justificar Cambios
        </button>

        <button
            class="fi-btn fi-btn-size-md fi-rounded-md fi-btn-color-success"
            onclick="enviarParaRevision()"
            style="padding: 0.5rem 1rem; background: #16a34a; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem; font-weight: 500;"
        >
            Enviar para Revisión
        </button>
    </div>

</div>

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

<style>
.traduccion-container {
    height: calc(100vh - 4rem);
}

.paneles-container {
    background: white;
}
</style>

</x-filament-panels::page>
