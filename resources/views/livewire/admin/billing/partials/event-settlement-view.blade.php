{{-- ========================================================== --}}
{{-- VISTA 6: DESGLOSE DE REPARTO E INGRESOS POR EVENTO       --}}
{{-- ========================================================== --}}
@php
    $sData = $kpis['settlement_summary'] ?? [];
    $evTotals = $kpis['events_settlement_totals'] ?? [];
    $settlementPartners = $sData['partners'] ?? [];
@endphp

<div class="space-y-6">
    <!-- CABECERA Y ACCIONES RÁPIDAS -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-5 sm:p-6 shadow-xs space-y-4">
        <!-- TÍTULO Y DESCRIPCIÓN -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1.5 flex-1 min-w-0">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-black bg-indigo-50 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                    <span>📑</span>
                    <span>Auditoría Evento a Evento</span>
                </div>
                <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>⚖️</span>
                    <span>Desglose de Reparto e Ingresos por Evento</span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed max-w-4xl">
                    Aquí ves exactamente el viaje de cada euro: cuánto pagó el cliente de cada evento, qué costes externos tuvo (Fotomatón, cooperativa, compras de material), el beneficio neto limpio que dejó y cómo se reparte entre los socios (o si se apartó para <strong>Fondo Común</strong> para cenas o material).
                </p>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <button
                    type="button"
                    wire:click="openPartnersModal"
                    class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-slate-100 dark:hover:bg-white text-white dark:text-slate-900 text-xs font-black shadow-xs transition cursor-pointer"
                >
                    <span>⚙️</span>
                    <span>Configurar Socios y %</span>
                </button>
                <button
                    type="button"
                    wire:click="$set('viewMode', 'settlement')"
                    class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/70 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-black shadow-xs hover:bg-emerald-100 dark:hover:bg-emerald-900 transition cursor-pointer"
                >
                    <span>📊</span>
                    <span>Ver Transferencias</span>
                </button>
            </div>
        </div>

        <!-- FILTROS: BASE, TEMPORADA/AÑO Y ORDEN CRONOLÓGICO -->
        <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <!-- Selector de Base de Cálculo -->
                <div class="flex flex-wrap items-center gap-1.5 bg-slate-100 dark:bg-slate-950 p-1.5 rounded-2xl border border-slate-200 dark:border-slate-800 text-xs">
                    <span class="text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 pl-2 pr-1">
                        Base:
                    </span>
                    <button
                        type="button"
                        wire:click="$set('eventStatus', 'completed')"
                        class="px-3 py-1.5 rounded-xl font-black transition cursor-pointer flex items-center gap-1.5 {{ $eventStatus === 'completed' ? 'bg-indigo-600 text-white shadow-sm ring-2 ring-indigo-400/40' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-800' }}"
                        title="Ver solo eventos ya realizados"
                    >
                        <span>🎯</span>
                        <span>Solo Realizados</span>
                    </button>
                    <button
                        type="button"
                        wire:click="$set('eventStatus', 'active')"
                        class="px-3 py-1.5 rounded-xl font-black transition cursor-pointer flex items-center gap-1.5 {{ $eventStatus === 'active' ? 'bg-indigo-600 text-white shadow-sm ring-2 ring-indigo-400/40' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-800' }}"
                        title="Ver todos los eventos con algún cobro"
                    >
                        <span>🌐</span>
                        <span>Todos con Cobros</span>
                    </button>
                </div>

                <!-- Selector de Temporada / Año -->
                <div class="flex flex-wrap items-center gap-1 bg-slate-100 dark:bg-slate-950 p-1.5 rounded-2xl border border-slate-200 dark:border-slate-800 text-xs">
                    <span class="text-[11px] font-black uppercase tracking-wider text-indigo-700 dark:text-indigo-400 pl-2 pr-1 flex items-center gap-1">
                        <span>💍</span>
                        <span>Año:</span>
                    </span>
                    <button
                        type="button"
                        wire:click="$set('season', 'all')"
                        class="px-2.5 py-1.5 rounded-xl font-black transition cursor-pointer flex items-center gap-1 {{ $season === 'all' ? 'bg-indigo-600 text-white shadow-xs ring-2 ring-indigo-400/40' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-800' }}"
                    >
                        🌍 Todos
                    </button>
                    @foreach($availableSeasons as $sy)
                        <button
                            type="button"
                            wire:click="$set('season', '{{ $sy }}')"
                            class="px-2.5 py-1.5 rounded-xl font-black transition cursor-pointer flex items-center gap-1 {{ (string)$season === (string)$sy ? 'bg-indigo-600 text-white shadow-xs ring-2 ring-indigo-400/40' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-800' }}"
                        >
                            {{ $sy }}
                        </button>
                    @endforeach
                </div>

                <!-- Selector de Orden Cronológico -->
                <div class="flex flex-wrap items-center gap-1 bg-slate-100 dark:bg-slate-950 p-1.5 rounded-2xl border border-slate-200 dark:border-slate-800 text-xs">
                    <span class="text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 pl-2 pr-1">
                        Orden:
                    </span>
                    <button
                        type="button"
                        wire:click="$set('eventSortOrder', 'event_date_asc')"
                        class="px-3 py-1.5 rounded-xl font-black transition cursor-pointer flex items-center gap-1.5 {{ ($eventSortOrder ?? 'event_date_asc') === 'event_date_asc' ? 'bg-indigo-600 text-white shadow-xs ring-2 ring-indigo-400/40' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-800' }}"
                        title="Cronológico: del más cercano/antiguo hacia el futuro (Nov 2026 ➔ Dic 2026 ➔ Enero 2027...)"
                    >
                        <span>📅</span>
                        <span>Cronológico (Nov 26 ➔ 2027)</span>
                    </button>
                    <button
                        type="button"
                        wire:click="$set('eventSortOrder', 'event_date_desc')"
                        class="px-3 py-1.5 rounded-xl font-black transition cursor-pointer flex items-center gap-1 {{ ($eventSortOrder ?? '') === 'event_date_desc' ? 'bg-indigo-600 text-white shadow-xs ring-2 ring-indigo-400/40' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-800' }}"
                        title="Inverso: eventos más lejanos en el futuro primero"
                    >
                        <span>⏳</span>
                        <span>Más Lejanos primero</span>
                    </button>
                </div>
            </div>

            <span class="text-[11px] text-slate-400 font-semibold">
                Mostrando <strong>{{ count($eventsRows) }}</strong> eventos
            </span>
        </div>
    </div>

    <!-- TARJETAS DE TOTALES Y BENEFICIO ACUMULADO POR EVENTOS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
        <!-- 1. Total Cobrado -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">
                (+) Total Cobrado Eventos
            </span>
            <div class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                +{{ number_format($evTotals['total_collected'] ?? 0, 2, ',', '.') }} €
            </div>
            <p class="text-[10px] text-slate-500 mt-0.5">En {{ count($eventsRows) }} eventos del período</p>
        </div>

        <!-- 2. Costes Externos -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">
                (-) Costes Directos
            </span>
            <div class="text-xl sm:text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">
                -{{ number_format($evTotals['total_costs'] ?? 0, 2, ',', '.') }} €
            </div>
            <p class="text-[10px] text-slate-500 mt-0.5">
                Proveedores: {{ number_format($evTotals['total_provider_costs'] ?? 0, 2, ',', '.') }}€ · Material: {{ number_format($evTotals['total_material_costs'] ?? 0, 2, ',', '.') }}€
            </p>
        </div>

        <!-- 3. Beneficio Neto Generado -->
        <div class="p-4 rounded-2xl bg-gradient-to-br from-indigo-600 to-indigo-800 text-white shadow-sm">
            <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-100 block">
                (=) Beneficio Limpio Total
            </span>
            <div class="text-xl sm:text-2xl font-black text-white mt-1">
                {{ number_format($evTotals['total_net_profit'] ?? 0, 2, ',', '.') }} €
            </div>
            <p class="text-[10px] text-indigo-200 mt-0.5">Margen real de los eventos</p>
        </div>

        <!-- 4. Destinado a Fondo Común -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">
                🏢 Destinado a Fondo Común
            </span>
            <div class="text-xl sm:text-2xl font-black text-teal-600 dark:text-teal-400 mt-1">
                {{ number_format($evTotals['total_joint_fund'] ?? 0, 2, ',', '.') }} €
            </div>
            <p class="text-[10px] text-slate-500 mt-0.5">Cena empresa / compras comunes</p>
        </div>

        <!-- 5. Repartible entre Socios -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">
                👥 Beneficio para Socios
            </span>
            <div class="text-xl sm:text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1">
                {{ number_format($evTotals['total_partner_profit'] ?? 0, 2, ',', '.') }} €
            </div>
            <div class="flex flex-wrap items-center gap-1.5 mt-1 text-[10px] font-bold text-slate-600 dark:text-slate-300">
                @foreach($evTotals['by_partner'] ?? [] as $pRow)
                    <span class="px-1.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300">
                        {{ $pRow['name'] }}: {{ number_format($pRow['profit_share'], 2, ',', '.') }}€
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    <!-- LISTADO DE EVENTOS CON DESGLOSE COMPLETO -->
    <div class="space-y-4">
        @forelse($eventsRows as $row)
            @php
                /** @var \App\Models\Event $ev */
                $ev = $row['event'];
                $fb = $row['fb'];
                $sett = $row['settlement'] ?? [];
                $evPartners = $sett['partners'] ?? [];
                $isFondo = !empty($sett['is_fondo_comun']);
                $dateFormatted = $ev->event_date ? \Carbon\Carbon::parse($ev->event_date)->translatedFormat('d \d\e F \d\e Y') : 'Fecha no definida';
                $clientName = $ev->client?->name ?? 'Sin cliente asignado';
            @endphp

            <div class="bg-white dark:bg-slate-900 rounded-3xl border-2 {{ $isFondo ? 'border-teal-500/50 dark:border-teal-600/50' : 'border-slate-200 dark:border-slate-800' }} p-5 sm:p-6 shadow-xs hover:shadow-md transition">
                <!-- CABECERA DEL EVENTO -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div class="flex items-start gap-3">
                        <div class="w-11 h-11 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xl shrink-0">
                            {{ $ev->event_type_icon ?: '🎉' }}
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">
                                    {{ $ev->name }}
                                </h4>
                                <!-- Badge Marca -->
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    {{ $ev->brand_label ?: 'Marca' }}
                                </span>
                                <!-- Badge Estado -->
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $ev->status === 'completed' ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300' : 'bg-indigo-100 dark:bg-indigo-950 text-indigo-800 dark:text-indigo-300' }}">
                                    {{ $ev->status_label ?: ucfirst($ev->status) }}
                                </span>
                                @if($isFondo)
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-teal-100 dark:bg-teal-950 text-teal-800 dark:text-teal-300 border border-teal-300 dark:border-teal-700">
                                        🏢 100% Fondo Común
                                    </span>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 dark:text-slate-400 mt-1">
                                <span class="flex items-center gap-1 font-semibold text-slate-700 dark:text-slate-300">
                                    <span>📅</span>
                                    <span>{{ $dateFormatted }}</span>
                                </span>
                                <span>&bull;</span>
                                <span>👤 Cliente: <strong>{{ $clientName }}</strong></span>
                                <span>&bull;</span>
                                <span>🎧 <strong>{{ $sett['staff_names'] ?? ($ev->dj?->name ?: 'DJ Titular') }}</strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- Enlace directo a la ficha del evento -->
                    <div class="flex items-center gap-2 shrink-0">
                        <a
                            href="{{ route('admin.events.show', $ev->id) }}"
                            target="_blank"
                            class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition flex items-center gap-1"
                            title="Abrir ficha completa del evento"
                        >
                            <span>Abrir Evento</span>
                            <span>↗</span>
                        </a>
                    </div>
                </div>

                <!-- CUERPO PRINCIPAL EN 3 COLUMNAS: INGRESOS & COSTES | EN MANO | REPARTO -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 pt-4 items-start">
                    <!-- COLUMNA 1: FLUJO ECONÓMICO (4 COLS) -->
                    <div class="lg:col-span-4 space-y-3 bg-slate-50 dark:bg-slate-950/60 p-4 rounded-2xl border border-slate-200 dark:border-slate-800">
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">
                            💰 1. Ingresos y Costes del Evento
                        </span>

                        <div class="space-y-1.5 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">PVP Presupuestado:</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">
                                    {{ number_format($sett['quote_total'] ?? 0, 2, ',', '.') }} €
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Efectivamente Cobrado:</span>
                                <span class="font-extrabold text-emerald-600 dark:text-emerald-400">
                                    +{{ number_format($sett['total_collected'] ?? 0, 2, ',', '.') }} €
                                </span>
                            </div>

                            @if(($sett['pending_amount'] ?? 0) > 0.05)
                                <div class="flex items-center justify-between text-[11px] text-amber-600 dark:text-amber-400 font-semibold">
                                    <span>Pendiente de Cobro:</span>
                                    <span>{{ number_format($sett['pending_amount'], 2, ',', '.') }} €</span>
                                </div>
                            @endif

                            <!-- Costes Proveedor Externo -->
                            @if(!empty($sett['has_provider']))
                                @php
                                    $pName = trim((string)($sett['provider_name'] ?? ''));
                                    $pService = trim((string)($sett['provider_service'] ?? ''));
                                    $pTitle = $pService ?: ($pName ?: 'Proveedor Externo');
                                    if ($pTitle === 'Fotomatón / Plataforma 360º' && !empty($pName) && !str_contains(mb_strtolower($pName), 'logroño') && !str_contains(mb_strtolower($pName), 'vitoria')) {
                                        $pTitle = $pName;
                                    }
                                    $pTitleLower = mb_strtolower($pTitle);
                                    $pIcon = (str_contains($pTitleLower, 'artist') || str_contains($pTitleLower, 'artíst'))
                                        ? '🎨'
                                        : ((str_contains($pTitleLower, 'foto') || str_contains($pTitleLower, '360')) ? '📸' : '💼');
                                @endphp
                                <div class="pt-2 border-t border-slate-200 dark:border-slate-800">
                                    <div class="flex items-center justify-between text-purple-700 dark:text-purple-300 font-semibold">
                                        <span>{{ $pIcon }} {{ $pTitle }}:</span>
                                        <span>-{{ number_format($sett['provider_net_cost'] ?? 0, 2, ',', '.') }} €</span>
                                    </div>
                                    @if($sett['provider_paid'] > 0)
                                        <div class="flex items-center justify-between text-[10px] text-slate-400 mt-1">
                                            @if(!empty($sett['provider_is_partner_pocket']))
                                                <span>Adelantado por: <strong>{{ $sett['provider_paid_by'] ?: 'Fran' }}</strong></span>
                                                @if(!empty($sett['provider_booking_id']))
                                                    @php
                                                        $rType = $sett['provider_reimbursement_type'] ?? 'assumed';
                                                    @endphp
                                                    <button
                                                        type="button"
                                                        wire:click="toggleProviderReimbursed({{ $sett['provider_booking_id'] }})"
                                                        class="px-1.5 py-0.5 rounded text-[9px] font-black transition cursor-pointer border {{ $rType === 'assumed' ? 'bg-indigo-100 text-indigo-800 border-indigo-300 dark:bg-indigo-950 dark:text-indigo-300' : (!empty($sett['provider_is_reimbursed']) ? 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-950 dark:text-amber-300') }}"
                                                        title="Pulsar para alternar: 👤 Coste Asumido (-{{ number_format($sett['provider_paid'], 2, ',', '.') }}€) ➔ ⏳ Pendiente Retorno (+{{ number_format($sett['provider_paid'], 2, ',', '.') }}€) ➔ ✅ Reembolsado"
                                                    >
                                                        @if($rType === 'assumed')
                                                            👤 Asumido por {{ $sett['provider_paid_by'] ?: 'Socio' }} (-{{ number_format($sett['provider_paid'], 2, ',', '.') }}€)
                                                        @elseif(!empty($sett['provider_is_reimbursed']))
                                                            ✅ Reembolsado (+{{ number_format($sett['provider_paid'], 2, ',', '.') }}€)
                                                        @else
                                                            ⏳ Pendiente Retorno (+{{ number_format($sett['provider_paid'], 2, ',', '.') }}€)
                                                        @endif
                                                    </button>
                                                @endif
                                            @else
                                                <span class="text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
                                                    <span>🏦</span>
                                                    <span>Pagado con Fondos Comunes ({{ $sett['provider_paid_by'] ?: 'Caja Común / Empresa' }})</span>
                                                </span>
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                    ✅ Restado en Banco
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <div class="flex items-center justify-between text-[10px] text-amber-600 dark:text-amber-400 mt-1">
                                            <span class="font-bold flex items-center gap-1">
                                                <span>⏳</span>
                                                <span>Coste previsto (Pendiente de pagar a empresa: {{ number_format($sett['provider_net_cost'] ?? 0, 2, ',', '.') }} €)</span>
                                            </span>
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-black bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                                ⏳ Aún NO pagado (0 € restado de banco)
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <!-- Costes de Empresa, Cenas y Material Imputados -->
                            @if(!empty($sett['has_material']))
                                <div class="pt-2 border-t border-slate-200 dark:border-slate-800 space-y-1">
                                    <div class="flex items-center justify-between text-amber-700 dark:text-amber-400 font-semibold">
                                        <span>🛠️ Gastos Empresa / Material:</span>
                                        <span>-{{ number_format($sett['material_cost'] ?? 0, 2, ',', '.') }} €</span>
                                    </div>
                                    @foreach($sett['material_items'] ?? [] as $mItem)
                                        <div class="text-[10px] flex items-center justify-between text-slate-600 dark:text-slate-300 gap-1.5 py-0.5">
                                            <span class="truncate max-w-[170px]" title="{{ $mItem->concept }}">
                                                {{ $mItem->category === 'cena_empresa' ? '🥂' : ($mItem->category === 'team_dietas' ? '🍽️' : '📦') }}
                                                {{ $mItem->concept }}
                                            </span>
                                            <div class="flex items-center gap-1.5 shrink-0">
                                                <span class="font-bold text-amber-700 dark:text-amber-400">-{{ number_format($mItem->amount, 2, ',', '.') }}€</span>
                                                <button
                                                    type="button"
                                                    wire:click="openMaterialModal({{ $mItem->id }})"
                                                    class="text-[10px] text-amber-700 dark:text-amber-400 hover:text-amber-600 font-bold cursor-pointer p-0.5"
                                                    title="Editar este gasto"
                                                >
                                                    ✏️
                                                </button>
                                                <button
                                                    type="button"
                                                    wire:click="deleteMaterialExpense({{ $mItem->id }})"
                                                    wire:confirm="¿Seguro que deseas eliminar este gasto de {{ number_format($mItem->amount, 2, ',', '.') }} €?"
                                                    class="text-[10px] text-rose-500 hover:text-rose-700 font-bold cursor-pointer p-0.5"
                                                    title="Eliminar este gasto"
                                                >
                                                    🗑️
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if(empty($sett['has_provider']) && empty($sett['has_material']))
                                <div class="text-[10px] text-slate-400 italic pt-1">
                                    Sin costes externos imputados (100% margen).
                                </div>
                            @endif

                            <!-- Botón para Imputar Gasto de Empresa / Cena con este evento -->
                            <div class="pt-1.5">
                                <button
                                    type="button"
                                    wire:click="openExpenseForEvent({{ $ev->id }}, {{ $sett['net_profit'] > 0 ? $sett['net_profit'] : $fb['total_paid'] }})"
                                    class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 dark:text-amber-300 bg-amber-50/80 dark:bg-amber-950/50 hover:bg-amber-100 px-2 py-0.5 rounded-lg border border-amber-200 dark:border-amber-800 transition cursor-pointer"
                                    title="Imputar cena de navidad, comida de equipo o compra técnica con los ingresos de este evento"
                                >
                                    <span>🍽️</span>
                                    <span>Imputar Gasto / Cena de Empresa</span>
                                </button>
                            </div>
                        </div>

                        <!-- RESULTADO NETO LIMPIO DEL EVENTO -->
                        <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <span class="text-xs font-black uppercase text-slate-700 dark:text-slate-300">
                                Beneficio Neto:
                            </span>
                            <span class="text-base font-black text-indigo-600 dark:text-indigo-400">
                                {{ number_format($sett['net_profit'] ?? 0, 2, ',', '.') }} €
                            </span>
                        </div>
                    </div>

                    <!-- COLUMNA 2: DINERO COBRADO EN MANO Y REASIGNACIÓN (4 COLS) -->
                    <div class="lg:col-span-4 space-y-3 bg-slate-50 dark:bg-slate-950/60 p-4 rounded-2xl border border-slate-200 dark:border-slate-800">
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">
                            🏦 2. ¿Quién tiene este dinero cobrado?
                        </span>

                        <!-- Desglose de quién cobró -->
                        <div class="space-y-2">
                            @php
                                $colList = $sett['collected_by_partner'] ?? [];
                            @endphp

                            @if(empty($colList) || ($sett['total_collected'] ?? 0) <= 0)
                                <div class="p-3 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 text-xs">
                                    No hay cobros registrados en mano todavía.
                                </div>
                            @else
                                @foreach($colList as $collectorName => $amountCollected)
                                    @php
                                        $cLower = mb_strtolower($collectorName);
                                        $isJoint = str_contains($cLower, 'comun') || str_contains($cLower, 'común') || str_contains($cLower, 'fondo') || str_contains($cLower, 'conjunt');
                                    @endphp
                                    <div class="p-2.5 rounded-xl border flex items-center justify-between {{ $isJoint ? 'bg-teal-50 dark:bg-teal-950/50 border-teal-200 dark:border-teal-800 text-teal-900 dark:text-teal-200' : 'bg-indigo-50/70 dark:bg-indigo-950/50 border-indigo-200 dark:border-indigo-800 text-indigo-900 dark:text-indigo-200' }}">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm">{{ $isJoint ? '🏢' : '👤' }}</span>
                                            <div>
                                                <strong class="text-xs font-bold block">{{ $collectorName }}</strong>
                                                <span class="text-[10px] opacity-75">
                                                    {{ $isJoint ? 'En Cuenta Bancaria Común' : 'Cobrado en mano / personal' }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-sm font-black block">
                                                {{ number_format($amountCollected, 2, ',', '.') }} €
                                            </span>
                                            @if(!$isJoint)
                                                <button
                                                    type="button"
                                                    wire:click="toggleDepositTransferredToJoint({{ $ev->id }})"
                                                    class="text-[9px] font-extrabold px-1.5 py-0.5 rounded bg-amber-200 dark:bg-amber-900/60 text-amber-900 dark:text-amber-200 hover:bg-amber-300 transition cursor-pointer mt-0.5 inline-block shadow-2xs"
                                                    title="Pulsar si este dinero ya se transfirió a la cuenta común"
                                                >
                                                    🏦 Traspasado a Común
                                                </button>
                                            @elseif(!empty($sett['deposit_transferred_to_joint']))
                                                <button
                                                    type="button"
                                                    wire:click="toggleDepositTransferredToJoint({{ $ev->id }})"
                                                    class="text-[9px] font-extrabold px-1.5 py-0.5 rounded bg-teal-200 dark:bg-teal-900/60 text-teal-900 dark:text-teal-200 hover:bg-teal-300 transition cursor-pointer mt-0.5 inline-block"
                                                    title="Traspasado el {{ $sett['deposit_transferred_at'] ?? 'recientemente' }}. Pulsar para revertir."
                                                >
                                                    ✅ Traspasado (Deshacer)
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        <!-- REASIGNACIÓN RÁPIDA EN 1 CLIC -->
                        <div class="pt-2 border-t border-slate-200 dark:border-slate-800 space-y-1.5">
                            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">
                                ¿Cambiar recaudador en 1 clic?
                            </span>
                            <div class="grid grid-cols-2 gap-1.5 text-[11px]">
                                <button
                                    type="button"
                                    wire:click="setPaymentCollector({{ $ev->id }}, 'all', 'Cuenta Conjunta / Fondo Común')"
                                    class="p-1.5 rounded-lg border font-bold transition text-center cursor-pointer {{ $isFondo ? 'bg-teal-600 text-white border-teal-600' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-teal-50 dark:hover:bg-teal-950' }}"
                                    title="Destinar cobro a Fondo Común (material Larriba, cena empresa, etc.)"
                                >
                                    🏢 Fondo Común
                                </button>
                                @foreach($partners as $pOption)
                                    <button
                                        type="button"
                                        wire:click="setPaymentCollector({{ $ev->id }}, 'all', '{{ $pOption['name'] }}')"
                                        class="p-1.5 rounded-lg border font-bold transition text-center cursor-pointer bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-indigo-950"
                                    >
                                        👤 {{ $pOption['name'] }}
                                    </button>
                                @endforeach
                            </div>
                            <p class="text-[9px] text-slate-400 leading-tight">
                                * Si fue para cena de empresa o compras, pulsa <strong>Fondo Común</strong> para que no sume dinero personal en tu bolsillo.
                            </p>
                        </div>
                    </div>

                    <!-- COLUMNA 3: REPARTO DEL EVENTO ENTRE SOCIOS (4 COLS) -->
                    <div class="lg:col-span-4 space-y-3 bg-slate-50 dark:bg-slate-950/60 p-4 rounded-2xl border border-slate-200 dark:border-slate-800">
                        <div class="flex items-center justify-between gap-1 flex-wrap">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">
                                    ⚖️ 3. Reparto de este Evento
                                </span>
                                @if(!empty($sett['has_custom_split']))
                                    <span class="px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800" title="Reparto personalizado para este evento">
                                        ⚡ A medida
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center gap-1.5">
                                <button
                                    type="button"
                                    wire:click="openEventSplitModal({{ $ev->id }})"
                                    class="text-[9px] font-black px-2 py-0.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white cursor-pointer transition shadow-2xs flex items-center gap-1"
                                    title="Personalizar quién cobra en este evento y qué importe"
                                >
                                    <span>✏️</span>
                                    <span>Personalizar</span>
                                </button>

                                @if(($sett['settlement_status'] ?? 'pending') === 'settled')
                                    <button
                                        type="button"
                                        wire:click="unsettleEventPartners({{ $ev->id }})"
                                        class="text-[9px] font-black px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700 cursor-pointer hover:opacity-80"
                                        title="Pulsar para revertir liquidación a pendiente"
                                    >
                                        ✅ Liquidado ({{ $sett['settlement_paid_at'] ?? 'Registrado' }}) ↩️
                                    </button>
                                @else
                                    <button
                                        type="button"
                                        wire:click="settleEventPartners({{ $ev->id }})"
                                        class="text-[9px] font-black px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300 dark:border-amber-700 cursor-pointer hover:scale-105 transition shadow-2xs"
                                        title="Marcar que el reparto de este evento ya se pagó y liquidó a los socios"
                                    >
                                        💸 Marcar Liquidado
                                    </button>
                                @endif
                            </div>
                        </div>

                        @if($isFondo)
                            <div class="p-3.5 rounded-2xl bg-teal-500/10 border-2 border-teal-500 text-teal-900 dark:text-teal-200 space-y-1.5">
                                <div class="flex items-center gap-2 font-black text-xs text-teal-800 dark:text-teal-300">
                                    <span>🏢</span>
                                    <span>Destinado Íntegramente a Fondo Común</span>
                                </div>
                                <p class="text-[11px] leading-relaxed text-teal-700 dark:text-teal-300/90 font-medium">
                                    Este importe ({{ number_format($sett['total_collected'] ?? 0, 2, ',', '.') }} €) se apartó para gastos de empresa, cena de empresa o compras compartidas. <strong>0,00 € a repartir a cuentas privadas</strong>. No genera desequilibrio ni deudas entre los socios.
                                </p>
                            </div>
                        @else
                            <div class="space-y-2">
                                @forelse($evPartners as $pRow)
                                    @php
                                        $pDiff = (float)($pRow['balance_diff'] ?? 0);
                                    @endphp
                                    <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-1">
                                        <div class="flex items-center justify-between text-xs font-black text-slate-900 dark:text-white">
                                            <div class="flex items-center gap-1.5">
                                                <span>👤 {{ $pRow['name'] }}</span>
                                                @if(!empty($pRow['role']))
                                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                                        {{ $pRow['role'] }}
                                                    </span>
                                                @elseif(($pRow['percentage'] ?? 0) > 0)
                                                    <span class="text-[10px] text-slate-400 font-normal">({{ $pRow['percentage'] }}%)</span>
                                                @endif
                                            </div>
                                            <span class="text-emerald-600 dark:text-emerald-400 font-extrabold text-sm">
                                                {{ number_format($pRow['total_to_receive'], 2, ',', '.') }} €
                                            </span>
                                        </div>

                                        <div class="flex items-center justify-between text-[10px] text-slate-500 flex-wrap gap-1">
                                            <span>Cuota: {{ number_format($pRow['profit_share'], 2, ',', '.') }}€</span>
                                            <div class="flex items-center gap-1.5">
                                                @if(($pRow['provider_assumed_cost'] ?? 0) > 0)
                                                    <span class="text-indigo-600 dark:text-indigo-400 font-bold" title="Coste de proveedor asumido por este socio y descontado de su cuota">
                                                        -{{ number_format($pRow['provider_assumed_cost'], 2, ',', '.') }}€ coste asumido
                                                    </span>
                                                @endif
                                                @if(($pRow['material_reimbursement'] + $pRow['provider_reimbursement']) > 0)
                                                    <span class="text-amber-600 dark:text-amber-400 font-bold">
                                                        +{{ number_format($pRow['material_reimbursement'] + $pRow['provider_reimbursement'], 2, ',', '.') }}€ reemb.
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Situación de arqueo en este evento -->
                                        <div class="pt-1 border-t border-slate-100 dark:border-slate-800 text-[10px] flex items-center justify-between gap-1 flex-wrap">
                                            <div>
                                                @if(!empty($pRow['is_settled']))
                                                    <span class="text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
                                                        <span>✅</span>
                                                        <span>Cobró sus {{ number_format($pRow['total_to_receive'], 2, ',', '.') }}€ (Cuentas cuadradas)</span>
                                                    </span>
                                                @elseif($pDiff > 0.05)
                                                    <span class="text-amber-600 dark:text-amber-400 font-bold">
                                                        ⚠️ Cobró {{ number_format($pRow['collected_in_hand'], 2, ',', '.') }}€ (tiene {{ number_format($pDiff, 2, ',', '.') }}€ de más a compensar)
                                                    </span>
                                                @elseif($pDiff < -0.05)
                                                    <span class="text-indigo-600 dark:text-indigo-400 font-bold">
                                                        ⏳ Cobró 0€ (tiene pendiente recibir {{ number_format(abs($pDiff), 2, ',', '.') }}€)
                                                    </span>
                                                @else
                                                    <span class="text-emerald-600 dark:text-emerald-400 font-bold">
                                                        ✅ Saldo cuadrado en este evento
                                                    </span>
                                                @endif
                                            </div>

                                            <button
                                                type="button"
                                                wire:click="togglePartnerSettledDirect({{ $ev->id }}, '{{ $pRow['name'] }}')"
                                                class="text-[9px] font-bold px-1.5 py-0.5 rounded cursor-pointer transition {{ !empty($pRow['is_settled']) ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 hover:bg-emerald-200' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200' }}"
                                                title="Pulsar para alternar si este socio ya cobró su dinero de este evento"
                                            >
                                                {{ !empty($pRow['is_settled']) ? '✅ Pagado' : '⏳ Marcar Cobrado' }}
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-xs text-slate-400">Sin socios configurados.</div>
                                @endforelse
                            </div>
                        @endif
                    </div>
                </div>

                <!-- PIE: EXPLICACIÓN EN LENGUAJE NATURAL -->
                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                    <span class="text-base shrink-0">💡</span>
                    <span>{{ $sett['explanation'] ?? 'Desglose calculado correctamente.' }}</span>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-12 text-center text-slate-400 space-y-2">
                <span class="text-4xl block">🔍</span>
                <p class="font-bold text-sm">No se encontraron eventos con los filtros actuales.</p>
                <p class="text-xs">Prueba a cambiar la temporada o seleccionar "Todos con Cobros".</p>
            </div>
        @endforelse
    </div>
</div>
