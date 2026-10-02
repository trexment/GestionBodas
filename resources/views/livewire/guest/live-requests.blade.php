<div class="max-w-xl mx-auto py-6 px-4" wire:poll.5s>
    
    <!-- CABECERA FIESTA -->
    <div class="text-center mb-6">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-tr from-pink-500 via-purple-500 to-indigo-600 flex items-center justify-center text-2xl shadow-xl shadow-purple-500/30 mb-3 animate-bounce">
            🎵
        </div>
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-purple-500/20 border border-purple-500/40 text-purple-300 text-xs font-bold mb-2">
            <span>🔥</span> Peticiones en Vivo para el DJ
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
            {{ $event->name }}
        </h1>
        <p class="text-xs text-slate-400 mt-1">¿Qué temazo no puede faltar en la fiesta? ¡Pídelo directamente a la cabina!</p>
    </div>

    <!-- MENSAJE DE ÉXITO -->
    @if($successMessage)
        <div class="bg-emerald-500/20 border border-emerald-500/50 text-emerald-300 p-4 rounded-2xl text-xs font-bold text-center mb-6 shadow-lg shadow-emerald-500/10 flex items-center justify-center gap-2">
            <span>🎉</span> {{ $successMessage }}
        </div>
    @endif

    <!-- ALERTA DE LISTA NEGRA -->
    @if($blacklistWarning)
        <div class="bg-rose-500/20 border border-rose-500/50 text-rose-300 p-4 rounded-2xl text-xs font-bold text-center mb-6 shadow-lg shadow-rose-500/10 flex items-center justify-center gap-2">
            <span>🚫</span> {{ $blacklistWarning }}
        </div>
    @endif

    <!-- FORMULARIO DE PETICIÓN -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-2xl space-y-4" x-data="songSearch()">
        
        <!-- BUSCADOR MUSICAL ITUNES -->
        <div class="relative">
            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                1. Busca la canción o artista *
            </label>
            <input type="text" x-model.debounce.400ms="query" placeholder="ej. Nochentera, Quevedo, Estopa..." class="w-full bg-slate-950 border border-slate-700/80 rounded-2xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-purple-500">
            
            <div x-show="loading" class="text-[11px] text-purple-400 mt-1.5 font-medium flex items-center gap-1.5">
                <span class="animate-spin">⏳</span> Buscando temazos en Apple Music...
            </div>

            <!-- RESULTADOS DESPLEGABLES -->
            <div x-show="showDropdown" @click.away="showDropdown = false" class="absolute z-30 w-full mt-1.5 bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl max-h-60 overflow-y-auto" style="display: none;">
                <template x-for="song in results" :key="song.trackId">
                    <div @click="selectSong(song)" class="px-4 py-2.5 hover:bg-purple-900/40 cursor-pointer flex items-center gap-3 border-b border-slate-800/80 transition">
                        <img :src="song.artworkUrl60 || song.artworkUrl30" class="w-10 h-10 rounded-xl object-cover" alt="cover">
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-bold text-white truncate" x-text="song.trackName"></div>
                            <div class="text-xs text-purple-300 truncate" x-text="song.artistName"></div>
                        </div>
                    </div>
                </template>
                <template x-if="query.trim().length > 1">
                    <div @click="selectCustom()" class="px-4 py-2.5 bg-purple-950/80 hover:bg-purple-900 cursor-pointer flex items-center justify-between gap-2 border-t border-slate-700 transition">
                        <div class="text-xs text-purple-300 font-bold truncate">
                            <span>➕</span> Usar lo que he escrito: "<span x-text="query" class="underline"></span>"
                        </div>
                        <span class="text-[10px] bg-purple-500/30 text-purple-200 px-2 py-0.5 rounded-full flex-shrink-0">Personalizada</span>
                    </div>
                </template>
            </div>
        </div>

        @if($song_title)
            <div class="p-3 bg-purple-950/50 border border-purple-500/40 rounded-xl flex items-center justify-between">
                <div>
                    <span class="text-[10px] uppercase font-bold text-purple-400 block">Canción Seleccionada</span>
                    <strong class="text-sm text-white font-extrabold">{{ $song_title }}</strong>
                    @if($song_artist) <span class="text-xs text-purple-300"> - {{ $song_artist }}</span> @endif
                </div>
                <button type="button" wire:click="$set('song_title', '')" class="text-xs text-slate-400 hover:text-white">Cambiar</button>
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">
                    2. Tu nombre o mesa (Opcional)
                </label>
                <input type="text" wire:model="guest_name" placeholder="ej. Mesa 3, Laura, Los amigos" class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">
                    3. Dedicatoria (Opcional)
                </label>
                <input type="text" wire:model="guest_note" placeholder="ej. Para que la bailen los novios" class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
        </div>

        <div class="pt-2">
            <button type="button" wire:click="sendRequest" class="w-full py-3.5 px-6 rounded-2xl bg-gradient-to-r from-purple-600 via-pink-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-extrabold text-sm shadow-xl shadow-purple-600/30 transition transform hover:-translate-y-0.5 flex items-center justify-center gap-2 cursor-pointer">
                <span>🚀</span> Enviar Canción a Cabina
            </button>
        </div>

    </div>

    <!-- LISTA DE PETICIONES DE LA FIESTA -->
    <div class="mt-8 space-y-3">
        <div class="flex items-center justify-between px-1">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <span>🔥</span> Temazos pedidos por los invitados ({{ $guestRequests->count() }})
            </h3>
            <span class="text-[11px] text-slate-400">¡Vota tus favoritas!</span>
        </div>

        @forelse($guestRequests as $gr)
            @php
                $isPlaying = $gr->status === 'playing';
                $isPlayed = $gr->status === 'played';
            @endphp
            <div class="bg-slate-900/80 border rounded-2xl p-4 flex items-center justify-between gap-3 transition {{ $isPlaying ? 'border-emerald-500/80 bg-emerald-950/30 shadow-md shadow-emerald-500/10' : ($isPlayed ? 'border-slate-800 opacity-60' : 'border-slate-800 hover:border-slate-700') }}">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <strong class="text-sm font-extrabold text-white truncate">{{ $gr->title }}</strong>
                        @if($isPlaying)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 animate-pulse">
                                ▶ Sonando
                            </span>
                        @elseif($isPlayed)
                            <span class="text-[10px] text-slate-500 font-bold">✓ Ya sonó</span>
                        @endif
                    </div>
                    @if($gr->artist)
                        <div class="text-xs text-purple-300 truncate">{{ $gr->artist }}</div>
                    @endif
                    <div class="text-[11px] text-slate-400 mt-1 flex items-center gap-2">
                        <span>👤 {{ $gr->guest_name ?: 'Invitado' }}</span>
                        @if($gr->guest_note)
                            <span>&bull;</span>
                            <span class="italic text-amber-300/80">"{{ $gr->guest_note }}"</span>
                        @endif
                    </div>
                </div>

                <!-- BOTÓN DE LIKE / VOTO -->
                <button type="button" wire:click="likeSong({{ $gr->id }})" class="flex-shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-purple-500/10 hover:bg-purple-500/30 text-pink-400 border border-purple-500/30 font-bold text-xs transition cursor-pointer active:scale-90" title="Votar canción">
                    <span>❤️</span>
                    <span>{{ $gr->likes }}</span>
                </button>
            </div>
        @empty
            <div class="bg-slate-900/40 rounded-2xl p-8 text-center text-slate-500 text-xs border border-slate-800">
                ¡Sé el primero en pedir un temazo para la pista de baile!
            </div>
        @endforelse
    </div>

    <script>
        function songSearch() {
            return {
                query: '',
                results: [],
                loading: false,
                showDropdown: false,
                init() {
                    this.$watch('query', (val) => {
                        if (val.length > 1) {
                            this.fetchSongs(val);
                        } else {
                            this.results = [];
                            this.showDropdown = false;
                        }
                    });
                },
                fetchSongs(term) {
                    this.loading = true;
                    fetch(`https://itunes.apple.com/search?term=${encodeURIComponent(term)}&entity=song&country=es&limit=25`)
                        .then(res => res.json())
                        .then(data => {
                            this.results = data.results || [];
                            this.showDropdown = true;
                            this.loading = false;
                        })
                        .catch(() => {
                            this.loading = false;
                            this.showDropdown = true;
                        });
                },
                selectSong(song) {
                    this.$wire.selectSong(song.trackName, song.artistName);
                    this.query = `${song.artistName} - ${song.trackName}`;
                    this.showDropdown = false;
                },
                selectCustom() {
                    const text = this.query.trim();
                    if (!text) return;
                    let title = text;
                    let artist = '';
                    if (text.includes('-')) {
                        const parts = text.split('-');
                        artist = parts[0].trim();
                        title = parts.slice(1).join('-').trim();
                    }
                    this.$wire.selectSong(title, artist);
                    this.query = text;
                    this.showDropdown = false;
                }
            }
        }
    </script>
</div>
