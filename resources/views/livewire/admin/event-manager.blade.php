<div>
    <!-- Flash Messages (Inline Alert) -->
    @if (session()->has('message'))
        <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 mb-5 rounded-2xl shadow-xs flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="text-lg">✅</span>
                <span class="text-xs sm:text-sm font-bold">{{ session('message') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 font-black p-1 text-sm">&times;</button>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-rose-50 border-l-4 border-rose-500 text-rose-800 p-4 mb-5 rounded-2xl shadow-xs flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="text-lg">⚠️</span>
                <span class="text-xs sm:text-sm font-bold">{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-900 font-black p-1 text-sm">&times;</button>
        </div>
    @endif

    <!-- Header Actions -->
    <div class="mb-5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <h2 class="text-xl sm:text-2xl font-black text-gray-800 dark:text-white tracking-tight">Próximos Eventos</h2>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Planificación, asignación de DJ / Asistente y seguimiento integral.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <button type="button" wire:click="syncEmailLeads" wire:loading.attr="disabled" class="flex-1 sm:flex-none bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 disabled:opacity-60 text-white font-bold py-2.5 px-4 rounded-xl shadow-md shadow-emerald-600/20 text-xs sm:text-sm transition flex items-center justify-center gap-2 cursor-pointer">
                <span wire:loading.remove wire:target="syncEmailLeads">📩</span>
                <span wire:loading wire:target="syncEmailLeads" class="animate-spin">🔄</span>
                <span wire:loading.remove wire:target="syncEmailLeads">Sincronizar Leads</span>
                <span wire:loading wire:target="syncEmailLeads">Sincronizando...</span>
            </button>
            <button wire:click="openCreateModal" class="flex-1 sm:flex-none bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold py-2.5 px-5 rounded-xl shadow-md shadow-indigo-600/20 text-xs sm:text-sm transition flex items-center justify-center gap-2 cursor-pointer">
                <span>➕</span>
                <span>Nuevo Evento</span>
            </button>
        </div>
    </div>

    <!-- ALERTA GLOBAL DE SEGUIMIENTO (SI HAY EVENTOS SIN RESPUESTA) -->
    @if(($urgentNoResponseCount ?? 0) > 0)
        <div class="bg-gradient-to-r from-purple-50 via-rose-50 to-amber-50 dark:from-purple-950/40 dark:via-rose-950/40 dark:to-slate-900 border border-purple-200 dark:border-purple-800 p-4 mb-5 rounded-2xl shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="text-2xl p-2 bg-purple-100 dark:bg-purple-900/60 text-purple-700 dark:text-purple-300 rounded-xl shrink-0">⏳</span>
                <div>
                    <h4 class="text-xs sm:text-sm font-extrabold text-purple-950 dark:text-purple-200 flex items-center gap-2">
                        <span>Tienes {{ $urgentNoResponseCount }} {{ $urgentNoResponseCount === 1 ? 'cliente sin respuesta que requiere' : 'clientes sin respuesta que requieren' }} reactivación</span>
                        <span class="text-[10px] bg-rose-600 text-white font-black px-2 py-0.5 rounded-full uppercase tracking-wider animate-pulse">¡Atención!</span>
                    </h4>
                    <p class="text-[11px] text-purple-800 dark:text-purple-300 mt-0.5">Llevan más de 2 días sin contestar tras el presupuesto. Envía un WhatsApp táctico con psicología inversa para saber si cerramos la fecha.</p>
                </div>
            </div>
            <button type="button" wire:click="setStatusFilter('no_response')" class="w-full sm:w-auto bg-purple-700 hover:bg-purple-800 text-white font-bold text-xs px-4 py-2 rounded-xl shadow-xs whitespace-nowrap transition cursor-pointer">
                Filtrar Sin Respuesta →
            </button>
        </div>
    @endif

    <!-- ALERTA GLOBAL DE EVENTOS EN ESPERA CON MÁS DE 30 DÍAS (FIN DE PLAZO DE PRESUPUESTO) -->
    @if(($expiredOnHoldCount ?? 0) > 0)
        <div class="bg-gradient-to-r from-amber-50 via-orange-50 to-rose-50 dark:from-amber-950/40 dark:via-orange-950/40 dark:to-slate-900 border border-amber-300 dark:border-amber-700 p-4 mb-5 rounded-2xl shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="text-2xl p-2 bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300 rounded-xl shrink-0">⌛</span>
                <div>
                    <h4 class="text-xs sm:text-sm font-extrabold text-amber-950 dark:text-amber-200 flex items-center gap-2">
                        <span>Tienes {{ $expiredOnHoldCount }} {{ $expiredOnHoldCount === 1 ? 'evento en espera con más de 30 días en pausa' : 'eventos en espera con más de 30 días en pausa' }}</span>
                        <span class="text-[10px] bg-amber-600 text-white font-black px-2 py-0.5 rounded-full uppercase tracking-wider">Plazo 30d cumplido</span>
                    </h4>
                    <p class="text-[11px] text-amber-800 dark:text-amber-300 mt-0.5">Envíales el aviso de finalización del plazo del presupuesto por si más adelante desean solicitar uno nuevo con las tarifas vigentes.</p>
                </div>
            </div>
            <button type="button" wire:click="setStatusFilter('on_hold')" class="w-full sm:w-auto bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs px-4 py-2 rounded-xl shadow-xs whitespace-nowrap transition cursor-pointer">
                Ver En Espera (+30d) →
            </button>
        </div>
    @endif

    <!-- FILTROS RÁPIDOS POR ESTADO + INTERRUPTORES (SWITCHES) PARA OCULTAR/MOSTRAR COMPLETADOS Y CANCELADOS -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 mb-4 w-full">
        <div class="flex items-center gap-1.5 overflow-x-auto scrollbar-none pb-1">
            <button type="button" wire:click="setStatusFilter('all')" class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer {{ $status_filter === 'all' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white dark:bg-slate-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 border border-gray-200 dark:border-slate-700' }}">
                {{ (!$show_completed && !$show_cancelled) ? 'Activos' : 'Todos' }} ({{ $statusCounts['all'] ?? 0 }})
            </button>
            <button type="button" wire:click="setStatusFilter('no_response')" class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer flex items-center gap-1.5 {{ $status_filter === 'no_response' ? 'bg-purple-600 text-white shadow-xs' : 'bg-purple-50 dark:bg-purple-950/40 text-purple-800 dark:text-purple-300 hover:bg-purple-100 border border-purple-200 dark:border-purple-800' }}">
                <span>⏳ Sin Respuesta</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ ($statusCounts['no_response'] ?? 0) > 0 ? 'bg-purple-200 dark:bg-purple-800 text-purple-900 dark:text-white font-black' : 'opacity-60' }}">{{ $statusCounts['no_response'] ?? 0 }}</span>
            </button>
            <button type="button" wire:click="setStatusFilter('on_hold')" class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer flex items-center gap-1.5 {{ $status_filter === 'on_hold' ? 'bg-cyan-600 text-white shadow-xs' : 'bg-cyan-50 dark:bg-cyan-950/40 text-cyan-800 dark:text-cyan-300 hover:bg-cyan-100 border border-cyan-200 dark:border-cyan-800' }}">
                <span>⏸️ En Espera</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ ($statusCounts['on_hold'] ?? 0) > 0 ? 'bg-cyan-200 dark:bg-cyan-800 text-cyan-900 dark:text-white font-black' : 'opacity-60' }}">{{ $statusCounts['on_hold'] ?? 0 }}</span>
                @if(($expiredOnHoldCount ?? 0) > 0)
                    <span class="px-1.5 py-0.2 rounded-full text-[9px] bg-amber-500 text-white font-black" title="Con más de 30 días en pausa">+30d: {{ $expiredOnHoldCount }}</span>
                @endif
            </button>
            <button type="button" wire:click="setStatusFilter('confirmed')" class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer {{ $status_filter === 'confirmed' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white dark:bg-slate-800 text-emerald-800 dark:text-emerald-300 hover:bg-emerald-50 border border-gray-200 dark:border-slate-700' }}">
                🟢 Confirmados ({{ $statusCounts['confirmed'] ?? 0 }})
            </button>
            <button type="button" wire:click="setStatusFilter('draft')" class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer {{ $status_filter === 'draft' ? 'bg-amber-600 text-white shadow-xs' : 'bg-white dark:bg-slate-800 text-amber-800 dark:text-amber-300 hover:bg-amber-50 border border-gray-200 dark:border-slate-700' }}">
                📝 Borradores ({{ $statusCounts['draft'] ?? 0 }})
            </button>
            @if($show_completed || $status_filter === 'completed')
                <button type="button" wire:click="setStatusFilter('completed')" class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer {{ $status_filter === 'completed' ? 'bg-blue-600 text-white shadow-xs' : 'bg-white dark:bg-slate-800 text-blue-800 dark:text-blue-300 hover:bg-blue-50 border border-gray-200 dark:border-slate-700' }}">
                    🔵 Completados ({{ $statusCounts['completed'] ?? 0 }})
                </button>
            @endif
            @if($show_cancelled || in_array($status_filter, ['cancelled', 'rejected'], true))
                <button type="button" wire:click="setStatusFilter('cancelled')" class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer {{ in_array($status_filter, ['cancelled', 'rejected'], true) ? 'bg-rose-600 text-white shadow-xs' : 'bg-white dark:bg-slate-800 text-rose-700 dark:text-rose-300 hover:bg-rose-50 border border-rose-200 dark:border-rose-800' }}">
                    🔴 Cancelados ({{ $statusCounts['cancelled'] ?? 0 }})
                </button>
            @endif
        </div>

        <!-- INTERRUPTORES (SWITCHES) PARA MOSTRAR / OCULTAR COMPLETADOS Y CANCELADOS -->
        <div class="flex flex-wrap items-center gap-3 bg-white/80 dark:bg-slate-900/90 border border-gray-200 dark:border-slate-800 px-3.5 py-2 rounded-2xl shadow-2xs shrink-0">
            <!-- Switch Completados -->
            <button type="button"
                    wire:click="toggleShowCompleted"
                    class="inline-flex items-center gap-2 text-xs font-bold cursor-pointer select-none transition {{ $show_completed ? 'text-blue-700 dark:text-blue-300' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200' }}"
                    title="{{ $show_completed ? 'Ocultar eventos completados' : 'Mostrar eventos completados' }}">
                <span class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors duration-200 {{ $show_completed ? 'bg-blue-600' : 'bg-gray-300 dark:bg-slate-700' }}">
                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform duration-200 {{ $show_completed ? 'translate-x-4' : 'translate-x-1' }}"></span>
                </span>
                <span>🔵 Completados ({{ $statusCounts['completed'] ?? 0 }})</span>
            </button>

            <span class="h-4 w-px bg-gray-200 dark:bg-slate-800"></span>

            <!-- Switch Cancelados -->
            <button type="button"
                    wire:click="toggleShowCancelled"
                    class="inline-flex items-center gap-2 text-xs font-bold cursor-pointer select-none transition {{ $show_cancelled ? 'text-rose-700 dark:text-rose-300' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200' }}"
                    title="{{ $show_cancelled ? 'Ocultar eventos cancelados' : 'Mostrar eventos cancelados' }}">
                <span class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors duration-200 {{ $show_cancelled ? 'bg-rose-600' : 'bg-gray-300 dark:bg-slate-700' }}">
                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform duration-200 {{ $show_cancelled ? 'translate-x-4' : 'translate-x-1' }}"></span>
                </span>
                <span>🔴 Cancelados ({{ $statusCounts['cancelled'] ?? 0 }})</span>
            </button>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- VISTA MÓVIL Y TABLET: TARJETAS RESPONSIVAS (< 1024px)      -->
    <!-- ========================================================== -->
    <div class="block lg:hidden space-y-3 mb-6">
        @forelse($events as $event)
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-3.5 sm:p-4 border border-gray-200 dark:border-slate-800 shadow-sm transition space-y-2.5">
                
                <!-- Encabezado de Tarjeta: Nombre clicable + Botón Gestionar Inmediato Arriba -->
                <div class="flex items-start justify-between gap-2 border-b border-gray-100 dark:border-slate-800 pb-2.5">
                    <a href="{{ route('admin.events.show', $event->id) }}" class="flex items-center gap-2 min-w-0 flex-1 group">
                        <span class="text-lg p-1.5 bg-indigo-50 dark:bg-slate-800 rounded-xl shrink-0">{{ $event->event_type_icon }}</span>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-extrabold text-sm text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 truncate leading-tight transition">
                                {{ $event->name }}
                            </h3>
                            <div class="flex flex-wrap items-center gap-1.5 mt-0.5">
                                <span class="text-[10px] text-indigo-700 dark:text-indigo-400 font-bold uppercase tracking-wider">
                                    {{ $event->event_type_label }}
                                </span>
                                <span class="px-2 py-0.2 text-[10px] font-bold rounded-full border {{ $event->status_badge_class }}">
                                    {{ $event->status_icon }} {{ $event->status_label }}
                                </span>
                            </div>
                        </div>
                    </a>

                    <!-- Acceso Rápido Directo en Cabecera (Siempre visible sin hacer scroll) -->
                    <div class="flex items-center gap-1.5 shrink-0">
                        <a href="{{ route('admin.events.show', $event->id) }}" class="inline-flex items-center gap-1 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-extrabold text-xs px-3 py-2 rounded-xl shadow-xs transition">
                            <span>⚙️</span>
                            <span>Gestionar</span>
                            <span>→</span>
                        </a>
                    </div>
                </div>

                @if($event->status === 'no_response')
                    <div class="flex flex-wrap items-center justify-between gap-2 bg-purple-50/80 dark:bg-purple-950/40 px-2.5 py-1.5 rounded-xl border border-purple-200 dark:border-purple-800/60">
                        <span class="text-[11px] font-semibold {{ $event->no_response_urgency_level === 'critical' ? 'text-rose-600 font-bold' : ($event->no_response_urgency_level === 'warning' ? 'text-amber-600 font-bold' : 'text-purple-700 dark:text-purple-300') }}">
                            ⏳ {{ $event->no_response_time_label }}
                        </span>
                        <div class="flex items-center gap-1 shrink-0">
                            <button type="button" wire:click="putEventOnHold({{ $event->id }})" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-[10px] py-1 px-2 rounded-lg shadow-2xs transition inline-flex items-center gap-1 shrink-0 cursor-pointer" title="Pasar a En Espera (el cliente dijo que ya avisará)">
                                ⏸️ En Espera
                            </button>
                            @if($event->no_response_whatsapp_url)
                                <button type="button" onclick="window.open('{{ $event->no_response_whatsapp_url }}', '_blank')" wire:click="promptFollowup({{ $event->id }})" class="bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-[11px] py-1 px-2.5 rounded-lg shadow-2xs transition inline-flex items-center gap-1 shrink-0 cursor-pointer">
                                    💬 Recordar
                                </button>
                            @else
                                <button type="button" wire:click="promptFollowup({{ $event->id }})" class="bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-[11px] py-1 px-2.5 rounded-lg shadow-2xs transition inline-flex items-center gap-1 shrink-0 cursor-pointer">
                                    📧 Recordar Email
                                </button>
                            @endif
                        </div>
                    </div>
                @elseif($event->status === 'on_hold')
                    <div class="flex flex-wrap items-center justify-between gap-2 {{ $event->is_on_hold_expired ? 'bg-amber-50/90 dark:bg-amber-950/40 border-amber-300 dark:border-amber-800' : 'bg-cyan-50/80 dark:bg-cyan-950/40 border-cyan-200 dark:border-cyan-800/60' }} px-2.5 py-1.5 rounded-xl border">
                        <span class="text-[11px] font-bold {{ $event->is_on_hold_expired ? 'text-amber-800 dark:text-amber-300' : 'text-cyan-800 dark:text-cyan-300' }}">
                            ⏸️ {{ $event->on_hold_time_label }}
                            @if($event->is_on_hold_expired)
                                <span class="ml-1 text-[10px] bg-amber-600 text-white px-1.5 py-0.2 rounded font-black uppercase">Fin plazo 30d</span>
                            @endif
                        </span>
                        <div class="flex items-center gap-1 shrink-0">
                            @if($event->on_hold_expired_whatsapp_url)
                                <button type="button" onclick="window.open('{{ $event->on_hold_expired_whatsapp_url }}', '_blank')" wire:click="promptFollowup({{ $event->id }}, 'fin_plazo_presupuesto')" class="{{ $event->is_on_hold_expired ? 'bg-amber-600 hover:bg-amber-700 text-white' : 'bg-cyan-100 hover:bg-cyan-200 text-cyan-800 dark:bg-cyan-900/60 dark:text-cyan-200' }} font-extrabold text-[11px] py-1 px-2.5 rounded-lg shadow-2xs transition inline-flex items-center gap-1 shrink-0 cursor-pointer">
                                    ⌛ {{ $event->is_on_hold_expired ? 'Aviso Fin Plazo (30d)' : 'Aviso Fin Plazo' }}
                                </button>
                            @else
                                <button type="button" wire:click="promptFollowup({{ $event->id }}, 'fin_plazo_presupuesto')" class="{{ $event->is_on_hold_expired ? 'bg-amber-600 hover:bg-amber-700 text-white' : 'bg-cyan-100 hover:bg-cyan-200 text-cyan-800 dark:bg-cyan-900/60 dark:text-cyan-200' }} font-extrabold text-[11px] py-1 px-2.5 rounded-lg shadow-2xs transition inline-flex items-center gap-1 shrink-0 cursor-pointer">
                                    📧 {{ $event->is_on_hold_expired ? 'Email Fin Plazo (30d)' : 'Email Fin Plazo' }}
                                </button>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Detalles Rápidos: Fecha, Lugar y Cliente -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
                    <div class="bg-gray-50 dark:bg-slate-800/60 p-2 rounded-xl border border-gray-100 dark:border-slate-800">
                        <span class="text-[10px] text-gray-400 dark:text-gray-400 uppercase font-bold block">📅 Fecha</span>
                        <span class="font-bold text-gray-800 dark:text-slate-200">{{ $event->event_date ? $event->event_date->format('d/m/Y') : 'Por definir' }}</span>
                    </div>
                    <div class="bg-gray-50 dark:bg-slate-800/60 p-2 rounded-xl border border-gray-100 dark:border-slate-800">
                        <span class="text-[10px] text-gray-400 dark:text-gray-400 uppercase font-bold block">📍 Lugar</span>
                        <span class="font-bold text-gray-800 dark:text-slate-200 truncate block" title="{{ $event->location }}">{{ $event->location }}</span>
                    </div>
                    <div class="col-span-2 sm:col-span-1 bg-gray-50 dark:bg-slate-800/60 p-2 rounded-xl border border-gray-100 dark:border-slate-800 flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <span class="text-[10px] text-gray-400 dark:text-gray-400 uppercase font-bold block">👤 Cliente</span>
                            <span class="font-bold text-gray-800 dark:text-slate-200 truncate block">{{ $event->client ? $event->client->name : 'Sin asignar' }}</span>
                        </div>
                        @if($event->client && $event->client->phone)
                            @php
                                $cPhone = preg_replace('/[^0-9]/', '', $event->client->phone);
                                if (strlen($cPhone) === 9 && in_array(substr($cPhone, 0, 1), ['6', '7'])) {
                                    $cPhone = '34' . $cPhone;
                                }
                            @endphp
                            <a href="https://wa.me/{{ $cPhone }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 border border-emerald-300 dark:border-emerald-800 px-2 py-1 rounded-lg transition shrink-0" title="Abrir chat de WhatsApp">
                                💬 WA
                            </a>
                        @elseif($event->client_contact_email)
                            <button type="button" wire:click="promptFollowup({{ $event->id }})" class="inline-flex items-center gap-1 text-[11px] font-bold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 border border-indigo-300 dark:border-indigo-800 px-2 py-1 rounded-lg transition shrink-0 cursor-pointer" title="{{ $event->client_contact_email }}">
                                📧 Email
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Montaje, Baile y Personal en línea compacta -->
                <div class="flex flex-wrap items-center justify-between gap-1.5 text-[11px] pt-0.5">
                    <div class="flex flex-wrap items-center gap-1.5">
                        @if($event->dj)
                            <span class="bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-bold px-2 py-0.5 rounded-lg border border-indigo-200 dark:border-indigo-900/60 inline-flex items-center gap-1">
                                🎧 {{ $event->dj->name }}
                            </span>
                        @endif
                        @php
                            $mobileAsts = $event->all_assistants;
                        @endphp
                        @if($mobileAsts->isNotEmpty())
                            <span class="bg-amber-50 dark:bg-amber-950/50 text-amber-800 dark:text-amber-300 font-bold px-2 py-0.5 rounded-lg border border-amber-200 dark:border-amber-900/60 inline-flex items-center gap-1">
                                👷‍♂️ {{ $mobileAsts->pluck('name')->implode(', ') }}
                            </span>
                        @endif
                        @if($event->dance_start_time)
                            <span class="bg-purple-50 dark:bg-purple-950/40 text-purple-800 dark:text-purple-300 font-bold px-2 py-0.5 rounded-lg border border-purple-200 dark:border-purple-900/50">
                                ⏰ {{ substr((string)$event->dance_start_time, 0, 5) }}h
                            </span>
                        @endif
                    </div>

                    <!-- Acciones Rápidas Secundarias (Editar / Eliminar) -->
                    <div class="flex items-center gap-1.5 ml-auto">
                        <button type="button" wire:click="openEditModal({{ $event->id }})" class="px-2.5 py-1.5 rounded-xl bg-amber-50 dark:bg-amber-950/50 hover:bg-amber-100 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 font-bold text-[11px] transition cursor-pointer inline-flex items-center gap-1" title="Editar Evento">
                            <span>✏️</span>
                            <span>Editar</span>
                        </button>
                        @if(auth()->user()->role === 'admin')
                            <button type="button" wire:click="deleteEvent({{ $event->id }})" wire:confirm="¿Estás seguro de que deseas eliminar permanentemente el evento '{{ $event->name }}'?" class="px-2.5 py-1.5 rounded-xl bg-rose-50 dark:bg-rose-950/50 hover:bg-rose-100 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-900/60 font-bold text-[11px] transition cursor-pointer" title="Eliminar Evento">
                                🗑️
                            </button>
                        @endif
                    </div>
                </div>

            </div>
        @empty
            <div class="p-8 text-center bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 text-gray-500 dark:text-gray-400">
                <span class="text-3xl block mb-2">🎉</span>
                <p class="font-bold text-sm">No hay eventos registrados.</p>
                <p class="text-xs text-gray-400 mt-1">Crea tu primer evento pulsando en el botón superior.</p>
            </div>
        @endforelse
    </div>

    <!-- ========================================================== -->
    <!-- VISTA ESCRITORIO (>= 1024px): TABLA 100% RESPONSIVE SIN SCROLL HORIZONTAL -->
    <!-- ========================================================== -->
    <div class="hidden lg:block bg-white dark:bg-slate-900 shadow-sm rounded-2xl border border-gray-200 dark:border-slate-800 overflow-hidden">
        <div class="w-full">
            <table class="w-full divide-y divide-gray-200 dark:divide-slate-800 table-fixed text-left">
                <thead class="bg-gray-50 dark:bg-slate-950">
                    <tr>
                        <th class="w-[26%] px-3 py-3 text-left text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Evento y Personal</th>
                        <th class="w-[12%] px-2.5 py-3 text-left text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha</th>
                        <th class="w-[16%] px-2.5 py-3 text-left text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Lugar</th>
                        <th class="w-[16%] px-2.5 py-3 text-left text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cliente</th>
                        <th class="w-[15%] px-2.5 py-3 text-left text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                        <th class="w-[15%] px-3 py-3 text-right text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800">
                    @forelse($events as $event)
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-slate-800/50 transition">
                            <td class="px-3 py-2.5 min-w-0">
                                <a href="{{ route('admin.events.show', $event->id) }}" class="flex items-center gap-1.5 min-w-0 group">
                                    <span class="text-sm shrink-0">{{ $event->event_type_icon }}</span>
                                    <span class="text-xs xl:text-sm font-extrabold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 truncate transition" title="{{ $event->name }}">{{ $event->name }}</span>
                                </a>
                                <div class="flex flex-wrap items-center gap-1 mt-1">
                                    <span class="text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold px-1.5 py-0.5 rounded-md">
                                        {{ $event->event_type_label }}
                                    </span>
                                    @if($event->dj)
                                        <span class="text-[10px] text-indigo-700 dark:text-indigo-400 font-semibold inline-flex items-center gap-0.5 truncate max-w-full">
                                            🎧 {{ $event->dj->name }}
                                        </span>
                                    @endif
                                    @php
                                        $assignedAsts = $event->all_assistants;
                                    @endphp
                                    @if($assignedAsts->isNotEmpty())
                                        <span class="text-[10px] text-amber-700 dark:text-amber-400 font-semibold inline-flex items-center gap-0.5 truncate max-w-full" title="{{ $assignedAsts->pluck('name')->implode(', ') }}">
                                            👷‍♂️ {{ $assignedAsts->pluck('name')->implode(', ') }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-2.5 py-2.5">
                                <div class="text-xs text-gray-900 dark:text-slate-200 font-bold">{{ $event->event_date ? $event->event_date->format('d/m/Y') : 'Por definir' }}</div>
                                @if($event->setup_date || $event->start_time)
                                    <div class="text-[10px] text-amber-800 dark:text-amber-300 font-bold flex items-center gap-1 mt-0.5 truncate" title="{{ $event->setup_schedule_label }}">
                                        <span>🚗</span>
                                        <span class="truncate">{{ $event->setup_schedule_label }}</span>
                                    </div>
                                @endif
                                @if($event->dance_start_time)
                                    <div class="text-[10px] text-purple-700 dark:text-purple-300 font-bold flex items-center gap-1 mt-0.5">
                                        <span>⏰</span>
                                        <span>{{ substr((string)$event->dance_start_time, 0, 5) }}h @if($event->dance_duration_hours)({{ (float)$event->dance_duration_hours }}h)@endif</span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-2.5 py-2.5 text-xs text-gray-600 dark:text-slate-400 truncate" title="{{ $event->location }}">
                                📍 {{ $event->location }}
                            </td>
                            <td class="px-2.5 py-2.5 text-xs text-gray-600 dark:text-slate-400 min-w-0">
                                <div class="font-bold text-gray-800 dark:text-slate-200 truncate" title="{{ $event->client ? $event->client->name : 'Sin asignar' }}">{{ $event->client ? $event->client->name : 'Sin asignar' }}</div>
                                @if($event->client && $event->client->phone)
                                    @php
                                        $cPhone = preg_replace('/[^0-9]/', '', $event->client->phone);
                                        if (strlen($cPhone) === 9 && in_array(substr($cPhone, 0, 1), ['6', '7'])) {
                                            $cPhone = '34' . $cPhone;
                                        }
                                    @endphp
                                    <a href="https://wa.me/{{ $cPhone }}" target="_blank" class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 border border-emerald-200 dark:border-emerald-800 px-1.5 py-0.5 rounded-lg mt-0.5 transition max-w-full truncate" title="Abrir chat de WhatsApp">
                                        💬 {{ $event->client->phone }}
                                    </a>
                                @elseif($event->client_contact_email)
                                    <button type="button" wire:click="promptFollowup({{ $event->id }})" class="inline-flex items-center gap-1 text-[10px] font-bold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 border border-indigo-200 dark:border-indigo-800 px-1.5 py-0.5 rounded-lg mt-0.5 transition max-w-full truncate cursor-pointer" title="Enviar seguimiento o correo a {{ $event->client_contact_email }}">
                                        📧 {{ $event->client_contact_email }}
                                    </button>
                                @endif
                            </td>
                            <td class="px-2.5 py-2.5">
                                <span class="px-2 py-0.5 inline-flex text-[10px] xl:text-[11px] leading-4 font-bold rounded-full border {{ $event->status_badge_class }}">
                                    {{ $event->status_icon }} {{ $event->status_label }}
                                </span>
                                @if($event->status === 'no_response')
                                    <div class="mt-1 flex flex-wrap items-center gap-1">
                                        <span class="text-[10px] font-semibold {{ $event->no_response_urgency_level === 'critical' ? 'text-rose-600 font-bold' : ($event->no_response_urgency_level === 'warning' ? 'text-amber-600 font-bold' : 'text-purple-600') }}">
                                            {{ $event->no_response_time_label }}
                                        </span>
                                        @if($event->no_response_whatsapp_url)
                                            <button type="button" onclick="window.open('{{ $event->no_response_whatsapp_url }}', '_blank')" wire:click="promptFollowup({{ $event->id }})" class="inline-flex items-center gap-0.5 text-[10px] bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:hover:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 px-1.5 py-0.5 rounded font-bold transition cursor-pointer" title="Enviar recordatorio táctico de WhatsApp (Técnica del NO)">
                                                💬 Recordar
                                            </button>
                                        @else
                                            <button type="button" wire:click="promptFollowup({{ $event->id }})" class="inline-flex items-center gap-0.5 text-[10px] bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 border border-indigo-300 dark:border-indigo-800 px-1.5 py-0.5 rounded font-bold transition cursor-pointer" title="Enviar seguimiento táctico por Email">
                                                📧 Recordar Email
                                            </button>
                                        @endif
                                        <button type="button" wire:click="putEventOnHold({{ $event->id }})" class="inline-flex items-center gap-0.5 text-[10px] bg-cyan-50 hover:bg-cyan-100 dark:bg-cyan-950/60 dark:hover:bg-cyan-900/60 text-cyan-700 dark:text-cyan-300 border border-cyan-300 dark:border-cyan-800 px-1.5 py-0.5 rounded font-bold transition cursor-pointer" title="Pasar a En Espera (el cliente dijo que ya se pondrá en contacto)">
                                            ⏸️ En Espera
                                        </button>
                                    </div>
                                @elseif($event->status === 'on_hold')
                                    <div class="mt-1 flex flex-wrap items-center gap-1">
                                        <span class="text-[10px] font-bold {{ $event->is_on_hold_expired ? 'text-amber-700 dark:text-amber-300' : 'text-cyan-700 dark:text-cyan-300' }}">
                                            {{ $event->on_hold_time_label }}
                                        </span>
                                        @if($event->is_on_hold_expired)
                                            <span class="text-[9px] bg-amber-600 text-white font-black px-1.5 py-0.2 rounded uppercase" title="Han pasado más de 30 días en pausa: vence la validez del presupuesto">
                                                +30d · Fin plazo
                                            </span>
                                            @if($event->on_hold_expired_whatsapp_url)
                                                <button type="button" onclick="window.open('{{ $event->on_hold_expired_whatsapp_url }}', '_blank')" wire:click="promptFollowup({{ $event->id }}, 'fin_plazo_presupuesto')" class="inline-flex items-center gap-0.5 text-[10px] bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-200 border border-amber-400 dark:border-amber-700 px-1.5 py-0.5 rounded font-extrabold transition cursor-pointer" title="Enviar mensaje de fin de plazo del presupuesto (+30 días)">
                                                    ⌛ Aviso Fin Plazo
                                                </button>
                                            @else
                                                <button type="button" wire:click="promptFollowup({{ $event->id }}, 'fin_plazo_presupuesto')" class="inline-flex items-center gap-0.5 text-[10px] bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-200 border border-amber-400 dark:border-amber-700 px-1.5 py-0.5 rounded font-extrabold transition cursor-pointer" title="Enviar por Email aviso de fin de plazo del presupuesto (+30 días)">
                                                    📧 Aviso Fin Plazo
                                                </button>
                                            @endif
                                        @else
                                            <button type="button" wire:click="promptFollowup({{ $event->id }}, 'fin_plazo_presupuesto')" class="inline-flex items-center gap-0.5 text-[10px] bg-slate-100 hover:bg-cyan-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 px-1.5 py-0.5 rounded font-semibold transition cursor-pointer" title="Ver o enviar aviso de fin de plazo de presupuesto (30 días)">
                                                ⌛ Aviso 30d
                                            </button>
                                        @endif
                                    </div>
                                @elseif(in_array($event->status, ['rejected', 'cancelled']) && $event->cancellation_reason_display)
                                    <div class="mt-1 text-[10px] font-bold text-rose-700 dark:text-rose-300 bg-rose-50/80 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800/70 px-1.5 py-0.5 rounded-md truncate max-w-[180px]" title="Motivo: {{ $event->cancellation_reason_display }}">
                                        📌 {{ $event->cancellation_reason_display }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-right text-xs font-medium">
                                <div class="inline-flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.events.show', $event->id) }}" class="inline-flex items-center gap-1 text-white bg-indigo-600 hover:bg-indigo-700 font-extrabold px-2.5 py-1.5 rounded-xl shadow-2xs transition text-[11px]">
                                        <span>⚙️</span>
                                        <span>Gestionar</span>
                                    </a>
                                    <button type="button" wire:click="openEditModal({{ $event->id }})" class="text-amber-600 hover:text-amber-900 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/60 border border-amber-200 dark:border-amber-800 px-2 py-1.5 rounded-xl transition cursor-pointer" title="Editar Evento">
                                        ✏️
                                    </button>
                                    @if(auth()->user()->role === 'admin')
                                        <button type="button" wire:click="deleteEvent({{ $event->id }})" wire:confirm="¿Estás seguro de que deseas eliminar permanentemente el evento '{{ $event->name }}' y todos sus presupuestos, contratos y música?" class="text-rose-600 hover:text-rose-900 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/60 px-1.5 py-1.5 rounded-xl transition cursor-pointer" title="Eliminar Evento">
                                            🗑️
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No hay eventos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- MODAL DE CREACIÓN / EDICIÓN DE EVENTO (SCROLLABLE MÓVIL) -->
    <!-- ============================================== -->
    @if($showCreateModal)
    <div class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-3 sm:p-4 text-center">
            
            <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs transition-opacity" aria-hidden="true" wire:click="closeCreateModal"></div>
            
            <div class="relative bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all w-full max-w-lg max-h-[90vh] flex flex-col border border-gray-200 dark:border-slate-800">
                
                <!-- Header Modal -->
                <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between bg-gray-50 dark:bg-slate-950">
                    <h3 class="text-base sm:text-lg font-black text-gray-900 dark:text-white flex items-center gap-2" id="modal-title">
                        <span>{{ $editing_event_id ? '✏️' : '✨' }}</span>
                        <span>{{ $editing_event_id ? 'Editar Evento: ' . $name : 'Crear Nuevo Evento' }}</span>
                    </h3>
                    <button type="button" wire:click="closeCreateModal" class="text-gray-400 hover:text-gray-700 dark:hover:text-white p-1 rounded-xl text-lg font-black">&times;</button>
                </div>
                
                <!-- Formulario con scroll vertical garantizado en móviles -->
                <div class="p-5 overflow-y-auto flex-1 space-y-4">
                    <form wire:submit.prevent="saveEvent" id="createEventForm">
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 mb-3">
                            <div class="sm:col-span-2">
                                <label class="block text-gray-700 dark:text-slate-300 text-xs font-bold mb-1">Nombre del Evento *</label>
                                <input type="text" wire:model="name" placeholder="Ej: Boda Laura y Carlos / Enlace Marta y Javier" class="border dark:border-slate-700 rounded-xl w-full py-2 px-3 text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-800 text-sm focus:ring-indigo-500 focus:border-indigo-500 font-bold">
                                @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-gray-700 dark:text-slate-300 text-xs font-bold mb-1">Marca / Perfil *</label>
                                <select wire:model.live="brand" class="border dark:border-slate-700 rounded-xl w-full py-2 px-2 text-gray-700 dark:text-slate-200 text-xs font-bold focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-slate-800">
                                    <option value="nunez_and_son">👑 Núñez & Son</option>
                                    <option value="javnx">🎧 JAVNX DJ</option>
                                    <option value="mago_leugim">🎩 Mago Leugim</option>
                                </select>
                                @error('brand') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-gray-700 dark:text-slate-300 text-xs font-bold mb-1">Tipo *</label>
                                <select wire:model.live="event_type" class="border dark:border-slate-700 rounded-xl w-full py-2 px-2 text-gray-700 dark:text-slate-200 text-xs font-bold focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-slate-800">
                                    <option value="boda">💍 Boda</option>
                                    @if($event_type === 'video' || \App\Models\Setting::isVideoServiceEnabled($brand))
                                        <option value="video">📹 Vídeo / Videógrafo</option>
                                    @endif
                                    <option value="empresa">🏢 Empresa</option>
                                    <option value="cumpleanos">🎂 Cumpleaños</option>
                                    <option value="comunion">🕊️ Comunión</option>
                                    <option value="otro">🎉 Fiesta / Otro</option>
                                </select>
                                @error('event_type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Sección Exclusiva para Servicio de Vídeo / Colaboración -->
                        @if($event_type === 'video')
                            <div class="p-3.5 bg-gradient-to-r from-indigo-50 via-purple-50 to-pink-50 dark:from-slate-800 dark:via-indigo-950/40 dark:to-purple-950/40 rounded-2xl border-2 border-indigo-200 dark:border-indigo-800 space-y-3 mb-3 shadow-xs">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">📹</span>
                                        <div>
                                            <h4 class="text-xs font-extrabold text-indigo-950 dark:text-indigo-200">Servicio de Videógrafo / Colaboración</h4>
                                            <p class="text-[10px] text-indigo-700 dark:text-indigo-300">Reserva la fecha con importe directo y sin necesidad de packs de DJ.</p>
                                        </div>
                                    </div>
                                    <span class="text-[10px] bg-indigo-600 text-white font-black px-2 py-0.5 rounded-full uppercase tracking-wider">Vídeo</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                    <div>
                                        <label class="block text-indigo-950 dark:text-indigo-300 text-xs font-bold mb-1">💶 Precio Acordado (€) *</label>
                                        <div class="relative">
                                            <input type="number" step="1" min="0" wire:model="agreed_price" placeholder="250" class="border border-indigo-300 dark:border-indigo-700 rounded-xl w-full py-1.5 px-3 text-gray-900 dark:text-white text-sm font-black bg-white dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500 pr-8">
                                            <span class="absolute right-3 top-2 text-xs font-bold text-gray-400">€</span>
                                        </div>
                                        <span class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5 block">Crea propuesta automática por este importe (ej. 250 €).</span>
                                        @error('agreed_price') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-indigo-950 dark:text-indigo-300 text-xs font-bold mb-1">📌 Estado de la Reserva *</label>
                                        <select wire:model="initial_status" class="border border-indigo-300 dark:border-indigo-700 rounded-xl w-full py-2 px-3 text-gray-900 dark:text-white text-xs font-bold bg-white dark:bg-slate-900 focus:ring-indigo-500 focus:border-indigo-500">
                                            <option value="confirmed">🟢 Confirmado (Bloquea la fecha en calendario)</option>
                                            <option value="draft">🟡 Borrador / Pendiente</option>
                                        </select>
                                        <span class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5 block">Confirmado reserva tu día de inmediato para que nadie más lo ocupe.</span>
                                    </div>
                                </div>

                                <div class="pt-2 border-t border-indigo-200/60 dark:border-indigo-900/60">
                                    <label class="block text-indigo-950 dark:text-indigo-300 text-xs font-bold mb-1">🤝 ¿Quién te contrata? (Fotógrafo / Productora / Colega)</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <div>
                                            <input type="text" wire:model="new_client_name" placeholder="Nombre (Ej: Jon Fotógrafo / Estudio X)" class="border border-indigo-200 dark:border-indigo-800 rounded-xl w-full py-1.5 px-3 text-xs bg-white dark:bg-slate-900 text-gray-900 dark:text-white font-semibold">
                                        </div>
                                        <div>
                                            <input type="text" wire:model="new_client_phone" placeholder="Teléfono / WhatsApp (Ej: 612345678)" class="border border-indigo-200 dark:border-indigo-800 rounded-xl w-full py-1.5 px-3 text-xs bg-white dark:bg-slate-900 text-gray-900 dark:text-white">
                                        </div>
                                    </div>
                                    <span class="text-[10px] text-indigo-700 dark:text-indigo-300 mt-1 block">Rellena esto si es un contacto nuevo, o selecciónalo abajo si ya existe.</span>
                                </div>
                            </div>
                        @endif
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-gray-700 dark:text-slate-300 text-xs font-bold mb-1">Fecha *</label>
                                <input type="date" wire:model="event_date" class="border dark:border-slate-700 rounded-xl w-full py-2 px-3 text-gray-700 dark:text-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-slate-800 font-bold">
                                @error('event_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-gray-700 dark:text-slate-300 text-xs font-bold mb-1">Lugar / Finca / Salón *</label>
                                <input type="text" wire:model="location" placeholder="Ej: Finca El Portón" class="border dark:border-slate-700 rounded-xl w-full py-2 px-3 text-gray-700 dark:text-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-slate-800">
                                @error('location') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Horarios y Montaje (Formato 24h) -->
                        <div class="p-3.5 bg-purple-50/80 dark:bg-purple-950/40 rounded-2xl border border-purple-100 dark:border-purple-900/40 space-y-3 mb-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black uppercase text-purple-900 dark:text-purple-300 tracking-wider">⏰ Horarios & Montaje</span>
                                <span class="text-[10px] text-purple-700 dark:text-purple-300 font-bold bg-purple-200/70 dark:bg-purple-900/60 px-2 py-0.5 rounded-md">Formato 24h</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-amber-900 dark:text-amber-300 text-xs font-bold mb-1">📅 Fecha Montaje</label>
                                    <input type="date" wire:model="setup_date" class="border border-amber-300 dark:border-amber-700 rounded-xl w-full py-1.5 px-2 text-gray-700 dark:text-slate-200 text-xs bg-white dark:bg-slate-800">
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5 block">Opcional si es el mismo día</span>
                                </div>
                                <div>
                                    <label class="block text-amber-900 dark:text-amber-300 text-xs font-bold mb-1">🚗 Hora Montaje (24h)</label>
                                    <input type="time" wire:model="start_time" class="border border-amber-300 dark:border-amber-700 rounded-xl w-full py-1.5 px-2 text-gray-700 dark:text-slate-200 text-xs bg-white dark:bg-slate-800 font-bold">
                                </div>
                            </div>

                            <!-- Fases: Ceremonia, Cóctel, Banquete -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-2 border-t border-purple-200/60 dark:border-purple-900/60">
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 dark:text-slate-300 mb-0.5">💍 Ceremonia</label>
                                    <input type="time" wire:model="ceremony_time" class="border border-purple-200 dark:border-purple-800 rounded-xl w-full py-1 px-2 text-gray-700 dark:text-slate-200 text-xs font-bold bg-white dark:bg-slate-800">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 dark:text-slate-300 mb-0.5">🍸 Cóctel</label>
                                    <input type="time" wire:model="cocktail_time" class="border border-purple-200 dark:border-purple-800 rounded-xl w-full py-1 px-2 text-gray-700 dark:text-slate-200 text-xs font-bold bg-white dark:bg-slate-800">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 dark:text-slate-300 mb-0.5">🍽️ Banquete</label>
                                    <input type="time" wire:model="banquet_time" class="border border-purple-200 dark:border-purple-800 rounded-xl w-full py-1 px-2 text-gray-700 dark:text-slate-200 text-xs font-bold bg-white dark:bg-slate-800">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-2 border-t border-purple-200/60 dark:border-purple-900/60">
                                <div>
                                    <label class="block text-purple-900 dark:text-purple-300 text-xs font-bold mb-1">🎧 Inicio Baile (24h)</label>
                                    <input type="time" wire:model="dance_start_time" class="border border-purple-200 dark:border-purple-800 rounded-xl w-full py-1.5 px-2 text-gray-700 dark:text-slate-200 text-xs font-bold bg-white dark:bg-slate-800">
                                </div>
                                <div>
                                    <label class="block text-purple-900 dark:text-purple-300 text-xs font-bold mb-1">⏱️ Duración Baile</label>
                                    <div class="relative">
                                        <input type="number" step="0.5" min="1" max="24" wire:model="dance_duration_hours" class="border border-purple-200 dark:border-purple-800 rounded-xl w-full py-1.5 px-2 text-gray-700 dark:text-slate-200 text-xs font-bold bg-white dark:bg-slate-800 pr-6">
                                        <span class="absolute right-2 top-1.5 text-[10px] text-gray-400 font-bold">h</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-purple-900 dark:text-purple-300 text-xs font-bold mb-1">🛑 Límite / Hora Máx.</label>
                                    <input type="time" wire:model="max_end_time" class="border border-purple-200 dark:border-purple-800 rounded-xl w-full py-1.5 px-2 text-gray-700 dark:text-slate-200 text-xs font-bold bg-white dark:bg-slate-800" title="Hora máxima permitida / Límite finca">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="block text-gray-700 dark:text-slate-300 text-xs font-bold mb-1">Cliente Principal / Contratante (Opcional)</label>
                            <select wire:model="client_id" class="border dark:border-slate-700 rounded-xl w-full py-2 px-3 text-gray-700 dark:text-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-slate-800">
                                <option value="">-- Sin asignar --</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }} {{ $c->phone ? '('.$c->phone.')' : '' }}</option>
                                @endforeach
                            </select>
                            @error('client_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <!-- Datos de la Pareja / Cónyuge (Especial Bodas o Pareja) -->
                        <div class="mb-3 bg-pink-50/60 dark:bg-pink-950/30 p-3 rounded-2xl border border-pink-100 dark:border-pink-900/40 space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="block text-pink-900 dark:text-pink-300 text-xs font-bold">💍 Datos de la Pareja / Cónyuge (Novio/Novia)</label>
                                <span class="text-[10px] text-pink-700 dark:text-pink-300 font-bold bg-pink-100 dark:bg-pink-900/60 px-2 py-0.5 rounded-md">Contacto adicional</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 dark:text-slate-300 mb-0.5">Nombre y Apellidos</label>
                                    <input type="text" wire:model="partner_name" placeholder="Ej: Laura / Carlos" class="border border-pink-200 dark:border-pink-800 rounded-xl w-full py-1.5 px-2 text-gray-700 dark:text-slate-200 text-xs bg-white dark:bg-slate-800 font-semibold">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 dark:text-slate-300 mb-0.5">Teléfono / WhatsApp</label>
                                    <input type="text" wire:model="partner_phone" placeholder="612345678" class="border border-pink-200 dark:border-pink-800 rounded-xl w-full py-1.5 px-2 text-gray-700 dark:text-slate-200 text-xs bg-white dark:bg-slate-800">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 dark:text-slate-300 mb-0.5">Email (Opcional)</label>
                                    <input type="email" wire:model="partner_email" placeholder="pareja@email.com" class="border border-pink-200 dark:border-pink-800 rounded-xl w-full py-1.5 px-2 text-gray-700 dark:text-slate-200 text-xs bg-white dark:bg-slate-800">
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-0.5">
                                        <label class="block text-[11px] font-bold text-gray-700 dark:text-slate-300">DNI / NIF (Opcional)</label>
                                        @if(!empty($partner_dni) && isset($dniValidations['partner_dni']))
                                            @if($dniValidations['partner_dni']['valid'])
                                                <span class="text-[9px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/80 px-1.5 py-0.2 rounded border border-emerald-300 dark:border-emerald-800 flex items-center gap-0.5">
                                                    <span>✓</span> {{ $dniValidations['partner_dni']['type'] ?? 'DNI' }} Válido
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                    <div class="relative">
                                        <input 
                                            type="text" 
                                            wire:model.live.debounce.300ms="partner_dni" 
                                            data-dni-input 
                                            placeholder="12345678Z" 
                                            class="border {{ !empty($partner_dni) && isset($dniValidations['partner_dni']) ? ($dniValidations['partner_dni']['valid'] ? 'border-emerald-500 focus:border-emerald-500' : 'border-amber-400 dark:border-amber-600 focus:border-amber-500 bg-amber-50/30') : 'border-pink-200 dark:border-pink-800' }} rounded-xl w-full py-1.5 px-2 text-gray-700 dark:text-slate-200 text-xs bg-white dark:bg-slate-800 uppercase font-mono transition"
                                        >
                                        @if(!empty($partner_dni) && isset($dniValidations['partner_dni']) && $dniValidations['partner_dni']['valid'])
                                            <div class="absolute right-2 top-1/2 -translate-y-1/2 text-emerald-500 font-bold text-xs pointer-events-none">
                                                ✓
                                            </div>
                                        @endif
                                    </div>
                                    @if(!empty($partner_dni) && isset($dniValidations['partner_dni']) && !$dniValidations['partner_dni']['valid'])
                                        <div class="mt-1 p-1 bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 rounded-lg flex items-center justify-between text-[10px] gap-1">
                                            <div class="text-amber-800 dark:text-amber-300 font-semibold leading-tight">
                                                <span>⚠️ {{ $dniValidations['partner_dni']['message'] ?? 'Letra incorrecta.' }}</span>
                                            </div>
                                            @if(!empty($dniValidations['partner_dni']['suggested']))
                                                <button 
                                                    type="button" 
                                                    wire:click="applyDniSuggestion('partner_dni', '{{ $dniValidations['partner_dni']['suggested'] }}')" 
                                                    class="px-2 py-0.5 bg-amber-600 hover:bg-amber-700 active:scale-95 text-white font-bold rounded text-[9px] shadow-xs cursor-pointer transition shrink-0 whitespace-nowrap"
                                                >
                                                    Corregir a {{ $dniValidations['partner_dni']['suggested'] }}
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($event_type !== 'video')
                        <!-- Asignación de Personal: DJ y Asistentes -->
                        <div class="mb-3 bg-indigo-50/60 dark:bg-indigo-950/40 p-3 rounded-2xl border border-indigo-100 dark:border-indigo-900/50 space-y-3">
                            <div>
                                <label class="block text-indigo-900 dark:text-indigo-300 text-xs font-bold mb-1">🎧 DJ Principal (Baile / Fiesta)</label>
                                <select wire:model="dj_id" class="border dark:border-slate-700 rounded-xl w-full py-2 px-3 text-gray-700 dark:text-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-slate-800 font-bold">
                                    <option value="">-- Seleccionar DJ --</option>
                                    @foreach($djs as $djItem)
                                        <option value="{{ $djItem->id }}">{{ $djItem->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-amber-900 dark:text-amber-300 text-xs font-bold">👷‍♂️ Asistentes / Personal (Selección múltiple)</label>
                                    <span class="text-[10px] font-bold bg-amber-100 dark:bg-amber-900 text-amber-900 dark:text-amber-200 px-2 py-0.5 rounded-full">
                                        {{ count($assistant_ids) }} {{ count($assistant_ids) === 1 ? 'asistente' : 'asistentes' }}
                                    </span>
                                </div>
                                <div class="space-y-1 max-h-36 overflow-y-auto border border-amber-200 dark:border-amber-800/60 rounded-xl p-2 bg-white/80 dark:bg-slate-900/80">
                                    @foreach($assistants as $ast)
                                        <label class="flex items-center gap-2 p-1.5 rounded-lg cursor-pointer hover:bg-amber-50/60 dark:hover:bg-slate-800 transition text-xs font-semibold text-gray-800 dark:text-slate-200">
                                            <input type="checkbox" wire:model="assistant_ids" value="{{ $ast->id }}" class="rounded text-amber-600 focus:ring-amber-500 border-gray-300 h-3.5 w-3.5">
                                            <span class="flex-1 truncate">{{ $ast->name }}</span>
                                            <span class="text-[10px] px-1.5 py-0.2 rounded font-bold {{ $ast->role === 'assistant' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300' }}">{{ ucfirst($ast->role) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <div class="flex items-center justify-between text-[11px] text-indigo-900 dark:text-indigo-300 font-bold mt-2 pt-1 border-t border-indigo-100 dark:border-indigo-900/40">
                                    <span>🍽️ Menú Staff / Manutención:</span>
                                    <span class="text-purple-700 dark:text-purple-300 font-extrabold">{{ 1 + count($assistant_ids) }} {{ (1 + count($assistant_ids)) === 1 ? 'persona' : 'personas' }} (1 DJ + {{ count($assistant_ids) }} {{ count($assistant_ids) === 1 ? 'Asist.' : 'Asists.' }})</span>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="mb-3 bg-indigo-50/50 dark:bg-slate-800/50 p-3 rounded-2xl border border-indigo-100 dark:border-indigo-900/40 flex items-center justify-between text-xs text-indigo-900 dark:text-indigo-200">
                            <span class="flex items-center gap-1.5 font-bold">
                                <span>🎥</span>
                                <span>Servicio de videógrafo a tu cargo ({{ auth()->user()->name }}).</span>
                            </span>
                            <span class="text-[10px] bg-indigo-100 dark:bg-indigo-900/60 text-indigo-800 dark:text-indigo-300 font-extrabold px-2 py-0.5 rounded-full">Sin DJ</span>
                        </div>
                        @endif

                        <div>
                            <label class="block text-gray-700 dark:text-slate-300 text-xs font-bold mb-1">Notas Privadas / Observaciones</label>
                            <textarea wire:model="notes" rows="2" placeholder="Detalles de montaje, peticiones especiales..." class="border dark:border-slate-700 rounded-xl w-full py-2 px-3 text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-800 text-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        </div>
                    </form>
                </div>

                <!-- Footer Acciones Sticky -->
                <div class="bg-gray-50 dark:bg-slate-950 px-5 py-3.5 border-t border-gray-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row sm:justify-end gap-2 shrink-0">
                    <button wire:click="closeCreateModal" type="button" class="w-full sm:w-auto inline-flex justify-center rounded-xl border border-gray-300 dark:border-slate-700 shadow-xs px-4 py-2.5 bg-white dark:bg-slate-800 text-xs font-bold text-gray-700 dark:text-slate-200 hover:bg-gray-100 dark:hover:bg-slate-700 focus:outline-none transition cursor-pointer">
                        Cancelar
                    </button>
                    <button wire:click="saveEvent" type="button" class="w-full sm:w-auto inline-flex justify-center rounded-xl border border-transparent shadow-md px-5 py-2.5 bg-indigo-600 text-xs font-extrabold text-white hover:bg-indigo-700 focus:outline-none transition cursor-pointer">
                        {{ $editing_event_id ? '💾 Guardar Cambios' : '🚀 Crear Evento' }}
                    </button>
                </div>

            </div>
        </div>
    </div>
    @endif

    <!-- MODAL DE SEGUIMIENTO MULTIETAPA MADRIGAL WHATSAPP & EMAIL -->
    @if($confirmingFollowupEventId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-xs animate-in fade-in duration-150">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-xl w-full p-6 shadow-2xl relative max-h-[90vh] overflow-y-auto">
                
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl font-bold">
                            {{ $confirmingFollowupHasPhone ? '💬' : '📧' }}
                        </div>
                        <div>
                            <h3 class="text-base font-black text-slate-900 dark:text-white">
                                Seguimiento Madrigal · WhatsApp & Email
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                <strong class="text-slate-800 dark:text-slate-200">{{ $confirmingFollowupClientName }}</strong> &bull; {{ $confirmingFollowupEventName }}
                            </p>
                        </div>
                    </div>
                    @if(($confirmingFollowupStatus ?? 'no_response') === 'on_hold')
                        <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $confirmingFollowupDays >= 30 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' : 'bg-cyan-100 text-cyan-800 dark:bg-cyan-950/60 dark:text-cyan-300' }}">
                            ⏸️ {{ $confirmingFollowupDays <= 0 ? 'En pausa hoy' : ($confirmingFollowupDays === 1 ? '1 día en pausa' : "{$confirmingFollowupDays} días en pausa") }}
                        </span>
                    @else
                        <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300">
                            {{ $confirmingFollowupDays <= 0 ? 'Puesto hoy' : ($confirmingFollowupDays === 1 ? '1 día sin respuesta' : "{$confirmingFollowupDays} días sin respuesta") }}
                        </span>
                    @endif
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

                <!-- Vista previa editable del mensaje -->
                <div class="mt-4 space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">Mensaje a enviar (editable):</span>
                        <span class="text-[10px] text-slate-400">Válido para WhatsApp y Email</span>
                    </div>
                    <textarea wire:model.live="confirmingFollowupText" rows="5" class="w-full bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200/60 dark:border-emerald-800/40 rounded-2xl p-3 text-xs text-slate-800 dark:text-slate-200 font-sans leading-relaxed focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                </div>

                <!-- Botón de apertura de WhatsApp (si tiene teléfono) -->
                @if(!empty($confirmingFollowupUrl))
                    <div class="mt-3">
                        <a href="{{ $confirmingFollowupUrl }}" target="_blank" class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-600/30 transition flex items-center justify-center gap-2">
                            <span>💬 Abrir WhatsApp y Enviar Mensaje</span>
                            <span class="text-xs">↗</span>
                        </a>
                    </div>
                @else
                    <div class="mt-3 p-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/70 text-[11px] text-amber-800 dark:text-amber-300 font-semibold flex items-center gap-2">
                        <span>📵</span>
                        <span>Este cliente no dejó teléfono móvil. Envía el seguimiento por Email aquí abajo:</span>
                    </div>
                @endif

                <!-- Envío directo por Email (con registro en Conversación Registrada) -->
                <div class="mt-3 p-3 bg-indigo-50/70 dark:bg-indigo-950/30 rounded-2xl border border-indigo-200 dark:border-indigo-800/80 space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[11px] font-extrabold text-indigo-900 dark:text-indigo-200 flex items-center gap-1">
                            <span>📧</span> Enviar Seguimiento por Email
                        </span>
                        <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold">Se guardará en el chat del evento</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-0.5">Email destino:</label>
                            <input type="email" wire:model.live="confirmingFollowupEmail" placeholder="cliente@email.com" class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-indigo-200 dark:border-indigo-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-semibold" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-0.5">Asunto:</label>
                            <input type="text" wire:model.live="confirmingFollowupSubject" placeholder="Asunto del seguimiento..." class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-indigo-200 dark:border-indigo-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-semibold" />
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-2 pt-1">
                        <button type="button" wire:click="sendDirectFollowupEmail" wire:loading.attr="disabled" class="flex-1 py-2.5 px-4 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-indigo-600/20 transition flex items-center justify-center gap-1.5 cursor-pointer">
                            <span wire:loading.remove wire:target="sendDirectFollowupEmail">📧 Enviar por Email Ahora</span>
                            <span wire:loading wire:target="sendDirectFollowupEmail">⏳ Enviando Email...</span>
                        </button>
                        @if(!empty($confirmingFollowupEmail))
                            <a href="{{ \App\Services\WhatsAppTemplateService::buildMailtoUrl($confirmingFollowupEmail, $confirmingFollowupSubject, $confirmingFollowupText) }}" target="_blank" class="py-2.5 px-3 bg-white dark:bg-slate-900 hover:bg-indigo-100 dark:hover:bg-slate-800 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-700 font-bold text-xs rounded-xl transition flex items-center justify-center gap-1 shrink-0">
                                <span>✉️ Abrir gestor correo</span>
                                <span>↗</span>
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Confirmaciones de acción posterior -->
                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 space-y-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block text-center">
                        Una vez enviado o revisado:
                    </span>
                    <button type="button" wire:click="confirmFollowupSent" class="w-full py-2.5 px-3 bg-purple-600 hover:bg-purple-500 text-white font-extrabold text-xs rounded-xl transition flex items-center justify-center gap-2 cursor-pointer shadow-xs">
                        <span>✅ He enviado el mensaje manualmente</span>
                        <span class="text-[10px] opacity-80">(Poner contador a 0 hoy)</span>
                    </button>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <button type="button" wire:click="markAsResponded('on_hold')" class="py-2 px-3 bg-cyan-50 hover:bg-cyan-100 dark:bg-cyan-950/60 text-cyan-800 dark:text-cyan-300 font-bold text-xs rounded-xl border border-cyan-300 dark:border-cyan-800 transition flex items-center justify-center gap-1 cursor-pointer" title="El cliente dijo que ya avisará: quita la alerta de sin respuesta y cuenta los días en pausa">
                            <span>⏸️ En Espera (Ya avisarán)</span>
                        </button>
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
