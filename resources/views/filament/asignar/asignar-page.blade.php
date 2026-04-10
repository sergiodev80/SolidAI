<x-filament-panels::page>

    {{-- Cabecera del presupuesto --}}
    <div style="margin-bottom:1.5rem;">
        <h2 style="font-size:1.5rem;font-weight:700;" class="text-gray-900 dark:text-white">
            Presupuesto #{{ $this->idPresup }}
        </h2>
        @if($this->presupuesto)
            <p class="text-sm text-gray-500 dark:text-gray-400" style="margin-top:.25rem;">
                {{ $this->presupuesto->nomb_pres ?? '' }}
                @if($this->presupuesto->plazo_ent)
                    · Plazo: {{ $this->presupuesto->plazo_ent }}
                @endif
            </p>
        @endif
    </div>

    {{-- Cards de documentos --}}
    @forelse($this->getDocumentos() as $documento)
        @php
            $traductores = $documento->asignaciones->where('rol', 'traductor');
            $revisores   = $documento->asignaciones->where('rol', 'revisor');

            $borderClass = match(true) {
                $traductores->isNotEmpty() && $revisores->isNotEmpty() => 'asignar-border-blue',
                $traductores->isEmpty() && $revisores->isEmpty()       => 'asignar-border-red',
                default                                                 => 'asignar-border-amber',
            };
        @endphp

        <div class="asignar-card {{ $borderClass }}">

            {{-- Cabecera --}}
            <div class="asignar-card-header">
                @php
                    $ftpController = new \App\Http\Controllers\FtpFileController();
                    try {
                        $pageCount = $ftpController->getPdfPageCount($documento->nombre_archivo);
                        $archivoEncontrado = true;
                    } catch (\Exception $e) {
                        $pageCount = 0;
                        $archivoEncontrado = false;
                    }
                @endphp
                @if($archivoEncontrado)
                    {{ ($this->previsualizarAction)([
                        'filename' => $documento->nombre_archivo,
                        'nombre' => $documento->nombre_archivo,
                        'paginas' => $pageCount
                    ]) }}
                    {{ ($this->asignarAction)(['id_adjun' => $documento->id_adjun, 'nombre' => $documento->nombre_archivo]) }}
                @else
                    <div style="padding: 1rem; background-color: #fee2e2; border-radius: 0.5rem; color: #991b1b; display: flex; align-items: center; gap: 0.75rem;">
                        <span>⚠️</span>
                        <span style="font-weight: 500;">Archivo no encontrado: {{ $documento->nombre_archivo }}</span>
                    </div>
                @endif
            </div>

            {{-- Traductores | Revisores --}}
            <div class="asignar-cols">

                <div>
                    <p class="asignar-col-label">Traductores</p>
                    @forelse($traductores as $asig)
                        @php
                            $badgeClass = match($asig->estado) {
                                'Asignado'      => 'estado-asignado',
                                'En Traducción' => 'estado-traduccion',
                                'En Revisión'   => 'estado-revision',
                                'Aceptado'      => 'estado-aceptado',
                                'Impreso'       => 'estado-impreso',
                                'Entregado'     => 'estado-entregado',
                                default         => 'estado-entregado',
                            };
                        @endphp
                        <div class="asignar-row">
                            <div class="asignar-row-info">
                                <span>{{ $asig->usuario?->name ?? $asig->login }}</span>
                                <span class="asignar-row-pages">· p.{{ $asig->pag_inicio }}–{{ $asig->pag_fin }}</span>
                                <span class="estado-badge {{ $badgeClass }}">{{ $asig->estado }}</span>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <a
                                    href="/admin/traduccion/{{ $asig->id }}"
                                    class="asignar-action-btn"
                                    title="Ver traducción"
                                    style="padding: 0.375rem 0.625rem; font-size: 0.875rem; background-color: #3b82f6; color: white; border-radius: 0.375rem; text-decoration: none;"
                                >
                                    Ver traducción
                                </a>
                                <button
                                    wire:click="eliminarAsignacion({{ $asig->id }})"
                                    wire:confirm="¿Quitar esta asignación?"
                                    class="asignar-remove-btn"
                                    title="Quitar"
                                >✕</button>
                            </div>
                        </div>
                    @empty
                        <p class="asignar-empty">sin asignar</p>
                    @endforelse
                </div>

                <div>
                    <p class="asignar-col-label">Revisores</p>
                    @forelse($revisores as $asig)
                        @php
                            $badgeClass = match($asig->estado) {
                                'Asignado'      => 'estado-asignado',
                                'En Traducción' => 'estado-traduccion',
                                'En Revisión'   => 'estado-revision',
                                'Aceptado'      => 'estado-aceptado',
                                'Impreso'       => 'estado-impreso',
                                'Entregado'     => 'estado-entregado',
                                default         => 'estado-entregado',
                            };
                            // Revisor accede al trabajo del primer traductor
                            $primerTraductor = $traductores->first();
                            $urlRevisor = $primerTraductor ? "/admin/traduccion/{$primerTraductor->id}" : '#';
                        @endphp
                        <div class="asignar-row">
                            <div class="asignar-row-info">
                                <span>{{ $asig->usuario?->name ?? $asig->login }}</span>
                                <span class="asignar-row-pages">· p.{{ $asig->pag_inicio }}–{{ $asig->pag_fin }}</span>
                                <span class="estado-badge {{ $badgeClass }}">{{ $asig->estado }}</span>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <a
                                    href="{{ $urlRevisor }}"
                                    class="asignar-action-btn"
                                    title="Ver traducción"
                                    style="padding: 0.375rem 0.625rem; font-size: 0.875rem; background-color: #3b82f6; color: white; border-radius: 0.375rem; text-decoration: none; {{ !$primerTraductor ? 'pointer-events: none; opacity: 0.5;' : '' }}"
                                    @if(!$primerTraductor) onclick="return false;" @endif
                                >
                                    Ver traducción
                                </a>
                                <button
                                    wire:click="eliminarAsignacion({{ $asig->id }})"
                                    wire:confirm="¿Quitar esta asignación?"
                                    class="asignar-remove-btn"
                                    title="Quitar"
                                >✕</button>
                            </div>
                        </div>
                    @empty
                        <p class="asignar-empty">sin asignar</p>
                    @endforelse
                </div>

            </div>
        </div>
    @empty
        <div class="text-center text-gray-400 dark:text-gray-500" style="padding:3rem 0;">
            Este presupuesto no tiene documentos adjuntos.
        </div>
    @endforelse

</x-filament-panels::page>
