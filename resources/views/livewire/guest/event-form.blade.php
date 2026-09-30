<div class="max-w-2xl mx-auto py-6 px-4">
    
    <!-- CABECERA -->
    <div class="text-center mb-8">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center text-2xl text-white shadow-xl shadow-indigo-500/20 mb-3">
            🎵
        </div>
        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
            {{ \App\Models\Setting::getCompanyName('Eventos Musicales') }}
        </h1>
        <div class="flex items-center justify-center gap-2 mt-1">
            <span class="text-base">{{ $event->event_type_icon }}</span>
            <p class="text-slate-700 dark:text-slate-300 text-sm font-bold">Cuestionario Musical: <strong>{{ $event->name }}</strong></p>
        </div>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            @if($event->is_wedding)
                Rellena las canciones que no pueden faltar en vuestro gran día.
            @else
                Indícanos los estilos, canciones imprescindibles y momentos especiales de tu {{ mb_strtolower($event->event_type_label) }}.
            @endif
        </p>
    </div>

    @if($event->is_dossier_completed)
        <div class="bg-slate-100 dark:bg-slate-900 border-l-4 border-slate-500 text-slate-700 dark:text-slate-300 p-6 rounded-2xl text-center shadow-sm">
            <svg class="w-12 h-12 mx-auto mb-2 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            <p class="font-extrabold text-lg text-slate-900 dark:text-white">Cuestionario Cerrado</p>
            <p class="text-sm mt-2 leading-relaxed">Toda la información musical ya ha sido procesada y confirmada con el equipo técnico. Si necesitas hacer algún cambio urgente de última hora, por favor contáctanos directamente por WhatsApp o teléfono.</p>
        </div>
    @elseif($submitted)
        <div class="bg-emerald-50 dark:bg-emerald-950/40 border-l-4 border-emerald-500 text-emerald-900 dark:text-emerald-200 p-6 rounded-2xl text-center shadow-sm">
            <svg class="w-14 h-14 mx-auto mb-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <p class="font-black text-xl text-emerald-950 dark:text-white">¡Cuestionario Guardado con Éxito!</p>
            <p class="text-sm mt-2 leading-relaxed text-emerald-800 dark:text-emerald-300">Hemos recibido vuestras peticiones y preferencias musicales. Nuestro DJ ya tiene vuestras canciones preparadas. ¡Nos vemos en la pista de baile!</p>
        </div>
    @else
        @if($hasExistingData)
            <div class="bg-amber-50 dark:bg-amber-950/50 border border-amber-300 dark:border-amber-700/70 text-amber-900 dark:text-amber-200 p-4 rounded-2xl shadow-sm mb-6 flex items-start gap-3">
                <span class="text-2xl flex-shrink-0">✏️</span>
                <div class="text-xs leading-relaxed">
                    <strong class="font-bold text-sm block text-amber-950 dark:text-amber-100 mb-0.5">Formulario Reabierto para Edición</strong>
                    Hemos precargado todas las canciones y notas que enviasteis anteriormente. Podéis revisar lo que pusisteis, realizar cualquier cambio o añadir nuevos momentos, y pulsar en <strong>"Guardar y Enviar Preferencias"</strong> para confirmarlo.
                </div>
            </div>
        @endif

        <form wire:submit.prevent="submitForm" class="space-y-6">
            
            <script>
                function singleSongPicker(targetWireProperty) {
                    return {
                        searchQuery: '',
                        results: [],
                        loading: false,
                        showDropdown: false,
                        timer: null,
                        onInput() {
                            clearTimeout(this.timer);
                            if (this.searchQuery.trim().length > 2) {
                                this.timer = setTimeout(() => {
                                    this.fetchSongs(this.searchQuery.trim());
                                }, 300);
                            } else {
                                this.results = [];
                                this.showDropdown = false;
                            }
                        },
                        fetchSongs(term) {
                            this.loading = true;
                            fetch(`https://itunes.apple.com/search?term=${encodeURIComponent(term)}&entity=song&limit=6`)
                                .then(res => res.json())
                                .then(data => {
                                    this.results = data.results || [];
                                    this.showDropdown = this.results.length > 0;
                                    this.loading = false;
                                })
                                .catch(() => {
                                    this.loading = false;
                                });
                        },
                        selectSong(song) {
                            const formatted = `${song.artistName} - ${song.trackName}`;
                            this.$wire.set(targetWireProperty, formatted);
                            this.searchQuery = '';
                            this.showDropdown = false;
                        }
                    }
                }

                function partyListBuilder(targetWireProperty) {
                    return {
                        songs: [],
                        searchQuery: '',
                        results: [],
                        loading: false,
                        showDropdown: false,
                        manualInput: '',
                        timer: null,
                        init() {
                            const raw = this.$wire.get(targetWireProperty) || '';
                            if (raw.trim()) {
                                this.songs = raw.split('\n')
                                    .map(s => s.trim().replace(/^[-*•]\s*/, ''))
                                    .filter(s => s.length > 0);
                            }
                        },
                        sync() {
                            this.$wire.set(targetWireProperty, this.songs.join('\n'));
                        },
                        onSearch() {
                            clearTimeout(this.timer);
                            if (this.searchQuery.trim().length > 2) {
                                this.timer = setTimeout(() => {
                                    this.fetchSongs(this.searchQuery.trim());
                                }, 300);
                            } else {
                                this.results = [];
                                this.showDropdown = false;
                            }
                        },
                        fetchSongs(term) {
                            this.loading = true;
                            fetch(`https://itunes.apple.com/search?term=${encodeURIComponent(term)}&entity=song&limit=6`)
                                .then(res => res.json())
                                .then(data => {
                                    this.results = data.results || [];
                                    this.showDropdown = this.results.length > 0;
                                    this.loading = false;
                                })
                                .catch(() => {
                                    this.loading = false;
                                });
                        },
                        addFromSearch(song) {
                            const formatted = `${song.artistName} - ${song.trackName}`;
                            if (!this.songs.includes(formatted)) {
                                this.songs.push(formatted);
                                this.sync();
                            }
                            this.searchQuery = '';
                            this.showDropdown = false;
                            this.results = [];
                        },
                        addManual() {
                            const val = this.manualInput.trim();
                            if (val && !this.songs.includes(val)) {
                                this.songs.push(val);
                                this.sync();
                            }
                            this.manualInput = '';
                        },
                        remove(index) {
                            this.songs.splice(index, 1);
                            this.sync();
                        }
                    }
                }
            </script>

            @if($event->is_wedding)
                <!-- ==================== CUESTIONARIO COMPLETO PARA BODAS ==================== -->

                <!-- SECCIÓN 1: CEREMONIA -->
                <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-3">
                    <h3 class="text-base font-black text-indigo-900 dark:text-indigo-300 border-b border-slate-100 dark:border-slate-800 pb-2.5 flex items-center gap-2">
                        <span>💍 1. Ceremonia (Opcional)</span>
                    </h3>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Canciones para la Ceremonia (Entradas, Anillos, Firmas, Salida)</label>
                        <textarea wire:model="ceremony_songs" rows="3" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 p-3 leading-relaxed" placeholder="Ej: Entrada Novio: Coldplay - Viva la Vida / Entrada Novia: Canon de Pachelbel / Salida: Bruno Mars - Marry You"></textarea>
                    </div>
                </div>

                <!-- SECCIÓN 2: CÓCTEL -->
                <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-3">
                    <h3 class="text-base font-black text-indigo-900 dark:text-indigo-300 border-b border-slate-100 dark:border-slate-800 pb-2.5 flex items-center gap-2">
                        <span>🍸 2. Cóctel / Aperitivo</span>
                    </h3>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Estilo musical preferido o canciones para el cóctel</label>
                        <textarea wire:model="cocktail_songs" rows="3" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 p-3 leading-relaxed" placeholder="Ej: Pop-rock acústico en español, Indie chill, Jazz moderno, Bossa nova..."></textarea>
                    </div>
                </div>

                <!-- SECCIÓN 3: BANQUETE Y MOMENTOS CLAVE -->
                <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-5">
                    <h3 class="text-base font-black text-indigo-900 dark:text-indigo-300 border-b border-slate-100 dark:border-slate-800 pb-2.5 flex items-center gap-2">
                        <span>🍽️ 3. Banquete y Momentos Clave</span>
                    </h3>

                    <!-- Entrada al comedor -->
                    <div x-data="singleSongPicker('entrance_song')" class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">🎯 Canción de Entrada al Comedor / Salón</label>
                        
                        <div class="relative">
                            <input type="text" wire:model="entrance_song" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 p-3" placeholder="Ej: AC/DC - Thunderstruck / Avicii - The Nights">
                        </div>

                        <!-- Buscador asistente de apoyo -->
                        <div class="relative pt-1">
                            <div class="flex items-center gap-2">
                                <input type="text" x-model="searchQuery" @input="onInput()" placeholder="🔍 O busca la canción en el catálogo para auto-rellenar..." class="w-full bg-white dark:bg-slate-900 border border-dashed border-slate-300 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3 py-1.5 focus:ring-1 focus:ring-indigo-500" autocomplete="off">
                                <span x-show="loading" class="text-xs text-indigo-500 animate-spin flex-shrink-0" style="display: none;">⏳</span>
                            </div>

                            <div x-show="showDropdown" @click.away="showDropdown = false" class="absolute z-30 w-full mt-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-2xl max-h-56 overflow-y-auto" style="display: none;">
                                <template x-for="song in results" :key="song.trackId">
                                    <div @click="selectSong(song)" class="px-3.5 py-2 hover:bg-indigo-50 dark:hover:bg-slate-800 cursor-pointer flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 transition">
                                        <img :src="song.artworkUrl30" class="w-7 h-7 rounded object-cover flex-shrink-0" alt="cover">
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold text-slate-800 dark:text-white truncate" x-text="song.trackName"></div>
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate" x-text="song.artistName"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Corte de tarta -->
                    <div x-data="singleSongPicker('cake_song')" class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">🎯 Canción para el Corte de Tarta (Opcional)</label>
                        
                        <div class="relative">
                            <input type="text" wire:model="cake_song" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 p-3" placeholder="Ej: Queen - Don't Stop Me Now">
                        </div>

                        <!-- Buscador asistente de apoyo -->
                        <div class="relative pt-1">
                            <div class="flex items-center gap-2">
                                <input type="text" x-model="searchQuery" @input="onInput()" placeholder="🔍 O busca la canción en el catálogo para auto-rellenar..." class="w-full bg-white dark:bg-slate-900 border border-dashed border-slate-300 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3 py-1.5 focus:ring-1 focus:ring-indigo-500" autocomplete="off">
                                <span x-show="loading" class="text-xs text-indigo-500 animate-spin flex-shrink-0" style="display: none;">⏳</span>
                            </div>

                            <div x-show="showDropdown" @click.away="showDropdown = false" class="absolute z-30 w-full mt-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-2xl max-h-56 overflow-y-auto" style="display: none;">
                                <template x-for="song in results" :key="song.trackId">
                                    <div @click="selectSong(song)" class="px-3.5 py-2 hover:bg-indigo-50 dark:hover:bg-slate-800 cursor-pointer flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 transition">
                                        <img :src="song.artworkUrl30" class="w-7 h-7 rounded object-cover flex-shrink-0" alt="cover">
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold text-slate-800 dark:text-white truncate" x-text="song.trackName"></div>
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate" x-text="song.artistName"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Regalos y Sorpresas -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">🎯 Regalos especiales, Ramos o Sorpresas con música</label>
                        <textarea wire:model="gifts_songs" rows="4" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 p-3 leading-relaxed" placeholder="Ej: 
