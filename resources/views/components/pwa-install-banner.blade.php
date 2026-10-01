<!-- PWA INSTALL BANNER & STEP-BY-STEP MODAL GUIDE -->
<div 
    x-data="{
        deferredPrompt: null,
        canInstallNatively: false,
        showBanner: false,
        showModal: false,
        activeTab: 'android',
        isStandalone: false,
        isIOS: false,
        isAndroid: false,

        init() {
            // Detectar si ya se está ejecutando como PWA instalada
            this.isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
            
            // Detectar sistema operativo
            const ua = navigator.userAgent || navigator.vendor || window.opera;
            this.isIOS = /iPad|iPhone|iPod/.test(ua) && !window.MSStream;
            this.isAndroid = /android/i.test(ua);
            
            if (this.isIOS) {
                this.activeTab = 'ios';
            } else if (this.isAndroid) {
                this.activeTab = 'android';
            } else {
                this.activeTab = 'desktop';
            }

            // Si ya está instalada, no mostrar banner
            if (this.isStandalone) {
                return;
            }

            // Capturar evento nativo de Chrome / Android para instalación directa en 1 clic
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                this.deferredPrompt = e;
                this.canInstallNatively = true;
                
                // Mostrar banner si no se ha cerrado recientemente
                const dismissed = localStorage.getItem('pwa_banner_dismissed_time');
                if (!dismissed || (Date.now() - parseInt(dismissed)) > (24 * 60 * 60 * 1000)) {
                    this.showBanner = true;
                }
            });

            // Si es iOS o Android donde no saltó el prompt, mostrar banner amigable pasados unos segundos
            setTimeout(() => {
                if (!this.isStandalone) {
                    const dismissed = localStorage.getItem('pwa_banner_dismissed_time');
                    if (!dismissed || (Date.now() - parseInt(dismissed)) > (3 * 24 * 60 * 60 * 1000)) {
                        this.showBanner = true;
                    }
                }
            }, 3000);

            // Escuchar evento global para abrir guía desde cualquier botón
            window.addEventListener('open-pwa-guide', () => {
                this.showModal = true;
            });
        },

        async triggerInstall() {
            if (this.deferredPrompt) {
                this.deferredPrompt.prompt();
                const { outcome } = await this.deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    this.showBanner = false;
                    this.showModal = false;
                }
                this.deferredPrompt = null;
            } else {
                // Si el navegador no permite el prompt nativo directamente (ej: iOS o Chrome sin permiso directo), abrir guía ilustrada
                this.showModal = true;
            }
        },

        dismissBanner() {
            this.showBanner = false;
            localStorage.setItem('pwa_banner_dismissed_time', Date.now().toString());
        }
    }"
    x-cloak
