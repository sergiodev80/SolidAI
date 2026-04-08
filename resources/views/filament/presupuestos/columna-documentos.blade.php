<button
    type="button"
    x-data
    x-on:click="$dispatch('abrir-modal-documentos', { id: {{ $getRecord()->id_pres }} })"
    style="background:transparent;border:none;padding:0;cursor:pointer"
>
    <x-filament::badge color="success" icon="heroicon-o-paper-clip">
        Documentos
    </x-filament::badge>
</button>
