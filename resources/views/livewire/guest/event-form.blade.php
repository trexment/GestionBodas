<div class="max-w-2xl mx-auto py-6 px-4">
    <div class="text-center mb-8">
        <h2 class="text-3xl font-extrabold text-indigo-600">Eventos Musicales</h2>
        <div class="flex items-center justify-center gap-2 mt-1">
            <span class="text-lg">{{ $event->event_type_icon }}</span>
            <p class="text-gray-700 text-sm font-bold">Cuestionario Musical: <strong>{{ $event->name }}</strong></p>
        </div>
        <p class="text-xs text-gray-400 mt-0.5">
            @if($event->is_wedding)
                Rellena las canciones que no pueden faltar en vuestro gran día.
            @else
                Indícanos los estilos, canciones imprescindibles y momentos especiales de tu {{ mb_strtolower($event->event_type_label) }}.
            @endif
        </p>
    </div>

    @if($event->is_dossier_completed)
        <div class="bg-gray-100 border-l-4 border-gray-500 text-gray-700 p-6 rounded-xl text-center shadow-sm">
            <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            <p class="font-bold text-lg">Cuestionario Cerrado</p>
            <p class="text-sm mt-2 text-gray-600">Toda la información musical ya ha sido procesada y confirmada con el equipo técnico. Si necesitas hacer algún cambio urgente de última hora, por favor contáctanos directamente por WhatsApp o teléfono.</p>
        </div>
    @elseif($submitted)
        <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-6 rounded-xl text-center shadow-sm">
            <svg class="w-14 h-14 mx-auto mb-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <p class="font-extrabold text-xl">¡Cuestionario Guardado con Éxito!</p>
            <p class="text-sm mt-2 text-emerald-700">Hemos recibido vuestras peticiones y preferencias musicales. Nuestro DJ ya tiene vuestras canciones preparadas. ¡Nos vemos en la pista de baile!</p>
        </div>
    @else
        @if($hasExistingData)
            <div class="bg-amber-50 border-l-4 border-amber-500 text-amber-900 p-4 rounded-xl shadow-sm mb-6 flex items-start gap-3">
                <span class="text-2xl">✏️</span>
                <div class="text-xs leading-relaxed">
                    <strong class="font-bold text-sm block text-amber-950 mb-0.5">Formulario Reabierto para Edición</strong>
                    Hemos precargado todas las canciones y notas que enviasteis anteriormente. Podéis revisar lo que pusisteis, realizar cualquier cambio o añadir nuevos momentos, y pulsar en <strong>"Guardar y Enviar Preferencias"</strong> para confirmarlo.
                </div>
            </div>
        @endif

        <form wire:submit.prevent="submitForm" class="space-y-6">
            
            <script>
                function songSearch(modelName) {
                    return {
                        query: '',
                        results: [],
                        loading: false,
                        showDropdown: false,
                        init() {
                            this.query = this.$wire.get(modelName) || '';
                            this.$watch('query', (value) => {
                                this.$wire.set(modelName, value);
                                if (value.length > 2) {
                                    this.fetchSongs(value);
                                } else {
                                    this.results = [];
                                    this.showDropdown = false;
                                }
                            });
                        },
                        fetchSongs(term) {
                            this.loading = true;
                            fetch(`https://itunes.apple.com/search?term=${encodeURIComponent(term)}&entity=song&limit=5`)
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
                            this.query = formatted;
                            this.$wire.set(modelName, formatted);
                            this.showDropdown = false;
                        }
                    }
                }
            </script>

            @if($event->is_wedding)
                <!-- ==================== CUESTIONARIO COMPLETO PARA BODAS ==================== -->

                <!-- SECCIÓN 1: CEREMONIA -->
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <h3 class="text-base font-bold text-indigo-900 border-b pb-2 mb-4 flex items-center gap-2">
                        <span>💍 1. Ceremonia (Opcional)</span>
                    </h3>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Canciones para la Ceremonia (Entradas, Anillos, Firmas, Salida)</label>
                        <textarea wire:model="ceremony_songs" rows="3" class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 p-2.5" placeholder="Ej: Entrada Novio: Coldplay - Viva la Vida / Entrada Novia: Canon de Pachelbel / Salida: Bruno Mars - Marry You"></textarea>
                    </div>
                </div>

                <!-- SECCIÓN 2: CÓCTEL -->
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <h3 class="text-base font-bold text-indigo-900 border-b pb-2 mb-4 flex items-center gap-2">
                        <span>🍸 2. Cóctel / Aperitivo</span>
                    </h3>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Estilo musical preferido o canciones para el cóctel</label>
                        <textarea wire:model="cocktail_songs" rows="3" class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 p-2.5" placeholder="Ej: Pop-rock acústico en español, Indie chill, Jazz moderno, Bossa nova..."></textarea>
                    </div>
                </div>

                <!-- SECCIÓN 3: BANQUETE Y MOMENTOS CLAVE -->
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm space-y-4">
                    <h3 class="text-base font-bold text-indigo-900 border-b pb-2 flex items-center gap-2">
                        <span>🍽️ 3. Banquete y Momentos Clave</span>
                    </h3>

                    <!-- Entrada al comedor -->
                    <div x-data="songSearch('entrance_song')" class="relative">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">🎯 Canción de Entrada al Comedor / Salón</label>
                        <input type="text" x-model.debounce.500ms="query" class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 p-2.5" placeholder="Ej: AC/DC - Thunderstruck / Avicii - The Nights" autocomplete="off">
                        
                        <div x-show="showDropdown" @click.away="showDropdown = false" class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-60 overflow-auto" style="display: none;">
                            <template x-for="song in results" :key="song.trackId">
                                <div @click="selectSong(song)" class="px-4 py-2 hover:bg-indigo-50 cursor-pointer flex items-center gap-3 border-b border-gray-100">
                                    <img :src="song.artworkUrl30" class="w-8 h-8 rounded" alt="cover">
                                    <div>
                                        <div class="text-sm font-bold text-gray-800" x-text="song.trackName"></div>
                                        <div class="text-xs text-gray-500" x-text="song.artistName"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Corte de tarta -->
                    <div x-data="songSearch('cake_song')" class="relative">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">🎯 Canción para el Corte de Tarta (Opcional)</label>
                        <input type="text" x-model.debounce.500ms="query" class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 p-2.5" placeholder="Ej: Queen - Don't Stop Me Now" autocomplete="off">
                        
                        <div x-show="showDropdown" @click.away="showDropdown = false" class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-60 overflow-auto" style="display: none;">
                            <template x-for="song in results" :key="song.trackId">
                                <div @click="selectSong(song)" class="px-4 py-2 hover:bg-indigo-50 cursor-pointer flex items-center gap-3 border-b border-gray-100">
                                    <img :src="song.artworkUrl30" class="w-8 h-8 rounded" alt="cover">
                                    <div>
                                        <div class="text-sm font-bold text-gray-800" x-text="song.trackName"></div>
                                        <div class="text-xs text-gray-500" x-text="song.artistName"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Regalos y Sorpresas -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">🎯 Regalos especiales, Ramos o Sorpresas con música</label>
                        <textarea wire:model="gifts_songs" rows="4" class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 p-2.5" placeholder="Ej: 
