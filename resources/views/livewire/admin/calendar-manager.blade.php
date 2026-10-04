<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8" x-data="{ openIcalModal: false }">

    @if (session()->has('message'))
        <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm">
            {{ session('message') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        
        <!-- Barra de Control Superior -->
        <div class="p-4 sm:p-6 bg-white border-b border-gray-200 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            
            <!-- Navegación de Mes -->
            <div class="flex items-center gap-2">
                <button wire:click="previousMonth" class="p-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500" title="Mes Anterior">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 min-w-[200px] text-center">
                    {{ $monthName }}
                </h2>

                <button wire:click="nextMonth" class="p-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500" title="Mes Siguiente">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>

                <button wire:click="goToToday" class="ml-2 px-3 py-1.5 text-xs font-semibold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition">
                    Hoy
                </button>
            </div>

            <!-- Filtros y Botones -->
            <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                <select wire:model.live="filter_status" class="border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 py-2">
                    <option value="">Todos los Estados</option>
                    <option value="confirmed">🟢 Confirmados</option>
                    <option value="draft">🟡 Borradores / Pendientes</option>
                    <option value="no_response">🟣 Sin Respuesta</option>
                    <option value="completed">🔵 Completados</option>
                    <option value="rejected">⚪ Rechazados</option>
                    <option value="cancelled">🔴 Cancelados</option>
                </select>

                <select wire:model.live="filter_dj" class="border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 py-2">
                    <option value="">Todo el Personal (DJ / Asistente)</option>
                    @foreach($djs as $djItem)
                        <option value="{{ $djItem->id }}">🎧 DJ: {{ $djItem->name }}</option>
                    @endforeach
                    @foreach($assistants->where('role', 'assistant') as $astItem)
                        <option value="{{ $astItem->id }}">👷‍♂️ Asistente: {{ $astItem->name }}</option>
                    @endforeach
                </select>

                <!-- Botón Sincronizar iCal -->
                <button type="button" @click="openIcalModal = true" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-semibold py-2 px-3.5 rounded-lg shadow-xs text-sm inline-flex items-center gap-1.5 transition" title="Sincronizar con Google Calendar o iPhone">
                    📅 <span class="hidden sm:inline">Sincronizar</span> iCal
                </button>

                <button wire:click="openCreateModal" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow text-sm inline-flex items-center gap-2 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Nuevo Evento
                </button>
            </div>
        </div>

        <!-- Leyenda -->
        <div class="px-6 py-2 bg-gray-50 border-b border-gray-100 flex flex-wrap items-center gap-4 text-xs font-medium text-gray-600">
            <span class="text-gray-400">Leyenda:</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Confirmado</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Borrador / Pendiente</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span> Sin Respuesta</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Completado</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span> Rechazado</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Cancelado</span>
        </div>

        <!-- Cuadrícula del Calendario -->
        <div class="overflow-x-auto">
            <div class="min-w-[750px]">
                
                <!-- Días de la semana -->
                <div class="grid grid-cols-7 bg-gray-100 border-b border-gray-200 text-center text-xs font-bold text-gray-700 uppercase tracking-wider py-2.5">
                    <div>Lunes</div>
                    <div>Martes</div>
                    <div>Miércoles</div>
                    <div>Jueves</div>
                    <div>Viernes</div>
                    <div class="text-indigo-600">Sábado</div>
                    <div class="text-indigo-600">Domingo</div>
                </div>

                <!-- Días -->
                <div class="grid grid-cols-7 divide-x divide-y divide-gray-200 bg-gray-200">
                    @foreach($weeks as $week)
                        @foreach($week as $dayData)
                            <div class="min-h-[120px] p-2 flex flex-col justify-between transition group {{ $dayData['isCurrentMonth'] ? 'bg-white' : 'bg-gray-50 text-gray-400' }} {{ $dayData['isToday'] ? 'ring-2 ring-indigo-500 ring-inset bg-indigo-50/20' : '' }}">
                                
                                <!-- Cabecera del Día -->
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-sm font-bold {{ $dayData['isToday'] ? 'bg-indigo-600 text-white w-6 h-6 rounded-full flex items-center justify-center shadow-sm' : ($dayData['isCurrentMonth'] ? ($dayData['isWeekend'] ? 'text-indigo-600' : 'text-gray-800') : 'text-gray-400') }}">
                                        {{ $dayData['day'] }}
                                    </span>
                                    
                                    <button wire:click="openCreateModal('{{ $dayData['date'] }}')" class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-indigo-600 p-0.5 rounded hover:bg-gray-100 transition" title="Crear evento el {{ $dayData['date'] }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    </button>
                                </div>

                                <!-- Lista de Eventos del Día -->
                                <div class="space-y-1.5 flex-1 overflow-y-auto max-h-[140px]">
                                    @foreach($dayData['events'] as $eventItem)
                                        @php
                                            $borderClass = match($eventItem->status) {
                                                'confirmed' => 'border-emerald-500 bg-emerald-50 text-emerald-900 hover:bg-emerald-100',
                                                'completed' => 'border-blue-500 bg-blue-50 text-blue-900 hover:bg-blue-100',
                                                'no_response' => 'border-purple-500 bg-purple-50 text-purple-900 hover:bg-purple-100',
                                                'rejected' => 'border-slate-400 bg-slate-100 text-slate-700 hover:bg-slate-200 opacity-60 line-through',
                                                'cancelled' => 'border-rose-500 bg-rose-50 text-rose-900 hover:bg-rose-100 opacity-60 line-through',
                                                default => 'border-amber-500 bg-amber-50 text-amber-900 hover:bg-amber-100',
                                            };
                                        @endphp
                                        <a href="{{ route('admin.events.show', $eventItem->id) }}" class="block p-1.5 rounded border-l-4 text-xs shadow-xs transition {{ $borderClass }}" title="{{ $eventItem->name }} ({{ $eventItem->location }})">
                                            <div class="font-bold truncate">{{ $eventItem->name }}</div>
                                            <div class="text-[10px] text-gray-600 truncate">📍 {{ $eventItem->location }}</div>
                                            @if($eventItem->dj)
                                                <div class="text-[10px] text-indigo-700 font-semibold truncate mt-0.5">🎧 {{ $eventItem->dj->name }}</div>
                                            @endif
                                            @if($eventItem->assistant)
                                                <div class="text-[10px] text-amber-700 font-semibold truncate">👷‍♂️ {{ $eventItem->assistant->name }}</div>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>

                            </div>
                        @endforeach
                    @endforeach
                </div>

            </div>
        </div>

    </div>

    <!-- Modal iCal Sync -->
    <div x-show="openIcalModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="openIcalModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6" x-data="{ copiedIcal: false }">
                <div class="w-14 h-14 bg-indigo-100 rounded-2xl mx-auto flex items-center justify-center text-indigo-600 text-3xl mb-4 shadow-sm">
                    📅
                </div>
                
                <h3 class="text-xl font-bold text-gray-900 text-center">Sincronización de Calendario</h3>
                <p class="text-xs text-gray-500 text-center mt-1">
                    Suscríbete a tus eventos en tiempo real desde <strong>Google Calendar, Apple Calendar (iPhone/Mac)</strong> o <strong>Outlook</strong>.
                </p>

                @php
                    $feedUrl = url('/calendar/feed/' . (auth()->user() ? auth()->user()->getCalendarToken() : 'token') . '.ics');
                    $webcalUrl = str_replace(['http://', 'https://'], 'webcal://', $feedUrl);
                @endphp

                <div class="mt-6 space-y-4">
                    <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Tu enlace personal iCal / ICS:</label>
                        <div class="flex items-center gap-2">
                            <input type="text" readonly value="{{ $feedUrl }}" class="flex-1 border-gray-300 rounded-xl text-xs bg-white px-3 py-2 select-all focus:ring-indigo-500" id="icalUrlInput">
                            <button type="button" @click="
                                const inp = document.getElementById('icalUrlInput');
                                inp.select();
                                inp.setSelectionRange(0, 99999);
                                if (navigator.clipboard && window.isSecureContext) {
                                    navigator.clipboard.writeText(inp.value);
                                } else {
                                    document.execCommand('copy');
                                }
                                copiedIcal = true;
                                setTimeout(() => copiedIcal = false, 2500);
                            " class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-3 py-2 rounded-xl text-xs shadow-xs transition">
                                <span x-show="!copiedIcal">📋 Copiar</span>
                                <span x-show="copiedIcal" style="display: none;">✅ ¡Copiado!</span>
                            </button>
                        </div>
                    </div>

                    <!-- Botón 1-clic para Apple Calendar / Mac / iOS -->
                    <a href="{{ $webcalUrl }}" class="w-full flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-bold py-2.5 px-4 rounded-xl text-xs shadow-md transition">
                        🍏 Suscribir en Apple Calendar (iPhone / Mac)
                    </a>

                    <div class="text-xs text-gray-500 space-y-1.5 p-3 bg-amber-50 rounded-xl border border-amber-200 text-amber-900">
                        <p class="font-bold">👉 ¿Cómo añadir a Google Calendar?</p>
                        <p>1. Abre Google Calendar en tu ordenador.</p>
                        <p>2. En el lateral izquierdo, junto a <em>"Otros calendarios"</em>, pulsa en <strong>+ > Desde URL</strong>.</p>
                        <p>3. Pega el enlace copiado arriba y pulsa <strong>Añadir calendario</strong>.</p>
                    </div>
                </div>

                <div class="mt-6 text-center">
                    <button type="button" @click="openIcalModal = false" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2 px-6 rounded-xl text-xs transition">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Evento Rápido -->
    @if($showCreateModal)
    <div class="fixed z-20 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="$set('showCreateModal', false)"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                
                <form wire:submit.prevent="createEvent">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">
                                📅 Nuevo Evento
                            </h3>
                            <button type="button" wire:click="$set('showCreateModal', false)" class="text-gray-400 hover:text-gray-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Nombre del Evento *</label>
                                <input type="text" wire:model="new_name" placeholder="Ej: Boda Carlos y Laura" required class="block w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('new_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Fecha del Evento *</label>
                                    <input type="date" wire:model="new_event_date" required class="block w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    @error('new_event_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Estado *</label>
                                    <select wire:model="new_status" required class="block w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                        <option value="draft">🟡 Borrador / Pendiente</option>
                                        <option value="no_response">🟣 Sin Respuesta</option>
                                        <option value="confirmed">🟢 Confirmado</option>
                                        <option value="completed">🔵 Completado</option>
                                        <option value="rejected">⚪ Rechazado</option>
                                        <option value="cancelled">🔴 Cancelado</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Lugar / Finca / Restaurante *</label>
                                <input type="text" wire:model="new_location" placeholder="Ej: Finca Los Olivos, Madrid" required class="block w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('new_location') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Cliente Asignado</label>
                                <select wire:model="new_client_id" class="block w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                    <option value="">-- Sin cliente asignado --</option>
                                    @foreach($clients as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->email }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-3 bg-indigo-50/50 p-3 rounded-xl border border-indigo-100">
                                <div>
                                    <label class="block text-xs font-bold text-indigo-900 mb-1">🎧 DJ (Baile)</label>
                                    <select wire:model="new_dj_id" class="block w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                        <option value="">-- Sin DJ asignado --</option>
                                        @foreach($djs as $dj)
                                            <option value="{{ $dj->id }}">{{ $dj->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-amber-900 mb-1">👷‍♂️ Asistente</label>
                                    <select wire:model="new_assistant_id" class="block w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                        <option value="">-- Sin Asistente --</option>
                                        @foreach($assistants as $ast)
                                            <option value="{{ $ast->id }}">{{ $ast->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-xl gap-2">
                        <button type="submit" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-sm font-bold text-white hover:bg-indigo-700 focus:outline-none transition">
                            Guardar Evento
                        </button>
                        <button type="button" wire:click="$set('showCreateModal', false)" class="w-full sm:w-auto mt-2 sm:mt-0 inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none transition">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

</div>
