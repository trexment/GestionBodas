<div
    class="min-h-screen w-full transition-colors duration-500 flex flex-col justify-between relative overflow-hidden select-none font-sans"
    :class="{
        'bg-[#080d1a] text-slate-100': @js($theme === 'dark'),
        'bg-[#140e0a] text-amber-50': @js($theme === 'gold'),
        'bg-[#fafaf8] text-slate-900': @js($theme === 'light')
    }"
    x-data="{
        isFullscreen: false,
        wakeLockActive: false,
        kioskMode: @entangle('kioskMode'),
        wakeLockSentinel: null,
        async initWakeLock() {
            if ('wakeLock' in navigator) {
                try {
                    this.wakeLockSentinel = await navigator.wakeLock.request('screen');
                    this.wakeLockActive = true;
                    this.wakeLockSentinel.addEventListener('release', () => {
                        this.wakeLockActive = false;
                    });
                } catch (err) {
                    console.log('WakeLock error:', err);
                }
            }
        },
        toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().then(() => {
                    this.isFullscreen = true;
                }).catch(err => console.log(err));
            } else {
                document.exitFullscreen().then(() => {
                    this.isFullscreen = false;
                });
            }
        }
    }"
    x-init="
        initWakeLock();
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                initWakeLock();
            }
        });
        document.addEventListener('fullscreenchange', () => {
            isFullscreen = !!document.fullscreenElement;
        });
    "
    wire:poll.25s="refreshPhotoCount"
