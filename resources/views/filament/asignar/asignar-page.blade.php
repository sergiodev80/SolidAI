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
                {{ ($this->previsualizarAction)(['filename' => $documento->nombre_archivo, 'nombre' => $documento->nombre_archivo]) }}
                {{ ($this->asignarAction)(['id_adjun' => $documento->id_adjun, 'nombre' => $documento->nombre_archivo]) }}
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
                            <button
                                wire:click="eliminarAsignacion({{ $asig->id }})"
                                wire:confirm="¿Quitar esta asignación?"
                                class="asignar-remove-btn"
                                title="Quitar"
                            >✕</button>
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
                        @endphp
                        <div class="asignar-row">
                            <div class="asignar-row-info">
                                <span>{{ $asig->usuario?->name ?? $asig->login }}</span>
                                <span class="asignar-row-pages">· p.{{ $asig->pag_inicio }}–{{ $asig->pag_fin }}</span>
                                <span class="estado-badge {{ $badgeClass }}">{{ $asig->estado }}</span>
                            </div>
                            <button
                                wire:click="eliminarAsignacion({{ $asig->id }})"
                                wire:confirm="¿Quitar esta asignación?"
                                class="asignar-remove-btn"
                                title="Quitar"
                            >✕</button>
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
