<x-ui.modal 
    :show="$showPartnersModal" 
    onClose="closePartnersModal" 
    title="Socios y Porcentajes de Reparto" 
    subtitle="Define los socios y qué porcentaje de beneficio le corresponde a cada uno."
    icon="⚙️"
    maxWidth="md"
>
    <!-- PLANTILLAS RÁPIDAS -->
    <div class="space-y-1.5 p-3 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800">
        <span class="text-[10px] font-black uppercase tracking-wider text-indigo-700 dark:text-indigo-300 block">
            Plantillas Rápidas de Socios:
        </span>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <button
                type="button"
                wire:click.prevent="setPartnersPreset('3_partners')"
                class="px-2.5 py-1.5 rounded-xl bg-white dark:bg-slate-900 border border-indigo-300 dark:border-indigo-700 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-600 text-indigo-900 dark:text-indigo-200 text-[11px] font-black transition cursor-pointer text-left flex items-center gap-1.5 shadow-2xs"
            >
                <span>👥</span>
                <span>3 Socios (Fran, Miguel, Ezequiel)</span>
            </button>
            <button
                type="button"
                wire:click.prevent="setPartnersPreset('2_partners')"
                class="px-2.5 py-1.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 hover:bg-slate-800 hover:text-white dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-[11px] font-black transition cursor-pointer text-left flex items-center gap-1.5 shadow-2xs"
            >
                <span>👥</span>
                <span>2 Socios (Fran 50% / Miguel 50%)</span>
            </button>
        </div>
    </div>

    @php
        $mPartners = $this->partnersList;
        $sumPct = array_sum(array_map(fn($p) => (float)($p['percentage'] ?? 0), $mPartners));
        $isExact100 = abs($sumPct - 100.0) < 0.01;
    @endphp

    <div class="space-y-3 text-xs">
        <div class="space-y-2">
            @foreach($this->partnersList as $idx => $prtRow)
                <div wire:key="partner-modal-row-{{ $idx }}" class="flex items-center gap-2 bg-slate-50 dark:bg-slate-950/60 p-2.5 rounded-2xl border border-slate-200 dark:border-slate-800">
                    <div class="w-7 h-7 rounded-xl bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 flex items-center justify-center font-black shrink-0">
                        {{ $idx + 1 }}
                    </div>
                    <div class="flex-1">
                        <input
                            type="text"
                            wire:model="partnersList.{{ $idx }}.name"
                            placeholder="Nombre del socio..."
                            class="w-full rounded-xl border border-slate-300 dark:border-slate-700 p-2 font-bold text-xs"
                        >
                    </div>
                    <div class="w-24 flex items-center gap-1 shrink-0">
                        <input
                            type="number"
                            step="0.5"
                            wire:model.live="partnersList.{{ $idx }}.percentage"
                            placeholder="%"
                            class="w-full rounded-xl border border-slate-300 dark:border-slate-700 p-2 text-right font-black text-xs"
                        >
                        <span class="font-black text-slate-500">%</span>
                    </div>
                    @if(count($this->partnersList) > 1)
                        <button
                            type="button"
                            wire:click.prevent="removePartnerRow({{ $idx }})"
                            class="p-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950 rounded-xl transition cursor-pointer"
                            title="Eliminar socio"
                        >
                            🗑️
                        </button>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="flex items-center justify-between pt-1">
            <x-ui.button type="button" wire:click.prevent="addPartnerRow" variant="secondary" size="xs" icon="➕">
                Añadir otro socio
            </x-ui.button>

            <div class="text-right">
                <span class="text-[11px] text-slate-500">Suma total:</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-black {{ $isExact100 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' }}">
                    {{ $sumPct }}% {{ $isExact100 ? '✔️' : '⚠️' }}
                </span>
            </div>
        </div>

        @if(!$isExact100)
            <div class="p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-300 text-[11px] font-bold">
                ⚠️ Los porcentajes deben sumar exactamente 100% (actualmente suman {{ $sumPct }}%).
            </div>
        @endif

        @error('partnersList')
            <div class="text-rose-600 text-xs font-bold">{{ $message }}</div>
        @enderror
    </div>

    <x-slot:footer>
        <x-ui.button type="button" wire:click="closePartnersModal" variant="secondary" size="sm">
            Cancelar
        </x-ui.button>
        <x-ui.button type="button" wire:click="savePartnersSettings" variant="primary" size="sm" icon="💾">
            Guardar Reparto
        </x-ui.button>
    </x-slot:footer>
</x-ui.modal>
