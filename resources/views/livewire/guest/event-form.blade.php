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
                Rellena o busca las canciones que no pueden faltar en vuestro gran día.
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
    @else
        @if($submitted)
            <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-700/70 text-emerald-900 dark:text-emerald-200 p-4 rounded-2xl shadow-sm mb-6 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <span class="text-2xl flex-shrink-0">🎉</span>
                    <div class="text-xs leading-relaxed">
                        <strong class="font-bold text-sm block text-emerald-950 dark:text-emerald-100 mb-0.5">¡Preferencias Guardadas con Éxito!</strong>
                        Se han guardado y enviado todas vuestras canciones y momentos para el DJ. Podéis seguir editando si lo deseáis.
                    </div>
                </div>
                <button type="button" wire:click="$set('showSuccessModal', true)" class="text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-xl shadow-xs transition whitespace-nowrap">
                    Ver Aviso
                </button>
            </div>
        @elseif($hasExistingData)
            <div class="bg-amber-50 dark:bg-amber-950/50 border border-amber-300 dark:border-amber-700/70 text-amber-900 dark:text-amber-200 p-4 rounded-2xl shadow-sm mb-6 flex items-start gap-3">
                <span class="text-2xl flex-shrink-0">✏️</span>
                <div class="text-xs leading-relaxed">
                    <strong class="font-bold text-sm block text-amber-950 dark:text-amber-100 mb-0.5">Formulario Precargado para Edición</strong>
                    Hemos precargado todas las canciones y notas que enviasteis anteriormente. Podéis revisar lo que pusisteis, realizar cualquier cambio o añadir nuevos momentos, y pulsar en <strong>"Guardar y Enviar Preferencias"</strong> para confirmarlo.
                </div>
            </div>
        @endif

        <form wire:submit.prevent="submitForm" class="space-y-6">
            
            <script>
                // Global Audio Preview Manager
                window.previewAudio = null;
                window.previewPlayingUrl = null;
                window.togglePreview = function(url, callback) {
                    if (window.previewAudio) {
                        window.previewAudio.pause();
                        const wasSame = (window.previewPlayingUrl === url);
                        window.previewAudio = null;
                        window.previewPlayingUrl = null;
                        if (wasSame) {
                            if (callback) callback(null);
                            return;
                        }
                    }
                    if (!url) {
                        if (callback) callback(null);
                        return;
                    }
                    window.previewPlayingUrl = url;
                    window.previewAudio = new Audio(url);
                    window.previewAudio.play().catch(() => {});
                    if (callback) callback(url);
                    window.previewAudio.onended = () => {
                        window.previewAudio = null;
                        window.previewPlayingUrl = null;
                        if (callback) callback(null);
                    };
                };

                // Single Song Picker (Entrada Comedor, Tarta, Baile)
                function singleSongPicker(targetWireProperty) {
                    return {
                        selectedSong: '',
                        searchQuery: '',
                        results: [],
                        loading: false,
                        showDropdown: false,
                        playingPreviewUrl: null,
                        timer: null,
                        init() {
                            this.selectedSong = this.$wire.get(targetWireProperty) || '';
                            this.$watch('$wire.' + targetWireProperty, val => {
                                this.selectedSong = val || '';
                            });
                        },
                        onInput() {
                            clearTimeout(this.timer);
                            if (this.searchQuery.trim().length > 1) {
                                this.timer = setTimeout(() => {
                                    this.fetchSongs(this.searchQuery.trim());
                                }, 220);
                            } else {
                                this.results = [];
                                this.showDropdown = false;
                            }
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
                            const formatted = `${song.artistName} - ${song.trackName}`;
                            this.selectedSong = formatted;
                            this.$wire.set(targetWireProperty, formatted);
                            this.searchQuery = '';
                            this.showDropdown = false;
                            this.results = [];
                            if (window.previewAudio) {
                                window.previewAudio.pause();
                                window.previewPlayingUrl = null;
                                this.playingPreviewUrl = null;
                            }
                        },
                        selectCustom() {
                            const formatted = this.searchQuery.trim();
                            if (!formatted) return;
                            this.selectedSong = formatted;
                            this.$wire.set(targetWireProperty, formatted);
                            this.searchQuery = '';
                            this.showDropdown = false;
                            this.results = [];
                            if (window.previewAudio) {
                                window.previewAudio.pause();
                                window.previewPlayingUrl = null;
                                this.playingPreviewUrl = null;
                            }
                        },
                        clearSong() {
                            this.selectedSong = '';
                            this.$wire.set(targetWireProperty, '');
                        },
                        toggleAudio(url) {
                            window.togglePreview(url, (activeUrl) => {
                                this.playingPreviewUrl = activeUrl;
                            });
                        }
                    }
                }

                // Moment List Builder (Ceremonia, Regalos & Sorpresas, Momentos Especiales)
                function momentListBuilder(targetWireProperty, defaultMoment = '') {
                    return {
                        items: [],
                        activeMoment: defaultMoment,
                        customMoment: '',
                        searchQuery: '',
                        manualSong: '',
                        results: [],
                        loading: false,
                        showDropdown: false,
                        playingPreviewUrl: null,
                        timer: null,
                        init() {
                            const raw = this.$wire.get(targetWireProperty) || '';
                            if (raw.trim()) {
                                this.items = raw.split('\n')
                                    .map(line => line.trim().replace(/^[-*•]\s*/, ''))
                                    .filter(line => line.length > 0)
                                    .map(line => {
                                        if (line.includes(':')) {
                                            const parts = line.split(':');
                                            return {
                                                moment: parts[0].trim(),
                                                song: parts.slice(1).join(':').trim()
                                            };
                                        }
                                        return { moment: '', song: line };
                                    });
                            }
                        },
                        sync() {
                            const lines = this.items.map(item => {
                                if (item.moment && item.moment.trim()) {
                                    return `${item.moment.trim()}: ${item.song.trim()}`;
                                }
                                return item.song.trim();
                            });
                            this.$wire.set(targetWireProperty, lines.join('\n'));
                        },
                        setMoment(m) {
                            this.activeMoment = m;
                        },
                        onSearch() {
                            clearTimeout(this.timer);
                            if (this.searchQuery.trim().length > 1) {
                                this.timer = setTimeout(() => {
                                    this.fetchSongs(this.searchQuery.trim());
                                }, 220);
                            } else {
                                this.results = [];
                                this.showDropdown = false;
                            }
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
                        addFromSearch(song) {
                            const formatted = `${song.artistName} - ${song.trackName}`;
                            const momentName = (this.customMoment.trim() || this.activeMoment || '').trim();
                            this.items.push({
                                moment: momentName,
                                song: formatted
                            });
                            this.sync();
                            this.searchQuery = '';
                            this.showDropdown = false;
                            this.results = [];
                            if (window.previewAudio) {
                                window.previewAudio.pause();
                                window.previewPlayingUrl = null;
                                this.playingPreviewUrl = null;
                            }
                        },
                        addCustomFromSearch() {
                            const songText = this.searchQuery.trim();
                            if (songText) {
                                const momentName = (this.customMoment.trim() || this.activeMoment || '').trim();
                                this.items.push({
                                    moment: momentName,
                                    song: songText
                                });
                                this.sync();
                                this.searchQuery = '';
                                this.showDropdown = false;
                                this.results = [];
                                if (window.previewAudio) {
                                    window.previewAudio.pause();
                                    window.previewPlayingUrl = null;
                                    this.playingPreviewUrl = null;
                                }
                            }
                        },
                        addManual() {
                            const songText = this.manualSong.trim();
                            if (songText) {
                                const momentName = (this.customMoment.trim() || this.activeMoment || '').trim();
                                this.items.push({
                                    moment: momentName,
                                    song: songText
                                });
                                this.sync();
                                this.manualSong = '';
                            }
                        },
                        remove(index) {
                            this.items.splice(index, 1);
                            this.sync();
                        },
                        toggleAudio(url) {
                            window.togglePreview(url, (activeUrl) => {
                                this.playingPreviewUrl = activeUrl;
                            });
                        }
                    }
                }

                // Multi Song List Builder (Temazos Fiesta, Lista Negra)
                function multiSongListBuilder(targetWireProperty) {
                    return {
                        songs: [],
                        searchQuery: '',
                        results: [],
                        loading: false,
                        showDropdown: false,
                        manualInput: '',
                        playingPreviewUrl: null,
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
                            if (this.searchQuery.trim().length > 1) {
                                this.timer = setTimeout(() => {
                                    this.fetchSongs(this.searchQuery.trim());
                                }, 220);
                            } else {
                                this.results = [];
                                this.showDropdown = false;
                            }
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
                        addFromSearch(song) {
                            const formatted = `${song.artistName} - ${song.trackName}`;
                            if (!this.songs.includes(formatted)) {
                                this.songs.push(formatted);
                                this.sync();
                            }
                            this.searchQuery = '';
                            this.showDropdown = false;
                            this.results = [];
                            if (window.previewAudio) {
                                window.previewAudio.pause();
                                window.previewPlayingUrl = null;
                                this.playingPreviewUrl = null;
                            }
                        },
                        addCustomFromSearch() {
                            const val = this.searchQuery.trim();
                            if (val && !this.songs.includes(val)) {
                                this.songs.push(val);
                                this.sync();
                            }
                            this.searchQuery = '';
                            this.showDropdown = false;
                            this.results = [];
                            if (window.previewAudio) {
                                window.previewAudio.pause();
                                window.previewPlayingUrl = null;
                                this.playingPreviewUrl = null;
                            }
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
                        },
                        toggleAudio(url) {
                            window.togglePreview(url, (activeUrl) => {
                                this.playingPreviewUrl = activeUrl;
                            });
                        }
                    }
                }

                // Cocktail Style & Songs Builder
                function cocktailStyleBuilder(targetWireProperty) {
                    return {
                        selectedStyles: [],
                        customNotes: '',
                        searchQuery: '',
                        results: [],
                        loading: false,
                        showDropdown: false,
                        playingPreviewUrl: null,
                        timer: null,
                        availableStyles: [
                            'Pop-Rock Español', 'Indie & Chill', 'Jazz & Bossa Nova', 'Deep House & Saxo',
                            'Acústicos / Versiones', 'R&B / Soul', 'Clásicos 80s y 90s', 'Flamenco Fusión'
                        ],
                        init() {
                            const raw = this.$wire.get(targetWireProperty) || '';
                            if (raw.trim()) {
                                const lines = raw.split('\n').map(l => l.trim()).filter(l => l.length > 0);
                                const remaining = [];
                                for (let line of lines) {
                                    if (line.startsWith('Estilos:')) {
                                        const stylesPart = line.replace('Estilos:', '').split(',').map(s => s.trim());
                                        this.selectedStyles = stylesPart.filter(s => this.availableStyles.includes(s));
                                    } else {
                                        remaining.push(line);
                                    }
                                }
                                this.customNotes = remaining.join('\n');
                            }
                        },
                        toggleStyle(style) {
                            if (this.selectedStyles.includes(style)) {
                                this.selectedStyles = this.selectedStyles.filter(s => s !== style);
                            } else {
                                this.selectedStyles.push(style);
                            }
                            this.sync();
                        },
                        sync() {
                            const parts = [];
                            if (this.selectedStyles.length > 0) {
                                parts.push('Estilos: ' + this.selectedStyles.join(', '));
                            }
                            if (this.customNotes.trim()) {
                                parts.push(this.customNotes.trim());
                            }
                            this.$wire.set(targetWireProperty, parts.join('\n'));
                        },
                        onSearch() {
                            clearTimeout(this.timer);
                            if (this.searchQuery.trim().length > 1) {
                                this.timer = setTimeout(() => {
                                    this.fetchSongs(this.searchQuery.trim());
                                }, 220);
                            } else {
                                this.results = [];
                                this.showDropdown = false;
                            }
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
                        addFromSearch(song) {
                            const formatted = `${song.artistName} - ${song.trackName}`;
                            if (this.customNotes.trim()) {
                                this.customNotes += '\n• ' + formatted;
                            } else {
                                this.customNotes = '• ' + formatted;
                            }
                            this.sync();
                            this.searchQuery = '';
                            this.showDropdown = false;
                            this.results = [];
                        },
                        addCustomFromSearch() {
                            const val = this.searchQuery.trim();
                            if (val) {
                                if (this.customNotes.trim()) {
                                    this.customNotes += '\n• ' + val;
                                } else {
                                    this.customNotes = '• ' + val;
                                }
                                this.sync();
                            }
                            this.searchQuery = '';
                            this.showDropdown = false;
                            this.results = [];
                        },
                        toggleAudio(url) {
                            window.togglePreview(url, (activeUrl) => {
                                this.playingPreviewUrl = activeUrl;
                            });
                        }
                    }
                }
            </script>

            @if($event->is_wedding)
                <!-- ==================== CUESTIONARIO COMPLETO PARA BODAS ==================== -->

                <!-- SECCIÓN 1: CEREMONIA -->
                <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4" x-data="momentListBuilder('ceremony_songs', 'Entrada Novio')">
                    <div>
                        <h3 class="text-base font-black text-indigo-900 dark:text-indigo-300 border-b border-slate-100 dark:border-slate-800 pb-2.5 flex items-center gap-2">
                            <span>💍 1. Ceremonia (Opcional)</span>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Busca y añade canciones para cada momento de la ceremonia (entradas, lecturas, anillos, firmas, salida).</p>
                    </div>

                    <!-- Selector de Momento de la Ceremonia -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Momento a asignar:</label>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="m in ['Entrada Novio', 'Entrada Novia', 'Lecturas / Votos', 'Anillos', 'Firmas / Fotos', 'Salida Novios']" :key="m">
                                <button type="button" @click="setMoment(m); customMoment = ''" :class="activeMoment === m && !customMoment ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'" class="px-2.5 py-1 rounded-lg text-xs font-semibold transition">
                                    <span x-text="m"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Buscador de canciones para la ceremonia -->
                    <div class="relative">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            🔍 Buscar canción para <span class="text-indigo-600 dark:text-indigo-400 font-bold" x-text="customMoment || activeMoment || 'la Ceremonia'"></span>:
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="text" x-model="searchQuery" @input="onSearch()" placeholder="Escribe título o artista... Ej: Coldplay, Pachelbel, Ludovico Einaudi, Morat..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500" autocomplete="off">
                            <span x-show="loading" class="text-xs text-indigo-500 animate-spin flex-shrink-0" style="display: none;">⏳</span>
                        </div>

                        <!-- Dropdown con resultados y preview -->
                        <div x-show="showDropdown" @click.away="showDropdown = false" class="absolute z-30 w-full mt-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-2xl max-h-64 overflow-y-auto" style="display: none;">
                            <template x-for="song in results" :key="song.trackId">
                                <div class="px-3.5 py-2 hover:bg-indigo-50 dark:hover:bg-slate-800 cursor-pointer flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 transition">
                                    <div @click="addFromSearch(song)" class="flex items-center gap-3 min-w-0 flex-1">
                                        <img :src="song.artworkUrl60 || song.artworkUrl30" class="w-8 h-8 rounded-lg object-cover flex-shrink-0" alt="cover">
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold text-slate-800 dark:text-white truncate" x-text="song.trackName"></div>
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate" x-text="song.artistName"></div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5 flex-shrink-0">
                                        <template x-if="song.previewUrl">
                                            <button type="button" @click.stop="toggleAudio(song.previewUrl)" class="p-1.5 rounded-lg text-xs bg-slate-100 dark:bg-slate-800 hover:bg-indigo-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300" title="Escuchar fragmento">
                                                <span x-show="playingPreviewUrl === song.previewUrl">⏸️</span>
                                                <span x-show="playingPreviewUrl !== song.previewUrl">🔊</span>
                                            </button>
                                        </template>
                                        <button type="button" @click="addFromSearch(song)" class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/80 px-2.5 py-1 rounded-lg hover:bg-indigo-600 hover:text-white transition">
                                            + Añadir
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <template x-if="searchQuery.trim().length > 1">
                                <div @click="addCustomFromSearch()" class="px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950/90 hover:bg-indigo-50 dark:hover:bg-slate-800 cursor-pointer flex items-center justify-between gap-2 border-t border-slate-200 dark:border-slate-700 transition">
                                    <div class="flex items-center gap-2 truncate text-xs text-indigo-600 dark:text-indigo-400 font-bold">
                                        <span>➕</span>
                                        <span class="truncate">Añadir lo que he escrito: "<span x-text="searchQuery" class="underline"></span>"</span>
                                    </div>
                                    <span class="text-[10px] font-semibold bg-indigo-100 dark:bg-indigo-900/60 text-indigo-800 dark:text-indigo-200 px-2 py-0.5 rounded flex-shrink-0">Personalizada</span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Lista de canciones añadidas para ceremonia -->
                    <div class="space-y-1.5" x-show="items.length > 0">
                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Canciones de Ceremonia (<span x-text="items.length"></span>):</span>
                        <div class="space-y-1.5 max-h-60 overflow-y-auto pr-1">
                            <template x-for="(item, idx) in items" :key="idx">
                                <div class="flex items-center justify-between gap-2 p-2.5 bg-indigo-50/50 dark:bg-slate-950 border border-indigo-100 dark:border-slate-800 rounded-xl">
                                    <div class="flex items-center gap-2 min-w-0 flex-1">
                                        <span class="text-xs px-2 py-0.5 rounded-md font-bold bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 flex-shrink-0" x-text="item.moment || 'Ceremonia'"></span>
                                        <span class="text-xs font-semibold text-slate-800 dark:text-white truncate" x-text="item.song"></span>
                                    </div>
                                    <button type="button" @click="remove(idx)" class="text-rose-500 hover:text-rose-700 text-xs font-bold px-2 py-0.5 rounded hover:bg-rose-50 dark:hover:bg-rose-950/50 transition flex-shrink-0" title="Eliminar">
                                        ✕
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Entrada manual / personalizada -->
                    <div class="pt-1">
                        <div class="flex items-center gap-2">
                            <input type="text" x-model="manualSong" @keydown.enter.prevent="addManual()" placeholder="O escribe manualmente una canción / versión..." class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3 py-2 focus:ring-1 focus:ring-indigo-500">
                            <button type="button" @click="addManual()" class="px-3 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold whitespace-nowrap transition">
                                + Añadir
                            </button>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 2: CÓCTEL -->
                <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4" x-data="cocktailStyleBuilder('cocktail_songs')">
                    <div>
                        <h3 class="text-base font-black text-indigo-900 dark:text-indigo-300 border-b border-slate-100 dark:border-slate-800 pb-2.5 flex items-center gap-2">
                            <span>🍸 2. Cóctel / Aperitivo</span>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Elige los estilos que queréis para el cóctel o busca canciones concretas que os gustaría escuchar.</p>
                    </div>

                    <!-- Botones de estilos recomendados -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Estilos musicales preferidos:</label>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="style in availableStyles" :key="style">
                                <button type="button" @click="toggleStyle(style)" :class="selectedStyles.includes(style) ? 'bg-indigo-600 text-white shadow-sm ring-2 ring-indigo-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'" class="px-2.5 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1">
                                    <span x-text="selectedStyles.includes(style) ? '✓' : '+'"></span>
                                    <span x-text="style"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Buscador de canciones para añadir al cóctel -->
                    <div class="relative">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">🔍 ¿Queréis añadir canciones específicas para el Cóctel?:</label>
                        <div class="flex items-center gap-2">
                            <input type="text" x-model="searchQuery" @input="onSearch()" placeholder="Buscar canción para el cóctel... Ej: Jack Johnson, Norah Jones, Leiva..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3.5 py-2 focus:ring-2 focus:ring-indigo-500" autocomplete="off">
                            <span x-show="loading" class="text-xs text-indigo-500 animate-spin flex-shrink-0" style="display: none;">⏳</span>
                        </div>

                        <div x-show="showDropdown" @click.away="showDropdown = false" class="absolute z-30 w-full mt-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-2xl max-h-60 overflow-y-auto" style="display: none;">
                            <template x-for="song in results" :key="song.trackId">
                                <div class="px-3.5 py-2 hover:bg-indigo-50 dark:hover:bg-slate-800 cursor-pointer flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 transition">
                                    <div @click="addFromSearch(song)" class="flex items-center gap-3 min-w-0 flex-1">
                                        <img :src="song.artworkUrl60 || song.artworkUrl30" class="w-8 h-8 rounded-lg object-cover flex-shrink-0" alt="cover">
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold text-slate-800 dark:text-white truncate" x-text="song.trackName"></div>
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate" x-text="song.artistName"></div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5 flex-shrink-0">
                                        <template x-if="song.previewUrl">
                                            <button type="button" @click.stop="toggleAudio(song.previewUrl)" class="p-1.5 rounded-lg text-xs bg-slate-100 dark:bg-slate-800 hover:bg-indigo-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300" title="Escuchar fragmento">
                                                <span x-show="playingPreviewUrl === song.previewUrl">⏸️</span>
                                                <span x-show="playingPreviewUrl !== song.previewUrl">🔊</span>
                                            </button>
                                        </template>
                                        <button type="button" @click="addFromSearch(song)" class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/80 px-2 py-1 rounded-lg hover:bg-indigo-600 hover:text-white transition">
                                            + Añadir
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <template x-if="searchQuery.trim().length > 1">
                                <div @click="addCustomFromSearch()" class="px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950/90 hover:bg-indigo-50 dark:hover:bg-slate-800 cursor-pointer flex items-center justify-between gap-2 border-t border-slate-200 dark:border-slate-700 transition">
                                    <div class="flex items-center gap-2 truncate text-xs text-indigo-600 dark:text-indigo-400 font-bold">
                                        <span>➕</span>
                                        <span class="truncate">Añadir lo que he escrito: "<span x-text="searchQuery" class="underline"></span>"</span>
                                    </div>
                                    <span class="text-[10px] font-semibold bg-indigo-100 dark:bg-indigo-900/60 text-indigo-800 dark:text-indigo-200 px-2 py-0.5 rounded flex-shrink-0">Personalizada</span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Notas / Canciones adicionales del cóctel -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Notas o canciones para el cóctel:</label>
                        <textarea x-model="customNotes" @input="sync()" rows="2" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 p-2.5 leading-relaxed" placeholder="Canciones o indicaciones adicionales para el aperitivo..."></textarea>
                    </div>
                </div>

                <!-- SECCIÓN 3: BANQUETE Y MOMENTOS CLAVE -->
                <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-6">
                    <div>
                        <h3 class="text-base font-black text-indigo-900 dark:text-indigo-300 border-b border-slate-100 dark:border-slate-800 pb-2.5 flex items-center gap-2">
                            <span>🍽️ 3. Banquete y Momentos Clave</span>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Busca la canción para cada momento especial del banquete.</p>
                    </div>

                    <!-- Entrada al comedor -->
                    <div x-data="singleSongPicker('entrance_song')" class="space-y-2 p-3.5 bg-slate-50/70 dark:bg-slate-950/60 rounded-xl border border-slate-200/80 dark:border-slate-800">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-black text-slate-800 dark:text-slate-200">🎯 Canción de Entrada al Comedor / Salón</label>
                            <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950 px-2 py-0.5 rounded-full">Momento Clave</span>
                        </div>
                        
                        <!-- Canción seleccionada -->
                        <div x-show="selectedSong" class="flex items-center justify-between gap-2 p-2.5 bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 rounded-xl">
                            <div class="flex items-center gap-2 min-w-0 flex-1">
                                <span class="text-indigo-600 dark:text-indigo-400 text-sm">🎵</span>
                                <span class="text-xs font-bold text-slate-900 dark:text-white truncate" x-text="selectedSong"></span>
                            </div>
                            <button type="button" @click="clearSong()" class="text-rose-500 hover:text-rose-700 text-xs font-bold px-2 py-0.5 rounded hover:bg-rose-50 dark:hover:bg-rose-950 transition flex-shrink-0" title="Cambiar canción">
                                Cambiar
                            </button>
                        </div>

                        <!-- Buscador asistente de apoyo -->
                        <div class="relative">
                            <div class="flex items-center gap-2">
                                <input type="text" x-model="searchQuery" @input="onInput()" placeholder="🔍 Busca aquí la canción... Ej: Avicii, AC/DC, Dua Lipa..." class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3 py-2 focus:ring-2 focus:ring-indigo-500" autocomplete="off">
                                <span x-show="loading" class="text-xs text-indigo-500 animate-spin flex-shrink-0" style="display: none;">⏳</span>
                            </div>

                            <div x-show="showDropdown" @click.away="showDropdown = false" class="absolute z-30 w-full mt-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-2xl max-h-60 overflow-y-auto" style="display: none;">
                                <template x-for="song in results" :key="song.trackId">
                                    <div class="px-3.5 py-2 hover:bg-indigo-50 dark:hover:bg-slate-800 cursor-pointer flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 transition">
                                        <div @click="selectSong(song)" class="flex items-center gap-3 min-w-0 flex-1">
                                            <img :src="song.artworkUrl60 || song.artworkUrl30" class="w-7 h-7 rounded object-cover flex-shrink-0" alt="cover">
                                            <div class="min-w-0 flex-1">
                                                <div class="text-xs font-bold text-slate-800 dark:text-white truncate" x-text="song.trackName"></div>
                                                <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate" x-text="song.artistName"></div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1.5 flex-shrink-0">
                                            <template x-if="song.previewUrl">
                                                <button type="button" @click.stop="toggleAudio(song.previewUrl)" class="p-1 rounded text-xs bg-slate-100 dark:bg-slate-800 hover:bg-indigo-100 text-slate-600 dark:text-slate-300">
                                                    <span x-show="playingPreviewUrl === song.previewUrl">⏸️</span>
                                                    <span x-show="playingPreviewUrl !== song.previewUrl">🔊</span>
                                                </button>
                                            </template>
                                            <button type="button" @click="selectSong(song)" class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950 px-2 py-0.5 rounded">
                                                Elegir
                                            </button>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="searchQuery.trim().length > 1">
                                    <div @click="selectCustom()" class="px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950/90 hover:bg-indigo-50 dark:hover:bg-slate-800 cursor-pointer flex items-center justify-between gap-2 border-t border-slate-200 dark:border-slate-700 transition">
                                        <div class="flex items-center gap-2 truncate text-xs text-indigo-600 dark:text-indigo-400 font-bold">
                                            <span>➕</span>
                                            <span class="truncate">Usar lo que he escrito: "<span x-text="searchQuery" class="underline"></span>"</span>
                                        </div>
                                        <span class="text-[10px] font-semibold bg-indigo-100 dark:bg-indigo-900/60 text-indigo-800 dark:text-indigo-200 px-2 py-0.5 rounded flex-shrink-0">Personalizada</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Corte de tarta -->
                    <div x-data="singleSongPicker('cake_song')" class="space-y-2 p-3.5 bg-slate-50/70 dark:bg-slate-950/60 rounded-xl border border-slate-200/80 dark:border-slate-800">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-black text-slate-800 dark:text-slate-200">🍰 Canción para el Corte de Tarta (Opcional)</label>
                        </div>
                        
                        <!-- Canción seleccionada -->
                        <div x-show="selectedSong" class="flex items-center justify-between gap-2 p-2.5 bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 rounded-xl">
                            <div class="flex items-center gap-2 min-w-0 flex-1">
                                <span class="text-indigo-600 dark:text-indigo-400 text-sm">🎵</span>
                                <span class="text-xs font-bold text-slate-900 dark:text-white truncate" x-text="selectedSong"></span>
                            </div>
                            <button type="button" @click="clearSong()" class="text-rose-500 hover:text-rose-700 text-xs font-bold px-2 py-0.5 rounded hover:bg-rose-50 dark:hover:bg-rose-950 transition flex-shrink-0" title="Cambiar canción">
                                Cambiar
                            </button>
                        </div>

                        <!-- Buscador asistente de apoyo -->
                        <div class="relative">
                            <div class="flex items-center gap-2">
                                <input type="text" x-model="searchQuery" @input="onInput()" placeholder="🔍 Busca aquí la canción... Ej: Queen - Don't Stop Me Now, Coldplay..." class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3 py-2 focus:ring-2 focus:ring-indigo-500" autocomplete="off">
                                <span x-show="loading" class="text-xs text-indigo-500 animate-spin flex-shrink-0" style="display: none;">⏳</span>
                            </div>

                            <div x-show="showDropdown" @click.away="showDropdown = false" class="absolute z-30 w-full mt-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-2xl max-h-60 overflow-y-auto" style="display: none;">
                                <template x-for="song in results" :key="song.trackId">
                                    <div class="px-3.5 py-2 hover:bg-indigo-50 dark:hover:bg-slate-800 cursor-pointer flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 transition">
                                        <div @click="selectSong(song)" class="flex items-center gap-3 min-w-0 flex-1">
                                            <img :src="song.artworkUrl60 || song.artworkUrl30" class="w-7 h-7 rounded object-cover flex-shrink-0" alt="cover">
                                            <div class="min-w-0 flex-1">
                                                <div class="text-xs font-bold text-slate-800 dark:text-white truncate" x-text="song.trackName"></div>
                                                <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate" x-text="song.artistName"></div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1.5 flex-shrink-0">
                                            <template x-if="song.previewUrl">
                                                <button type="button" @click.stop="toggleAudio(song.previewUrl)" class="p-1 rounded text-xs bg-slate-100 dark:bg-slate-800 hover:bg-indigo-100 text-slate-600 dark:text-slate-300">
                                                    <span x-show="playingPreviewUrl === song.previewUrl">⏸️</span>
                                                    <span x-show="playingPreviewUrl !== song.previewUrl">🔊</span>
                                                </button>
                                            </template>
                                            <button type="button" @click="selectSong(song)" class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950 px-2 py-0.5 rounded">
                                                Elegir
                                            </button>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="searchQuery.trim().length > 1">
                                    <div @click="selectCustom()" class="px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950/90 hover:bg-indigo-50 dark:hover:bg-slate-800 cursor-pointer flex items-center justify-between gap-2 border-t border-slate-200 dark:border-slate-700 transition">
                                        <div class="flex items-center gap-2 truncate text-xs text-indigo-600 dark:text-indigo-400 font-bold">
                                            <span>➕</span>
                                            <span class="truncate">Usar lo que he escrito: "<span x-text="searchQuery" class="underline"></span>"</span>
                                        </div>
                                        <span class="text-[10px] font-semibold bg-indigo-100 dark:bg-indigo-900/60 text-indigo-800 dark:text-indigo-200 px-2 py-0.5 rounded flex-shrink-0">Personalizada</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Regalos y Sorpresas con Buscador -->
                    <div x-data="momentListBuilder('gifts_songs', 'Ramo de Novia')" class="space-y-3.5 p-3.5 bg-slate-50/70 dark:bg-slate-950/60 rounded-xl border border-slate-200/80 dark:border-slate-800">
                        <div>
                            <label class="block text-xs font-black text-slate-800 dark:text-slate-200">🎁 Regalos Especiales, Ramos o Sorpresas con música</label>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Añade cada entrega indicando a quién va dirigida y su canción.</p>
                        </div>

                        <!-- Selector rápido de tipo de regalo -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Destinatario / Motivo:</label>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="r in ['Ramo de Novia', 'Regalo Padres', 'Regalo Madres', 'Amigos / Testigos', 'Hermanos', 'Cumpleaños / Sorpresa']" :key="r">
                                    <button type="button" @click="setMoment(r); customMoment = ''" :class="activeMoment === r && !customMoment ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-100'" class="px-2 py-1 rounded-lg text-xs font-semibold transition">
                                        <span x-text="r"></span>
                                    </button>
                                </template>
                            </div>
                            <div class="mt-1.5">
                                <input type="text" x-model="customMoment" placeholder="O escribe otro motivo (ej: Regalo para mi abuela)..." class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-800 dark:text-white px-2.5 py-1.5">
                            </div>
                        </div>

                        <!-- Buscador de canciones para el regalo -->
                        <div class="relative">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                🔍 Buscar canción para <strong class="text-indigo-600 dark:text-indigo-400" x-text="customMoment || activeMoment || 'este regalo'"></strong>:
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="text" x-model="searchQuery" @input="onSearch()" placeholder="Buscar canción... Ej: Manuel Carrasco, Beyoncé, Melendi..." class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3 py-2 focus:ring-2 focus:ring-indigo-500" autocomplete="off">
                                <span x-show="loading" class="text-xs text-indigo-500 animate-spin flex-shrink-0" style="display: none;">⏳</span>
                            </div>

                            <div x-show="showDropdown" @click.away="showDropdown = false" class="absolute z-30 w-full mt-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-2xl max-h-60 overflow-y-auto" style="display: none;">
                                <template x-for="song in results" :key="song.trackId">
                                    <div class="px-3.5 py-2 hover:bg-indigo-50 dark:hover:bg-slate-800 cursor-pointer flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 transition">
                                        <div @click="addFromSearch(song)" class="flex items-center gap-3 min-w-0 flex-1">
                                            <img :src="song.artworkUrl60 || song.artworkUrl30" class="w-7 h-7 rounded object-cover flex-shrink-0" alt="cover">
                                            <div class="min-w-0 flex-1">
                                                <div class="text-xs font-bold text-slate-800 dark:text-white truncate" x-text="song.trackName"></div>
                                                <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate" x-text="song.artistName"></div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1.5 flex-shrink-0">
                                            <template x-if="song.previewUrl">
                                                <button type="button" @click.stop="toggleAudio(song.previewUrl)" class="p-1 rounded text-xs bg-slate-100 dark:bg-slate-800 hover:bg-indigo-100 text-slate-600 dark:text-slate-300">
                                                    <span x-show="playingPreviewUrl === song.previewUrl">⏸️</span>
                                                    <span x-show="playingPreviewUrl !== song.previewUrl">🔊</span>
                                                </button>
                                            </template>
                                            <button type="button" @click="addFromSearch(song)" class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950 px-2 py-0.5 rounded">
                                                + Añadir
                                            </button>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="searchQuery.trim().length > 1">
                                    <div @click="addCustomFromSearch()" class="px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950/90 hover:bg-indigo-50 dark:hover:bg-slate-800 cursor-pointer flex items-center justify-between gap-2 border-t border-slate-200 dark:border-slate-700 transition">
                                        <div class="flex items-center gap-2 truncate text-xs text-indigo-600 dark:text-indigo-400 font-bold">
                                            <span>➕</span>
                                            <span class="truncate">Añadir lo que he escrito: "<span x-text="searchQuery" class="underline"></span>"</span>
                                        </div>
                                        <span class="text-[10px] font-semibold bg-indigo-100 dark:bg-indigo-900/60 text-indigo-800 dark:text-indigo-200 px-2 py-0.5 rounded flex-shrink-0">Personalizada</span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Lista de regalos añadidos -->
                        <div class="space-y-1.5" x-show="items.length > 0">
                            <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Entregas y Regalos configurados (<span x-text="items.length"></span>):</span>
                            <div class="space-y-1.5 max-h-56 overflow-y-auto pr-1">
                                <template x-for="(item, idx) in items" :key="idx">
                                    <div class="flex items-center justify-between gap-2 p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl">
                                        <div class="flex items-center gap-2 min-w-0 flex-1">
                                            <span class="text-xs px-2 py-0.5 rounded-md font-bold bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300 flex-shrink-0" x-text="item.moment || 'Regalo'"></span>
                                            <span class="text-xs font-semibold text-slate-800 dark:text-white truncate" x-text="item.song"></span>
                                        </div>
                                        <button type="button" @click="remove(idx)" class="text-rose-500 hover:text-rose-700 text-xs font-bold px-2 py-0.5 rounded hover:bg-rose-50 dark:hover:bg-rose-950 transition flex-shrink-0">
                                            ✕
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Añadir manualmente -->
                        <div>
                            <div class="flex items-center gap-2">
                                <input type="text" x-model="manualSong" @keydown.enter.prevent="addManual()" placeholder="O escribe manualmente la canción / momento..." class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3 py-1.5 focus:ring-1 focus:ring-indigo-500">
                                <button type="button" @click="addManual()" class="px-2.5 py-1.5 bg-slate-200 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded-xl text-xs font-bold whitespace-nowrap">
                                    + Añadir
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 4: BAILE NUPCIAL Y FIESTA -->
                <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-6">
                    <div>
                        <h3 class="text-base font-black text-indigo-900 dark:text-indigo-300 border-b border-slate-100 dark:border-slate-800 pb-2.5 flex items-center gap-2">
                            <span>💃 4. Baile Nupcial y Fiesta</span>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">La apertura de baile y la lista de temazos que no pueden faltar en la fiesta.</p>
                    </div>

                    <!-- Baile Nupcial -->
                    <div x-data="singleSongPicker('dance_song')" class="space-y-2 p-3.5 bg-slate-50/70 dark:bg-slate-950/60 rounded-xl border border-slate-200/80 dark:border-slate-800">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-black text-slate-800 dark:text-slate-200">🎯 Canción del Baile Nupcial (Apertura de Baile)</label>
                            <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950 px-2 py-0.5 rounded-full">Momento Clave</span>
                        </div>
                        
                        <!-- Canción seleccionada -->
                        <div x-show="selectedSong" class="flex items-center justify-between gap-2 p-2.5 bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 rounded-xl">
                            <div class="flex items-center gap-2 min-w-0 flex-1">
                                <span class="text-indigo-600 dark:text-indigo-400 text-sm">🎵</span>
                                <span class="text-xs font-bold text-slate-900 dark:text-white truncate" x-text="selectedSong"></span>
                            </div>
                            <button type="button" @click="clearSong()" class="text-rose-500 hover:text-rose-700 text-xs font-bold px-2 py-0.5 rounded hover:bg-rose-50 dark:hover:bg-rose-950 transition flex-shrink-0" title="Cambiar canción">
                                Cambiar
                            </button>
                        </div>

                        <!-- Buscador asistente de apoyo -->
                        <div class="relative">
                            <div class="flex items-center gap-2">
                                <input type="text" x-model="searchQuery" @input="onInput()" placeholder="🔍 Busca aquí la canción del baile... Ej: Ed Sheeran, Elvis, Adele..." class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3 py-2 focus:ring-2 focus:ring-indigo-500" autocomplete="off">
                                <span x-show="loading" class="text-xs text-indigo-500 animate-spin flex-shrink-0" style="display: none;">⏳</span>
                            </div>

                            <div x-show="showDropdown" @click.away="showDropdown = false" class="absolute z-30 w-full mt-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-2xl max-h-60 overflow-y-auto" style="display: none;">
                                <template x-for="song in results" :key="song.trackId">
                                    <div class="px-3.5 py-2 hover:bg-indigo-50 dark:hover:bg-slate-800 cursor-pointer flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 transition">
                                        <div @click="selectSong(song)" class="flex items-center gap-3 min-w-0 flex-1">
                                            <img :src="song.artworkUrl60 || song.artworkUrl30" class="w-7 h-7 rounded object-cover flex-shrink-0" alt="cover">
                                            <div class="min-w-0 flex-1">
                                                <div class="text-xs font-bold text-slate-800 dark:text-white truncate" x-text="song.trackName"></div>
                                                <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate" x-text="song.artistName"></div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1.5 flex-shrink-0">
                                            <template x-if="song.previewUrl">
                                                <button type="button" @click.stop="toggleAudio(song.previewUrl)" class="p-1 rounded text-xs bg-slate-100 dark:bg-slate-800 hover:bg-indigo-100 text-slate-600 dark:text-slate-300">
                                                    <span x-show="playingPreviewUrl === song.previewUrl">⏸️</span>
                                                    <span x-show="playingPreviewUrl !== song.previewUrl">🔊</span>
                                                </button>
                                            </template>
                                            <button type="button" @click="selectSong(song)" class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950 px-2 py-0.5 rounded">
                                                Elegir
                                            </button>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="searchQuery.trim().length > 1">
                                    <div @click="selectCustom()" class="px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950/90 hover:bg-indigo-50 dark:hover:bg-slate-800 cursor-pointer flex items-center justify-between gap-2 border-t border-slate-200 dark:border-slate-700 transition">
                                        <div class="flex items-center gap-2 truncate text-xs text-indigo-600 dark:text-indigo-400 font-bold">
                                            <span>➕</span>
                                            <span class="truncate">Usar lo que he escrito: "<span x-text="searchQuery" class="underline"></span>"</span>
                                        </div>
                                        <span class="text-[10px] font-semibold bg-indigo-100 dark:bg-indigo-900/60 text-indigo-800 dark:text-indigo-200 px-2 py-0.5 rounded flex-shrink-0">Personalizada</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Temazos imprescindibles con Lista Interactiva -->
                    <div x-data="multiSongListBuilder('party_favs')" class="space-y-3.5">
                        <div>
                            <label class="block text-xs font-black text-slate-800 dark:text-slate-200">⭐ Temazos Imprescindibles para la Barra Libre / Fiesta</label>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Busca tantas canciones como queráis y pulsa para añadirlas a vuestra lista.</p>
                        </div>
                        
                        <!-- Buscador rápido -->
                        <div class="relative">
                            <div class="flex items-center gap-2">
                                <input type="text" x-model="searchQuery" @input="onSearch()" placeholder="🔍 Busca por canción o artista... Ej: Quevedo, Bizarrap, Bad Bunny, Estopa..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500" autocomplete="off">
                                <span x-show="loading" class="text-xs text-indigo-500 animate-spin flex-shrink-0" style="display: none;">⏳</span>
                            </div>

                            <div x-show="showDropdown" @click.away="showDropdown = false" class="absolute z-30 w-full mt-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-2xl max-h-64 overflow-y-auto" style="display: none;">
                                <template x-for="song in results" :key="song.trackId">
                                    <div class="px-4 py-2.5 hover:bg-indigo-50 dark:hover:bg-slate-800 cursor-pointer flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 transition">
                                        <div @click="addFromSearch(song)" class="flex items-center gap-3 min-w-0 flex-1">
                                            <img :src="song.artworkUrl60 || song.artworkUrl30" class="w-8 h-8 rounded-lg object-cover flex-shrink-0" alt="cover">
                                            <div class="min-w-0 flex-1">
                                                <div class="text-xs font-bold text-slate-800 dark:text-white truncate" x-text="song.trackName"></div>
                                                <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate" x-text="song.artistName"></div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1.5 flex-shrink-0">
                                            <template x-if="song.previewUrl">
                                                <button type="button" @click.stop="toggleAudio(song.previewUrl)" class="p-1.5 rounded-lg text-xs bg-slate-100 dark:bg-slate-800 hover:bg-indigo-100 text-slate-600 dark:text-slate-300" title="Escuchar fragmento">
                                                    <span x-show="playingPreviewUrl === song.previewUrl">⏸️</span>
                                                    <span x-show="playingPreviewUrl !== song.previewUrl">🔊</span>
                                                </button>
                                            </template>
                                            <button type="button" @click="addFromSearch(song)" class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/80 px-2.5 py-1 rounded-lg hover:bg-indigo-600 hover:text-white transition">
                                                + Añadir
                                            </button>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="searchQuery.trim().length > 1">
                                    <div @click="addCustomFromSearch()" class="px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950/90 hover:bg-indigo-50 dark:hover:bg-slate-800 cursor-pointer flex items-center justify-between gap-2 border-t border-slate-200 dark:border-slate-700 transition">
                                        <div class="flex items-center gap-2 truncate text-xs text-indigo-600 dark:text-indigo-400 font-bold">
                                            <span>➕</span>
                                            <span class="truncate">Añadir lo que he escrito: "<span x-text="searchQuery" class="underline"></span>"</span>
                                        </div>
                                        <span class="text-[10px] font-semibold bg-indigo-100 dark:bg-indigo-900/60 text-indigo-800 dark:text-indigo-200 px-2 py-0.5 rounded flex-shrink-0">Personalizada</span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Lista visual de canciones añadidas -->
                        <div class="space-y-1.5" x-show="songs.length > 0">
                            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Canciones añadidas a la fiesta (<span x-text="songs.length"></span>):</span>
                            <div class="space-y-1.5 max-h-60 overflow-y-auto pr-1">
                                <template x-for="(song, idx) in songs" :key="idx">
                                    <div class="flex items-center justify-between gap-2 p-2.5 bg-indigo-50/50 dark:bg-slate-950 border border-indigo-100 dark:border-slate-800 rounded-xl">
                                        <div class="flex items-center gap-2 min-w-0 flex-1">
                                            <span class="text-indigo-500 flex-shrink-0 text-xs">🔥</span>
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
                <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4" x-data="cocktailStyleBuilder('cocktail_songs')">
                    <div>
                        <h3 class="text-base font-black text-indigo-900 dark:text-indigo-300 border-b border-slate-100 dark:border-slate-800 pb-2.5 flex items-center gap-2">
                            <span>🎵 1. Estilo Musical & Ambiente de la Fiesta</span>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">¿Qué tipo de música queréis que predomine en el evento?</p>
                    </div>

                    <!-- Botones de estilos recomendados -->
                    <div>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="style in availableStyles" :key="style">
                                <button type="button" @click="toggleStyle(style)" :class="selectedStyles.includes(style) ? 'bg-indigo-600 text-white shadow-sm ring-2 ring-indigo-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'" class="px-2.5 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1">
                                    <span x-text="selectedStyles.includes(style) ? '✓' : '+'"></span>
                                    <span x-text="style"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Buscador -->
                    <div class="relative">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">🔍 Añadir canciones de ejemplo o artistas:</label>
                        <div class="flex items-center gap-2">
                            <input type="text" x-model="searchQuery" @input="onSearch()" placeholder="Buscar canción... Ej: Pop 80s, House, Reggaeton..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3.5 py-2 focus:ring-2 focus:ring-indigo-500" autocomplete="off">
                            <span x-show="loading" class="text-xs text-indigo-500 animate-spin flex-shrink-0" style="display: none;">⏳</span>
                        </div>

                        <div x-show="showDropdown" @click.away="showDropdown = false" class="absolute z-30 w-full mt-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-2xl max-h-60 overflow-y-auto" style="display: none;">
                            <template x-for="song in results" :key="song.trackId">
                                <div class="px-3.5 py-2 hover:bg-indigo-50 dark:hover:bg-slate-800 cursor-pointer flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 transition">
                                    <div @click="addFromSearch(song)" class="flex items-center gap-3 min-w-0 flex-1">
                                        <img :src="song.artworkUrl60 || song.artworkUrl30" class="w-8 h-8 rounded-lg object-cover flex-shrink-0" alt="cover">
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold text-slate-800 dark:text-white truncate" x-text="song.trackName"></div>
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate" x-text="song.artistName"></div>
                                        </div>
                                    </div>
                                    <button type="button" @click="addFromSearch(song)" class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950 px-2 py-1 rounded-lg">
                                        + Añadir
                                    </button>
                                </div>
                            </template>
                            <template x-if="searchQuery.trim().length > 1">
                                <div @click="addCustomFromSearch()" class="px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950/90 hover:bg-indigo-50 dark:hover:bg-slate-800 cursor-pointer flex items-center justify-between gap-2 border-t border-slate-200 dark:border-slate-700 transition">
                                    <div class="flex items-center gap-2 truncate text-xs text-indigo-600 dark:text-indigo-400 font-bold">
                                        <span>➕</span>
                                        <span class="truncate">Añadir lo que he escrito: "<span x-text="searchQuery" class="underline"></span>"</span>
                                    </div>
                                    <span class="text-[10px] font-semibold bg-indigo-100 dark:bg-indigo-900/60 text-indigo-800 dark:text-indigo-200 px-2 py-0.5 rounded flex-shrink-0">Personalizada</span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div>
                        <textarea x-model="customNotes" @input="sync()" rows="3" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 p-2.5 leading-relaxed" placeholder="Notas adicionales sobre estilos o preferencias..."></textarea>
                    </div>
                </div>

                <!-- SECCIÓN 2: TEMAZOS IMPRESCINDIBLES -->
                <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4" x-data="multiSongListBuilder('party_favs')">
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
                                <div class="px-4 py-2.5 hover:bg-indigo-50 dark:hover:bg-slate-800 cursor-pointer flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 transition">
                                    <div @click="addFromSearch(song)" class="flex items-center gap-3 min-w-0 flex-1">
                                        <img :src="song.artworkUrl60 || song.artworkUrl30" class="w-8 h-8 rounded-lg object-cover flex-shrink-0" alt="cover">
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold text-slate-800 dark:text-white truncate" x-text="song.trackName"></div>
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate" x-text="song.artistName"></div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5 flex-shrink-0">
                                        <template x-if="song.previewUrl">
                                            <button type="button" @click.stop="toggleAudio(song.previewUrl)" class="p-1.5 rounded-lg text-xs bg-slate-100 dark:bg-slate-800 hover:bg-indigo-100 text-slate-600 dark:text-slate-300" title="Escuchar fragmento">
                                                <span x-show="playingPreviewUrl === song.previewUrl">⏸️</span>
                                                <span x-show="playingPreviewUrl !== song.previewUrl">🔊</span>
                                            </button>
                                        </template>
                                        <button type="button" @click="addFromSearch(song)" class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/80 px-2.5 py-1 rounded-lg hover:bg-indigo-600 hover:text-white transition">
                                            + Añadir
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <template x-if="searchQuery.trim().length > 1">
                                <div @click="addCustomFromSearch()" class="px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950/90 hover:bg-indigo-50 dark:hover:bg-slate-800 cursor-pointer flex items-center justify-between gap-2 border-t border-slate-200 dark:border-slate-700 transition">
                                    <div class="flex items-center gap-2 truncate text-xs text-indigo-600 dark:text-indigo-400 font-bold">
                                        <span>➕</span>
                                        <span class="truncate">Añadir lo que he escrito: "<span x-text="searchQuery" class="underline"></span>"</span>
                                    </div>
                                    <span class="text-[10px] font-semibold bg-indigo-100 dark:bg-indigo-900/60 text-indigo-800 dark:text-indigo-200 px-2 py-0.5 rounded flex-shrink-0">Personalizada</span>
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
                <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4" x-data="momentListBuilder('special_moments', 'Entrada Protagonista')">
                    <div>
                        <h3 class="text-base font-black text-indigo-900 dark:text-indigo-300 border-b border-slate-100 dark:border-slate-800 pb-2.5 flex items-center gap-2">
                            <span>🎯 3. Momentos Especiales o Clave (Opcional)</span>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">¿Habrá algún momento especial? (Entrada del protagonista, tarta/velas, entrega de regalos, brindis...)</p>
                    </div>

                    <!-- Moment chips -->
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="m in ['Entrada Protagonista', 'Tarta / Velas', 'Brindis', 'Discurso / Entrega', 'Momento Especial']" :key="m">
                            <button type="button" @click="setMoment(m); customMoment = ''" :class="activeMoment === m && !customMoment ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300'" class="px-2.5 py-1 rounded-lg text-xs font-semibold">
                                <span x-text="m"></span>
                            </button>
                        </template>
                    </div>

                    <!-- Buscador -->
                    <div class="relative">
                        <div class="flex items-center gap-2">
                            <input type="text" x-model="searchQuery" @input="onSearch()" placeholder="Buscar canción para el momento... Ej: Survivor, Stevie Wonder, Queen..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-xs text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3 py-2 focus:ring-2 focus:ring-indigo-500" autocomplete="off">
                            <span x-show="loading" class="text-xs text-indigo-500 animate-spin flex-shrink-0" style="display: none;">⏳</span>
                        </div>

                        <div x-show="showDropdown" @click.away="showDropdown = false" class="absolute z-30 w-full mt-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-2xl max-h-60 overflow-y-auto" style="display: none;">
                            <template x-for="song in results" :key="song.trackId">
                                <div class="px-3.5 py-2 hover:bg-indigo-50 dark:hover:bg-slate-800 cursor-pointer flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 transition">
                                    <div @click="addFromSearch(song)" class="flex items-center gap-3 min-w-0 flex-1">
                                        <img :src="song.artworkUrl60 || song.artworkUrl30" class="w-7 h-7 rounded object-cover flex-shrink-0" alt="cover">
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold text-slate-800 dark:text-white truncate" x-text="song.trackName"></div>
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate" x-text="song.artistName"></div>
                                        </div>
                                    </div>
                                    <button type="button" @click="addFromSearch(song)" class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950 px-2 py-0.5 rounded">
                                        + Añadir
                                    </button>
                                </div>
                            </template>
                            <template x-if="searchQuery.trim().length > 1">
                                <div @click="addCustomFromSearch()" class="px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950/90 hover:bg-indigo-50 dark:hover:bg-slate-800 cursor-pointer flex items-center justify-between gap-2 border-t border-slate-200 dark:border-slate-700 transition">
                                    <div class="flex items-center gap-2 truncate text-xs text-indigo-600 dark:text-indigo-400 font-bold">
                                        <span>➕</span>
                                        <span class="truncate">Añadir lo que he escrito: "<span x-text="searchQuery" class="underline"></span>"</span>
                                    </div>
                                    <span class="text-[10px] font-semibold bg-indigo-100 dark:bg-indigo-900/60 text-indigo-800 dark:text-indigo-200 px-2 py-0.5 rounded flex-shrink-0">Personalizada</span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Items list -->
                    <div class="space-y-1.5" x-show="items.length > 0">
                        <div class="space-y-1.5 max-h-56 overflow-y-auto pr-1">
                            <template x-for="(item, idx) in items" :key="idx">
                                <div class="flex items-center justify-between gap-2 p-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl">
                                    <div class="flex items-center gap-2 min-w-0 flex-1">
                                        <span class="text-xs px-2 py-0.5 rounded-md font-bold bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 flex-shrink-0" x-text="item.moment || 'Especial'"></span>
                                        <span class="text-xs font-semibold text-slate-800 dark:text-white truncate" x-text="item.song"></span>
                                    </div>
                                    <button type="button" @click="remove(idx)" class="text-rose-500 hover:text-rose-700 text-xs font-bold px-2 py-0.5 rounded">
                                        ✕
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

            @endif

            <!-- SECCIÓN COMÚN: LISTA NEGRA -->
            <div class="bg-rose-50/40 dark:bg-rose-950/20 p-5 sm:p-6 rounded-2xl border border-rose-200 dark:border-rose-900/60 shadow-sm space-y-3.5" x-data="multiSongListBuilder('blacklist')">
                <div>
                    <h3 class="text-base font-black text-rose-800 dark:text-rose-300 border-b border-rose-200/80 dark:border-rose-900/60 pb-2.5 flex items-center gap-2">
                        <span>🚫 Lista Negra (Canciones o estilos PROHIBIDOS)</span>
                    </h3>
                    <p class="text-xs text-rose-700/90 dark:text-rose-400 mt-1">Música, géneros o canciones que <strong>NO queréis que suenen</strong> bajo ningún concepto.</p>
                </div>

                <!-- Buscador de canciones para prohibir -->
                <div class="relative">
                    <div class="flex items-center gap-2">
                        <input type="text" x-model="searchQuery" @input="onSearch()" placeholder="🔍 Busca una canción o artista para prohibir..." class="w-full bg-white dark:bg-slate-950 border border-rose-300 dark:border-rose-800/80 rounded-xl text-xs text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3 py-2 focus:ring-2 focus:ring-rose-500" autocomplete="off">
                        <span x-show="loading" class="text-xs text-rose-500 animate-spin flex-shrink-0" style="display: none;">⏳</span>
                    </div>

                    <div x-show="showDropdown" @click.away="showDropdown = false" class="absolute z-30 w-full mt-1 bg-white dark:bg-slate-900 border border-rose-200 dark:border-rose-800 rounded-xl shadow-2xl max-h-60 overflow-y-auto" style="display: none;">
                        <template x-for="song in results" :key="song.trackId">
                            <div class="px-3.5 py-2 hover:bg-rose-50 dark:hover:bg-rose-950/50 cursor-pointer flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 transition">
                                <div @click="addFromSearch(song)" class="flex items-center gap-3 min-w-0 flex-1">
                                    <img :src="song.artworkUrl60 || song.artworkUrl30" class="w-7 h-7 rounded object-cover flex-shrink-0" alt="cover">
                                    <div class="min-w-0 flex-1">
                                        <div class="text-xs font-bold text-slate-800 dark:text-white truncate" x-text="song.trackName"></div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate" x-text="song.artistName"></div>
                                    </div>
                                </div>
                                <button type="button" @click="addFromSearch(song)" class="text-[11px] font-bold text-rose-600 bg-rose-50 dark:bg-rose-950 px-2 py-0.5 rounded">
                                    🚫 Prohibir
                                </button>
                            </div>
                        </template>
                        <template x-if="searchQuery.trim().length > 1">
                            <div @click="addCustomFromSearch()" class="px-3.5 py-2.5 bg-rose-50/80 dark:bg-rose-950/90 hover:bg-rose-100 dark:hover:bg-rose-900/60 cursor-pointer flex items-center justify-between gap-2 border-t border-rose-200 dark:border-rose-800 transition">
                                <div class="flex items-center gap-2 truncate text-xs text-rose-700 dark:text-rose-300 font-bold">
                                    <span>🚫</span>
                                    <span class="truncate">Prohibir lo que he escrito: "<span x-text="searchQuery" class="underline"></span>"</span>
                                </div>
                                <span class="text-[10px] font-semibold bg-rose-100 dark:bg-rose-900/60 text-rose-800 dark:text-rose-200 px-2 py-0.5 rounded flex-shrink-0">Personalizada</span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Lista de prohibidas -->
                <div class="space-y-1.5" x-show="songs.length > 0">
                    <span class="text-[10px] font-bold text-rose-800 dark:text-rose-300 uppercase tracking-wider block">Canciones o estilos prohibidos (<span x-text="songs.length"></span>):</span>
                    <div class="space-y-1.5 max-h-56 overflow-y-auto pr-1">
                        <template x-for="(song, idx) in songs" :key="idx">
                            <div class="flex items-center justify-between gap-2 p-2 bg-white dark:bg-slate-900 border border-rose-200 dark:border-rose-900/60 rounded-xl">
                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                    <span class="text-rose-500 text-xs">🚫</span>
                                    <span class="text-xs font-bold text-slate-800 dark:text-white truncate" x-text="song"></span>
                                </div>
                                <button type="button" @click="remove(idx)" class="text-rose-500 hover:text-rose-700 text-xs font-bold px-2 py-0.5 rounded">
                                    ✕
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Añadir manualmente estilo o descripción prohibida -->
                <div>
                    <div class="flex items-center gap-2">
                        <input type="text" x-model="manualInput" @keydown.enter.prevent="addManual()" placeholder="O escribe un género prohibido (ej: Reggaeton antiguo, Paquito Chocolatero)..." class="w-full bg-white dark:bg-slate-950 border border-rose-200 dark:border-rose-800 rounded-xl text-xs text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3 py-1.5 focus:ring-1 focus:ring-rose-500">
                        <button type="button" @click="addManual()" class="px-2.5 py-1.5 bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-200 rounded-xl text-xs font-bold whitespace-nowrap">
                            + Añadir
                        </button>
                    </div>
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
                <button type="submit" wire:loading.attr="disabled" class="w-full flex justify-center items-center gap-2 py-4 px-6 rounded-2xl shadow-lg shadow-indigo-600/20 text-base font-extrabold text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-75 disabled:cursor-not-allowed focus:outline-none focus:ring-4 focus:ring-indigo-300 transition cursor-pointer transform hover:-translate-y-0.5">
                    <span wire:loading.remove wire:target="submitForm">🚀 Guardar y Enviar Preferencias</span>
                    <span wire:loading wire:target="submitForm" class="inline-flex items-center gap-2" style="display: none;">
                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Guardando y notificando al DJ...</span>
                    </span>
                </button>
            </div>
        </form>
    @endif

    <!-- MODAL FLOTANTE DE CONFIRMACIÓN DE ENVÍO -->
    @if($showSuccessModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-success-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
            <!-- Backdrop con desenfoque suave -->
            <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" wire:click="closeSuccessModal"></div>

            <div class="relative bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full border border-emerald-200 dark:border-emerald-800 animate-in fade-in zoom-in-95 duration-200">
                <!-- Cabecera Festiva / Verde Esmeralda -->
                <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-indigo-600 px-6 py-6 text-center text-white relative">
                    <button type="button" wire:click="closeSuccessModal" class="absolute top-4 right-4 text-white/80 hover:text-white text-2xl font-bold leading-none cursor-pointer">&times;</button>
                    
                    <div class="w-16 h-16 mx-auto rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center text-3xl shadow-inner mb-3">
                        🎉
                    </div>
                    <h3 class="text-xl sm:text-2xl font-black tracking-tight" id="modal-success-title">
                        ¡Preferencias Guardadas con Éxito!
                    </h3>
                    <p class="text-xs text-emerald-100 mt-1 font-medium">
                        {{ $event->name }} &bull; {{ $event->event_date ? $event->event_date->format('d/m/Y') : 'Fecha confirmada' }}
                    </p>
                </div>

                <div class="p-6 text-center space-y-4">
                    <div class="inline-flex items-center gap-2 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 px-4 py-2 rounded-full text-xs font-bold">
                        <span>✅</span>
                        <span>{{ $totalSavedSongs }} momentos / canciones registrados</span>
                    </div>

                    <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed">
                        Hemos recibido y procesado vuestra selección musical correctamente. Tanto el equipo de <strong>{{ \App\Models\Setting::getCompanyName('Eventos Musicales') }}</strong> como el <strong>DJ asignado</strong> ya tienen acceso a todas vuestras canciones para preparar la sesión.
                    </p>

                    <div class="bg-slate-50 dark:bg-slate-950 p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 text-xs text-slate-600 dark:text-slate-400">
                        💡 <em>Podéis volver a acceder a este enlace en cualquier momento para añadir nuevas canciones o hacer cambios si lo necesitáis.</em>
                    </div>
                </div>

                <div class="bg-slate-50 dark:bg-slate-800/80 px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row justify-center gap-2.5">
                    <button type="button" wire:click="closeSuccessModal" class="w-full sm:w-auto px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl shadow-md shadow-emerald-600/20 transition cursor-pointer">
                        👍 ¡Entendido, todo listo!
                    </button>
                    <button type="button" wire:click="closeSuccessModal" class="w-full sm:w-auto px-5 py-3 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 font-bold text-sm rounded-xl transition cursor-pointer">
                        ✏️ Seguir revisando
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
