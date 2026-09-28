<div 
    class="min-h-screen bg-[#080d16] text-slate-100 p-3 sm:p-5 select-none font-sans pb-32" 
    wire:poll.10s
    x-data="djAudioPlayer()"
    x-init="initPlayer()"
>
    <!-- TOP NAVIGATION & EXIT BAR -->
    <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-3 pb-3 mb-3 border-b border-slate-800/80">
        
        <!-- BOTONES DE RETORNO / SALIR DEL MODO CABINA -->
        <div class="flex items-center gap-2">
            @if(auth()->check() && auth()->user()->role === 'admin')
                <a href="{{ route('admin.events.show', $event->id) }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-200 hover:text-white text-xs font-bold border border-slate-700 transition shadow-sm">
                    &larr; Volver a Ficha
                </a>
                <a href="{{ route('admin.events') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white text-xs font-medium border border-slate-800 transition">
                    🎉 Eventos
                </a>
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white text-xs font-medium border border-slate-800 transition">
                    📊 Panel
                </a>
            @else
                <button type="button" onclick="window.history.back()" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-200 hover:text-white text-xs font-bold border border-slate-700 transition">
                    &larr; Volver Atrás
                </button>
            @endif
        </div>

        <!-- ACCESOS RÁPIDOS Y ESTADO DE APPLE MUSIC / SPOTIFY -->
        <div class="flex flex-wrap items-center gap-2">
            
            <!-- BADGE APPLE MUSIC -->
            <template x-if="appleMusicReady">
                <div class="px-2.5 py-1 rounded-xl bg-pink-950/80 border border-pink-500/40 text-pink-300 text-xs font-bold flex items-center gap-1.5 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-pink-500 animate-pulse"></span>
                    <span>🍎 Apple Music Streaming</span>
                </div>
            </template>

            <!-- BADGE SPOTIFY -->
            <template x-if="spotifyReady">
                <div class="px-2.5 py-1 rounded-xl bg-emerald-950/80 border border-emerald-500/40 text-emerald-400 text-xs font-bold flex items-center gap-1.5 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>🟢 Spotify Streaming</span>
                </div>
            </template>

            <template x-if="!spotifyReady && !appleMusicReady">
                <div class="px-2.5 py-1 rounded-xl bg-slate-900 border border-red-500/40 text-red-300 text-xs font-bold flex items-center gap-1.5 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                    <span>🔴 Reproductor Completo (100% Canción)</span>
                </div>
            </template>

            <div class="flex items-center gap-1.5 bg-slate-900/90 p-1 rounded-xl border border-slate-800 text-xs">
                <button type="button" wire:click="setTab('escaleta')" class="px-3 py-1 rounded-lg font-bold transition {{ $activeTab === 'escaleta' ? 'bg-cyan-600 text-white shadow-sm' : 'text-slate-400 hover:text-white' }}">
                    📑 Escaleta
                </button>
                @if(auth()->check() && auth()->user()->role === 'admin')
                    <a href="{{ route('admin.music') }}" class="px-3 py-1 rounded-lg font-medium text-slate-400 hover:text-white transition">
                        🎵 Biblioteca
                    </a>
                    <a href="{{ route('admin.analytics') }}" class="px-3 py-1 rounded-lg font-medium text-slate-400 hover:text-white transition">
                        📈 Estadísticas
                    </a>
                @endif
                <a href="{{ route('guest.requests', $event->token) }}" target="_blank" class="px-3 py-1 rounded-lg font-bold text-purple-400 hover:text-purple-300 transition">
                    📱 QR Clientes
                </a>
            </div>
        </div>
    </div>

    <!-- FLASH MESSAGE -->
    @if (session()->has('booth_message'))
        <div class="max-w-7xl mx-auto mb-3 bg-emerald-950/80 border border-emerald-500/50 text-emerald-200 px-4 py-2.5 rounded-2xl text-xs font-bold flex items-center justify-between shadow-lg">
            <span>{{ session('booth_message') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-white text-base">&times;</button>
        </div>
    @endif

    <!-- LIVE TRACKER BAR (EN VIVO / PROGRESO) -->
    <div class="max-w-7xl mx-auto mb-4 bg-gradient-to-r from-red-950/40 via-slate-900/90 to-slate-900/90 border border-red-900/40 rounded-2xl p-3.5 shadow-lg">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="flex h-3 w-3 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                </span>
                <div class="text-xs font-bold tracking-wide">
                    <span class="text-red-400 uppercase">● MODO CABINA EN VIVO</span>
                    <span class="text-slate-300 ml-2"><strong>{{ $playedCount }}</strong> / {{ $totalCount }} canciones reproducidas</span>
                    <span class="text-red-400 ml-1">({{ $progressPct }}%)</span>
                </div>
            </div>

            @if($lastPlayed)
                <div class="text-xs text-slate-400 flex items-center gap-1.5 truncate">
                    <span class="text-emerald-400">✅ Última:</span>
                    <span class="font-bold text-slate-200 truncate">{{ $lastPlayed->title }}</span>
                    @if($lastPlayed->artist) <span class="text-slate-400 truncate">- {{ $lastPlayed->artist }}</span> @endif
                </div>
            @endif
        </div>

        <div class="w-full bg-slate-800/80 rounded-full h-1.5 mt-2.5 overflow-hidden">
            <div class="bg-gradient-to-r from-red-500 via-pink-500 to-cyan-400 h-1.5 rounded-full transition-all duration-500" style="width: {{ max(5, $progressPct) }}%"></div>
        </div>
    </div>

    <!-- TABS PRINCIPALES -->
    <div class="max-w-7xl mx-auto mb-5 flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-slate-800 pb-3">
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 scrollbar-thin">
            <button type="button" wire:click="setTab('escaleta')" class="px-3.5 py-1.5 rounded-xl text-xs font-black whitespace-nowrap transition cursor-pointer flex items-center gap-1.5 {{ $activeTab === 'escaleta' ? 'bg-cyan-500 text-slate-950 shadow-md shadow-cyan-500/30 ring-2 ring-cyan-400/40' : 'bg-slate-900/80 text-cyan-400 hover:text-white hover:bg-slate-800' }}">
                <span>📑</span> Escaleta
            </button>
            <button type="button" wire:click="setTab('tracks')" class="px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition cursor-pointer flex items-center gap-1.5 {{ in_array($activeTab, ['tracks', 'requests', 'custom', 'playlists']) ? 'bg-cyan-500 text-slate-950 font-black shadow-md shadow-cyan-500/30 ring-2 ring-cyan-400/40' : 'bg-slate-900/80 text-slate-300 hover:text-white hover:bg-slate-800' }}">
                <span>🎵</span> Repertorio Evento ({{ $totalCount }})
            </button>
            <button type="button" wire:click="setTab('guest_live')" class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition cursor-pointer flex items-center gap-1.5 {{ $activeTab === 'guest_live' ? 'bg-purple-600 text-white font-black shadow-md shadow-purple-600/40 ring-2 ring-purple-400/40' : 'bg-slate-900/80 text-purple-400 hover:text-white hover:bg-slate-800' }}">
                <span>⚡</span> En Vivo QR ({{ $guestRequestsCount }})
            </button>
            <button type="button" wire:click="setTab('blacklist')" class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition cursor-pointer flex items-center gap-1.5 {{ $activeTab === 'blacklist' ? 'bg-rose-600 text-white font-black shadow-md shadow-rose-600/40 ring-2 ring-rose-400/40' : 'bg-slate-900/80 text-rose-400 hover:text-white hover:bg-slate-800' }}">
                <span>🚫</span> Lista Negra ({{ $blacklist->count() }})
            </button>
            <button type="button" wire:click="setTab('notes')" class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition cursor-pointer flex items-center gap-1.5 {{ $activeTab === 'notes' ? 'bg-indigo-600 text-white font-black shadow-md' : 'bg-slate-900/80 text-slate-300 hover:text-white hover:bg-slate-800' }}">
                <span>📝</span> Notas DJ
            </button>
            <button type="button" wire:click="setTab('checklist')" class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition cursor-pointer flex items-center gap-1.5 {{ $activeTab === 'checklist' ? 'bg-emerald-600 text-white font-black shadow-md' : 'bg-slate-900/80 text-slate-300 hover:text-white hover:bg-slate-800' }}">
                <span>✅</span> Checklist
            </button>
            <button type="button" wire:click="setTab('documents')" class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition cursor-pointer flex items-center gap-1.5 {{ $activeTab === 'documents' ? 'bg-blue-600 text-white font-black shadow-md' : 'bg-slate-900/80 text-slate-300 hover:text-white hover:bg-slate-800' }}">
                <span>📄</span> Documentos
            </button>
        </div>

        <div class="flex items-center gap-2">
            <button wire:click="$set('showAddModal', true)" class="px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-emerald-600 to-cyan-600 hover:from-emerald-500 hover:to-cyan-500 text-white font-bold text-xs shadow-md transition flex items-center gap-1.5 cursor-pointer whitespace-nowrap">
                <span>➕</span> Añadir Canción
            </button>
        </div>
    </div>

    <!-- CONTENIDO SEGÚN LA PESTAÑA ACTIVA -->
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- PESTAÑA 1: 📑 ESCALETA -->
        @if($activeTab === 'escaleta')
            
            <div class="bg-slate-900/70 border border-slate-800 rounded-3xl p-5 shadow-lg space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h2 class="text-base font-black text-white flex items-center gap-2">
                        <span>📗</span> Resumen del evento
                    </h2>
                    <span class="text-xs text-cyan-400 font-bold bg-slate-950 px-2.5 py-1 rounded-xl border border-slate-800">
                        {{ $event->name }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                    <div class="flex items-center gap-2 text-slate-300">
                        <span>📅</span>
                        <span><strong>{{ $event->event_date ? $event->event_date->format('Y-m-d') : 'Sin fecha' }}</strong></span>
                    </div>

                    <div class="flex items-center gap-2 text-slate-300">
                        <span>📍</span>
                        <span><strong>{{ $event->location ?: 'Delicatto (Logroño)' }}</strong></span>
                    </div>

                    <div class="flex items-center gap-2 text-slate-300">
                        <span>⏰</span>
                        <span><strong>00:30 - 04:30</strong> <span class="text-slate-500 ml-1">Baile: 4 horas</span></span>
                    </div>

                    <div class="flex items-center gap-2 text-slate-300">
                        <span>👥</span>
                        <span>
                            DJ: <strong class="text-cyan-400">{{ $event->dj ? $event->dj->name : 'Luis' }}</strong>
                            &bull; Asistente: <strong class="text-amber-400">{{ $event->assistant ? $event->assistant->name : 'Fran' }}</strong>
                        </span>
                    </div>
                </div>

                <div class="pt-2 text-xs space-y-1 text-slate-400 border-t border-slate-800/80">
                    <span class="text-[11px] font-black uppercase text-slate-500 block mb-1">📍 ESPACIOS POR FASE</span>
                    <p><strong class="text-cyan-400">Banquete:</strong> Salón 1 (Entrada comedor, corte tarta, momentos)</p>
                    <p><strong class="text-cyan-400">Baile:</strong> Discoteca 1 (Sesión de 4 horas aprox)</p>
                </div>
            </div>

            <div class="bg-slate-900/70 border border-slate-800 rounded-3xl p-5 shadow-lg space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800 pb-3">
                    <div>
                        <h3 class="text-base font-black text-white flex items-center gap-2">
                            <span>📑</span> Escaleta del evento
                        </h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">El guion cronológico de la noche con enlaces instantáneos a MP3, Apple Music, Spotify y YouTube.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button 
                            type="button" 
                            wire:click="generateEscaleta" 
                            class="px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-black text-xs shadow-md shadow-purple-600/30 transition flex items-center gap-1.5 cursor-pointer"
                        >
                            <span>🪄</span> Generar escaleta
                        </button>

                        <a 
                            href="{{ route('pdf.music-escaleta', $event->id) }}" 
                            target="_blank" 
                            class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white font-bold text-xs border border-slate-700 transition flex items-center gap-1.5"
                        >
                            <span>📄</span> Informe PDF
                        </a>

                        <button 
                            type="button" 
                            wire:click="saveNotes" 
                            class="px-3.5 py-1.5 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-black text-xs shadow-md transition flex items-center gap-1.5 cursor-pointer"
                        >
                            <span>💾</span> Guardar
                        </button>
                    </div>
                </div>

                <div class="space-y-2.5">
                    @forelse($escaletaItems as $index => $item)
                        @php
                            $searchQuery = urlencode(trim(($item->artist ? $item->artist . ' ' : '') . $item->title));
                            $spUrl = $item->spotify_url ?: "https://open.spotify.com/search/{$searchQuery}";
                            $appleUrl = $item->apple_music_url ?: "https://music.apple.com/es/search?term={$searchQuery}";
                            $ytUrl = $item->youtube_url ?: "https://www.youtube.com/results?search_query={$searchQuery}";
                            $isPlaying = $item->status === 'playing';
                            $isPlayed = $item->status === 'played';
                        @endphp

                        <div class="p-3.5 rounded-2xl border transition flex flex-col md:flex-row md:items-center justify-between gap-3 {{ $isPlaying ? 'bg-emerald-950/50 border-emerald-500 shadow-md ring-2 ring-emerald-500/20' : ($isPlayed ? 'bg-slate-950/80 border-slate-800 opacity-60' : 'bg-slate-950/60 border-slate-800 hover:border-slate-700') }}">
                            
                            <div class="flex items-center gap-3">
                                <div class="flex flex-col items-center gap-0.5">
                                    <button type="button" wire:click="moveMomentUp({{ $item->id }})" class="text-slate-400 hover:text-white text-[10px] p-0.5">&uarr;</button>
                                    <span class="font-mono text-xs font-bold text-cyan-400">#{{ $index + 1 }}</span>
                                    <button type="button" wire:click="moveMomentDown({{ $item->id }})" class="text-slate-400 hover:text-white text-[10px] p-0.5">&darr;</button>
                                </div>

                                <button 
                                    type="button" 
                                    @click="handleTrackPlay({{ $item->id }}, {{ json_encode($item->title) }}, {{ json_encode($item->artist ?? '') }}, {{ json_encode($item->audio_file ?? '') }}, {{ json_encode($item->spotify_url ?? '') }}, {{ json_encode($item->apple_music_url ?? '') }})" 
                                    class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-xs shadow-sm transition {{ $isPlaying ? 'bg-emerald-500 text-slate-950' : 'bg-slate-800 text-slate-200 hover:bg-slate-700' }}"
                                >
                                    <span x-text="currentId === {{ $item->id }} && isPlaying ? '⏸' : '▶'"></span>
                                </button>

                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-cyan-950 text-cyan-300 border border-cyan-800/60">
                                            {{ $item->moment ?: 'MOMENTO' }}
                                        </span>
                                        @if($item->cue_time)
                                            <span class="text-[10px] font-bold text-amber-300 bg-amber-950/80 px-1.5 py-0.5 rounded border border-amber-800/60">
                                                ⏱️ {{ $item->cue_time }}
                                            </span>
                                        @endif
                                        @if($item->audio_file)
                                            <span class="text-[10px] font-bold text-emerald-400 bg-emerald-950/80 px-1.5 py-0.5 rounded border border-emerald-800/60 font-mono">
                                                MP3 Completo
                                            </span>
                                        @endif
                                    </div>
                                    <h4 class="font-bold text-white text-sm mt-0.5 {{ $isPlayed ? 'line-through text-slate-500' : '' }}">
                                        {{ $item->title }}
                                        @if($item->artist) <span class="text-xs text-slate-400 font-normal">&bull; {{ $item->artist }}</span> @endif
                                    </h4>
                                    @if($item->notes)
                                        <p class="text-[11px] text-amber-300/80 italic mt-0.5">📝 {{ $item->notes }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-1.5 self-end md:self-center">
                                <a href="{{ $appleUrl }}" target="_blank" class="px-2 py-1 rounded-lg text-xs font-bold bg-pink-950/80 text-pink-300 hover:bg-pink-800 hover:text-white border border-pink-700/50 transition">
                                    Apple
                                </a>
                                <a href="{{ $spUrl }}" target="_blank" class="px-2 py-1 rounded-lg text-xs font-bold bg-emerald-950/80 text-emerald-400 hover:bg-emerald-800 hover:text-white border border-emerald-700/50 transition">
                                    Spotify
                                </a>
                                <a href="{{ $ytUrl }}" target="_blank" class="px-1.5 py-1 rounded-lg text-xs font-bold bg-slate-800 text-slate-300 hover:text-white transition">
                                    YT
                                </a>
                                <button 
                                    type="button" 
                                    wire:click="setStatus({{ $item->id }}, '{{ $isPlayed ? 'pending' : 'played' }}')" 
                                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition {{ $isPlayed ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white' }}"
                                >
                                    {{ $isPlayed ? '✓ Sonada' : 'Listo' }}
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center bg-slate-950/40 rounded-2xl border border-dashed border-slate-800">
                            <span class="text-3xl">📑</span>
                            <p class="text-sm font-bold text-slate-300 mt-2">Aún no hay escaleta generada.</p>
                            <p class="text-xs text-slate-500 mt-1">Pulsa «Generar escaleta» para crearla automáticamente.</p>
                        </div>
                    @endforelse
                </div>

                <div class="pt-2">
                    <button 
                        type="button" 
                        wire:click="$set('showAddMomentModal', true)" 
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white font-bold text-xs border border-slate-700 transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <span>➕</span> Añadir momento
                    </button>
                </div>
            </div>

            <!-- PETICIONES AGRUPADAS POR MOMENTOS -->
            <div class="bg-slate-900/70 border border-slate-800 rounded-3xl p-5 shadow-lg space-y-5">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-black text-white flex items-center gap-2">
                        <span>🎵</span> Peticiones del evento <span class="text-cyan-400 font-bold">({{ $event->musicRequests->count() }})</span>
                    </h3>
                    <span class="text-xs text-slate-500">Agrupadas por momentos</span>
                </div>

                <div class="space-y-4">
                    @foreach($groupedByMoment as $momentName => $songs)
                        <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-4 space-y-2.5">
                            <span class="text-xs font-black uppercase text-cyan-400 tracking-wider flex items-center gap-1.5">
                                <span>✨</span> {{ $momentName }} <span class="text-slate-500 font-normal">({{ $songs->count() }})</span>
                            </span>

                            <div class="space-y-2">
                                @foreach($songs as $song)
                                    @php
                                        $searchQuery = urlencode(trim(($song->artist ? $song->artist . ' ' : '') . $song->title));
                                        $spUrl = $song->spotify_url ?: "https://open.spotify.com/search/{$searchQuery}";
                                        $appleUrl = $song->apple_music_url ?: "https://music.apple.com/es/search?term={$searchQuery}";
                                        $ytUrl = $song->youtube_url ?: "https://www.youtube.com/results?search_query={$searchQuery}";
                                        $isPlaying = $song->status === 'playing';
                                    @endphp

                                    <div class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800/80 flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-2.5 truncate">
                                            <span class="text-slate-500 text-xs">🎵</span>
                                            <div class="truncate">
                                                <strong class="text-xs font-bold text-white">{{ $song->title }}</strong>
                                                @if($song->artist) <span class="text-xs text-slate-400 ml-1">— {{ $song->artist }}</span> @endif
                                                @if($song->audio_file) <span class="text-[10px] text-cyan-400 ml-1.5 font-mono bg-cyan-950/60 px-1.5 py-0.5 rounded border border-cyan-800/40">Audio Subido</span> @endif
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-1.5 flex-shrink-0">
                                            <button 
                                                type="button" 
                                                @click="handleTrackPlay({{ $song->id }}, {{ json_encode($song->title) }}, {{ json_encode($song->artist ?? '') }}, {{ json_encode($song->audio_file ?? '') }}, {{ json_encode($song->spotify_url ?? '') }}, {{ json_encode($song->apple_music_url ?? '') }})" 
                                                class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold transition {{ $isPlaying ? 'bg-emerald-500 text-slate-950' : 'bg-slate-800 text-emerald-400 hover:bg-slate-700' }}"
                                            >
                                                <span x-text="currentId === {{ $song->id }} && isPlaying ? '⏸' : '▶'"></span>
                                            </button>

                                            <a href="{{ $appleUrl }}" target="_blank" class="px-2 py-1 rounded-md text-[10px] font-bold bg-pink-950/80 text-pink-300 hover:bg-pink-800 hover:text-white border border-pink-700/50 transition">
                                                Apple
                                            </a>

                                            <a href="{{ $spUrl }}" target="_blank" class="px-2 py-1 rounded-md text-[10px] font-bold bg-emerald-950/80 text-emerald-400 hover:bg-emerald-800 hover:text-white border border-emerald-700/50 transition">
                                                Spotify
                                            </a>

                                            <a href="{{ $ytUrl }}" target="_blank" class="px-1.5 py-1 rounded-md text-[10px] font-bold bg-slate-800 text-red-400 hover:bg-red-800 hover:text-white transition">
                                                YT
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        <!-- PESTAÑAS DE CANCIONES (PADS & LISTA) -->
        @elseif(in_array($activeTab, ['tracks', 'custom', 'playlists', 'requests', 'guest_live', 'blacklist']))
            
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-4 sm:p-5 shadow-lg space-y-4">
                
                @if($activeTab === 'blacklist')
                    <div class="bg-rose-950/60 border border-rose-500/40 rounded-2xl p-4 flex items-center gap-3">
                        <span class="text-3xl">🚫</span>
                        <div>
                            <h3 class="text-sm font-black text-rose-300 uppercase tracking-wide">Lista Negra de los Novios</h3>
                            <p class="text-xs text-rose-200/80">Canciones prohibidas expresamente por el cliente. ¡No reproducir bajo ningún concepto!</p>
                        </div>
                    </div>
                @elseif($activeTab === 'guest_live')
                    <div class="bg-purple-950/60 border border-purple-500/40 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">⚡</span>
                            <div>
                                <h3 class="text-sm font-black text-purple-300 uppercase tracking-wide">Peticiones en Vivo vía QR</h3>
                                <p class="text-xs text-purple-200/80">Canciones enviadas y votadas en directo por los invitados durante la fiesta.</p>
                            </div>
                        </div>
                        <a href="{{ route('guest.requests', $event->token) }}" target="_blank" class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs rounded-xl shadow-md inline-flex items-center gap-1.5 self-start sm:self-auto">
                            📱 Abrir Portal QR
                        </a>
                    </div>
                @endif

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
                    <div class="relative flex-1 max-w-md">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">🔍</span>
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="search" 
                            placeholder="Buscar por título, artista o momento..." 
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl pl-9 pr-4 py-2 text-xs text-white placeholder-slate-500 focus:ring-2 focus:ring-cyan-500 focus:outline-none"
                        >
                    </div>

                    <div class="flex items-center gap-1 bg-slate-950 p-1 rounded-xl border border-slate-800 self-start sm:self-auto">
                        <button 
                            type="button" 
                            wire:click="setViewMode('pads')" 
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $viewMode === 'pads' ? 'bg-cyan-500 text-slate-950 shadow' : 'text-slate-400 hover:text-white' }}"
                        >
                            <span>🎛️</span> Pads Sampler
                        </button>
                        <button 
                            type="button" 
                            wire:click="setViewMode('list')" 
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $viewMode === 'list' ? 'bg-cyan-500 text-slate-950 shadow' : 'text-slate-400 hover:text-white' }}"
                        >
                            <span>📋</span> Lista Detallada
                        </button>
                    </div>
                </div>

                <!-- MODO PADS -->
                @if($viewMode === 'pads')
                    @php
                        $colors = [
                            ['border' => 'border-cyan-500', 'hover' => 'hover:border-cyan-400', 'glow' => 'shadow-cyan-500/20', 'tag' => 'text-cyan-400', 'tag_bg' => 'bg-cyan-950/60'],
                            ['border' => 'border-fuchsia-500', 'hover' => 'hover:border-fuchsia-400', 'glow' => 'shadow-fuchsia-500/20', 'tag' => 'text-fuchsia-400', 'tag_bg' => 'bg-fuchsia-950/60'],
                            ['border' => 'border-amber-400', 'hover' => 'hover:border-amber-300', 'glow' => 'shadow-amber-400/20', 'tag' => 'text-amber-400', 'tag_bg' => 'bg-amber-950/60'],
                            ['border' => 'border-lime-400', 'hover' => 'hover:border-lime-300', 'glow' => 'shadow-lime-400/20', 'tag' => 'text-lime-400', 'tag_bg' => 'bg-lime-950/60'],
                            ['border' => 'border-purple-500', 'hover' => 'hover:border-purple-400', 'glow' => 'shadow-purple-500/20', 'tag' => 'text-purple-400', 'tag_bg' => 'bg-purple-950/60'],
                            ['border' => 'border-emerald-400', 'hover' => 'hover:border-emerald-300', 'glow' => 'shadow-emerald-400/20', 'tag' => 'text-emerald-400', 'tag_bg' => 'bg-emerald-950/60'],
                            ['border' => 'border-pink-500', 'hover' => 'hover:border-pink-400', 'glow' => 'shadow-pink-500/20', 'tag' => 'text-pink-400', 'tag_bg' => 'bg-pink-950/60'],
                            ['border' => 'border-sky-400', 'hover' => 'hover:border-sky-300', 'glow' => 'shadow-sky-400/20', 'tag' => 'text-sky-400', 'tag_bg' => 'bg-sky-950/60'],
                        ];
                    @endphp

                    <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3.5 pt-2">
                        @forelse($paginatedPads as $index => $req)
                            @php
                                $colorScheme = $colors[$index % count($colors)];
                                $searchQuery = urlencode(trim(($req->artist ? $req->artist . ' ' : '') . $req->title));
                                $spUrl = $req->spotify_url ?: "https://open.spotify.com/search/{$searchQuery}";
                                $appleUrl = $req->apple_music_url ?: "https://music.apple.com/es/search?term={$searchQuery}";
                                $ytUrl = $req->youtube_url ?: "https://www.youtube.com/results?search_query={$searchQuery}";
                                $isPlaying = $req->status === 'playing';
                                $isPaused = $req->status === 'paused';
                                $isPlayed = $req->status === 'played';
                            @endphp

                            <div 
                                @click="handleTrackPlay({{ $req->id }}, {{ json_encode($req->title) }}, {{ json_encode($req->artist ?? '') }}, {{ json_encode($req->audio_file ?? '') }}, {{ json_encode($req->spotify_url ?? '') }}, {{ json_encode($req->apple_music_url ?? '') }})"
                                class="relative rounded-2xl p-4 flex flex-col justify-between min-h-[220px] transition-all duration-200 cursor-pointer group select-none border-2"
                                :class="{
                                    'bg-slate-900/95 border-emerald-400 shadow-2xl shadow-emerald-500/40 ring-4 ring-emerald-500/20 scale-[1.02]': currentId === {{ $req->id }} && isPlaying,
                                    'bg-slate-900/90 border-amber-400 shadow-xl shadow-amber-400/20': currentId === {{ $req->id }} && isPaused,
                                    'bg-slate-950/70 border-slate-800 opacity-60': '{{ $req->status }}' === 'played' && currentId !== {{ $req->id }},
                                    'bg-slate-900/70 {{ $colorScheme['border'] }} {{ $colorScheme['hover'] }} shadow-lg {{ $colorScheme['glow'] }}': currentId !== {{ $req->id }} && '{{ $req->status }}' !== 'played'
                                }"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-md uppercase tracking-wider {{ $colorScheme['tag'] }} bg-slate-950/80 border border-slate-800">
                                        @if($req->is_guest_request)
                                            ⚡ INVITADO @if($req->likes > 0) 🔥 {{ $req->likes }} @endif
                                        @elseif($req->audio_file)
                                            📁 MP3 LOCAL
                                        @else
                                            🎵 STREAMING
                                        @endif
                                    </span>
                                    <template x-if="currentId === {{ $req->id }} && isPlaying">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500 text-slate-950 animate-pulse">▶ SONANDO</span>
                                    </template>
                                </div>

                                <div class="my-auto py-3 text-center">
                                    <h3 class="text-base sm:text-lg font-black text-white leading-tight tracking-tight group-hover:scale-105 transition-transform duration-200 line-clamp-3">
                                        {{ $req->title }}
                                    </h3>
                                    @if($req->artist)
                                        <p class="text-xs text-slate-400 font-semibold mt-1 truncate">{{ $req->artist }}</p>
                                    @endif
                                    @if($req->notes)
                                        <p class="text-[10px] text-amber-300/80 italic mt-1 truncate">📝 {{ $req->notes }}</p>
                                    @endif
                                </div>

                                <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between gap-1.5" @click.stop>
                                    <span class="text-[9px] font-black uppercase tracking-wider truncate max-w-[100px] px-1.5 py-0.5 rounded {{ $colorScheme['tag_bg'] }} {{ $colorScheme['tag'] }}">
                                        {{ $req->moment ?: 'FIESTA' }}
                                    </span>
                                    <div class="flex items-center gap-1">
                                        <a href="{{ $appleUrl }}" target="_blank" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-pink-950/80 text-pink-300 hover:bg-pink-800 hover:text-white border border-pink-700/50 transition">APPLE</a>
                                        <a href="{{ $spUrl }}" target="_blank" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-950/80 text-emerald-400 hover:bg-emerald-800 hover:text-white border border-emerald-700/50 transition">SPOTIFY</a>
                                        <a href="{{ $ytUrl }}" target="_blank" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-300 hover:text-white transition">▶</a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full py-16 text-center bg-slate-950/40 rounded-3xl border border-dashed border-slate-800">
                                <span class="text-4xl">🎵</span>
                                <p class="text-base font-bold text-slate-300 mt-2">No se encontraron canciones</p>
                                <p class="text-xs text-slate-500 mt-1">Prueba con otro término de búsqueda o añade una nueva canción.</p>
                            </div>
                        @endforelse
                    </div>

                    @if($totalPages > 1)
                        <div class="flex items-center justify-between pt-4 border-t border-slate-800/80">
                            <button 
                                type="button" 
                                wire:click="prevPage" 
                                {{ $padPage <= 1 ? 'disabled' : '' }} 
                                class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 disabled:opacity-30 disabled:cursor-not-allowed text-xs font-bold text-white transition flex items-center gap-1.5"
                            >
                                &larr; Anterior
                            </button>
                            <span class="text-xs font-mono font-bold text-slate-400">
                                Página <strong class="text-cyan-400">{{ $padPage }}</strong> de {{ $totalPages }} ({{ $totalItems }} temas)
                            </span>
                            <button 
                                type="button" 
                                wire:click="nextPage({{ $totalPages }})" 
                                {{ $padPage >= $totalPages ? 'disabled' : '' }} 
                                class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 disabled:opacity-30 disabled:cursor-not-allowed text-xs font-bold text-white transition flex items-center gap-1.5"
                            >
                                Siguiente &rarr;
                            </button>
                        </div>
                    @endif

                <!-- MODO LISTA -->
                @else
                    <div class="overflow-x-auto rounded-2xl border border-slate-800 mt-2">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-950 text-slate-400 uppercase font-black tracking-wider text-[10px] border-b border-slate-800">
                                <tr>
                                    <th class="py-3 px-4 w-12 text-center">#</th>
                                    <th class="py-3 px-4 w-12 text-center">Play</th>
                                    <th class="py-3 px-4">Canción & Artista</th>
                                    <th class="py-3 px-4">Momento / Fase</th>
                                    <th class="py-3 px-4">Peticionario</th>
                                    <th class="py-3 px-4 text-center">Plataformas</th>
                                    <th class="py-3 px-4 text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60 bg-slate-950/40">
                                @forelse($filteredRequests as $idx => $item)
                                    @php
                                        $searchQuery = urlencode(trim(($item->artist ? $item->artist . ' ' : '') . $item->title));
                                        $spUrl = $item->spotify_url ?: "https://open.spotify.com/search/{$searchQuery}";
                                        $appleUrl = $item->apple_music_url ?: "https://music.apple.com/es/search?term={$searchQuery}";
                                        $ytUrl = $item->youtube_url ?: "https://www.youtube.com/results?search_query={$searchQuery}";
                                        $isPlaying = $item->status === 'playing';
                                        $isPlayed = $item->status === 'played';
                                    @endphp
                                    <tr class="hover:bg-slate-900/60 transition {{ $isPlaying ? 'bg-emerald-950/40' : '' }}">
                                        <td class="py-3 px-4 font-mono text-center text-slate-400 font-bold">
                                            {{ $idx + 1 }}
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <button 
                                                type="button" 
                                                @click="handleTrackPlay({{ $item->id }}, {{ json_encode($item->title) }}, {{ json_encode($item->artist ?? '') }}, {{ json_encode($item->audio_file ?? '') }}, {{ json_encode($item->spotify_url ?? '') }}, {{ json_encode($item->apple_music_url ?? '') }})" 
                                                class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold transition mx-auto {{ $isPlaying ? 'bg-emerald-500 text-slate-950' : 'bg-slate-800 text-emerald-400 hover:bg-slate-700' }}"
                                            >
                                                <span x-text="currentId === {{ $item->id }} && isPlaying ? '⏸' : '▶'"></span>
                                            </button>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="font-bold text-white text-sm {{ $isPlayed ? 'line-through text-slate-500' : '' }}">
                                                {{ $item->title }}
                                            </div>
                                            @if($item->artist)
                                                <div class="text-slate-400 text-xs">{{ $item->artist }}</div>
                                            @endif
                                            @if($item->notes)
                                                <div class="text-[10px] text-amber-300/90 italic mt-0.5">📝 {{ $item->notes }}</div>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="px-2.5 py-1 rounded-md text-[10px] font-black uppercase tracking-wider bg-slate-900 text-cyan-400 border border-slate-800">
                                                {{ $item->moment ?: 'FIESTA' }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-slate-400 text-xs">
                                            {{ $item->requested_by ?: 'Novios' }}
                                            @if($item->likes > 0)
                                                <span class="text-amber-400 font-bold ml-1">🔥 {{ $item->likes }}</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <div class="inline-flex items-center gap-1">
                                                <a href="{{ $appleUrl }}" target="_blank" class="px-2 py-1 rounded bg-pink-950 text-pink-300 hover:bg-pink-800 hover:text-white text-[10px] font-bold border border-pink-800/60 transition">Apple</a>
                                                <a href="{{ $spUrl }}" target="_blank" class="px-2 py-1 rounded bg-emerald-950 text-emerald-400 hover:bg-emerald-800 hover:text-white text-[10px] font-bold border border-emerald-800/60 transition">Spotify</a>
                                                <a href="{{ $ytUrl }}" target="_blank" class="px-2 py-1 rounded bg-slate-800 text-slate-300 hover:text-white text-[10px] font-bold transition">YT</a>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <button 
                                                type="button" 
                                                wire:click="setStatus({{ $item->id }}, '{{ $isPlayed ? 'pending' : 'played' }}')" 
                                                class="px-2.5 py-1 rounded-lg text-xs font-bold transition {{ $isPlayed ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white' }}"
                                            >
                                                {{ $isPlayed ? '✓ Sonada' : 'Listo' }}
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-12 text-center text-slate-500">
                                            No se encontraron canciones en esta sección.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif

            </div>

        <!-- PESTAÑA: NOTAS -->
        @elseif($activeTab === 'notes')
            <div class="bg-slate-900/70 border border-slate-800 rounded-3xl p-6 shadow-lg space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-black text-white flex items-center gap-2">
                        <span>📝</span> Cuaderno de Notas del DJ & Montaje
                    </h3>
                    <button type="button" wire:click="saveNotes" class="px-4 py-1.5 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-black text-xs shadow-md transition">
                        Guardar Notas
                    </button>
                </div>
                <textarea wire:model="dj_notes" rows="12" placeholder="Escribe aquí cualquier indicación técnica..." class="w-full bg-slate-950 border border-slate-800 rounded-2xl p-4 text-sm text-slate-200 font-mono focus:ring-2 focus:ring-cyan-500"></textarea>
            </div>

        <!-- PESTAÑA: CHECKLIST -->
        @elseif($activeTab === 'checklist')
            <div class="bg-slate-900/70 border border-slate-800 rounded-3xl p-6 shadow-lg space-y-4">
                <div class="border-b border-slate-800 pb-3">
                    <h3 class="text-base font-black text-white flex items-center gap-2">
                        <span>✅</span> Checklist Técnico Previo a la Actuación
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">Marca cada punto antes de que comience el evento.</p>
                </div>

                <div class="space-y-2.5">
                    @foreach($checklist as $i => $item)
                        <div 
                            wire:click="toggleChecklist({{ $i }})" 
                            class="p-3.5 rounded-2xl border transition flex items-center gap-3 cursor-pointer {{ $item['done'] ? 'bg-emerald-950/30 border-emerald-500/40 text-emerald-200' : 'bg-slate-950/60 border-slate-800 text-slate-300 hover:border-slate-700' }}"
                        >
                            <span class="text-lg">{{ $item['done'] ? '✅' : '⬜' }}</span>
                            <span class="text-xs font-bold {{ $item['done'] ? 'line-through text-slate-400' : '' }}">{{ $item['text'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

        <!-- PESTAÑA: DOCUMENTOS -->
        @elseif($activeTab === 'documents')
            <div class="bg-slate-900/70 border border-slate-800 rounded-3xl p-6 shadow-lg space-y-4">
                <div class="border-b border-slate-800 pb-3">
                    <h3 class="text-base font-black text-white flex items-center gap-2">
                        <span>📄</span> Documentos Oficiales del Evento
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">Descarga o visualiza los PDFs de este evento en un solo clic.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <a href="{{ route('pdf.music-escaleta', $event->id) }}" target="_blank" class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 hover:border-cyan-500/60 transition group flex flex-col justify-between">
                        <div>
                            <span class="text-2xl block mb-2">📑</span>
                            <strong class="text-sm text-white group-hover:text-cyan-400 transition block">Escaleta Musical (PDF)</strong>
                            <p class="text-xs text-slate-400 mt-1">Guión completo de momentos y canciones para el DJ.</p>
                        </div>
                        <span class="text-xs font-bold text-cyan-400 mt-4 block">&darr; Descargar PDF</span>
                    </a>

                    <a href="{{ route('pdf.packing-list', $event->id) }}" target="_blank" class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 hover:border-amber-500/60 transition group flex flex-col justify-between">
                        <div>
                            <span class="text-2xl block mb-2">📦</span>
                            <strong class="text-sm text-white group-hover:text-amber-400 transition block">Hoja de Carga y Montaje</strong>
                            <p class="text-xs text-slate-400 mt-1">Equipos de sonido, luces y microfonía asignados.</p>
                        </div>
                        <span class="text-xs font-bold text-amber-400 mt-4 block">&darr; Descargar PDF</span>
                    </a>

                    @if($event->contracts->isNotEmpty())
                        <a href="{{ route('pdf.contract', $event->contracts->first()->id) }}" target="_blank" class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 hover:border-emerald-500/60 transition group flex flex-col justify-between">
                            <div>
                                <span class="text-2xl block mb-2">📜</span>
                                <strong class="text-sm text-white group-hover:text-emerald-400 transition block">Contrato de Servicios</strong>
                                <p class="text-xs text-slate-400 mt-1">Contrato firmado por el cliente con sello legal.</p>
                            </div>
                            <span class="text-xs font-bold text-emerald-400 mt-4 block">&darr; Descargar PDF</span>
                        </a>
                    @endif
                </div>
            </div>

        @endif

    </div>

    <!-- FLOATING DJ REPRODUCTOR DE AUDIO BAR (INFERIOR) -->
    <div 
        x-show="currentId !== null" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform translate-y-12"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform translate-y-12"
        class="fixed bottom-0 left-0 right-0 z-40 bg-slate-950/95 backdrop-blur-md border-t border-cyan-500/40 px-4 py-3 shadow-2xl"
        style="display: none;"
    >
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            
            <!-- TRACK INFO -->
            <div class="flex items-center gap-3 min-w-[200px] max-w-sm">
                <div class="w-10 h-10 rounded-xl bg-cyan-950 border border-cyan-500/50 flex items-center justify-center text-cyan-400 shrink-0 relative overflow-hidden">
                    <span class="text-xl" :class="isPlaying ? 'animate-spin' : ''" style="animation-duration: 4s;">💿</span>
                </div>
                <div class="truncate">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-black text-white truncate" x-text="currentTitle"></span>
                        <span class="w-2 h-2 rounded-full bg-emerald-400 shrink-0" :class="isPlaying ? 'animate-ping' : 'opacity-40'"></span>
                    </div>
                    <p class="text-[11px] text-slate-400 truncate flex items-center gap-1">
                        <span x-text="currentArtist || 'Pista de Audio'"></span>
                        <span class="text-[10px] text-red-400 font-mono" x-show="playbackSource === 'youtube'">[Canción Completa]</span>
                        <span class="text-[10px] text-pink-400 font-mono" x-show="playbackSource === 'apple_music'">[Apple Music]</span>
                        <span class="text-[10px] text-cyan-400 font-mono" x-show="playbackSource === 'spotify'">[Spotify SDK]</span>
                        <span class="text-[10px] text-emerald-400 font-mono" x-show="playbackSource === 'local_mp3'">[Cloud Drive / Local]</span>
                        <span class="text-[10px] text-amber-400 font-mono" x-show="playbackSource === 'preview'">[Preview 30s]</span>
                    </p>
                </div>
            </div>

            <!-- CONTROLES PRINCIPALES Y BARRA DE PROGRESO -->
            <div class="flex-1 max-w-xl flex flex-col items-center gap-1.5">
                <div class="flex items-center gap-4">
                    <button 
                        type="button"
                        @click="togglePlayPause()" 
                        class="w-10 h-10 rounded-full bg-cyan-500 hover:bg-cyan-400 text-slate-950 flex items-center justify-center font-black text-base shadow-lg shadow-cyan-500/30 transition transform hover:scale-105 cursor-pointer"
                    >
                        <span x-text="isPlaying ? '⏸' : '▶'"></span>
                    </button>
                    <button 
                        type="button"
                        @click="stopAndMarkPlayed()" 
                        title="Marcar como Terminada / Siguiente"
                        class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-emerald-600 text-slate-200 hover:text-white text-xs font-bold border border-slate-700 transition cursor-pointer"
                    >
                        ✓ Marcar Lista
                    </button>
                </div>

                <!-- SEEK BAR & TIMERS -->
                <div class="w-full flex items-center gap-2 text-[10px] text-slate-400 font-mono">
                    <span x-text="formatTime(currentTime)">00:00</span>
                    <div class="flex-1 bg-slate-800 rounded-full h-1.5 cursor-pointer relative overflow-hidden" @click="seekByEvent($event)">
                        <div class="bg-gradient-to-r from-pink-500 via-purple-500 to-cyan-400 h-full rounded-full transition-all" :style="'width: ' + progress + '%'"></div>
                    </div>
                    <span x-text="formatTime(duration)">00:00</span>
                </div>
            </div>

            <!-- VOLUMEN Y CONTROLES -->
            <div class="flex items-center gap-3 justify-end">
                <div class="flex items-center gap-1.5 bg-slate-900 px-2.5 py-1 rounded-xl border border-slate-800">
                    <span class="text-xs cursor-pointer" @click="toggleMute()" x-text="isMuted ? '🔇' : (volume > 0.5 ? '🔊' : '🔉')"></span>
                    <input type="range" min="0" max="1" step="0.05" x-model="volume" @input="updateVolume()" class="w-16 sm:w-20 h-1 bg-slate-700 rounded-lg appearance-none cursor-pointer accent-cyan-400">
                </div>

                <button 
                    type="button" 
                    @click="closePlayer()" 
                    class="text-slate-400 hover:text-white text-lg p-1 cursor-pointer"
                    title="Cerrar reproductor"
                >
                    &times;
                </button>
            </div>

        </div>
    </div>

    <!-- CONTENEDOR OCULTO PARA EL REPRODUCTOR COMPLETO YOUTUBE -->
    <div id="youtube-audio-frame" style="position: fixed; bottom: -9999px; left: -9999px; width: 1px; height: 1px; opacity: 0; pointer-events: none;"></div>

</div>

<!-- CARGA DE SDKs OFICIALES DE YOUTUBE, SPOTIFY & APPLE MUSIC -->
<script src="https://www.youtube.com/iframe_api"></script>
<script src="https://sdk.scdn.co/spotify-player.js"></script>
<script src="https://js-cdn.music.apple.com/musickit/v3/musickit.js" async></script>

<script>
function djAudioPlayer() {
    return {
        audio: null,
        youtubePlayer: null,
        youtubeReady: false,
        youtubeTimer: null,

        spotifyPlayer: null,
        spotifyDeviceId: null,
        spotifyToken: null,
        spotifyReady: false,

        musicKit: null,
        appleMusicReady: false,
        appleDevToken: null,

        playbackSource: 'youtube', // 'youtube', 'apple_music', 'spotify', 'local_mp3', 'preview'

        currentId: null,
        currentTitle: '',
        currentArtist: '',
        isPlaying: false,
        isPaused: false,
        currentTime: 0,
        duration: 0,
        progress: 0,
        volume: 0.9,
        isMuted: false,
        loading: false,

        async initPlayer() {
            // 1. Inicializar HTML5 Audio para archivos MP3 locales
            this.audio = new Audio();
            this.audio.volume = this.volume;

            this.audio.addEventListener('timeupdate', () => {
                if (this.playbackSource === 'local_mp3' || this.playbackSource === 'preview') {
                    this.currentTime = Math.floor(this.audio.currentTime || 0);
                    this.duration = Math.floor(this.audio.duration || 0);
                    if (this.duration > 0) {
                        this.progress = (this.currentTime / this.duration) * 100;
                    }
                }
            });

            this.audio.addEventListener('ended', () => {
                if (this.playbackSource === 'local_mp3' || this.playbackSource === 'preview') {
                    this.isPlaying = false;
                    this.isPaused = false;
                    this.currentTime = 0;
                    this.progress = 0;
                    if (this.currentId) {
                        this.$wire.setStatus(this.currentId, 'played');
                    }
                }
            });

            this.audio.addEventListener('play', () => {
                if (this.playbackSource === 'local_mp3' || this.playbackSource === 'preview') {
                    this.isPlaying = true;
                    this.isPaused = false;
                    this.loading = false;
                }
            });

            this.audio.addEventListener('pause', () => {
                if (this.playbackSource === 'local_mp3' || this.playbackSource === 'preview') {
                    this.isPlaying = false;
                    this.isPaused = true;
                }
            });

            // 2. Inicializar YouTube IFrame Player para canciones completas 100%
            this.initYouTubeSdk();

            // 3. Inicializar Apple Music (MusicKit JS)
            await this.initAppleMusicSdk();

            // 4. Inicializar Spotify (Web Playback SDK)
            await this.initSpotifySdk();
        },

        initYouTubeSdk() {
            const setupYT = () => {
                if (window.YT && window.YT.Player) {
                    try {
                        this.youtubePlayer = new YT.Player('youtube-audio-frame', {
                            height: '1',
                            width: '1',
                            playerVars: {
                                playsinline: 1,
                                controls: 0,
                                disablekb: 1,
                                origin: window.location.origin
                            },
                            events: {
                                onReady: () => {
                                    this.youtubeReady = true;
                                    if (this.youtubePlayer && this.youtubePlayer.setVolume) {
                                        this.youtubePlayer.setVolume(this.volume * 100);
                                    }
                                },
                                onStateChange: (event) => {
                                    if (this.playbackSource === 'youtube') {
                                        if (event.data === YT.PlayerState.PLAYING) {
                                            this.isPlaying = true;
                                            this.isPaused = false;
                                            this.loading = false;
                                            this.startYouTubeTimer();
                                        } else if (event.data === YT.PlayerState.PAUSED) {
                                            this.isPlaying = false;
                                            this.isPaused = true;
                                            this.stopYouTubeTimer();
                                        } else if (event.data === YT.PlayerState.ENDED) {
                                            this.isPlaying = false;
                                            this.isPaused = false;
                                            this.stopYouTubeTimer();
                                            this.currentTime = 0;
                                            this.progress = 0;
                                            if (this.currentId) {
                                                this.$wire.setStatus(this.currentId, 'played');
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    } catch (e) {}
                }
            };

            if (window.YT && window.YT.Player) {
                setupYT();
            } else {
                window.onYouTubeIframeAPIReady = setupYT;
            }
        },

        startYouTubeTimer() {
            this.stopYouTubeTimer();
            this.youtubeTimer = setInterval(() => {
                if (this.playbackSource === 'youtube' && this.youtubePlayer && this.youtubePlayer.getCurrentTime) {
                    try {
                        this.currentTime = Math.floor(this.youtubePlayer.getCurrentTime() || 0);
                        this.duration = Math.floor(this.youtubePlayer.getDuration() || 0);
                        if (this.duration > 0) {
                            this.progress = (this.currentTime / this.duration) * 100;
                        }
                    } catch (e) {}
                }
            }, 500);
        },

        stopYouTubeTimer() {
            if (this.youtubeTimer) {
                clearInterval(this.youtubeTimer);
                this.youtubeTimer = null;
            }
        },

        async initAppleMusicSdk() {
            try {
                const res = await fetch('/api/apple-music/token');
                if (res.ok) {
                    const data = await res.json();
                    if (data.success && data.developer_token) {
                        this.appleDevToken = data.developer_token;

                        const configureMusicKit = async () => {
                            if (window.MusicKit) {
                                try {
                                    await MusicKit.configure({
                                        developerToken: data.developer_token,
                                        app: {
                                            name: 'Eventos Musicales Cabina',
                                            build: '2026.1.0'
                                        }
                                    });
                                    this.musicKit = MusicKit.getInstance();
                                    this.appleMusicReady = true;

                                    this.musicKit.addEventListener('playbackStateDidChange', () => {
                                        if (this.playbackSource === 'apple_music') {
                                            this.isPlaying = this.musicKit.isPlaying;
                                            this.isPaused = !this.musicKit.isPlaying;
                                        }
                                    });

                                    this.musicKit.addEventListener('playbackTimeDidChange', () => {
                                        if (this.playbackSource === 'apple_music') {
                                            this.currentTime = Math.floor(this.musicKit.currentPlaybackTime || 0);
                                            this.duration = Math.floor(this.musicKit.currentPlaybackDuration || 0);
                                            if (this.duration > 0) {
                                                this.progress = (this.currentTime / this.duration) * 100;
                                            }
                                        }
                                    });
                                } catch (e) {}
                            }
                        };

                        if (window.MusicKit) {
                            configureMusicKit();
                        } else {
                            document.addEventListener('musickitloaded', configureMusicKit);
                        }
                    }
                }
            } catch (e) {}
        },

        async initSpotifySdk() {
            try {
                const res = await fetch('/api/spotify/token');
                if (res.ok) {
                    const data = await res.json();
                    if (data.success && data.access_token) {
                        this.spotifyToken = data.access_token;

                        window.onSpotifyWebPlaybackSDKReady = () => {
                            this.setupSpotifyPlayer(this.spotifyToken);
                        };

                        if (window.Spotify && window.Spotify.Player) {
                            this.setupSpotifyPlayer(this.spotifyToken);
                        }
                    }
                }
            } catch (e) {}
        },

        setupSpotifyPlayer(token) {
            if (this.spotifyPlayer) return;

            const player = new Spotify.Player({
                name: 'Eventos Musicales - Cabina DJ',
                getOAuthToken: async cb => {
                    try {
                        const res = await fetch('/api/spotify/token');
                        const data = await res.json();
                        cb(data.access_token);
                    } catch (e) {
                        cb(token);
                    }
                },
                volume: this.volume
            });

            player.addListener('ready', ({ device_id }) => {
                this.spotifyDeviceId = device_id;
                this.spotifyReady = true;
            });

            player.addListener('not_ready', () => {
                this.spotifyReady = false;
            });

            player.addListener('player_state_changed', state => {
                if (!state) return;

                if (this.playbackSource === 'spotify') {
                    this.isPlaying = !state.paused;
                    this.isPaused = state.paused;
                    this.currentTime = Math.floor(state.position / 1000);
                    this.duration = Math.floor(state.duration / 1000);
                    if (this.duration > 0) {
                        this.progress = (state.position / state.duration) * 100;
                    }

                    if (state.position === 0 && state.paused && this.currentTime > 0) {
                        if (this.currentId) {
                            this.$wire.setStatus(this.currentId, 'played');
                        }
                    }
                }
            });

            player.connect();
            this.spotifyPlayer = player;
        },

        async handleTrackPlay(id, title, artist, audioFile, spotifyUrl, appleMusicUrl) {
            if (this.currentId === id) {
                this.togglePlayPause();
                return;
            }

            await this.playSong(id, title, artist, audioFile, spotifyUrl, appleMusicUrl);
        },

        async playSong(id, title, artist, audioFile, spotifyUrl, appleMusicUrl) {
            this.stopCurrent();

            this.currentId = id;
            this.currentTitle = title;
            this.currentArtist = artist;
            this.loading = true;
            this.progress = 0;
            this.currentTime = 0;

            // 1. Almacenamiento Nube / Local (Google Drive, Dropbox, Servidor Propio)
            if (audioFile && audioFile.trim() !== '') {
                let streamUrl = audioFile.trim();
                if (streamUrl.includes('drive.google.com')) {
                    const driveMatch = streamUrl.match(/\/d\/([a-zA-Z0-9_-]+)/) || streamUrl.match(/id=([a-zA-Z0-9_-]+)/);
                    if (driveMatch && driveMatch[1]) {
                        streamUrl = `https://drive.google.com/uc?export=download&id=${driveMatch[1]}`;
                    }
                } else if (streamUrl.includes('dropbox.com')) {
                    streamUrl = streamUrl.replace('dl=0', 'dl=1');
                } else if (!streamUrl.startsWith('http')) {
                    streamUrl = '/storage/' + streamUrl;
                }

                this.playbackSource = 'local_mp3';
                this.audio.src = streamUrl;
                this.audio.load();
                try {
                    await this.audio.play();
                    this.isPlaying = true;
                    this.isPaused = false;
                    this.loading = false;
                    this.$wire.setStatus(id, 'playing');
                    return;
                } catch (e) {}
            }

            // 2. Apple Music (MusicKit Full Streaming)
            if (this.appleMusicReady && this.musicKit) {
                try {
                    const q = encodeURIComponent(((artist || '') + ' ' + title).trim());
                    const searchRes = await this.musicKit.api.music(`/v1/catalog/es/search?term=${q}&types=songs&limit=1`);
                    if (searchRes.data && searchRes.data.results && searchRes.data.results.songs && searchRes.data.results.songs.data.length > 0) {
                        const songId = searchRes.data.results.songs.data[0].id;
                        await this.musicKit.setQueue({ song: songId });
                        await this.musicKit.play();
                        this.playbackSource = 'apple_music';
                        this.isPlaying = true;
                        this.isPaused = false;
                        this.loading = false;
                        this.$wire.setStatus(id, 'playing');
                        return;
                    }
                } catch (e) {}
            }

            // 3. Spotify Web Playback SDK
            if (this.spotifyReady && this.spotifyDeviceId) {
                let trackUri = null;
                if (spotifyUrl && spotifyUrl.includes('/track/')) {
                    const match = spotifyUrl.match(/track\/([a-zA-Z0-9]+)/);
                    if (match) {
                        trackUri = 'spotify:track:' + match[1];
                    }
                }

                if (!trackUri) {
                    try {
                        const q = encodeURIComponent(((artist || '') + ' ' + title).trim());
                        const res = await fetch(`https://api.spotify.com/v1/search?q=${q}&type=track&limit=1`, {
                            headers: { 'Authorization': `Bearer ${this.spotifyToken}` }
                        });
                        if (res.ok) {
                            const data = await res.json();
                            if (data.tracks && data.tracks.items.length > 0) {
                                trackUri = data.tracks.items[0].uri;
                            }
                        }
                    } catch (e) {}
                }

                if (trackUri) {
                    try {
                        const playRes = await fetch(`https://api.spotify.com/v1/me/player/play?device_id=${this.spotifyDeviceId}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Authorization': `Bearer ${this.spotifyToken}`
                            },
                            body: JSON.stringify({ uris: [trackUri] })
                        });

                        if (playRes.status === 204 || playRes.ok) {
                            this.playbackSource = 'spotify';
                            this.isPlaying = true;
                            this.isPaused = false;
                            this.loading = false;
                            this.$wire.setStatus(id, 'playing');
                            return;
                        }
                    } catch (e) {}
                }
            }

            // 4. Reproductor Completo Universal (YouTube Full Track)
            try {
                const searchQ = encodeURIComponent(((artist || '') + ' ' + title).trim());
                const ytRes = await fetch(`/api/music/youtube-id?q=${searchQ}`);
                if (ytRes.ok) {
                    const ytData = await ytRes.json();
                    if (ytData.success && ytData.video_id) {
                        this.playbackSource = 'youtube';
                        if (this.youtubePlayer && this.youtubePlayer.loadVideoById) {
                            this.youtubePlayer.loadVideoById(ytData.video_id);
                            this.youtubePlayer.playVideo();
                            this.isPlaying = true;
                            this.isPaused = false;
                            this.loading = false;
                            this.$wire.setStatus(id, 'playing');
                            this.startYouTubeTimer();
                            return;
                        }
                    }
                }
            } catch (e) {}

            // 5. Preescucha gratuita oficial de Apple Music / iTunes (Fallback)
            this.playbackSource = 'preview';
            let previewUrl = null;
            try {
                const query = encodeURIComponent(((artist || '') + ' ' + title).trim());
                const res = await fetch(`https://itunes.apple.com/search?term=${query}&media=music&entity=song&country=es&limit=1`);
                if (res.ok) {
                    const data = await res.json();
                    if (data.results && data.results.length > 0 && data.results[0].previewUrl) {
                        previewUrl = data.results[0].previewUrl;
                    }
                }
            } catch (err) {}

            if (previewUrl) {
                this.audio.src = previewUrl;
                this.audio.load();
                try {
                    await this.audio.play();
                    this.isPlaying = true;
                    this.isPaused = false;
                    this.loading = false;
                    this.$wire.setStatus(id, 'playing');
                    return;
                } catch (e) {}
            }

            // 6. Fallback final
            this.loading = false;
            this.playFallbackBeep();
            this.isPlaying = true;
            this.$wire.setStatus(id, 'playing');
        },

        togglePlayPause() {
            if (!this.currentId) return;

            if (this.playbackSource === 'youtube' && this.youtubePlayer && this.youtubePlayer.getPlayerState) {
                const state = this.youtubePlayer.getPlayerState();
                if (state === YT.PlayerState.PLAYING) {
                    this.youtubePlayer.pauseVideo();
                    this.isPlaying = false;
                    this.isPaused = true;
                } else {
                    this.youtubePlayer.playVideo();
                    this.isPlaying = true;
                    this.isPaused = false;
                }
            } else if (this.playbackSource === 'apple_music' && this.musicKit) {
                if (this.isPlaying) {
                    this.musicKit.pause();
                } else {
                    this.musicKit.play();
                }
            } else if (this.playbackSource === 'spotify' && this.spotifyPlayer) {
                this.spotifyPlayer.togglePlay();
            } else if (this.audio) {
                if (this.isPlaying) {
                    this.audio.pause();
                } else {
                    this.audio.play().catch(() => {});
                }
            }
        },

        stopCurrent() {
            this.stopYouTubeTimer();
            if (this.youtubePlayer && this.youtubePlayer.stopVideo) {
                try { this.youtubePlayer.stopVideo(); } catch (e) {}
            }
            if (this.audio) {
                this.audio.pause();
                this.audio.currentTime = 0;
            }
            if (this.musicKit && this.playbackSource === 'apple_music') {
                try { this.musicKit.pause(); } catch (e) {}
            }
            if (this.spotifyPlayer && this.playbackSource === 'spotify') {
                try { this.spotifyPlayer.pause(); } catch (e) {}
            }
            this.isPlaying = false;
            this.isPaused = false;
        },

        stopAndMarkPlayed() {
            this.stopCurrent();
            if (this.currentId && this.currentId !== 999999) {
                this.$wire.setStatus(this.currentId, 'played');
            }
            this.currentId = null;
        },

        closePlayer() {
            this.stopCurrent();
            this.currentId = null;
        },

        updateVolume() {
            if (this.youtubePlayer && this.youtubePlayer.setVolume) {
                try { this.youtubePlayer.setVolume(this.volume * 100); } catch (e) {}
            }
            if (this.audio) {
                this.audio.volume = this.volume;
                this.isMuted = this.volume === 0;
            }
            if (this.musicKit) {
                this.musicKit.volume = this.volume;
            }
            if (this.spotifyPlayer) {
                this.spotifyPlayer.setVolume(this.volume);
            }
        },

        toggleMute() {
            this.isMuted = !this.isMuted;
            if (this.youtubePlayer && this.youtubePlayer.mute) {
                try { this.isMuted ? this.youtubePlayer.mute() : this.youtubePlayer.unMute(); } catch (e) {}
            }
            if (this.audio) {
                this.audio.muted = this.isMuted;
            }
            if (this.musicKit) {
                this.musicKit.volume = this.isMuted ? 0 : this.volume;
            }
            if (this.spotifyPlayer) {
                this.spotifyPlayer.setVolume(this.isMuted ? 0 : this.volume);
            }
        },

        seekByEvent(e) {
            if (!this.duration) return;
            const rect = e.currentTarget.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const width = rect.width;
            const seekPct = Math.max(0, Math.min(1, clickX / width));
            const targetSeconds = Math.floor(seekPct * this.duration);

            if (this.playbackSource === 'youtube' && this.youtubePlayer && this.youtubePlayer.seekTo) {
                this.youtubePlayer.seekTo(targetSeconds, true);
                this.currentTime = targetSeconds;
            } else if (this.playbackSource === 'apple_music' && this.musicKit) {
                this.musicKit.seekToTime(targetSeconds);
            } else if (this.playbackSource === 'spotify' && this.spotifyPlayer) {
                this.spotifyPlayer.seek(targetSeconds * 1000);
            } else if (this.audio) {
                this.audio.currentTime = targetSeconds;
            }
        },

        formatTime(seconds) {
            if (isNaN(seconds) || seconds < 0) return '00:00';
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        },

        playFallbackBeep() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(440, ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.3);
                gain.gain.setValueAtTime(0.1, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.3);
            } catch (e) {}
        }
    };
}
</script>
