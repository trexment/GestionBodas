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

    <!-- ALERTA / RECORDATORIO DE CLIENTES SIN RESPUESTA EN DASHBOARD -->
    @if(($noResponseCount ?? 0) > 0)
        <div class="bg-gradient-to-r from-purple-900/90 via-indigo-950 to-slate-900 rounded-3xl p-5 sm:p-6 text-white shadow-xl border border-purple-500/30 relative overflow-hidden">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-purple-500/20 border border-purple-400/40 text-purple-300 flex items-center justify-center text-2xl shrink-0">
                        ⏳
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-extrabold text-base sm:text-lg text-white">
                                Recordatorio de Seguimiento: {{ $noResponseCount }} {{ $noResponseCount === 1 ? 'cliente sin respuesta' : 'clientes sin respuesta' }}
                            </h3>
                            @if(($urgentNoResponseCount ?? 0) > 0)
                                <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-rose-500 text-white animate-pulse">
                                    {{ $urgentNoResponseCount }} requieren atención
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-300 mt-1 max-w-2xl leading-relaxed">
                            Hay clientes esperando respuesta tras enviarles la propuesta. No dejes que se enfríen: activa la técnica del "NO" o el cierre de expediente por WhatsApp con 1 clic.
                        </p>
                    </div>
                </div>

                <a href="{{ route('admin.events') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-purple-600/30 transition whitespace-nowrap self-start md:self-auto">
                    <span>📋 Ver Todos en Agenda</span>
                    <span>→</span>
                </a>
            </div>

            <!-- Mini Listado de Clientes Pendientes -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-4 pt-4 border-t border-purple-500/20">
                @foreach($noResponseEvents as $ev)
                    <div class="bg-slate-900/70 border border-purple-400/20 rounded-2xl p-3 flex flex-col justify-between gap-2.5">
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <strong class="text-xs font-bold text-white truncate block">{{ $ev->name }}</strong>
                                <span class="text-[10px] font-bold px-1.5 py-0.2 rounded {{ $ev->no_response_urgency_level === 'critical' ? 'bg-rose-500/30 text-rose-300 border border-rose-500/40' : 'bg-purple-500/30 text-purple-300' }}">
                                    {{ $ev->no_response_time_label }}
                                </span>
                            </div>
                            <span class="text-[11px] text-slate-400 block mt-0.5 truncate">👤 {{ $ev->client ? $ev->client->name : 'Sin cliente' }} &bull; {{ $ev->event_date->format('d/m/Y') }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 pt-1">
                            <a href="{{ route('admin.events.show', $ev->id) }}" class="flex-1 text-center py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-[11px] font-bold rounded-lg transition">
                                Ficha
                            </a>
                            @if($ev->no_response_whatsapp_url)
                                <button type="button" onclick="window.open('{{ $ev->no_response_whatsapp_url }}', '_blank')" wire:click="promptFollowup({{ $ev->id }})" class="py-1.5 px-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-bold rounded-lg transition inline-flex items-center gap-1 cursor-pointer" title="Enviar WhatsApp y actualizar seguimiento">
                                    💬 Reactivar
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- EMBUDO DE MARKETING & OCUPACIÓN DE TEMPORADAS (MÉTODO MADRIGAL) -->
    @if(auth()->user()->role === 'admin')
        <div class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 border border-indigo-500/30 rounded-3xl p-6 shadow-xl text-white space-y-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-indigo-500/20 pb-5">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 border border-indigo-400/40 text-indigo-300 text-[11px] font-bold uppercase tracking-wider mb-2">
                        <span>🎯</span> Funnel de Conversión &amp; Agenda Comercial
                    </div>
                    <h3 class="text-xl sm:text-2xl font-black tracking-tight text-white flex items-center gap-2">
                        Embudo de Ventas &amp; Previsión de Temporadas
                    </h3>
                    <p class="text-xs text-indigo-200/70 mt-0.5">
                        Metodología Madrigal: De primer contacto a contrato firmado sin fugas de clientes.
                    </p>
                </div>
                
                <!-- Ratio de Conversión Global -->
                <div class="flex items-center gap-3 bg-white/5 border border-white/10 rounded-2xl p-3.5 backdrop-blur-md self-start md:self-auto">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 text-black flex items-center justify-center text-xl font-black shadow-lg shadow-amber-500/20">
                        ⚡
                    </div>
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-300">Ratio de Cierre</div>
                        <div class="text-2xl font-black text-amber-300 font-mono">
                            {{ $funnelMetrics['conversion_rate'] }}%
                        </div>
                        <div class="text-[10px] text-slate-400">Leads a bodas confirmadas</div>
                    </div>
                </div>
            </div>

            <!-- Fila 1: Las 4 fases del Funnel -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
                <!-- Fase 1: Leads -->
                <div class="bg-slate-950/60 border border-indigo-500/20 rounded-2xl p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between text-xs text-indigo-300 font-bold uppercase tracking-wider">
                        <span>1. Leads Recibidos</span>
                        <span>📥</span>
                    </div>
                    <div class="my-3">
                        <div class="text-3xl font-black text-white font-mono">{{ $funnelMetrics['total_leads'] }}</div>
                        <span class="text-[11px] text-emerald-400 font-semibold">+{{ $funnelMetrics['this_month_leads'] }} este mes</span>
                    </div>
                    <div class="text-[10px] text-slate-400 border-t border-white/5 pt-2">Vía web, WhatsApp y correo</div>
                </div>

                <!-- Fase 2: Presupuestos -->
                <div class="bg-slate-950/60 border border-indigo-500/20 rounded-2xl p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between text-xs text-indigo-300 font-bold uppercase tracking-wider">
                        <span>2. Presupuestos</span>
                        <span>📑</span>
                    </div>
                    <div class="my-3">
                        <div class="text-3xl font-black text-white font-mono">{{ $funnelMetrics['draft_count'] + $funnelMetrics['no_response_count'] }}</div>
                        <span class="text-[11px] text-indigo-300 font-semibold">Dossier + tarifa entregada</span>
                    </div>
                    <div class="text-[10px] text-slate-400 border-t border-white/5 pt-2">En proceso de valoración</div>
                </div>

                <!-- Fase 3: En Negociación / Seguimiento -->
                <div class="bg-slate-950/60 border border-purple-500/20 rounded-2xl p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between text-xs text-purple-300 font-bold uppercase tracking-wider">
                        <span>3. En Seguimiento</span>
                        <span>💬</span>
                    </div>
                    <div class="my-3">
                        <div class="text-3xl font-black text-purple-300 font-mono">{{ $funnelMetrics['no_response_count'] }}</div>
                        <span class="text-[11px] text-amber-300 font-semibold">Esperando respuesta</span>
                    </div>
                    <div class="text-[10px] text-slate-400 border-t border-white/5 pt-2">Secuencia Madrigal activa</div>
                </div>

                <!-- Fase 4: Confirmados -->
                <div class="bg-slate-950/60 border border-emerald-500/30 rounded-2xl p-4 flex flex-col justify-between bg-emerald-950/10">
                    <div class="flex items-center justify-between text-xs text-emerald-300 font-bold uppercase tracking-wider">
                        <span>4. Confirmados</span>
                        <span>💍</span>
                    </div>
                    <div class="my-3">
                        <div class="text-3xl font-black text-emerald-400 font-mono">{{ $funnelMetrics['confirmed_count'] }}</div>
                        <span class="text-[11px] text-emerald-300 font-semibold">+{{ $funnelMetrics['this_month_confirmed'] }} cerrados este mes</span>
                    </div>
                    <div class="text-[10px] text-slate-400 border-t border-white/5 pt-2">Con fecha asegurada o señal</div>
                </div>
            </div>

            <!-- Fila 2: Previsión y Ocupación Temporada 2027 vs 2028 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                
                <!-- TEMPORADA 2027 -->
                <div class="bg-slate-950/70 border border-amber-500/30 rounded-2xl p-5 space-y-3 relative overflow-hidden">
                    <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-amber-500/10 rounded-full blur-2xl pointer-events-none"></div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-base">📅</span>
                            <h4 class="text-sm font-extrabold text-amber-300 uppercase tracking-wider">Temporada 2027</h4>
                        </div>
                        <span class="px-2.5 py-1 rounded-full bg-amber-400/20 text-amber-300 text-xs font-bold border border-amber-400/30">
                            {{ $season2027['occupancy_pct'] }}% Ocupación
                        </span>
                    </div>

                    <div class="w-full bg-slate-800 rounded-full h-2.5 overflow-hidden">
                        <div class="bg-gradient-to-r from-amber-400 to-yellow-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ $season2027['occupancy_pct'] }}%"></div>
                    </div>

                    <div class="grid grid-cols-3 gap-2 pt-1 text-center">
                        <div class="bg-white/[0.03] p-2.5 rounded-xl border border-white/5">
                            <div class="text-lg font-black text-white font-mono">{{ $season2027['confirmed_count'] }}</div>
                            <div class="text-[10px] text-slate-400 uppercase font-semibold">Confirmadas</div>
                        </div>
                        <div class="bg-white/[0.03] p-2.5 rounded-xl border border-white/5">
                            <div class="text-lg font-black text-amber-300 font-mono">{{ $season2027['pipeline_count'] }}</div>
                            <div class="text-[10px] text-slate-400 uppercase font-semibold">En Negociación</div>
                        </div>
                        <div class="bg-white/[0.03] p-2.5 rounded-xl border border-white/5">
                            <div class="text-lg font-black text-emerald-400 font-mono">{{ number_format($season2027['confirmed_revenue'], 0, ',', '.') }} €</div>
                            <div class="text-[10px] text-slate-400 uppercase font-semibold">Facturado 2027</div>
                        </div>
                    </div>
                </div>

                <!-- TEMPORADA 2028 -->
                <div class="bg-slate-950/70 border border-cyan-500/30 rounded-2xl p-5 space-y-3 relative overflow-hidden">
                    <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-cyan-500/10 rounded-full blur-2xl pointer-events-none"></div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-base">🚀</span>
                            <h4 class="text-sm font-extrabold text-cyan-300 uppercase tracking-wider">Temporada 2028</h4>
                        </div>
                        <span class="px-2.5 py-1 rounded-full bg-cyan-400/20 text-cyan-300 text-xs font-bold border border-cyan-400/30">
                            Agenda Abierta
                        </span>
                    </div>

                    <div class="w-full bg-slate-800 rounded-full h-2.5 overflow-hidden">
                        <div class="bg-gradient-to-r from-cyan-400 to-blue-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ $season2028['occupancy_pct'] }}%"></div>
                    </div>

                    <div class="grid grid-cols-3 gap-2 pt-1 text-center">
                        <div class="bg-white/[0.03] p-2.5 rounded-xl border border-white/5">
                            <div class="text-lg font-black text-white font-mono">{{ $season2028['confirmed_count'] }}</div>
                            <div class="text-[10px] text-slate-400 uppercase font-semibold">Confirmadas</div>
                        </div>
                        <div class="bg-white/[0.03] p-2.5 rounded-xl border border-white/5">
                            <div class="text-lg font-black text-cyan-300 font-mono">{{ $season2028['pipeline_count'] }}</div>
                            <div class="text-[10px] text-slate-400 uppercase font-semibold">Solicitudes 2028</div>
                        </div>
                        <div class="bg-white/[0.03] p-2.5 rounded-xl border border-white/5">
                            <div class="text-lg font-black text-emerald-400 font-mono">{{ number_format($season2028['confirmed_revenue'], 0, ',', '.') }} €</div>
                            <div class="text-[10px] text-slate-400 uppercase font-semibold">Facturado 2028</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    @endif

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

    <!-- MODAL DE SEGUIMIENTO MULTIETAPA MADRIGAL WHATSAPP -->
    @if($confirmingFollowupEventId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-xs animate-in fade-in duration-150">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-xl w-full p-6 shadow-2xl relative max-h-[90vh] overflow-y-auto">
                
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl font-bold">
                            💬
                        </div>
                        <div>
                            <h3 class="text-base font-black text-slate-900 dark:text-white">
                                Seguimiento Madrigal · WhatsApp
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                <strong class="text-slate-800 dark:text-slate-200">{{ $confirmingFollowupClientName }}</strong> &bull; {{ $confirmingFollowupEventName }}
                            </p>
                        </div>
                    </div>
                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300">
                        {{ $confirmingFollowupDays <= 0 ? 'Puesto hoy' : ($confirmingFollowupDays === 1 ? '1 día sin respuesta' : "{$confirmingFollowupDays} días sin respuesta") }}
                    </span>
                </div>

                <!-- Selector de Plantilla Madrigal -->
                <div class="mt-4 space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                        Selecciona el guion de seguimiento:
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        @foreach($confirmingFollowupOptions as $optKey => $opt)
                            <button type="button" 
                                wire:click="selectFollowupTemplate('{{ $optKey }}')" 
                                class="p-2.5 rounded-xl border text-left text-xs font-medium transition cursor-pointer relative {{ $confirmingFollowupSelectedKey === $optKey ? 'border-emerald-500 bg-emerald-50/70 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200 ring-2 ring-emerald-500/30' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800/40' }}">
                                <div class="flex items-center justify-between gap-1 mb-1">
                                    <span class="text-sm">{{ $opt['icon'] }}</span>
                                    @if($opt['is_recommended'])
                                        <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.2 rounded bg-amber-500/20 text-amber-600 dark:text-amber-300 border border-amber-500/30">
                                            Recomendado
                                        </span>
                                    @endif
                                </div>
                                <div class="font-bold text-[11px] truncate">{{ $opt['stage'] }}</div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 truncate">{{ $opt['title'] }}</div>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Vista previa del mensaje -->
                <div class="mt-4 space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">Mensaje a enviar:</span>
                        <span class="text-[10px] text-slate-400">Listo para abrir en WhatsApp</span>
                    </div>
                    <div class="bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200/60 dark:border-emerald-800/40 rounded-2xl p-3.5 text-xs text-slate-800 dark:text-slate-200 whitespace-pre-wrap font-sans leading-relaxed max-h-44 overflow-y-auto">
{{ $confirmingFollowupText }}
                    </div>
                </div>

                <!-- Botón de apertura de WhatsApp -->
                <div class="mt-4">
                    <a href="{{ $confirmingFollowupUrl }}" target="_blank" class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-600/30 transition flex items-center justify-center gap-2">
                        <span>💬 Abrir WhatsApp y Enviar Mensaje</span>
                        <span class="text-xs">↗</span>
                    </a>
                </div>

                <!-- Confirmaciones de acción posterior -->
                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 space-y-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block text-center">
                        Una vez enviado o revisado:
                    </span>
                    <button type="button" wire:click="confirmFollowupSent" class="w-full py-2.5 px-3 bg-purple-600 hover:bg-purple-500 text-white font-extrabold text-xs rounded-xl transition flex items-center justify-center gap-2 cursor-pointer shadow-xs">
                        <span>✅ He enviado el mensaje</span>
                        <span class="text-[10px] opacity-80">(Poner contador a 0 hoy)</span>
                    </button>

                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" wire:click="markAsResponded('draft')" class="py-2 px-3 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-bold text-xs rounded-xl border border-indigo-200 dark:border-indigo-800 transition flex items-center justify-center gap-1 cursor-pointer" title="Marcar como atendido y pasar a borrador">
                            <span>📝 ¡Ha respondido!</span>
                        </button>
                        <button type="button" wire:click="markAsResponded('confirmed')" class="py-2 px-3 bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 font-bold text-xs rounded-xl border border-amber-200 dark:border-amber-800 transition flex items-center justify-center gap-1 cursor-pointer" title="Marcar como confirmado">
                            <span>🟢 Confirmar Evento</span>
                        </button>
                    </div>

                    <button type="button" wire:click="cancelFollowup" class="w-full py-1.5 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 font-medium text-xs transition cursor-pointer">
                        Cerrar sin cambiar contador
                    </button>
                </div>

            </div>
        </div>
    @endif

</div>