- Regalo Padres: Manuel Carrasco - No dejes de soñar
- Ramo Novia: Beyonce - Single Ladies
- Amigos viaje: La La Love You - El fin del mundo"></textarea>
                    </div>
                </div>

                <!-- SECCIÓN 4: BAILE NUPCIAL Y FIESTA -->
                <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-5">
                    <h3 class="text-base font-black text-indigo-900 dark:text-indigo-300 border-b border-slate-100 dark:border-slate-800 pb-2.5 flex items-center gap-2">
                        <span>💃 4. Baile Nupcial y Fiesta</span>
                    </h3>

                    <!-- Baile Nupcial -->
                    <div x-data="singleSongPicker('dance_song')" class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">🎯 Canción del Baile Nupcial (Apertura de Baile)</label>
                        
                        <div class="relative">
                            <input type="text" wire:model="dance_song" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 p-3" placeholder="Ej: Ed Sheeran - Perfect">
                        </div>

                        <!-- Buscador asistente de apoyo -->
                        <div class="relative pt-1">
                            <div class="flex items-center gap-2">
                                <input type="text" x-model="searchQuery" @input="onInput()" placeholder="🔍 O busca la canción en el catálogo para auto-rellenar..." class="w-full bg-white dark:bg-slate-900 border border-dashed border-slate-300 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3 py-1.5 focus:ring-1 focus:ring-indigo-500" autocomplete="off">
                                <span x-show="loading" class="text-xs text-indigo-500 animate-spin flex-shrink-0" style="display: none;">⏳</span>
                            </div>

                            <div x-show="showDropdown" @click.away="showDropdown = false" class="absolute z-30 w-full mt-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-2xl max-h-56 overflow-y-auto" style="display: none;">
                                <template x-for="song in results" :key="song.trackId">
                                    <div @click="selectSong(song)" class="px-3.5 py-2 hover:bg-indigo-50 dark:hover:bg-slate-800 cursor-pointer flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 transition">
                                        <img :src="song.artworkUrl30" class="w-7 h-7 rounded object-cover flex-shrink-0" alt="cover">
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold text-slate-800 dark:text-white truncate" x-text="song.trackName"></div>
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate" x-text="song.artistName"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Temazos imprescindibles con Lista Interactiva -->
                    <div x-data="partyListBuilder('party_favs')" class="space-y-3">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">⭐ Temazos imprescindibles para la Barra Libre / Fiesta</label>
                        
                        <!-- Buscador rápido -->
                        <div class="relative">
                            <div class="flex items-center gap-2">
                                <input type="text" x-model="searchQuery" @input="onSearch()" placeholder="🔍 Busca una canción y pulsa para añadir a tu lista..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500" autocomplete="off">
                                <span x-show="loading" class="text-xs text-indigo-500 animate-spin flex-shrink-0" style="display: none;">⏳</span>
                            </div>

                            <div x-show="showDropdown" @click.away="showDropdown = false" class="absolute z-30 w-full mt-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-2xl max-h-60 overflow-y-auto" style="display: none;">
                                <template x-for="song in results" :key="song.trackId">
                                    <div @click="addFromSearch(song)" class="px-4 py-2.5 hover:bg-indigo-50 dark:hover:bg-slate-800 cursor-pointer flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 transition">
                                        <img :src="song.artworkUrl30" class="w-8 h-8 rounded-lg object-cover flex-shrink-0" alt="cover">
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold text-slate-800 dark:text-white truncate" x-text="song.trackName"></div>
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate" x-text="song.artistName"></div>
                                        </div>
                                        <span class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/80 px-2 py-0.5 rounded-md flex-shrink-0">+ Añadir</span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Lista visual de canciones añadidas -->
                        <div class="space-y-1.5" x-show="songs.length > 0">
                            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Canciones en tu lista (<span x-text="songs.length"></span>):</span>
                            <div class="space-y-1.5 max-h-56 overflow-y-auto pr-1">
                                <template x-for="(song, idx) in songs" :key="idx">
                                    <div class="flex items-center justify-between gap-2 p-2.5 bg-indigo-50/50 dark:bg-slate-950 border border-indigo-100 dark:border-slate-800 rounded-xl">
                                        <div class="flex items-center gap-2 min-w-0 flex-1">
                                            <span class="text-indigo-500 flex-shrink-0 text-xs">🎵</span>
                                            <span class="text-xs font-bold text-slate-800 dark:text-white truncate" x-text="song"></span>
                                        </div>
                                        <button type="button" @click="remove(idx)" class="text-rose-500 hover:text-rose-700 text-xs font-bold px-2 py-0.5 rounded hover:bg-rose-50 dark:hover:bg-rose-950/50 transition flex-shrink-0" title="Eliminar de la lista">
                                            ✕
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Añadir manualmente -->
                        <div class="pt-1">
                            <div class="flex items-center gap-2">
                                <input type="text" x-model="manualInput" @keydown.enter.prevent="addManual()" placeholder="O escribe una canción/artista manualmente..." class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3 py-2 focus:ring-1 focus:ring-indigo-500">
                                <button type="button" @click="addManual()" class="px-3 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold whitespace-nowrap transition">
                                    + Añadir
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            @else
                <!-- ==================== CUESTIONARIO ADAPTADO PARA EMPRESAS, CUMPLEAÑOS Y FIESTAS ==================== -->

                <!-- SECCIÓN 1: ESTILOS & AMBIENTE -->
                <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-2.5">
                    <h3 class="text-base font-black text-indigo-900 dark:text-indigo-300 border-b border-slate-100 dark:border-slate-800 pb-2.5 flex items-center gap-2">
                        <span>🎵 1. Estilo Musical & Ambiente de la Fiesta</span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">¿Qué tipo de música queréis que predomine en el evento?</p>
                    <div>
                        <textarea wire:model="cocktail_songs" rows="3" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 p-3 leading-relaxed" placeholder="Ej: Pop-rock español, Éxitos 80s y 90s, Comercial actual, Reggaeton bailable, Indie pop, House/Electrónica elegante..."></textarea>
                    </div>
                </div>

                <!-- SECCIÓN 2: TEMAZOS IMPRESCINDIBLES -->
                <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4" x-data="partyListBuilder('party_favs')">
                    <div>
                        <h3 class="text-base font-black text-indigo-900 dark:text-indigo-300 border-b border-slate-100 dark:border-slate-800 pb-2.5 flex items-center gap-2">
                            <span>🔥 2. Canciones Imprescindibles (Temazos Favoritos)</span>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Busca canciones para añadirlas a tu lista, o escribe tus temas favoritos.</p>
                    </div>

                    <!-- Buscador rápido -->
                    <div class="relative">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">🔍 Buscar canción en catálogo:</label>
                        <div class="flex items-center gap-2">
                            <input type="text" x-model="searchQuery" @input="onSearch()" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 p-2.5 focus:ring-2 focus:ring-indigo-500" placeholder="Escribe para buscar... Ej: KAROL G, Coldplay, Quevedo..." autocomplete="off">
                            <span x-show="loading" class="text-xs text-indigo-500 animate-spin flex-shrink-0" style="display: none;">⏳</span>
                        </div>
                        
                        <div x-show="showDropdown" @click.away="showDropdown = false" class="absolute z-30 w-full mt-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-2xl max-h-60 overflow-y-auto" style="display: none;">
                            <template x-for="song in results" :key="song.trackId">
                                <div @click="addFromSearch(song)" class="px-4 py-2.5 hover:bg-indigo-50 dark:hover:bg-slate-800 cursor-pointer flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 transition">
                                    <img :src="song.artworkUrl30" class="w-8 h-8 rounded-lg object-cover flex-shrink-0" alt="cover">
                                    <div class="min-w-0 flex-1">
                                        <div class="text-xs font-bold text-slate-800 dark:text-white truncate" x-text="song.trackName"></div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate" x-text="song.artistName"></div>
                                    </div>
                                    <span class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/80 px-2.5 py-1 rounded-lg flex-shrink-0">+ Añadir a Lista</span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Lista visual de canciones añadidas -->
                    <div class="space-y-1.5" x-show="songs.length > 0">
                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Canciones en tu lista (<span x-text="songs.length"></span>):</span>
                        <div class="space-y-1.5 max-h-56 overflow-y-auto pr-1">
                            <template x-for="(song, idx) in songs" :key="idx">
                                <div class="flex items-center justify-between gap-2 p-2.5 bg-indigo-50/50 dark:bg-slate-950 border border-indigo-100 dark:border-slate-800 rounded-xl">
                                    <div class="flex items-center gap-2 min-w-0 flex-1">
                                        <span class="text-indigo-500 flex-shrink-0 text-xs">🎵</span>
                                        <span class="text-xs font-bold text-slate-800 dark:text-white truncate" x-text="song"></span>
                                    </div>
                                    <button type="button" @click="remove(idx)" class="text-rose-500 hover:text-rose-700 text-xs font-bold px-2 py-0.5 rounded hover:bg-rose-50 dark:hover:bg-rose-950/50 transition flex-shrink-0" title="Eliminar de la lista">
                                        ✕
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Añadir manualmente -->
                    <div class="pt-1">
                        <div class="flex items-center gap-2">
                            <input type="text" x-model="manualInput" @keydown.enter.prevent="addManual()" placeholder="O escribe una canción/artista manualmente..." class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3 py-2 focus:ring-1 focus:ring-indigo-500">
                            <button type="button" @click="addManual()" class="px-3 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold whitespace-nowrap transition">
                                + Añadir
                            </button>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 3: MOMENTOS ESPECIALES -->
                <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-2.5">
                    <h3 class="text-base font-black text-indigo-900 dark:text-indigo-300 border-b border-slate-100 dark:border-slate-800 pb-2.5 flex items-center gap-2">
                        <span>🎯 3. Momentos Especiales o Clave (Opcional)</span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">¿Habrá algún momento especial que requiera una canción concreta? (Entrada del protagonista, tarta/velas, entrega de regalos, discurso, brindis...)</p>
                    <div>
                        <textarea wire:model="special_moments" rows="4" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 p-3 leading-relaxed" placeholder="Ej:
