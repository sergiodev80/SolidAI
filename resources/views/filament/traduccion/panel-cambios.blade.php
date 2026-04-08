<div id="cambios-panel">
    <div style="margin-bottom: 1rem;">
        <p style="color: #6b7280; font-size: 0.75rem; margin: 0;">
            Páginas
        </p>
        <p style="color: #374151; font-size: 0.875rem; font-weight: 600; margin: 0.5rem 0 0 0;">
            {{ $asignacion->pag_inicio }} - {{ $asignacion->pag_fin }}
        </p>
    </div>

    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 1rem 0;">

    <div id="cambios-lista" style="font-size: 0.75rem;">
        <p style="color: #9ca3af; text-align: center; padding: 1rem 0;">
            Sin cambios aún
        </p>
        {{--
            TODO: Mostrar cambios detectados
            - Listar cada cambio (palabra, línea, párrafo)
            - Mostrar original → nueva
            - Input para justificación
            - Marcar como justificado cuando complete
        --}}
    </div>
</div>
