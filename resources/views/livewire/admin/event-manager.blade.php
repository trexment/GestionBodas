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
        <button wire:click="openCreateModal" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold py-2.5 px-5 rounded-xl shadow-md shadow-indigo-600/20 text-xs sm:text-sm transition flex items-center justify-center gap-2 cursor-pointer">
            <span>➕</span>
            <span>Nuevo Evento</span>
        </button>
    </div>

    <!-- ============================================== -->
    <!-- VISTA MÓVIL: TARJETAS RESPONSIVAS (< 640px)    -->
    <!-- ============================================== -->
    <div class="block sm:hidden space-y-3.5 mb-6">
        @forelse($events as $event)
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-gray-200 dark:border-slate-800 shadow-sm transition space-y-3">
                
                <!-- Encabezado de Tarjeta: Icono + Nombre + Estado -->
                <div class="flex items-start justify-between gap-2 border-b border-gray-100 dark:border-slate-800 pb-2.5">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-xl p-1.5 bg-indigo-50 dark:bg-slate-800 rounded-xl shrink-0">{{ $event->event_type_icon }}</span>
                        <div class="min-w-0">
                            <h3 class="font-extrabold text-sm text-gray-900 dark:text-white truncate leading-tight">{{ $event->name }}</h3>
                            <span class="text-[10px] text-indigo-700 dark:text-indigo-400 font-bold uppercase tracking-wider">
                                {{ $event->event_type_label }}
                            </span>
                        </div>
                    </div>
                    <span class="shrink-0 px-2.5 py-0.5 text-[11px] font-bold rounded-full border {{ $event->status_badge_class }}">
                        {{ $event->status_icon }} {{ $event->status_label }}
                    </span>
                </div>

                <!-- Detalles Rápidos: Fecha, Montaje y Horario Baile 24h -->
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-gray-50 dark:bg-slate-800/60 p-2 rounded-xl border border-gray-100 dark:border-slate-800">
                        <span class="text-[10px] text-gray-400 dark:text-gray-400 uppercase font-bold block">📅 Fecha Evento</span>
                        <span class="font-bold text-gray-800 dark:text-slate-200">{{ $event->event_date->format('d/m/Y') }}</span>
                    </div>
                    <div class="bg-gray-50 dark:bg-slate-800/60 p-2 rounded-xl border border-gray-100 dark:border-slate-800">
                        <span class="text-[10px] text-gray-400 dark:text-gray-400 uppercase font-bold block">📍 Lugar</span>
                        <span class="font-bold text-gray-800 dark:text-slate-200 truncate block" title="{{ $event->location }}">{{ $event->location }}</span>
                    </div>
                </div>

                <!-- Montaje & Baile (Si están configurados) -->
                @if($event->setup_date || $event->start_time || $event->dance_start_time)
                    <div class="bg-purple-50/70 dark:bg-purple-950/30 p-2.5 rounded-xl border border-purple-100 dark:border-purple-900/40 text-[11px] space-y-1">
                        @if($event->setup_date || $event->start_time)
                            <div class="text-amber-900 dark:text-amber-300 font-semibold flex items-center gap-1.5">
                                <span>🚗</span>
                                <span><strong>Montaje:</strong> {{ $event->setup_schedule_label }}</span>
                            </div>
                        @endif
                        @if($event->dance_start_time)
                            <div class="text-purple-900 dark:text-purple-300 font-bold flex items-center gap-1.5">
                                <span>⏰</span>
                                <span><strong>Baile (24h):</strong> {{ substr($event->dance_start_time, 0, 5) }}h @if($event->dance_duration_hours)({{ (float)$event->dance_duration_hours }}h)@endif</span>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Personal (DJ y Asistente) -->
                <div class="flex flex-wrap items-center gap-1.5 text-xs pt-1">
                    @if($event->dj)
                        <span class="bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-bold px-2.5 py-1 rounded-lg border border-indigo-200 dark:border-indigo-900/60 inline-flex items-center gap-1">
                            🎧 DJ: {{ $event->dj->name }}
                        </span>
                    @else
                        <span class="text-gray-400 dark:text-gray-400 text-[11px]">Sin DJ asignado</span>
                    @endif

                    @if($event->assistant)
                        <span class="bg-amber-50 dark:bg-amber-950/50 text-amber-800 dark:text-amber-300 font-bold px-2.5 py-1 rounded-lg border border-amber-200 dark:border-amber-900/60 inline-flex items-center gap-1">
                            👷‍♂️ Asist: {{ $event->assistant->name }}
                        </span>
                    @endif
                </div>

                <!-- Cliente & WhatsApp -->
                <div class="flex items-center justify-between text-xs pt-1 border-t border-gray-100 dark:border-slate-800">
                    <div class="truncate text-gray-700 dark:text-slate-300">
                        <span class="text-gray-400 font-semibold">Cliente:</span> <strong>{{ $event->client ? $event->client->name : 'Sin asignar' }}</strong>
                    </div>
                    @if($event->client && $event->client->phone)
                        @php
                            $cPhone = preg_replace('/[^0-9]/', '', $event->client->phone);
                            if (strlen($cPhone) === 9 && in_array(substr($cPhone, 0, 1), ['6', '7'])) {
                                $cPhone = '34' . $cPhone;
                            }
                        @endphp
                        <a href="https://wa.me/{{ $cPhone }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 border border-emerald-300 dark:border-emerald-800 px-2 py-0.5 rounded-lg transition shrink-0" title="Abrir chat de WhatsApp">
                            💬 WhatsApp
                        </a>
                    @endif
                </div>

                <!-- Botones de Acción Móvil -->
                <div class="flex items-center gap-2 pt-2">
                    <a href="{{ route('admin.events.show', $event->id) }}" class="flex-1 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-extrabold text-xs py-2.5 px-4 rounded-xl shadow-xs text-center transition flex items-center justify-center gap-1.5">
                        <span>⚙️</span>
                        <span>Gestionar Evento</span>
                    </a>
                    @if(auth()->user()->role === 'admin')
                        <button type="button" wire:click="deleteEvent({{ $event->id }})" wire:confirm="¿Estás seguro de que deseas eliminar permanentemente el evento '{{ $event->name }}'?" class="p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/50 hover:bg-rose-100 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-900/60 transition cursor-pointer" title="Eliminar Evento">
                            🗑️
                        </button>
                    @endif
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

    <!-- ============================================== -->
    <!-- VISTA ESCRITORIO / TABLET: TABLA SIN SCROLL    -->
    <!-- ============================================== -->
    <div class="hidden sm:block bg-white dark:bg-slate-900 shadow-sm overflow-hidden rounded-2xl border border-gray-200 dark:border-slate-800">
        <table class="w-full divide-y divide-gray-200 dark:divide-slate-800 table-auto text-left">
            <thead class="bg-gray-50 dark:bg-slate-950">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Evento y Personal</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Lugar</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cliente</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800">
                @forelse($events as $event)
                    <tr class="hover:bg-gray-50/70 dark:hover:bg-slate-800/50 transition">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="text-base shrink-0">{{ $event->event_type_icon }}</span>
                                <span class="text-sm font-extrabold text-gray-900 dark:text-white truncate">{{ $event->name }}</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                <span class="text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold px-2 py-0.5 rounded-md">
                                    {{ $event->event_type_label }}
                                </span>
                                @if($event->dj)
                                    <span class="text-[11px] text-indigo-700 dark:text-indigo-400 font-semibold inline-flex items-center gap-1">
                                        🎧 {{ $event->dj->name }}
                                    </span>
                                @endif
                                @php
                                    $assignedAsts = $event->all_assistants;
                                @endphp
                                @if($assignedAsts->isNotEmpty())
                                    <span class="text-[11px] text-amber-700 dark:text-amber-400 font-semibold inline-flex items-center gap-1">
                                        👷‍♂️ {{ $assignedAsts->pluck('name')->implode(', ') }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-xs text-gray-900 dark:text-slate-200 font-bold">{{ $event->event_date->format('d/m/Y') }}</div>
                            @if($event->setup_date || $event->start_time)
                                <div class="text-[10px] text-amber-800 dark:text-amber-300 font-bold flex items-center gap-1 mt-0.5">
                                    <span>🚗</span>
                                    <span>{{ $event->setup_schedule_label }}</span>
                                </div>
                            @endif
                            @if($event->dance_start_time)
                                <div class="text-[10px] text-purple-700 dark:text-purple-300 font-bold flex items-center gap-1 mt-0.5">
                                    <span>⏰</span>
                                    <span>{{ substr($event->dance_start_time, 0, 5) }}h @if($event->dance_duration_hours)({{ (float)$event->dance_duration_hours }}h)@endif</span>
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-600 dark:text-slate-400 truncate max-w-[160px]" title="{{ $event->location }}">
                            📍 {{ $event->location }}
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-600 dark:text-slate-400">
                            <div class="font-bold text-gray-800 dark:text-slate-200 truncate max-w-[140px]">{{ $event->client ? $event->client->name : 'Sin asignar' }}</div>
                            @if($event->client && $event->client->phone)
                                @php
                                    $cPhone = preg_replace('/[^0-9]/', '', $event->client->phone);
                                    if (strlen($cPhone) === 9 && in_array(substr($cPhone, 0, 1), ['6', '7'])) {
                                        $cPhone = '34' . $cPhone;
                                    }
                                @endphp
                                <a href="https://wa.me/{{ $cPhone }}" target="_blank" class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 border border-emerald-200 dark:border-emerald-800 px-1.5 py-0.5 rounded-lg mt-0.5 transition" title="Abrir chat de WhatsApp">
                                    💬 {{ $event->client->phone }}
                                </a>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="px-2.5 py-0.5 inline-flex text-[11px] leading-5 font-bold rounded-full border {{ $event->status_badge_class }}">
                                {{ $event->status_icon }} {{ $event->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-right text-xs font-medium">
                            <a href="{{ route('admin.events.show', $event->id) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 font-extrabold bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 border border-indigo-200 dark:border-indigo-800 px-3 py-1.5 rounded-xl transition">Gestionar</a>
                            <button type="button" wire:click="openEditModal({{ $event->id }})" class="text-amber-600 hover:text-amber-900 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/60 border border-amber-200 dark:border-amber-800 px-2.5 py-1.5 rounded-xl transition ml-1 cursor-pointer" title="Editar Evento">
                                ✏️
                            </button>
                            @if(auth()->user()->role === 'admin')
                                <button type="button" wire:click="deleteEvent({{ $event->id }})" wire:confirm="¿Estás seguro de que deseas eliminar permanentemente el evento '{{ $event->name }}' y todos sus presupuestos, contratos y música?" class="text-rose-600 hover:text-rose-900 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/60 px-2 py-1.5 rounded-xl transition ml-1 cursor-pointer" title="Eliminar Evento">
                                    🗑️
                                </button>
                            @endif
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
                                <input type="text" wire:model="name" placeholder="Ej: Enlace Vidalia y Omar / Boda Laura y Carlos" class="border dark:border-slate-700 rounded-xl w-full py-2 px-3 text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-800 text-sm focus:ring-indigo-500 focus:border-indigo-500 font-bold">
                                @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-gray-700 dark:text-slate-300 text-xs font-bold mb-1">Marca / Perfil *</label>
                                <select wire:model="brand" class="border dark:border-slate-700 rounded-xl w-full py-2 px-2 text-gray-700 dark:text-slate-200 text-xs font-bold focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-slate-800">
                                    <option value="nunez_and_son">👑 Núñez & Son</option>
                                    <option value="javnx">🎧 JAVNX DJ</option>
                                    <option value="mago_leugim">🎩 Mago Leugim</option>
                                </select>
                                @error('brand') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-gray-700 dark:text-slate-300 text-xs font-bold mb-1">Tipo *</label>
                                <select wire:model="event_type" class="border dark:border-slate-700 rounded-xl w-full py-2 px-2 text-gray-700 dark:text-slate-200 text-xs font-bold focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-slate-800">
                                    <option value="boda">💍 Boda</option>
                                    <option value="empresa">🏢 Empresa</option>
                                    <option value="cumpleanos">🎂 Cumpleaños</option>
                                    <option value="comunion">🕊️ Comunión</option>
                                    <option value="otro">🎉 Fiesta / Otro</option>
                                </select>
                                @error('event_type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        
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
                            <label class="block text-gray-700 dark:text-slate-300 text-xs font-bold mb-1">Cliente / Novios (Opcional)</label>
                            <select wire:model="client_id" class="border dark:border-slate-700 rounded-xl w-full py-2 px-3 text-gray-700 dark:text-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-slate-800">
                                <option value="">-- Sin asignar --</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }} {{ $c->phone ? '('.$c->phone.')' : '' }}</option>
                                @endforeach
                            </select>
                            @error('client_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

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
</div>
