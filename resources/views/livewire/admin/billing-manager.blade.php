<div class="space-y-6">
    <!-- CABECERA PRINCIPAL Y BARRA DE NAVEGACIÓN EN 4 PESTAÑAS MAESTRAS -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="text-2xl">💶</span>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">
                        Gestión Financiera & Cuentas
                    </h2>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Control ordenado de cobros a clientes, liquidaciones entre socios, gastos de proveedores y arqueo de caja y banco.
                </p>
            </div>

            <!-- Botones de Exportación -->
            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('admin.billing.export.excel') }}{{ $exportQuery ? '?' . $exportQuery : '' }}"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black shadow-xs transition cursor-pointer"
                    title="Descargar libro Excel (.xlsx) con hojas ordenadas"
                >
                    <span>📊</span>
                    <span>Exportar Excel</span>
                </a>
                <a
                    href="{{ route('admin.billing.export.pdf') }}{{ $exportQuery ? '?' . $exportQuery : '' }}"
                    target="_blank"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-black shadow-xs transition cursor-pointer"
                    title="Descargar informe en PDF oficial"
                >
                    <span>📄</span>
                    <span>Exportar PDF</span>
                </a>
            </div>
        </div>

        <!-- 4 PESTAÑAS MAESTRAS FINANCIERAS -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
            <!-- 1. Cobros Clientes -->
            <button
                type="button"
                wire:click="setViewMode('events')"
                class="flex items-center justify-center gap-2 py-3 px-3 rounded-2xl text-xs font-black transition cursor-pointer {{ in_array($viewMode, ['events', 'ledger'], true) ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/25 ring-2 ring-indigo-400/40' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}"
            >
                <span class="text-base">💳</span>
                <span>1. Cobros Clientes</span>
                <span class="px-1.5 py-0.5 rounded-md text-[10px] {{ in_array($viewMode, ['events', 'ledger'], true) ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300' }}">
                    {{ count($eventsRows) }}
                </span>
            </button>

            <!-- 2. Reparto Socios -->
            <button
                type="button"
                wire:click="setViewMode('settlement')"
                class="flex items-center justify-center gap-2 py-3 px-3 rounded-2xl text-xs font-black transition cursor-pointer {{ in_array($viewMode, ['settlement', 'event_settlement'], true) ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/25 ring-2 ring-emerald-400/40' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}"
            >
                <span class="text-base">⚖️</span>
                <span>2. Reparto Socios</span>
                <span class="px-1.5 py-0.5 rounded-md text-[10px] {{ in_array($viewMode, ['settlement', 'event_settlement'], true) ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300' }}">
                    {{ count($eventsRows) }}
                </span>
            </button>

            <!-- 3. Gastos & Proveedores -->
            <button
                type="button"
                wire:click="setViewMode('expenses')"
                class="flex items-center justify-center gap-2 py-3 px-3 rounded-2xl text-xs font-black transition cursor-pointer {{ in_array($viewMode, ['expenses', 'providers', 'materials'], true) ? 'bg-amber-600 text-white shadow-md shadow-amber-600/25 ring-2 ring-amber-400/40' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}"
            >
                <span class="text-base">🍽️</span>
                <span>3. Gastos & Proveedores</span>
                <span class="px-1.5 py-0.5 rounded-md text-[10px] {{ in_array($viewMode, ['expenses', 'providers', 'materials'], true) ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300' }}">
                    {{ count($providerRows) + count($materialRows) }}
                </span>
            </button>

            <!-- 4. Caja & Banco -->
            <button
                type="button"
                wire:click="setViewMode('joint_account')"
                class="flex items-center justify-center gap-2 py-3 px-3 rounded-2xl text-xs font-black transition cursor-pointer {{ $viewMode === 'joint_account' ? 'bg-teal-600 text-white shadow-md shadow-teal-600/25 ring-2 ring-teal-400/40' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}"
            >
                <span class="text-base">🏦</span>
                <span>4. Caja & Banco</span>
            </button>
        </div>

        <!-- SELECTOR RÁPIDO DE TEMPORADA (AÑO DEL EVENTO) -->
        <div class="mt-4 pt-3.5 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 flex items-center gap-1.5 mr-1">
                    <span>🗓️</span>
                    <span>Filtrar por Temporada:</span>
                </span>

                <button
                    type="button"
                    wire:click="setSeason('all')"
                    class="px-3 py-1.5 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5 {{ $season === 'all' ? 'bg-slate-900 dark:bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}"
                >
                    <span>🌍</span>
                    <span>Todas las Temporadas</span>
                    <span class="px-1.5 py-0.2 rounded-md text-[10px] {{ $season === 'all' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300' }}">
                        {{ array_sum($seasonCounts ?? []) }}
                    </span>
                </button>

                @foreach($availableSeasons ?? [] as $sy)
                    @php
                        $sCnt = (int) ($seasonCounts[$sy] ?? 0);
                        $isActiveSeason = (string) $season === (string) $sy;
                    @endphp
                    <button
                        type="button"
                        wire:click="setSeason('{{ $sy }}')"
                        class="px-3 py-1.5 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5 {{ $isActiveSeason ? 'bg-indigo-600 text-white shadow-xs ring-2 ring-indigo-300 dark:ring-indigo-800' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-slate-700' }}"
                    >
                        <span>💍</span>
                        <span>Temporada {{ $sy }}</span>
                        <span class="px-1.5 py-0.2 rounded-md text-[10px] {{ $isActiveSeason ? 'bg-white/25 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300' }}">
                            {{ $sCnt }} ev.
                        </span>
                    </button>
                @endforeach
            </div>

            @if($season !== 'all')
                <div class="inline-flex items-center gap-2 text-xs font-bold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 px-3 py-1 rounded-xl">
                    <span>🎯 Viendo solo eventos de la <strong>Temporada {{ $season }}</strong> (incluye señales cobradas en cualquier año)</span>
                    <button type="button" wire:click="setSeason('all')" class="text-indigo-500 hover:text-rose-600 font-black cursor-pointer ml-1" title="Ver todas las temporadas">✕</button>
                </div>
            @endif
        </div>
    </div>

    <!-- TARJETAS KPI GLOBALES Y FILTROS (SOLO EN EVENTOS Y LIBRO DE COBROS) -->
    <!-- PESTAÑA 1: COBROS A CLIENTES Y FACTURACIÓN -->
    @if(in_array($viewMode, ['events', 'ledger'], true))
        <!-- SUB-BARRA DE NAVEGACIÓN EN COBROS A CLIENTES -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white dark:bg-slate-900 p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="flex items-center gap-2">
                <span class="text-[11px] font-black uppercase tracking-wider text-slate-400 pl-1">
                    Vista:
                </span>
                <button
                    type="button"
                    wire:click="$set('viewMode', 'events')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5 {{ $viewMode === 'events' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}"
                >
                    <span>📋</span>
                    <span>Por Eventos ({{ count($eventsRows) }})</span>
                </button>
                <button
                    type="button"
                    wire:click="$set('viewMode', 'ledger')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5 {{ $viewMode === 'ledger' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}"
                >
                    <span>🧾</span>
                    <span>Libro de Facturas y Recibos ({{ count($ledgerRows) }})</span>
                </button>
            </div>
            <div class="text-[11px] text-slate-500 font-semibold">
                Control de cobros de clientes, señales y emisión de justificantes en PDF.
            </div>
        </div>

        <!-- TARJETAS KPI DE COBROS A CLIENTES -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
            <!-- 1. TOTAL INGRESADO / COBRADO -->
            <x-ui.stat-card
                label="Total Cobrado Clientes"
                value="{{ number_format($kpis['total_collected'], 2, ',', '.') }} €"
                variant="emerald"
                icon="💶"
            >
                <div class="flex items-center justify-between text-[11px]">
                    <span>Señales: <strong class="text-slate-800 dark:text-slate-200">{{ number_format($kpis['total_deposits_collected'], 2, ',', '.') }} €</strong></span>
                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/70 text-emerald-800 dark:text-emerald-300 font-bold">{{ $kpis['events_with_payment_count'] }} ev.</span>
                </div>
                <div class="flex items-center justify-between text-[11px]">
                    <span>Restos: <strong class="text-slate-800 dark:text-slate-200">{{ number_format($kpis['total_finals_collected'], 2, ',', '.') }} €</strong></span>
                </div>
            </x-ui.stat-card>

            <!-- 2. PENDIENTE DE COBRO -->
            <x-ui.stat-card
                label="Pendiente de Cobro"
                value="{{ number_format($kpis['total_pending'], 2, ',', '.') }} €"
                variant="amber"
                icon="⏳"
            >
                <div class="flex items-center justify-between text-[11px]">
                    <span>Total acordado:</span>
                    <strong class="text-slate-700 dark:text-slate-300">{{ number_format($kpis['total_agreed'], 2, ',', '.') }} €</strong>
                </div>
                <div class="flex items-center justify-between text-[10px] text-slate-400">
                    <span>Liquidados 100%: <strong>{{ $kpis['events_fully_paid_count'] }}</strong></span>
                    <span>{{ $kpis['events_partial_count'] + $kpis['events_unpaid_count'] }} pend.</span>
                </div>
            </x-ui.stat-card>

            <!-- 3. FACTURACIÓN NETA CLIENTES -->
            <x-ui.stat-card
                label="Facturación Neta Acordada"
                value="{{ number_format($kpis['net_agreed_after_providers'], 2, ',', '.') }} €"
                variant="indigo"
                icon="💎"
            >
                <div class="flex items-center justify-between text-[11px]">
                    <span>Ingresos brutos:</span>
                    <strong class="text-slate-700 dark:text-slate-300">{{ number_format($kpis['total_agreed'], 2, ',', '.') }} €</strong>
                </div>
                <div class="flex items-center justify-between text-[10px] text-slate-500">
                    <span>Descontando costes externos</span>
                </div>
            </x-ui.stat-card>

            <!-- 4. FACTURAS (IVA) vs RECIBOS -->
            <x-ui.stat-card
                label="Facturas vs Recibos"
                value="{{ number_format(($kpis['tax_summary']['invoices_total'] ?? 0) + ($kpis['tax_summary']['receipts_total'] ?? 0), 2, ',', '.') }} €"
                variant="neutral"
                icon="🧾"
            >
                <div class="flex items-center justify-between text-[11px]">
                    <span class="text-slate-500 dark:text-slate-400">Facturas ({{ $kpis['tax_summary']['invoices_count'] }}):</span>
                    <strong class="text-blue-600 dark:text-blue-400">{{ number_format($kpis['tax_summary']['invoices_total'], 2, ',', '.') }} €</strong>
                </div>
                <div class="flex items-center justify-between text-[10px] text-slate-400">
                    <span>Base: {{ number_format($kpis['tax_summary']['invoices_base'], 2, ',', '.') }} €</span>
                    <span>IVA: {{ number_format($kpis['tax_summary']['invoices_tax'], 2, ',', '.') }} €</span>
                </div>
                <div class="flex items-center justify-between text-[11px] pt-1 border-t border-slate-100 dark:border-slate-800">
                    <span class="text-slate-500 dark:text-slate-400">Recibos:</span>
                    <strong class="text-emerald-600 dark:text-emerald-400">{{ number_format($kpis['tax_summary']['receipts_total'], 2, ',', '.') }} €</strong>
                </div>
            </x-ui.stat-card>
        </div>
                <span class="text-slate-500 dark:text-slate-400">Facturas ({{ $kpis['tax_summary']['invoices_count'] }}):</span>
                <strong class="text-blue-600 dark:text-blue-400">{{ number_format($kpis['tax_summary']['invoices_total'], 2, ',', '.') }} €</strong>
            </div>
            <div class="flex items-center justify-between text-[10px] text-slate-400">
                <span>Base: {{ number_format($kpis['tax_summary']['invoices_base'], 2, ',', '.') }} €</span>
                <span>IVA: {{ number_format($kpis['tax_summary']['invoices_tax'], 2, ',', '.') }} €</span>
            </div>
            <div class="flex items-center justify-between text-[11px] pt-1 border-t border-slate-100 dark:border-slate-800">
                <span class="text-slate-500 dark:text-slate-400">Recibos:</span>
                <strong class="text-emerald-600 dark:text-emerald-400">{{ number_format($kpis['tax_summary']['receipts_total'], 2, ',', '.') }} €</strong>
            </div>
        </x-ui.stat-card>
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
            <div>
                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">
                    🔍 Buscar Evento, Cliente, DNI o Factura
                </label>
                <input
                    type="text"
                    wire:model.live.debounce.250ms="search"
                    placeholder="Ej: Boda Javi, 45422025S, REC-2026..."
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-3 py-2 text-xs font-semibold text-slate-900 dark:text-white"
                >
            </div>

            <!-- Temporada (Año del Evento) -->
            <div>
                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 mb-1">
                    💍 Temporada (Año Evento)
                </label>
                <select wire:model.live="season" class="w-full rounded-xl border border-indigo-300 dark:border-indigo-700 bg-indigo-50/50 dark:bg-indigo-950/30 px-3 py-2 text-xs font-black text-indigo-900 dark:text-indigo-200">
                    <option value="all">🌍 Todas las temporadas</option>
                    @foreach($availableSeasons ?? [] as $sy)
                        <option value="{{ $sy }}">💍 Temporada {{ $sy }} ({{ $seasonCounts[$sy] ?? 0 }} ev.)</option>
                    @endforeach
                </select>
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

    @endif

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
                            <th class="py-3 px-4">📸 Gasto Proveedor / Neto</th>
                            <th class="py-3 px-4">Facturas / Recibos</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                        @forelse($eventsRows as $row)
                            @php
                                /** @var \App\Models\Event $ev */
                                $ev = $row['event'];
                                $fb = $row['fb'];
                                $evProv = $row['prov'] ?? $ev->external_provider_summary;
                                $evProvCost = !empty($evProv['has_external_service']) ? (float) ($evProv['provider_total_cost'] ?? 0) : 0.0;
                                $evNetTotal = max(0, round((float) $fb['quote_total'] - $evProvCost, 2));
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
                                <td class="py-3.5 px-3 text-right align-top whitespace-nowrap">
                                    @if($fb['quote_total'] > 0)
                                        <div class="font-black text-slate-900 dark:text-white">
                                            {{ number_format($fb['quote_total'], 2, ',', '.') }} €
                                        </div>
                                        @if($evProvCost > 0)
                                            <div class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 mt-0.5" title="Ingreso neto del evento descontando el gasto de Fotomatón / 360º ({{ number_format($evProvCost, 2, ',', '.') }} €)">
                                                💎 Neto: {{ number_format($evNetTotal, 2, ',', '.') }} €
                                            </div>
                                        @endif
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
                                                @if(!empty($fb['deposit_collected_by']))
                                                    @php
                                                        $isDepJoint = str_contains(mb_strtolower($fb['deposit_collected_by']), 'conjunt') 
                                                            || str_contains(mb_strtolower($fb['deposit_collected_by']), 'comun') 
                                                            || str_contains(mb_strtolower($fb['deposit_collected_by']), 'común')
                                                            || str_contains(mb_strtolower($fb['deposit_collected_by']), 'empresa')
                                                            || str_contains(mb_strtolower($fb['deposit_collected_by']), 'fondo');
                                                    @endphp
                                                    @if($isDepJoint)
                                                        @php
                                                            $isDepCash = ($fb['deposit_method'] ?? '') === 'cash' || str_contains(mb_strtolower($fb['deposit_collected_by']), 'efectivo') || str_contains(mb_strtolower($fb['deposit_collected_by']), 'caja');
                                                        @endphp
                                                        <span class="inline-flex items-center gap-1 font-extrabold px-1.5 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-[10px]" title="{{ $isDepCash ? 'Fondo Común en Efectivo / Caja física (no en banco)' : 'Fondo común en cuenta bancaria' }}">
                                                            {{ $isDepCash ? '💵 Fondo Común (Efectivo / Caja)' : '🏦 Fondo Común (Banco)' }}
                                                        </span>
                                                        <button
                                                            type="button"
                                                            wire:click="openExpenseForEvent({{ $ev->id }}, {{ $fb['deposit_amount'] }})"
                                                            class="inline-flex items-center gap-0.5 text-[10px] text-amber-700 dark:text-amber-400 hover:underline font-bold cursor-pointer"
                                                            title="Anotar en qué se va a gastar este dinero (Cena de Navidad, compras, etc.)"
                                                        >
                                                            🍽️ Anotar Gasto / Uso
                                                        </button>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 font-extrabold px-1.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 text-[10px]" title="Cobrado en cuenta de {{ $fb['deposit_collected_by'] }}">
                                                            👤 {{ $fb['deposit_collected_by'] }}
                                                        </span>
                                                    @endif
                                                @endif
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
                                                <button
                                                    type="button"
                                                    wire:click="openQuickPayment({{ $ev->id }}, 'final')"
                                                    class="text-[11px] text-blue-700 dark:text-blue-400 hover:underline font-bold cursor-pointer"
                                                    title="Editar importe, fecha, método o quién cobró este pago final"
                                                >
                                                    ✏️
                                                </button>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-1.5 text-[11px] text-slate-600 dark:text-slate-300">
                                                @if($fb['final_method_label'])
                                                    <span class="inline-flex items-center gap-1 font-bold bg-white dark:bg-slate-900 px-1.5 py-0.5 rounded border border-blue-200 dark:border-blue-800">
                                                        <span>{{ $fb['final_method_icon'] }}</span>
                                                        <span>{{ $fb['final_method_label'] }}</span>
                                                    </span>
                                                @endif
                                                @if(!empty($fb['final_collected_by']))
                                                    @php
                                                        $isFinJoint = str_contains(mb_strtolower($fb['final_collected_by']), 'conjunt') 
                                                            || str_contains(mb_strtolower($fb['final_collected_by']), 'comun') 
                                                            || str_contains(mb_strtolower($fb['final_collected_by']), 'común')
                                                            || str_contains(mb_strtolower($fb['final_collected_by']), 'empresa')
                                                            || str_contains(mb_strtolower($fb['final_collected_by']), 'fondo');
                                                    @endphp
                                                    @if($isFinJoint)
                                                        @php
                                                            $isFinCash = ($fb['final_method'] ?? '') === 'cash' || str_contains(mb_strtolower($fb['final_collected_by']), 'efectivo') || str_contains(mb_strtolower($fb['final_collected_by']), 'caja');
                                                        @endphp
                                                        <span class="inline-flex items-center gap-1 font-extrabold px-1.5 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-[10px]" title="{{ $isFinCash ? 'Fondo Común en Efectivo / Caja física (no en banco)' : 'Fondo común en cuenta bancaria' }}">
                                                            {{ $isFinCash ? '💵 Fondo Común (Efectivo / Caja)' : '🏦 Fondo Común (Banco)' }}
                                                        </span>
                                                        <button
                                                            type="button"
                                                            wire:click="openExpenseForEvent({{ $ev->id }}, {{ $fb['final_paid_amount'] }})"
                                                            class="inline-flex items-center gap-0.5 text-[10px] text-amber-700 dark:text-amber-400 hover:underline font-bold cursor-pointer"
                                                            title="Anotar en qué se va a gastar este dinero (Cena de Navidad, compras, etc.)"
                                                        >
                                                            🍽️ Anotar Gasto / Uso
                                                        </button>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 font-extrabold px-1.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 text-[10px]" title="Cobrado en cuenta de {{ $fb['final_collected_by'] }}">
                                                            👤 {{ $fb['final_collected_by'] }}
                                                        </span>
                                                    @endif
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

                                <!-- Gasto Proveedor (Fotomatón / 360º) y Neto -->
                                <td class="py-3.5 px-4 align-top">
                                    @if($evProv['has_external_service'])
                                        <div class="p-2 rounded-xl border space-y-1 {{ $evProv['status'] === 'paid' ? 'bg-emerald-50/50 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-800/70' : ($evProv['status'] === 'deposit_paid' ? 'bg-purple-50/60 dark:bg-purple-950/30 border-purple-200 dark:border-purple-800/70' : 'bg-amber-50/50 dark:bg-amber-950/20 border-amber-200 dark:border-amber-800/70') }}">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-[11px] font-black text-purple-700 dark:text-purple-300 truncate max-w-[155px]" title="{{ $evProv['service_description'] }} ({{ $evProv['provider_name'] }})">
                                                    📸 Gasto: {{ number_format($evProv['provider_total_cost'], 2, ',', '.') }} €
                                                </span>
                                                <button
                                                    type="button"
                                                    wire:click="openProviderModal({{ $ev->id }}, 'deposit')"
                                                    class="text-[11px] text-purple-700 dark:text-purple-400 hover:underline font-bold cursor-pointer shrink-0"
                                                    title="Editar reserva y pagos a proveedor ({{ $evProv['provider_name'] }})"
                                                >
                                                    ✏️
                                                </button>
                                            </div>
                                            @if(!empty($evProv['detected_from_quote']) && (float)($evProv['client_price'] ?? 0) > 0)
                                                <div class="text-[10px] text-slate-500 dark:text-slate-400">
                                                    PVP Cliente: <strong class="text-slate-700 dark:text-slate-200">{{ number_format($evProv['client_price'], 2, ',', '.') }} €</strong>
                                                </div>
                                            @endif
                                            <div class="flex flex-wrap items-center justify-between gap-1 text-[10px] pt-0.5 border-t border-purple-200/50 dark:border-purple-800/40">
                                                @if($evProv['status'] === 'paid')
                                                    <span class="font-bold text-emerald-700 dark:text-emerald-400">
                                                        ✅ Pagado 100% ({{ number_format($evProv['total_paid'], 0, ',', '.') }}€)
                                                    </span>
                                                @elseif($evProv['status'] === 'deposit_paid')
                                                    <span class="font-bold text-emerald-700 dark:text-emerald-400">
                                                        Señal: {{ number_format($evProv['deposit_amount'], 0, ',', '.') }}€
                                                    </span>
                                                    <button
                                                        type="button"
                                                        wire:click="openProviderModal({{ $ev->id }}, 'final')"
                                                        class="font-bold text-amber-700 dark:text-amber-300 hover:underline cursor-pointer"
                                                        title="Anotar liquidación pendiente al proveedor"
                                                    >
                                                        Falta: {{ number_format($evProv['pending_amount'], 0, ',', '.') }}€
                                                    </button>
                                                @else
                                                    <button
                                                        type="button"
                                                        wire:click="openProviderModal({{ $ev->id }}, 'deposit')"
                                                        class="font-bold text-amber-700 dark:text-amber-300 hover:underline cursor-pointer"
                                                    >
                                                        ⏳ + Anotar Señal ({{ number_format($evProv['suggested_deposit'], 0, ',', '.') }}€)
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex items-center justify-between gap-1">
                                            <span class="text-[11px] text-slate-400">—</span>
                                            <button
                                                type="button"
                                                wire:click="openProviderModal({{ $ev->id }}, 'deposit')"
                                                class="text-[10px] font-bold text-slate-400 hover:text-purple-600 transition cursor-pointer"
                                                title="Añadir gasto de proveedor externo (Fotomatón / 360º) a este evento"
                                            >
                                                + Prov.
                                            </button>
                                        </div>
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
                                <td colspan="9" class="py-10 text-center text-slate-400 text-xs">
                                    No hay eventos que coincidan con los filtros seleccionados. Prueba cambiando el filtro de «Estado de Cobro» a «Todos los Eventos».
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($eventsRows) > 0)
                        <tfoot>
                            <tr class="border-t-2 border-slate-300 dark:border-slate-700 bg-slate-100/90 dark:bg-slate-950 font-black text-xs text-slate-900 dark:text-white">
                                <td class="py-3.5 px-4" colspan="2">
                                    TOTALES ({{ count($eventsRows) }} eventos listados)
                                </td>
                                <td class="py-3.5 px-3 text-right whitespace-nowrap">
                                    <div>{{ number_format($kpis['total_agreed'], 2, ',', '.') }} €</div>
                                    @if(($kpis['provider_cost_in_events'] ?? 0) > 0)
                                        <div class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400">
                                            💎 Neto: {{ number_format($kpis['net_agreed_after_providers'], 2, ',', '.') }} €
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-emerald-700 dark:text-emerald-400">
                                    Señales: {{ number_format($kpis['total_deposits_collected'], 2, ',', '.') }} €
                                </td>
                                <td class="py-3.5 px-4 text-blue-700 dark:text-blue-400">
                                    Restos: {{ number_format($kpis['total_finals_collected'], 2, ',', '.') }} €
                                </td>
                                <td class="py-3.5 px-3 text-right text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                    <div>{{ number_format($kpis['total_collected'], 2, ',', '.') }} €</div>
                                    @if(($kpis['provider_paid_in_events'] ?? 0) > 0)
                                        <div class="text-[10px] font-bold text-slate-500">
                                            Caja neta: {{ number_format($kpis['net_collected_after_providers'], 2, ',', '.') }} €
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-3 text-right text-amber-600 dark:text-amber-400 whitespace-nowrap">
                                    {{ number_format($kpis['total_pending'], 2, ',', '.') }} €
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="text-purple-700 dark:text-purple-300">
                                        Gasto Prov.: {{ number_format($kpis['provider_cost_in_events'] ?? 0, 2, ',', '.') }} €
                                    </div>
                                    <div class="text-[10px] font-bold text-slate-500">
                                        Pagado: {{ number_format($kpis['provider_paid_in_events'] ?? 0, 0, ',', '.') }}€ · Falta: {{ number_format($kpis['provider_pending_in_events'] ?? 0, 0, ',', '.') }}€
                                    </div>
                                </td>
                                <td class="py-3.5 px-4"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    @elseif($viewMode === 'ledger')
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
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase {{ ($item['document_type'] ?? '') === 'factura' ? 'bg-blue-100 text-blue-800' : 'bg-emerald-100 text-emerald-800' }}">
                                            {{ ($item['document_type'] ?? '') === 'factura' ? '📄 FACTURA' : '🧾 RECIBO / SEÑAL' }}
                                        </span>
                                        <span class="font-mono font-black text-slate-900 dark:text-white">{{ $item['document_number'] }}</span>
                                        @if(!empty($item['collected_by']))
                                            <span class="inline-flex items-center gap-1 font-extrabold px-1.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 text-[10px]" title="Cobrado por {{ $item['collected_by'] }}">
                                                👤 {{ $item['collected_by'] }}
                                            </span>
                                        @endif
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
    @elseif(in_array($viewMode, ['expenses', 'providers', 'materials'], true))
        <!-- ==================================================================== -->
        <!-- PESTAÑA 3: GASTOS DE EMPRESA, PROVEEDORES Y MATERIAL (SALIDAS)      -->
        <!-- ==================================================================== -->
        @php
            $pKpis = $kpis['provider_summary'] ?? [];
            $mKpis = $kpis['material_summary'] ?? [];
        @endphp
        <div class="space-y-6">
            <!-- SUB-BARRA DE NAVEGACIÓN EN GASTOS -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white dark:bg-slate-900 p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-[11px] font-black uppercase tracking-wider text-slate-400 pl-1">
                        Ver Gastos:
                    </span>
                    <button
                        type="button"
                        wire:click="$set('expensesSubTab', 'all')"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5 {{ $expensesSubTab === 'all' ? 'bg-amber-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}"
                    >
                        <span>👁️</span>
                        <span>Ver Todo ({{ count($providerRows) + count($materialRows) }})</span>
                    </button>
                    <button
                        type="button"
                        wire:click="$set('expensesSubTab', 'providers')"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5 {{ $expensesSubTab === 'providers' ? 'bg-purple-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}"
                    >
                        <span>📸</span>
                        <span>Proveedores Externos ({{ count($providerRows) }})</span>
                    </button>
                    <button
                        type="button"
                        wire:click="$set('expensesSubTab', 'materials')"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5 {{ $expensesSubTab === 'materials' ? 'bg-amber-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}"
                    >
                        <span>🍽️</span>
                        <span>Cenas, Empresa y Material ({{ count($materialRows) }})</span>
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        wire:click="openMaterialModal"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-black shadow-xs transition cursor-pointer"
                    >
                        <span>➕</span>
                        <span>Gasto / Cena Empresa</span>
                    </button>
                    <button
                        type="button"
                        wire:click="openProviderModal(null, 'deposit')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-black shadow-xs transition cursor-pointer"
                    >
                        <span>📸</span>
                        <span>Reserva Proveedor</span>
                    </button>
                </div>
            </div>

            @if(in_array($expensesSubTab, ['all', 'providers'], true))
            <!-- SECCIÓN: RESERVAS Y PAGOS A PROVEEDOR (FOTOMATÓN Y PLATAFORMA 360º) -->
            <div class="space-y-4">
            <!-- Resumen de Pagos a la Empresa de Fotomatón / 360º -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-purple-200 dark:border-purple-900/60 p-4 shadow-xs">
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-purple-700 dark:text-purple-400 block">
                        📸 Eventos con Fotomatón / 360º
                    </span>
                    <div class="text-2xl font-black text-slate-900 dark:text-white mt-1.5">
                        {{ $pKpis['events_count'] ?? 0 }} eventos
                    </div>
                    <div class="text-[11px] text-slate-500 mt-1 space-y-0.5">
                        <div>Gasto total proveedor: <strong class="text-purple-700 dark:text-purple-300">{{ number_format($pKpis['total_cost'] ?? 0, 2, ',', '.') }} €</strong></div>
                        @if(($pKpis['total_client_price'] ?? 0) > 0)
                            <div>PVP pasado a clientes: <strong class="text-slate-800 dark:text-slate-200">{{ number_format($pKpis['total_client_price'], 2, ',', '.') }} €</strong></div>
                        @endif
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-emerald-200 dark:border-emerald-900/60 p-4 shadow-xs">
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 block">
                        🟢 Señales de Reserva Pagadas
                    </span>
                    <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1.5">
                        {{ number_format($pKpis['deposits_paid'] ?? 0, 2, ',', '.') }} €
                    </div>
                    <div class="text-[11px] text-slate-500 mt-1">
                        En <strong>{{ ($pKpis['events_deposit_paid_count'] ?? 0) + ($pKpis['events_fully_paid_count'] ?? 0) }}</strong> eventos reservados
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-blue-200 dark:border-blue-900/60 p-4 shadow-xs">
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-blue-700 dark:text-blue-400 block">
                        ✅ Total Pagado al Proveedor
                    </span>
                    <div class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1.5">
                        {{ number_format($pKpis['total_paid'] ?? 0, 2, ',', '.') }} €
                    </div>
                    <div class="text-[11px] text-slate-500 mt-1">
                        Liquidaciones finales: <strong>{{ number_format($pKpis['finals_paid'] ?? 0, 2, ',', '.') }} €</strong> ({{ $pKpis['events_fully_paid_count'] ?? 0 }} ev.)
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-amber-200 dark:border-amber-900/60 p-4 shadow-xs">
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-amber-700 dark:text-amber-400 block">
                        ⏳ Falta por Pagar al Proveedor
                    </span>
                    <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1.5">
                        {{ number_format($pKpis['total_pending'] ?? 0, 2, ',', '.') }} €
                    </div>
                    <div class="text-[11px] text-slate-500 mt-1">
                        Pendiente de liquidar en <strong>{{ ($pKpis['events_deposit_paid_count'] ?? 0) + ($pKpis['events_unpaid_count'] ?? 0) }}</strong> eventos
                    </div>
                </div>
            </div>

            <!-- Tabla de Reservas y Pagos a Proveedor (Fotomatón / 360º) -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3 bg-purple-50/50 dark:bg-slate-950/60">
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <span>📸</span>
                            <span>Control de Proveedores Externos (Fotomatón, 360º, Artístamente...) ({{ count($providerRows) }})</span>
                        </h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Detecta automáticamente los servicios externos contratados (Fotomatón, 360º, altas en Artístamente...). Controla señales, liquidaciones y qué socio lo adelantó de su bolsillo.
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="openProviderModal(null, 'deposit'); resetProviderModalForNewBooking();"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-extrabold shadow-xs transition cursor-pointer"
                    >
                        <span>➕</span>
                        <span>Añadir Proveedor / Gasto Externo</span>
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-[10px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-950">
                                <th class="py-3 px-4">Evento / Fecha</th>
                                <th class="py-3 px-3">Empresa / Servicio Contratado</th>
                                <th class="py-3 px-3 text-right">PVP Cliente (Presupuesto)</th>
                                <th class="py-3 px-3 text-right">Gasto Proveedor</th>
                                <th class="py-3 px-4">1º Pago: Señal Reserva a Empresa</th>
                                <th class="py-3 px-4">2º Pago: Liquidación / Resto a Empresa</th>
                                <th class="py-3 px-3 text-right">Total Pagado</th>
                                <th class="py-3 px-3 text-right">Falta por Pagar</th>
                                <th class="py-3 px-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                            @forelse($providerRows as $pRow)
                                @php
                                    /** @var \App\Models\Event $ev */
                                    $ev = $pRow['event'];
                                    $prov = $pRow['prov'];
                                @endphp
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                    <!-- Evento -->
                                    <td class="py-3.5 px-4 align-top">
                                        <a href="{{ route('admin.events.show', $ev->id) }}" class="font-black text-slate-900 dark:text-white hover:text-indigo-600 block">
                                            {{ $ev->event_type_icon }} {{ $ev->name }}
                                        </a>
                                        <div class="text-[11px] text-slate-500 mt-0.5">
                                            📅 {{ $ev->event_date ? $ev->event_date->format('d/m/Y') : 'Sin fecha' }}
                                            &bull; 📍 {{ $ev->location ?: 'Por definir' }}
                                        </div>
                                        <div class="text-[11px] text-slate-400">
                                            👤 {{ $ev->client?->name ?? 'Sin cliente' }}
                                        </div>
                                    </td>

                                    <!-- Empresa Proveedora y Servicio -->
                                    <td class="py-3.5 px-3 align-top">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-purple-100 dark:bg-purple-950 text-purple-800 dark:text-purple-300 text-[10px] font-black">
                                                🏢 {{ $prov['provider_name'] }}
                                            </span>
                                            @if(!empty($prov['paid_by']))
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 text-[10px] font-bold" title="Abonado por {{ $prov['paid_by'] }}">
                                                    👤 Pagado por: {{ $prov['paid_by'] }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="font-bold text-slate-800 dark:text-slate-200 mt-1 leading-snug">
                                            {{ $prov['service_description'] }}
                                        </div>
                                        @if(($prov['bookings_count'] ?? 0) > 1)
                                            <div class="mt-2 space-y-1">
                                                @foreach($prov['bookings'] as $b)
                                                    <div class="flex items-center justify-between text-[10px] bg-slate-50 dark:bg-slate-950 p-1.5 rounded-lg border border-slate-200 dark:border-slate-800">
                                                        <span class="font-bold text-slate-700 dark:text-slate-300">{{ $b->provider_name }}: {{ $b->service_description }} ({{ number_format($b->provider_total_cost, 2, ',', '.') }} €)</span>
                                                        <span class="font-bold text-indigo-600 dark:text-indigo-400">👤 {{ $b->paid_by ?: 'Fran' }}</span>
                                                        <button type="button" wire:click="openProviderModal({{ $ev->id }}, 'final', {{ $b->id }})" class="text-purple-600 dark:text-purple-400 hover:underline font-bold cursor-pointer" title="Editar este gasto">✏️</button>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if(!empty($prov['notes']))
                                            <div class="text-[11px] text-slate-500 italic mt-0.5">
                                                📝 {{ $prov['notes'] }}
                                            </div>
                                        @endif
                                    </td>

                                    <!-- PVP Pasado al Cliente en Presupuesto -->
                                    <td class="py-3.5 px-3 text-right align-top whitespace-nowrap">
                                        @if($prov['client_price'] > 0)
                                            <span class="font-black text-indigo-700 dark:text-indigo-300">
                                                {{ number_format($prov['client_price'], 2, ',', '.') }} €
                                            </span>
                                            <div class="text-[10px] text-slate-400">En presupuesto</div>
                                        @else
                                            <span class="text-slate-400 text-[11px]">—</span>
                                        @endif
                                    </td>

                                    <!-- Coste Total Proveedor -->
                                    <td class="py-3.5 px-3 text-right align-top font-black whitespace-nowrap">
                                        <div class="text-purple-700 dark:text-purple-300">
                                            {{ number_format($prov['provider_total_cost'], 2, ',', '.') }} €
                                        </div>
                                        @if(!empty($prov['has_refund']))
                                            <div class="inline-flex items-center gap-1 mt-1 px-1.5 py-0.5 rounded text-[10px] font-black bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-300" title="Liquidación cooperativa que se suma al reparto">
                                                <span>🎭</span>
                                                <span>Dev: +{{ number_format($prov['refund_amount'], 2, ',', '.') }} €</span>
                                            </div>
                                            <div class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 mt-0.5">
                                                Neto: {{ number_format($prov['net_cost'], 2, ',', '.') }} €
                                            </div>
                                        @endif
                                    </td>

                                    <!-- 1º Pago: Señal pagada al proveedor -->
                                    <td class="py-3.5 px-4 align-top">
                                        @if($prov['deposit_paid'] && $prov['deposit_amount'] > 0)
                                            <div class="p-2 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/70 space-y-1">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="font-black text-emerald-700 dark:text-emerald-300 text-xs">
                                                        ✅ {{ number_format($prov['deposit_amount'], 2, ',', '.') }} €
                                                    </span>
                                                    <button
                                                        type="button"
                                                        wire:click="openProviderModal({{ $ev->id }}, 'deposit')"
                                                        class="text-[11px] text-emerald-700 hover:underline font-bold cursor-pointer"
                                                        title="Editar señal pagada al proveedor"
                                                    >
                                                        ✏️
                                                    </button>
                                                </div>
                                                <div class="flex flex-wrap items-center gap-1.5 text-[11px] text-slate-600 dark:text-slate-300">
                                                    <span class="inline-flex items-center gap-1 font-bold bg-white dark:bg-slate-900 px-1.5 py-0.5 rounded border border-emerald-200 dark:border-emerald-800">
                                                        <span>{{ $prov['deposit_method_icon'] }}</span>
                                                        <span>{{ $prov['deposit_method_label'] }}</span>
                                                    </span>
                                                    @if($prov['deposit_date'])
                                                        <span class="text-slate-500">
                                                            📅 {{ \Carbon\Carbon::parse($prov['deposit_date'])->format('d/m/Y') }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-[11px] text-amber-700 dark:text-amber-400 font-semibold">
                                                    ⏳ Sin reservar ({{ number_format($prov['suggested_deposit'], 0, ',', '.') }}€ sug.)
                                                </span>
                                                <button
                                                    type="button"
                                                    wire:click="openProviderModal({{ $ev->id }}, 'deposit')"
                                                    class="px-2.5 py-1 rounded-lg bg-purple-600 hover:bg-purple-700 text-white text-[10px] font-bold transition cursor-pointer shadow-2xs"
                                                >
                                                    + Anotar Señal
                                                </button>
                                            </div>
                                        @endif
                                    </td>

                                    <!-- 2º Pago: Liquidación / Resto al proveedor -->
                                    <td class="py-3.5 px-4 align-top">
                                        @if($prov['final_paid'] && $prov['final_amount'] > 0)
                                            <div class="p-2 rounded-xl bg-blue-50/70 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/70 space-y-1">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="font-black text-blue-700 dark:text-blue-300 text-xs">
                                                        ✅ {{ number_format($prov['final_amount'], 2, ',', '.') }} €
                                                    </span>
                                                    <button
                                                        type="button"
                                                        wire:click="openProviderModal({{ $ev->id }}, 'final')"
                                                        class="text-[11px] text-blue-700 hover:underline font-bold cursor-pointer"
                                                    >
                                                        ✏️
                                                    </button>
                                                </div>
                                                <div class="flex flex-wrap items-center gap-1.5 text-[11px] text-slate-600 dark:text-slate-300">
                                                    <span class="inline-flex items-center gap-1 font-bold bg-white dark:bg-slate-900 px-1.5 py-0.5 rounded border border-blue-200 dark:border-blue-800">
                                                        <span>{{ $prov['final_method_icon'] }}</span>
                                                        <span>{{ $prov['final_method_label'] }}</span>
                                                    </span>
                                                    @if($prov['final_date'])
                                                        <span class="text-slate-500">
                                                            📅 {{ \Carbon\Carbon::parse($prov['final_date'])->format('d/m/Y') }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @elseif($prov['pending_amount'] > 0)
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-[11px] text-slate-500">
                                                    Faltan <strong>{{ number_format($prov['pending_amount'], 2, ',', '.') }} €</strong>
                                                </span>
                                                <button
                                                    type="button"
                                                    wire:click="openProviderModal({{ $ev->id }}, 'final')"
                                                    class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-[10px] font-bold transition cursor-pointer"
                                                >
                                                    + Pagar Resto
                                                </button>
                                            </div>
                                        @else
                                            <span class="text-[11px] text-slate-400">—</span>
                                        @endif
                                    </td>

                                    <!-- Total Pagado al Proveedor -->
                                    <td class="py-3.5 px-3 text-right align-top whitespace-nowrap">
                                        <div class="font-black text-sm {{ $prov['total_paid'] > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }}">
                                            {{ number_format($prov['total_paid'], 2, ',', '.') }} €
                                        </div>
                                        <span class="text-[10px] font-bold text-slate-400">{{ $prov['paid_percentage'] }}% pagado</span>
                                    </td>

                                    <!-- Falta por Pagar al Proveedor -->
                                    <td class="py-3.5 px-3 text-right align-top whitespace-nowrap">
                                        @if($prov['pending_amount'] > 0)
                                            <span class="font-black text-amber-600 dark:text-amber-400">
                                                {{ number_format($prov['pending_amount'], 2, ',', '.') }} €
                                            </span>
                                        @elseif($prov['total_paid'] > 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 text-[10px] font-black">
                                                ¡Liquidado!
                                            </span>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>

                                    <!-- Acciones -->
                                    <td class="py-3.5 px-3 text-center align-top whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1">
                                            <button
                                                type="button"
                                                wire:click="openProviderModal({{ $ev->id }}, 'deposit')"
                                                class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-indigo-50 text-slate-700 dark:text-slate-200 text-[11px] font-bold transition cursor-pointer"
                                                title="Editar reserva y pagos al proveedor"
                                            >
                                                ✏️ Editar
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="openProviderModal({{ $ev->id }}, 'final', null); resetProviderModalForNewBooking();"
                                                class="px-2 py-1 rounded-lg bg-purple-50 dark:bg-purple-950/40 hover:bg-purple-100 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800 text-[10px] font-bold transition cursor-pointer"
                                                title="Añadir otro gasto o proveedor a este evento (ej: Artístamente, saxo...)"
                                            >
                                                ➕ Otro
                                            </button>
                                            @if(!empty($prov['booking_id']))
                                                <button
                                                    type="button"
                                                    wire:click="deleteProviderBooking({{ $prov['booking_id'] }})"
                                                    wire:confirm="¿Seguro que deseas borrar los datos de pago a proveedor de este evento?"
                                                    class="px-2 py-1 rounded-lg text-rose-600 hover:bg-rose-50 text-[11px] font-bold transition cursor-pointer"
                                                    title="Eliminar anotación"
                                                >
                                                    🗑️
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-10 text-center text-slate-400 text-xs">
                                        No hay eventos con Fotomatón o Plataforma 360º en los filtros actuales. Pulsa «➕ Añadir Reserva Fotomatón / 360º» para anotar uno manualmente.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(count($providerRows) > 0)
                            <tfoot>
                                <tr class="border-t-2 border-slate-300 dark:border-slate-700 bg-slate-100/90 dark:bg-slate-950 font-black text-xs text-slate-900 dark:text-white">
                                    <td class="py-3.5 px-4" colspan="2">
                                        TOTALES PROVEEDOR ({{ count($providerRows) }} eventos)
                                    </td>
                                    <td class="py-3.5 px-3 text-right text-indigo-700 dark:text-indigo-300 whitespace-nowrap">
                                        {{ number_format($pKpis['total_client_price'] ?? 0, 2, ',', '.') }} €
                                    </td>
                                    <td class="py-3.5 px-3 text-right text-purple-700 dark:text-purple-300 whitespace-nowrap">
                                        {{ number_format($pKpis['total_cost'] ?? 0, 2, ',', '.') }} €
                                    </td>
                                    <td class="py-3.5 px-4 text-emerald-700 dark:text-emerald-400">
                                        Señales: {{ number_format($pKpis['deposits_paid'] ?? 0, 2, ',', '.') }} €
                                    </td>
                                    <td class="py-3.5 px-4 text-blue-700 dark:text-blue-400">
                                        Restos: {{ number_format($pKpis['finals_paid'] ?? 0, 2, ',', '.') }} €
                                    </td>
                                    <td class="py-3.5 px-3 text-right text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                        {{ number_format($pKpis['total_paid'] ?? 0, 2, ',', '.') }} €
                                    </td>
                                    <td class="py-3.5 px-3 text-right text-amber-600 dark:text-amber-400 whitespace-nowrap">
                                        {{ number_format($pKpis['total_pending'] ?? 0, 2, ',', '.') }} €
                                    </td>
                                    <td class="py-3.5 px-3"></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
            @endif

            @if(in_array($expensesSubTab, ['all', 'materials'], true))
            <!-- SECCIÓN: COMPRAS Y GASTOS DE MATERIAL Y EMPRESA -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/60 dark:bg-slate-950/50">
                <div>
                    <h3 class="text-sm font-black text-slate-800 dark:text-white flex items-center gap-2">
                        <span>🍽️</span>
                        <span>Gastos de Empresa, Cenas y Material ({{ count($materialRows) }})</span>
                    </h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">
                        Cenas de navidad y comidas de equipo, material técnico de sonido/iluminación, consumibles o combustible. Si se paga con Fondo Común (Efectivo o Banco), descuenta directamente de la caja común sin generar deudas; si lo adelanta un socio de su bolsillo, se le reembolsa íntegro.
                    </p>
                </div>
                <button
                    type="button"
                    wire:click="openMaterialModal"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-black shadow-sm shadow-amber-600/20 transition cursor-pointer self-start sm:self-auto shrink-0"
                >
                    <span>➕</span>
                    <span>Registrar Gasto / Cena de Empresa</span>
                </button>
            </div>

            <!-- Resumen rápido de quién pagó el material -->
            <div class="p-4 bg-amber-50/40 dark:bg-amber-950/20 border-b border-amber-100 dark:border-amber-900/40 flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-extrabold text-amber-900 dark:text-amber-300 flex items-center gap-1 text-[11px] uppercase tracking-wider">
                        <span>💳</span>
                        <span>Pagado por:</span>
                    </span>
                    @if(!empty($mKpis['by_paid_by']))
                        @foreach($mKpis['by_paid_by'] as $pName => $pAmt)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-white dark:bg-slate-800 border border-amber-200 dark:border-amber-800/60 text-slate-800 dark:text-slate-200 font-bold shadow-2xs">
                                <span>👤 {{ $pName }}:</span>
                                <strong class="text-amber-700 dark:text-amber-400 font-black">{{ number_format($pAmt, 2, ',', '.') }} €</strong>
                            </span>
                        @endforeach
                    @else
                        <span class="text-slate-400 text-xs">No hay compras registradas en los filtros seleccionados.</span>
                    @endif
                </div>

                <div class="text-right">
                    <span class="text-[11px] text-slate-500">Total Material:</span>
                    <strong class="text-sm font-black text-amber-600 dark:text-amber-400 ml-1">
                        {{ number_format($mKpis['total_amount'] ?? 0, 2, ',', '.') }} €
                    </strong>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[10px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-950">
                            <th class="py-3 px-4">Fecha Compra</th>
                            <th class="py-3 px-4">Concepto / Descripción</th>
                            <th class="py-3 px-3">Categoría</th>
                            <th class="py-3 px-3">Comercio / Proveedor</th>
                            <th class="py-3 px-3">Evento / Temporada</th>
                            <th class="py-3 px-3">Pagado Por</th>
                            <th class="py-3 px-3">Método Pago</th>
                            <th class="py-3 px-3 text-right">Importe (€)</th>
                            <th class="py-3 px-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                        @forelse($materialRows as $mat)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/30 transition">
                                <!-- Fecha -->
                                <td class="py-3 px-4 font-bold text-slate-800 dark:text-slate-200 whitespace-nowrap">
                                    {{ $mat->purchase_date ? $mat->purchase_date->format('d/m/Y') : '—' }}
                                </td>

                                <!-- Concepto y notas -->
                                <td class="py-3 px-4">
                                    <div class="font-extrabold text-slate-900 dark:text-white">
                                        {{ $mat->concept }}
                                    </div>
                                    @if($mat->invoice_number)
                                        <div class="text-[10px] text-slate-400 font-mono">
                                            Doc: {{ $mat->invoice_number }}
                                        </div>
                                    @endif
                                    @if($mat->notes)
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                            📝 {{ $mat->notes }}
                                        </div>
                                    @endif
                                </td>

                                <!-- Categoría -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                        {{ $mat->category_label }}
                                    </span>
                                </td>

                                <!-- Proveedor / Tienda -->
                                <td class="py-3 px-3 text-slate-600 dark:text-slate-300">
                                    {{ $mat->supplier ?: '—' }}
                                </td>

                                <!-- Evento o Temporada -->
                                <td class="py-3 px-3 text-slate-600 dark:text-slate-300">
                                    @if($mat->event)
                                        <span class="font-bold text-indigo-600 dark:text-indigo-400 block truncate max-w-[150px]" title="{{ $mat->event->name }}">
                                            {{ $mat->event->name }}
                                        </span>
                                    @elseif($mat->season)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                            Temp. {{ $mat->season }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 text-[11px]">General</span>
                                    @endif
                                </td>

                                <!-- Pagado Por -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    @php
                                        $paidByLower = mb_strtolower($mat->paid_by ?? '');
                                    @endphp
                                    @if(str_contains($paidByLower, 'fran'))
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-black bg-blue-100 dark:bg-blue-950 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                            👤 {{ $mat->paid_by }}
                                        </span>
                                    @elseif(str_contains($paidByLower, 'miguel') || str_contains($paidByLower, 'nunez'))
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-black bg-purple-100 dark:bg-purple-950 text-purple-800 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                            👤 {{ $mat->paid_by }}
                                        </span>
                                    @elseif(str_contains($paidByLower, 'caja') || str_contains($paidByLower, 'empresa'))
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-black bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                            🏦 {{ $mat->paid_by }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-black bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700">
                                            👤 {{ $mat->paid_by }}
                                        </span>
                                    @endif

                                    @if(!str_contains($paidByLower, 'caja') && !str_contains($paidByLower, 'empresa'))
                                        <button
                                            type="button"
                                            wire:click="toggleMaterialReimbursed({{ $mat->id }})"
                                            class="block mt-1 text-[9px] font-black px-1.5 py-0.5 rounded border transition cursor-pointer {{ $mat->is_reimbursed ? 'bg-emerald-50 text-emerald-800 border-emerald-300 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-50 text-amber-800 border-amber-300 dark:bg-amber-950 dark:text-amber-300' }}"
                                            title="Pulsar para alternar si este gasto ya fue reembolsado por la cuenta común a {{ $mat->paid_by }}"
                                        >
                                            {{ $mat->is_reimbursed ? '✅ Reembolsado' : '⏳ Pendiente Retorno' }}
                                        </button>
                                    @endif
                                </td>

                                <!-- Método de Pago -->
                                <td class="py-3 px-3 whitespace-nowrap text-slate-600 dark:text-slate-300">
                                    <span class="inline-flex items-center gap-1 font-semibold">
                                        <span>{{ $mat->payment_method_icon }}</span>
                                        <span>{{ $mat->payment_method_label }}</span>
                                    </span>
                                </td>

                                <!-- Importe -->
                                <td class="py-3 px-3 text-right font-black text-amber-600 dark:text-amber-400 whitespace-nowrap text-sm">
                                    {{ number_format($mat->amount, 2, ',', '.') }} €
                                </td>

                                <!-- Acciones -->
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button
                                            type="button"
                                            wire:click="openMaterialModal({{ $mat->id }})"
                                            class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-amber-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-[11px] font-bold transition cursor-pointer"
                                            title="Editar compra de material"
                                        >
                                            ✏️ Editar
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="deleteMaterialExpense({{ $mat->id }})"
                                            wire:confirm="¿Seguro que deseas eliminar esta compra de material?"
                                            class="p-1 rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-xs font-bold transition cursor-pointer"
                                            title="Eliminar registro"
                                        >
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-12 text-center text-slate-400 text-xs">
                                    <div class="text-2xl mb-1">🛠️</div>
                                    <div class="font-bold text-slate-600 dark:text-slate-300">No hay compras de material registradas</div>
                                    <p class="text-[11px] mt-0.5">Pulsa «➕ Registrar Compra de Material» para añadir cables, altavoces, atrezzo fotomatón o cualquier compra deducible.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($materialRows) > 0)
                        <tfoot>
                            <tr class="border-t-2 border-slate-300 dark:border-slate-700 bg-slate-100/90 dark:bg-slate-950 font-black text-xs text-slate-900 dark:text-white">
                                <td class="py-3.5 px-4" colspan="7">
                                    TOTAL GASTOS DE MATERIAL DEDUCIBLES ({{ count($materialRows) }} compras)
                                </td>
                                <td class="py-3.5 px-3 text-right text-amber-600 dark:text-amber-400 whitespace-nowrap text-sm">
                                    {{ number_format($mKpis['total_amount'] ?? 0, 2, ',', '.') }} €
                                </td>
                                <td class="py-3.5 px-3"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
        @endif
    </div>
    @elseif(in_array($viewMode, ['settlement', 'event_settlement'], true))
    <!-- ========================================================== -->
    <!-- PESTAÑA 2: LIQUIDACIÓN Y REPARTO ENTRE SOCIOS              -->
    <!-- ========================================================== -->
        @php
            $sData = $kpis['settlement_summary'] ?? [];
            $settlementPartners = $sData['partners'] ?? [];
        @endphp
        <div class="space-y-6">
            <!-- SUB-BARRA DE NAVEGACIÓN EN REPARTO DE SOCIOS -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white dark:bg-slate-900 p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
                <div class="flex items-center gap-2">
                    <span class="text-[11px] font-black uppercase tracking-wider text-slate-400 pl-1">
                        Ver Reparto:
                    </span>
                    <button
                        type="button"
                        wire:click="$set('viewMode', 'event_settlement')"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5 {{ $viewMode === 'event_settlement' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}"
                    >
                        <span>📑</span>
                        <span>Desglose Evento a Evento ({{ count($eventsRows) }})</span>
                    </button>
                    <button
                        type="button"
                        wire:click="$set('viewMode', 'settlement')"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5 {{ $viewMode === 'settlement' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}"
                    >
                        <span>⚖️</span>
                        <span>Resumen Global & Transferencias</span>
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        wire:click="openPartnersModal"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-slate-100 dark:hover:bg-white text-white dark:text-slate-900 text-xs font-black shadow-xs transition cursor-pointer"
                    >
                        <span>⚙️</span>
                        <span>Configurar Socios y %</span>
                    </button>
                </div>
            </div>

            @if($viewMode === 'event_settlement')
                @include('livewire.admin.billing.partials.event-settlement-view')
            @else
            <!-- VISTA 5: REPARTO GLOBAL Y AJUSTE DE TRANSFERENCIAS ENTRE SOCIOS -->
            <div class="space-y-6">
                <!-- CABECERA Y BOTÓN DE CONFIGURACIÓN -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                        <span>⚖️</span>
                        <span>Liquidación y Reparto de Beneficios entre Socios</span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Cálculo automático y transparente de beneficios netos. El dinero efectivamente cobrado se limpia restando los costes a proveedores (Fotomatón/360º) y compras de material. A cada socio se le abona su cuota (%) más el reembolso de las compras adelantadas de su propio bolsillo.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Selector de Eventos a Repartir -->
                    <div class="flex flex-wrap items-center gap-2 bg-slate-100 dark:bg-slate-950 p-1.5 rounded-2xl border border-slate-200 dark:border-slate-800 text-xs">
                        <span class="text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 pl-2">
                            Base de Cálculo:
                        </span>
                        <button
                            type="button"
                            wire:click="$set('eventStatus', 'completed')"
                            class="px-3 py-1.5 rounded-xl font-black transition cursor-pointer flex items-center gap-1.5 {{ $eventStatus === 'completed' ? 'bg-indigo-600 text-white shadow-sm ring-2 ring-indigo-400/40' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-800' }}"
                            title="Calcular beneficios únicamente de eventos ya completados/realizados"
                        >
                            <span>🎯</span>
                            <span>Solo Eventos Realizados (Completados)</span>
                        </button>
                        <button
                            type="button"
                            wire:click="$set('eventStatus', 'active')"
                            class="px-3 py-1.5 rounded-xl font-black transition cursor-pointer flex items-center gap-1.5 {{ $eventStatus === 'active' ? 'bg-indigo-600 text-white shadow-sm ring-2 ring-indigo-400/40' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-800' }}"
                            title="Calcular beneficios incluyendo señales cobradas de eventos futuros"
                        >
                            <span>🌐</span>
                            <span>Todos con Cobros (Señales + Resto)</span>
                        </button>
                    </div>

                    <button
                        type="button"
                        wire:click="$set('viewMode', 'event_settlement')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black shadow-sm transition cursor-pointer shrink-0"
                        title="Ver desglose detallado de reparto evento a evento"
                    >
                        <span>📑</span>
                        <span>Ver Desglose por Evento</span>
                    </button>

                    <button
                        type="button"
                        wire:click="openPartnersModal"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-slate-100 dark:hover:bg-white text-white dark:text-slate-900 text-xs font-black shadow-sm transition cursor-pointer shrink-0"
                    >
                        <span>⚙️</span>
                        <span>Configurar Socios y %</span>
                    </button>
                </div>
            </div>

            <!-- CUENTA DE RESULTADOS: CASCADA DE INGRESOS Y GASTOS -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs">
                <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-2">
                    <span>📊</span>
                    <span>Cuenta de Resultados del Período / Temporada</span>
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center">
                    <!-- 1. Total Cobrado -->
                    <div class="p-4 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/80">
                        <div class="flex items-center justify-between text-[11px] font-bold text-emerald-800 dark:text-emerald-300">
                            <span>(+) Total Cobrado</span>
                            <span>Ingresos</span>
                        </div>
                        <div class="text-xl font-black text-emerald-700 dark:text-emerald-300 mt-1.5">
                            +{{ number_format($sData['total_collected'] ?? 0, 2, ',', '.') }} €
                        </div>
                        <p class="text-[10px] text-slate-500 mt-1">Señales + liquidaciones finales cobradas</p>
                    </div>

                    <!-- 2. Gasto Proveedores -->
                    <div class="p-4 rounded-2xl bg-purple-50/70 dark:bg-purple-950/30 border border-purple-200 dark:border-purple-800/80">
                        <div class="flex items-center justify-between text-[11px] font-bold text-purple-800 dark:text-purple-300">
                            <span>(-) Gasto Proveedores</span>
                            <span>Fotomatón / 360º / Artístamente</span>
                        </div>
                        <div class="text-xl font-black text-purple-700 dark:text-purple-300 mt-1.5">
                            -{{ number_format($sData['provider_expenses'] ?? 0, 2, ',', '.') }} €
                        </div>
                        @if(!empty($sData['provider_refunds']) && $sData['provider_refunds'] > 0)
                            <p class="text-[10px] text-amber-700 dark:text-amber-300 mt-1 font-bold">
                                Facturas: {{ number_format($sData['gross_provider_expenses'] ?? 0, 2, ',', '.') }} € | Devoluciones: +{{ number_format($sData['provider_refunds'], 2, ',', '.') }} €
                            </p>
                        @else
                            <p class="text-[10px] text-slate-500 mt-1">Coste neto externo en los eventos</p>
                        @endif
                    </div>

                    <!-- 3. Gasto Material -->
                    <div class="p-4 rounded-2xl bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/80">
                        <div class="flex items-center justify-between text-[11px] font-bold text-amber-800 dark:text-amber-300">
                            <span>(-) Compras Material</span>
                            <span>Deducibles</span>
                        </div>
                        <div class="text-xl font-black text-amber-700 dark:text-amber-400 mt-1.5">
                            -{{ number_format($sData['material_expenses'] ?? 0, 2, ',', '.') }} €
                        </div>
                        <p class="text-[10px] text-slate-500 mt-1">Cables, luces, atrezzo, consumibles...</p>
                    </div>

                    <!-- 4. Beneficio Neto Disponible -->
                    <div class="p-4 rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-white shadow-md">
                        <div class="flex items-center justify-between text-[11px] font-extrabold text-indigo-100">
                            <span>(=) BENEFICIO NETO</span>
                            <span>A Repartir</span>
                        </div>
                        <div class="text-2xl font-black text-white mt-1.5">
                            {{ number_format($sData['net_profit'] ?? 0, 2, ',', '.') }} €
                        </div>
                        <p class="text-[10px] text-indigo-100/90 mt-1">Base limpia repartible al 100%</p>
                    </div>
                </div>
            </div>

            <!-- ARQUEO DE CUENTAS SEPARADAS Y AJUSTE DE TRANSFERENCIAS ENTRE SOCIOS -->
            @php
                $tInst = $sData['transfer_instruction'] ?? null;
                $colFran = (float)($sData['collected_by_partner']['Fran'] ?? 0);
                $colMiguel = (float)($sData['collected_by_partner']['Miguel'] ?? 0);
                $colJoint = (float)($sData['joint_collected'] ?? 0);
            @endphp
            <div class="bg-gradient-to-br from-slate-900 to-indigo-950 text-white rounded-3xl border border-indigo-800/60 p-6 shadow-lg space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-indigo-800/60 pb-4">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-wider text-indigo-300 bg-indigo-950 px-2.5 py-1 rounded-lg border border-indigo-700/60">
                            🏦 Cuentas Separadas & Arqueo de Caja
                        </span>
                        <h4 class="text-base font-black text-white mt-1.5 flex items-center gap-2">
                            <span>⚖️</span>
                            <span>Ajuste de Cuentas y Transferencias entre Socios</span>
                        </h4>
                        <p class="text-xs text-indigo-200/80 mt-0.5">
                            ¿Cuánto dinero tiene cada socio en su cuenta o Bizum vs. lo que le corresponde tras gastos adelantados?
                        </p>
                    </div>
                    <div class="flex items-center gap-2 bg-indigo-950/80 px-3.5 py-2 rounded-2xl border border-indigo-700/60">
                        <span class="text-xs text-indigo-200">Total cobrado:</span>
                        <span class="font-black text-emerald-400 text-sm">{{ number_format($sData['total_collected'] ?? 0, 2, ',', '.') }} €</span>
                    </div>
                </div>

                <!-- TARJETAS DINÁMICAS POR SOCIO (FRAN, MIGUEL, EZEQUIEL...) + FONDO COMÚN -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-{{ min(4, count($settlementPartners) + 1) }} gap-4">
                    @foreach($settlementPartners as $pRow)
                        @php
                            $pName = $pRow['name'];
                            $pCol = (float)($pRow['collected_in_hand'] ?? ($sData['collected_by_partner'][$pName] ?? 0));
                        @endphp
                        <div class="p-4 rounded-2xl bg-indigo-900/40 border border-indigo-700/50">
                            <div class="flex items-center justify-between text-xs text-indigo-300 font-bold">
                                <span>👤 Cuenta / Bizum {{ $pName }}</span>
                                <span class="text-[10px] bg-indigo-800/80 px-2 py-0.5 rounded text-white">En mano</span>
                            </div>
                            <div class="text-2xl font-black text-white mt-1.5">
                                {{ number_format($pCol, 2, ',', '.') }} €
                            </div>
                            <p class="text-[10px] text-indigo-300/80 mt-1">Cobros de eventos ingresados a {{ $pName }}</p>
                        </div>
                    @endforeach

                    <!-- Cuenta Conjunta / Fondo Común -->
                    <div class="p-4 rounded-2xl bg-indigo-900/40 border border-indigo-700/50">
                        <div class="flex items-center justify-between text-xs text-indigo-300 font-bold">
                            <span>🏦 Fondo Común / Empresa</span>
                            <span class="text-[10px] bg-emerald-800/80 px-2 py-0.5 rounded text-emerald-200">Común</span>
                        </div>
                        <div class="text-2xl font-black text-emerald-400 mt-1.5">
                            {{ number_format($colJoint, 2, ',', '.') }} €
                        </div>
                        <p class="text-[10px] text-indigo-300/80 mt-1">Dinero ya centralizado en caja común</p>
                    </div>
                </div>

                <!-- INSTRUCCIÓN DIRECTA DE TRANSFERENCIA -->
                @if(!empty($tInst['has_transfer']))
                    <div class="p-4.5 rounded-2xl bg-emerald-950/70 border-2 border-emerald-500/80 text-white flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center text-2xl font-black shadow-md shrink-0">
                                💸
                            </div>
                            <div class="space-y-1">
                                <span class="text-[10px] font-black uppercase tracking-wider text-emerald-300 block">
                                    INSTRUCCIÓN DE AJUSTE EXACTO ENTRE SOCIOS
                                </span>
                                @if(!empty($tInst['list']) && count($tInst['list']) > 1)
                                    <div class="space-y-1 text-sm font-black text-white mt-0.5">
                                        @foreach($tInst['list'] as $itemInst)
                                            <div class="flex items-center gap-2">
                                                <span class="text-emerald-400">&bull;</span>
                                                <span>{{ $itemInst['text'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-sm sm:text-base font-black text-white mt-0.5">
                                        {{ $tInst['text'] }}
                                    </div>
                                @endif
                                <p class="text-[11px] text-emerald-200/80 mt-0.5">
                                    Realizando estas transferencias de compensación, las cuentas de los socios quedan cuadradas al céntimo según sus porcentajes y gastos adelantados.
                                </p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="px-3.5 py-1.5 rounded-xl bg-emerald-500 text-slate-950 text-xs font-black shadow-sm">
                                Total Ajuste: {{ number_format($tInst['amount'], 2, ',', '.') }} €
                            </span>
                        </div>
                    </div>
                @else
                    <div class="p-4 rounded-2xl bg-indigo-950/80 border border-indigo-700/60 text-indigo-200 text-xs flex items-center gap-3">
                        <span class="text-xl">✅</span>
                        <div>
                            <strong>Cuentas cuadradas:</strong> {{ $tInst['text'] ?? 'No se requiere transferencia de ajuste entre socios en este período.' }}
                        </div>
                    </div>
                @endif
            </div>

            <!-- TARJETAS DESTACADAS DE LIQUIDACIÓN POR SOCIO -->
            <div class="grid grid-cols-1 md:grid-cols-{{ min(3, max(2, count($settlementPartners))) }} gap-5">
                @forelse($settlementPartners as $partner)
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border-2 border-slate-200 dark:border-slate-800 p-6 shadow-sm hover:shadow-md transition relative overflow-hidden">
                        <!-- Banda superior decorativa -->
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-indigo-100 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300 flex items-center justify-center font-black text-lg shadow-2xs">
                                    👤
                                </div>
                                <div>
                                    <h4 class="text-base font-black text-slate-900 dark:text-white">
                                        {{ $partner['name'] }}
                                    </h4>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-black bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300">
                                        Participación: {{ $partner['percentage'] }}%
                                    </span>
                                </div>
                            </div>
                            @if(($partner['balance_diff'] ?? 0) > 0.05)
                                <span class="px-2.5 py-1 rounded-xl bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300 text-xs font-black">
                                    Debe transferir {{ number_format($partner['balance_diff'], 2, ',', '.') }} €
                                </span>
                            @elseif(($partner['balance_diff'] ?? 0) < -0.05)
                                <span class="px-2.5 py-1 rounded-xl bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 text-xs font-black">
                                    Debe recibir {{ number_format(abs($partner['balance_diff']), 2, ',', '.') }} €
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-black">
                                    En paz (0,00 €)
                                </span>
                            @endif
                        </div>

                        <!-- Desglose de importes -->
                        <div class="py-4 space-y-2.5 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 dark:text-slate-400">Cuota de Beneficio ({{ $partner['percentage'] }}% de {{ number_format($sData['net_profit'] ?? 0, 2, ',', '.') }}€):</span>
                                <strong class="text-slate-800 dark:text-slate-200 font-bold text-sm">
                                    {{ number_format($partner['profit_share'], 2, ',', '.') }} €
                                </strong>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                    <span>🛠️ Reembolso compras material:</span>
                                </span>
                                <strong class="text-amber-600 dark:text-amber-400 font-bold text-sm">
                                    +{{ number_format($partner['material_reimbursement'] ?? $partner['reimbursement'] ?? 0, 2, ',', '.') }} €
                                </strong>
                            </div>

                            @if(($partner['provider_reimbursement'] ?? 0) > 0)
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                        <span>🎭 Reembolso proveedores adelantados (Artístamente, etc.):</span>
                                    </span>
                                    <strong class="text-purple-600 dark:text-purple-400 font-bold text-sm">
                                        +{{ number_format($partner['provider_reimbursement'], 2, ',', '.') }} €
                                    </strong>
                                </div>
                            @endif

                            <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
                                <div>
                                    <span class="text-slate-500 dark:text-slate-400 font-semibold block">💵 Dinero total en su cuenta / Bizum:</span>
                                    @if(($partner['refund_received'] ?? 0) > 0)
                                        <span class="text-[10px] text-amber-600 dark:text-amber-400 font-semibold">
                                            Clientes: {{ number_format($partner['client_collected'] ?? 0, 2, ',', '.') }} € &bull; Devolución Artístamente: +{{ number_format($partner['refund_received'], 2, ',', '.') }} €
                                        </span>
                                    @endif
                                </div>
                                <strong class="text-indigo-600 dark:text-indigo-400 font-extrabold text-sm">
                                    {{ number_format($partner['collected_in_hand'] ?? 0, 2, ',', '.') }} €
                                </strong>
                            </div>

                            @if(($partner['reimbursement'] ?? 0) > 0)
                                <div class="text-[10px] text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/40 p-2 rounded-xl">
                                    💡 Se le reembolsan <strong>{{ number_format($partner['reimbursement'], 2, ',', '.') }} €</strong> por gastos que adelantó de su bolsillo (material y proveedores).
                                </div>
                            @endif
                        </div>

                        <!-- TOTAL A PERCIBIR EN GRANDE -->
                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between bg-emerald-50/50 dark:bg-emerald-950/30 -mx-6 -mb-6 p-6 rounded-b-3xl">
                            <div>
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-800 dark:text-emerald-300 block">
                                    TOTAL NETO A PERCIBIR
                                </span>
                                <span class="text-[11px] text-slate-500">Beneficio + Reembolsos</span>
                            </div>
                            <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">
                                {{ number_format($partner['total_to_receive'], 2, ',', '.') }} €
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-2 py-10 text-center text-slate-400 text-xs">
                        No hay socios configurados. Pulsa «⚙️ Configurar Socios y %» para definir los socios.
                    </div>
                @endforelse
            </div>

            <!-- CUADRO AUDITOR DE LIQUIDACIÓN Y REPARTO -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-950/50">
                    <h4 class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-wider">
                        📋 Cuadro Resumen de Liquidación y Auditoría
                    </h4>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-[10px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-950">
                                <th class="py-3 px-4">Socio</th>
                                <th class="py-3 px-2 text-center">% Reparto</th>
                                <th class="py-3 px-3 text-right">Beneficio Neto</th>
                                <th class="py-3 px-3 text-right">Reemb. Material</th>
                                <th class="py-3 px-3 text-right">Reemb. Proveedores</th>
                                <th class="py-3 px-3 text-right">TOTAL A PERCIBIR</th>
                                <th class="py-3 px-3 text-right">Cobrado en Mano</th>
                                <th class="py-3 px-3 text-center">Ajuste de Arqueo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            @foreach($settlementPartners as $partner)
                                <tr>
                                    <td class="py-3 px-4 font-black text-slate-900 dark:text-white whitespace-nowrap">
                                        👤 {{ $partner['name'] }}
                                    </td>
                                    <td class="py-3 px-2 text-center font-bold text-slate-600 dark:text-slate-300">
                                        {{ $partner['percentage'] }}%
                                    </td>
                                    <td class="py-3 px-3 text-right font-bold text-slate-800 dark:text-slate-200 whitespace-nowrap">
                                        {{ number_format($partner['profit_share'], 2, ',', '.') }} €
                                    </td>
                                    <td class="py-3 px-3 text-right font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap">
                                        {{ number_format($partner['material_reimbursement'] ?? 0, 2, ',', '.') }} €
                                    </td>
                                    <td class="py-3 px-3 text-right font-bold text-purple-600 dark:text-purple-400 whitespace-nowrap">
                                        {{ number_format($partner['provider_reimbursement'] ?? 0, 2, ',', '.') }} €
                                    </td>
                                    <td class="py-3 px-3 text-right font-black text-sm text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                        {{ number_format($partner['total_to_receive'], 2, ',', '.') }} €
                                    </td>
                                    <td class="py-3 px-3 text-right font-extrabold text-indigo-600 dark:text-indigo-400 whitespace-nowrap">
                                        {{ number_format($partner['collected_in_hand'] ?? 0, 2, ',', '.') }} €
                                    </td>
                                    <td class="py-3 px-3 text-center whitespace-nowrap">
                                        @if(($partner['balance_diff'] ?? 0) > 0.05)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800">
                                                Transfiere {{ number_format($partner['balance_diff'], 2, ',', '.') }} €
                                            </span>
                                        @elseif(($partner['balance_diff'] ?? 0) < -0.05)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800">
                                                Recibe {{ number_format(abs($partner['balance_diff']), 2, ',', '.') }} €
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-100 text-slate-700">
                                                En paz (0,00 €)
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-slate-300 dark:border-slate-700 bg-slate-100/90 dark:bg-slate-950 font-black text-xs text-slate-900 dark:text-white">
                                <td class="py-3.5 px-4" colspan="2">TOTALES AUDITORÍA</td>
                                <td class="py-3.5 px-3 text-right text-indigo-700 dark:text-indigo-300 whitespace-nowrap">
                                    {{ number_format($sData['net_profit'] ?? 0, 2, ',', '.') }} €
                                </td>
                                <td class="py-3.5 px-3 text-right text-amber-600 dark:text-amber-400 whitespace-nowrap">
                                    {{ number_format(array_sum(array_column($settlementPartners, 'material_reimbursement')), 2, ',', '.') }} €
                                </td>
                                <td class="py-3.5 px-3 text-right text-purple-600 dark:text-purple-400 whitespace-nowrap">
                                    {{ number_format(array_sum(array_column($settlementPartners, 'provider_reimbursement')), 2, ',', '.') }} €
                                </td>
                                <td class="py-3.5 px-3 text-right text-emerald-600 dark:text-emerald-400 text-sm whitespace-nowrap">
                                    {{ number_format(array_sum(array_column($settlementPartners, 'total_to_receive')), 2, ',', '.') }} €
                                </td>
                                <td class="py-3.5 px-3 text-right text-indigo-600 dark:text-indigo-400 whitespace-nowrap">
                                    {{ number_format(array_sum(array_column($settlementPartners, 'collected_in_hand')), 2, ',', '.') }} €
                                </td>
                                <td class="py-3.5 px-3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="p-4 bg-slate-50 dark:bg-slate-950/70 border-t border-slate-200 dark:border-slate-800 text-[11px] text-slate-500 space-y-1">
                    <p>💡 <strong>Cuadre exacto de caja:</strong> La suma de lo que percibe cada socio más las compras de material realizadas desde caja común de empresa y los pagos a proveedores externos equivale exactamente a los {{ number_format($sData['total_collected'] ?? 0, 2, ',', '.') }} € ingresados en el período.</p>
                </div>
            </div>
            @endif
        </div>
    @elseif($viewMode === 'joint_account')
        @include('livewire.admin.billing.partials.joint-account-view')
    @endif

    <!-- ========================================================== -->
    <!-- MODALES MODULARIZADOS (CLEAN ARCHITECTURE) -->
    <!-- ========================================================== -->
    @include('livewire.admin.billing.partials.modals.quick-payment-modal')
    @include('livewire.admin.billing.partials.modals.provider-modal')
    @include('livewire.admin.billing.partials.modals.material-modal')
    @include('livewire.admin.billing.partials.modals.partners-modal')
    @include('livewire.admin.billing.partials.modals.event-split-modal')
</div>
