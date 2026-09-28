<div>
    <!-- Navegación de Pestañas -->
    <div class="mb-6 border-b border-gray-200">
        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
            <li class="mr-2">
                <button wire:click="$set('activeTab', 'library')" class="inline-block p-4 border-b-2 rounded-t-lg {{ $activeTab == 'library' ? 'border-indigo-600 text-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300 text-gray-500' }}">Biblioteca Principal</button>
            </li>
            <li class="mr-2">
                <button wire:click="$set('activeTab', 'playlists')" class="inline-block p-4 border-b-2 rounded-t-lg {{ in_array($activeTab, ['playlists', 'manage_playlist']) ? 'border-indigo-600 text-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300 text-gray-500' }}">Playlists</button>
            </li>
        </ul>
    </div>

    <!-- BIBLIOTECA GENERAL -->
    @if($activeTab == 'library')
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Formulario Alta -->
        <div class="md:col-span-1 bg-white shadow rounded-lg p-6 h-fit">
            <h3 class="text-lg font-bold mb-4">Añadir Canción</h3>
            @if (session()->has('track_message')) <div class="text-green-600 text-sm mb-2">{{ session('track_message') }}</div> @endif
            
            <form wire:submit.prevent="saveTrack">
                <div class="mb-3">
                    <label class="block text-xs font-bold mb-1">Título *</label>
                    <input type="text" wire:model="track_title" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none">
                    @error('track_title') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="mb-3">
                    <label class="block text-xs font-bold mb-1">Artista</label>
                    <input type="text" wire:model="track_artist" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none">
                </div>
                <div class="mb-3 grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold mb-1">Género</label>
                        <input type="text" wire:model="track_genre" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold mb-1">BPM</label>
                        <input type="number" wire:model="track_bpm" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none">
                    </div>
                </div>
                <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-2 px-4 rounded hover:bg-indigo-700">Guardar Canción</button>
            </form>
        </div>

        <!-- Listado -->
        <div class="md:col-span-2 bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título y Artista</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Género / BPM</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Borrar</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($tracks as $track)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-gray-900">{{ $track->title }}</div>
                                <div class="text-sm text-gray-500">{{ $track->artist ?? 'Desconocido' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $track->genre ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $track->bpm ? $track->bpm . ' BPM' : '' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <button wire:click="deleteTrack({{ $track->id }})" wire:confirm="¿Borrar del catálogo?" class="text-red-500 hover:text-red-700 text-sm">X</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-gray-500">Catálogo vacío. Añade algunas canciones.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- PLAYLISTS -->
    @if($activeTab == 'playlists')
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Formulario Alta Playlist -->
        <div class="md:col-span-1 bg-white shadow rounded-lg p-6 h-fit">
            <h3 class="text-lg font-bold mb-4">Crear Playlist</h3>
            @if (session()->has('playlist_message')) <div class="text-green-600 text-sm mb-2">{{ session('playlist_message') }}</div> @endif
            
            <form wire:submit.prevent="savePlaylist">
                <div class="mb-3">
                    <label class="block text-xs font-bold mb-1">Nombre *</label>
                    <input type="text" wire:model="playlist_name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none" placeholder="Ej: Música Cóctel">
                    @error('playlist_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="mb-4">
                    <label class="block text-xs font-bold mb-1">Vincular a un Evento (Opcional)</label>
                    <select wire:model="playlist_event_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none bg-white">
                        <option value="">-- Lista genérica --</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}">{{ $event->name }} ({{ $event->event_date->format('d/m') }})</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-2 px-4 rounded hover:bg-indigo-700">Crear Playlist</button>
            </form>
        </div>

        <!-- Listado Playlists -->
        <div class="md:col-span-2 bg-white shadow rounded-lg overflow-hidden p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
            @forelse($playlists as $playlist)
                <div class="border rounded-lg p-4 hover:shadow-md transition">
                    <h4 class="font-bold text-gray-800 text-lg">{{ $playlist->name }}</h4>
                    <p class="text-xs text-gray-500 mb-3">
                        @if($playlist->event)
                            Evento: {{ $playlist->event->name }}
                        @else
                            Lista Genérica
                        @endif
                    </p>
                    <button wire:click="managePlaylist({{ $playlist->id }})" class="bg-gray-100 text-gray-700 px-3 py-1 rounded text-sm hover:bg-gray-200">Gestionar canciones</button>
                </div>
            @empty
                <div class="col-span-2 text-center py-6 text-gray-500">No hay listas creadas.</div>
            @endforelse
        </div>
    </div>
    @endif

    <!-- GESTIONAR PLAYLIST ACTIVA -->
    @if($activeTab == 'manage_playlist' && $activePlaylist)
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <button wire:click="backToPlaylists" class="text-sm text-indigo-600 hover:underline mb-1">&larr; Volver a listas</button>
                <h3 class="text-2xl font-bold">Editando: {{ $activePlaylist->name }}</h3>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Canciones en la Playlist -->
            <div>
                <h4 class="font-bold text-gray-700 border-b pb-2 mb-4">Canciones de esta lista</h4>
                <ul class="divide-y divide-gray-200">
                    @forelse($activePlaylist->tracks as $track)
                        <li class="py-2 flex justify-between items-center">
                            <div>
                                <span class="font-semibold">{{ $track->title }}</span> 
                                <span class="text-xs text-gray-500">- {{ $track->artist }}</span>
                            </div>
                            <button wire:click="removeTrackFromPlaylist({{ $track->id }})" class="text-red-500 text-xs hover:underline">Quitar</button>
                        </li>
                    @empty
                        <li class="py-4 text-gray-500 text-sm">Esta lista está vacía. Busca y añade canciones desde la derecha.</li>
                    @endforelse
                </ul>
            </div>

            <!-- Buscador para añadir -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="font-bold text-gray-700 border-b pb-2 mb-4">Añadir desde el catálogo</h4>
                <input type="text" wire:model.live.debounce.300ms="searchTrack" wire:keyup="searchTracksToAdd" placeholder="Buscar por título o artista..." class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-4 focus:outline-none">
                
                @if($searchTrack !== '' && count($searchResults) > 0)
                    <ul class="divide-y divide-gray-200 border rounded bg-white">
                        @foreach($searchResults as $res)
                            <li class="py-2 px-3 flex justify-between items-center">
                                <div>
                                    <span class="font-semibold text-sm">{{ $res->title }}</span> 
                                    <span class="text-xs text-gray-500">- {{ $res->artist }}</span>
                                </div>
                                <button wire:click="addTrackToPlaylist({{ $res->id }})" class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs hover:bg-green-200">Añadir</button>
                            </li>
                        @endforeach
                    </ul>
                @elseif($searchTrack !== '')
                    <p class="text-xs text-gray-500">No se encontraron resultados en tu catálogo.</p>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
