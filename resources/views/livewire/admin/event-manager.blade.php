<div>
    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded shadow-sm">
            {{ session('message') }}
        </div>
    @endif

    <!-- Header Actions -->
    <div class="mb-4 flex justify-between items-center">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Próximos Eventos</h2>
            <p class="text-xs text-gray-500">Planificación, personal asignado (DJ y Asistente) y seguimiento.</p>
        </div>
        <button wire:click="openCreateModal" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm text-sm transition">
            + Nuevo Evento
        </button>
    </div>

    <!-- Eventos Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Evento y Personal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lugar</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($events as $event)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-1.5">
                                <span>{{ $event->event_type_icon }}</span>
                                <span class="text-sm font-bold text-gray-900">{{ $event->name }}</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 mt-1">
                                <span class="text-[10px] bg-slate-100 text-slate-700 font-semibold px-2 py-0.5 rounded-md">
                                    {{ $event->event_type_label }}
                                </span>
                                @if($event->dj)
                                    <span class="text-xs text-indigo-700 font-semibold inline-flex items-center gap-1">
                                        🎧 DJ: {{ $event->dj->name }}
                                    </span>
                                @endif
                                @if($event->assistant)
                                    <span class="text-xs text-amber-700 font-semibold inline-flex items-center gap-1">
                                        👷‍♂️ Asistente: {{ $event->assistant->name }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 font-semibold">{{ $event->event_date->format('d/m/Y') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            📍 {{ $event->location }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div class="font-medium text-gray-800">{{ $event->client ? $event->client->name : 'Sin asignar' }}</div>
                            @if($event->client && $event->client->phone)
                                @php
                                    $cPhone = preg_replace('/[^0-9]/', '', $event->client->phone);
                                    if (strlen($cPhone) === 9 && in_array(substr($cPhone, 0, 1), ['6', '7'])) {
                                        $cPhone = '34' . $cPhone;
                                    }
                                @endphp
                                <a href="https://wa.me/{{ $cPhone }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-1.5 py-0.5 rounded mt-0.5 transition" title="Abrir chat de WhatsApp">
                                    💬 {{ $event->client->phone }}
                                </a>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @if($event->status == 'draft') bg-amber-100 text-amber-800 
                                @elseif($event->status == 'confirmed') bg-green-100 text-green-800 
                                @elseif($event->status == 'completed') bg-blue-100 text-blue-800 
                                @else bg-red-100 text-red-800 @endif">
                                {{ ucfirst($event->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('admin.events.show', $event->id) }}" class="text-indigo-600 hover:text-indigo-900 font-bold bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition">Gestionar</a>
                            @if(auth()->user()->role === 'admin')
                                <button type="button" wire:click="deleteEvent({{ $event->id }})" wire:confirm="¿Estás seguro de que deseas eliminar permanentemente el evento '{{ $event->name }}' y todos sus presupuestos, contratos y música?" class="text-rose-600 hover:text-rose-900 hover:bg-rose-50 px-2 py-1.5 rounded-lg transition ml-1 cursor-pointer" title="Eliminar Evento">
                                    🗑️
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No hay eventos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Modal de Creación -->
    @if($showCreateModal)
    <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="closeCreateModal"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-bold text-gray-900 mb-4" id="modal-title">
                        Crear Nuevo Evento
                    </h3>
                    
                    <form wire:submit.prevent="saveEvent">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                            <div class="sm:col-span-2">
                                <label class="block text-gray-700 text-xs font-bold mb-1">Nombre del Evento *</label>
                                <input type="text" wire:model="name" placeholder="Ej: Boda Laura y Carlos / Fiesta ACME" class="border rounded-lg w-full py-2 px-3 text-gray-700 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-gray-700 text-xs font-bold mb-1">Tipo de Evento *</label>
                                <select wire:model="event_type" class="border rounded-lg w-full py-2 px-2 text-gray-700 text-xs font-bold focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                    <option value="boda">💍 Boda</option>
                                    <option value="empresa">🏢 Empresa</option>
                                    <option value="cumpleanos">🎂 Cumpleaños</option>
                                    <option value="comunion">🕊️ Comunión</option>
                                    <option value="otro">🎉 Fiesta / Otro</option>
                                </select>
                                @error('event_type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                            <div>
                                <label class="block text-gray-700 text-xs font-bold mb-1">Fecha *</label>
                                <input type="date" wire:model="event_date" class="border rounded-lg w-full py-2 px-3 text-gray-700 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('event_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-gray-700 text-xs font-bold mb-1">Lugar / Finca / Salón *</label>
                                <input type="text" wire:model="location" placeholder="Ej: Marqués de Riscal" class="border rounded-lg w-full py-2 px-3 text-gray-700 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('location') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-xs font-bold mb-1">Cliente / Novios (Opcional)</label>
                            <select wire:model="client_id" class="border rounded-lg w-full py-2 px-3 text-gray-700 text-sm focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                <option value="">-- Sin asignar --</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }} {{ $c->phone ? '('.$c->phone.')' : '' }}</option>
                                @endforeach
                            </select>
                            @error('client_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <!-- Asignación de Personal: DJ y Asistente -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4 bg-indigo-50/50 p-3 rounded-xl border border-indigo-100">
                            <div>
                                <label class="block text-indigo-900 text-xs font-bold mb-1">🎧 DJ (Baile / Fiesta)</label>
                                <select wire:model="dj_id" class="border rounded-lg w-full py-2 px-3 text-gray-700 text-sm focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                    <option value="">-- Seleccionar DJ --</option>
                                    @foreach($djs as $djItem)
                                        <option value="{{ $djItem->id }}">{{ $djItem->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-amber-900 text-xs font-bold mb-1">👷‍♂️ Asistente (Ceremonia / Banquete)</label>
                                <select wire:model="assistant_id" class="border rounded-lg w-full py-2 px-3 text-gray-700 text-sm focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                    <option value="">-- Sin Asistente (Opcional) --</option>
                                    @foreach($assistants as $ast)
                                        <option value="{{ $ast->id }}">{{ $ast->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-xs font-bold mb-1">Notas Privadas / Observaciones</label>
                            <textarea wire:model="notes" rows="2" placeholder="Detalles de montaje, peticiones especiales..." class="border rounded-lg w-full py-2 px-3 text-gray-700 text-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        </div>
                    </form>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-xl gap-2">
                    <button wire:click="saveEvent" type="button" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-sm font-bold text-white hover:bg-indigo-700 focus:outline-none transition">
                        Guardar Evento
                    </button>
                    <button wire:click="closeCreateModal" type="button" class="w-full sm:w-auto mt-2 sm:mt-0 inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none transition">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