- Regalo Padres: Manuel Carrasco - No dejes de soñar
- Ramo Novia: Beyonce - Single Ladies
- Amigos viaje: La La Love You - El fin del mundo"></textarea>
                    </div>
                </div>

                <!-- SECCIÓN 4: BAILE NUPCIAL Y FIESTA -->
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm space-y-4">
                    <h3 class="text-base font-bold text-indigo-900 border-b pb-2 flex items-center gap-2">
                        <span>💃 4. Baile Nupcial y Fiesta</span>
                    </h3>

                    <!-- Baile Nupcial -->
                    <div x-data="songSearch('dance_song')" class="relative">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">🎯 Canción del Baile Nupcial (Apertura de Baile)</label>
                        <input type="text" x-model.debounce.500ms="query" class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 p-2.5" placeholder="Ej: Ed Sheeran - Perfect" autocomplete="off">
                        
                        <div x-show="showDropdown" @click.away="showDropdown = false" class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-60 overflow-auto" style="display: none;">
                            <template x-for="song in results" :key="song.trackId">
                                <div @click="selectSong(song)" class="px-4 py-2 hover:bg-indigo-50 cursor-pointer flex items-center gap-3 border-b border-gray-100">
                                    <img :src="song.artworkUrl30" class="w-8 h-8 rounded" alt="cover">
                                    <div>
                                        <div class="text-sm font-bold text-gray-800" x-text="song.trackName"></div>
                                        <div class="text-xs text-gray-500" x-text="song.artistName"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Temazos imprescindibles -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">⭐ Temazos imprescindibles para la Barra Libre / Fiesta</label>
                        <textarea wire:model="party_favs" rows="3" class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 p-2.5" placeholder="Canciones o artistas que tienen que sonar sí o sí (ej: Estopa, Quevedo, Pop 2000s, Rock clásico...)"></textarea>
                    </div>
                </div>

            @else
                <!-- ==================== CUESTIONARIO ADAPTADO PARA EMPRESAS, CUMPLEAÑOS Y FIESTAS ==================== -->

                <!-- SECCIÓN 1: ESTILOS & AMBIENTE -->
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm space-y-2">
                    <h3 class="text-base font-bold text-indigo-900 border-b pb-2 flex items-center gap-2">
                        <span>🎵 1. Estilo Musical & Ambiente de la Fiesta</span>
                    </h3>
                    <p class="text-xs text-gray-500">¿Qué tipo de música queréis que predomine en el evento?</p>
                    <div>
                        <textarea wire:model="cocktail_songs" rows="3" class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 p-2.5" placeholder="Ej: Pop-rock español, Éxitos 80s y 90s, Comercial actual, Reggaeton bailable, Indie pop, House/Electrónica elegante..."></textarea>
                    </div>
                </div>

                <!-- SECCIÓN 2: TEMAZOS IMPRESCINDIBLES -->
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm space-y-4">
                    <h3 class="text-base font-bold text-indigo-900 border-b pb-2 flex items-center gap-2">
                        <span>🔥 2. Canciones Imprescindibles (Temazos Favoritos)</span>
                    </h3>
                    <p class="text-xs text-gray-500">Canciones concretas o artistas que tienen que sonar sí o sí durante la fiesta.</p>

                    <!-- Buscador rápido para añadir temazo -->
                    <div x-data="songSearch('party_favs')" class="relative">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">🔍 Buscar canción en iTunes / Spotify (Opcional):</label>
                        <input type="text" x-model.debounce.500ms="query" class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 p-2.5" placeholder="Escribe para buscar... Ej: Coldplay - Viva la Vida" autocomplete="off">
                        
                        <div x-show="showDropdown" @click.away="showDropdown = false" class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-60 overflow-auto" style="display: none;">
                            <template x-for="song in results" :key="song.trackId">
                                <div @click="selectSong(song)" class="px-4 py-2 hover:bg-indigo-50 cursor-pointer flex items-center gap-3 border-b border-gray-100">
                                    <img :src="song.artworkUrl30" class="w-8 h-8 rounded" alt="cover">
                                    <div>
                                        <div class="text-sm font-bold text-gray-800" x-text="song.trackName"></div>
                                        <div class="text-xs text-gray-500" x-text="song.artistName"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Lista de canciones favoritas (una por línea o separadas por comas):</label>
                        <textarea wire:model="party_favs" rows="5" class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 p-2.5 font-mono text-xs" placeholder="Ej:
