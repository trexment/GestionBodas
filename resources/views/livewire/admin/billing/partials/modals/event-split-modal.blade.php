<x-ui.modal 
    :show="$showEventSplitModal" 
    onClose="closeEventSplitModal" 
    title="Personalizar Reparto del Evento: {{ $splitEventName }}" 
    subtitle="Indica exactamente cuánto corresponde o cobró cada persona en este evento (importes fijos o porcentajes)."
    icon="⚖️"
    maxWidth="2xl"
>
    <div class="space-y-4 text-xs">
        <!-- Tarjeta Resumen Financiero del Evento -->
        <div class="p-3.5 rounded-2xl bg-slate-100 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 grid grid-cols-1 sm:grid-cols-3 gap-3 text-center">
            <div class="p-2 rounded-xl bg-white dark:bg-slate-950 border border-slate-200/60 dark:border-slate-800">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">Total Cobrado</span>
                <span class="text-sm font-black text-emerald-600 dark:text-emerald-400">
                    +{{ number_format($splitEventTotal, 2, ',', '.') }} €
                </span>
            </div>
            <div class="p-2 rounded-xl bg-white dark:bg-slate-950 border border-slate-200/60 dark:border-slate-800">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">Costes Deducidos</span>
                <span class="text-sm font-black text-red-600 dark:text-red-400">
                    -{{ number_format($splitEventCosts, 2, ',', '.') }} €
                </span>
            </div>
            <div class="p-2 rounded-xl bg-white dark:bg-slate-950 border border-slate-200/60 dark:border-slate-800">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">Beneficio Neto</span>
                <span class="text-sm font-black text-purple-600 dark:text-purple-400">
                    {{ number_format($splitEventNet, 2, ',', '.') }} €
                </span>
            </div>
        </div>

        <!-- Atajos Rápidos de Reparto -->
        <div class="p-3 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 space-y-2">
            <div class="text-[11px] font-black text-indigo-900 dark:text-indigo-300 flex items-center justify-between">
                <span>⚡ Plantillas Rápidas de Reparto:</span>
                <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold">1 clic para auto-rellenar</span>
            </div>
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    wire:click="applySplitPreset('by_services')"
                    class="px-2.5 py-1 rounded-xl bg-white dark:bg-slate-900 border border-indigo-300 dark:border-indigo-700 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 font-bold text-[11px] text-indigo-800 dark:text-indigo-200 cursor-pointer transition shadow-2xs"
                    title="Asigna los importes según los servicios presupuestados (ej. DJ 400€ + Fotografía 600€)"
                >
                    📸 Por Servicios Presupuesto (DJ + Foto)
                </button>
                <button
                    type="button"
                    wire:click="applySplitPreset('staff_50_50')"
                    class="px-2.5 py-1 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 font-bold text-[11px] text-slate-700 dark:text-slate-300 cursor-pointer transition"
                >
                    👥 50% / 50% Personal del Evento
                </button>
                <button
                    type="button"
                    wire:click="applySplitPreset('global_partners')"
                    class="px-2.5 py-1 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 font-bold text-[11px] text-slate-700 dark:text-slate-300 cursor-pointer transition"
                >
                    🏛️ 3 Socios Habituales (33% / 33% / 34%)
                </button>
            </div>
        </div>

        <!-- Modo de Reparto -->
        <div class="flex items-center justify-between gap-2 p-2 rounded-xl bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
            <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300">Modo de Cálculo:</span>
            <div class="flex items-center gap-1">
                <button
                    type="button"
                    wire:click="$set('splitMode', 'custom_amounts')"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer {{ $splitMode === 'custom_amounts' ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-transparent text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800' }}"
                >
                    💶 Importes Fijos (€)
                </button>
                <button
                    type="button"
                    wire:click="$set('splitMode', 'percentages')"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer {{ $splitMode === 'percentages' ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-transparent text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800' }}"
                >
                    📊 Porcentajes (%)
                </button>
            </div>
        </div>

        <!-- Lista Editable de Personas / Socios -->
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-black uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Personas que cobran en este evento
                </span>
                <button
                    type="button"
                    wire:click="addPartnerToSplit"
                    class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline cursor-pointer flex items-center gap-1"
                >
                    <span>➕</span>
                    <span>Añadir Persona</span>
                </button>
            </div>

            <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                @forelse($splitPartners as $idx => $p)
                    <div class="p-3 rounded-xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-2 shadow-2xs">
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center">
                            <!-- Nombre -->
                            <div class="sm:col-span-4">
                                <label class="block text-[10px] font-bold text-slate-400 mb-0.5">Nombre / Socio</label>
                                <input
                                    type="text"
                                    wire:model="splitPartners.{{ $idx }}.name"
                                    placeholder="Ej: Fran / Miguel..."
                                    class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1.5 text-xs font-bold text-slate-900 dark:text-white"
                                >
                            </div>

                            <!-- Rol / Concepto -->
                            <div class="sm:col-span-3">
                                <label class="block text-[10px] font-bold text-slate-400 mb-0.5">Rol / Concepto</label>
                                <input
                                    type="text"
                                    wire:model="splitPartners.{{ $idx }}.role"
                                    placeholder="Ej: DJ / Fotografía..."
                                    class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1.5 text-xs text-slate-700 dark:text-slate-300"
                                >
                            </div>

                            <!-- Importe o Porcentaje -->
                            <div class="sm:col-span-3">
                                @if($splitMode === 'custom_amounts')
                                    <label class="block text-[10px] font-bold text-slate-400 mb-0.5">Importe Cobrado (€)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        wire:model="splitPartners.{{ $idx }}.amount"
                                        placeholder="0.00"
                                        class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1.5 text-xs font-black text-emerald-600 dark:text-emerald-400"
                                    >
                                @else
                                    <label class="block text-[10px] font-bold text-slate-400 mb-0.5">Porcentaje (%)</label>
                                    <input
                                        type="number"
                                        step="0.1"
                                        wire:model="splitPartners.{{ $idx }}.percentage"
                                        placeholder="0.0"
                                        class="w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1.5 text-xs font-black text-purple-600 dark:text-purple-400"
                                    >
                                @endif
                            </div>

                            <!-- Acciones / Estado -->
                            <div class="sm:col-span-2 flex items-center justify-end gap-1.5 pt-4 sm:pt-0">
                                <button
                                    type="button"
                                    wire:click="togglePartnerSettledInSplit({{ $idx }})"
                                    class="px-2 py-1 rounded-lg text-[10px] font-bold transition cursor-pointer {{ !empty($p['is_settled']) ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200' }}"
                                    title="Alternar si esta persona ya cobró su dinero de este evento"
                                >
                                    {{ !empty($p['is_settled']) ? '✅ Cobrado' : '⏳ Pendiente' }}
                                </button>
                                <button
                                    type="button"
                                    wire:click="removePartnerFromSplit({{ $idx }})"
                                    class="p-1 rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-950/40 text-xs cursor-pointer"
                                    title="Eliminar persona del reparto"
                                >
                                    🗑️
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-3 text-center text-slate-400 text-xs">
                        No hay personas añadidas al reparto de este evento.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Notas / Observaciones del Reparto -->
        <div>
            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Notas / Explicación del Reparto</label>
            <input
                type="text"
                wire:model="splitNotes"
                placeholder="Ej: Miguel cobró 400€ por DJ y Fran 600€ por Fotografía..."
                class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 p-2 text-xs text-slate-800 dark:text-slate-200"
            >
        </div>

        <!-- Botones de Acción -->
        <div class="flex flex-wrap items-center justify-between gap-2 pt-3 border-t border-slate-200 dark:border-slate-800">
            <button
                type="button"
                wire:click="resetEventSplitToDefault"
                class="px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold transition cursor-pointer"
                title="Quitar personalización y volver a la regla general de socios"
            >
                ↩️ Volver a Reparto General
            </button>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    wire:click="closeEventSplitModal"
                    class="px-3.5 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition cursor-pointer"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    wire:click="saveEventSplit"
                    class="px-4 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black transition cursor-pointer shadow-md flex items-center gap-1.5"
                >
                    <span>💾 Guardar Reparto</span>
                </button>
            </div>
        </div>
    </div>
</x-ui.modal>
