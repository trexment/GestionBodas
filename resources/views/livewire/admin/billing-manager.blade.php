<div class="space-y-6">
    <!-- CABECERA PRINCIPAL Y BOTONES DE EXPORTACIÓN -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="text-2xl">💶</span>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">
                        Panel de Facturación y Control de Ingresos
                    </h2>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Consulta de un vistazo qué eventos han pagado señal o liquidación, en qué fecha, por qué método de pago (Bizum, Transferencia, Efectivo, Tarjeta) y exporta el informe a <strong>Excel (.xlsx)</strong> o <strong>PDF</strong>.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Selector de Vista -->
                <div class="inline-flex bg-slate-100 dark:bg-slate-950 p-1 rounded-xl border border-slate-200 dark:border-slate-800">
                    <button
                        type="button"
                        wire:click="$set('viewMode', 'events')"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer flex items-center gap-1.5 {{ $viewMode === 'events' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                    >
                        <span>📋</span>
                        <span>Por Eventos ({{ count($eventsRows) }})</span>
                    </button>
                    <button
                        type="button"
                        wire:click="$set('viewMode', 'ledger')"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer flex items-center gap-1.5 {{ $viewMode === 'ledger' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                    >
                        <span>🧾</span>
                        <span>Libro de Cobros ({{ count($ledgerRows) }})</span>
                    </button>
                </div>

                <!-- Botón Exportar Excel (.xlsx) -->
                <a
                    href="{{ route('admin.billing.export.excel') }}{{ $exportQuery ? '?' . $exportQuery : '' }}"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold shadow-sm shadow-emerald-600/20 transition cursor-pointer"
                    title="Descargar libro Excel (.xlsx) con 3 pestañas: Control por Eventos, Libro de Cobros y Resumen"
                >
                    <span>📊</span>
                    <span>Exportar Excel (.xlsx)</span>
                </a>

                <!-- Botón Exportar Informe PDF -->
                <a
                    href="{{ route('admin.billing.export.pdf') }}{{ $exportQuery ? '?' . $exportQuery : '' }}"
                    target="_blank"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-extrabold shadow-sm shadow-rose-600/20 transition cursor-pointer"
                    title="Descargar informe en PDF listo para imprimir o enviar a gestoría"
                >
                    <span>📄</span>
                    <span>Exportar PDF</span>
                </a>
            </div>
        </div>
    </div>

    <!-- TARJETAS KPI GLOBALES -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. TOTAL INGRESADO / COBRADO -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-emerald-200 dark:border-emerald-900/60 p-4 shadow-xs relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
                    ✅ Total Ingresado / Cobrado
                </span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300">
                    {{ $kpis['events_with_payment_count'] }} eventos
                </span>
            </div>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-2">
                {{ number_format($kpis['total_collected'], 2, ',', '.') }} €
            </div>
            <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>Señales: <strong class="text-slate-800 dark:text-slate-200">{{ number_format($kpis['total_deposits_collected'], 2, ',', '.') }} €</strong></span>
                <span>Resto/Final: <strong class="text-slate-800 dark:text-slate-200">{{ number_format($kpis['total_finals_collected'], 2, ',', '.') }} €</strong></span>
            </div>
        </div>

        <!-- 2. PENDIENTE DE COBRO -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-amber-200 dark:border-amber-900/60 p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-amber-700 dark:text-amber-400">
                    ⏳ Pendiente de Cobrar
                </span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300">
                    {{ $kpis['events_partial_count'] + $kpis['events_unpaid_count'] }} pendientes
                </span>
            </div>
            <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-2">
                {{ number_format($kpis['total_pending'], 2, ',', '.') }} €
            </div>
            <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>Con señal: <strong class="text-slate-800 dark:text-slate-200">{{ $kpis['events_partial_count'] }}</strong></span>
                <span>Liquidados 100%: <strong class="text-emerald-600 dark:text-emerald-400">{{ $kpis['events_fully_paid_count'] }}</strong></span>
            </div>
        </div>

        <!-- 3. VOLUMEN TOTAL ACORDADO -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-indigo-200 dark:border-indigo-900/60 p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-indigo-700 dark:text-indigo-400">
                    📊 Volumen Total Eventos
                </span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-800 dark:text-indigo-300">
                    {{ $kpis['events_count'] }} listados
                </span>
            </div>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-2">
                {{ number_format($kpis['total_agreed'], 2, ',', '.') }} €
            </div>
            @php
                $globalPct = $kpis['total_agreed'] > 0 ? min(100, (int) round(($kpis['total_collected'] / $kpis['total_agreed']) * 100)) : 0;
            @endphp
            <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                <span>Porcentaje cobrado:</span>
                <strong class="text-indigo-600 dark:text-indigo-400">{{ $globalPct }}% del total</strong>
            </div>
        </div>

        <!-- 4. DESGLOSE FACTURAS (IVA) vs RECIBOS -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                    🧾 Facturas IVA vs Recibos
                </span>
                <span class="text-[10px] font-bold text-slate-400">Cobrado</span>
            </div>
            <div class="mt-2 space-y-1 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500 dark:text-slate-400">📄 Facturas ({{ $kpis['tax_summary']['invoices_count'] }}):</span>
                    <strong class="text-blue-600 dark:text-blue-400">{{ number_format($kpis['tax_summary']['invoices_total'], 2, ',', '.') }} €</strong>
                </div>
                <div class="flex items-center justify-between text-[11px] text-slate-400">
                    <span>Base: {{ number_format($kpis['tax_summary']['invoices_base'], 2, ',', '.') }} €</span>
                    <span>IVA: {{ number_format($kpis['tax_summary']['invoices_tax'], 2, ',', '.') }} €</span>
                </div>
                <div class="flex items-center justify-between pt-1 border-t border-slate-100 dark:border-slate-800">
                    <span class="text-slate-500 dark:text-slate-400">🧾 Recibos / Señales ({{ $kpis['tax_summary']['receipts_count'] }}):</span>
                    <strong class="text-emerald-600 dark:text-emerald-400">{{ number_format($kpis['tax_summary']['receipts_total'], 2, ',', '.') }} €</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- DESGLOSE INTERACTIVO POR MÉTODO DE PAGO (1 CLIC PARA FILTRAR) -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        @foreach(['bizum', 'transfer', 'cash', 'card'] as $mKey)
            @php
                $mData = $kpis['by_method'][$mKey];
                $isActiveMethod = ($paymentMethod === $mKey);
            @endphp
            <button
                type="button"
                wire:click="filterByMethod('{{ $mKey }}')"
                class="text-left p-3.5 rounded-2xl border transition cursor-pointer flex items-center justify-between {{ $isActiveMethod ? 'bg-indigo-600 text-white border-indigo-600 shadow-md' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 hover:border-indigo-400' }}"
                title="Pulsar para filtrar pagos por {{ $mData['label'] }}"
            >
                <div>
                    <div class="flex items-center gap-1.5 text-xs font-bold {{ $isActiveMethod ? 'text-indigo-100' : 'text-slate-500 dark:text-slate-400' }}">
                        <span class="text-base">{{ $mData['icon'] }}</span>
                        <span>{{ $mData['label'] }}</span>
                    </div>
                    <div class="text-lg font-black mt-1 {{ $isActiveMethod ? 'text-white' : 'text-slate-900 dark:text-white' }}">
                        {{ number_format($mData['amount'], 2, ',', '.') }} €
                    </div>
                </div>
                <span class="text-[11px] font-bold px-2 py-0.5 rounded-lg {{ $isActiveMethod ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">
                    {{ $mData['count'] }} cobros
                </span>
            </button>
        @endforeach
    </div>

    <!-- BARRA DE FILTROS AVANZADOS -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <!-- Buscador -->
            <div class="lg:col-span-2">
                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">
                    🔍 Buscar Evento, Cliente, DNI o Nº Factura
                </label>
                <input
                    type="text"
                    wire:model.live.debounce.250ms="search"
                    placeholder="Ej: Boda Javi, 45422025S, REC-2026..."
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-xs font-semibold text-slate-900 dark:text-white"
                >
            </div>

            <!-- Estado de Pago -->
            <div>
                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">
                    💶 Estado de Cobro
                </label>
                <select wire:model.live="paymentStatus" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-xs font-bold text-slate-900 dark:text-white">
                    <option value="with_payments">✅ Con Pagos Realizados (Señal o Total)</option>
                    <option value="all">📋 Todos los Eventos (Pagados y Pendientes)</option>
                    <option value="paid">💯 Pagados 100% (Liquidados)</option>
                    <option value="partial">🟢 Solo Señal / Pago Parcial (Falta resto)</option>
                    <option value="unpaid">⏳ Sin Pagos aún (0,00 € cobrado)</option>
                </select>
            </div>

            <!-- Método de Pago -->
            <div>
                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">
                    💳 Método de Pago
                </label>
                <select wire:model.live="paymentMethod" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-xs font-bold text-slate-900 dark:text-white">
                    <option value="all">Todos los métodos</option>
                    <option value="bizum">📱 Bizum</option>
                    <option value="transfer">🏦 Transferencia Bancaria</option>
                    <option value="cash">💵 Efectivo</option>
                    <option value="card">💳 Tarjeta / TPV</option>
                    <option value="other">💶 Otro / No especificado</option>
                </select>
            </div>

            <!-- Año y Base de Fecha -->
            <div>
                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">
                    📅 Año ({{ $dateBasis === 'payment_date' ? 'Fecha Cobro' : 'Fecha Evento' }})
                </label>
                <div class="flex gap-1.5">
                    <select wire:model.live="year" class="w-1/2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-2.5 py-2 text-xs font-bold text-slate-900 dark:text-white">
                        <option value="all">Todos</option>
                        @foreach($availableYears as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="quarter" class="w-1/2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-2 py-2 text-xs font-bold text-slate-900 dark:text-white" title="Trimestre fiscal">
                        <option value="all">Todo el año</option>
                        <option value="1">1T (Ene-Mar)</option>
                        <option value="2">2T (Abr-Jun)</option>
                        <option value="3">3T (Jul-Sep)</option>
                        <option value="4">4T (Oct-Dic)</option>
                    </select>
                </div>
            </div>

            <!-- Marca y Tipo Documento -->
            <div>
                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">
                    👑 Marca / Tipo Doc.
                </label>
                <div class="flex gap-1.5">
                    <select wire:model.live="brand" class="w-1/2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-2 py-2 text-xs font-bold text-slate-900 dark:text-white">
                        <option value="all">Todas</option>
                        <option value="nunez_and_son">👑 Núñez</option>
                        <option value="javnx">🎧 JAVNX</option>
                    </select>
                    <select wire:model.live="docType" class="w-1/2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-2 py-2 text-xs font-bold text-slate-900 dark:text-white">
                        <option value="all">Fact. + Rec.</option>
                        <option value="factura">📄 Facturas</option>
                        <option value="recibo">🧾 Recibos</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Segunda línea de filtros: Rango de fechas exacto, criterio de fecha y botón limpiar -->
        <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-100 dark:border-slate-800 text-xs">
            <div class="flex flex-wrap items-center gap-3">
                <div class="inline-flex items-center gap-1.5">
                    <span class="text-[11px] font-bold text-slate-500">Filtrar fechas por:</span>
                    <button
                        type="button"
                        wire:click="$set('dateBasis', 'payment_date')"
                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold cursor-pointer transition {{ $dateBasis === 'payment_date' ? 'bg-indigo-100 dark:bg-indigo-950 text-indigo-800 dark:text-indigo-300 border border-indigo-300' : 'text-slate-500 hover:text-slate-800' }}"
                    >
                        💳 Fecha de Cobro
                    </button>
                    <button
                        type="button"
                        wire:click="$set('dateBasis', 'event_date')"
                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold cursor-pointer transition {{ $dateBasis === 'event_date' ? 'bg-indigo-100 dark:bg-indigo-950 text-indigo-800 dark:text-indigo-300 border border-indigo-300' : 'text-slate-500 hover:text-slate-800' }}"
                    >
                        🎉 Fecha del Evento
                    </button>
                </div>

                <div class="flex items-center gap-1.5">
                    <span class="text-[11px] text-slate-400">Desde:</span>
                    <input type="date" wire:model.live="dateFrom" class="rounded-lg border border-slate-300 dark:border-slate-700 px-2 py-1 text-[11px]">
                    <span class="text-[11px] text-slate-400">Hasta:</span>
                    <input type="date" wire:model.live="dateTo" class="rounded-lg border border-slate-300 dark:border-slate-700 px-2 py-1 text-[11px]">
                </div>
            </div>

            <button
                type="button"
                wire:click="resetFilters"
                class="text-[11px] font-bold text-slate-500 hover:text-rose-600 transition cursor-pointer"
            >
                🔄 Restablecer filtros
            </button>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- VISTA 1: CONTROL DE PAGOS POR EVENTO (SEÑAL, RESTO, MÉTODO) -->
    <!-- ========================================================== -->
    @if($viewMode === 'events')
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2 bg-slate-50/60 dark:bg-slate-950/50">
                <h3 class="text-sm font-black text-slate-800 dark:text-white flex items-center gap-2">
                    <span>📋</span>
                    <span>Desglose de Ingresos por Evento ({{ count($eventsRows) }})</span>
                </h3>
                <span class="text-[11px] text-slate-500">
                    Haz clic en <strong>✏️</strong> en cualquier evento para anotar o editar su señal o cobro final sin salir de esta pantalla.
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[10px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-950">
                            <th class="py-3 px-4">Evento / Fecha</th>
                            <th class="py-3 px-3">Cliente / DNI</th>
                            <th class="py-3 px-3 text-right">Total Evento</th>
                            <th class="py-3 px-4">1º Pago: Señal / Reserva</th>
                            <th class="py-3 px-4">2º Pago: Liquidación / Resto</th>
                            <th class="py-3 px-3 text-right">Total Cobrado</th>
                            <th class="py-3 px-3 text-right">Pendiente</th>
                            <th class="py-3 px-4">Facturas / Recibos</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                        @forelse($eventsRows as $row)
                            @php
                                /** @var \App\Models\Event $ev */
                                $ev = $row['event'];
                                $fb = $row['fb'];
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                <!-- Evento y Fecha -->
                                <td class="py-3.5 px-4 align-top">
                                    <div class="flex items-start gap-2">
                                        <span class="text-base mt-0.5" title="{{ $ev->event_type_label }}">{{ $ev->event_type_icon }}</span>
                                        <div>
                                            <a href="{{ route('admin.events.show', $ev->id) }}" class="font-black text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition block leading-snug">
                                                {{ $ev->name }}
                                            </a>
                                            <div class="flex flex-wrap items-center gap-1.5 mt-1 text-[11px] text-slate-500">
                                                <span>📅 {{ $ev->event_date ? $ev->event_date->format('d/m/Y') : 'Sin fecha' }}</span>
                                                <span>&bull;</span>
                                                <span>{{ $ev->brand_icon }} {{ $ev->brand_label }}</span>
                                                <span class="px-1.5 py-0.2 rounded text-[10px] font-bold {{ $ev->status_badge_class }}">
                                                    {{ $ev->status_label }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Cliente / DNI -->
                                <td class="py-3.5 px-3 align-top">
                                    <div class="font-bold text-slate-800 dark:text-slate-200">
                                        {{ $ev->client?->name ?? 'Sin cliente' }}
                                    </div>
                                    @if($ev->client?->dni || $ev->partner_dni)
                                        <div class="text-[11px] font-mono text-slate-500 mt-0.5">
                                            🪪 {{ $ev->client?->dni ?: $ev->partner_dni }}
                                        </div>
                                    @endif
                                    @if($ev->client_contact_phone)
                                        <div class="text-[11px] text-slate-400">
                                            📞 {{ $ev->client_contact_phone }}
                                        </div>
                                    @endif
                                </td>

                                <!-- Total Acordado -->
                                <td class="py-3.5 px-3 text-right align-top font-black text-slate-900 dark:text-white whitespace-nowrap">
                                    @if($fb['quote_total'] > 0)
                                        {{ number_format($fb['quote_total'], 2, ',', '.') }} €
                                    @else
                                        <span class="text-slate-400 font-normal text-[11px]">Sin imp.</span>
                                    @endif
                                </td>

                                <!-- 1º Pago: Señal / Reserva (Importe, Fecha, Método) -->
                                <td class="py-3.5 px-4 align-top">
                                    @if($fb['deposit_paid'] && $fb['deposit_amount'] > 0)
                                        <div class="p-2 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/70 space-y-1">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="font-black text-emerald-700 dark:text-emerald-300 text-xs">
                                                    ✅ {{ number_format($fb['deposit_amount'], 2, ',', '.') }} €
                                                </span>
                                                <button
                                                    type="button"
                                                    wire:click="openQuickPayment({{ $ev->id }}, 'deposit')"
                                                    class="text-[11px] text-emerald-700 dark:text-emerald-400 hover:underline font-bold cursor-pointer"
                                                    title="Editar importe, fecha o método de pago de la señal"
                                                >
                                                    ✏️
                                                </button>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-1.5 text-[11px] text-slate-600 dark:text-slate-300">
                                                <span class="inline-flex items-center gap-1 font-bold bg-white dark:bg-slate-900 px-1.5 py-0.5 rounded border border-emerald-200 dark:border-emerald-800">
                                                    <span>{{ $fb['deposit_method_icon'] }}</span>
                                                    <span>{{ $fb['deposit_method_label'] }}</span>
                                                </span>
                                                @if($fb['deposit_date'])
                                                    <span class="text-slate-500">
                                                        📅 {{ \Carbon\Carbon::parse($fb['deposit_date'])->format('d/m/Y') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-[11px] text-slate-400 italic">
                                                Pendiente{{ $fb['signal_expected'] > 0 ? ' (' . number_format($fb['signal_expected'], 0, ',', '.') . '€)' : '' }}
                                            </span>
                                            <button
                                                type="button"
                                                wire:click="openQuickPayment({{ $ev->id }}, 'deposit')"
                                                class="px-2 py-1 rounded-lg bg-amber-50 dark:bg-amber-950/50 hover:bg-amber-100 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-800 text-[10px] font-bold transition cursor-pointer"
                                            >
                                                + Anotar Señal
                                            </button>
                                        </div>
                                    @endif
                                </td>

                                <!-- 2º Pago: Liquidación / Resto (Importe, Fecha, Método) -->
                                <td class="py-3.5 px-4 align-top">
                                    @if($fb['final_paid_amount'] > 0)
                                        <div class="p-2 rounded-xl bg-blue-50/70 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/70 space-y-1">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="font-black text-blue-700 dark:text-blue-300 text-xs">
                                                    ✅ {{ number_format($fb['final_paid_amount'], 2, ',', '.') }} €
                                                </span>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-1.5 text-[11px] text-slate-600 dark:text-slate-300">
                                                @if($fb['final_method_label'])
                                                    <span class="inline-flex items-center gap-1 font-bold bg-white dark:bg-slate-900 px-1.5 py-0.5 rounded border border-blue-200 dark:border-blue-800">
                                                        <span>{{ $fb['final_method_icon'] }}</span>
                                                        <span>{{ $fb['final_method_label'] }}</span>
                                                    </span>
                                                @endif
                                                @if($fb['final_paid_date'])
                                                    <span class="text-slate-500">
                                                        📅 {{ \Carbon\Carbon::parse($fb['final_paid_date'])->format('d/m/Y') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @elseif($fb['pending_amount'] > 0 && $fb['deposit_paid'])
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-[11px] text-slate-400">
                                                Restan {{ number_format($fb['pending_amount'], 2, ',', '.') }} €
                                            </span>
                                            <button
                                                type="button"
                                                wire:click="openQuickPayment({{ $ev->id }}, 'final')"
                                                class="px-2 py-1 rounded-lg bg-blue-50 dark:bg-blue-950/50 hover:bg-blue-100 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 text-[10px] font-bold transition cursor-pointer"
                                            >
                                                + Cobrar Resto
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-[11px] text-slate-400">—</span>
                                    @endif
                                </td>

                                <!-- Total Cobrado -->
                                <td class="py-3.5 px-3 text-right align-top whitespace-nowrap">
                                    <div class="font-black text-sm {{ $fb['total_paid'] > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }}">
                                        {{ number_format($fb['total_paid'], 2, ',', '.') }} €
                                    </div>
                                    <div class="w-20 ml-auto bg-slate-200 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden mt-1">
                                        <div class="bg-emerald-500 h-full rounded-full" style="width: {{ $fb['paid_percentage'] }}%"></div>
                                    </div>
                                    <span class="text-[10px] font-bold text-slate-400">{{ $fb['paid_percentage'] }}%</span>
                                </td>

                                <!-- Pendiente -->
                                <td class="py-3.5 px-3 text-right align-top whitespace-nowrap">
                                    @if($fb['pending_amount'] > 0)
                                        <span class="font-black text-amber-600 dark:text-amber-400">
                                            {{ number_format($fb['pending_amount'], 2, ',', '.') }} €
                                        </span>
                                    @elseif($fb['total_paid'] > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 text-[10px] font-black">
                                            ¡Pagado 100%!
                                        </span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>

                                <!-- Facturas y Recibos -->
                                <td class="py-3.5 px-4 align-top">
                                    <div class="flex flex-wrap gap-1.5">
                                        @forelse($ev->invoices->where('status', '!=', 'cancelled') as $inv)
                                            <a
                                                href="{{ route('admin.invoice.pdf', $inv->id) }}"
                                                target="_blank"
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-bold border transition {{ $inv->isReceipt() ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100' : 'bg-blue-50 dark:bg-blue-950/40 text-blue-800 dark:text-blue-300 border-blue-200 dark:border-blue-800 hover:bg-blue-100' }}"
                                                title="Descargar PDF {{ $inv->invoice_number }} ({{ number_format($inv->total, 2, ',', '.') }} €)"
                                            >
                                                <span>{{ $inv->isReceipt() ? '🧾' : '📄' }}</span>
                                                <span>{{ $inv->invoice_number }}</span>
                                                <span>⬇️</span>
                                            </a>
                                        @empty
                                            <a
                                                href="{{ route('admin.events.show', ['event' => $ev->id, 'tab' => 'invoices']) }}"
                                                class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline"
                                            >
                                                + Emitir Factura/Recibo
                                            </a>
                                        @endforelse
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-10 text-center text-slate-400 text-xs">
                                    No hay eventos que coincidan con los filtros seleccionados. Prueba cambiando el filtro de «Estado de Cobro» a «Todos los Eventos».
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <!-- ========================================================== -->
        <!-- VISTA 2: LIBRO CRONOLÓGICO DE COBROS, SEÑALES Y FACTURAS   -->
        <!-- ========================================================== -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2 bg-slate-50/60 dark:bg-slate-950/50">
                <h3 class="text-sm font-black text-slate-800 dark:text-white flex items-center gap-2">
                    <span>🧾</span>
                    <span>Libro Cronológico de Cobros, Señales, Recibos y Facturas ({{ count($ledgerRows) }})</span>
                </h3>
                <span class="text-[11px] text-slate-500">
                    Ordenado por fecha de cobro / emisión más reciente.
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[10px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-950">
                            <th class="py-3 px-4">Fecha Cobro</th>
                            <th class="py-3 px-3">Documento / Concepto</th>
                            <th class="py-3 px-4">Evento / Fecha Evento</th>
                            <th class="py-3 px-3">Cliente / DNI</th>
                            <th class="py-3 px-3">Método de Pago</th>
                            <th class="py-3 px-3 text-right">Base / IVA</th>
                            <th class="py-3 px-3 text-right">Importe Total</th>
                            <th class="py-3 px-3 text-center">Estado / PDF</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                        @forelse($ledgerRows as $item)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                <!-- Fecha de Cobro -->
                                <td class="py-3 px-4 font-bold text-slate-800 dark:text-slate-200 whitespace-nowrap">
                                    {{ $item['date'] ? \Carbon\Carbon::parse($item['date'])->format('d/m/Y') : '—' }}
                                </td>

                                <!-- Documento y Concepto -->
                                <td class="py-3 px-3">
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase {{ ($item['document_type'] ?? '') === 'factura' ? 'bg-blue-100 text-blue-800' : 'bg-emerald-100 text-emerald-800' }}">
                                            {{ ($item['document_type'] ?? '') === 'factura' ? '📄 FACTURA' : '🧾 RECIBO / SEÑAL' }}
                                        </span>
                                        <span class="font-mono font-black text-slate-900 dark:text-white">{{ $item['document_number'] }}</span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                        {{ $item['concept'] }}
                                        @if(!empty($item['notes']))
                                            <span class="text-slate-400"> — {{ \Illuminate\Support\Str::limit($item['notes'], 60) }}</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Evento -->
                                <td class="py-3 px-4">
                                    <a href="{{ route('admin.events.show', $item['event_id']) }}" class="font-bold text-slate-900 dark:text-white hover:text-indigo-600 transition block">
                                        {{ $item['event_type_icon'] }} {{ $item['event_name'] }}
                                    </a>
                                    <span class="text-[11px] text-slate-500">
                                        📅 Evento: {{ $item['event_date'] ? \Carbon\Carbon::parse($item['event_date'])->format('d/m/Y') : 'Sin fecha' }}
                                        &bull; {{ $item['brand_icon'] }} {{ $item['brand_label'] }}
                                    </span>
                                </td>

                                <!-- Cliente -->
                                <td class="py-3 px-3">
                                    <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $item['client_name'] }}</div>
                                    @if(!empty($item['client_dni']))
                                        <div class="text-[11px] font-mono text-slate-500">🪪 {{ $item['client_dni'] }}</div>
                                    @endif
                                </td>

                                <!-- Método de Pago -->
                                <td class="py-3 px-3">
                                    @if(!empty($item['invoice_id']))
                                        <select
                                            wire:change="updateInvoicePaymentMethod({{ $item['invoice_id'] }}, $event.target.value)"
                                            class="rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-2 py-1 text-[11px] font-bold text-slate-800 dark:text-slate-200"
                                        >
                                            <option value="bizum" @selected(($item['method'] ?? '') === 'bizum')>📱 Bizum</option>
                                            <option value="transfer" @selected(($item['method'] ?? '') === 'transfer')>🏦 Transferencia</option>
                                            <option value="cash" @selected(($item['method'] ?? '') === 'cash')>💵 Efectivo</option>
                                            <option value="card" @selected(($item['method'] ?? '') === 'card')>💳 Tarjeta / TPV</option>
                                            <option value="other" @selected(!in_array($item['method'] ?? '', ['bizum', 'transfer', 'cash', 'card']))>💶 Otro</option>
                                        </select>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-[11px] font-bold text-slate-800 dark:text-slate-200">
                                            <span>{{ $item['method_icon'] }}</span>
                                            <span>{{ $item['method_label'] }}</span>
                                        </span>
                                    @endif
                                </td>

                                <!-- Base / IVA -->
                                <td class="py-3 px-3 text-right text-[11px] text-slate-500 whitespace-nowrap">
                                    @if(($item['document_type'] ?? '') === 'factura')
                                        <div>Base: {{ number_format($item['base_amount'], 2, ',', '.') }} €</div>
                                        <div>IVA ({{ number_format($item['tax_rate'], 0) }}%): {{ number_format($item['tax_amount'], 2, ',', '.') }} €</div>
                                    @else
                                        <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Sin IVA</span>
                                    @endif
                                </td>

                                <!-- Importe Total -->
                                <td class="py-3 px-3 text-right font-black text-sm text-slate-900 dark:text-white whitespace-nowrap">
                                    {{ number_format($item['total'], 2, ',', '.') }} €
                                </td>

                                <!-- Estado y Descarga PDF -->
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        @if(!empty($item['invoice_id']))
                                            <button
                                                type="button"
                                                wire:click="toggleInvoicePaid({{ $item['invoice_id'] }})"
                                                class="px-2 py-1 rounded-lg text-[10px] font-bold border transition cursor-pointer {{ ($item['status'] ?? '') === 'paid' ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-amber-100 text-amber-800 border-amber-300' }}"
                                                title="Pulsar para alternar entre Cobrado y Pendiente"
                                            >
                                                {{ ($item['status'] ?? '') === 'paid' ? '✅ Cobrado' : '⏳ Pendiente' }}
                                            </button>
                                            <a
                                                href="{{ route('admin.invoice.pdf', $item['invoice_id']) }}"
                                                target="_blank"
                                                class="px-2 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-[10px] font-bold transition"
                                                title="Descargar PDF Oficial"
                                            >
                                                ⬇️ PDF
                                            </a>
                                        @else
                                            <span class="px-2 py-1 rounded-lg bg-emerald-100 text-emerald-800 text-[10px] font-bold">
                                                ✅ Cobrado
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-10 text-center text-slate-400 text-xs">
                                    No se encontraron cobros ni documentos con los filtros actuales.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- MODAL RÁPIDO PARA ANOTAR O EDITAR SEÑAL / COBRO FINAL -->
    @if($showQuickPaymentModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 backdrop-blur-xs p-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div>
                        <h4 class="text-base font-black text-slate-900 dark:text-white">
                            {{ $quickPaymentType === 'deposit' ? '💳 Registrar / Editar Señal de Reserva' : '💶 Registrar Cobro Final / Liquidación' }}
                        </h4>
                        <p class="text-xs text-indigo-600 dark:text-indigo-400 font-bold mt-0.5">
                            {{ $quickEventName }}
                        </p>
                    </div>
                    <button type="button" wire:click="closeQuickPaymentModal" class="text-slate-400 hover:text-slate-700 text-lg font-bold cursor-pointer">&times;</button>
                </div>

                <div class="space-y-3 text-xs">
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
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Método de Pago *</label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach(['bizum' => '📱 Bizum', 'transfer' => '🏦 Transferencia', 'cash' => '💵 Efectivo', 'card' => '💳 Tarjeta / TPV'] as $mVal => $mText)
                                <button
                                    type="button"
                                    wire:click="$set('quickMethod', '{{ $mVal }}')"
                                    class="p-2.5 rounded-xl border text-left font-bold transition cursor-pointer {{ $quickMethod === $mVal ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300' }}"
                                >
                                    {{ $mText }}
                                </button>
                            @endforeach
                        </div>
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

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" wire:click="closeQuickPaymentModal" class="px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 cursor-pointer">
                        Cancelar
                    </button>
                    <button type="button" wire:click="saveQuickPayment" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black shadow-sm cursor-pointer">
                        💾 Guardar Cobro
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
