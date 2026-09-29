<div>
    <!-- Navegación de Pestañas -->
    <div class="mb-6 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
            <li class="mr-2">
                <button wire:click="$set('activeTab', 'library')" class="inline-flex items-center gap-2 p-4 border-b-2 rounded-t-lg transition {{ $activeTab == 'library' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent hover:text-gray-600 hover:border-gray-300 text-gray-500' }}">
                    <span>🎵</span> Biblioteca General
                    <span class="px-2 py-0.5 text-xs bg-slate-100 text-slate-700 rounded-full font-bold">{{ $totalTracks }}</span>
                </button>
            </li>
            <li class="mr-2">
                <button wire:click="$set('activeTab', 'playlists')" class="inline-flex items-center gap-2 p-4 border-b-2 rounded-t-lg transition {{ in_array($activeTab, ['playlists', 'manage_playlist']) ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent hover:text-gray-600 hover:border-gray-300 text-gray-500' }}">
                    <span>📑</span> Playlists Oficiales
                    <span class="px-2 py-0.5 text-xs bg-slate-100 text-slate-700 rounded-full font-bold">{{ count($playlists) }}</span>
                </button>
            </li>
        </ul>

        <!-- BOTÓN SINCRONIZAR DRIVE DIRECTO DESDE LA BIBLIOTECA -->
        @if($activeTab === 'library')
            <div class="flex items-center gap-2 pb-2 sm:pb-0">
                <button 
                    wire:click="syncDrive" 
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs rounded-xl shadow-sm transition disabled:opacity-50 cursor-pointer"
                >
                    <span wire:loading.remove wire:target="syncDrive">🔄 Sincronizar Google Drive</span>
                    <span wire:loading wire:target="syncDrive" class="inline-flex items-center gap-1.5">
                        <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        Indexando...
                    </span>
                </button>
                <a href="{{ route('admin.settings', ['tab' => 'music_integrations']) }}" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition" title="Configurar carpeta de Google Drive">
                    ⚙️
                </a>
            </div>
        @endif
    </div>

    <!-- MENSAJES FLASH -->
    @if (session()->has('sync_success'))
        <div class="mb-4 p-4 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-xl text-xs font-bold flex items-center gap-2 shadow-sm">
            <span>✅</span> {{ session('sync_success') }}
        </div>
    @endif
    @if (session()->has('sync_error'))
        <div class="mb-4 p-4 bg-rose-50 text-rose-800 border border-rose-200 rounded-xl text-xs font-bold flex items-center justify-between gap-2 shadow-sm">
            <div class="flex items-center gap-2">
                <span>⚠️</span> {{ session('sync_error') }}
            </div>
            @if(!$driveFolderConfigured)
                <a href="{{ route('admin.settings', ['tab' => 'music_integrations']) }}" class="px-3 py-1 bg-rose-600 text-white rounded-lg font-bold hover:bg-rose-700 transition">
                    Configurar Carpeta
                </a>
            @endif
        </div>
    @endif

    <!-- BIBLIOTECA GENERAL -->
    @if($activeTab == 'library')
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        
        <!-- Formulario Alta Canción Manual -->
        <div class="lg:col-span-1 bg-white shadow-sm rounded-2xl border border-gray-200 p-5 h-fit space-y-4">
            <div class="border-b border-gray-100 pb-3">
                <h3 class="text-sm font-black text-gray-900 flex items-center gap-2">
                    <span>➕</span> Añadir Canción Manual
                </h3>
                <p class="text-xs text-gray-500">Se buscarán automáticamente sus carátulas y enlaces.</p>
            </div>
            
            @if (session()->has('track_message')) 
                <div class="p-3 bg-indigo-50 text-indigo-700 rounded-xl text-xs font-bold">
                    {{ session('track_message') }}
                </div> 
            @endif
            
            <form wire:submit.prevent="saveTrack" class="space-y-3">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Título de la Canción *</label>
                    <input type="text" wire:model="track_title" placeholder="ej. Perfect" class="w-full border-gray-300 rounded-lg text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500">
                    @error('track_title') <span class="text-red-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Artista / Grupo</label>
                    <input type="text" wire:model="track_artist" placeholder="ej. Ed Sheeran" class="w-full border-gray-300 rounded-lg text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Género</label>
                        <input type="text" wire:model="track_genre" placeholder="Pop, Rock..." class="w-full border-gray-300 rounded-lg text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">BPM</label>
                        <input type="number" wire:model="track_bpm" placeholder="128" class="w-full border-gray-300 rounded-lg text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Enlace de Audio MP3 / Drive (Opcional)</label>
                    <input type="text" wire:model="track_file_path" placeholder="https://drive.google.com/..." class="w-full border-gray-300 rounded-lg text-xs font-mono focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-2.5 px-4 rounded-xl text-xs shadow-md shadow-indigo-600/20 transition cursor-pointer">
                    Guardar en Catálogo
                </button>
            </form>

            <!-- Resumen de Almacenamiento -->
            <div class="pt-3 border-t border-gray-100 space-y-2 text-xs">
                <div class="flex justify-between text-gray-600">
                    <span>Total canciones:</span>
                    <strong class="text-gray-900">{{ $totalTracks }}</strong>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>Sincronizadas con Drive:</span>
                    <span class="inline-flex items-center gap-1 font-bold text-sky-700">
                        📁 {{ $driveCount }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Listado y Filtros de Canciones -->
        <div class="lg:col-span-3 space-y-4">
            
            <!-- Barra de Búsqueda y Filtros -->
            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm space-y-3">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="w-full sm:w-72">
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="search" 
                            placeholder="🔍 Buscar por título, artista o género..." 
                            class="w-full border-gray-300 rounded-xl text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500"
                        >
                    </div>

                    <div class="flex items-center gap-1.5 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
                        <button 
                            type="button" 
                            wire:click="$set('sourceFilter', 'all')" 
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ $sourceFilter === 'all' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                        >
                            Todas ({{ $totalTracks }})
                        </button>
                        <button 
                            type="button" 
                            wire:click="$set('sourceFilter', 'google_drive')" 
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ $sourceFilter === 'google_drive' ? 'bg-sky-600 text-white shadow-xs' : 'bg-sky-50 text-sky-700 hover:bg-sky-100' }}"
                        >
                            📁 Drive ({{ $driveCount }})
                        </button>
                        <button 
                            type="button" 
                            wire:click="$set('sourceFilter', 'local')" 
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ $sourceFilter === 'local' ? 'bg-slate-800 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                        >
                            💻 Locales ({{ $totalTracks - $driveCount }})
                        </button>
                    </div>
                </div>

                <!-- Filtro por Carpetas de Google Drive -->
                @if($availableFolders->count() > 0)
                    <div class="pt-2 border-t border-gray-100 flex items-center gap-1.5 overflow-x-auto pb-1 text-xs">
                        <span class="text-gray-500 text-[11px] font-bold shrink-0 flex items-center gap-1">
                            <span>📂</span> Carpetas:
                        </span>
                        <button 
                            type="button" 
                            wire:click="$set('folderFilter', 'all')" 
                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition shrink-0 {{ $folderFilter === 'all' ? 'bg-sky-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                        >
                            Todas las carpetas
                        </button>
                        @foreach($availableFolders as $f)
                            <button 
                                type="button" 
                                wire:click="$set('folderFilter', '{{ $f->cloud_folder }}')" 
                                class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition shrink-0 flex items-center gap-1 {{ $folderFilter === $f->cloud_folder ? 'bg-sky-600 text-white shadow-xs' : 'bg-sky-50 text-sky-800 hover:bg-sky-100 border border-sky-200/60' }}"
                            >
                                <span>📁</span> {{ $f->cloud_folder }}
                                <span class="px-1.5 py-0.2 rounded-full text-[9px] {{ $folderFilter === $f->cloud_folder ? 'bg-sky-800 text-white' : 'bg-sky-200/80 text-sky-900' }} font-bold">
                                    {{ $f->count }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Tabla de Canciones -->
            <div class="bg-white shadow-sm rounded-2xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-xs">
                        <thead class="bg-slate-50 text-gray-600 font-bold uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3">Canción / Artista</th>
                                <th class="px-4 py-3">Origen / Carpeta</th>
                                <th class="px-4 py-3">Preescucha</th>
                                <th class="px-4 py-3">Plataformas</th>
                                <th class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($tracks as $track)
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-900 text-sm">{{ $track->title }}</div>
                                        <div class="text-xs text-gray-500 flex items-center gap-2">
                                            <span>{{ $track->artist ?: 'Artista Desconocido' }}</span>
                                            @if($track->genre)
                                                <span class="px-1.5 py-0.5 bg-gray-100 rounded text-[10px] text-gray-600 font-medium">{{ $track->genre }}</span>
                                            @endif
                                            @if($track->bpm)
                                                <span class="text-[10px] text-gray-400 font-mono">{{ $track->bpm }} BPM</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if($track->source === 'google_drive')
                                            <div>
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-800 border border-sky-200">
                                                    <span>📁</span> Google Drive
                                                </span>
                                                @if($track->cloud_folder && $track->cloud_folder !== 'Raíz')
                                                    <button 
                                                        type="button" 
                                                        wire:click="$set('folderFilter', '{{ $track->cloud_folder }}')" 
                                                        class="text-[10px] text-sky-700 bg-sky-100/70 hover:bg-sky-200 px-1.5 py-0.5 rounded font-mono font-bold block mt-1 transition cursor-pointer" 
                                                        title="Filtrar por esta carpeta"
                                                    >
                                                        📂 {{ $track->cloud_folder }}
                                                    </button>
                                                @endif
                                            </div>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                                <span>💻</span> Local / Manual
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if($track->audio_url)
                                            <audio controls preload="none" class="h-7 w-48 rounded">
                                                <source src="{{ $track->audio_url }}" type="audio/mpeg">
                                            </audio>
                                        @else
                                            <span class="text-gray-400 text-[11px] italic">Sin audio local</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-1.5">
                                            @if($track->spotify_url)
                                                <a href="{{ $track->spotify_url }}" target="_blank" class="p-1 rounded-md bg-emerald-50 text-emerald-700 hover:bg-emerald-100 text-xs font-bold" title="Abrir en Spotify">
                                                    🟢
                                                </a>
                                            @endif
                                            @if($track->apple_music_url)
                                                <a href="{{ $track->apple_music_url }}" target="_blank" class="p-1 rounded-md bg-pink-50 text-pink-700 hover:bg-pink-100 text-xs font-bold" title="Abrir en Apple Music">
                                                    🍎
                                                </a>
                                            @endif
                                            @if($track->youtube_url)
                                                <a href="{{ $track->youtube_url }}" target="_blank" class="p-1 rounded-md bg-rose-50 text-rose-700 hover:bg-rose-100 text-xs font-bold" title="Buscar en YouTube">
                                                    ▶️
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right">
                                        <button 
                                            wire:click="deleteTrack({{ $track->id }})" 
                                            wire:confirm="¿Eliminar '{{ $track->title }}' del catálogo?" 
                                            class="px-2 py-1 text-xs text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded font-bold transition"
                                        >
                                            Eliminar
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                        <div class="space-y-2">
                                            <span class="text-3xl block">🎵</span>
                                            <p class="font-bold text-gray-700">No se encontraron canciones en el catálogo.</p>
                                            <p class="text-xs text-gray-400">
                                                @if(!empty($search))
                                                    Prueba con otro término de búsqueda o limpia el filtro.
                                                @else
                                                    Pulsa en "🔄 Sincronizar Google Drive" o añade una canción manualmente.
                                                @endif
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($tracks->hasPages())
                    <div class="p-4 border-t border-gray-100">
                        {{ $tracks->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- PLAYLISTS -->
    @if($activeTab == 'playlists')
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Formulario Alta Playlist -->
        <div class="md:col-span-1 bg-white shadow-sm rounded-2xl border border-gray-200 p-5 h-fit space-y-4">
            <div>
                <h3 class="text-sm font-black text-gray-900">Crear Nueva Playlist</h3>
                <p class="text-xs text-gray-500">Agrupa canciones para usarlas en momentos específicos.</p>
            </div>
            
            @if (session()->has('playlist_message')) 
                <div class="p-3 bg-emerald-50 text-emerald-700 rounded-xl text-xs font-bold">
                    {{ session('playlist_message') }}
                </div> 
            @endif
            
            <form wire:submit.prevent="savePlaylist" class="space-y-3">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Nombre de la Playlist *</label>
                    <input type="text" wire:model="playlist_name" class="w-full border-gray-300 rounded-lg text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500" placeholder="Ej: Éxitos Cóctel 2026">
                    @error('playlist_name') <span class="text-red-500 text-[11px] block mt-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Vincular a un Evento (Opcional)</label>
                    <select wire:model="playlist_event_id" class="w-full border-gray-300 rounded-lg text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                        <option value="">-- Playlist Genérica / Reutilizable --</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}">{{ $event->name }} ({{ \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') }})</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-2.5 px-4 rounded-xl text-xs shadow-md shadow-indigo-600/20 transition cursor-pointer">
                    Crear Playlist
                </button>
            </form>
        </div>

        <!-- Listado Playlists -->
        <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
            @forelse($playlists as $playlist)
                <div class="bg-white shadow-sm rounded-2xl border border-gray-200 p-5 hover:shadow-md transition flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-black text-gray-900 text-base">{{ $playlist->name }}</h4>
                            <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full">
                                {{ $playlist->tracks->count() }} temas
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mb-4">
                            @if($playlist->event)
                                📅 Vinculada a: <strong>{{ $playlist->event->name }}</strong>
                            @else
                                🌐 Playlist Genérica
                            @endif
                        </p>
                    </div>
                    <button wire:click="managePlaylist({{ $playlist->id }})" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold px-3 py-2 rounded-xl text-xs transition cursor-pointer">
                        Gestionar Canciones &rarr;
                    </button>
                </div>
            @empty
                <div class="col-span-2 bg-white rounded-2xl border border-dashed border-gray-300 p-8 text-center text-gray-500">
                    <span class="text-3xl block mb-2">📑</span>
                    <p class="font-bold">No hay playlists creadas todavía.</p>
                </div>
            @endforelse
        </div>
    </div>
    @endif

    <!-- GESTIONAR PLAYLIST ACTIVA -->
    @if($activeTab == 'manage_playlist' && $activePlaylist)
    <div class="bg-white shadow-sm rounded-2xl border border-gray-200 p-6 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-4">
            <div>
                <button wire:click="backToPlaylists" class="text-xs font-bold text-indigo-600 hover:underline mb-1 inline-flex items-center gap-1">
                    &larr; Volver al listado de playlists
                </button>
                <h3 class="text-xl font-black text-gray-900">Playlist: {{ $activePlaylist->name }}</h3>
            </div>
            <span class="text-xs font-bold px-3 py-1 bg-indigo-50 text-indigo-700 rounded-full">
                {{ $activePlaylist->tracks->count() }} canciones añadidas
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Canciones en la Playlist -->
            <div class="space-y-3">
                <h4 class="font-bold text-xs uppercase tracking-wider text-gray-700 border-b pb-2">
                    Canciones en esta lista
                </h4>
                <ul class="divide-y divide-gray-100 max-h-96 overflow-y-auto pr-2">
                    @forelse($activePlaylist->tracks as $track)
                        <li class="py-2.5 flex justify-between items-center gap-3">
                            <div>
                                <span class="font-bold text-sm text-gray-900">{{ $track->title }}</span> 
                                <span class="text-xs text-gray-500 block">{{ $track->artist ?: 'Artista Desconocido' }}</span>
                            </div>
                            <button wire:click="removeTrackFromPlaylist({{ $track->id }})" class="text-rose-600 hover:text-rose-800 text-xs font-bold hover:underline">
                                Quitar
                            </button>
                        </li>
                    @empty
                        <li class="py-6 text-center text-gray-400 text-xs">
                            Esta playlist está vacía. Añade canciones desde el catálogo a la derecha.
                        </li>
                    @endforelse
                </ul>
            </div>

            <!-- Buscador para añadir desde el catálogo -->
            <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200 space-y-4">
                <div>
                    <h4 class="font-bold text-xs uppercase tracking-wider text-gray-800">
                        Añadir canciones del catálogo
                    </h4>
                    <p class="text-xs text-gray-500">Busca por título o artista de tu biblioteca.</p>
                </div>

                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="searchTrack" 
                    wire:keyup="searchTracksToAdd" 
                    placeholder="Escribe para buscar (ej. Queen, Despacito)..." 
                    class="w-full border-gray-300 rounded-xl text-xs font-medium focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                >
                
                @if($searchTrack !== '' && count($searchResults) > 0)
                    <ul class="divide-y divide-gray-200 border border-gray-200 rounded-xl bg-white max-h-60 overflow-y-auto shadow-xs">
                        @foreach($searchResults as $res)
                            <li class="p-3 flex justify-between items-center hover:bg-slate-50 transition">
                                <div>
                                    <span class="font-bold text-xs text-gray-900 block">{{ $res->title }}</span> 
                                    <span class="text-[11px] text-gray-500">{{ $res->artist ?: 'Artista Desconocido' }}</span>
                                </div>
                                <button wire:click="addTrackToPlaylist({{ $res->id }})" class="bg-emerald-100 hover:bg-emerald-200 text-emerald-800 font-bold px-3 py-1 rounded-lg text-xs transition">
                                    + Añadir
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @elseif(strlen($searchTrack) > 1)
                    <p class="text-xs text-gray-500 italic text-center py-3">No se encontraron coincidencias en tu catálogo.</p>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
