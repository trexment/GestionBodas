<x-ui.modal 
    :show="$showMaterialModal" 
    onClose="closeMaterialModal" 
    title="{{ $materialExpenseId ? 'Editar Compra de Material' : 'Registrar Compra de Material' }}" 
    subtitle="Gasto deducible antes del reparto. Se le reembolsa al socio que lo haya adelantado."
    icon="🛠️"
    maxWidth="lg"
>
    <div class="space-y-3.5 text-xs">
        <!-- Concepto -->
        <div>
            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Concepto / Descripción del Material *</label>
            <input
                type="text"
                wire:model="matConcept"
                placeholder="Ej: Cable XLR 10m, Bobinas papel fotomatón, Foco LED RGB..."
                class="w-full rounded-xl border border-slate-300 dark:border-slate-700 p-2.5 font-bold"
            >
            @error('matConcept') <span class="text-rose-600 text-[11px] font-bold">{{ $message }}</span> @enderror
        </div>

        <!-- Importe y Fecha -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Importe Total (€) *</label>
                <input
                    type="number"
                    step="0.01"
                    wire:model="matAmount"
                    placeholder="0,00"
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-700 p-2.5 font-black text-sm text-amber-600 dark:text-amber-400"
                >
                @error('matAmount') <span class="text-rose-600 text-[11px] font-bold">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Fecha de Compra *</label>
                <input
                    type="date"
                    wire:model="matPurchaseDate"
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-700 p-2.5 font-bold"
                >
                @error('matPurchaseDate') <span class="text-rose-600 text-[11px] font-bold">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- ¿Quién lo pagó? -->
        <div>
            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">¿Quién lo pagó de su bolsillo / cuenta? *</label>
            <div class="flex flex-wrap gap-1.5 mb-1.5">
                @foreach($partners as $prtOpt)
                    <button
                        type="button"
                        wire:click="$set('matPaidBy', '{{ $prtOpt['name'] }}')"
                        class="px-2.5 py-1 rounded-lg text-xs font-bold border cursor-pointer {{ $matPaidBy === $prtOpt['name'] ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700' }}"
                    >
                        👤 {{ $prtOpt['name'] }}
                    </button>
                @endforeach
                <button
                    type="button"
                    wire:click="$set('matPaidBy', 'Caja Común / Empresa')"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold border cursor-pointer {{ $matPaidBy === 'Caja Común / Empresa' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700' }}"
                >
                    🏦 Caja Común / Empresa
                </button>
            </div>
            <input
                type="text"
                wire:model="matPaidBy"
                placeholder="Nombre del socio o pagador..."
                class="w-full rounded-xl border border-slate-300 dark:border-slate-700 p-2 font-bold"
            >
            <p class="text-[10px] text-slate-400 mt-1">
                * Si lo pagó un socio, se le sumará a su liquidación final como reembolso íntegro. Si fue de Caja Común, no genera reembolso individual.
            </p>
            @error('matPaidBy') <span class="text-rose-600 text-[11px] font-bold">{{ $message }}</span> @enderror
        </div>

        <!-- Categoría y Método de Pago -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Categoría de Gasto *</label>
                <select wire:model="matCategory" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 p-2.5 font-bold">
                    <option value="sound_lighting">🔊 Sonido e Iluminación</option>
                    <option value="fx">🎆 Efectos Especiales (FX) / Fuego Frío</option>
                    <option value="photobooth_360">📸 Fotomatón y Plataforma 360</option>
                    <option value="it_cables">🔌 Informática y Cableado</option>
                    <option value="fuel_transport">⛽ Combustible y Transporte</option>
                    <option value="consumables">📦 Consumibles y Atrezzo</option>
                    <option value="general">🛠️ Herramientas y General</option>
                </select>
            </div>
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Método de Pago *</label>
                <select wire:model="matPaymentMethod" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 p-2.5 font-bold">
                    <option value="card">💳 Tarjeta / TPV</option>
                    <option value="transfer">🏦 Transferencia Bancaria</option>
                    <option value="bizum">📱 Bizum</option>
                    <option value="cash">💵 Efectivo</option>
                    <option value="other">📝 Otro</option>
                </select>
            </div>
        </div>

        <!-- Proveedor y Nº Factura -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Comercio / Proveedor</label>
                <input
                    type="text"
                    wire:model="matSupplier"
                    placeholder="Ej: Amazon, Thomann, Leroy Merlin..."
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-700 p-2"
                >
            </div>
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Nº Factura / Ticket</label>
                <input
                    type="text"
                    wire:model="matInvoiceNumber"
                    placeholder="Ej: FAC-2026-9812..."
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-700 p-2"
                >
            </div>
        </div>

        <!-- Evento o Temporada -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Evento Asociado (Opcional)</label>
                <select wire:model="matEventId" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 p-2 font-semibold">
                    <option value="">-- Gasto General de Temporada --</option>
                    @foreach($allEventsList as $evOpt)
                        <option value="{{ $evOpt->id }}">
                            {{ $evOpt->name }} ({{ $evOpt->event_date ? $evOpt->event_date->format('d/m/Y') : '' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Temporada (Año)</label>
                <input
                    type="text"
                    wire:model="matSeason"
                    placeholder="Ej: 2026, 2027..."
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-700 p-2 font-bold"
                >
            </div>
        </div>

        <!-- Notas -->
        <div>
            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Notas / Observaciones</label>
            <input
                type="text"
                wire:model="matNotes"
                placeholder="Ej: Repuesto urgente para la boda de Navarrete..."
                class="w-full rounded-xl border border-slate-300 dark:border-slate-700 p-2"
            >
        </div>
    </div>

    <x-slot:footer>
        <x-ui.button type="button" wire:click="closeMaterialModal" variant="secondary" size="sm">
            Cancelar
        </x-ui.button>
        <x-ui.button type="button" wire:click="saveMaterialExpense" variant="amber" size="sm" icon="💾">
            Guardar Compra de Material
        </x-ui.button>
    </x-slot:footer>
</x-ui.modal>