>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600;1,700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap');
        .font-serif-luxury { font-family: 'Playfair Display', Georgia, serif; }
        .font-sans-clean { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>

    <!-- Fondo ambiental decorativo -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden">
        @if($theme === 'dark')
            <div class="absolute -top-40 -left-40 w-96 h-96 bg-indigo-600/15 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 -right-40 w-96 h-96 bg-purple-600/15 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-40 left-1/3 w-96 h-96 bg-pink-600/10 rounded-full blur-3xl"></div>
        @elseif($theme === 'gold')
            <div class="absolute -top-40 -left-40 w-96 h-96 bg-amber-500/15 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 -right-40 w-96 h-96 bg-orange-600/15 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-40 left-1/3 w-96 h-96 bg-yellow-500/10 rounded-full blur-3xl"></div>
        @else
            <div class="absolute -top-40 -left-40 w-96 h-96 bg-amber-200/30 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 -right-40 w-96 h-96 bg-indigo-200/25 rounded-full blur-3xl"></div>
        @endif
    </div>

    <!-- Barra Superior Flotante de Controles Tablet (se oculta en Modo Kiosko) -->
    <div
        class="relative z-30 transition-all duration-300"
        x-show="!kioskMode"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
    >
        <div class="max-w-7xl mx-auto px-4 sm:px-6 pt-3 sm:pt-4 flex items-center justify-between gap-3">
            <!-- Selector de Marca / Evento -->
            <div class="flex items-center gap-2">
                <span class="text-xs font-black tracking-wider uppercase px-2.5 py-1 rounded-full {{ $theme === 'light' ? 'bg-slate-200 text-slate-700' : 'bg-white/10 text-white/80' }}">
                    📺 Modo Pantalla Tablet
                </span>
                <span class="text-xs font-bold opacity-75 hidden sm:inline-block">
                    {{ $event->brand_clean === 'javnx' ? '🎧 JAVNX' : '👑 Núñez & Son' }}
                </span>
            </div>

            <!-- Botones de Acción Rápida para Tablet -->
            <div class="flex items-center gap-1.5 sm:gap-2">
                <!-- Estado WakeLock (Pantalla Encendida) -->
                <button
                    type="button"
                    @click="initWakeLock()"
                    class="px-2.5 py-1 rounded-xl text-[11px] font-bold border transition flex items-center gap-1.5 cursor-pointer {{ $theme === 'light' ? 'bg-white border-slate-200 text-slate-700' : 'bg-slate-900/80 border-slate-800 text-slate-300' }}"
                    title="Evita que la tablet apague la pantalla durante el evento"
                >
                    <span class="w-2 h-2 rounded-full" :class="wakeLockActive ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400'"></span>
                    <span class="hidden md:inline" x-text="wakeLockActive ? 'Pantalla Despierta (WakeLock)' : 'Activar WakeLock'"></span>
                    <span class="md:hidden">💡</span>
                </button>

                <!-- Selector de Tema -->
                <div class="inline-flex p-0.5 rounded-xl border {{ $theme === 'light' ? 'bg-slate-100 border-slate-300' : 'bg-slate-900/80 border-slate-800' }}">
                    <button
                        type="button"
                        wire:click="setTheme('dark')"
                        class="px-2 py-1 rounded-lg text-xs font-bold transition cursor-pointer {{ $theme === 'dark' ? 'bg-indigo-600 text-white shadow-xs' : 'opacity-60 hover:opacity-100' }}"
                        title="Tema Noche / Fiesta"
                    >
                        🌙 Noche
                    </button>
                    <button
                        type="button"
                        wire:click="setTheme('gold')"
                        class="px-2 py-1 rounded-lg text-xs font-bold transition cursor-pointer {{ $theme === 'gold' ? 'bg-amber-600 text-white shadow-xs' : 'opacity-60 hover:opacity-100' }}"
                        title="Tema Boda Dorada"
                    >
                        ✨ Oro
                    </button>
                    <button
                        type="button"
                        wire:click="setTheme('light')"
                        class="px-2 py-1 rounded-lg text-xs font-bold transition cursor-pointer {{ $theme === 'light' ? 'bg-white text-slate-900 shadow-xs' : 'opacity-60 hover:opacity-100' }}"
                        title="Tema Día / Blanco"
                    >
                        ☀️ Día
                    </button>
                </div>

                <!-- Botón Pantalla Completa (F11) -->
                <button
                    type="button"
                    @click="toggleFullscreen()"
                    class="px-2.5 py-1 rounded-xl text-xs font-bold border transition flex items-center gap-1 cursor-pointer {{ $theme === 'light' ? 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50' : 'bg-slate-900/80 border-slate-800 text-slate-200 hover:bg-slate-800' }}"
                    title="Poner en pantalla completa sin barras del navegador"
                >
                    <span x-text="isFullscreen ? '⛶ Reducir' : '⛶ Pantalla Completa'"></span>
                </button>

                <!-- Modo Kiosko (Ocultar botones para los invitados) -->
                <button
                    type="button"
                    @click="kioskMode = true"
                    class="px-2.5 py-1 rounded-xl text-xs font-bold border transition flex items-center gap-1 cursor-pointer {{ $theme === 'light' ? 'bg-slate-900 text-white border-slate-900' : 'bg-indigo-600 text-white border-indigo-500 shadow-sm' }}"
                    title="Oculta esta barra para dejar la tablet fija en el evento sin que nadie toque"
                >
                    <span>🔒 Modo Kiosko</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Mini Botón Flotante para Salir del Modo Kiosko -->
    <div
        x-show="kioskMode"
        class="fixed top-3 right-3 z-40 opacity-20 hover:opacity-100 transition-opacity"
    >
        <button
            type="button"
            @click="kioskMode = false"
            class="px-3 py-1.5 rounded-full text-xs font-bold bg-slate-900/90 text-white border border-slate-700 shadow-lg cursor-pointer flex items-center gap-1"
        >
            <span>🔓 Salir Kiosko</span>
        </button>
    </div>

    <!-- CUERPO PRINCIPAL: DISTRIBUCIÓN ADAPTADA A TABLET 12" (LENOVO TAB / IPAD) -->
    <div class="relative z-10 max-w-7xl mx-auto w-full px-4 sm:px-8 lg:px-12 py-4 sm:py-6 my-auto flex-1 flex items-center">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 w-full items-center">

            <!-- COLUMNA IZQUIERDA: IDENTIDAD DEL EVENTO Y LLAMADA A LA ACCIÓN -->
            <div class="lg:col-span-7 flex flex-col justify-center space-y-4 sm:space-y-6 text-center lg:text-left">

                <!-- Monograma / Iniciales & Fecha -->
                <div class="flex flex-wrap items-center justify-center lg:justify-start gap-2.5">
                    @if($initials !== '')
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl flex items-center justify-center font-serif-luxury font-bold text-base sm:text-lg tracking-wider border shadow-sm {{ $theme === 'gold' ? 'bg-amber-900/40 border-amber-500/50 text-amber-200' : ($theme === 'light' ? 'bg-amber-100 border-amber-300 text-amber-900' : 'bg-indigo-950/60 border-indigo-400/40 text-indigo-300') }}">
                            {{ $initials }}
                        </div>
                    @endif

                    <div class="text-left">
                        <span class="text-[11px] sm:text-xs font-black tracking-widest uppercase block {{ $theme === 'gold' ? 'text-amber-400' : ($theme === 'light' ? 'text-indigo-600' : 'text-indigo-400') }}">
                            ÁLBUM COLABORATIVO DE FOTOS
                        </span>
                        @if($dateFormatted !== '')
                            <span class="text-xs font-semibold opacity-75 block">
                                📅 {{ $dateFormatted }}{{ $location ? ' · ' . $location : '' }}
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Nombres de los Novios / Evento -->
                <div>
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-serif-luxury font-bold tracking-tight leading-tight">
                        {{ $names }}
                    </h1>
                </div>

                <!-- Titular de Invitación -->
                <div class="space-y-2">
                    <h2 class="text-xl sm:text-2xl lg:text-3xl font-extrabold tracking-tight {{ $theme === 'gold' ? 'text-amber-200' : ($theme === 'light' ? 'text-slate-900' : 'text-white') }}">
                        {{ $headline }}
                    </h2>
                    <p class="text-sm sm:text-base opacity-80 max-w-xl mx-auto lg:mx-0 leading-relaxed font-normal">
                        {{ $subtitle }}
                    </p>
                </div>

                <!-- 3 PASOS VISUALES PARA LOS INVITADOS -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 sm:gap-3 pt-2">
                    <!-- Paso 1 -->
                    <div class="p-3.5 sm:p-4 rounded-2xl border text-left transition {{ $theme === 'gold' ? 'bg-amber-950/30 border-amber-900/50' : ($theme === 'light' ? 'bg-white border-slate-200 shadow-xs' : 'bg-slate-900/60 border-slate-800') }}">
                        <div class="text-2xl mb-1.5">📱</div>
                        <div class="text-xs font-black uppercase tracking-wider mb-0.5 opacity-90">1. Abre tu móvil</div>
                        <div class="text-xs opacity-75">Abre la cámara normal, sin descargar apps.</div>
                    </div>

                    <!-- Paso 2 -->
                    <div class="p-3.5 sm:p-4 rounded-2xl border text-left transition {{ $theme === 'gold' ? 'bg-amber-950/30 border-amber-900/50' : ($theme === 'light' ? 'bg-white border-slate-200 shadow-xs' : 'bg-slate-900/60 border-slate-800') }}">
                        <div class="text-2xl mb-1.5">📷</div>
                        <div class="text-xs font-black uppercase tracking-wider mb-0.5 opacity-90">2. Enfoca el QR</div>
                        <div class="text-xs opacity-75">Toca el enlace que aparecerá en pantalla.</div>
                    </div>

                    <!-- Paso 3 -->
                    <div class="p-3.5 sm:p-4 rounded-2xl border text-left transition {{ $theme === 'gold' ? 'bg-amber-950/30 border-amber-900/50' : ($theme === 'light' ? 'bg-white border-slate-200 shadow-xs' : 'bg-slate-900/60 border-slate-800') }}">
                        <div class="text-2xl mb-1.5">✨</div>
                        <div class="text-xs font-black uppercase tracking-wider mb-0.5 opacity-90">3. ¡Sube tus fotos!</div>
                        <div class="text-xs opacity-75">En calidad original directamente para los novios.</div>
                    </div>
                </div>

                <!-- BADGE EN VIVO: CONTADOR Y ESTADO -->
                <div class="pt-2 flex flex-wrap items-center justify-center lg:justify-start gap-3">
                    @if($isActive)
                        <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-black {{ $theme === 'gold' ? 'bg-amber-900/40 text-amber-300 border border-amber-700/60' : ($theme === 'light' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-emerald-950/70 text-emerald-300 border border-emerald-700/80') }}">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>🟢 Álbum en directo abierto</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-black bg-rose-950/70 text-rose-300 border border-rose-700/80">
                            <span>🔴 Subida temporalmente cerrada</span>
                        </span>
                    @endif

                    @if($photosCount !== null)
                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-extrabold border {{ $theme === 'gold' ? 'bg-amber-950/60 border-amber-800 text-amber-200' : ($theme === 'light' ? 'bg-slate-100 border-slate-300 text-slate-800' : 'bg-indigo-950/60 border-indigo-800 text-indigo-300') }}">
                            <span>📸</span>
                            <span class="font-black text-sm">{{ $photosCount }}</span>
                            <span>{{ $photosCount === 1 ? 'foto compartida' : 'fotos compartidas' }}</span>
                        </span>
                    @endif
                </div>

            </div>

            <!-- COLUMNA DERECHA: CÓDIGO QR GIGANTE ESTILIZADO -->
            <div class="lg:col-span-5 flex flex-col items-center justify-center">

                <!-- TARJETA ILUMINADA DEL CÓDIGO QR -->
                <div class="relative w-full max-w-sm sm:max-w-md mx-auto">
                    <!-- Glow decorativo posterior -->
                    <div class="absolute -inset-1 rounded-3xl opacity-50 blur-lg transition duration-1000 group-hover:opacity-100 {{ $theme === 'gold' ? 'bg-gradient-to-r from-amber-500 to-orange-500' : ($theme === 'light' ? 'bg-gradient-to-r from-amber-300 to-indigo-300' : 'bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500') }}"></div>

                    <!-- Contenedor blanco puro para contraste perfecto de cámara -->
                    <div class="relative bg-white rounded-3xl p-6 sm:p-8 shadow-2xl border-4 text-center {{ $theme === 'gold' ? 'border-amber-400' : ($theme === 'light' ? 'border-amber-300' : 'border-indigo-400/80') }}">

                        <!-- Indicador superior -->
                        <div class="mb-3 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-[11px] font-extrabold tracking-wide uppercase">
                            <span>✨</span>
                            <span>Escanea aquí</span>
                        </div>

                        <!-- CÓDIGO QR VECTORIAL GIGANTE -->
                        <div class="w-full aspect-square max-w-[280px] sm:max-w-[320px] mx-auto flex items-center justify-center bg-white rounded-2xl overflow-hidden p-1 shadow-inner">
                            {!! $qrSvg !!}
                        </div>

                        <!-- Enlace Web Limpio de Apoyo -->
                        @if($shortDisplayUrl !== '')
                            <div class="mt-4 pt-3 border-t border-slate-100">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">
                                    O accede directamente en tu navegador:
                                </span>
                                <span class="text-xs font-mono font-bold text-indigo-700 select-all">
                                    {{ $shortDisplayUrl }}
                                </span>
                            </div>
                        @endif

                    </div>
                </div>

                <!-- Pie de Apoyo para Invitados -->
                <div class="mt-3 text-center text-xs opacity-60">
                    💡 Mantén tu móvil a unos 30-50 cm de la pantalla para enfocar
                </div>

            </div>

        </div>
    </div>

    <!-- PIE INFERIOR DISCRETO -->
    <div class="relative z-10 py-3 text-center border-t text-[11px] opacity-50 {{ $theme === 'light' ? 'border-slate-200' : 'border-white/10' }}">
        <span>{{ $names }} · Producción por {{ $event->brand_clean === 'javnx' ? 'JAVNX DJ' : 'Núñez & Son' }}</span>
    </div>
</div>
