<x-ui.modal 
    :show="$showProviderModal" 
    onClose="closeProviderModal" 
    title="Reserva y Pagos a Proveedor (Fotomatón / 360º / Artístamente)" 
    subtitle="Anota los costes de proveedores y quién lo pagó de su bolsillo para descontarlo y reembolsarlo."
    icon="📸"
    maxWidth="2xl"
>
    <div class="space-y-4 text-xs">
        <!-- Plantillas rápidas -->
        <div class="p-3.5 rounded-2xl bg-purple-50/70 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800/80 space-y-2">
            <div class="flex items-center justify-between text-[11px] font-black text-purple-900 dark:text-purple-300">
                <span class="flex items-center gap-1.5">
                    <span>⚡</span>
                    <span>Plantillas Rápidas de Gasto / Proveedor:</span>
                </span>
                <button type="button" wire:click="resetProviderModalForNewBooking" class="text-[11px] text-purple-700 dark:text-purple-400 font-bold hover:underline cursor-pointer flex items-center gap-1">
                    <span>➕</span>
                    <span>Nuevo Gasto Adicional</span>
                </button>
            </div>
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    wire:click="applyProviderPreset('artistamente')"
                    class="px-2.5 py-1 rounded-xl border text-[11px] font-bold transition cursor-pointer {{ $provKey === 'artistamente' ? 'bg-purple-600 text-white border-purple-600 shadow-sm' : 'bg-white dark:bg-slate-900 border-purple-300 text-purple-800 dark:text-purple-300 hover:bg-purple-100 dark:hover:bg-purple-900/50' }}"
                >
                    🎭 Artístamente (Alta / Gestión)
                </button>
                <button
                    type="button"
                    wire:click="applyProviderPreset('photobooth')"
                    class="px-2.5 py-1 rounded-xl border text-[11px] font-bold transition cursor-pointer {{ $provKey === 'logrono' ? 'bg-purple-600 text-white border-purple-600 shadow-sm' : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}"
                >
                    🏢 Fotomatón Logroño
                </button>
                <button
                    type="button"
                    wire:click="applyProviderPreset('photobooth_vitoria')"
                    class="px-2.5 py-1 rounded-xl border text-[11px] font-bold transition cursor-pointer {{ $provKey === 'vitoria' ? 'bg-purple-600 text-white border-purple-600 shadow-sm' : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}"
                >
                    🧀 Fotomatón Vitoria (Cheese)
                </button>
            </div>
        </div>

        <!-- Selección de Evento -->
        <div>
            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Evento *</label>
            <select wire:model.live="provEventId" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 p-2.5 font-bold text-slate-900 dark:text-white">
                @foreach($allEventsList as $evOpt)
                    <option value="{{ $evOpt->id }}">
                        {{ $evOpt->name }} ({{ $evOpt->event_date ? $evOpt->event_date->format('d/m/Y') : 'Sin fecha' }})
                    </option>
                @endforeach
            </select>
        </div>

        @if($provDetectedQuotePrice > 0 || !empty($provDetectedItemsText))
            <div class="p-3.5 rounded-2xl bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <div class="text-[11px] font-black text-indigo-800 dark:text-indigo-300">
                        💶 Detectado en el presupuesto del cliente: {{ number_format((float)$provDetectedQuotePrice, 2, ',', '.') }} €
                    </div>
                    @if(!empty($provDetectedItemsText))
                        <div class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold mt-0.5">
                            {{ $provDetectedItemsText }}
                        </div>
                    @endif
                </div>
                @if($provDetectedQuotePrice > 0 && abs((float)$provTotalCost - (float)$provDetectedQuotePrice) > 0.05)
                    <button
                        type="button"
                        wire:click="syncProviderFromQuote"
                        class="px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-bold transition cursor-pointer shrink-0"
                    >
                        🔄 Usar {{ number_format((float)$provDetectedQuotePrice, 2, ',', '.') }} €
                    </button>
                @endif
            </div>
        @endif

        <!-- Proveedor y Gasto Total -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 items-start">
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Empresa Proveedora *</label>
                <input type="text" wire:model="provName" placeholder="Ej: Fotomatón Logroño / Artístamente..." class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 p-2.5 font-bold text-slate-900 dark:text-white">
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Total a Pagar / Factura Proveedor (€) *</label>
                <input type="number" step="0.01" wire:model.live="provTotalCost" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 p-2.5 font-black text-sm text-purple-700 dark:text-purple-300">
            </div>
        </div>

        <!-- Servicio Contratado -->
        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="block font-bold text-slate-700 dark:text-slate-300">Servicio Contratado *</label>
                <span class="text-[10px] text-slate-400">Atajos rápidos:</span>
            </div>
            <div class="flex flex-wrap gap-1.5 mb-2">
                <button type="button" wire:click="$set('provService', 'Fotomatón')" class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-[11px] font-bold hover:bg-purple-100 dark:hover:bg-purple-900/40 text-slate-700 dark:text-slate-300 transition cursor-pointer">📸 Fotomatón</button>
                <button type="button" wire:click="$set('provService', 'Plataforma 360º')" class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-[11px] font-bold hover:bg-purple-100 dark:hover:bg-purple-900/40 text-slate-700 dark:text-slate-300 transition cursor-pointer">🎥 Plataforma 360º</button>
                <button type="button" wire:click="$set('provService', 'Pack Dúo: Fotomatón + Plataforma 360º')" class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-[11px] font-bold hover:bg-purple-100 dark:hover:bg-purple-900/40 text-slate-700 dark:text-slate-300 transition cursor-pointer">✨ Pack Dúo (Fotomatón + 360º)</button>
                <button type="button" wire:click="$set('provService', 'Alta en plataforma / Gestión de artistas')" class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-[11px] font-bold hover:bg-purple-100 dark:hover:bg-purple-900/40 text-slate-700 dark:text-slate-300 transition cursor-pointer">🎭 Artístamente</button>
            </div>
            <input type="text" wire:model="provService" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 p-2.5 font-semibold text-slate-900 dark:text-white">
        </div>

        <!-- Quién pagó a este proveedor de su propio bolsillo -->
        <div>
            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">¿Quién pagó este proveedor de su propio bolsillo? *</label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <button
                    type="button"
                    wire:click="$set('provPaidBy', 'Fran')"
                    class="p-2.5 rounded-xl border text-center font-bold text-xs transition cursor-pointer {{ $provPaidBy === 'Fran' ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}"
                >
                    👤 Fran
                </button>
                <button
                    type="button"
                    wire:click="$set('provPaidBy', 'Miguel')"
                    class="p-2.5 rounded-xl border text-center font-bold text-xs transition cursor-pointer {{ $provPaidBy === 'Miguel' ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}"
                >
                    👤 Miguel
                </button>
                <button
                    type="button"
                    wire:click="$set('provPaidBy', 'Ezequiel')"
                    class="p-2.5 rounded-xl border text-center font-bold text-xs transition cursor-pointer {{ $provPaidBy === 'Ezequiel' ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}"
                >
                    👤 Ezequiel
                </button>
                <button
                    type="button"
                    wire:click="$set('provPaidBy', 'Caja Común / Empresa')"
                    class="p-2.5 rounded-xl border text-center font-bold text-xs transition cursor-pointer {{ $provPaidBy === 'Caja Común / Empresa' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}"
                >
                    🏦 Caja Común
                </button>
            </div>
            <p class="text-[10px] text-slate-400 mt-1">
                * Si es un coste asumido (ej. Artístamente), descuenta de su beneficio pactado. Si es un adelanto (ej. fotomatón), la empresa se lo devuelve íntegro.
            </p>
        </div>

        <!-- Tratamiento del Coste en el Reparto de Socios -->
        @if($provPaidBy !== 'Caja Común / Empresa')
            <div class="p-3.5 rounded-2xl bg-purple-50/60 dark:bg-purple-950/30 border border-purple-200 dark:border-purple-800/80 space-y-2">
                <label class="block font-bold text-slate-800 dark:text-slate-200 text-xs">
                    ¿Cómo computar este gasto en el reparto de socios?
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <button
                        type="button"
                        wire:click="$set('provReimbursementType', 'assumed')"
                        class="p-2.5 rounded-xl border text-left transition cursor-pointer {{ $provReimbursementType === 'assumed' ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}"
                    >
                        <div class="font-bold text-xs flex items-center gap-1.5">
                            <span>👤</span>
                            <span>Coste Asumido por {{ $provPaidBy }}</span>
                        </div>
                        <div class="text-[10px] mt-0.5 {{ $provReimbursementType === 'assumed' ? 'text-indigo-100' : 'text-slate-400' }}">
                            Descuenta de su caché pactado de este evento (ej: Artístamente). Nadie se lo devuelve.
                        </div>
                    </button>

                    <button
                        type="button"
                        wire:click="$set('provReimbursementType', 'reimbursable')"
                        class="p-2.5 rounded-xl border text-left transition cursor-pointer {{ $provReimbursementType === 'reimbursable' ? 'bg-amber-600 text-white border-amber-600 shadow-sm' : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}"
                    >
                        <div class="font-bold text-xs flex items-center gap-1.5">
                            <span>⏳</span>
                            <span>Adelanto Reembolsable</span>
                        </div>
                        <div class="text-[10px] mt-0.5 {{ $provReimbursementType === 'reimbursable' ? 'text-amber-100' : 'text-slate-400' }}">
                            La empresa se lo debe y se le devolverá de la cuenta común.
                        </div>
                    </button>
                </div>
            </div>
        @endif

        <!-- BLOQUE 1: SEÑAL DE RESERVA A LA EMPRESA -->
        <div class="p-3.5 rounded-2xl border {{ $provDepositPaid ? 'bg-emerald-50/60 dark:bg-emerald-950/30 border-emerald-300 dark:border-emerald-800' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800' }} space-y-2.5">
            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 font-black text-slate-900 dark:text-white cursor-pointer">
                    <input type="checkbox" wire:model.live="provDepositPaid" class="rounded border-slate-300 text-emerald-600">
                    <span>1º PAGO: Señal de Reserva Pagada a la Empresa</span>
                </label>
                <div class="flex gap-1">
                    <button type="button" wire:click="$set('provDepositAmount', 100)" class="px-2 py-0.5 rounded bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[10px] font-bold cursor-pointer">100 €</button>
                    <button type="button" wire:click="$set('provDepositAmount', 200)" class="px-2 py-0.5 rounded bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[10px] font-bold cursor-pointer">200 €</button>
                </div>
            </div>

            @if($provDepositPaid)
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Importe Señal (€)</label>
                        <input type="number" step="0.01" wire:model.live="provDepositAmount" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 p-2 font-black text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Fecha de Pago</label>
                        <input type="date" wire:model="provDepositDate" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 p-2 font-bold text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Método de Pago</label>
                        <select wire:model="provDepositMethod" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 p-2 font-bold text-slate-900 dark:text-white">
                            <option value="bizum">📱 Bizum</option>
                            <option value="transfer">🏦 Transferencia</option>
                            <option value="cash">💵 Efectivo</option>
                            <option value="card">💳 Tarjeta</option>
                        </select>
                    </div>
                </div>
            @endif
        </div>

        <!-- BLOQUE 2: RESTO / LIQUIDACIÓN FINAL A LA EMPRESA -->
        @php
            $calcPendingToProv = max(0, (float)$provTotalCost - ($provDepositPaid ? (float)$provDepositAmount : 0) - ($provFinalPaid ? (float)$provFinalAmount : 0));
        @endphp
        <div class="p-3.5 rounded-2xl border {{ $provFinalPaid ? 'bg-blue-50/60 dark:bg-blue-950/30 border-blue-300 dark:border-blue-800' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800' }} space-y-2.5">
            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 font-black text-slate-900 dark:text-white cursor-pointer">
                    <input type="checkbox" wire:model.live="provFinalPaid" class="rounded border-slate-300 text-blue-600">
                    <span>2º PAGO: Liquidación / Resto Pagado a la Empresa</span>
                </label>
                <button
                    type="button"
                    wire:click="fillProviderRemaining"
                    class="px-2.5 py-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-bold transition cursor-pointer"
                >
                    ⚡ Liquidar Resto ({{ number_format(max(0, (float)$provTotalCost - ($provDepositPaid ? (float)$provDepositAmount : 0)), 2, ',', '.') }} €)
                </button>
            </div>

            @if($provFinalPaid)
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Importe Resto (€)</label>
                        <input type="number" step="0.01" wire:model.live="provFinalAmount" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 p-2 font-black text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Fecha Pago Final</label>
                        <input type="date" wire:model="provFinalDate" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 p-2 font-bold text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Método de Pago</label>
                        <select wire:model="provFinalMethod" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 p-2 font-bold text-slate-900 dark:text-white">
                            <option value="transfer">🏦 Transferencia</option>
                            <option value="bizum">📱 Bizum</option>
                            <option value="cash">💵 Efectivo</option>
                            <option value="card">💳 Tarjeta</option>
                        </select>
                    </div>
                </div>
            @else
                <div class="text-[11px] text-amber-700 dark:text-amber-300 font-bold">
                    ⏳ Queda pendiente de pagarle a la empresa: <strong>{{ number_format($calcPendingToProv, 2, ',', '.') }} €</strong>
                </div>
            @endif
        </div>

        <!-- BLOQUE DE LIQUIDACIÓN Y DEVOLUCIÓN DE ARTÍSTAMENTE / COOPERATIVA -->
        <div class="p-3.5 rounded-2xl border transition {{ $provHasRefund ? 'bg-amber-50/70 dark:bg-amber-950/30 border-amber-300 dark:border-amber-700' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800' }} space-y-3">
            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 font-black text-slate-900 dark:text-white cursor-pointer">
                    <input type="checkbox" wire:model.live="provHasRefund" class="rounded border-slate-300 text-amber-600">
                    <span>🎭 ¿Hay Liquidación / Devolución de Cooperativa? (Artístamente)</span>
                </label>
                @if($provHasRefund)
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-200 dark:bg-amber-900 text-amber-900 dark:text-amber-200">
                        Se recupera dinero
                    </span>
                @endif
            </div>

            @if($provHasRefund)
                <div class="space-y-3 pt-1 border-t border-amber-200 dark:border-amber-800/80">
                    <p class="text-[11px] text-amber-800 dark:text-amber-300">
                        Al facturar con Artístamente pagamos la factura (ej. {{ number_format((float)$provTotalCost, 2, ',', '.') }} €), y tras deducir gestión y S.S., la cooperativa nos devuelve una cantidad que <strong>se añade al dinero a repartir</strong>.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-black text-slate-800 dark:text-slate-200 mb-1">
                                💰 Importe Devuelto / Cobrado (€) *
                            </label>
                            <input type="number" step="0.01" wire:model.live="provRefundAmount" class="w-full rounded-xl border border-amber-300 dark:border-amber-700 bg-white dark:bg-slate-900 p-2.5 font-black text-base text-amber-600 dark:text-amber-400">
                            <span class="text-[10px] text-slate-500">Ej: 136,44 € devuelto según liquidación</span>
                        </div>

                        <div>
                            <label class="block text-[11px] font-black text-slate-800 dark:text-slate-200 mb-1">
                                🏦 ¿A qué cuenta bancaria ingresó Artístamente la devolución? *
                            </label>
                            <select wire:model.live="provRefundRecipient" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-2.5 font-bold text-xs">
                                <option value="50_50">👥 50% Fran / 50% Miguel (Mitad directamente a cada uno)</option>
                                <option value="fran">👤 Ingresado íntegro en banco de Fran (se divide en el arqueo final)</option>
                                <option value="miguel">👤 Ingresado íntegro en banco de Miguel (se divide en el arqueo final)</option>
                                <option value="ezequiel">👤 Ingresado íntegro en banco de Ezequiel (se divide en el arqueo final)</option>
                                <option value="joint">🏦 Cuenta Conjunta / Fondo Común</option>
                                <option value="custom">⚙️ Reparto Personalizado</option>
                            </select>
                            <span class="text-[10px] text-slate-500">El dinero devuelto se divide siempre al 50% entre ambos socios</span>
                        </div>
                    </div>

                    @if($provRefundRecipient === 'custom')
                        <div class="grid grid-cols-2 gap-3 p-2.5 rounded-xl bg-amber-100/60 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300">Ingresado a Fran (€)</label>
                                <input type="number" step="0.01" wire:model.live="provRefundFranAmount" class="w-full rounded-lg border border-slate-300 p-1.5 font-bold text-xs">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300">Ingresado a Miguel (€)</label>
                                <input type="number" step="0.01" wire:model.live="provRefundMiguelAmount" class="w-full rounded-lg border border-slate-300 p-1.5 font-bold text-xs">
                            </div>
                        </div>
                    @else
                        <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-amber-200 dark:border-amber-800/60 text-xs space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-600 dark:text-slate-400 font-bold">Reparto de la devolución entre socios:</span>
                                <span class="px-2 py-0.5 rounded bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-200 font-black text-[10px]">
                                    Total Devuelto: {{ number_format((float)$provRefundAmount, 2, ',', '.') }} €
                                </span>
                            </div>
                            <div class="font-bold text-slate-900 dark:text-white text-xs pt-0.5">
                                @if($provRefundRecipient === '50_50')
                                    Fran: <strong class="text-amber-600">{{ number_format((float)$provRefundFranAmount, 2, ',', '.') }} €</strong> &bull; Miguel: <strong class="text-amber-600">{{ number_format((float)$provRefundMiguelAmount, 2, ',', '.') }} €</strong> (50% exacto para cada uno)
                                @elseif($provRefundRecipient === 'fran')
                                    Artístamente ingresó los <strong class="text-amber-600">{{ number_format((float)$provRefundAmount, 2, ',', '.') }} €</strong> en la cuenta de Fran &rarr; En el reparto le corresponden <strong class="text-emerald-600">{{ number_format((float)$provRefundAmount / 2, 2, ',', '.') }} €</strong> a Fran y <strong class="text-blue-600">{{ number_format((float)$provRefundAmount / 2, 2, ',', '.') }} €</strong> a Miguel (Fran transfiere su mitad a Miguel en el arqueo).
                                @elseif($provRefundRecipient === 'miguel')
                                    Artístamente ingresó los <strong class="text-amber-600">{{ number_format((float)$provRefundAmount, 2, ',', '.') }} €</strong> en la cuenta de Miguel &rarr; En el reparto le corresponden <strong class="text-emerald-600">{{ number_format((float)$provRefundAmount / 2, 2, ',', '.') }} €</strong> a Miguel y <strong class="text-blue-600">{{ number_format((float)$provRefundAmount / 2, 2, ',', '.') }} €</strong> a Fran (Miguel transfiere su mitad a Fran en el arqueo).
                                @elseif($provRefundRecipient === 'ezequiel')
                                    Artístamente ingresó los <strong class="text-amber-600">{{ number_format((float)$provRefundAmount, 2, ',', '.') }} €</strong> en la cuenta de Ezequiel &rarr; Se compensa equitativamente con la cuota de beneficio de cada socio en el arqueo general.
                                @elseif($provRefundRecipient === 'joint')
                                    Ingresado en Cuenta Conjunta / Fondo Común (<strong class="text-emerald-600">{{ number_format((float)$provRefundAmount, 2, ',', '.') }} €</strong>) &rarr; Beneficio compartido para la caja común de la empresa.
                                @endif
                            </div>
                        </div>
                    @endif

                    @php
                        $netEventCost = max(0.0, round((float)$provTotalCost - (float)$provRefundAmount, 2));
                    @endphp
                    <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-800 flex items-center justify-between text-xs">
                        <div>
                            <span class="font-black text-emerald-800 dark:text-emerald-300 block">
                                Coste Neto Real Asumido por el Evento:
                            </span>
                            <span class="text-[10px] text-emerald-700/80 dark:text-emerald-400">
                                Factura ({{ number_format((float)$provTotalCost, 2, ',', '.') }} €) - Devolución ({{ number_format((float)$provRefundAmount, 2, ',', '.') }} €)
                            </span>
                        </div>
                        <div class="text-lg font-black text-emerald-700 dark:text-emerald-300">
                            {{ number_format($netEventCost, 2, ',', '.') }} €
                        </div>
                    </div>

                    <details class="text-[11px] text-slate-600 dark:text-slate-400 pt-1">
                        <summary class="font-bold text-amber-700 dark:text-amber-400 cursor-pointer hover:underline">
                            📄 Ver desglose de retenciones y documentos de la liquidación (Opcional)
                        </summary>
                        <div class="mt-2.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-2.5">
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <div>
                                    <label class="block text-[10px] text-slate-500 font-bold">Gestión (€)</label>
                                    <input type="number" step="0.01" wire:model.live="provManagementFee" class="w-full rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-1 font-semibold text-xs">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 font-bold">Seg. Social (€)</label>
                                    <input type="number" step="0.01" wire:model.live="provSocialSecurityFee" class="w-full rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-1 font-semibold text-xs">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 font-bold">Retención IRPF (€)</label>
                                    <input type="number" step="0.01" wire:model.live="provTaxFee" class="w-full rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-1 font-semibold text-xs">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 font-bold">Cuota Alta (€)</label>
                                    <input type="number" step="0.01" wire:model.live="provRegistrationFee" class="w-full rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-1 font-semibold text-xs">
                                </div>
                            </div>
                            <div class="flex items-center justify-between pt-1">
                                <button type="button" wire:click="calculateRefundFromFees" class="px-2 py-1 rounded bg-amber-600 text-white text-[10px] font-bold hover:bg-amber-700 cursor-pointer transition">
                                    ⚡ Auto-calcular importe devuelto
                                </button>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1 border-t border-slate-200 dark:border-slate-800">
                                <div>
                                    <label class="block text-[10px] text-slate-500 font-bold">Nº Liquidación Artístamente</label>
                                    <input type="text" wire:model="provSettlementNumber" placeholder="Ej: EAM-322971" class="w-full rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-1 font-semibold text-xs">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 font-bold">Nº Factura / Presupuesto</label>
                                    <input type="text" wire:model="provInvoiceNumber" placeholder="Ej: PEV-322971-1" class="w-full rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-1 font-semibold text-xs">
                                </div>
                            </div>
                        </div>
                    </details>
                </div>
            @endif
        </div>

        <div>
            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Notas / Referencia Reserva (Opcional)</label>
            <input type="text" wire:model="provNotes" placeholder="Ej: Reservado con Águeda por WhatsApp, montaje a las 20:00..." class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 p-2.5 text-slate-900 dark:text-white">
        </div>
    </div>

    <x-slot:footer>
        <x-ui.button type="button" wire:click="closeProviderModal" variant="secondary" size="sm">
            Cancelar
        </x-ui.button>
        <x-ui.button type="button" wire:click="saveProviderBooking" variant="primary" size="sm" icon="💾">
            Guardar Reserva y Pagos
        </x-ui.button>
    </x-slot:footer>
</x-ui.modal>
