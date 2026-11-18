<x-ui.modal 
    :show="$showQuickPaymentModal" 
    onClose="closeQuickPaymentModal" 
    title="{{ $quickPaymentType === 'deposit' ? 'Registrar / Editar Señal de Reserva' : 'Registrar Cobro Final / Liquidación' }}" 
    subtitle="{{ $quickEventName }}"
    icon="{{ $quickPaymentType === 'deposit' ? '💳' : '💶' }}"
    maxWidth="md"
>
    <div class="space-y-3.5 text-xs">
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Importe Cobrado (€) *</label>
                <input type="number" step="0.01" wire:model="quickAmount" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 p-2.5 font-black text-sm">
                @error('quickAmount') <span class="text-rose-600 text-[11px] font-bold">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Fecha de Cobro *</label>
                <input type="date" wire:model="quickDate" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 p-2.5 font-bold">
                @error('quickDate') <span class="text-rose-600 text-[11px] font-bold">{{ $message }}</span> @enderror
            </div>
        </div>

        <div>
            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Método de Pago *</label>
            <div class="grid grid-cols-2 gap-2">
                @foreach(['bizum' => '📱 Bizum', 'transfer' => '🏦 Transferencia', 'cash' => '💵 Efectivo', 'card' => '💳 Tarjeta / TPV'] as $mVal => $mText)
                    <button
                        type="button"
                        wire:click="$set('quickMethod', '{{ $mVal }}')"
                        class="p-2.5 rounded-xl border text-left font-bold transition cursor-pointer {{ $quickMethod === $mVal ? 'bg-indigo-600 text-white border-indigo-600 shadow-2xs' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}"
                    >
                        {{ $mText }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Quién cobró este dinero -->
        <div>
            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">¿Quién cobró este dinero? *</label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <button
                    type="button"
                    wire:click="$set('quickCollectedBy', 'Fran')"
                    class="p-2.5 rounded-xl border text-center font-bold text-xs transition cursor-pointer {{ $quickCollectedBy === 'Fran' ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}"
                >
                    👤 Fran
                </button>
                <button
                    type="button"
                    wire:click="$set('quickCollectedBy', 'Miguel')"
                    class="p-2.5 rounded-xl border text-center font-bold text-xs transition cursor-pointer {{ $quickCollectedBy === 'Miguel' ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}"
                >
                    👤 Miguel
                </button>
                <button
                    type="button"
                    wire:click="$set('quickCollectedBy', 'Ezequiel')"
                    class="p-2.5 rounded-xl border text-center font-bold text-xs transition cursor-pointer {{ $quickCollectedBy === 'Ezequiel' ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}"
                >
                    👤 Ezequiel
                </button>
                <button
                    type="button"
                    wire:click="$set('quickCollectedBy', 'Cuenta Conjunta / Fondo Común')"
                    class="p-2.5 rounded-xl border text-center font-bold text-xs transition cursor-pointer {{ in_array($quickCollectedBy, ['Cuenta Conjunta', 'Cuenta Conjunta / Fondo Común', 'Fondo Común', 'Empresa'], true) ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}"
                >
                    🏦 Fondo Común
                </button>
            </div>
            <p class="text-[10px] text-slate-400 mt-1">
                * Selecciona <strong>Fondo Común</strong> si el dinero se destinó a material, cenas de empresa o caja compartida (no se sumará a la cuenta personal de ningún socio).
            </p>
        </div>

        <div>
            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Notas / Observaciones (Opcional)</label>
            <input type="text" wire:model="quickNotes" placeholder="Ej: Pagado por el novio, Bizum recibido..." class="w-full rounded-xl border border-slate-300 dark:border-slate-700 p-2.5">
        </div>

        @if($quickPaymentType === 'deposit')
            <label class="flex items-center gap-2 pt-1 cursor-pointer">
                <input type="checkbox" wire:model="quickGenerateReceipt" class="rounded border-slate-300 text-indigo-600">
                <span class="text-slate-600 dark:text-slate-300 font-semibold">Generar / actualizar Recibo de pago automáticamente</span>
            </label>
        @endif
    </div>

    <x-slot:footer>
        <x-ui.button type="button" wire:click="closeQuickPaymentModal" variant="secondary" size="sm">
            Cancelar
        </x-ui.button>
        <x-ui.button type="button" wire:click="saveQuickPayment" variant="success" size="sm" icon="💾">
            Guardar Cobro
        </x-ui.button>
    </x-slot:footer>
</x-ui.modal>
