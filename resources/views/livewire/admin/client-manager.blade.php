<div class="space-y-6">
    <!-- MENSAJES FLASH -->
    @if (session()->has('message'))
        <div class="bg-emerald-50 border border-emerald-300 text-emerald-800 text-xs font-bold p-4 rounded-2xl shadow-xs flex items-center justify-between">
            <span class="flex items-center gap-2"><span>✓</span> {{ session('message') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-rose-50 border border-rose-300 text-rose-800 text-xs font-bold p-4 rounded-2xl shadow-xs flex items-center justify-between">
            <span class="flex items-center gap-2"><span>⚠️</span> {{ session('error') }}</span>
        </div>
    @endif

    <!-- CABECERA DE LA PÁGINA -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xs">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                <span>👤</span> Gestión de Clientes y Parejas
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Consulta y gestiona las fichas de clientes, datos de contacto, direcciones, DNI y contraseñas de acceso.
            </p>
        </div>
        <button 
            wire:click="openCreateModal" 
            class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-2xl shadow-md text-xs transition flex items-center gap-1.5 cursor-pointer whitespace-nowrap"
        >
            <span>+</span> Añadir Nuevo Cliente
        </button>
    </div>

    <!-- FILTROS POR PESTAÑAS Y HERRAMIENTAS DE LIMPIEZA -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 bg-white dark:bg-slate-900 p-4 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xs">
        <div class="flex flex-wrap items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-800/60 rounded-2xl">
            <button 
                type="button" 
                wire:click="$set('filterEvents', 'all')" 
                class="px-3 py-1.5 text-xs font-bold rounded-xl transition cursor-pointer {{ $filterEvents === 'all' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-xs' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}"
            >
                👥 Todos ({{ $totalCount }})
            </button>
            <button 
                type="button" 
                wire:click="$set('filterEvents', 'with_events')" 
                class="px-3 py-1.5 text-xs font-bold rounded-xl transition cursor-pointer flex items-center gap-1 {{ $filterEvents === 'with_events' ? 'bg-white dark:bg-slate-900 text-emerald-700 dark:text-emerald-400 shadow-xs' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}"
            >
                <span>🎉</span> Con Eventos ({{ $withEventsCount }})
            </button>
            <button 
                type="button" 
                wire:click="$set('filterEvents', 'without_events')" 
                class="px-3 py-1.5 text-xs font-bold rounded-xl transition cursor-pointer flex items-center gap-1 {{ $filterEvents === 'without_events' ? 'bg-white dark:bg-slate-900 text-amber-700 dark:text-amber-400 shadow-xs' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}"
            >
                <span>📩</span> Sin Eventos ({{ $withoutEventsCount }})
            </button>
        </div>

        <!-- Botones de Acción Rápida -->
        <div class="flex flex-wrap items-center gap-2">
            <button 
                type="button" 
                wire:click="mergeDuplicates" 
                wire:confirm="¿Deseas buscar y fusionar automáticamente los contactos duplicados que compartan el mismo teléfono, DNI o nombre exacto? Los eventos se conservarán y reasignarán al contacto principal."
                class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-2 rounded-2xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/40 dark:hover:bg-indigo-900/50 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-900/60 shadow-2xs transition cursor-pointer"
                title="Fusiona automáticamente contactos duplicados sin perder eventos"
            >
                <span>🔗</span> Fusionar Duplicados
            </button>

            <button 
                type="button" 
                wire:click="cleanSpamContacts" 
                wire:confirm="¿Deseas eliminar automáticamente los contactos detectados como marketing o notificaciones (Bodas.net, Microsoft, Madrigal Marketing, RobertNar, etc.) que no tienen eventos asignados?"
                class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-2 rounded-2xl bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:hover:bg-rose-900/50 dark:text-rose-300 border border-rose-200 dark:border-rose-900/60 shadow-2xs transition cursor-pointer"
                title="Elimina contactos de correo spam y notificaciones automáticas"
            >
                <span>🧹</span> Limpiar Spam / Notificaciones
            </button>
        </div>
    </div>

    <!-- BARRA DE BÚSQUEDA Y TOTALES -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="text-xs font-bold text-slate-600 dark:text-slate-400">
            Mostrando: <strong class="text-slate-900 dark:text-white text-sm">{{ $clients->count() }}</strong>
            @if($filterEvents === 'with_events')
                <span class="text-emerald-600 dark:text-emerald-400 font-semibold">(clientes con eventos activos)</span>
            @elseif($filterEvents === 'without_events')
                <span class="text-amber-600 dark:text-amber-400 font-semibold">(contactos sin eventos)</span>
            @endif
        </div>

        <div class="relative w-full sm:w-80">
            <input 
                type="text" 
                wire:model.live.debounce.250ms="search" 
                placeholder="🔍 Buscar cliente por nombre, tlf, DNI, ciudad..." 
                class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl text-xs text-slate-800 dark:text-white p-2.5 pl-3 shadow-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
            >
            @if($search)
                <button type="button" wire:click="$set('search', '')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs">
                    ✕
                </button>
            @endif
        </div>
    </div>

    <!-- TABLA DE CLIENTES -->
    <div class="bg-white dark:bg-slate-900 shadow-sm overflow-hidden rounded-3xl border border-slate-200 dark:border-slate-800">
        <div class="w-full">
            <table class="w-full divide-y divide-slate-200 dark:divide-slate-800 text-left">
                <thead class="bg-slate-50 dark:bg-slate-950 text-slate-500 dark:text-slate-400 text-xs uppercase font-bold tracking-wider">
                    <tr>
                        <th class="px-4 py-3.5">Cliente / Pareja</th>
                        <th class="px-4 py-3.5">Contacto</th>
                        <th class="px-4 py-3.5">DNI & Localidad</th>
                        <th class="px-4 py-3.5">Eventos</th>
                        <th class="px-4 py-3.5 text-right w-28 shrink-0">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                    @forelse($clients as $client)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <!-- Nombre -->
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-9 h-9 rounded-2xl bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-300 flex items-center justify-center font-black text-xs shrink-0 shadow-2xs">
                                        {{ strtoupper(substr($client->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm truncate max-w-[170px] sm:max-w-[220px] md:max-w-xs" title="{{ $client->name }}">
                                            {{ $client->name }}
                                        </div>
                                        @if($client->username)
                                            <div class="text-[11px] text-slate-500 font-mono truncate max-w-[150px]">@<span>{{ $client->username }}</span></div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Contacto -->
                            <td class="px-4 py-3.5">
                                <div class="min-w-0">
                                    @if(str_ends_with($client->email, '@eventosmusicales.local'))
                                        <span class="text-[11px] text-slate-400 italic">Acceso por enlace</span>
                                    @else
                                        <div class="text-slate-800 dark:text-slate-200 font-medium text-xs truncate max-w-[150px] sm:max-w-[190px]" title="{{ $client->email }}">
                                            {{ $client->email }}
                                        </div>
                                    @endif
                                </div>
                                @if($client->phone)
                                    @php
                                        $cleanPhone = preg_replace('/[^0-9]/', '', $client->phone);
                                        if (strlen($cleanPhone) === 9 && in_array(substr($cleanPhone, 0, 1), ['6', '7'])) {
                                            $cleanPhone = '34' . $cleanPhone;
                                        }
                                    @endphp
                                    <div class="flex items-center gap-1.5 mt-1">
                                        <span class="text-slate-600 dark:text-slate-400 text-[11px] font-mono whitespace-nowrap">📞 {{ $client->phone }}</span>
                                        <a href="https://wa.me/{{ $cleanPhone }}" target="_blank" class="text-[10px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/70 hover:bg-emerald-100 border border-emerald-300 dark:border-emerald-800 px-1.5 py-0.5 rounded transition whitespace-nowrap">
                                            💬 WA
                                        </a>
                                    </div>
                                @endif
                            </td>

                            <!-- DNI y Localidad -->
                            <td class="px-4 py-3.5">
                                <div>
                                    @if($client->dni)
                                        <span class="font-mono font-bold text-slate-800 dark:text-slate-200 text-xs">{{ $client->dni }}</span>
                                    @else
                                        <span class="text-slate-400 text-[11px] font-medium">Sin DNI</span>
                                    @endif
                                </div>
                                @if($client->city || $client->province)
                                    <div class="text-[11px] text-slate-500 mt-0.5 truncate max-w-[140px]" title="{{ $client->city }} {{ $client->province ? '('.$client->province.')' : '' }}">
                                        📍 {{ $client->city }} {{ $client->province ? '('.$client->province.')' : '' }}
                                    </div>
                                @endif
                            </td>

                            <!-- Eventos Asociados -->
                            <td class="px-4 py-3.5">
                                @php
                                    $eventsCount = $client->client_events_count ?? 0;
                                    $latestEvent = $client->clientEvents->first();
                                @endphp

                                @if($eventsCount > 0 && $latestEvent)
                                    <a href="{{ route('admin.events.show', $latestEvent->id) }}" class="inline-flex items-center gap-1 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 border border-indigo-200 dark:border-indigo-800 text-indigo-800 dark:text-indigo-300 font-bold px-2 py-1 rounded-xl transition text-[11px] max-w-[160px] truncate" title="{{ $latestEvent->name }}">
                                        <span>🎉</span>
                                        <span class="truncate">{{ $latestEvent->name }}</span>
                                        @if($eventsCount > 1)
                                            <span class="bg-indigo-200 text-indigo-900 rounded-full px-1.5 py-0.2 text-[9px] shrink-0">+{{ $eventsCount - 1 }}</span>
                                        @endif
                                    </a>
                                @else
                                    <span class="text-slate-400 text-[11px]">Sin eventos</span>
                                @endif
                            </td>

                            <!-- Botones de Acción -->
                            <td class="px-4 py-3.5 text-right w-28 shrink-0 whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1">
                                    <!-- Botón Rápido Cambiar Contraseña -->
                                    <button 
                                        type="button" 
                                        wire:click="openPasswordModal({{ $client->id }})" 
                                        class="p-1.5 text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 rounded-xl transition cursor-pointer" 
                                        title="Asignar o Cambiar Contraseña"
                                    >
                                        🔑
                                    </button>

                                    <!-- Botón Editar Ficha Completa -->
                                    <button 
                                        type="button" 
                                        wire:click="openEditModal({{ $client->id }})" 
                                        class="p-1.5 text-amber-700 hover:text-amber-900 bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/60 dark:hover:bg-amber-900/60 rounded-xl transition cursor-pointer" 
                                        title="Editar Ficha del Cliente"
                                    >
                                        ✏️
                                    </button>

                                    <!-- Botón Eliminar -->
                                    <button 
                                        type="button" 
                                        wire:click="deleteClient({{ $client->id }})" 
                                        wire:confirm="¿Estás seguro de que deseas eliminar la ficha de {{ $client->name }}?" 
                                        class="p-1.5 text-rose-600 hover:text-rose-900 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900/60 rounded-xl transition cursor-pointer" 
                                        title="Eliminar Cliente"
                                    >
                                        🗑️
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-400 text-xs">
                                <span class="text-3xl block mb-2">👤</span>
                                No se encontraron clientes registrados con ese criterio de búsqueda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ==================== MODAL DE CREACIÓN DE CLIENTE ==================== -->
    @if($showCreateModal)
    <div class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-client-create" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" aria-hidden="true" wire:click="closeCreateModal"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-slate-200 dark:border-slate-800">
                <div class="p-6 sm:p-7 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-1.5" id="modal-client-create">
                            <span>👤</span> Añadir Nuevo Cliente / Pareja
                        </h3>
                        <button type="button" wire:click="closeCreateModal" class="text-slate-400 hover:text-slate-600 text-base cursor-pointer">✕</button>
                    </div>
                    
                    <form wire:submit.prevent="saveClient" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="sm:col-span-2">
                                <label class="block text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider mb-1">Nombre Completo o Pareja *</label>
                                <input type="text" wire:model.live.debounce.400ms="name" required placeholder="Ej: Laura García y Carlos Ruiz" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs font-semibold text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950">
                                @if(!empty($duplicateWarnings['name']))
                                    <p class="text-[11px] font-bold text-amber-600 dark:text-amber-400 mt-1 leading-tight">{{ $duplicateWarnings['name'] }}</p>
                                @endif
                                @error('name') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider mb-1">Teléfono (WhatsApp)</label>
                                <input type="text" wire:model.live.debounce.400ms="phone" placeholder="612345678" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950">
                                @if(!empty($duplicateWarnings['phone']))
                                    <p class="text-[11px] font-bold text-amber-600 dark:text-amber-400 mt-1 leading-tight">{{ $duplicateWarnings['phone'] }}</p>
                                @endif
                                @error('phone') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">DNI / NIF</label>
                                    @if(!empty($dni) && isset($dniValidations['dni']))
                                        @if($dniValidations['dni']['valid'])
                                            <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/80 px-2 py-0.5 rounded border border-emerald-300 dark:border-emerald-800 flex items-center gap-1">
                                                <span>✓</span> {{ $dniValidations['dni']['type'] ?? 'DNI' }} Válido
                                            </span>
                                        @endif
                                    @endif
                                </div>
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        wire:model.live.debounce.300ms="dni" 
                                        data-dni-input 
                                        placeholder="12345678Z" 
                                        class="w-full border {{ !empty($dni) && isset($dniValidations['dni']) ? ($dniValidations['dni']['valid'] ? 'border-emerald-500 focus:border-emerald-500 focus:ring-emerald-500' : 'border-amber-400 dark:border-amber-600 focus:border-amber-500 focus:ring-amber-500 bg-amber-50/30') : 'border-slate-300 dark:border-slate-700' }} rounded-xl p-2.5 text-xs font-mono font-bold text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950 uppercase transition"
                                    >
                                    @if(!empty($dni) && isset($dniValidations['dni']) && $dniValidations['dni']['valid'])
                                        <div class="absolute right-3 top-1/2 -translate-y-1/2 text-emerald-500 font-bold text-sm pointer-events-none">
                                            ✓
                                        </div>
                                    @endif
                                </div>
                                @if(!empty($duplicateWarnings['dni']))
                                    <p class="text-[11px] font-bold text-rose-600 dark:text-rose-400 mt-1 leading-tight">{{ $duplicateWarnings['dni'] }}</p>
                                @endif
                                @error('dni') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                                @if(!empty($dni) && isset($dniValidations['dni']) && !$dniValidations['dni']['valid'])
                                    <div class="mt-1.5 p-2 bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 rounded-xl flex items-center justify-between text-xs gap-2">
                                        <div class="flex items-center gap-1.5 text-amber-800 dark:text-amber-300 font-semibold leading-tight text-[11px]">
                                            <span>⚠️</span>
                                            <span>{{ $dniValidations['dni']['message'] ?? 'Letra de DNI incorrecta.' }}</span>
                                        </div>
                                        @if(!empty($dniValidations['dni']['suggested']))
                                            <button 
                                                type="button" 
                                                wire:click="applyDniSuggestion('dni', '{{ $dniValidations['dni']['suggested'] }}')" 
                                                class="px-2.5 py-1 bg-amber-600 hover:bg-amber-700 active:scale-95 text-white font-bold rounded-lg text-[10px] shadow-xs cursor-pointer transition shrink-0 whitespace-nowrap"
                                                title="Pulsar para sustituir la letra errónea por la letra oficial calculada"
                                            >
                                                Corregir a {{ $dniValidations['dni']['suggested'] }}
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div>
                                <label class="block text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider mb-1">Correo Electrónico (Opcional)</label>
                                <input type="email" wire:model="email" placeholder="cliente@email.com" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950">
                                @error('email') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider mb-1">Nombre de Usuario (Login)</label>
                                <input type="text" wire:model="username" placeholder="lauraycarlos" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950">
                                @error('username') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Dirección y Residencia con autocompletado por CP -->
                        <div class="p-3.5 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-2.5">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider block">Dirección y Residencia</span>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-1">Código Postal (CP)</label>
                                    <input type="text" wire:model.live.debounce.300ms="postal_code" placeholder="26370" maxlength="5" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2 text-xs font-bold text-slate-800 dark:text-white bg-white dark:bg-slate-900">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-1">Población / Ciudad</label>
                                    <input type="text" wire:model="city" placeholder="Navarrete" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2 text-xs text-slate-800 dark:text-white bg-white dark:bg-slate-900">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-1">Provincia</label>
                                    <input type="text" wire:model="province" placeholder="La Rioja" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2 text-xs text-slate-800 dark:text-white bg-white dark:bg-slate-900">
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-1">Dirección (Calle, Nº, Piso...)</label>
                                    <input type="text" wire:model="address" placeholder="Calle Mayor 12" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2 text-xs text-slate-800 dark:text-white bg-white dark:bg-slate-900">
                                </div>
                            </div>
                        </div>

                        <!-- Contraseña opcional -->
                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider mb-1">
                                Contraseña de Acceso (Opcional)
                            </label>
                            <input type="password" wire:model="password" placeholder="Dejar en blanco para autogenerar" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950">
                        </div>

                        <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100 dark:border-slate-800">
                            <button wire:click="closeCreateModal" type="button" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 font-bold rounded-xl text-xs transition">
                                Cancelar
                            </button>
                            <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md transition cursor-pointer">
                                Guardar Cliente
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- ==================== MODAL DE EDICIÓN DE CLIENTE ==================== -->
    @if($showEditModal)
    <div class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-client-edit" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" aria-hidden="true" wire:click="closeEditModal"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-slate-200 dark:border-slate-800">
                <div class="p-6 sm:p-7 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-1.5" id="modal-client-edit">
                            <span>✏️</span> Editar Ficha de Cliente
                        </h3>
                        <button type="button" wire:click="closeEditModal" class="text-slate-400 hover:text-slate-600 text-base cursor-pointer">✕</button>
                    </div>
                    
                    <form wire:submit.prevent="updateClient" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="sm:col-span-2">
                                <label class="block text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider mb-1">Nombre Completo o Pareja *</label>
                                <input type="text" wire:model.live.debounce.400ms="edit_name" required class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs font-semibold text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950">
                                @if(!empty($duplicateWarnings['edit_name']))
                                    <p class="text-[11px] font-bold text-amber-600 dark:text-amber-400 mt-1 leading-tight">{{ $duplicateWarnings['edit_name'] }}</p>
                                @endif
                                @error('edit_name') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider mb-1">Teléfono (WhatsApp)</label>
                                <input type="text" wire:model.live.debounce.400ms="edit_phone" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950">
                                @if(!empty($duplicateWarnings['edit_phone']))
                                    <p class="text-[11px] font-bold text-amber-600 dark:text-amber-400 mt-1 leading-tight">{{ $duplicateWarnings['edit_phone'] }}</p>
                                @endif
                                @error('edit_phone') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider">DNI / NIF</label>
                                    @if(!empty($edit_dni) && isset($dniValidations['edit_dni']))
                                        @if($dniValidations['edit_dni']['valid'])
                                            <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/80 px-2 py-0.5 rounded border border-emerald-300 dark:border-emerald-800 flex items-center gap-1">
                                                <span>✓</span> {{ $dniValidations['edit_dni']['type'] ?? 'DNI' }} Válido
                                            </span>
                                        @endif
                                    @endif
                                </div>
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        wire:model.live.debounce.300ms="edit_dni" 
                                        data-dni-input 
                                        placeholder="12345678Z" 
                                        class="w-full border {{ !empty($edit_dni) && isset($dniValidations['edit_dni']) ? ($dniValidations['edit_dni']['valid'] ? 'border-emerald-500 focus:border-emerald-500 focus:ring-emerald-500' : 'border-amber-400 dark:border-amber-600 focus:border-amber-500 focus:ring-amber-500 bg-amber-50/30') : 'border-slate-300 dark:border-slate-700' }} rounded-xl p-2.5 text-xs font-mono font-bold text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950 uppercase transition"
                                    >
                                    @if(!empty($edit_dni) && isset($dniValidations['edit_dni']) && $dniValidations['edit_dni']['valid'])
                                        <div class="absolute right-3 top-1/2 -translate-y-1/2 text-emerald-500 font-bold text-sm pointer-events-none">
                                            ✓
                                        </div>
                                    @endif
                                </div>
                                @if(!empty($duplicateWarnings['edit_dni']))
                                    <p class="text-[11px] font-bold text-rose-600 dark:text-rose-400 mt-1 leading-tight">{{ $duplicateWarnings['edit_dni'] }}</p>
                                @endif
                                @error('edit_dni') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                                @if(!empty($edit_dni) && isset($dniValidations['edit_dni']) && !$dniValidations['edit_dni']['valid'])
                                    <div class="mt-1.5 p-2 bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 rounded-xl flex items-center justify-between text-xs gap-2">
                                        <div class="flex items-center gap-1.5 text-amber-800 dark:text-amber-300 font-semibold leading-tight text-[11px]">
                                            <span>⚠️</span>
                                            <span>{{ $dniValidations['edit_dni']['message'] ?? 'Letra de DNI incorrecta.' }}</span>
                                        </div>
                                        @if(!empty($dniValidations['edit_dni']['suggested']))
                                            <button 
                                                type="button" 
                                                wire:click="applyDniSuggestion('edit_dni', '{{ $dniValidations['edit_dni']['suggested'] }}')" 
                                                class="px-2.5 py-1 bg-amber-600 hover:bg-amber-700 active:scale-95 text-white font-bold rounded-lg text-[10px] shadow-xs cursor-pointer transition shrink-0 whitespace-nowrap"
                                                title="Pulsar para sustituir la letra errónea por la letra oficial calculada"
                                            >
                                                Corregir a {{ $dniValidations['edit_dni']['suggested'] }}
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div>
                                <label class="block text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider mb-1">Correo Electrónico</label>
                                <input type="email" wire:model="edit_email" placeholder="cliente@email.com" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950">
                                @error('edit_email') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider mb-1">Nombre de Usuario</label>
                                <input type="text" wire:model="edit_username" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950">
                                @error('edit_username') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Dirección y Población -->
                        <div class="p-3.5 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-2.5">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider block">Dirección y Localidad</span>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-1">Código Postal (CP)</label>
                                    <input type="text" wire:model.live.debounce.300ms="edit_postal_code" placeholder="26370" maxlength="5" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2 text-xs font-bold text-slate-800 dark:text-white bg-white dark:bg-slate-900">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-1">Población / Ciudad</label>
                                    <input type="text" wire:model="edit_city" placeholder="Navarrete" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2 text-xs text-slate-800 dark:text-white bg-white dark:bg-slate-900">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-1">Provincia</label>
                                    <input type="text" wire:model="edit_province" placeholder="La Rioja" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2 text-xs text-slate-800 dark:text-white bg-white dark:bg-slate-900">
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-1">Dirección (Calle, Nº, Piso...)</label>
                                    <input type="text" wire:model="edit_address" placeholder="Calle Mayor 12" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2 text-xs text-slate-800 dark:text-white bg-white dark:bg-slate-900">
                                </div>
                            </div>
                        </div>

                        <!-- Nueva Contraseña (Opcional) -->
                        <div class="p-3 bg-emerald-50/50 dark:bg-emerald-950/30 rounded-2xl border border-emerald-200 dark:border-emerald-900/60 space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-emerald-950 dark:text-emerald-200 uppercase tracking-wider flex items-center gap-1">
                                    <span>🔑</span> Cambiar Contraseña del Cliente (Opcional)
                                </label>
                                <span class="text-[10px] text-emerald-600 font-normal">Dejar en blanco para mantener</span>
                            </div>
                            <input 
                                type="text" 
                                wire:model="edit_password" 
                                placeholder="Escribe nueva contraseña..." 
                                class="w-full border-emerald-200 dark:border-emerald-800 rounded-xl p-2.5 text-xs text-slate-800 dark:text-white bg-white dark:bg-slate-900 font-mono"
                            >
                            @error('edit_password') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100 dark:border-slate-800">
                            <button wire:click="closeEditModal" type="button" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 font-bold rounded-xl text-xs transition">
                                Cancelar
                            </button>
                            <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md transition cursor-pointer">
                                Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- ==================== MODAL RÁPIDO CAMBIO DE CONTRASEÑA ==================== -->
    @if($showPasswordModal)
    <div class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-pass-client-title" role="dialog" aria-modal="true" x-data="{ copied: false }">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" aria-hidden="true" wire:click="closePasswordModal"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200 dark:border-slate-800">
                <div class="p-6 sm:p-7 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-1.5" id="modal-pass-client-title">
                                <span>🔑</span> Asignar Contraseña a Cliente
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Cliente: <strong class="text-slate-800 dark:text-white">{{ $password_client_name }}</strong></p>
                        </div>
                        <button type="button" wire:click="closePasswordModal" class="text-slate-400 hover:text-slate-600 text-base cursor-pointer">✕</button>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Nueva Contraseña *</label>
                                <button 
                                    type="button" 
                                    wire:click="generateRandomPassword" 
                                    class="text-[11px] font-bold text-emerald-600 hover:text-emerald-800 flex items-center gap-1 cursor-pointer"
                                >
                                    <span>🎲</span> Generar Aleatoria
                                </button>
                            </div>
                            <input 
                                type="text" 
                                wire:model="new_password" 
                                placeholder="Escribe o genera contraseña..." 
                                class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-3 text-sm font-mono font-bold text-slate-900 dark:text-white bg-slate-50 dark:bg-slate-950 focus:bg-white focus:ring-2 focus:ring-emerald-500"
                            >
                            @error('new_password') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                        </div>

                        <!-- Resumen y botón para copiar credenciales -->
                        @if($generatedPasswordInfo)
                            @php
                                $loginUrl = url('/login');
                                $credText = "🔐 *Datos de acceso a la plataforma:*\n\n👤 Usuario / Login: " . ($generatedPasswordInfo['username'] ?: $generatedPasswordInfo['email']) . "\n🔑 Contraseña: " . $generatedPasswordInfo['password'] . "\n🔗 Enlace: " . $loginUrl;
                            @endphp
                            <div class="p-4 bg-emerald-50 dark:bg-emerald-950/60 rounded-2xl border border-emerald-300 dark:border-emerald-800 text-xs space-y-2.5">
                                <div class="font-bold text-emerald-900 dark:text-emerald-200 flex items-center gap-1.5">
                                    <span>✓</span> ¡Contraseña asignada correctamente!
                                </div>
                                <div class="bg-white dark:bg-slate-900 p-3 rounded-xl border border-emerald-200 dark:border-emerald-900 font-mono text-[11px] text-slate-800 dark:text-slate-200 space-y-1">
                                    <div><strong>Login:</strong> {{ $generatedPasswordInfo['username'] ?: $generatedPasswordInfo['email'] }}</div>
                                    <div><strong>Contraseña:</strong> <span class="text-emerald-700 dark:text-emerald-300 font-bold">{{ $generatedPasswordInfo['password'] }}</span></div>
                                </div>
                                
                                <div class="flex items-center gap-2">
                                    <button 
                                        type="button" 
                                        @click="navigator.clipboard.writeText(`{{ $credText }}`); copied = true; setTimeout(() => copied = false, 2500)" 
                                        class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold p-2 rounded-xl text-xs transition flex items-center justify-center gap-1 cursor-pointer"
                                    >
                                        <span x-text="copied ? '✓ ¡Copiado!' : '📋 Copiar Datos de Acceso'"></span>
                                    </button>

                                    @if($generatedPasswordInfo['phone'])
                                        @php
                                            $cleanPh = preg_replace('/[^0-9]/', '', $generatedPasswordInfo['phone']);
                                            if (strlen($cleanPh) === 9 && in_array(substr($cleanPh, 0, 1), ['6', '7'])) { $cleanPh = '34' . $cleanPh; }
                                            $waCredUrl = "https://wa.me/{$cleanPh}?text=" . rawurlencode($credText);
                                        @endphp
                                        <a 
                                            href="{{ $waCredUrl }}" 
                                            target="_blank" 
                                            class="bg-emerald-100 hover:bg-emerald-200 text-emerald-800 font-bold p-2 px-3 rounded-xl text-xs border border-emerald-300 transition flex items-center gap-1"
                                            title="Enviar por WhatsApp"
                                        >
                                            💬 WhatsApp
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100 dark:border-slate-800">
                            <button wire:click="closePasswordModal" type="button" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 font-bold rounded-xl text-xs transition">
                                Cerrar
                            </button>
                            <button wire:click="saveNewPassword" type="button" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md transition cursor-pointer">
                                💾 Guardar Contraseña
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
