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
                <span>👥</span> Equipo, Empleados y Personal
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Gestiona a los DJs, Asistentes técnicos y Administradores de la plataforma. Para gestionar novios y parejas, ve a <a href="{{ route('admin.clients') }}" class="text-indigo-600 dark:text-indigo-400 font-bold underline">Clientes</a>.
            </p>
        </div>
        <button 
            wire:click="openCreateModal" 
            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-4 rounded-2xl shadow-md text-xs transition flex items-center gap-1.5 cursor-pointer whitespace-nowrap"
        >
            <span>+</span> Nuevo Empleado / DJ
        </button>
    </div>

    <!-- BARRA DE FILTROS Y BÚSQUEDA -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Píldoras de Filtro por Rol -->
        <div class="flex flex-wrap items-center gap-1.5">
            <button wire:click="$set('filterRole', 'team')" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer {{ $filterRole === 'team' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-800 hover:bg-slate-50' }}">
                Todo el Equipo ({{ \App\Models\User::whereIn('role', ['assistant', 'dj', 'admin'])->count() }})
            </button>
            <button wire:click="$set('filterRole', 'assistant')" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer {{ $filterRole === 'assistant' ? 'bg-amber-600 text-white shadow-sm' : 'bg-white dark:bg-slate-900 text-amber-800 dark:text-amber-400 border border-slate-200 dark:border-slate-800 hover:bg-amber-50' }}">
                👷‍♂️ Asistentes / Personal ({{ \App\Models\User::where('role', 'assistant')->count() }})
            </button>
            <button wire:click="$set('filterRole', 'dj')" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer {{ $filterRole === 'dj' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white dark:bg-slate-900 text-blue-800 dark:text-blue-400 border border-slate-200 dark:border-slate-800 hover:bg-blue-50' }}">
                🎧 DJs ({{ \App\Models\User::where('role', 'dj')->count() }})
            </button>
            <button wire:click="$set('filterRole', 'admin')" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer {{ $filterRole === 'admin' ? 'bg-purple-600 text-white shadow-sm' : 'bg-white dark:bg-slate-900 text-purple-800 dark:text-purple-400 border border-slate-200 dark:border-slate-800 hover:bg-purple-50' }}">
                ⚡ Administradores ({{ \App\Models\User::where('role', 'admin')->count() }})
            </button>
            <a href="{{ route('admin.clients') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 hover:bg-emerald-100 flex items-center gap-1">
                <span>👤 Ver Clientes</span> <span>&rarr;</span>
            </a>
        </div>

        <!-- Buscador en tiempo real -->
        <div class="relative w-full md:w-72">
            <input 
                type="text" 
                wire:model.live.debounce.250ms="search" 
                placeholder="🔍 Buscar por nombre, email, tlf..." 
                class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl text-xs text-slate-800 dark:text-white p-2.5 pl-3 shadow-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            >
            @if($search)
                <button type="button" wire:click="$set('search', '')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs">
                    ✕
                </button>
            @endif
        </div>
    </div>

    <!-- TABLA DE USUARIOS -->
    <div class="bg-white dark:bg-slate-900 shadow-sm overflow-hidden rounded-3xl border border-slate-200 dark:border-slate-800">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-left">
                <thead class="bg-slate-50 dark:bg-slate-950 text-slate-500 dark:text-slate-400 text-xs uppercase font-bold tracking-wider">
                    <tr>
                        <th class="px-5 py-3.5">Usuario / Nombre</th>
                        <th class="px-5 py-3.5">Contacto</th>
                        <th class="px-5 py-3.5">Rol / Puesto</th>
                        <th class="px-5 py-3.5">DNI / Fiscal</th>
                        <th class="px-5 py-3.5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <!-- Nombre y Avatar -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl flex items-center justify-center font-black text-sm shrink-0 shadow-2xs {{ $user->role === 'assistant' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-300' : ($user->role === 'dj' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950/70 dark:text-blue-300 border border-blue-300' : ($user->role === 'admin' ? 'bg-purple-100 text-purple-800 dark:bg-purple-950/70 dark:text-purple-300 border border-purple-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-300')) }}">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 dark:text-white text-sm flex items-center gap-1.5">
                                            <span>{{ $user->name }}</span>
                                            @if($user->id === auth()->id())
                                                <span class="text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold px-1.5 py-0.2 rounded border border-slate-300 dark:border-slate-700">Tú</span>
                                            @endif
                                        </div>
                                        @if($user->username)
                                            <div class="text-[11px] text-slate-500 font-mono">@<span>{{ $user->username }}</span></div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Contacto -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div>
                                    @if(str_ends_with($user->email, '@eventosmusicales.local'))
                                        <span class="text-[11px] text-slate-400 italic">Sin email registrado</span>
                                    @else
                                        <span class="text-slate-800 dark:text-slate-200 font-medium">{{ $user->email }}</span>
                                    @endif
                                </div>
                                @if($user->phone)
                                    @php
                                        $cleanPhone = preg_replace('/[^0-9]/', '', $user->phone);
                                        if (strlen($cleanPhone) === 9 && in_array(substr($cleanPhone, 0, 1), ['6', '7'])) {
                                            $cleanPhone = '34' . $cleanPhone;
                                        }
                                    @endphp
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-slate-600 dark:text-slate-400 text-[11px] font-mono">📞 {{ $user->phone }}</span>
                                        <a href="https://wa.me/{{ $cleanPhone }}" target="_blank" class="text-[10px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/70 hover:bg-emerald-100 border border-emerald-300 dark:border-emerald-800 px-1.5 py-0.5 rounded transition">
                                            💬 WhatsApp
                                        </a>
                                    </div>
                                @endif
                            </td>

                            <!-- Rol / Puesto -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if($user->role == 'admin')
                                    <span class="px-2.5 py-1 inline-flex text-xs font-bold rounded-xl bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                        ⚡ Administrador
                                    </span>
                                @elseif($user->role == 'dj')
                                    <span class="px-2.5 py-1 inline-flex text-xs font-bold rounded-xl bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                        🎧 DJ
                                    </span>
                                @elseif($user->role == 'assistant')
                                    <span class="px-2.5 py-1 inline-flex text-xs font-bold rounded-xl bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                        👷‍♂️ Asistente / Personal
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 inline-flex text-xs font-bold rounded-xl bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        👤 Cliente
                                    </span>
                                @endif
                            </td>

                            <!-- DNI / Datos Fiscales -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if($user->dni)
                                    <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ $user->dni }}</span>
                                    @if($user->city)
                                        <div class="text-[11px] text-slate-500">{{ $user->city }} {{ $user->province ? '('.$user->province.')' : '' }}</div>
                                    @endif
                                @else
                                    <span class="text-slate-400 text-[11px]">—</span>
                                @endif
                            </td>

                            <!-- Botones de Acción -->
                            <td class="px-5 py-4 whitespace-nowrap text-right font-medium">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Botón Rápido Cambiar Contraseña -->
                                    <button 
                                        type="button" 
                                        wire:click="openPasswordModal({{ $user->id }})" 
                                        class="p-2 text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 rounded-xl transition cursor-pointer" 
                                        title="Cambiar Contraseña"
                                    >
                                        🔑
                                    </button>

                                    <!-- Botón Editar Datos Completos -->
                                    <button 
                                        type="button" 
                                        wire:click="openEditModal({{ $user->id }})" 
                                        class="p-2 text-amber-700 hover:text-amber-900 bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/60 dark:hover:bg-amber-900/60 rounded-xl transition cursor-pointer" 
                                        title="Editar Datos"
                                    >
                                        ✏️
                                    </button>

                                    <!-- Botón Eliminar (Solo si no es uno mismo) -->
                                    @if($user->id !== auth()->id())
                                        <button 
                                            type="button" 
                                            wire:click="deleteUser({{ $user->id }})" 
                                            wire:confirm="¿Estás seguro de que deseas eliminar a {{ $user->name }}?" 
                                            class="p-2 text-rose-600 hover:text-rose-900 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900/60 rounded-xl transition cursor-pointer" 
                                            title="Eliminar Usuario"
                                        >
                                            🗑️
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-400 text-xs">
                                <span class="text-3xl block mb-2">🔍</span>
                                No se encontraron usuarios con los criterios seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ==================== MODAL DE CREACIÓN ==================== -->
    @if($showCreateModal)
    <div class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" aria-hidden="true" wire:click="closeCreateModal"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 dark:border-slate-800">
                <div class="p-6 sm:p-7 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white" id="modal-title">
                            {{ $role === 'client' ? 'Añadir Nuevo Cliente' : 'Crear Nuevo Usuario / Empleado' }}
                        </h3>
                        <button type="button" wire:click="closeCreateModal" class="text-slate-400 hover:text-slate-600 text-base cursor-pointer">✕</button>
                    </div>
                    
                    <form wire:submit.prevent="saveUser" class="space-y-4">
                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider mb-1">Rol / Tipo de Cuenta *</label>
                            <select wire:model.live="role" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs font-bold text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950">
                                <option value="client">👤 Cliente / Novios</option>
                                <option value="assistant">👷‍♂️ Asistente / Empleado (Ceremonia, Cóctel, Banquete)</option>
                                <option value="dj">🎧 DJ (Baile / Fiesta)</option>
                                <option value="admin">⚡ Administrador General</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider mb-1">Nombre Completo *</label>
                            <input type="text" wire:model="name" required placeholder="{{ $role === 'client' ? 'Ej: Laura y Carlos' : 'Ej: Carlos Gómez' }}" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs font-semibold text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950">
                            @error('name') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider mb-1">Teléfono (WhatsApp)</label>
                                <input type="text" wire:model="phone" placeholder="Ej: 612345678" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950">
                                @error('phone') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider mb-1">Nombre de Usuario</label>
                                <input type="text" wire:model="username" placeholder="carlos" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950">
                                @error('username') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider mb-1">
                                Correo Electrónico {{ $role === 'client' ? '(Opcional)' : '*' }}
                            </label>
                            <input type="email" wire:model="email" {{ $role === 'client' ? '' : 'required' }} placeholder="{{ $role === 'client' ? 'cliente@email.com (Opcional)' : 'carlos@empresa.es' }}" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950">
                            @error('email') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider mb-1">
                                Contraseña {{ $role === 'client' ? '(Opcional)' : '*' }}
                            </label>
                            <input type="password" wire:model="password" {{ $role === 'client' ? '' : 'required' }} placeholder="{{ $role === 'client' ? 'Dejar en blanco para autogenerar' : '••••••••' }}" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950">
                            @error('password') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100 dark:border-slate-800">
                            <button wire:click="closeCreateModal" type="button" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 font-bold rounded-xl text-xs transition">
                                Cancelar
                            </button>
                            <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow-md transition cursor-pointer">
                                Guardar Usuario
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- ==================== MODAL DE EDICIÓN COMPLETA ==================== -->
    @if($showEditModal)
    <div class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-edit-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" aria-hidden="true" wire:click="closeEditModal"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-200 dark:border-slate-800">
                <div class="p-6 sm:p-7 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-1.5" id="modal-edit-title">
                                <span>✏️</span> Editar Datos de Usuario / Cliente
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Modifica los datos personales, de contacto, fiscales o actualiza su contraseña.</p>
                        </div>
                        <button type="button" wire:click="closeEditModal" class="text-slate-400 hover:text-slate-600 text-base cursor-pointer">✕</button>
                    </div>
                    
                    <form wire:submit.prevent="updateUser" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider mb-1">Nombre Completo *</label>
                                <input type="text" wire:model="edit_name" required class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs font-semibold text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950">
                                @error('edit_name') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider mb-1">Rol / Tipo de Cuenta *</label>
                                <select wire:model="edit_role" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs font-bold text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950">
                                    <option value="client">👤 Cliente / Novios</option>
                                    <option value="assistant">👷‍♂️ Asistente / Empleado</option>
                                    <option value="dj">🎧 DJ (Baile / Fiesta)</option>
                                    <option value="admin">⚡ Administrador General</option>
                                </select>
                                @error('edit_role') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider mb-1">Correo Electrónico</label>
                                <input type="email" wire:model="edit_email" placeholder="email@dominio.com" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950">
                                @error('edit_email') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider mb-1">Teléfono (WhatsApp)</label>
                                <input type="text" wire:model="edit_phone" placeholder="612345678" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950">
                                @error('edit_phone') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider mb-1">DNI / NIF / CIF</label>
                                <input type="text" wire:model="edit_dni" placeholder="12345678Z" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs font-mono font-bold text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950 uppercase">
                            </div>

                            <div>
                                <label class="block text-slate-700 dark:text-slate-300 text-xs font-bold uppercase tracking-wider mb-1">Nombre de Usuario (Login)</label>
                                <input type="text" wire:model="edit_username" placeholder="usuario" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950">
                                @error('edit_username') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Datos de Dirección / Localidad -->
                        <div class="p-3.5 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-3">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider block">Dirección y Localidad</span>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Código Postal (CP)</label>
                                    <input type="text" wire:model.live.debounce.300ms="edit_postal_code" placeholder="26370" maxlength="5" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2 text-xs font-bold text-slate-800 dark:text-white bg-white dark:bg-slate-900">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Ciudad / Población</label>
                                    <input type="text" wire:model="edit_city" placeholder="Navarrete" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2 text-xs text-slate-800 dark:text-white bg-white dark:bg-slate-900">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Provincia</label>
                                    <input type="text" wire:model="edit_province" placeholder="La Rioja" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2 text-xs text-slate-800 dark:text-white bg-white dark:bg-slate-900">
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Dirección (Calle, Nº...)</label>
                                    <input type="text" wire:model="edit_address" placeholder="Calle Mayor 12" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2 text-xs text-slate-800 dark:text-white bg-white dark:bg-slate-900">
                                </div>
                            </div>
                        </div>

                        <!-- Nueva Contraseña (Opcional) -->
                        <div class="p-3.5 bg-indigo-50/50 dark:bg-indigo-950/30 rounded-2xl border border-indigo-200 dark:border-indigo-900/60 space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-indigo-950 dark:text-indigo-200 uppercase tracking-wider flex items-center gap-1">
                                    <span>🔑</span> Cambiar Contraseña (Opcional)
                                </label>
                                <span class="text-[10px] text-indigo-500 font-normal">Dejar en blanco para mantener la actual</span>
                            </div>
                            <input 
                                type="text" 
                                wire:model="edit_password" 
                                placeholder="Escribe nueva contraseña si deseas cambiarla..." 
                                class="w-full border-indigo-200 dark:border-indigo-800 rounded-xl p-2.5 text-xs text-slate-800 dark:text-white bg-white dark:bg-slate-900 font-mono"
                            >
                            @error('edit_password') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100 dark:border-slate-800">
                            <button wire:click="closeEditModal" type="button" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 font-bold rounded-xl text-xs transition">
                                Cancelar
                            </button>
                            <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow-md transition cursor-pointer">
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
    <div class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-pass-title" role="dialog" aria-modal="true" x-data="{ copied: false }">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" aria-hidden="true" wire:click="closePasswordModal"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200 dark:border-slate-800">
                <div class="p-6 sm:p-7 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-1.5" id="modal-pass-title">
                                <span>🔑</span> Cambiar Contraseña
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Usuario: <strong class="text-slate-800 dark:text-white">{{ $password_user_name }}</strong></p>
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
                                    class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 cursor-pointer"
                                >
                                    <span>🎲</span> Generar Aleatoria
                                </button>
                            </div>
                            <input 
                                type="text" 
                                wire:model="new_password" 
                                placeholder="Escribe o genera contraseña..." 
                                class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-3 text-sm font-mono font-bold text-slate-900 dark:text-white bg-slate-50 dark:bg-slate-950 focus:bg-white focus:ring-2 focus:ring-indigo-500"
                            >
                            @error('new_password') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                        </div>

                        <!-- Resumen y botón para copiar credenciales generadas -->
                        @if($generatedPasswordInfo)
                            @php
                                $loginUrl = url('/login');
                                $credText = "🔐 *Datos de acceso a la plataforma:*\n\n👤 Usuario / Email: " . ($generatedPasswordInfo['username'] ?: $generatedPasswordInfo['email']) . "\n🔑 Contraseña: " . $generatedPasswordInfo['password'] . "\n🔗 Enlace: " . $loginUrl;
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
                                        <span x-text="copied ? '✓ ¡Credenciales Copiadas!' : '📋 Copiar Datos de Acceso'"></span>
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
                            <button wire:click="saveNewPassword" type="button" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow-md transition cursor-pointer">
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