>
    <!-- BANNER FLOTANTE INFERIOR -->
    <template x-if="showBanner && !isStandalone">
        <div class="fixed bottom-4 left-4 right-4 sm:left-auto sm:right-6 sm:max-w-md z-40 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 border border-indigo-500/40 text-white p-4 rounded-2xl shadow-2xl backdrop-blur-md transition-all duration-300 animate-bounce-subtle">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-xl shrink-0 shadow-md shadow-indigo-600/50">
                    📱
                </div>
                <div class="flex-1 pr-2">
                    <h4 class="text-xs font-black uppercase tracking-wider text-indigo-300 flex items-center gap-1.5">
                        <span>Instalar App en tu Móvil o Tablet</span>
                    </h4>
                    <p class="text-xs text-slate-300 mt-1 leading-relaxed">
                        Accede a pantalla completa, con rotación horizontal para cabina y sin barras de navegador.
                    </p>
                    <div class="flex flex-wrap items-center gap-2 mt-3">
                        <button 
                            type="button" 
                            @click="triggerInstall()" 
                            class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-md shadow-indigo-600/40 transition flex items-center gap-1.5 cursor-pointer"
                        >
                            <span>📲</span>
                            <span x-text="canInstallNatively ? 'Instalar en 1 Clic' : 'Instalar / Añadir a Pantalla'"></span>
                        </button>
                        <button 
                            type="button" 
                            @click="showModal = true" 
                            class="px-3 py-1.5 bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white rounded-xl text-xs font-bold border border-slate-700 transition flex items-center gap-1 cursor-pointer"
                        >
                            <span>ℹ️</span> Ver Guía
                        </button>
                    </div>
                </div>
                <button 
                    type="button" 
                    @click="dismissBanner()" 
                    class="text-slate-400 hover:text-white text-lg font-bold p-1 leading-none transition"
                    title="Cerrar aviso"
                >
                    &times;
                </button>
            </div>
        </div>
    </template>

    <!-- MODAL GUÍA PASO A PASO -->
    <div 
        x-show="showModal" 
        class="fixed inset-0 z-50 overflow-y-auto" 
        style="display: none;"
        aria-labelledby="modal-pwa-title" 
        role="dialog" 
        aria-modal="true"
    >
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
            <!-- Backdrop -->
            <div 
                class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" 
                @click="showModal = false"
            ></div>

            <!-- Modal Panel -->
            <div class="relative inline-block align-bottom bg-slate-900 border border-slate-700 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full text-slate-100">
                
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-indigo-900 via-purple-900 to-slate-900 p-5 flex items-center justify-between border-b border-slate-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center text-2xl">
                            📲
                        </div>
                        <div>
                            <h3 class="font-black text-base text-white leading-tight">
                                Cómo Instalar la App en tu Dispositivo
                            </h3>
                            <p class="text-xs text-indigo-200">
                                Funciona en Android, Tablet, iPhone, iPad y PC
                            </p>
                        </div>
                    </div>
                    <button 
                        type="button" 
                        @click="showModal = false" 
                        class="text-slate-400 hover:text-white text-2xl font-bold p-1"
                    >
                        &times;
                    </button>
                </div>

                <!-- Botón de instalación nativa si el navegador lo soporta -->
                <template x-if="canInstallNatively">
                    <div class="p-4 bg-indigo-950/70 border-b border-indigo-800/50 flex items-center justify-between gap-3">
                        <div class="text-xs text-indigo-200">
                            <strong>Tu navegador permite instalación directa:</strong>
                        </div>
                        <button 
                            type="button" 
                            @click="triggerInstall()" 
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-black shadow-lg transition flex items-center gap-1.5 cursor-pointer"
                        >
                            <span>⚡</span> Instalar Ahora
                        </button>
                    </div>
                </template>

                <!-- Pestañas de Sistemas Operativos -->
                <div class="flex border-b border-slate-800 bg-slate-950/60 p-1.5 gap-1 text-xs font-bold">
                    <button 
                        type="button" 
                        @click="activeTab = 'android'" 
                        class="flex-1 py-2 px-3 rounded-xl transition flex items-center justify-center gap-1.5 cursor-pointer"
                        :class="activeTab === 'android' ? 'bg-emerald-600 text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800'"
                    >
                        <span>🤖</span> Android / Tablet
                    </button>
                    <button 
                        type="button" 
                        @click="activeTab = 'ios'" 
                        class="flex-1 py-2 px-3 rounded-xl transition flex items-center justify-center gap-1.5 cursor-pointer"
                        :class="activeTab === 'ios' ? 'bg-slate-700 text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800'"
                    >
                        <span>🍏</span> iPhone / iPad
                    </button>
                    <button 
                        type="button" 
                        @click="activeTab = 'desktop'" 
                        class="flex-1 py-2 px-3 rounded-xl transition flex items-center justify-center gap-1.5 cursor-pointer"
                        :class="activeTab === 'desktop' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800'"
                    >
                        <span>💻</span> PC / Chrome
                    </button>
                </div>

                <!-- Contenido de las Guías -->
                <div class="p-6 space-y-4 text-xs">
                    
                    <!-- GUÍA ANDROID (CHROME) -->
                    <div x-show="activeTab === 'android'" class="space-y-3.5">
                        <div class="flex items-start gap-3 p-3 rounded-2xl bg-slate-800/60 border border-slate-700/60">
                            <span class="w-6 h-6 rounded-full bg-emerald-600 text-white font-black flex items-center justify-center text-xs shrink-0">1</span>
                            <div>
                                <strong class="text-white block text-sm">Abre el menú del navegador</strong>
                                <p class="text-slate-300 mt-0.5">En Google Chrome, pulsa los <strong>3 puntos verticales (⋮)</strong> en la esquina superior derecha.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 p-3 rounded-2xl bg-slate-800/60 border border-slate-700/60">
                            <span class="w-6 h-6 rounded-full bg-emerald-600 text-white font-black flex items-center justify-center text-xs shrink-0">2</span>
                            <div>
                                <strong class="text-white block text-sm">Selecciona "Instalar aplicación"</strong>
                                <p class="text-slate-300 mt-0.5">Toca sobre <strong>"Instalar aplicación"</strong> o <strong>"Añadir a la pantalla de inicio"</strong>.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 p-3 rounded-2xl bg-slate-800/60 border border-slate-700/60">
                            <span class="w-6 h-6 rounded-full bg-emerald-600 text-white font-black flex items-center justify-center text-xs shrink-0">3</span>
                            <div>
                                <strong class="text-white block text-sm">¡Listo! Acceso nativo e icono propio</strong>
                                <p class="text-slate-300 mt-0.5">Se creará el icono de <strong>Eventos DJ</strong> en tu móvil/tablet. Al abrirlo, funcionará a pantalla completa y con orientación giratoria horizontal.</p>
                            </div>
                        </div>
                    </div>

                    <!-- GUÍA IPHONE / IPAD (SAFARI) -->
                    <div x-show="activeTab === 'ios'" class="space-y-3.5" style="display: none;">
                        <div class="flex items-start gap-3 p-3 rounded-2xl bg-slate-800/60 border border-slate-700/60">
                            <span class="w-6 h-6 rounded-full bg-indigo-600 text-white font-black flex items-center justify-center text-xs shrink-0">1</span>
                            <div>
                                <strong class="text-white block text-sm">Pulsa el botón de Compartir</strong>
                                <p class="text-slate-300 mt-0.5">En Safari, toca el icono de <strong>Compartir</strong> (cuadrado con flecha hacia arriba <strong>⎋</strong>) en la barra inferior o superior.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 p-3 rounded-2xl bg-slate-800/60 border border-slate-700/60">
                            <span class="w-6 h-6 rounded-full bg-indigo-600 text-white font-black flex items-center justify-center text-xs shrink-0">2</span>
                            <div>
                                <strong class="text-white block text-sm">Añadir a la pantalla de inicio</strong>
                                <p class="text-slate-300 mt-0.5">Desliza hacia abajo en las opciones y selecciona <strong>"Añadir a la pantalla de inicio"</strong> (icono con un <strong>+</strong>).</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 p-3 rounded-2xl bg-slate-800/60 border border-slate-700/60">
                            <span class="w-6 h-6 rounded-full bg-indigo-600 text-white font-black flex items-center justify-center text-xs shrink-0">3</span>
                            <div>
                                <strong class="text-white block text-sm">Pulsa "Añadir"</strong>
                                <p class="text-slate-300 mt-0.5">Confirma pulsando <strong>"Añadir"</strong> arriba a la derecha. ¡Ya tendrás la app en tu iPhone o iPad sin barras de navegación!</p>
                            </div>
                        </div>
                    </div>

                    <!-- GUÍA PC / MAC (CHROME / EDGE) -->
                    <div x-show="activeTab === 'desktop'" class="space-y-3.5" style="display: none;">
                        <div class="flex items-start gap-3 p-3 rounded-2xl bg-slate-800/60 border border-slate-700/60">
                            <span class="w-6 h-6 rounded-full bg-indigo-600 text-white font-black flex items-center justify-center text-xs shrink-0">1</span>
                            <div>
                                <strong class="text-white block text-sm">Icono de instalación en la barra de direcciones</strong>
                                <p class="text-slate-300 mt-0.5">En Google Chrome o Microsoft Edge, busca a la derecha de la barra de URL el icono de <strong>Instalar app</strong> (ordenador con flecha hacia abajo).</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 p-3 rounded-2xl bg-slate-800/60 border border-slate-700/60">
                            <span class="w-6 h-6 rounded-full bg-indigo-600 text-white font-black flex items-center justify-center text-xs shrink-0">2</span>
                            <div>
                                <strong class="text-white block text-sm">Confirmar instalación</strong>
                                <p class="text-slate-300 mt-0.5">Haz clic en <strong>"Instalar"</strong> para tener una ventana independiente como programa de escritorio.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Ventajas de la versión instalada -->
                    <div class="p-3.5 rounded-2xl bg-purple-950/40 border border-purple-800/40 text-purple-200">
                        <strong class="text-white block text-xs mb-1">✨ Ventajas en cabina de DJ y eventos:</strong>
                        <ul class="list-disc list-inside space-y-0.5 text-[11px] text-purple-300">
                            <li>Pantalla completa real (sin barras de URL que ocupen espacio).</li>
                            <li>Giro automático en horizontal para tablets y cabina DJ.</li>
                            <li>Carga ultra rápida y soporte offline.</li>
                        </ul>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="bg-slate-950/80 px-6 py-3 border-t border-slate-800 flex justify-end">
                    <button 
                        type="button" 
                        @click="showModal = false" 
                        class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition cursor-pointer shadow-md"
                    >
                        Entendido
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>
