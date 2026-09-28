<div>
    @if (session()->has('message'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded shadow-sm">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- Header Actions -->
    <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Equipo, Empleados y Clientes</h2>
            <p class="text-xs text-gray-500">Gestiona los DJs, Asistentes de evento y Clientes del sistema.</p>
        </div>
        <button wire:click="openCreateModal" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm text-sm transition">
            + Nuevo Usuario / Empleado
        </button>
    </div>

    <!-- Píldoras de Filtro por Rol -->
    <div class="flex flex-wrap items-center gap-2 mb-4">
        <button wire:click="$set('filterRole', 'all')" class="px-3.5 py-1.5 rounded-full text-xs font-bold transition {{ $filterRole === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
            Todos
        </button>
        <button wire:click="$set('filterRole', 'assistant')" class="px-3.5 py-1.5 rounded-full text-xs font-bold transition {{ $filterRole === 'assistant' ? 'bg-amber-600 text-white shadow-sm' : 'bg-amber-50 text-amber-800 hover:bg-amber-100' }}">
            👷‍♂️ Asistentes / Empleados
        </button>
        <button wire:click="$set('filterRole', 'dj')" class="px-3.5 py-1.5 rounded-full text-xs font-bold transition {{ $filterRole === 'dj' ? 'bg-blue-600 text-white shadow-sm' : 'bg-blue-50 text-blue-800 hover:bg-blue-100' }}">
            🎧 DJs
        </button>
        <button wire:click="$set('filterRole', 'admin')" class="px-3.5 py-1.5 rounded-full text-xs font-bold transition {{ $filterRole === 'admin' ? 'bg-purple-600 text-white shadow-sm' : 'bg-purple-50 text-purple-800 hover:bg-purple-100' }}">
            ⚡ Administradores
        </button>
        <button wire:click="$set('filterRole', 'client')" class="px-3.5 py-1.5 rounded-full text-xs font-bold transition {{ $filterRole === 'client' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100' }}">
            👤 Clientes
        </button>
    </div>

    <!-- Users Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contacto</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol / Puesto</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alta</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($users as $user)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <span class="h-10 w-10 rounded-full flex items-center justify-center font-bold text-sm {{ $user->role === 'assistant' ? 'bg-amber-100 text-amber-800' : ($user->role === 'dj' ? 'bg-blue-100 text-blue-800' : ($user->role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-emerald-100 text-emerald-800')) }}">
                                        {{ substr($user->name, 0, 1) }}
                                    </span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-bold text-gray-900">{{ $user->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $user->username ? '@'.$user->username : '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                @if(str_ends_with($user->email, '@eventosmusicales.local'))
                                    <span class="text-xs text-gray-400 italic">Sin email (Acceso por enlace)</span>
                                @else
                                    {{ $user->email }}
                                @endif
                            </div>
                            @if($user->phone)
                                @php
                                    $cleanPhone = preg_replace('/[^0-9]/', '', $user->phone);
                                    if (strlen($cleanPhone) === 9 && in_array(substr($cleanPhone, 0, 1), ['6', '7'])) {
                                        $cleanPhone = '34' . $cleanPhone;
                                    }
                                @endphp
                                <div class="text-xs text-gray-600 flex items-center gap-2 mt-0.5">
                                    <span>📞 {{ $user->phone }}</span>
                                    <a href="https://wa.me/{{ $cleanPhone }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-1.5 py-0.5 rounded transition" title="Abrir WhatsApp">
                                        💬 WhatsApp
                                    </a>
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($user->role == 'admin')
                                <span class="px-2.5 py-1 inline-flex text-xs font-bold rounded-full bg-purple-100 text-purple-800">
                                    ⚡ Administrador
                                </span>
                            @elseif($user->role == 'dj')
                                <span class="px-2.5 py-1 inline-flex text-xs font-bold rounded-full bg-blue-100 text-blue-800">
                                    🎧 DJ (Baile)
                                </span>
                            @elseif($user->role == 'assistant')
                                <span class="px-2.5 py-1 inline-flex text-xs font-bold rounded-full bg-amber-100 text-amber-800">
                                    👷‍♂️ Asistente / Empleado
                                </span>
                            @else
                                <span class="px-2.5 py-1 inline-flex text-xs font-bold rounded-full bg-emerald-100 text-emerald-800">
                                    👤 Cliente
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $user->created_at->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            @if($user->id !== auth()->id())
                                <button wire:click="deleteUser({{ $user->id }})" wire:confirm="¿Estás seguro de eliminar a este usuario?" class="text-red-600 hover:text-red-900 ml-3">Eliminar</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">No hay usuarios en esta categoría.</td>
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
                        {{ $role === 'client' ? 'Añadir Nuevo Cliente / Novios' : 'Crear Nuevo Usuario / Empleado' }}
                    </h3>
                    
                    <form wire:submit.prevent="saveUser">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-xs font-bold mb-1">Puesto / Rol del Usuario *</label>
                            <select wire:model.live="role" class="border rounded-lg w-full py-2 px-3 text-gray-700 text-sm focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                <option value="client">👤 Cliente / Novios (Sin contraseña obligatoria)</option>
                                <option value="assistant">👷‍♂️ Asistente / Empleado (Ceremonia, Cóctel, Banquete)</option>
                                <option value="dj">🎧 DJ (Baile / Fiesta)</option>
                                <option value="admin">⚡ Administrador</option>
                            </select>
                            @error('role') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-xs font-bold mb-1">Nombre Completo *</label>
                            <input type="text" wire:model="name" required placeholder="{{ $role === 'client' ? 'Ej: Laura y Carlos' : 'Ej: Carlos Gómez' }}" class="border rounded-lg w-full py-2 px-3 text-gray-700 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div>
                                <label class="block text-gray-700 text-xs font-bold mb-1">Teléfono / WhatsApp {{ $role === 'client' ? '*' : '(Opcional)' }}</label>
                                <input type="text" wire:model="phone" placeholder="Ej: 612345678" class="border rounded-lg w-full py-2 px-3 text-gray-700 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('phone') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-gray-700 text-xs font-bold mb-1">Usuario (Opcional)</label>
                                <input type="text" wire:model="username" placeholder="carlos" class="border rounded-lg w-full py-2 px-3 text-gray-700 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @error('username') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-xs font-bold mb-1">
                                Email {{ $role === 'client' ? '(Opcional - para avisos por email)' : '*' }}
                            </label>
                            <input type="email" wire:model="email" {{ $role === 'client' ? '' : 'required' }} placeholder="{{ $role === 'client' ? 'cliente@gmail.com (Opcional)' : 'carlos@empresa.es' }}" class="border rounded-lg w-full py-2 px-3 text-gray-700 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @if($role === 'client')
                                <p class="text-[11px] text-gray-400 mt-0.5">Si se deja vacío, el cliente accederá mediante los enlaces de WhatsApp / token web.</p>
                            @endif
                            @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-xs font-bold mb-1">
                                {{ $role === 'client' ? 'Contraseña (Opcional)' : 'Contraseña provisional *' }}
                            </label>
                            <input type="password" wire:model="password" {{ $role === 'client' ? '' : 'required' }} placeholder="{{ $role === 'client' ? 'Dejar en blanco para autogenerar' : '••••••••' }}" class="border rounded-lg w-full py-2 px-3 text-gray-700 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @if($role === 'client')
                                <p class="text-[11px] text-gray-400 mt-0.5">Los clientes no necesitan contraseña para firmar contratos o rellenar canciones.</p>
                            @endif
                            @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </form>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-xl gap-2">
                    <button wire:click="saveUser" type="button" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-sm font-bold text-white hover:bg-indigo-700 focus:outline-none transition">
                        {{ $role === 'client' ? 'Guardar Cliente' : 'Guardar Usuario' }}
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