- Entrada / Llegada: Survivor - Eye of the Tiger
- Tarta / Velas: Stevie Wonder - Happy Birthday
- Brindis / Entrega: Queen - We Are The Champions"></textarea>
                    </div>
                </div>

            @endif

            <!-- SECCIÓN COMÚN: LISTA NEGRA -->
            <div class="bg-rose-50/40 dark:bg-rose-950/20 p-5 sm:p-6 rounded-2xl border border-rose-200 dark:border-rose-900/60 shadow-sm space-y-2.5">
                <h3 class="text-base font-black text-rose-800 dark:text-rose-300 border-b border-rose-200/80 dark:border-rose-900/60 pb-2.5 flex items-center gap-2">
                    <span>🚫 Lista Negra (Canciones o estilos PROHIBIDOS)</span>
                </h3>
                <p class="text-xs text-rose-700/90 dark:text-rose-400">Música, géneros o artistas que <strong>NO queréis que suenen</strong> bajo ningún concepto.</p>
                <div>
                    <textarea wire:model="blacklist" rows="3" class="w-full bg-white dark:bg-slate-950 border border-rose-300 dark:border-rose-800/80 rounded-xl text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 p-3 leading-relaxed" placeholder="Ej: Nada de reggaeton antiguo, evitar canciones tristes, no poner Paquito el Chocolatero, nada de trap..."></textarea>
                </div>
            </div>

            <!-- SECCIÓN COMÚN: COMENTARIOS ADICIONALES -->
            <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-2.5">
                <h3 class="text-base font-black text-slate-800 dark:text-slate-200 border-b border-slate-100 dark:border-slate-800 pb-2.5">📝 Otros comentarios o indicaciones para el DJ</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Cualquier detalle sobre el horario, volumen, microfonía o público asistente.</p>
                <div>
                    <textarea wire:model="comments" rows="3" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 p-3 leading-relaxed" placeholder="Cualquier indicación que consideréis importante para el DJ..."></textarea>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full flex justify-center items-center gap-2 py-4 px-6 rounded-2xl shadow-lg shadow-indigo-600/20 text-base font-extrabold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-300 transition cursor-pointer transform hover:-translate-y-0.5">
                    <span>🚀 Guardar y Enviar Preferencias</span>
                </button>
            </div>
        </form>
    @endif
</div>
