<style>[x-cloak]{display:none!important}</style>

<div x-data>
    <template x-teleport="body">
        <div
            x-data="{
                abierto: false,
                cargando: false,
                presupId: null,
                adjuntos: [],
                total: 0,

                async abrir(id) {
                    this.presupId = id;
                    this.adjuntos = [];
                    this.total = 0;
                    this.cargando = true;
                    this.abierto = true;

                    try {
                        const res = await fetch(`/admin/presupuestos/documentos/${id}`, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const data = await res.json();
                        this.adjuntos = data.adjuntos;
                        this.total = data.total;
                    } catch (e) {
                        this.adjuntos = [];
                    } finally {
                        this.cargando = false;
                    }
                },

                cerrar() {
                    this.abierto = false;
                    this.presupId = null;
                },

                iconoColor(ext) {
                    if (ext === 'pdf') return '#ef4444';
                    if (['doc','docx'].includes(ext)) return '#3b82f6';
                    if (['jpg','jpeg','png','gif','webp'].includes(ext)) return '#22c55e';
                    return '#9ca3af';
                }
            }"
            x-on:abrir-modal-documentos.window="abrir($event.detail.id)"
            x-on:keydown.escape.window="cerrar()"
        >
            {{-- Backdrop --}}
            <div
                x-show="abierto"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                x-on:click="cerrar()"
                x-cloak
                style="position:fixed;inset:0;z-index:9998;background:rgba(0,0,0,0.6)"
            ></div>

            {{-- Panel --}}
            <div
                x-show="abierto"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95"
                x-cloak
                style="position:fixed;inset:0;z-index:9999;padding:1rem"
            >
            <div style="display:flex;align-items:center;justify-content:center;width:100%;height:100%">
                <div style="width:100%;max-width:42rem;border-radius:0.75rem;background:#1f2937;box-shadow:0 25px 60px rgba(0,0,0,0.6);display:flex;flex-direction:column;max-height:85vh;border:1px solid rgba(255,255,255,0.08)">

                    {{-- Header --}}
                    <div style="display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(255,255,255,0.08);padding:1rem 1.5rem;flex-shrink:0">
                        <h2 style="font-size:0.9375rem;font-weight:600;color:#f9fafb;margin:0">
                            Documentos — Presupuesto #<span x-text="presupId"></span>
                        </h2>
                        <button type="button" x-on:click="cerrar()"
                            style="border-radius:0.5rem;padding:0.375rem;color:#9ca3af;border:none;background:transparent;cursor:pointer;line-height:0">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:20px;height:20px">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Body --}}
                    <div style="overflow-y:auto;padding:1.5rem;flex:1;display:flex;flex-direction:column;gap:0.75rem">

                        {{-- Spinner --}}
                        <template x-if="cargando">
                            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:3rem 0;gap:1rem">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                     style="width:48px;height:48px;color:#6b7280;animation:mdSpin 1s linear infinite">
                                    <style>@keyframes mdSpin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}</style>
                                    <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                </svg>
                                <p style="font-size:0.875rem;color:#9ca3af;margin:0">Cargando documentos…</p>
                            </div>
                        </template>

                        {{-- Sin documentos --}}
                        <template x-if="!cargando && adjuntos.length === 0">
                            <div style="display:flex;flex-direction:column;align-items:center;padding:3rem 0;gap:0.5rem">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:40px;height:40px;color:#6b7280">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                <p style="font-size:0.875rem;color:#9ca3af;margin:0">No hay documentos adjuntos.</p>
                            </div>
                        </template>

                        {{-- Lista --}}
                        <template x-if="!cargando && adjuntos.length > 0">
                            <div style="display:flex;flex-direction:column;gap:0.5rem">
                                <p style="font-size:0.8125rem;color:#9ca3af;margin:0 0 0.25rem" x-text="`${total} documento(s) encontrado(s)`"></p>

                                <template x-for="adj in adjuntos" :key="adj.id">
                                    <div style="display:flex;align-items:center;gap:0.75rem;border-radius:0.5rem;border:1px solid rgba(255,255,255,0.08);background:rgba(255,255,255,0.03);padding:0.75rem 1rem">

                                        {{-- Ícono por extensión --}}
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                             :style="`width:22px;height:22px;flex-shrink:0;color:${iconoColor(adj.ext)}`">
                                            <template x-if="adj.ext === 'pdf' || adj.ext === 'doc' || adj.ext === 'docx'">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                            </template>
                                            <template x-if="['jpg','jpeg','png','gif','webp'].includes(adj.ext)">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                            </template>
                                            <template x-if="!['pdf','doc','docx','jpg','jpeg','png','gif','webp'].includes(adj.ext)">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                                            </template>
                                        </svg>

                                        <div style="min-width:0;flex:1">
                                            <p x-text="adj.nombre" style="font-size:0.875rem;font-weight:500;color:#f3f4f6;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></p>
                                            <p x-text="`ID: ${adj.id}`" style="font-size:0.75rem;color:#6b7280;margin:0"></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    {{-- Footer --}}
                    <div style="display:flex;justify-content:flex-end;border-top:1px solid rgba(255,255,255,0.08);padding:0.75rem 1.5rem;flex-shrink:0">
                        <button type="button" x-on:click="cerrar()"
                            style="border-radius:0.5rem;border:1px solid rgba(255,255,255,0.15);padding:0.5rem 1rem;font-size:0.875rem;font-weight:500;color:#d1d5db;background:transparent;cursor:pointer">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </template>
</div>