- Queen - Don't Stop Me Now
- Quevedo - Columbia
- Pereza - Princesas
- ABBA - Gimme! Gimme! Gimme!
- Dua Lipa - Levitating"></textarea>
                    </div>
                </div>

                <!-- SECCIÓN 3: MOMENTOS ESPECIALES -->
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm space-y-2">
                    <h3 class="text-base font-bold text-indigo-900 border-b pb-2 flex items-center gap-2">
                        <span>🎯 3. Momentos Especiales o Clave (Opcional)</span>
                    </h3>
                    <p class="text-xs text-gray-500">¿Habrá algún momento especial que requiera una canción concreta? (Entrada del protagonista, tarta/velas, entrega de regalos, discurso, brindis...)</p>
                    <div>
                        <textarea wire:model="special_moments" rows="4" class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 p-2.5" placeholder="Ej:
- Entrada / Llegada: Survivor - Eye of the Tiger
- Tarta / Velas: Stevie Wonder - Happy Birthday
- Brindis / Entrega: Queen - We Are The Champions"></textarea>
                    </div>
                </div>

            @endif

            <!-- SECCIÓN COMÚN: LISTA NEGRA -->
            <div class="bg-white p-5 rounded-xl border border-rose-200 shadow-sm bg-rose-50/20">
                <h3 class="text-base font-bold text-rose-800 border-b border-rose-200 pb-2 mb-2 flex items-center gap-2">
                    <span>🚫 Lista Negra (Canciones o estilos PROHIBIDOS)</span>
                </h3>
                <p class="text-xs text-rose-700/80 mb-3">Música, géneros o artistas que <strong>NO queréis que suenen</strong> bajo ningún concepto.</p>
                <div>
                    <textarea wire:model="blacklist" rows="3" class="w-full border-rose-300 rounded-lg text-sm focus:ring-rose-500 focus:border-rose-500 p-2.5" placeholder="Ej: Nada de reggaeton antiguo, evitar canciones tristes, no poner Paquito el Chocolatero, nada de trap..."></textarea>
                </div>
            </div>

            <!-- SECCIÓN COMÚN: COMENTARIOS ADICIONALES -->
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                <h3 class="text-base font-bold text-gray-800 border-b pb-2 mb-2">📝 Otros comentarios o indicaciones para el DJ</h3>
                <p class="text-xs text-gray-500 mb-3">Cualquier detalle sobre el horario, volumen, microfonía o público asistente.</p>
                <div>
                    <textarea wire:model="comments" rows="3" class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 p-2.5" placeholder="Cualquier indicación que consideréis importante para el DJ..."></textarea>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full flex justify-center items-center gap-2 py-3.5 px-6 rounded-xl shadow-md text-base font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-300 transition cursor-pointer">
                    <span>🚀 Guardar y Enviar Preferencias</span>
                </button>
            </div>
        </form>
    @endif
</div>
