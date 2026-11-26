{{-- Vista de Cuenta Común, Arqueo Bancario Continuo y Control de Retornos --}}
@php
    $jaSummary = $kpis['joint_account_summary'] ?? [];
    $settSummary = $kpis['settlement_summary'] ?? [];
    $partnersList = $settSummary['partners'] ?? [];
    $bankBalance = (float)($jaSummary['theoretical_bank_balance'] ?? 0);
    $cashBalance = (float)($jaSummary['cash_balance'] ?? 0);
    $totalAvailable = (float)($jaSummary['total_available_balance'] ?? 0);
    $pendingDebts = (float)($jaSummary['pending_partner_debts'] ?? 0);
    $freeBalance = (float)($jaSummary['available_free_balance'] ?? 0);
@endphp

<div class="space-y-6">
    <!-- 1. TARJETAS KPI DE SALDO REAL: BANCO, CAJA EFECTIVO Y LIQUIDEZ TOTAL -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Saldo Real Actual en Banco -->
        <div class="bg-gradient-to-br from-emerald-600 to-teal-700 text-white rounded-2xl p-5 shadow-lg space-y-2 relative overflow-hidden sm:col-span-2 lg:col-span-1">
            <div class="flex items-center justify-between">
                <span class="text-xs font-black uppercase tracking-wider text-emerald-100 flex items-center gap-1.5">
                    <span>🏦</span> Saldo en Banco
                </span>
                <span class="text-xs px-2 py-0.5 rounded-full bg-white/20 font-bold">Cuenta Común</span>
            </div>
            <div class="text-3xl font-black tracking-tight {{ $bankBalance < 0 ? 'text-rose-200' : '' }}">
                {{ number_format($bankBalance, 2, ',', '.') }} €
            </div>
            <div class="space-y-1 text-[11px] text-emerald-100/90 pt-1 border-t border-white/20">
                <div class="flex justify-between">
                    <span>Entradas Banco (Cobros + Señales):</span>
                    <strong>+{{ number_format($jaSummary['bank_inflows'] ?? 0, 2, ',', '.') }} €</strong>
                </div>
                <div class="flex justify-between">
                    <span>Salidas Banco (Compras + Proveedores):</span>
                    <strong>-{{ number_format($jaSummary['bank_outflows'] ?? 0, 2, ',', '.') }} €</strong>
                </div>
            </div>
        </div>

        <!-- Bote / Caja de Efectivo de la Empresa -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4.5 shadow-xs space-y-2">
            <div class="flex items-center justify-between text-xs font-bold text-slate-500 dark:text-slate-400">
                <span class="flex items-center gap-1.5"><span>💵</span> Bote Efectivo</span>
                <span class="text-[10px] font-black text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-full">Caja Empresa</span>
            </div>
            <div class="text-2xl font-black {{ $cashBalance >= 0 ? 'text-slate-900 dark:text-white' : 'text-rose-600' }}">
                {{ number_format($cashBalance, 2, ',', '.') }} €
            </div>
            <div class="space-y-0.5 text-[10px] text-slate-500 pt-1 border-t border-slate-100 dark:border-slate-800">
                <div class="flex justify-between">
                    <span>Cobros en efectivo fondo común:</span>
                    <strong class="text-emerald-600 dark:text-emerald-400">+{{ number_format($jaSummary['cash_inflows'] ?? 0, 2, ',', '.') }} €</strong>
                </div>
                <div class="flex justify-between">
                    <span>Gastos pagados en efectivo:</span>
                    <strong class="text-rose-600 dark:text-rose-400">-{{ number_format($jaSummary['cash_outflows'] ?? 0, 2, ',', '.') }} €</strong>
                </div>
            </div>
        </div>

        <!-- Liquidez Total Disponible (Banco + Caja) -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4.5 shadow-xs space-y-2">
            <div class="flex items-center justify-between text-xs font-bold text-slate-500 dark:text-slate-400">
                <span class="flex items-center gap-1.5"><span>💰</span> Total Disponible</span>
                <span class="text-[10px] font-black text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/60 px-2 py-0.5 rounded-full">Banco + Caja</span>
            </div>
            <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400">
                {{ number_format($totalAvailable, 2, ',', '.') }} €
            </div>
            <div class="space-y-0.5 text-[10px] text-slate-500 pt-1 border-t border-slate-100 dark:border-slate-800">
                <div class="flex justify-between">
                    <span>En Cuenta Bancaria:</span>
                    <strong class="text-slate-700 dark:text-slate-300">{{ number_format($bankBalance, 2, ',', '.') }} €</strong>
                </div>
                <div class="flex justify-between">
                    <span>En Caja de Efectivo:</span>
                    <strong class="text-slate-700 dark:text-slate-300">{{ number_format($cashBalance, 2, ',', '.') }} €</strong>
                </div>
            </div>
        </div>

        <!-- Deudas Pendientes a Socios & Remanente Libre -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4.5 shadow-xs space-y-2">
            <div class="flex items-center justify-between text-xs font-bold text-slate-500 dark:text-slate-400">
                <span class="flex items-center gap-1.5"><span>⏳</span> Deuda Pendiente a Socios</span>
                <span class="text-[10px] font-black text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/60 px-2 py-0.5 rounded-full">A Devolver</span>
            </div>
            <div class="text-2xl font-black text-amber-600 dark:text-amber-400">
                {{ number_format($pendingDebts, 2, ',', '.') }} €
            </div>
            <div class="pt-1 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                <span class="text-[11px] text-slate-500 font-semibold">💎 Remanente Libre:</span>
                <strong class="text-xs font-black {{ $freeBalance >= 0 ? 'text-teal-600 dark:text-teal-400' : 'text-rose-600' }}">
                    {{ number_format($freeBalance, 2, ',', '.') }} €
                </strong>
            </div>
            <p class="text-[9px] text-slate-400">
                * Gastos que los socios pagaron de su bolsillo y la empresa aún les debe reembolsar.
            </p>
        </div>
    </div>

    <!-- 2. DESGLOSE DE ARQUEO RÁPIDO POR SOCIO (Bolsillo vs Devoluciones) -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
            <div>
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>👥</span>
                    <span>Arqueo Individual de Socios: ¿Quién tiene dinero en mano y a quién se le debe retornar?</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Permite ver qué socio cobró de clientes, cuánto traspasó ya a la común y qué gastos de su bolsillo le faltan por cobrar.
                </p>
            </div>
            <span class="text-[11px] font-bold text-slate-500 self-start sm:self-auto bg-slate-100 dark:bg-slate-800 px-2.5 py-1 rounded-lg">
                {{ count($partnersList) }} Socios activos
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @forelse($partnersList as $p)
                @php
                    $diff = (float)($p['balance_diff'] ?? 0);
                    $pendReimb = (float)($p['reimbursement_pending'] ?? 0);
                    $doneReimb = (float)($p['reimbursement_done'] ?? 0);
                    $inHand = (float)($p['collected_in_hand'] ?? 0);
                    $transferred = (float)($p['transferred_to_joint'] ?? 0);
                    $origCollected = (float)($p['original_collected'] ?? 0);
                    $evBreakdown = (array)($p['events_breakdown'] ?? []);
                @endphp
                <div x-data="{ showEvents: false }" class="p-4 rounded-xl border {{ $diff > 0.05 ? 'bg-amber-50/40 dark:bg-amber-950/20 border-amber-200 dark:border-amber-800' : ($diff < -0.05 ? 'bg-indigo-50/40 dark:bg-indigo-950/20 border-indigo-200 dark:border-indigo-800' : 'bg-slate-50/60 dark:bg-slate-950/40 border-slate-200 dark:border-slate-800') }} space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5">
                            <span>👤</span> {{ $p['name'] }} ({{ $p['percentage'] }}%)
                        </span>
                        <span class="text-xs font-black {{ $diff > 0.05 ? 'text-amber-700 dark:text-amber-400' : ($diff < -0.05 ? 'text-indigo-700 dark:text-indigo-400' : 'text-emerald-600') }}">
                            @if($diff > 0.05)
                                Debe transferir: {{ number_format($diff, 2, ',', '.') }} €
                            @elseif($diff < -0.05)
                                Debe percibir: {{ number_format(abs($diff), 2, ',', '.') }} €
                            @else
                                ✅ Cuentas saldadas
                            @endif
                        </span>
                    </div>

                    <div class="space-y-1.5 pt-2 border-t border-slate-200/60 dark:border-slate-800 text-xs">
                        <div class="flex items-center justify-between font-bold">
                            <span class="text-slate-700 dark:text-slate-300">💼 Cuota asignada (Eventos):</span>
                            <span class="text-emerald-600 dark:text-emerald-400 font-black">{{ number_format((float)($p['profit_share'] ?? 0), 2, ',', '.') }} €</span>
                        </div>
                        <div class="flex items-center justify-between pt-0.5 font-bold">
                            <span class="text-slate-700 dark:text-slate-300">💵 Dinero real en mano:</span>
                            <span class="{{ $inHand > 0 ? 'text-indigo-600 dark:text-indigo-400 font-black' : 'text-slate-500' }}">{{ number_format($inHand, 2, ',', '.') }} €</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-500 text-[11px] pt-1 border-t border-slate-200/40 dark:border-slate-800">
                            <span>Cobrado directo de clientes:</span>
                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ number_format($origCollected, 2, ',', '.') }} €</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-500 text-[11px]">
                            <span>Traspasado a cuenta común:</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400">-{{ number_format($transferred, 2, ',', '.') }} €</span>
                        </div>

                        @if(($pendReimb + $doneReimb) > 0)
                            <div class="flex items-center justify-between text-slate-500 text-[11px] pt-1">
                                <span>Gastos de bolsillo adelantados:</span>
                                <span class="font-bold text-slate-700 dark:text-slate-300">{{ number_format($pendReimb + $doneReimb, 2, ',', '.') }} €</span>
                            </div>
                            @if($pendReimb > 0)
                                <div class="flex items-center justify-between text-[11px] text-amber-600 dark:text-amber-400 font-bold">
                                    <span>↳ Pendiente retorno por común:</span>
                                    <span>+{{ number_format($pendReimb, 2, ',', '.') }} €</span>
                                </div>
                            @endif
                        @endif

                        <!-- Botón desplegable para ver desglose de eventos -->
                        <div class="pt-2 border-t border-slate-200/60 dark:border-slate-800">
                            <button type="button" @click="showEvents = !showEvents" class="w-full py-1.5 px-2.5 rounded-lg bg-white hover:bg-slate-100 dark:bg-slate-900 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-700 dark:text-slate-300 flex items-center justify-between transition cursor-pointer shadow-2xs">
                                <span class="flex items-center gap-1.5">
                                    <span>📋</span>
                                    <span>Ver eventos y servicios ({{ count($evBreakdown) }})</span>
                                </span>
                                <span x-text="showEvents ? '▲ Cerrar' : '▼ Ver'"></span>
                            </button>

                            <div x-show="showEvents" x-cloak class="mt-2 space-y-1.5 bg-white dark:bg-slate-900 p-2 rounded-xl border border-slate-200 dark:border-slate-800 shadow-inner text-[11px]" style="display: none;">
                                @forelse($evBreakdown as $b)
                                    <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-950/60 border border-slate-100 dark:border-slate-800/60 space-y-1">
                                        <div class="flex items-center justify-between gap-1 font-bold">
                                            <a href="{{ route('admin.events.show', $b['event_id']) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline truncate" title="{{ $b['event_name'] }}">
                                                {{ $b['event_name'] }}
                                            </a>
                                            <span class="text-[9px] px-1.5 py-0.5 rounded {{ $b['is_settled'] ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300' : 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300' }} font-black shrink-0">
                                                {{ $b['is_settled'] ? '✓ Liquidado' : '⏳ Pendiente' }}
                                            </span>
                                        </div>
                                        <div class="flex items-center justify-between text-slate-500 text-[10px]">
                                            <span>Rol: <strong>{{ $b['role'] }}</strong></span>
                                            <span class="text-slate-400">{{ $b['event_date'] }}</span>
                                        </div>
                                        <div class="flex items-center justify-between text-[10px] pt-0.5 border-t border-slate-200/40 dark:border-slate-800">
                                            <span>Cuota: <strong class="text-slate-700 dark:text-slate-300">{{ number_format($b['profit_share'], 2, ',', '.') }} €</strong></span>
                                            <span>En mano: <strong class="text-indigo-600 dark:text-indigo-400">{{ number_format($b['collected_in_hand'], 2, ',', '.') }} €</strong></span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-2 text-slate-400 text-[10px]">
                                        Sin eventos asignados a este socio en esta temporada.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-3 text-center text-slate-400 py-6">
                    Sin socios configurados en el panel.
                </div>
            @endforelse
        </div>
    </div>

    <!-- 3. EXTRACTO / LIBRO CRONOLÓGICO DE MOVIMIENTOS DE LA CUENTA COMÚN -->
    <div x-data="{ channelFilter: 'all' }" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-3 bg-slate-50/60 dark:bg-slate-950/50">
            <div>
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>📜</span>
                    <span>Libro de Movimientos de la Cuenta Común ({{ count($jointAccountLedger) }} operaciones)</span>
                </h3>
                <p class="text-[11px] text-slate-500 mt-0.5">
                    Historial cronológico continuo de cobros ingresados/traspasados, pagos a proveedores, compras de material y gastos compartidos.
                </p>
            </div>
            
            <!-- Selector de Canal: Todos, Banco o Efectivo -->
            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex rounded-xl p-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xs gap-1">
                    <button type="button" 
                        @click="channelFilter = 'all'" 
                        :class="channelFilter === 'all' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'" 
                        class="px-3 py-1 rounded-lg text-xs font-bold transition cursor-pointer">
                        🌍 Todos ({{ count($jointAccountLedger) }})
                    </button>
                    <button type="button" 
                        @click="channelFilter = 'bank'" 
                        :class="channelFilter === 'bank' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'" 
                        class="px-3 py-1 rounded-lg text-xs font-bold transition cursor-pointer flex items-center gap-1">
                        <span>🏦</span> Solo Banco ({{ collect($jointAccountLedger)->where('channel', 'bank')->count() }})
                    </button>
                    <button type="button" 
                        @click="channelFilter = 'cash'" 
                        :class="channelFilter === 'cash' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'" 
                        class="px-3 py-1 rounded-lg text-xs font-bold transition cursor-pointer flex items-center gap-1">
                        <span>💵</span> Solo Efectivo ({{ collect($jointAccountLedger)->where('channel', 'cash')->count() }})
                    </button>
                </div>

                <div class="flex items-center gap-1.5">
                    <span class="text-xs font-black px-3 py-1 rounded-xl bg-blue-50 text-blue-900 dark:bg-blue-950 dark:text-blue-300 border border-blue-200 dark:border-blue-800" title="Saldo disponible en Banco">
                        🏦 Banco: {{ number_format($bankBalance, 2, ',', '.') }} €
                    </span>
                    <span class="text-xs font-black px-3 py-1 rounded-xl bg-emerald-50 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800" title="Saldo disponible en Caja de Efectivo">
                        💵 Caja: {{ number_format($cashBalance, 2, ',', '.') }} €
                    </span>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-[10px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-950">
                        <th class="py-3 px-4">Fecha</th>
                        <th class="py-3 px-3 text-center">Cuenta / Canal</th>
                        <th class="py-3 px-3 text-center">Tipo</th>
                        <th class="py-3 px-4">Concepto / Detalle</th>
                        <th class="py-3 px-3">Evento Asociado</th>
                        <th class="py-3 px-3">Flujo / Actor</th>
                        <th class="py-3 px-3 text-right">Importe</th>
                        <th class="py-3 px-3 text-center">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                    @forelse($jointAccountLedger as $row)
                        @php
                            $isInflow = ($row['type'] ?? '') === 'inflow';
                            $rowDate = !empty($row['date']) ? ($row['date'] instanceof \Carbon\Carbon ? $row['date']->format('d/m/Y') : \Carbon\Carbon::parse($row['date'])->format('d/m/Y')) : '—';
                            $rChan = $row['channel'] ?? 'bank';
                        @endphp
                        <tr 
                            x-show="channelFilter === 'all' || channelFilter === '{{ $rChan }}'" 
                            class="hover:bg-slate-50/80 dark:hover:bg-slate-850/60 transition"
                        >
                            <!-- Fecha -->
                            <td class="py-3 px-4 font-bold text-slate-700 dark:text-slate-300 whitespace-nowrap">
                                📅 {{ $rowDate }}
                            </td>

                            <!-- Canal / Cuenta (Banco vs Efectivo) -->
                            <td class="py-3 px-3 text-center whitespace-nowrap">
                                @if($rChan === 'bank')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border border-blue-300 dark:border-blue-700">
                                        🏦 Banco
                                    </span>
                                @elseif($rChan === 'cash')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">
                                        💵 Efectivo
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border border-purple-300 dark:border-purple-700">
                                        🤝 Reparto
                                    </span>
                                @endif
                            </td>

                            <!-- Tipo (Entrada / Salida) -->
                            <td class="py-3 px-3 text-center whitespace-nowrap">
                                @if($isInflow)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">
                                        📥 Entrada
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border border-rose-300 dark:border-rose-700">
                                        📤 Salida
                                    </span>
                                @endif
                            </td>

                            <!-- Concepto -->
                            <td class="py-3 px-4 text-slate-800 dark:text-slate-200">
                                <span class="font-extrabold block text-xs">{{ $row['concept'] ?? '—' }}</span>
                                <span class="text-[10px] text-slate-400">{{ $row['type_label'] ?? '' }}</span>
                            </td>

                            <!-- Evento -->
                            <td class="py-3 px-3 text-slate-600 dark:text-slate-300">
                                @if(!empty($row['event_id']))
                                    <a
                                        href="{{ route('admin.events.show', $row['event_id']) }}"
                                        target="_blank"
                                        class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1"
                                        title="Abrir evento #{{ $row['event_id'] }}"
                                    >
                                        <span>🔗</span>
                                        <span class="truncate max-w-[170px]">{{ $row['event_name'] ?: ('Evento #' . $row['event_id']) }}</span>
                                    </a>
                                @else
                                    <span class="text-slate-400 text-[11px]">{{ $row['event_name'] ?: 'General' }}</span>
                                @endif
                            </td>

                            <!-- Flujo / Actor -->
                            <td class="py-3 px-3 text-slate-600 dark:text-slate-400 whitespace-nowrap font-medium text-[11px]">
                                {{ $row['actor'] ?? 'Cuenta Común' }}
                            </td>

                            <!-- Importe -->
                            <td class="py-3 px-3 text-right font-black whitespace-nowrap text-sm {{ $isInflow ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                {{ $isInflow ? '+' : '-' }}{{ number_format($row['amount'] ?? 0, 2, ',', '.') }} €
                            </td>

                            <!-- Estado -->
                            <td class="py-3 px-3 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                    ✓ Confirmado
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <span class="text-3xl block mb-2">🔍</span>
                                <p class="font-bold text-sm">No hay movimientos registrados en la cuenta común para los filtros seleccionados.</p>
                                <p class="text-xs">Prueba a seleccionar otra temporada o ampliar el rango de fechas.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
