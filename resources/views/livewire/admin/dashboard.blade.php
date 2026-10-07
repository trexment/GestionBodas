<div class="space-y-8">
    
    <!-- BIENVENIDA & ACCIONES RÁPIDAS -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-slate-800 relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 border border-indigo-400/30 text-indigo-300 text-xs font-semibold mb-3">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Sistema Operativo en Vivo</span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    ¡Hola de nuevo, {{ Auth::user()->name }}! 👋
                </h2>
                <p class="text-slate-300 text-sm mt-1 max-w-xl font-light">
                    Tienes <strong class="text-white font-bold">{{ $upcomingEventsCount }} eventos</strong> en los próximos 30 días y <strong class="text-indigo-300 font-bold">{{ $pendingDossiersCount }} cuestionarios</strong> musicales pendientes de recibir.
                </p>
            </div>

            <!-- BOTONES DE ACCIÓN RÁPIDA -->
            <div class="flex flex-wrap gap-2.5">
                <a href="{{ route('admin.events') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition transform hover:-translate-y-0.5">
                    <span>➕</span> Nuevo Evento
                </a>
                <a href="{{ route('admin.calendar') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs font-bold rounded-xl transition">
                    <span>📅</span> Calendario
                </a>
                <a href="{{ route('guest.quote') }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs font-bold rounded-xl transition">
                    <span>⚡</span> Calculadora
                </a>
                <a href="{{ route('admin.settings') }}" class="inline-flex items-center gap-2 px-3 py-2.5 bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs font-bold rounded-xl transition" title="Configuración">
                    <span>⚙️</span>
                </a>
            </div>
        </div>
    </div>

    <!-- TARJETAS DE MÉTRICAS / KPIS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <!-- Facturación Total -->
        <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Facturación Total</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg font-bold">
                    💶
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl font-extrabold text-slate-800">{{ number_format($totalBilled, 2, ',', '.') }} €</div>
                <div class="flex items-center gap-1.5 text-xs text-slate-500 mt-1">
                    <span class="text-emerald-600 font-bold">●</span> Total emitido en facturas
                </div>
            </div>
        </div>

        <!-- Pendiente de Cobro -->
        <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Pendiente de Cobro</span>
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg font-bold">
                    ⏳
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl font-extrabold {{ $totalPending > 0 ? 'text-rose-600' : 'text-slate-800' }}">
                    {{ number_format($totalPending, 2, ',', '.') }} €
                </div>
                <div class="flex items-center gap-1.5 text-xs text-slate-500 mt-1">
                    <span class="{{ $totalPending > 0 ? 'text-rose-500 font-bold' : 'text-slate-400' }}">●</span> Facturas sin liquidar
                </div>
            </div>
        </div>

        <!-- Eventos a 30 días -->
        <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Próximos 30 Días</span>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg font-bold">
                    🎉
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl font-extrabold text-indigo-600">{{ $upcomingEventsCount }}</div>
                <div class="flex items-center gap-1.5 text-xs text-slate-500 mt-1">
                    <span>De un total de <strong>{{ $totalEventsCount }}</strong> eventos</span>
                </div>
            </div>
        </div>

        <!-- Personal & Equipos -->
        <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200 hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Equipo & Material</span>
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg font-bold">
                    🎛️
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl font-extrabold text-slate-800">{{ $djsCount + $assistantsCount }} <span class="text-xs font-normal text-slate-400">staff</span></div>
                <div class="flex items-center gap-2 text-xs text-slate-500 mt-1">
                    <span>{{ $djsCount }} DJs</span> &bull; 
                    <span>{{ $assistantsCount }} Asistentes</span> &bull; 
                    <span>{{ $equipmentCount }} equipos</span>
                </div>
            </div>
        </div>

    </div>

    <!-- SECCIÓN PRINCIPAL DE DATOS: PRÓXIMOS EVENTOS + FACTURACIÓN -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- COLUMNA IZQUIERDA: PRÓXIMOS EVENTOS (8 cols) -->
        <div class="lg:col-span-7 bg-white rounded-2xl shadow-xs border border-slate-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-2">
                    <span class="text-lg">📅</span>
                    <h3 class="text-base font-bold text-slate-800">Próximos Eventos en Agenda</h3>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.calendar') }}" class="text-xs font-bold px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 transition">
                        Ver Calendario
                    </a>
                    <a href="{{ route('admin.events') }}" class="text-xs font-bold px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                        Ver Todos
                    </a>
                </div>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($upcomingEvents as $ev)
                    <div class="p-5 hover:bg-slate-50/80 transition flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        
                        <!-- FECHA Y DATOS EVENTO -->
                        <div class="flex items-start gap-4">
                            <!-- BADGE DE FECHA -->
                            <div class="flex-shrink-0 w-14 h-14 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-700 flex flex-col items-center justify-center shadow-xs">
                                <span class="text-[10px] uppercase font-bold tracking-wider leading-none text-indigo-500">{{ $ev->event_date->translatedFormat('M') }}</span>
                                <span class="text-xl font-extrabold leading-tight">{{ $ev->event_date->format('d') }}</span>
                            </div>

                            <div>
                                <h4 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                    {{ $ev->name }}
                                    @if($ev->type)
                                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ $ev->type }}</span>
                                    @endif
                                </h4>
                                
                                <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                                    <span>👤 {{ $ev->client ? $ev->client->name : 'Sin cliente' }}</span>
                                    @if($ev->location)
                                        <span>&bull;</span>
                                        <span>📍 {{ $ev->location }}</span>
                                    @endif
                                </p>

                                <!-- BADGES DE PERSONAL -->
                                <div class="flex flex-wrap items-center gap-2 mt-2">
                                    @if($ev->dj)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 border border-purple-200 text-[11px] font-semibold">
                                            <span>🎧 DJ:</span> {{ $ev->dj->name }}
                                        </span>
                                    @endif

                                    @php
                                        $dashAsts = $ev->all_assistants;
                                    @endphp
                                    @if($dashAsts->isNotEmpty())
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 border border-amber-200 text-[11px] font-semibold">
                                            <span>🛠️ Asistente(s):</span> {{ $dashAsts->pluck('name')->implode(', ') }}
                                        </span>
                                    @endif

                                    @if($ev->is_dossier_completed)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 text-[11px] font-semibold">
                                            <span>✓</span> Música Confirmada
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 text-slate-500 text-[11px] font-medium">
                                            <span>⏳</span> Cuestionario pendiente
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- ACCIÓN -->
                        <div class="flex-shrink-0 sm:self-center">
                            <a href="{{ route('admin.events.show', $ev->id) }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white text-xs font-bold transition shadow-xs">
                                <span>Gestionar</span>
                                <span>&rarr;</span>
                            </a>
                        </div>

                    </div>
                @empty
                    <div class="p-10 text-center text-slate-400 text-sm">
                        <span class="text-3xl block mb-2">🎈</span>
                        No hay eventos próximos registrados en la agenda.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- COLUMNA DERECHA: FACTURACIÓN RECIENTE & ACCESOS (5 cols) -->
        <div class="lg:col-span-5 space-y-6">
            
            <!-- FACTURACIÓN RECIENTE -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200 overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">💶</span>
                        <h3 class="text-base font-bold text-slate-800">Facturación Reciente</h3>
                    </div>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($recentInvoices as $inv)
                        <div class="p-4 hover:bg-slate-50 transition flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs font-bold text-slate-800">
                                    {{ $inv->invoice_number }}
                                    <span class="text-slate-400 font-normal">({{ $inv->event->name ?? 'Evento' }})</span>
                                </div>
                                <div class="text-sm font-extrabold text-slate-900 mt-0.5">
                                    {{ number_format($inv->total, 2, ',', '.') }} €
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                @if($inv->status == 'unpaid')
                                    <span class="px-2.5 py-1 text-[11px] font-bold rounded-full bg-rose-50 text-rose-700 border border-rose-200">
                                        Pendiente
                                    </span>
                                    <button wire:click="markInvoiceAsPaid({{ $inv->id }})" title="Marcar como cobrada" class="px-2.5 py-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-300 rounded-lg transition cursor-pointer">
                                        ✓ Cobrada
                                    </button>
                                @else
                                    <span class="px-2.5 py-1 text-[11px] font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Pagada
                                    </span>
                                @endif

                                <a href="{{ route('admin.invoice.pdf', $inv->id) }}" target="_blank" title="Descargar PDF de Factura" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-slate-400 text-xs">
                            No hay facturas emitidas todavía.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- RESUMEN DE RECURSOS -->
            <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-2xl p-5 border border-indigo-100">
                <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-900 mb-3 flex items-center gap-2">
                    <span>💡</span> Accesos Rápidos de Gestión
                </h4>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <a href="{{ route('admin.users') }}" class="p-3 bg-white rounded-xl border border-indigo-100 hover:border-indigo-300 transition text-slate-700 hover:text-indigo-700 font-semibold flex items-center gap-2 shadow-2xs">
                        <span>👥</span> Personal ({{ $djsCount + $assistantsCount }})
                    </a>
                    <a href="{{ route('admin.inventory') }}" class="p-3 bg-white rounded-xl border border-indigo-100 hover:border-indigo-300 transition text-slate-700 hover:text-indigo-700 font-semibold flex items-center gap-2 shadow-2xs">
                        <span>📦</span> Inventario ({{ $equipmentCount }})
                    </a>
                    <a href="{{ route('admin.music') }}" class="p-3 bg-white rounded-xl border border-indigo-100 hover:border-indigo-300 transition text-slate-700 hover:text-indigo-700 font-semibold flex items-center gap-2 shadow-2xs">
                        <span>🎵</span> Repertorio DJ
                    </a>
                    <a href="{{ route('admin.settings') }}" class="p-3 bg-white rounded-xl border border-indigo-100 hover:border-indigo-300 transition text-slate-700 hover:text-indigo-700 font-semibold flex items-center gap-2 shadow-2xs">
                        <span>📜</span> Contratos & Tarifas
                    </a>
                </div>
            </div>

        </div>

    </div>

</div>
