{{-- Vista de Cuenta Común, Arqueo Bancario Continuo y Control de Retornos --}}
@php
    $jaSummary = $kpis['joint_account_summary'] ?? [];
    $settSummary = $kpis['settlement_summary'] ?? [];
    $partnersList = $settSummary['partners'] ?? [];
@endphp

<div class="space-y-6">
    <!-- 1. TARJETAS KPI DE SALDO TEÓRICO Y SALUD DE LA CUENTA COMÚN -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Saldo Teórico Actual en Banco -->
        <div class="bg-gradient-to-br from-emerald-600 to-teal-700 text-white rounded-2xl p-5 shadow-lg space-y-2 relative overflow-hidden sm:col-span-2 lg:col-span-1">
            <div class="flex items-center justify-between">
                <span class="text-xs font-black uppercase tracking-wider text-emerald-100 flex items-center gap-1.5">
                    <span>🏦</span> Saldo Teórico en Banco
                </span>
                <span class="text-xs px-2 py-0.5 rounded-full bg-white/20 font-bold">Cuenta Conjunta</span>
            </div>
            <div class="text-3xl font-black tracking-tight">
                {{ number_format($jaSummary['theoretical_bank_balance'] ?? 0, 2, ',', '.') }} €
            </div>
            <p class="text-[11px] text-emerald-100/90 leading-tight">
                Dinero que debería haber físicamente en la cuenta bancaria común tras sumar ingresos directos/traspasados y restar salidas efectivas.
            </p>
        </div>

        <!-- Total Ingresos / Entradas -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4.5 shadow-xs space-y-2">
            <div class="flex items-center justify-between text-xs font-bold text-slate-500 dark:text-slate-400">
                <span class="flex items-center gap-1.5"><span>📥</span> Total Entradas</span>
                <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-full">Ingresos</span>
            </div>
            <div class="text-2xl font-black text-slate-900 dark:text-white">
                {{ number_format($jaSummary['total_inflows'] ?? 0, 2, ',', '.') }} €
            </div>
            <div class="space-y-0.5 text-[10px] text-slate-500 pt-1 border-t border-slate-100 dark:border-slate-800">
                <div class="flex justify-between">
                    <span>Cobros directos en banco:</span>
                    <strong class="text-slate-700 dark:text-slate-300">{{ number_format($jaSummary['direct_inflows'] ?? 0, 2, ',', '.') }} €</strong>
                </div>
                <div class="flex justify-between">
                    <span>Señales traspasadas a común:</span>
                    <strong class="text-emerald-600 dark:text-emerald-400">{{ number_format($jaSummary['transferred_inflows'] ?? 0, 2, ',', '.') }} €</strong>
                </div>
                @if(($jaSummary['refund_inflows'] ?? 0) > 0)
                    <div class="flex justify-between">
                        <span>Devoluciones proveedor:</span>
                        <strong class="text-teal-600 dark:text-teal-400">{{ number_format($jaSummary['refund_inflows'], 2, ',', '.') }} €</strong>
                    </div>
                @endif
            </div>
        </div>

        <!-- Total Salidas Reales -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4.5 shadow-xs space-y-2">
            <div class="flex items-center justify-between text-xs font-bold text-slate-500 dark:text-slate-400">
                <span class="flex items-center gap-1.5"><span>📤</span> Total Salidas Pagadas</span>
                <span class="text-[10px] font-black text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/60 px-2 py-0.5 rounded-full">Gastos</span>
            </div>
            <div class="text-2xl font-black text-rose-600 dark:text-rose-400">
                -{{ number_format($jaSummary['total_outflows'] ?? 0, 2, ',', '.') }} €
            </div>
            <div class="space-y-0.5 text-[10px] text-slate-500 pt-1 border-t border-slate-100 dark:border-slate-800">
                <div class="flex justify-between">
                    <span>Material pagado por banco:</span>
                    <strong class="text-slate-700 dark:text-slate-300">{{ number_format($jaSummary['direct_material'] ?? 0, 2, ',', '.') }} €</strong>
                </div>
                <div class="flex justify-between">
                    <span>Proveedores pagados por banco:</span>
                    <strong class="text-slate-700 dark:text-slate-300">{{ number_format($jaSummary['direct_provider'] ?? 0, 2, ',', '.') }} €</strong>
                </div>
                <div class="flex justify-between">
                    <span>Reembolsos pagados a socios:</span>
                    <strong class="text-amber-600 dark:text-amber-400">{{ number_format(($jaSummary['reimbursed_material'] ?? 0) + ($jaSummary['reimbursed_provider'] ?? 0), 2, ',', '.') }} €</strong>
                </div>
                @if(($jaSummary['settled_profits_distributed'] ?? 0) > 0)
                    <div class="flex justify-between">
                        <span>Beneficios ya liquidados:</span>
                        <strong class="text-indigo-600 dark:text-indigo-400">{{ number_format($jaSummary['settled_profits_distributed'], 2, ',', '.') }} €</strong>
                    </div>
                @endif
            </div>
        </div>

        <!-- Deudas Pendientes a Socios & Remanente Libre -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4.5 shadow-xs space-y-2">
            <div class="flex items-center justify-between text-xs font-bold text-slate-500 dark:text-slate-400">
                <span class="flex items-center gap-1.5"><span>⏳</span> Deuda Pendiente a Socios</span>
                <span class="text-[10px] font-black text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/60 px-2 py-0.5 rounded-full">A Devolver</span>
            </div>
            <div class="text-2xl font-black text-amber-600 dark:text-amber-400">
                {{ number_format($jaSummary['pending_partner_debts'] ?? 0, 2, ',', '.') }} €
            </div>
            <div class="pt-1 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                <span class="text-[11px] text-slate-500">💎 Remanente Libre:</span>
                <strong class="text-xs font-black {{ ($jaSummary['available_free_balance'] ?? 0) >= 0 ? 'text-teal-600 dark:text-teal-400' : 'text-rose-600' }}">
                    {{ number_format($jaSummary['available_free_balance'] ?? 0, 2, ',', '.') }} €
                </strong>
            </div>
            <p class="text-[9px] text-slate-400">
                * Gastos que los socios pagaron de su bolsillo y la cuenta común aún les debe reembolsar.
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
                @endphp
                <div class="p-4 rounded-xl border {{ $diff > 0.05 ? 'bg-amber-50/40 dark:bg-amber-950/20 border-amber-200 dark:border-amber-800' : ($diff < -0.05 ? 'bg-indigo-50/40 dark:bg-indigo-950/20 border-indigo-200 dark:border-indigo-800' : 'bg-slate-50/60 dark:bg-slate-950/40 border-slate-200 dark:border-slate-800') }} space-y-3">
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
                                Cuadrado (0,00 €)
                            @endif
                        </span>
                    </div>

                    <!-- Métricas del socio -->
                    <div class="space-y-1.5 text-xs text-slate-600 dark:text-slate-300 pt-2 border-t border-slate-200/60 dark:border-slate-800">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] text-slate-500">Cobrado de clientes:</span>
                            <span class="font-bold">{{ number_format($origCollected, 2, ',', '.') }} €</span>
                        </div>
                        <div class="flex items-center justify-between text-teal-700 dark:text-teal-400">
                            <span class="text-[11px]">Traspasado a cuenta común:</span>
                            <span class="font-extrabold">-{{ number_format($transferred, 2, ',', '.') }} €</span>
                        </div>
                        <div class="flex items-center justify-between font-black text-slate-900 dark:text-white bg-white/70 dark:bg-slate-900/70 p-1.5 rounded-lg border border-slate-200/50 dark:border-slate-800">
                            <span class="text-[11px]">Dinero real en mano actual:</span>
                            <span>{{ number_format($inHand, 2, ',', '.') }} €</span>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <span class="text-[11px] text-slate-500">Gastos de bolsillo adelantados:</span>
                            <span class="font-bold">{{ number_format((float)($p['reimbursement'] ?? 0), 2, ',', '.') }} €</span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-emerald-600 dark:text-emerald-400">↳ Ya reembolsados por común:</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($doneReimb, 2, ',', '.') }} €</span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-amber-600 dark:text-amber-400 font-bold">↳ Pendientes de retorno:</span>
                            <span class="font-extrabold text-amber-600 dark:text-amber-400">{{ number_format($pendReimb, 2, ',', '.') }} €</span>
                        </div>

                        <div class="flex items-center justify-between pt-1 border-t border-slate-200/40 dark:border-slate-800">
                            <span class="text-[11px] text-slate-500">Cuota beneficio teórica:</span>
                            <span class="font-black text-emerald-600 dark:text-emerald-400">{{ number_format((float)($p['profit_share'] ?? 0), 2, ',', '.') }} €</span>
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
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/60 dark:bg-slate-950/50">
            <div>
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>📜</span>
                    <span>Libro de Movimientos de la Cuenta Común ({{ count($jointAccountLedger) }} operaciones)</span>
                </h3>
                <p class="text-[11px] text-slate-500 mt-0.5">
                    Historial cronológico continuo de cobros ingresados/traspasados, pagos a proveedores, compras de material y reembolsos abonados.
                </p>
            </div>
            <div class="flex items-center gap-2 self-start sm:self-auto">
                <span class="text-xs font-black px-3 py-1 rounded-xl bg-teal-100 text-teal-900 dark:bg-teal-950 dark:text-teal-300 border border-teal-300 dark:border-teal-700">
                    🏦 Saldo: {{ number_format($jaSummary['theoretical_bank_balance'] ?? 0, 2, ',', '.') }} €
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-[10px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-950">
                        <th class="py-3 px-4">Fecha</th>
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
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-850/60 transition">
                            <!-- Fecha -->
                            <td class="py-3 px-4 font-bold text-slate-700 dark:text-slate-300 whitespace-nowrap">
                                📅 {{ $rowDate }}
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
                                    ✅ Efectivo
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
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
