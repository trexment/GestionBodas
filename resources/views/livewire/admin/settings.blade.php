<div class="max-w-5xl mx-auto py-4 px-2 sm:px-4 space-y-6">
    
    <!-- HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div class="flex items-center gap-4">
            @php $headerLogo = \App\Models\Setting::getLogoUrl(); @endphp
            <div class="relative flex-shrink-0">
                @if($headerLogo)
                    <img src="{{ $headerLogo }}" 
                         alt="{{ $company_name }}" 
                         onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';"
                         class="h-16 w-16 object-contain rounded-xl border border-gray-200 p-1 bg-white shadow-xs">
                    <div style="display: none;" class="h-16 w-16 bg-gradient-to-tr from-indigo-500 to-purple-600 rounded-xl items-center justify-center text-white font-black text-2xl shadow-md">
                        {{ substr($company_name, 0, 1) }}
                    </div>
                @else
                    <div class="h-16 w-16 bg-gradient-to-tr from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white font-black text-2xl shadow-md">
                        {{ substr($company_name, 0, 1) }}
                    </div>
                @endif
            </div>
            <div>
                <h1 class="text-2xl font-black text-gray-900">{{ $company_name }}</h1>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">{{ $company_subtitle }} &bull; {{ $company_season }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span> Sistema Activo
            </span>
        </div>
    </div>

    <!-- FLOATING TOAST NOTIFICATION -->
    @if (session()->has('message'))
        <div x-data="{ show: true }" 
             x-init="setTimeout(() => show = false, 5000)" 
             x-show="show" 
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 translate-y-4 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 scale-95"
             class="fixed bottom-6 right-6 z-50 bg-emerald-600 text-white px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 border border-emerald-400/40">
            <span class="text-2xl">✨</span>
            <div>
                <div class="text-[10px] font-black uppercase tracking-wider text-emerald-100">Guardado con Éxito</div>
                <div class="text-sm font-bold">{{ session('message') }}</div>
            </div>
            <button type="button" @click="show = false" class="ml-2 text-emerald-200 hover:text-white text-xl font-black cursor-pointer">&times;</button>
        </div>
    @endif

    @if (session()->has('error'))
        <div x-data="{ show: true }" 
             x-show="show" 
             class="fixed bottom-6 right-6 z-50 bg-rose-600 text-white px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 border border-rose-400/40">
            <span class="text-2xl">⚠️</span>
            <div>
                <div class="text-[10px] font-black uppercase tracking-wider text-rose-100">Aviso del Sistema</div>
                <div class="text-sm font-bold">{{ session('error') }}</div>
            </div>
            <button type="button" @click="show = false" class="ml-2 text-rose-200 hover:text-white text-xl font-black cursor-pointer">&times;</button>
        </div>
    @endif

    <!-- FLASH MESSAGES INLINE -->
    @if (session()->has('message'))
        <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-xl text-emerald-800 text-sm font-semibold shadow-xs flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span>✅</span>
                <span>{{ session('message') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 text-lg font-bold">&times;</button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-xl text-rose-800 text-sm font-semibold shadow-xs flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span>⚠️</span>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 text-lg font-bold">&times;</button>
        </div>
    @endif

    <!-- TABS NAVEGACIÓN MODERNA (GRID EN 2 LÍNEAS / RESPONSIVE) -->
    <div class="bg-slate-100/90 dark:bg-slate-900/90 p-2 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs grid grid-cols-2 sm:grid-cols-4 gap-2">
        <button type="button" wire:click="$set('activeTab', 'general')" 
            class="w-full px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 flex items-center justify-start gap-2 cursor-pointer select-none truncate {{ $activeTab === 'general' ? 'bg-white dark:bg-indigo-600 text-indigo-700 dark:text-white shadow-xs ring-1 ring-black/5 dark:ring-white/10' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-slate-800/60' }}">
            <span class="text-base shrink-0">🏢</span>
            <span class="truncate">Datos Generales</span>
        </button>

        <button type="button" wire:click="$set('activeTab', 'pricing')" 
            class="w-full px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 flex items-center justify-start gap-2 cursor-pointer select-none truncate {{ $activeTab === 'pricing' ? 'bg-white dark:bg-indigo-600 text-indigo-700 dark:text-white shadow-xs ring-1 ring-black/5 dark:ring-white/10' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-slate-800/60' }}">
            <span class="text-base shrink-0">💶</span>
            <span class="truncate">Tarifas y Precios</span>
        </button>

        <button type="button" wire:click="$set('activeTab', 'contracts')" 
            class="w-full px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 flex items-center justify-start gap-2 cursor-pointer select-none truncate {{ $activeTab === 'contracts' ? 'bg-white dark:bg-indigo-600 text-indigo-700 dark:text-white shadow-xs ring-1 ring-black/5 dark:ring-white/10' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-slate-800/60' }}">
            <span class="text-base shrink-0">📜</span>
            <span class="truncate">Editor de Contratos</span>
        </button>

        <button type="button" wire:click="$set('activeTab', 'dossier')" 
            class="w-full px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 flex items-center justify-start gap-2 cursor-pointer select-none truncate {{ $activeTab === 'dossier' ? 'bg-white dark:bg-amber-600 text-amber-700 dark:text-white shadow-xs ring-1 ring-black/5 dark:ring-white/10' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-slate-800/60' }}">
            <span class="text-base shrink-0">📖</span>
            <span class="truncate">Dossier y Propuestas</span>
        </button>

        <button type="button" wire:click="$set('activeTab', 'music_integrations')" 
            class="w-full px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 flex items-center justify-start gap-2 cursor-pointer select-none truncate {{ $activeTab === 'music_integrations' ? 'bg-white dark:bg-pink-600 text-pink-700 dark:text-white shadow-xs ring-1 ring-black/5 dark:ring-white/10' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-slate-800/60' }}">
            <span class="text-base shrink-0">🎵</span>
            <span class="truncate">Música (Spotify / Apple)</span>
        </button>

        <button type="button" wire:click="$set('activeTab', 'email_smtp')" 
            class="w-full px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 flex items-center justify-start gap-2 cursor-pointer select-none truncate {{ $activeTab === 'email_smtp' ? 'bg-white dark:bg-indigo-600 text-indigo-700 dark:text-white shadow-xs ring-1 ring-black/5 dark:ring-white/10' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-slate-800/60' }}">
            <span class="text-base shrink-0">✉️</span>
            <span class="truncate">Correo Saliente (SMTP)</span>
        </button>

        <button type="button" wire:click="$set('activeTab', 'email_imap')" 
            class="w-full px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 flex items-center justify-start gap-2 cursor-pointer select-none truncate {{ $activeTab === 'email_imap' ? 'bg-white dark:bg-purple-600 text-purple-700 dark:text-white shadow-xs ring-1 ring-black/5 dark:ring-white/10' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-slate-800/60' }}">
            <span class="text-base shrink-0">📥</span>
            <span class="truncate">Leads Entrantes (IMAP)</span>
        </button>

        <button type="button" wire:click="$set('activeTab', 'whatsapp_gateway')" 
            class="w-full px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 flex items-center justify-start gap-2 cursor-pointer select-none truncate {{ $activeTab === 'whatsapp_gateway' ? 'bg-emerald-600 text-white shadow-xs ring-1 ring-emerald-400/40' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-slate-800/60' }}">
            <span class="text-base shrink-0">💬</span>
            <span class="truncate">WhatsApp API</span>
            @if($whatsapp_gateway_enabled)
                <span class="inline-block h-2 w-2 rounded-full bg-emerald-400 ring-2 ring-emerald-200 dark:ring-emerald-900 shrink-0 ml-auto"></span>
            @endif
        </button>
    </div>

    <!-- FORM GLOBAL -->
    <form wire:submit.prevent="save" novalidate>
        
        <!-- PESTAÑA: DATOS GENERALES -->
        <div class="{{ $activeTab === 'general' ? 'block' : 'hidden' }} space-y-6">
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 space-y-4">
                <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3">Información Corporativa y Fiscal</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Nombre Comercial de la Empresa *</label>
                        <input type="text" wire:model="company_name" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        @error('company_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Subtítulo / Slogan *</label>
                        <input type="text" wire:model="company_subtitle" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        @error('company_subtitle') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">CIF / NIF Fiscal *</label>
                        <input type="text" wire:model="company_cif" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        @error('company_cif') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Teléfono / WhatsApp de Contacto *</label>
                        <input type="text" wire:model="company_phone" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        @error('company_phone') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Email de Empresa *</label>
                        <input type="email" wire:model="company_email" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        @error('company_email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Temporada por defecto *</label>
                        <input type="text" wire:model="company_season" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        @error('company_season') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 mb-1">Dirección Fiscal / Sede</label>
                        <input type="text" wire:model="company_address" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 mb-1">Localidad / Ciudad</label>
                        <input type="text" wire:model="company_city" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    </div>

                    <div class="md:col-span-2">
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-gray-700">URL Web Pública Principal (Opcional)</label>
                            <span class="text-[11px] text-indigo-600 font-semibold">Auto-detectado: {{ \App\Models\Setting::getPublicWebsiteUrl() }}</span>
                        </div>
                        <input type="url" wire:model="public_website_url" placeholder="Dejar vacío para auto-detectar según dominio (ej. app.javnxdj.com &rarr; javnxdj.com)" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        <span class="text-[11px] text-gray-400 mt-1 block">Si se deja vacío, el botón "Web Pública" redirigirá automáticamente a la raíz del dominio principal (ej: desde <code>app.javnxdj.com</code> irá a <code>https://javnxdj.com</code>).</span>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100 space-y-3">
                    <label class="block text-xs font-bold text-gray-700 mb-1">Logotipo Corporativo (PNG, JPG o SVG)</label>
                    <div class="flex items-center gap-4">
                        @if($logo)
                            <div class="flex flex-col items-center gap-1">
                                <img src="{{ $logo->temporaryUrl() }}" alt="Previsualización" class="h-16 w-16 object-contain rounded-xl border-2 border-indigo-500 p-1 bg-white shadow-xs">
                                <span class="text-[10px] text-indigo-600 font-bold">Nuevo a guardar</span>
                            </div>
                        @elseif($headerLogo)
                            <div class="flex flex-col items-center gap-1">
                                <img src="{{ $headerLogo }}" alt="Logo actual" class="h-16 w-16 object-contain rounded-xl border border-gray-200 p-1 bg-white shadow-xs">
                                <span class="text-[10px] text-gray-500 font-semibold">Logo actual</span>
                            </div>
                        @endif
                        <div class="flex-1">
                            <input type="file" wire:model="logo" accept="image/*" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                            <span class="text-[11px] text-gray-400 mt-1 block">Aparecerá en el encabezado de presupuestos, contratos, facturas y barra lateral.</span>
                            <div wire:loading wire:target="logo" class="text-xs text-indigo-600 font-medium mt-1">
                                ⏳ Subiendo archivo temporal...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN FIRMA / SELLO DE LA EMPRESA (DIBUJAR O SUBIR ARCHIVO) -->
                <div class="pt-5 border-t border-gray-100 dark:border-slate-800 space-y-4" x-data="adminSignaturePad()" wire:ignore>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <label class="block text-xs font-bold text-gray-900 dark:text-white">✍️ Firma / Sello Oficial de la Empresa Prestadora</label>
                            <span class="text-[11px] text-gray-500 dark:text-slate-400 block mt-0.5">
                                Aparecerá estampada automáticamente en los contratos, facturas y presupuestos PDF oficiales.
                            </span>
                        </div>
                        <!-- Botones de Alternancia: Dibujar / Subir -->
                        <div class="inline-flex rounded-xl bg-gray-100 dark:bg-slate-800 p-1 border border-gray-200 dark:border-slate-700">
                            <button 
                                type="button" 
                                @click="mode = 'draw'" 
                                :class="mode === 'draw' ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-xs font-bold' : 'text-gray-600 dark:text-slate-300 hover:text-gray-900 dark:hover:text-white font-medium'"
                                class="px-3 py-1.5 text-xs rounded-lg transition-all flex items-center gap-1.5 cursor-pointer"
                            >
                                <span>✍️</span> Dibujar en Pantalla
                            </button>
                            <button 
                                type="button" 
                                @click="mode = 'upload'" 
                                :class="mode === 'upload' ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-xs font-bold' : 'text-gray-600 dark:text-slate-300 hover:text-gray-900 dark:hover:text-white font-medium'"
                                class="px-3 py-1.5 text-xs rounded-lg transition-all flex items-center gap-1.5 cursor-pointer"
                            >
                                <span>📁</span> Subir Archivo
                            </button>
                        </div>
                    </div>

                    <!-- PREVISUALIZACIÓN DE FIRMA ACTUAL GUARDADA -->
                    @if(\App\Models\Setting::getCompanySignatureUrl())
                        <div class="p-4 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition shadow-2xs">
                            <div class="flex items-center gap-3.5">
                                <!-- Lienzo blanco papel con fondo blanco puro garantizado para ver la tinta oscura -->
                                <div class="rounded-xl border border-slate-300 dark:border-slate-600 p-2.5 shadow-xs flex items-center justify-center shrink-0" style="background-color: #ffffff !important; min-width: 150px; min-height: 68px;">
                                    <img src="{{ \App\Models\Setting::getCompanySignatureUrl() }}" alt="Firma Guardada" class="h-14 sm:h-16 w-36 sm:w-48 object-contain">
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-extrabold text-slate-800 dark:text-white block">Firma / Sello actual en uso</span>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                            Activa
                                        </span>
                                    </div>
                                    <span class="text-xs text-emerald-700 dark:text-emerald-400 font-semibold flex items-center gap-1 mt-0.5">
                                        <span>✔</span> Lista y activa en todos los contratos, presupuestos y facturas PDF
                                    </span>
                                </div>
                            </div>
                            <span class="text-xs text-slate-400 dark:text-slate-400 hidden lg:inline-block max-w-xs text-right">
                                Si dibujas una nueva firma abajo o subes una imagen, reemplazará a esta al guardar.
                            </span>
                        </div>
                    @endif

                    <!-- MODO A: DIBUJAR FIRMA EN PANTALLA (CANVAS) -->
                    <div x-show="mode === 'draw'" class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                <span>🖊️</span> Dibuja tu firma en el recuadro blanco:
                            </span>
                            <button 
                                type="button" 
                                @click="clearSignature()" 
                                class="text-xs font-bold text-slate-700 dark:text-slate-200 hover:text-rose-600 px-3.5 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-rose-50 border border-slate-300 dark:border-slate-600 transition cursor-pointer flex items-center gap-1.5 shadow-2xs"
                            >
                                <span>🔄</span> Limpiar y Repetir
                            </button>
                        </div>

                        <!-- Contenedor del lienzo siempre en blanco puro para máximo contraste -->
                        <div 
                            class="relative border-2 border-indigo-400 dark:border-indigo-500 rounded-2xl overflow-hidden shadow-inner touch-none select-none w-full"
                            style="background-color: #ffffff !important; height: 210px; touch-action: none; -webkit-touch-callout: none; -webkit-user-select: none; user-select: none;"
                        >
                            <canvas 
                                x-ref="canvas" 
                                class="w-full h-full cursor-crosshair block"
                                style="background-color: #ffffff !important; touch-action: none;"
                            ></canvas>

                            <!-- Guía de firma -->
                            <div class="absolute bottom-4 left-6 right-6 pointer-events-none border-b border-dashed border-slate-400 flex items-center justify-between text-[11px] text-slate-500 font-sans pb-1 select-none">
                                <span class="font-bold flex items-center gap-1 text-slate-700">
                                    <span class="text-slate-600 font-black">✕</span> Firma de la Empresa Prestadora
                                </span>
                                <span class="text-[10px] text-slate-500">Núñez and Son / JAVNX DJ</span>
                            </div>

                            <!-- Mensaje indicativo si está vacío -->
                            <div x-show="!hasDrawn" class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center text-slate-500 text-xs font-medium gap-1.5 select-none p-4 text-center">
                                <span class="text-2xl">✍️</span>
                                <span class="text-slate-800 font-bold text-sm">Estampa tu firma aquí</span>
                                <span class="text-[11px] text-slate-500">Dibuja con el ratón o desliza el dedo en pantalla táctil</span>
                            </div>
                        </div>
                        <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold block" x-show="hasDrawn">
                            ✨ Firma capturada correctamente. Haz clic en "Guardar Configuración" para aplicarla a todos los contratos.
                        </span>
                    </div>

                    <!-- MODO B: SUBIR ARCHIVO (PNG/JPG) -->
                    <div x-show="mode === 'upload'" class="space-y-2" style="display: none;">
                        <div class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-2xl">
                            @if($company_signature)
                                <div class="flex flex-col items-center gap-1">
                                    <div class="rounded-xl border border-indigo-400 p-1.5 shadow-xs flex items-center justify-center shrink-0" style="background-color: #ffffff !important;">
                                        <img src="{{ $company_signature->temporaryUrl() }}" alt="Previsualización Firma" class="h-14 w-28 object-contain">
                                    </div>
                                    <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold">Nueva firma</span>
                                </div>
                            @endif
                            <div class="flex-1">
                                <label class="block text-xs font-bold text-gray-700 dark:text-slate-200 mb-1">Seleccionar archivo de firma / sello digital (PNG transparente recomendado)</label>
                                <input type="file" wire:model="company_signature" accept="image/*" class="w-full text-xs text-gray-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-950/70 file:text-indigo-700 dark:file:text-indigo-300 hover:file:bg-indigo-100 cursor-pointer">
                                <span class="text-[11px] text-gray-400 dark:text-slate-400 mt-1 block">Formatos permitidos: PNG, JPG o SVG (máx. 2MB).</span>
                                <div wire:loading wire:target="company_signature" class="text-xs text-indigo-600 dark:text-indigo-400 font-medium mt-1">
                                    ⏳ Subiendo firma temporal...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN MULTI-MARCA (NÚÑEZ AND SON & JAVNX DJ) -->
                <div class="pt-6 border-t border-gray-200 dark:border-slate-800 space-y-4">
                    <div>
                        <h4 class="text-sm font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                            <span>👑🎧</span> Configuración de Marcas Comerciales (Multi-Marca)
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
                            Define los datos que aparecerán automáticamente en las propuestas según la marca que selecciones en cada evento.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <!-- PERFIL 1: NÚÑEZ AND SON -->
                        <div class="p-4 bg-amber-50/60 dark:bg-amber-950/30 rounded-2xl border border-amber-200 dark:border-amber-800/60 space-y-3">
                            <div class="flex items-center gap-2 border-b border-amber-200 dark:border-amber-800/60 pb-2">
                                <span class="text-xl">👑</span>
                                <div>
                                    <strong class="text-xs font-black text-amber-950 dark:text-amber-200 block">Marca 1: Núñez and Son</strong>
                                    <span class="text-[11px] text-amber-800 dark:text-amber-300">Bodas, eventos familiares y sonorización tradicional</span>
                                </div>
                            </div>

                            <div class="space-y-2.5 text-xs">
                                <div>
                                    <label class="block font-bold text-amber-900 dark:text-amber-200 mb-0.5">Nombre Comercial</label>
                                    <input type="text" wire:model="brand_nunez_name" class="w-full border-amber-300 dark:border-amber-800 rounded-lg p-2 text-xs bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 font-bold">
                                </div>
                                <div>
                                    <label class="block font-bold text-amber-900 dark:text-amber-200 mb-0.5">Subtítulo / Especialidad</label>
                                    <input type="text" wire:model="brand_nunez_subtitle" class="w-full border-amber-300 dark:border-amber-800 rounded-lg p-2 text-xs bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100">
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block font-bold text-amber-900 dark:text-amber-200 mb-0.5">Teléfono 1 (Fran)</label>
                                        <input type="text" wire:model="brand_nunez_phone" class="w-full border-amber-300 dark:border-amber-800 rounded-lg p-2 text-xs bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-amber-900 dark:text-amber-200 mb-0.5">Teléfono 2 (Miguel)</label>
                                        <input type="text" wire:model="brand_nunez_phone_2" class="w-full border-amber-300 dark:border-amber-800 rounded-lg p-2 text-xs bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100">
                                    </div>
                                </div>
                                <div>
                                    <label class="block font-bold text-amber-900 dark:text-amber-200 mb-0.5">Página Web / Landing</label>
                                    <input type="text" wire:model="brand_nunez_website" class="w-full border-amber-300 dark:border-amber-800 rounded-lg p-2 text-xs bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100">
                                </div>
                                <div>
                                    <label class="block font-bold text-amber-900 dark:text-amber-200 mb-0.5">Email de Contacto</label>
                                    <input type="email" wire:model="brand_nunez_email" class="w-full border-amber-300 dark:border-amber-800 rounded-lg p-2 text-xs bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100">
                                </div>
                            </div>
                        </div>

                        <!-- PERFIL 2: JAVNX DJ -->
                        <div class="p-4 bg-indigo-50/60 dark:bg-indigo-950/30 rounded-2xl border border-indigo-200 dark:border-indigo-800/60 space-y-3">
                            <div class="flex items-center gap-2 border-b border-indigo-200 dark:border-indigo-800/60 pb-2">
                                <span class="text-xl">🎧</span>
                                <div>
                                    <strong class="text-xs font-black text-indigo-950 dark:text-indigo-200 block">Marca 2: JAVNX DJ</strong>
                                    <span class="text-[11px] text-indigo-800 dark:text-indigo-300">Fiestas, sesiones DJ, eventos de empresa y festivales</span>
                                </div>
                            </div>

                            <div class="space-y-2.5 text-xs">
                                <div>
                                    <label class="block font-bold text-indigo-900 dark:text-indigo-200 mb-0.5">Nombre Comercial</label>
                                    <input type="text" wire:model="brand_javnx_name" class="w-full border-indigo-300 dark:border-indigo-800 rounded-lg p-2 text-xs bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 font-bold">
                                </div>
                                <div>
                                    <label class="block font-bold text-indigo-900 dark:text-indigo-200 mb-0.5">Subtítulo / Especialidad</label>
                                    <input type="text" wire:model="brand_javnx_subtitle" class="w-full border-indigo-300 dark:border-indigo-800 rounded-lg p-2 text-xs bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100">
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block font-bold text-indigo-900 dark:text-indigo-200 mb-0.5">Teléfono Principal</label>
                                        <input type="text" wire:model="brand_javnx_phone" class="w-full border-indigo-300 dark:border-indigo-800 rounded-lg p-2 text-xs bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-indigo-900 dark:text-indigo-200 mb-0.5">Teléfono Secundario (Opcional)</label>
                                        <input type="text" wire:model="brand_javnx_phone_2" class="w-full border-indigo-300 dark:border-indigo-800 rounded-lg p-2 text-xs bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100">
                                    </div>
                                </div>
                                <div>
                                    <label class="block font-bold text-indigo-900 dark:text-indigo-200 mb-0.5">Página Web / Redes</label>
                                    <input type="text" wire:model="brand_javnx_website" class="w-full border-indigo-300 dark:border-indigo-800 rounded-lg p-2 text-xs bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100">
                                </div>
                                <div>
                                    <label class="block font-bold text-indigo-900 dark:text-indigo-200 mb-0.5">Email de Contacto</label>
                                    <input type="email" wire:model="brand_javnx_email" class="w-full border-indigo-300 dark:border-indigo-800 rounded-lg p-2 text-xs bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100">
                                </div>
                            </div>
                        </div>

                        <!-- PERFIL 3: MAGO LEUGIM -->
                        <div class="p-4 bg-purple-50/60 dark:bg-purple-950/30 rounded-2xl border border-purple-200 dark:border-purple-800/60 space-y-3">
                            <div class="flex items-center gap-2 border-b border-purple-200 dark:border-purple-800/60 pb-2">
                                <span class="text-xl">🎩</span>
                                <div>
                                    <strong class="text-xs font-black text-purple-950 dark:text-purple-200 block">Marca 3: Mago Leugim</strong>
                                    <span class="text-[11px] text-purple-800 dark:text-purple-300">Ilusionismo, magia de cerca, espectáculos y bodas</span>
                                </div>
                            </div>

                            <div class="space-y-2.5 text-xs">
                                <div>
                                    <label class="block font-bold text-purple-900 dark:text-purple-200 mb-0.5">Nombre Comercial</label>
                                    <input type="text" wire:model="brand_leugim_name" class="w-full border-purple-300 dark:border-purple-800 rounded-lg p-2 text-xs bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 font-bold">
                                </div>
                                <div>
                                    <label class="block font-bold text-purple-900 dark:text-purple-200 mb-0.5">Subtítulo / Especialidad</label>
                                    <input type="text" wire:model="brand_leugim_subtitle" class="w-full border-purple-300 dark:border-purple-800 rounded-lg p-2 text-xs bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100">
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block font-bold text-purple-900 dark:text-purple-200 mb-0.5">Teléfono Principal (Miguel)</label>
                                        <input type="text" wire:model="brand_leugim_phone" class="w-full border-purple-300 dark:border-purple-800 rounded-lg p-2 text-xs bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-purple-900 dark:text-purple-200 mb-0.5">Teléfono Secundario</label>
                                        <input type="text" wire:model="brand_leugim_phone_2" class="w-full border-purple-300 dark:border-purple-800 rounded-lg p-2 text-xs bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100">
                                    </div>
                                </div>
                                <div>
                                    <label class="block font-bold text-purple-900 dark:text-purple-200 mb-0.5">Página Web / Redes</label>
                                    <input type="text" wire:model="brand_leugim_website" class="w-full border-purple-300 dark:border-purple-800 rounded-lg p-2 text-xs bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100">
                                </div>
                                <div>
                                    <label class="block font-bold text-purple-900 dark:text-purple-200 mb-0.5">Email de Contacto</label>
                                    <input type="email" wire:model="brand_leugim_email" class="w-full border-purple-300 dark:border-purple-800 rounded-lg p-2 text-xs bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SERVICIO DE VIDEÓGRAFO / COLABORACIÓN EXTERNA POR MARCA -->
                    <div class="mt-4 p-4 bg-gradient-to-r from-purple-50 via-indigo-50 to-pink-50 dark:from-slate-800 dark:via-purple-950/30 dark:to-indigo-950/30 rounded-2xl border border-purple-200 dark:border-purple-800/60 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">📹</span>
                                <div>
                                    <strong class="text-xs font-black text-purple-950 dark:text-purple-200 block">Servicio de Vídeo / Videógrafo por Marca y Dominio</strong>
                                    <span class="text-[11px] text-purple-800 dark:text-purple-300">Selecciona en qué perfiles/dominios quieres que aparezca disponible el tipo de evento y presupuesto de Vídeo (ej: habilitado para JAVNX DJ y deshabilitado para Núñez and Son).</span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                            <!-- JAVNX DJ -->
                            <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition {{ in_array('javnx', (array)($video_service_brands ?? [])) ? 'bg-indigo-100/70 border-indigo-400 dark:bg-indigo-950/60 dark:border-indigo-700' : 'bg-white/80 border-gray-200 dark:bg-slate-900/60 dark:border-slate-800 opacity-60' }}">
                                <input type="checkbox" wire:model="video_service_brands" value="javnx" class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                <div class="text-xs">
                                    <strong class="block text-indigo-950 dark:text-indigo-200 font-bold">🎧 JAVNX DJ</strong>
                                    <span class="text-[10px] text-indigo-700 dark:text-indigo-300">javnxdj.com</span>
                                </div>
                            </label>

                            <!-- NÚÑEZ AND SON -->
                            <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition {{ in_array('nunez_and_son', (array)($video_service_brands ?? [])) ? 'bg-amber-100/70 border-amber-400 dark:bg-amber-950/60 dark:border-amber-700' : 'bg-white/80 border-gray-200 dark:bg-slate-900/60 dark:border-slate-800 opacity-60' }}">
                                <input type="checkbox" wire:model="video_service_brands" value="nunez_and_son" class="rounded text-amber-600 focus:ring-amber-500 h-4 w-4">
                                <div class="text-xs">
                                    <strong class="block text-amber-950 dark:text-amber-200 font-bold">👑 Núñez and Son</strong>
                                    <span class="text-[10px] text-amber-700 dark:text-amber-300">nunezandson.com</span>
                                </div>
                            </label>

                            <!-- MAGO LEUGIM -->
                            <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition {{ in_array('mago_leugim', (array)($video_service_brands ?? [])) ? 'bg-purple-100/70 border-purple-400 dark:bg-purple-950/60 dark:border-purple-700' : 'bg-white/80 border-gray-200 dark:bg-slate-900/60 dark:border-slate-800 opacity-60' }}">
                                <input type="checkbox" wire:model="video_service_brands" value="mago_leugim" class="rounded text-purple-600 focus:ring-purple-500 h-4 w-4">
                                <div class="text-xs">
                                    <strong class="block text-purple-950 dark:text-purple-200 font-bold">🎩 Mago Leugim</strong>
                                    <span class="text-[10px] text-purple-700 dark:text-purple-300">magoleugim.es</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PESTAÑA: TARIFAS, PACKS Y PRECIOS -->
        <div class="{{ $activeTab === 'pricing' ? 'block' : 'hidden' }} space-y-6">
            
            <!-- SECCIÓN 1: PACKS COMERCIALES -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 space-y-4">
                <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <span>📦</span> Packs Comerciales Predefinidos
                        </h3>
                        <p class="text-xs text-gray-500">Configura tus packs estrella para aplicarlos con 1 solo clic en los presupuestos y contratos de eventos.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <!-- Pack 1: La Base / Esencial -->
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase text-slate-600">Pack 1: Esencial / Base</span>
                            <span class="text-xs bg-slate-200 text-slate-800 font-bold px-2 py-0.5 rounded">La Base</span>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 mb-0.5">Nombre del Pack</label>
                            <input type="text" wire:model="pack_basic_name" class="w-full border-gray-300 rounded-lg text-xs font-bold">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 mb-0.5">Precio (€)</label>
                                <input type="number" step="0.01" wire:model="pack_basic_price" class="w-full border-gray-300 rounded-lg text-xs font-bold text-indigo-700">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 mb-0.5">Horas DJ</label>
                                <input type="number" wire:model="pack_basic_hours" class="w-full border-gray-300 rounded-lg text-xs">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 mb-0.5">Descripción y Equipamiento</label>
                            <textarea wire:model="pack_basic_desc" rows="2" class="w-full border-gray-300 rounded-lg text-xs"></textarea>
                        </div>
                    </div>

                    <!-- Pack 2: La Opción Estrella -->
                    <div class="p-4 bg-indigo-50/60 rounded-xl border-2 border-indigo-200 space-y-3 relative">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase text-indigo-800">Pack 2: Opción Estrella</span>
                            <span class="text-[10px] bg-indigo-600 text-white font-bold px-2 py-0.5 rounded-full">⭐ Recomendado</span>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-indigo-900 mb-0.5">Nombre del Pack</label>
                            <input type="text" wire:model="pack_medium_name" class="w-full border-indigo-300 rounded-lg text-xs font-bold">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[11px] font-bold text-indigo-900 mb-0.5">Precio (€)</label>
                                <input type="number" step="0.01" wire:model="pack_medium_price" class="w-full border-indigo-300 rounded-lg text-xs font-bold text-indigo-700">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-indigo-900 mb-0.5">Horas DJ</label>
                                <input type="number" wire:model="pack_medium_hours" class="w-full border-indigo-300 rounded-lg text-xs">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-indigo-900 mb-0.5">Descripción y Equipamiento</label>
                            <textarea wire:model="pack_medium_desc" rows="2" class="w-full border-indigo-300 rounded-lg text-xs"></textarea>
                        </div>
                    </div>

                    <!-- Pack 3: La Experiencia Completa -->
                    <div class="p-4 bg-amber-50/60 rounded-xl border border-amber-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase text-amber-800">Pack 3: Experiencia Completa</span>
                            <span class="text-xs bg-amber-200 text-amber-900 font-bold px-2 py-0.5 rounded">👑 Show Completo</span>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 mb-0.5">Nombre del Pack</label>
                            <input type="text" wire:model="pack_premium_name" class="w-full border-gray-300 rounded-lg text-xs font-bold">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 mb-0.5">Precio (€)</label>
                                <input type="number" step="0.01" wire:model="pack_premium_price" class="w-full border-gray-300 rounded-lg text-xs font-bold text-indigo-700">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 mb-0.5">Horas DJ</label>
                                <input type="number" wire:model="pack_premium_hours" class="w-full border-gray-300 rounded-lg text-xs">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-700 mb-0.5">Descripción y Equipamiento</label>
                            <textarea wire:model="pack_premium_desc" rows="2" class="w-full border-gray-300 rounded-lg text-xs"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2: SERVICIOS FOTOGRAFÍA -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 space-y-4">
                <div class="border-b border-gray-100 pb-3">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <span>📸</span> Tarifas de Fotografía & Álbum
                    </h3>
                    <p class="text-xs text-gray-500">Tarifas para cobertura fotográfica de eventos, ceremonia, banquete, fiesta y maquetación.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
                    <div class="p-3 bg-gray-50 rounded-xl border border-gray-200">
                        <label class="block text-xs font-bold text-gray-700 mb-1">📷 Foto Ceremonia (€)</label>
                        <input type="number" step="0.01" wire:model="price_photo_ceremony" required class="w-full border-gray-300 rounded-lg text-xs font-bold text-indigo-700">
                    </div>
                    <div class="p-3 bg-gray-50 rounded-xl border border-gray-200">
                        <label class="block text-xs font-bold text-gray-700 mb-1">🍽️ Foto Restaurante (€)</label>
                        <input type="number" step="0.01" wire:model="price_photo_restaurant" required class="w-full border-gray-300 rounded-lg text-xs font-bold text-indigo-700">
                    </div>
                    <div class="p-3 bg-gray-50 rounded-xl border border-gray-200">
                        <label class="block text-xs font-bold text-gray-700 mb-1">🎉 Foto Baile/Fiesta (€)</label>
                        <input type="number" step="0.01" wire:model="price_photo_party" required class="w-full border-gray-300 rounded-lg text-xs font-bold text-indigo-700">
                    </div>
                    <div class="p-3 bg-gray-50 rounded-xl border border-gray-200">
                        <label class="block text-xs font-bold text-gray-700 mb-1">📖 Maquetación Álbum (€)</label>
                        <input type="number" step="0.01" wire:model="price_photo_album" required class="w-full border-gray-300 rounded-lg text-xs font-bold text-indigo-700">
                    </div>
                    <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200">
                        <label class="block text-xs font-bold text-emerald-900 mb-1">🌟 Pack Completo Foto (€)</label>
                        <input type="number" step="0.01" wire:model="price_photo_full_pack" required class="w-full border-emerald-300 rounded-lg text-xs font-bold text-emerald-700">
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 3: SERVICIOS MUSICALES INDIVIDUALES -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 space-y-4">
                <div class="border-b border-gray-100 pb-3">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <span>🎵</span> Tarifas Base por Servicio Musical
                    </h3>
                    <p class="text-xs text-gray-500">Se utilizan para los servicios sueltos o añadidos.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200">
                        <label class="block text-xs font-bold text-gray-700 mb-1">💍 Ceremonia Civil / Religiosa (€)</label>
                        <input type="number" step="0.01" wire:model="price_ceremony" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm font-bold">
                    </div>

                    <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200">
                        <label class="block text-xs font-bold text-gray-700 mb-1">🍸 Cóctel de Bienvenida (€)</label>
                        <input type="number" step="0.01" wire:model="price_cocktail" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm font-bold">
                    </div>

                    <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200">
                        <label class="block text-xs font-bold text-gray-700 mb-1">🍽️ Banquete & Regalos (€)</label>
                        <input type="number" step="0.01" wire:model="price_restaurant" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm font-bold">
                    </div>

                    <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200">
                        <label class="block text-xs font-bold text-gray-700 mb-1">💃 DJ Baile / Hora (€)</label>
                        <input type="number" step="0.01" wire:model="price_dj" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm font-bold">
                    </div>

                    <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200">
                        <label class="block text-xs font-bold text-gray-700 mb-1">🎤 Karaoke / Animación (€)</label>
                        <input type="number" step="0.01" wire:model="price_karaoke" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm font-bold">
                    </div>

                    <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200">
                        <label class="block text-xs font-bold text-gray-700 mb-1">⏰ Hora Extra Baile (€)</label>
                        <input type="number" step="0.01" wire:model="price_extra_hours" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm font-bold">
                    </div>

                    <div class="p-3.5 bg-indigo-50/60 rounded-xl border border-indigo-200">
                        <label class="block text-xs font-bold text-indigo-900 mb-1">📸 Fotomatón & Photocall (€)</label>
                        <input type="number" step="0.01" wire:model="price_photobooth" required class="w-full border-indigo-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm font-bold text-indigo-700">
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                        <label class="block text-xs font-bold text-slate-800 mb-1">💨 Máquina de Humo (€)</label>
                        <input type="number" step="0.01" wire:model="price_effects_smoke" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm font-bold">
                        <span class="text-[10px] text-gray-500 mt-1 block">Tarifa como extra (Incluido en Pack Signature / 5h+)</span>
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                        <label class="block text-xs font-bold text-slate-800 mb-1">✨ Fuego Frío / Chispas Frías (€ / máquina)</label>
                        <input type="number" step="0.01" wire:model="price_effects_sparks" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm font-bold">
                        <span class="text-[10px] text-gray-500 mt-1 block">Tarifa por máquina (ej: 2 máquinas = 120 €). En Pack Deluxe Show se incluye espectáculo de 2 máquinas (0 €).</span>
                    </div>
                </div>

                <!-- PACK COMBINADO CÓCTEL + BANQUETE -->
                <div class="mt-4 p-4 rounded-xl border-2 border-indigo-100 bg-gradient-to-br from-indigo-50/60 to-purple-50/40 space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-indigo-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">🎁</span>
                            <div>
                                <h4 class="text-sm font-black text-indigo-950">Pack Combinado: Cóctel + Banquete</h4>
                                <p class="text-[11px] text-indigo-700/80">Aplica un precio promocional automático en la app y el cotizador online cuando se seleccionen ambos servicios.</p>
                            </div>
                        </div>
                        <label class="inline-flex items-center gap-2 cursor-pointer bg-white px-3 py-1.5 rounded-lg border border-indigo-200 shadow-2xs">
                            <input type="checkbox" wire:model.live="pack_cocktail_restaurant_enabled" class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                            <span class="text-xs font-bold text-indigo-900">Activar Pack Automático</span>
                        </label>
                    </div>

                    @if($pack_cocktail_restaurant_enabled)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center pt-1">
                            <div class="space-y-3">
                                <label class="block text-xs font-bold text-gray-700">Modalidad de Descuento</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <label class="flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer transition {{ $pack_cocktail_restaurant_discount_type === 'percentage' ? 'bg-indigo-600 text-white font-bold border-indigo-600 shadow-xs' : 'bg-white border-gray-200 text-gray-700' }}">
                                        <input type="radio" wire:model.live="pack_cocktail_restaurant_discount_type" value="percentage" class="text-indigo-600">
                                        <span class="text-xs">Porcentaje (%)</span>
                                    </label>
                                    <label class="flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer transition {{ $pack_cocktail_restaurant_discount_type === 'fixed_price' ? 'bg-indigo-600 text-white font-bold border-indigo-600 shadow-xs' : 'bg-white border-gray-200 text-gray-700' }}">
                                        <input type="radio" wire:model.live="pack_cocktail_restaurant_discount_type" value="fixed_price" class="text-indigo-600">
                                        <span class="text-xs">Precio Fijo (€)</span>
                                    </label>
                                </div>

                                <div>
                                    @if($pack_cocktail_restaurant_discount_type === 'percentage')
                                        <label class="block text-xs font-bold text-indigo-950 mb-1">Porcentaje de Descuento sobre la suma de ambos (%)</label>
                                        <div class="flex items-center gap-2">
                                            <input type="number" step="0.5" min="1" max="90" wire:model.live="pack_cocktail_restaurant_discount_percentage" class="w-32 border-indigo-300 rounded-lg shadow-sm text-sm font-bold text-indigo-700">
                                            <span class="text-xs font-bold text-indigo-900">% de ahorro</span>
                                        </div>
                                    @else
                                        <label class="block text-xs font-bold text-indigo-950 mb-1">Precio Cerrado del Pack Cóctel + Banquete (€)</label>
                                        <div class="flex items-center gap-2">
                                            <input type="number" step="1" min="10" wire:model.live="pack_cocktail_restaurant_price" class="w-36 border-indigo-300 rounded-lg shadow-sm text-sm font-bold text-indigo-700">
                                            <span class="text-xs font-bold text-indigo-900">€ (Tarifa final)</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Resumen / Cálculo en vivo -->
                            @php
                                $cPrice = (float)($price_cocktail ?: 0);
                                $rPrice = (float)($price_restaurant ?: 0);
                                $sumPrices = $cPrice + $rPrice;
                                if ($pack_cocktail_restaurant_discount_type === 'fixed_price') {
                                    $finalPackPrice = (float)($pack_cocktail_restaurant_price ?: 0);
                                    $savingsAmount = max(0, $sumPrices - $finalPackPrice);
                                    $savingsPercent = $sumPrices > 0 ? round(($savingsAmount / $sumPrices) * 100, 1) : 0;
                                } else {
                                    $pct = (float)($pack_cocktail_restaurant_discount_percentage ?: 0);
                                    $savingsAmount = round($sumPrices * ($pct / 100), 2);
                                    $finalPackPrice = max(0, $sumPrices - $savingsAmount);
                                    $savingsPercent = $pct;
                                }
                            @endphp
                            <div class="bg-white p-4 rounded-xl border border-indigo-100 shadow-xs space-y-2">
                                <span class="text-[10px] font-black uppercase tracking-wider text-indigo-600 block">Simulación en tiempo real</span>
                                <div class="flex items-center justify-between text-xs text-gray-600">
                                    <span>🍸 Cóctel ({{ number_format($cPrice, 2, ',', '.') }} €) + 🍽️ Banquete ({{ number_format($rPrice, 2, ',', '.') }} €):</span>
                                    <span class="line-through text-gray-400 font-semibold">{{ number_format($sumPrices, 2, ',', '.') }} €</span>
                                </div>
                                <div class="flex items-center justify-between text-xs font-bold text-emerald-700 bg-emerald-50 p-2 rounded-lg border border-emerald-200">
                                    <span>🎉 Tarifa Final del Pack:</span>
                                    <span class="text-sm font-black">{{ number_format($finalPackPrice, 2, ',', '.') }} €</span>
                                </div>
                                <div class="text-[11px] text-indigo-700 font-medium text-right">
                                    Ahorro para el cliente: <strong>{{ number_format($savingsAmount, 2, ',', '.') }} €</strong> ({{ round($savingsPercent) }}% de descuento)
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- SECCIÓN 3B: PROVEEDORES EXTERNOS (FOTOMATÓN, PLATAFORMA 360º, PACK DÚO, FUENTES DE CHOCOLATE, CHUCHES Y GOFRES) -->
            @php
                $activeProvKey = isset($photobooth_providers[$photobooth_settings_tab])
                    ? $photobooth_settings_tab
                    : (array_key_first($photobooth_providers) ?: 'logrono');
                $activeProv = $photobooth_providers[$activeProvKey] ?? [];
            @endphp
            <div class="bg-white shadow-sm rounded-xl border border-indigo-200 p-6 space-y-5">
                <div class="border-b border-indigo-100 pb-4 flex flex-col xl:flex-row xl:items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-indigo-950 flex items-center gap-2">
                            <span>📸🎥🍫</span> Empresas Externas: Fotomatón, 360º, Fuentes de Chocolate, Chuches & Gofres
                        </h3>
                        <p class="text-xs text-gray-500">Cada proveedor tiene su propio nombre, tarifas y servicios activos/desactivados de forma 100% independiente.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="inline-flex items-center gap-1.5 bg-amber-50 border border-amber-200 px-2.5 py-1.5 rounded-xl text-xs">
                            <span class="font-bold text-amber-900">Proveedor por defecto:</span>
                            <select wire:model="default_photobooth_provider" class="text-xs font-black bg-white border-amber-300 rounded-lg py-0.5 px-2 text-amber-950">
                                @foreach($photobooth_providers as $pKey => $pItem)
                                    <option value="{{ $pKey }}">📍 {{ $pItem['name'] ?? ucfirst($pKey) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="button" wire:click="$toggle('show_add_provider_form')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-black bg-indigo-600 text-white hover:bg-indigo-700 transition cursor-pointer shadow-xs">
                            <span>➕</span> {{ $show_add_provider_form ? 'Cerrar formulario' : 'Añadir Proveedor' }}
                        </button>
                    </div>
                </div>

                <!-- Pestañas de selección de proveedor -->
                <div class="flex flex-wrap items-center gap-2 bg-slate-100 p-1.5 rounded-xl border border-slate-200">
                    @foreach($photobooth_providers as $pKey => $pItem)
                        @php
                            $isCurrentTab = ($activeProvKey === $pKey);
                            $enabledCount = collect([
                                !empty($pItem['photobooth_enabled']),
                                !empty($pItem['video_360_enabled']),
                                !empty($pItem['pack_duo_enabled']),
                                !empty($pItem['chocolate_fountain_enabled']),
                                !empty($pItem['candy_bar_enabled']),
                                !empty($pItem['waffle_cart_enabled']),
                            ])->filter()->count();
                        @endphp
                        <button
                            type="button"
                            wire:key="pb-tab-btn-{{ $pKey }}"
                            wire:click="$set('photobooth_settings_tab', '{{ $pKey }}')"
                            class="px-3 py-1.5 rounded-lg text-xs font-black transition cursor-pointer flex items-center gap-1.5 {{ $isCurrentTab ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white/70 text-slate-700 hover:bg-white hover:text-slate-950' }}"
                        >
                            <span>📍 {{ $pItem['name'] ?: ucfirst($pKey) }}</span>
                            <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $isCurrentTab ? 'bg-indigo-800 text-indigo-100' : 'bg-slate-200 text-slate-700' }}">
                                {{ $enabledCount }} serv.
                            </span>
                        </button>
                    @endforeach
                </div>

                <!-- Formulario desplegable para crear un nuevo proveedor externo -->
                @if($show_add_provider_form)
                    <div class="p-4 bg-indigo-50/70 border-2 border-dashed border-indigo-300 rounded-xl space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-black uppercase tracking-wider text-indigo-950">➕ Crear Nuevo Proveedor Externo</h4>
                            <button type="button" wire:click="$set('show_add_provider_form', false)" class="text-xs font-bold text-gray-500 hover:text-gray-800">✕ Cancelar</button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-indigo-950 mb-1">Nombre del Proveedor *</label>
                                <input type="text" wire:model="new_provider_name" placeholder="Ej: Bilbao Fotomatón / Navarra 360" class="w-full border-indigo-300 rounded-lg text-xs font-bold px-3 py-2 bg-white">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-indigo-950 mb-1">Zona / Ciudad</label>
                                <input type="text" wire:model="new_provider_zone" placeholder="Ej: Bilbao / Bizkaia / Navarra" class="w-full border-indigo-300 rounded-lg text-xs px-3 py-2 bg-white">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-indigo-950 mb-1">Contacto / Teléfono (uso interno)</label>
                                <input type="text" wire:model="new_provider_contact" placeholder="Ej: Laura 600 123 456" class="w-full border-indigo-300 rounded-lg text-xs px-3 py-2 bg-white">
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="button" wire:click="addPhotoboothProvider" class="px-4 py-2 rounded-lg text-xs font-black bg-emerald-600 text-white hover:bg-emerald-700 transition cursor-pointer shadow-xs">
                                ✅ Guardar y Configurar Proveedor
                            </button>
                        </div>
                    </div>
                @endif

                <!-- PANELES INDEPENDIENTES POR PROVEEDOR (Cada proveedor tiene sus propios inputs en el DOM con wire:key exclusivo para no mezclar jamás tarifas entre pestañas) -->
                @foreach($photobooth_providers as $provKey => $provData)
                    <div
                        wire:key="pb-provider-panel-{{ $provKey }}"
                        class="{{ $activeProvKey === $provKey ? 'block space-y-4' : 'hidden' }}"
                    >
                        <!-- Datos identificativos del proveedor -->
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-3">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Nombre del Proveedor (independiente)</label>
                                    <input
                                        type="text"
                                        wire:key="pb-in-{{ $provKey }}-name"
                                        wire:model.live.debounce.400ms="photobooth_providers.{{ $provKey }}.name"
                                        class="w-full border-indigo-300 rounded-lg text-xs font-black px-3 py-1.5 bg-white text-indigo-950"
                                    >
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Zona / Ciudad de Cobertura</label>
                                    <input
                                        type="text"
                                        wire:key="pb-in-{{ $provKey }}-zone"
                                        wire:model.live.debounce.400ms="photobooth_providers.{{ $provKey }}.zone"
                                        placeholder="Ej: La Rioja / Álava / Navarra"
                                        class="w-full border-slate-300 rounded-lg text-xs font-semibold px-3 py-1.5 bg-white"
                                    >
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Contacto / Teléfono Interno</label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="text"
                                            wire:key="pb-in-{{ $provKey }}-contact"
                                            wire:model.live.debounce.400ms="photobooth_providers.{{ $provKey }}.contact_info"
                                            placeholder="Ej: Águeda 697198216"
                                            class="w-full border-slate-300 rounded-lg text-xs px-3 py-1.5 bg-white"
                                        >
                                        @if(!in_array($provKey, ['logrono', 'vitoria']))
                                            <button
                                                type="button"
                                                wire:key="pb-del-{{ $provKey }}"
                                                wire:click="deletePhotoboothProvider('{{ $provKey }}')"
                                                wire:confirm="¿Seguro que quieres eliminar este proveedor externo?"
                                                class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 whitespace-nowrap cursor-pointer"
                                                title="Eliminar proveedor"
                                            >
                                                🗑️ Borrar
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- INTERRUPTORES DE SERVICIOS QUE OFRECE ESTE PROVEEDOR -->
                            <div class="pt-3 border-t border-slate-200">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                                    <span class="text-[11px] font-black uppercase tracking-wider text-slate-700">
                                        🎚️ Servicios activos para «{{ $provData['name'] ?? ucfirst($provKey) }}» (haz clic para activar o desactivar en el presupuesto):
                                    </span>
                                    <span class="text-[11px] text-emerald-700 font-semibold">✓ Guardado automático e independiente para {{ $provData['name'] ?? ucfirst($provKey) }}</span>
                                </div>
                                @php
                                    $serviceToggles = [
                                        'photobooth_enabled' => ['icon' => '📸', 'label' => 'Fotomatón'],
                                        'video_360_enabled' => ['icon' => '🎥', 'label' => 'Plataforma 360º'],
                                        'pack_duo_enabled' => ['icon' => '🔹', 'label' => 'Pack Dúo (Foto + 360º)'],
                                        'chocolate_fountain_enabled' => ['icon' => '🍫', 'label' => 'Fuente de Chocolate'],
                                        'candy_bar_enabled' => ['icon' => '🍬', 'label' => 'Mesa de Chuches'],
                                        'waffle_cart_enabled' => ['icon' => '🧇', 'label' => 'Carrito de Gofres'],
                                    ];
                                @endphp
                                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
                                    @foreach($serviceToggles as $flagKey => $meta)
                                        @php $isOn = !empty($provData[$flagKey]); @endphp
                                        <button
                                            type="button"
                                            wire:key="toggle-{{ $provKey }}-{{ $flagKey }}"
                                            wire:click="toggleProviderService('{{ $provKey }}', '{{ $flagKey }}')"
                                            class="px-2.5 py-2 rounded-xl border text-left transition cursor-pointer flex items-center justify-between gap-1.5 {{ $isOn ? 'bg-emerald-50 border-emerald-300 text-emerald-950 shadow-2xs' : 'bg-slate-100 border-slate-200 text-slate-400 hover:text-slate-600' }}"
                                        >
                                            <span class="text-xs font-bold truncate">{{ $meta['icon'] }} {{ $meta['label'] }}</span>
                                            <span class="text-[10px] font-black px-1.5 py-0.5 rounded {{ $isOn ? 'bg-emerald-600 text-white' : 'bg-slate-300 text-slate-600' }}">
                                                {{ $isOn ? 'ON' : 'OFF' }}
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- TARJETAS DE TARIFAS Y DESCRIPCIONES DEL PROVEEDOR -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            <!-- 1. FOTOMATÓN -->
                            <div class="p-4 rounded-xl border space-y-3 transition {{ !empty($provData['photobooth_enabled']) ? 'bg-indigo-50/40 border-indigo-200' : 'bg-slate-50 border-slate-200 opacity-60' }}">
                                <div class="flex items-center justify-between border-b border-indigo-100 pb-2">
                                    <span class="text-xs font-black uppercase text-indigo-900">📸 Fotomatón ({{ $provData['name'] }})</span>
                                    <button type="button" wire:key="btn-on-pb-{{ $provKey }}" wire:click="toggleProviderService('{{ $provKey }}', 'photobooth_enabled')" class="text-[10px] font-black px-2 py-0.5 rounded cursor-pointer {{ !empty($provData['photobooth_enabled']) ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                        {{ !empty($provData['photobooth_enabled']) ? '✓ Activo' : '✕ Desactivado' }}
                                    </button>
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Precio 2 Horas (€)</label>
                                        <input type="number" step="0.01" wire:key="pb-in-{{ $provKey }}-pb2h" wire:model.live.debounce.400ms="photobooth_providers.{{ $provKey }}.photobooth_2h" class="w-full border-indigo-300 rounded-lg text-sm font-black text-indigo-700">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Precio 3 Horas (€)</label>
                                        <input type="number" step="0.01" wire:key="pb-in-{{ $provKey }}-pb3h" wire:model.live.debounce.400ms="photobooth_providers.{{ $provKey }}.photobooth_3h" class="w-full border-indigo-300 rounded-lg text-sm font-black text-indigo-700">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-700 mb-1">+ Hora Extra (€/h)</label>
                                        <input type="number" step="0.01" wire:key="pb-in-{{ $provKey }}-pbextra" wire:model.live.debounce.400ms="photobooth_providers.{{ $provKey }}.photobooth_extra_hour" class="w-full border-indigo-300 rounded-lg text-sm font-black text-amber-700">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 mb-1">Plantilla Descriptiva (Qué incluye en PDF)</label>
                                    <textarea rows="3" wire:key="pb-in-{{ $provKey }}-pbdesc" wire:model.live.debounce.400ms="photobooth_providers.{{ $provKey }}.photobooth_desc" class="w-full border-gray-300 rounded-lg text-xs"></textarea>
                                </div>
                            </div>

                            <!-- 2. PLATAFORMA 360º -->
                            <div class="p-4 rounded-xl border space-y-3 transition {{ !empty($provData['video_360_enabled']) ? 'bg-purple-50/40 border-purple-200' : 'bg-slate-50 border-slate-200 opacity-60' }}">
                                <div class="flex items-center justify-between border-b border-purple-100 pb-2">
                                    <span class="text-xs font-black uppercase text-purple-900">🎥 Plataforma 360º ({{ $provData['name'] }})</span>
                                    <button type="button" wire:key="btn-on-360-{{ $provKey }}" wire:click="toggleProviderService('{{ $provKey }}', 'video_360_enabled')" class="text-[10px] font-black px-2 py-0.5 rounded cursor-pointer {{ !empty($provData['video_360_enabled']) ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                        {{ !empty($provData['video_360_enabled']) ? '✓ Activo' : '✕ Desactivado' }}
                                    </button>
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Precio 2 Horas (€)</label>
                                        <input type="number" step="0.01" wire:key="pb-in-{{ $provKey }}-v360_2h" wire:model.live.debounce.400ms="photobooth_providers.{{ $provKey }}.video_360_2h" class="w-full border-purple-300 rounded-lg text-sm font-black text-purple-700">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Precio 3 Horas (€)</label>
                                        <input type="number" step="0.01" wire:key="pb-in-{{ $provKey }}-v360_3h" wire:model.live.debounce.400ms="photobooth_providers.{{ $provKey }}.video_360_3h" class="w-full border-purple-300 rounded-lg text-sm font-black text-purple-700">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-700 mb-1">+ Hora Extra (€/h)</label>
                                        <input type="number" step="0.01" wire:key="pb-in-{{ $provKey }}-v360_extra" wire:model.live.debounce.400ms="photobooth_providers.{{ $provKey }}.video_360_extra_hour" class="w-full border-purple-300 rounded-lg text-sm font-black text-amber-700">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 mb-1">Plantilla Descriptiva (Qué incluye en PDF)</label>
                                    <textarea rows="3" wire:key="pb-in-{{ $provKey }}-v360_desc" wire:model.live.debounce.400ms="photobooth_providers.{{ $provKey }}.video_360_desc" class="w-full border-gray-300 rounded-lg text-xs"></textarea>
                                </div>
                            </div>

                            <!-- 3. PACK DÚO TODO INCLUIDO -->
                            <div class="p-4 rounded-xl border-2 space-y-3 transition {{ !empty($provData['pack_duo_enabled']) ? 'bg-gradient-to-br from-indigo-50/70 to-purple-50/70 border-indigo-300' : 'bg-slate-50 border-slate-200 opacity-60' }}">
                                <div class="flex items-center justify-between border-b border-indigo-200 pb-2">
                                    <span class="text-xs font-black uppercase text-indigo-950">🔹 Pack Dúo ({{ $provData['name'] }})</span>
                                    <button type="button" wire:key="btn-on-duo-{{ $provKey }}" wire:click="toggleProviderService('{{ $provKey }}', 'pack_duo_enabled')" class="text-[10px] font-black px-2 py-0.5 rounded cursor-pointer {{ !empty($provData['pack_duo_enabled']) ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                        {{ !empty($provData['pack_duo_enabled']) ? '✓ Activo' : '✕ Desactivado' }}
                                    </button>
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div>
                                        <label class="block text-[11px] font-bold text-indigo-950 mb-1">Pack Dúo 2h (€)</label>
                                        <input type="number" step="0.01" wire:key="pb-in-{{ $provKey }}-duo2h" wire:model.live.debounce.400ms="photobooth_providers.{{ $provKey }}.pack_duo_2h" class="w-full border-indigo-300 rounded-lg text-sm font-black text-indigo-700">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-indigo-950 mb-1">Pack Dúo 3h (€)</label>
                                        <input type="number" step="0.01" wire:key="pb-in-{{ $provKey }}-duo3h" wire:model.live.debounce.400ms="photobooth_providers.{{ $provKey }}.pack_duo_3h" class="w-full border-indigo-300 rounded-lg text-sm font-black text-indigo-700">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-indigo-950 mb-1">Reserva / Serv. (€)</label>
                                        <input type="number" step="0.01" wire:key="pb-in-{{ $provKey }}-deposit" wire:model.live.debounce.400ms="photobooth_providers.{{ $provKey }}.deposit_per_service" class="w-full border-indigo-300 rounded-lg text-sm font-bold text-gray-800">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-indigo-950 mb-1">Plantilla Descriptiva Pack Dúo</label>
                                    <textarea rows="3" wire:key="pb-in-{{ $provKey }}-duodesc" wire:model.live.debounce.400ms="photobooth_providers.{{ $provKey }}.pack_duo_desc" class="w-full border-indigo-300 rounded-lg text-xs"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- FILA 2: SERVICIOS DULCES / EXTRAS DE ESTE PROVEEDOR -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 pt-2">
                            <!-- 4. MESA CON FUENTE DE CHOCOLATE -->
                            <div class="p-4 rounded-xl border space-y-3 transition {{ !empty($provData['chocolate_fountain_enabled']) ? 'bg-amber-50/40 border-amber-200' : 'bg-slate-50 border-slate-200 opacity-60' }}">
                                <div class="flex items-center justify-between border-b border-amber-100 pb-2">
                                    <span class="text-xs font-black uppercase text-amber-900">🍫 Fuente de Chocolate ({{ $provData['name'] }})</span>
                                    <button type="button" wire:key="btn-on-choc-{{ $provKey }}" wire:click="toggleProviderService('{{ $provKey }}', 'chocolate_fountain_enabled')" class="text-[10px] font-black px-2 py-0.5 rounded cursor-pointer {{ !empty($provData['chocolate_fountain_enabled']) ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                        {{ !empty($provData['chocolate_fountain_enabled']) ? '✓ Activo' : '✕ No lo ofrece' }}
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-700 mb-1">1 Fuente · 2h (€)</label>
                                        <input type="number" step="0.01" wire:key="pb-in-{{ $provKey }}-choc1_2h" wire:model.live.debounce.400ms="photobooth_providers.{{ $provKey }}.chocolate_fountain_1_2h" class="w-full border-amber-300 rounded-lg text-sm font-black text-amber-800">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-700 mb-1">1 Fuente · 3h (€)</label>
                                        <input type="number" step="0.01" wire:key="pb-in-{{ $provKey }}-choc1_3h" wire:model.live.debounce.400ms="photobooth_providers.{{ $provKey }}.chocolate_fountain_1_3h" class="w-full border-amber-300 rounded-lg text-sm font-black text-amber-800">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-700 mb-1">2 Fuentes · 2h (€)</label>
                                        <input type="number" step="0.01" wire:key="pb-in-{{ $provKey }}-choc2_2h" wire:model.live.debounce.400ms="photobooth_providers.{{ $provKey }}.chocolate_fountain_2_2h" class="w-full border-amber-300 rounded-lg text-sm font-black text-amber-900">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-700 mb-1">2 Fuentes · 3h (€)</label>
                                        <input type="number" step="0.01" wire:key="pb-in-{{ $provKey }}-choc2_3h" wire:model.live.debounce.400ms="photobooth_providers.{{ $provKey }}.chocolate_fountain_2_3h" class="w-full border-amber-300 rounded-lg text-sm font-black text-amber-900">
                                    </div>
                                    <div class="col-span-2 sm:col-span-1">
                                        <label class="block text-[10px] font-bold text-gray-700 mb-1">Rincón Gofre (€/ud)</label>
                                        <input type="number" step="0.01" wire:key="pb-in-{{ $provKey }}-chocwaffle" wire:model.live.debounce.400ms="photobooth_providers.{{ $provKey }}.chocolate_fountain_waffle_extra" class="w-full border-amber-300 rounded-lg text-sm font-bold text-gray-800">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 mb-1">Plantilla Descriptiva (Qué incluye en PDF)</label>
                                    <textarea rows="3" wire:key="pb-in-{{ $provKey }}-chocdesc" wire:model.live.debounce.400ms="photobooth_providers.{{ $provKey }}.chocolate_fountain_desc" class="w-full border-gray-300 rounded-lg text-xs"></textarea>
                                </div>
                            </div>

                            <!-- 5. MESA DE SOLO CHUCHES -->
                            <div class="p-4 rounded-xl border space-y-3 transition {{ !empty($provData['candy_bar_enabled']) ? 'bg-pink-50/40 border-pink-200' : 'bg-slate-50 border-slate-200 opacity-60' }}">
                                <div class="flex items-center justify-between border-b border-pink-100 pb-2">
                                    <span class="text-xs font-black uppercase text-pink-900">🍬 Mesa de Chuches ({{ $provData['name'] }})</span>
                                    <button type="button" wire:key="btn-on-candy-{{ $provKey }}" wire:click="toggleProviderService('{{ $provKey }}', 'candy_bar_enabled')" class="text-[10px] font-black px-2 py-0.5 rounded cursor-pointer {{ !empty($provData['candy_bar_enabled']) ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                        {{ !empty($provData['candy_bar_enabled']) ? '✓ Activo' : '✕ No lo ofrece' }}
                                    </button>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 mb-1">Precio Servicio Completo (€)</label>
                                    <input type="number" step="0.01" wire:key="pb-in-{{ $provKey }}-candyprice" wire:model.live.debounce.400ms="photobooth_providers.{{ $provKey }}.candy_bar_price" class="w-full border-pink-300 rounded-lg text-sm font-black text-pink-800">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 mb-1">Plantilla Descriptiva (Qué incluye en PDF)</label>
                                    <textarea rows="3" wire:key="pb-in-{{ $provKey }}-candydesc" wire:model.live.debounce.400ms="photobooth_providers.{{ $provKey }}.candy_bar_desc" class="w-full border-gray-300 rounded-lg text-xs"></textarea>
                                </div>
                            </div>

                            <!-- 6. CARRITO DE GOFRES -->
                            <div class="p-4 rounded-xl border space-y-3 transition {{ !empty($provData['waffle_cart_enabled']) ? 'bg-orange-50/40 border-orange-200' : 'bg-slate-50 border-slate-200 opacity-60' }}">
                                <div class="flex items-center justify-between border-b border-orange-100 pb-2">
                                    <span class="text-xs font-black uppercase text-orange-900">🧇 Carrito de Gofres ({{ $provData['name'] }})</span>
                                    <button type="button" wire:key="btn-on-waffle-{{ $provKey }}" wire:click="toggleProviderService('{{ $provKey }}', 'waffle_cart_enabled')" class="text-[10px] font-black px-2 py-0.5 rounded cursor-pointer {{ !empty($provData['waffle_cart_enabled']) ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                        {{ !empty($provData['waffle_cart_enabled']) ? '✓ Activo' : '✕ No lo ofrece' }}
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-700 mb-1">2h (~60 gofres) (€)</label>
                                        <input type="number" step="0.01" wire:key="pb-in-{{ $provKey }}-waffle2h" wire:model.live.debounce.400ms="photobooth_providers.{{ $provKey }}.waffle_cart_2h" class="w-full border-orange-300 rounded-lg text-sm font-black text-orange-800">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-700 mb-1">3h (~100 gofres) (€)</label>
                                        <input type="number" step="0.01" wire:key="pb-in-{{ $provKey }}-waffle3h" wire:model.live.debounce.400ms="photobooth_providers.{{ $provKey }}.waffle_cart_3h" class="w-full border-orange-300 rounded-lg text-sm font-black text-orange-800">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 mb-1">Plantilla Descriptiva (Qué incluye en PDF)</label>
                                    <textarea rows="3" wire:key="pb-in-{{ $provKey }}-waffledesc" wire:model.live.debounce.400ms="photobooth_providers.{{ $provKey }}.waffle_cart_desc" class="w-full border-gray-300 rounded-lg text-xs"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- SECCIÓN 4: CONDICIONES DE PAGO Y SEÑAL DE RESERVA -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 space-y-5">
                <div class="border-b border-gray-100 pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <span>💶</span> Señal de Reserva y Datos de Cobro
                        </h3>
                        <p class="text-xs text-gray-500">Configura el importe o porcentaje que se solicita como señal a la firma del contrato y los datos de pago.</p>
                    </div>
                    <span class="text-xs font-bold text-emerald-800 bg-emerald-100 px-3 py-1 rounded-full self-start sm:self-auto">
                        Configuración Global
                    </span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Configuración de Señal -->
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-4">
                        <h4 class="text-xs font-black uppercase tracking-wider text-slate-800">
                            Cálculo de la Señal por Defecto
                        </h4>

                        <div class="space-y-3">
                            <label class="block text-xs font-bold text-gray-700">Modalidad de Señal</label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer transition {{ $deposit_type === 'percentage' ? 'bg-indigo-50 border-indigo-500 text-indigo-950 font-bold' : 'bg-white border-gray-200 text-gray-700' }}">
                                    <input type="radio" wire:model.live="deposit_type" value="percentage" class="text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-xs">Porcentaje (%)</span>
                                </label>
                                <label class="flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer transition {{ $deposit_type === 'fixed' ? 'bg-indigo-50 border-indigo-500 text-indigo-950 font-bold' : 'bg-white border-gray-200 text-gray-700' }}">
                                    <input type="radio" wire:model.live="deposit_type" value="fixed" class="text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-xs">Importe Fijo (€)</span>
                                </label>
                            </div>

                            @if($deposit_type === 'percentage')
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Porcentaje de Señal (%)</label>
                                    <div class="flex items-center gap-2">
                                        <input type="number" step="1" min="0" max="100" wire:model.live="deposit_percentage" class="w-28 border-gray-300 rounded-lg text-sm font-black text-indigo-700">
                                        <span class="text-sm font-bold text-gray-600">% sobre el total del presupuesto</span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 mt-1">Ej: Con 40%, en un presupuesto de 1.000 € se pedirán 400 € de señal y 600 € restantes.</p>
                                </div>
                            @else
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Importe Fijo de Reserva (€)</label>
                                    <div class="flex items-center gap-2">
                                        <input type="number" step="1" min="0" wire:model.live="deposit_fixed_amount" class="w-28 border-gray-300 rounded-lg text-sm font-black text-indigo-700">
                                        <span class="text-sm font-bold text-gray-600">€ fijos por evento</span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 mt-1">Ej: Con 200 €, en un presupuesto de 700 € se pedirán 200 € de señal y 500 € restantes.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Datos Bancarios & Bizum -->
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-4">
                        <h4 class="text-xs font-black uppercase tracking-wider text-slate-800">
                            Cuentas y Métodos de Cobro
                        </h4>

                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">IBAN Cuenta Bancaria (Transferencias)</label>
                                <input type="text" wire:model="company_iban" placeholder="ES00 0000 0000 0000 0000 0000" class="w-full border-gray-300 rounded-lg font-mono text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Teléfono para Cobros por Bizum</label>
                                <input type="text" wire:model="company_bizum" placeholder="622634790" class="w-full border-gray-300 rounded-lg font-mono text-xs">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- PESTAÑA: EDITOR DE CONTRATOS -->
        <div class="{{ $activeTab === 'contracts' ? 'block' : 'hidden' }} space-y-6">
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Plantilla Oficial de Contrato</h3>
                        <p class="text-xs text-gray-500">Diseña las cláusulas legales y términos que firmarán tus clientes en el portal online.</p>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-gray-500">Cargar Plantilla:</span>
                        @foreach($presets as $key => $preset)
                            <button type="button" wire:click="loadPreset('{{ $key }}')" class="px-2.5 py-1 text-xs font-bold rounded-lg border border-gray-300 hover:bg-indigo-50 hover:text-indigo-600 transition {{ $selectedPreset === $key ? 'bg-indigo-100 text-indigo-800 border-indigo-400' : 'bg-white' }}">
                                {{ $preset['name'] }}
                            </button>
                        @endforeach
                    </div>
                </div>

                @if(session()->has('contract_preset_loaded'))
                    <div class="p-3 bg-amber-50 text-amber-900 border border-amber-200 rounded-xl text-xs font-semibold">
                        {{ session('contract_preset_loaded') }}
                    </div>
                @endif

                <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <span class="text-xs font-bold text-slate-700 block">Etiquetas dinámicas (Haz clic para insertar):</span>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($availableVariables as $tag => $desc)
                            <button type="button" wire:click="insertVariable('{{ $tag }}')" title="{{ $desc }}" class="px-2 py-1 bg-white hover:bg-indigo-50 border border-slate-300 hover:border-indigo-400 text-slate-700 hover:text-indigo-700 text-[11px] font-mono rounded font-medium transition shadow-2xs">
                                {{ $tag }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-800 mb-1">Título del Documento *</label>
                    <input type="text" wire:model="contract_title" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm font-bold">
                    @error('contract_title') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-bold text-gray-800">Cláusulas y Condiciones del Contrato *</label>
                        <span class="text-xs text-gray-400 font-mono">Texto completo con saltos de línea</span>
                    </div>
                    <textarea wire:model="contract_body" rows="18" class="w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500 font-mono text-xs leading-relaxed text-gray-800"></textarea>
                    @error('contract_body') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-800 mb-1">Pie de Página / Aviso Legal</label>
                    <input type="text" wire:model="contract_footer" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs text-gray-600">
                </div>
            </div>
        </div>

        <!-- PESTAÑA: CONFIGURACIÓN DE DOSSIER Y PROPUESTAS COMERCIALES -->
        <div class="{{ $activeTab === 'dossier' ? 'block' : 'hidden' }} space-y-6">
            
            <!-- BANNER DE ACCESO DIRECTO AL NUEVO GENERADOR DE DOSSIER GENERAL DE TARIFAS -->
            <div class="bg-gradient-to-r from-slate-900 via-slate-900 to-amber-950/80 text-white rounded-2xl p-5 shadow-md border border-amber-500/40 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="inline-flex items-center gap-2 text-[11px] font-black uppercase tracking-wider text-amber-400">
                        <span>📘</span> Nuevo Generador Oficial (8 a 10 páginas)
                    </div>
                    <h3 class="text-base font-black text-white">Dossier General de Tarifas (Igual para todas las parejas)</h3>
                    <p class="text-xs text-slate-300">Gestiona el dossier general de tarifas con checklist de validación (Portada, Quiénes somos, Nuestro trabajo, Cómo trabajamos, 3 Packs en página central con IVA incluido, Extras cerrados, Opiniones y Cómo reservar), vista de videollamada y envío por WhatsApp. <em>(Esta pestaña de abajo se mantiene para las propuestas personalizadas por boda).</em></p>
                </div>
                <a href="{{ route('admin.tariff-dossier') }}" class="px-4 py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs rounded-xl shadow-lg transition shrink-0 text-center">
                    Abrir Generador de Dossier de Tarifas &rarr;
                </a>
            </div>

            <!-- HEADER DE SECCIÓN CON PRESETS & BOTÓN GUARDAR -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 space-y-4">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-gray-100 pb-4">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <span>📖</span> Personalización de Dossier y Propuestas PDF
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Configura los textos de presentación, bloques de equipamiento y fotos reales de tus montajes que verán tus clientes al descargar o recibir la propuesta comercial.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <!-- BOTONES DE PRESETS / PLANTILLAS RÁPIDAS -->
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="text-xs font-bold text-gray-500 mr-1">Plantilla:</span>
                            @foreach($dossierPresets as $key => $preset)
                                <button type="button" wire:click="loadDossierPreset('{{ $key }}')" class="px-2.5 py-1.5 text-xs font-bold rounded-xl border transition flex items-center gap-1 shadow-2xs {{ $selectedDossierPreset === $key ? 'bg-amber-100 text-amber-900 border-amber-400 font-black' : 'bg-white text-gray-700 border-gray-300 hover:bg-amber-50 hover:text-amber-800' }}">
                                    {{ $preset['name'] }}
                                </button>
                            @endforeach
                        </div>

                        <!-- BOTÓN DIRECTO GUARDAR DOSSIER -->
                        <button 
                            type="button" 
                            wire:click="save"
                            wire:loading.attr="disabled"
                            wire:target="save"
                            class="px-4 py-2 bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-700 hover:to-amber-800 text-white font-black text-xs rounded-xl shadow-md transition flex items-center gap-2 cursor-pointer disabled:opacity-50 shrink-0"
                        >
                            <span wire:loading.remove wire:target="save">💾 Guardar Dossier</span>
                            <span wire:loading wire:target="save" class="inline-flex items-center gap-1.5">
                                <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                Guardando...
                            </span>
                        </button>
                    </div>
                </div>

                @if(session()->has('message'))
                    <div class="p-3.5 bg-emerald-50 text-emerald-900 border-2 border-emerald-300 rounded-xl text-xs font-bold flex items-center justify-between shadow-xs animate-pulse">
                        <div class="flex items-center gap-2">
                            <span class="text-base">✅</span>
                            <span>{{ session('message') }}</span>
                        </div>
                        <span class="text-[10px] uppercase font-black tracking-wider bg-emerald-200 text-emerald-800 px-2 py-0.5 rounded-md">Guardado</span>
                    </div>
                @endif

                @if(session()->has('dossier_preset_loaded'))
                    <div class="p-3 bg-amber-50 text-amber-900 border border-amber-200 rounded-xl text-xs font-semibold flex items-center gap-2">
                        <span>✨</span>
                        <span>{{ session('dossier_preset_loaded') }}</span>
                    </div>
                @endif
            </div>

            <!-- SECCIÓN 1: PORTADA (PÁGINA 1) -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h4 class="text-sm font-black text-gray-900 flex items-center gap-2">
                        <span>📑</span> 1. Portada del Documento (Página 1)
                    </h4>
                    <span class="text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-0.5 rounded-full">
                        Portada Elegante
                    </span>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold text-gray-700">Título Principal de Portada</label>
                        <span class="text-[11px] text-gray-400">Puedes usar saltos de línea para estructurar el título</span>
                    </div>
                    <textarea wire:model="dossier_cover_title" rows="2" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm font-bold text-gray-900"></textarea>
                    <p class="text-[11px] text-gray-400 mt-1">Ej: "Propuesta para\ntu evento" o "Propuesta para\nvuestra boda".</p>
                </div>
            </div>

            <!-- SECCIÓN 2: PÁGINA 2 - CABECERA Y TEXTO INTRODUCTORIO -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h4 class="text-sm font-black text-gray-900 flex items-center gap-2">
                        <span>📢</span> 2. Presentación y Qué Llevamos (Página 2)
                    </h4>
                    <span class="text-[11px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2.5 py-0.5 rounded-full">
                        Cabecera & Bienvenida
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Subtítulo Superior de Cabecera</label>
                        <input type="text" wire:model="dossier_page2_subtitle" placeholder="QUÉ LLEVAMOS" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Título de la Página 2</label>
                        <input type="text" wire:model="dossier_page2_title" placeholder="DJ, sonido e iluminación propios" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm font-bold">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Párrafo de Introducción / Propuesta de Valor</label>
                    <textarea wire:model="dossier_intro_text" rows="3" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm text-gray-800 leading-relaxed"></textarea>
                    <p class="text-[11px] text-gray-400 mt-1">Este texto aparece justo debajo del encabezado en la segunda página del PDF.</p>
                </div>
            </div>

            <!-- SECCIÓN 3: GALERÍA DE MONTAJES Y FOTOS REALES (1 A 4 FOTOS) -->
            <div class="bg-white dark:bg-slate-900 shadow-sm rounded-xl border border-gray-200 dark:border-slate-800 p-6 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 dark:border-slate-800 pb-4">
                    <div>
                        <h4 class="text-base font-black text-gray-900 dark:text-white flex items-center gap-2">
                            <span>📸</span> 3. Fotos de Montajes y Equipamiento Real
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Sube libremente de 1 a 4 fotos de tus montajes (reales, cabinas de madera o discoteca, iluminación, fiesta o renders). El dossier PDF organizará las fotos automáticamente en un mosaico profesional.</p>
                    </div>
                    <span class="text-xs font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 px-3 py-1 rounded-full whitespace-nowrap self-start sm:self-auto">
                        Galería Adaptable (1 a 4 fotos)
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    @php
                        $photoSlots = [
                            1 => ['key' => 'photo1', 'defaultTitle' => 'Montaje de Sonido & Acústica', 'icon' => '🔊', 'color' => 'amber'],
                            2 => ['key' => 'photo2', 'defaultTitle' => 'Iluminación & Efectos Visuales', 'icon' => '💡', 'color' => 'indigo'],
                            3 => ['key' => 'photo3', 'defaultTitle' => 'Cabina DJ & Fiesta en Directo', 'icon' => '🎧', 'color' => 'purple'],
                            4 => ['key' => 'photo4', 'defaultTitle' => 'Montajes Especiales & Fincas', 'icon' => '🌟', 'color' => 'emerald'],
                        ];
                    @endphp

                    @foreach($photoSlots as $idx => $slot)
                        @php
                            $imgProp = "dossier_{$slot['key']}_image";
                            $uploadProp = "dossier_{$slot['key']}_image_upload";
                            $titleProp = "dossier_{$slot['key']}_title";
                            $descProp = "dossier_{$slot['key']}_desc";
                            $currentImg = $$imgProp;
                            $currentUpload = $$uploadProp;
                        @endphp
                        
                        <div class="p-5 rounded-2xl border transition bg-slate-50 dark:bg-slate-800/80 border-slate-200 dark:border-slate-700/80 shadow-xs flex flex-col justify-between space-y-4">
                            <div class="space-y-4">
                                <!-- Cabecera de la Tarjeta -->
                                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700/80 pb-3">
                                    <div class="flex items-center gap-2 font-black text-xs text-slate-800 dark:text-white uppercase tracking-wider">
                                        <span class="h-6 w-6 rounded-lg bg-amber-500/15 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center text-sm font-normal">
                                            {{ $slot['icon'] }}
                                        </span>
                                        <span>Foto {{ $idx }} &bull; {{ $slot['defaultTitle'] }}</span>
                                    </div>
                                    @if($currentImg)
                                        <button type="button" wire:click="deleteDossierImage('{{ $slot['key'] }}')" class="text-[11px] text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300 font-bold px-2.5 py-1 rounded-md bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/50 transition cursor-pointer">
                                            ✕ Quitar Foto
                                        </button>
                                    @endif
                                </div>

                                <!-- Preview / Marco de Imagen -->
                                <div class="space-y-2">
                                    @if ($currentUpload)
                                        <div class="relative rounded-xl overflow-hidden border-2 border-amber-500 h-44 bg-slate-950 shadow-inner flex items-center justify-center">
                                            <img src="{{ $currentUpload->temporaryUrl() }}" class="w-full h-full object-contain">
                                            <span class="absolute bottom-2 right-2 bg-amber-500 text-slate-950 text-[10px] font-black px-2 py-0.5 rounded-md shadow">
                                                Nueva Foto Seleccionada
                                            </span>
                                        </div>
                                    @elseif ($currentImg)
                                        <div class="relative rounded-xl overflow-hidden border border-slate-300 dark:border-slate-700 h-44 bg-slate-950 shadow-inner flex items-center justify-center">
                                            <img src="{{ asset('storage/' . $currentImg) }}" class="w-full h-full object-contain">
                                            <span class="absolute bottom-2 right-2 bg-slate-900/90 border border-slate-700 text-emerald-400 text-[10px] font-bold px-2 py-0.5 rounded-md">
                                                ✓ Foto Activa
                                            </span>
                                        </div>
                                    @else
                                        <div class="rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-700/80 h-36 flex flex-col items-center justify-center text-slate-400 dark:text-slate-500 bg-white dark:bg-slate-900/60">
                                            <span class="text-3xl opacity-70 mb-1">{{ $slot['icon'] }}</span>
                                            <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">Sin foto asignada</span>
                                            <span class="text-[10px] text-slate-400 dark:text-slate-500">Se mostrará el diseño estándar en el PDF</span>
                                        </div>
                                    @endif

                                    <!-- Selector de Archivo -->
                                    <div class="pt-1">
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Subir / Cambiar Foto:</label>
                                        <input type="file" wire:model="{{ $uploadProp }}" accept="image/*" class="w-full text-xs text-slate-600 dark:text-slate-300 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 dark:file:bg-indigo-900/50 file:text-indigo-700 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/80 cursor-pointer bg-white dark:bg-slate-900 rounded-lg p-1.5 border border-slate-200 dark:border-slate-700">
                                        @error($uploadProp) <span class="text-rose-500 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <!-- Inputs de Texto -->
                                <div class="space-y-3 pt-1">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Título / Etiqueta del Montaje (Opcional)</label>
                                        <input type="text" wire:model="{{ $titleProp }}" placeholder="{{ $slot['defaultTitle'] }}" class="w-full bg-white dark:bg-slate-900 border border-gray-300 dark:border-slate-700 rounded-lg shadow-2xs text-xs font-bold text-gray-900 dark:text-white p-2.5 focus:ring-amber-500 focus:border-amber-500">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Pie de Foto / Descripción (Opcional)</label>
                                        <textarea wire:model="{{ $descProp }}" rows="2" placeholder="Breve detalle sobre este montaje o servicio..." class="w-full bg-white dark:bg-slate-900 border border-gray-300 dark:border-slate-700 rounded-lg shadow-2xs text-xs text-gray-800 dark:text-slate-200 p-2.5 focus:ring-amber-500 focus:border-amber-500"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                </div>
            </div>

            <!-- SECCIÓN FOTO PANORÁMICA DE FIESTA / PIE DE PÁGINA 2 -->
            <div class="bg-white dark:bg-slate-800/90 shadow-sm rounded-xl border border-gray-200 dark:border-slate-700/80 p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-slate-700 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">📸</span>
                        <div>
                            <h4 class="text-sm font-black text-gray-900 dark:text-white flex items-center gap-2">
                                Foto Panorámica de Fiesta & Montaje (Pie de Página 2 del Dossier)
                            </h4>
                            <p class="text-xs text-gray-500 dark:text-slate-400">Esta imagen horizontal ocupará todo el ancho inferior de la página 2 del dossier, cubriendo el hueco y enmarcando el pie de página de forma impecable.</p>
                        </div>
                    </div>
                    @if($dossier_banner_image)
                        <button type="button" wire:click="deleteDossierImage('banner')" class="text-[11px] text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300 font-bold px-2.5 py-1 rounded-md bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/50 transition cursor-pointer">
                            ✕ Quitar Foto
                        </button>
                    @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                    <div>
                        @if ($dossier_banner_image_upload)
                            <div class="relative rounded-xl overflow-hidden border-2 border-amber-500 h-36 bg-slate-950 shadow-inner flex items-center justify-center">
                                <img src="{{ $dossier_banner_image_upload->temporaryUrl() }}" class="w-full h-full object-cover">
                                <span class="absolute bottom-2 right-2 bg-amber-500 text-slate-950 text-[10px] font-black px-2 py-0.5 rounded-md shadow">
                                    Nueva Foto Seleccionada
                                </span>
                            </div>
                        @elseif ($dossier_banner_image)
                            <div class="relative rounded-xl overflow-hidden border border-slate-300 dark:border-slate-700 h-36 bg-slate-950 shadow-inner flex items-center justify-center">
                                <img src="{{ asset('storage/' . $dossier_banner_image) }}" class="w-full h-full object-cover">
                                <span class="absolute bottom-2 right-2 bg-slate-900/90 border border-slate-700 text-emerald-400 text-[10px] font-bold px-2 py-0.5 rounded-md">
                                    ✓ Foto Activa en Dossier
                                </span>
                            </div>
                        @else
                            <div class="rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-700/80 h-36 flex flex-col items-center justify-center text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-900/60 p-4 text-center">
                                <span class="text-3xl opacity-70 mb-1">🎉</span>
                                <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">Sin foto panorámica asignada</span>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Sube una foto horizontal de la pista de baile, cabina iluminada o fuegos fríos</span>
                            </div>
                        @endif

                        <div class="pt-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Subir Foto Panorámica (Recomendado: horizontal de 1200x400 px aprox.):</label>
                            <input type="file" wire:model="dossier_banner_image_upload" accept="image/*" class="w-full text-xs text-slate-600 dark:text-slate-300 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 dark:file:bg-indigo-900/50 file:text-indigo-700 dark:file:text-indigo-300 hover:file:bg-indigo-100 cursor-pointer bg-white dark:bg-slate-900 rounded-lg p-1.5 border border-slate-200 dark:border-slate-700">
                            @error('dossier_banner_image_upload') <span class="text-rose-500 text-xs font-bold block mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Título sobre la foto (Opcional)</label>
                            <input type="text" wire:model="dossier_banner_title" placeholder="Fiesta en Directo & Iluminación Espectacular" class="w-full bg-white dark:bg-slate-900 border border-gray-300 dark:border-slate-700 rounded-lg shadow-2xs text-xs font-bold text-gray-900 dark:text-white p-2.5 focus:ring-amber-500 focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Frase / Subtítulo descriptivo (Opcional)</label>
                            <textarea wire:model="dossier_banner_subtitle" rows="3" placeholder="Pista de baile siempre llena con efectos visuales y sonido de máxima fidelidad..." class="w-full bg-white dark:bg-slate-900 border border-gray-300 dark:border-slate-700 rounded-lg shadow-2xs text-xs text-gray-800 dark:text-slate-200 p-2.5 focus:ring-amber-500 focus:border-amber-500"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 4: CÓMO TRABAJAMOS (PÁGINA 2) -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h4 class="text-sm font-black text-gray-900 flex items-center gap-2">
                        <span>📋</span> 4. Bloque "Cómo Trabajamos" (Página 2)
                    </h4>
                    <span class="text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-0.5 rounded-full">
                        Proceso de Trabajo
                    </span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Título del Bloque</label>
                    <input type="text" wire:model="dossier_work_title" placeholder="Cómo trabajamos" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm font-bold">
                </div>

                <div class="space-y-3 pt-2">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Punto 1 (Montaje y pruebas)</label>
                        <input type="text" wire:model="dossier_work_item1" class="w-full border-gray-300 rounded-lg shadow-sm text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Punto 2 (Personalización musical y coordinación)</label>
                        <input type="text" wire:model="dossier_work_item2" class="w-full border-gray-300 rounded-lg shadow-sm text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Punto 3 (Desmontaje y tranquilidad)</label>
                        <input type="text" wire:model="dossier_work_item3" class="w-full border-gray-300 rounded-lg shadow-sm text-xs">
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 5: CONDICIONES Y NOTAS INFORMATIVAS (PÁGINA 3) -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h4 class="text-sm font-black text-gray-900 flex items-center gap-2">
                        <span>💬</span> 5. Tarjetas Informativas y Condiciones (Página 3)
                    </h4>
                    <span class="text-[11px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2.5 py-0.5 rounded-full">
                        Página de Packs y Precios
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Tarjeta Horas Extra -->
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Título Tarjeta 1 (Horas extra)</label>
                            <input type="text" wire:model="dossier_extra_hours_title" class="w-full border-gray-300 rounded-lg text-xs font-bold">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Texto Informativo</label>
                            <textarea wire:model="dossier_extra_hours_desc" rows="3" class="w-full border-gray-300 rounded-lg text-xs text-gray-700"></textarea>
                        </div>
                    </div>

                    <!-- Tarjeta Personalización Canciones -->
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Título Tarjeta 2 (Personalización)</label>
                            <input type="text" wire:model="dossier_music_custom_title" class="w-full border-gray-300 rounded-lg text-xs font-bold">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Texto Informativo</label>
                            <textarea wire:model="dossier_music_custom_desc" rows="3" class="w-full border-gray-300 rounded-lg text-xs text-gray-700"></textarea>
                        </div>
                    </div>
                </div>

                <!-- VALIDEZ Y CADUCIDAD DE LAS PROPUESTAS COMERCIALES -->
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-slate-800">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-lg">⏳</span>
                        <div>
                            <h4 class="font-bold text-gray-800 dark:text-white text-xs uppercase tracking-wider">Caducidad y Validez de las Propuestas (PDF & WhatsApp)</h4>
                            <p class="text-[11px] text-gray-500 dark:text-slate-400">Configura el plazo de días naturales tras el cual la propuesta comercial caduca para poder revisar o actualizar tarifas si no responden.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center bg-amber-50/50 dark:bg-amber-950/20 p-4 rounded-xl border border-amber-200/80 dark:border-amber-800/40">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-slate-300 mb-1">Días de validez garantizada de la oferta</label>
                            <div class="flex items-center gap-2">
                                <input type="number" min="1" max="365" wire:model="quote_validity_days" class="w-24 border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white rounded-lg text-xs font-bold text-center focus:ring-amber-500 focus:border-amber-500">
                                <span class="text-xs font-semibold text-gray-600 dark:text-slate-300">días naturales desde la fecha de emisión</span>
                            </div>
                            <p class="text-[11px] text-gray-400 dark:text-slate-400 mt-1">Por defecto: 15 días (o 30 días si prefieres mayor margen).</p>
                        </div>

                        <div class="p-3 bg-white dark:bg-slate-900 rounded-xl border border-amber-200 dark:border-amber-800/50 text-xs text-amber-900 dark:text-amber-300 space-y-1 shadow-2xs">
                            <div class="font-bold flex items-center gap-1 text-[11px]">
                                <span>💡</span> ¿Cómo se refleja en el PDF y en WhatsApp?
                            </div>
                            <p class="text-[11px] text-gray-600 dark:text-slate-300 leading-relaxed">
                                El PDF calculará la fecha límite exacta (ej: <em>"Válido hasta el 28 de octubre de 2026"</em>) tanto en la portada como en el desglose de tarifas. Pasada esa fecha, el presupuesto figurará como caducado y podrás actualizar las tarifas con total justificación.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- PESTAÑA: INTEGRACIONES APPLE MUSIC & SPOTIFY -->
        <div class="{{ $activeTab === 'music_integrations' ? 'block' : 'hidden' }} space-y-6">
            
            <!-- MOTOR PRINCIPAL DE STREAMING EN CABINA -->
            <div class="bg-gradient-to-r from-slate-900 to-indigo-950 text-white rounded-2xl p-6 shadow-md border border-slate-800 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-black text-white flex items-center gap-2">
                            <span>🎧</span> Motor de Streaming Preferido en Cabina
                        </h3>
                        <p class="text-xs text-slate-300 mt-0.5">Elige qué plataforma tiene prioridad cuando el DJ pulse Play sobre una canción en el Modo Cabina.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                    <label class="flex items-center gap-3 p-3.5 rounded-xl border {{ $primary_streaming_service === 'auto' ? 'border-cyan-400 bg-cyan-950/40 ring-1 ring-cyan-400' : 'border-slate-700 bg-slate-900/60' }} cursor-pointer transition">
                        <input type="radio" wire:model="primary_streaming_service" value="auto" class="text-cyan-500 focus:ring-cyan-500">
                        <div>
                            <span class="text-xs font-bold text-white block">⚡ Automático Inteligente</span>
                            <span class="text-[11px] text-slate-400">Detecta si la canción tiene enlace de Apple Music, Spotify o MP3 local.</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3.5 rounded-xl border {{ $primary_streaming_service === 'apple_music' ? 'border-pink-500 bg-pink-950/40 ring-1 ring-pink-500' : 'border-slate-700 bg-slate-900/60' }} cursor-pointer transition">
                        <input type="radio" wire:model="primary_streaming_service" value="apple_music" class="text-pink-500 focus:ring-pink-500">
                        <div>
                            <span class="text-xs font-bold text-white block">🍎 Apple Music (MusicKit)</span>
                            <span class="text-[11px] text-slate-400">Prioriza reproducción completa con cuenta de Apple Music.</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3.5 rounded-xl border {{ $primary_streaming_service === 'spotify' ? 'border-emerald-500 bg-emerald-950/40 ring-1 ring-emerald-500' : 'border-slate-700 bg-slate-900/60' }} cursor-pointer transition">
                        <input type="radio" wire:model="primary_streaming_service" value="spotify" class="text-emerald-500 focus:ring-emerald-500">
                        <div>
                            <span class="text-xs font-bold text-white block">🟢 Spotify (Web SDK)</span>
                            <span class="text-[11px] text-slate-400">Prioriza reproducción completa con cuenta de Spotify Premium.</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- SECCIÓN 1: APPLE MUSIC (MUSICKIT JS STREAMING) -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 space-y-5">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-pink-100 text-pink-700 flex items-center justify-center font-bold text-2xl shadow-sm">
                            🍎
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-gray-900">Integración con Apple Music (MusicKit JS)</h3>
                            <p class="text-xs text-gray-500">Streaming de canciones completas con calidad Lossless, búsqueda oficial y deep-linking.</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-pink-50 text-pink-700 border border-pink-200 flex items-center gap-1.5">
                        MusicKit v3 JS Activo
                    </span>
                </div>

                <div class="p-4 bg-pink-50/50 rounded-2xl border border-pink-200/80 space-y-2">
                    <h4 class="text-xs font-black uppercase text-pink-800 tracking-wider flex items-center gap-2">
                        <span>✨</span> ¿Cómo funciona el Streaming Completo con Apple Music?
                    </h4>
                    <p class="text-xs text-pink-950 leading-relaxed">
                        1. <strong>Búsqueda y Preescuchas de 30s</strong>: Funcionan de forma automática, gratuita y sin configurar nada.<br>
                        2. <strong>Streaming de canciones completas</strong>: Puedes introducir un <strong>Apple Music Developer Token</strong> para que el DJ inicie sesión con su Apple ID en la cabina y escuche las canciones al 100% de su duración con la máxima calidad de Apple.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">País del Catálogo de Apple Music</label>
                        <select wire:model="apple_music_country" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-pink-500 focus:border-pink-500 text-sm">
                            <option value="es">🇪🇸 España (ES)</option>
                            <option value="mx">🇲🇽 México (MX)</option>
                            <option value="us">🇺🇸 Estados Unidos (US)</option>
                            <option value="gb">🇬🇧 Reino Unido (GB)</option>
                            <option value="fr">🇫🇷 Francia (FR)</option>
                            <option value="it">🇮🇹 Italia (IT)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Acción al pulsar botón "Apple Music"</label>
                        <select wire:model="apple_music_launch_mode" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-pink-500 focus:border-pink-500 text-sm">
                            <option value="app">📲 Abrir directamente en la App nativa de Apple Music (music://)</option>
                            <option value="web">🌐 Abrir en Apple Music Web (music.apple.com)</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 mb-1">Apple Music Developer Token (Opcional para MusicKit Web Streaming)</label>
                        <textarea wire:model="apple_music_developer_token" rows="2" placeholder="eyJhbGciOiJFUzI1NiIs..." class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-pink-500 focus:border-pink-500 text-xs font-mono"></textarea>
                        <span class="text-[11px] text-gray-400 mt-1 block">Generado en el portal de Apple Developer (Media Services ➔ MusicKit). Si se deja vacío, se usan las preescuchas de alta calidad de 30s.</span>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2: SPOTIFY (WEB PLAYBACK SDK & OAUTH) -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-2xl shadow-sm">
                            🟢
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-gray-900">Integración con Spotify & Web Playback SDK</h3>
                            <p class="text-xs text-gray-500">Streaming completo de canciones de 3-4 minutos en cabina, búsqueda y deep-linking.</p>
                        </div>
                    </div>
                    @if(!empty($spotifyUser['connected']))
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Cuenta Spotify Conectada
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                            Modo Preview 30s
                        </span>
                    @endif
                </div>

                <!-- ESTADO DE LA CUENTA OAUTH SPOTIFY -->
                <div class="p-4 rounded-2xl border {{ !empty($spotifyUser['connected']) ? 'bg-emerald-50/60 border-emerald-200' : 'bg-slate-50 border-slate-200' }}">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <span class="text-xs font-black uppercase tracking-wider block {{ !empty($spotifyUser['connected']) ? 'text-emerald-800' : 'text-slate-700' }}">
                                🎧 Estado del Streaming en Cabina:
                            </span>
                            @if(!empty($spotifyUser['connected']))
                                <p class="text-sm font-bold text-gray-900 mt-0.5">
                                    Conectado como: <span class="text-emerald-700 font-extrabold">{{ $spotifyUser['name'] }}</span> 
                                    @if(!empty($spotifyUser['email'])) <span class="text-xs text-gray-500 font-normal">({{ $spotifyUser['email'] }})</span> @endif
                                </p>
                                <p class="text-xs text-emerald-700 mt-1">
                                    ✨ <strong>Streaming completo activado</strong>: Las canciones sonarán de principio a fin en el Modo Cabina a través de Spotify Web SDK.
                                </p>
                            @else
                                <p class="text-sm font-bold text-gray-800 mt-0.5">
                                    Sin cuenta de Spotify vinculada.
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Conecta tu cuenta para habilitar la reproducción completa de cualquier canción del catálogo de Spotify.
                                </p>
                            @endif
                        </div>

                        <!-- BOTONES DE CONEXIÓN OAUTH -->
                        <div class="flex items-center gap-2 self-start sm:self-auto">
                            @if(!empty($spotifyUser['connected']))
                                <button 
                                    type="button" 
                                    wire:click="disconnectSpotify" 
                                    wire:confirm="¿Seguro que deseas desconectar tu cuenta de Spotify?"
                                    class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs rounded-xl transition cursor-pointer"
                                >
                                    Desconectar Cuenta
                                </button>
                            @else
                                <a href="{{ route('spotify.connect') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs rounded-xl shadow-md shadow-emerald-600/30 transition transform hover:-translate-y-0.5">
                                    <span>🟢</span> Conectar Cuenta de Spotify
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- PASOS PARA CONFIGURAR LAS CLAVES DE SPOTIFY DEVELOPER -->
                <div class="p-4 bg-slate-900 text-slate-200 rounded-2xl border border-slate-800 space-y-3">
                    <h4 class="text-xs font-black uppercase text-emerald-400 tracking-wider flex items-center gap-2">
                        <span>⚙️</span> Configuración de Claves de Spotify Developer
                    </h4>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        Para habilitar la conexión, crea una App gratuita en <a href="https://developer.spotify.com/dashboard" target="_blank" class="text-cyan-400 underline font-bold">Spotify Developer Dashboard</a>:
                    </p>
                    <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 text-xs font-mono space-y-1">
                        <p class="text-slate-400">1. Nombre de la App: <strong class="text-white">Eventos Musicales Cabina</strong></p>
                        <p class="text-slate-400">2. Redirect URI: <strong class="text-emerald-400 select-all">{{ url('/spotify/callback') }}</strong></p>
                        <p class="text-slate-400">3. APIs a marcar: <strong class="text-white">Web API</strong> y <strong class="text-white">Web Playback SDK</strong></p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Spotify Client ID</label>
                        <input type="text" wire:model="spotify_client_id" placeholder="ej. 4c3b2a1..." class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 text-sm font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Spotify Client Secret</label>
                        <input type="password" wire:model="spotify_client_secret" placeholder="ej. 9z8y7x6..." class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 text-sm font-mono">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Comportamiento del Botón "Spotify" en Cabina</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-1">
                        <label class="flex items-start gap-3 p-3 rounded-xl border {{ $spotify_launch_mode === 'app' ? 'border-emerald-500 bg-emerald-50/40' : 'border-gray-200 bg-white' }} cursor-pointer">
                            <input type="radio" wire:model="spotify_launch_mode" value="app" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="text-xs font-bold text-gray-900 block">📲 Abrir en la App de Spotify (Recomendado para DJ)</span>
                                <span class="text-[11px] text-gray-500">Abre la aplicación nativa instalada en tu ordenador o tablet al instante sin pasar por el navegador.</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-3 rounded-xl border {{ $spotify_launch_mode === 'web' ? 'border-emerald-500 bg-emerald-50/40' : 'border-gray-200 bg-white' }} cursor-pointer">
                            <input type="radio" wire:model="spotify_launch_mode" value="web" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="text-xs font-bold text-gray-900 block">🌐 Abrir en Reproductor Web de Spotify</span>
                                <span class="text-[11px] text-gray-500">Abre una pestaña nueva en open.spotify.com.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- BOTÓN PROBAR SPOTIFY API -->
                <div class="flex items-center gap-3 pt-2">
                    <button type="button" wire:click="testSpotify" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition flex items-center gap-1.5 shadow-sm">
                        <span>⚡</span> Probar Conexión Client ID / Secret
                    </button>
                    @if($spotifyConnectionStatus)
                        <span class="text-xs font-semibold {{ $spotifyConnectionStatus['success'] ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $spotifyConnectionStatus['message'] }}
                        </span>
                    @endif
                </div>
            </div>

            <!-- SECCIÓN 2.5: INTEGRACIÓN TIDAL HI-FI & ENGINE DJ -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 space-y-5">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-cyan-100 text-cyan-800 flex items-center justify-center font-bold text-2xl shadow-sm">
                            🌊
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-gray-900">Tidal Hi-Fi &amp; Denon Engine DJ</h3>
                            <p class="text-xs text-gray-500">Enlaces directos a canciones en máxima calidad de audio (FLAC/Lossless) y compatibilidad nativa con cabinas y controladoras DJ.</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-cyan-50 text-cyan-800 border border-cyan-200 flex items-center gap-1.5">
                        ✓ Enlace Tidal Activo
                    </span>
                </div>

                <div class="bg-gradient-to-r from-slate-900 to-cyan-950 p-4 rounded-xl border border-cyan-900/50 text-white space-y-2">
                    <h4 class="text-sm font-bold text-cyan-300 flex items-center gap-2">
                        <span>🎛️</span> ¿Cómo usar tu suscripción de Tidal en tu equipo DJ?
                    </h4>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        1. <strong>En la App Web:</strong> Todas las peticiones de música, escaletas y búsquedas generan enlaces directos a <strong>Tidal</strong> para que puedas abrir o guardar cualquier canción en tu biblioteca con 1 clic.
                    </p>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        2. <strong>En tu Denon DJ / Engine DJ:</strong> En la pantalla táctil de tu controladora (Prime 4, SC Live, etc.), ve a <em>Source &gt; Tidal</em>, inicia sesión con tu cuenta de pago de Tidal vía Wi-Fi y podrás cargar instantáneamente cualquier canción solicitada por los invitados en calidad Lossless.
                    </p>
                </div>

                <!-- CLAVES API DE TIDAL DEVELOPER (OPCIONAL) -->
                <div class="bg-slate-900 text-white p-4 rounded-xl border border-slate-800 space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold text-cyan-400 flex items-center gap-2">
                            <span>🔑</span> Claves de API de Tidal Developer (Opcional)
                        </h4>
                        <a href="https://developer.tidal.com" target="_blank" class="text-[11px] text-cyan-400 hover:text-cyan-300 underline font-semibold">
                            developer.tidal.com ↗
                        </a>
                    </div>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        Si deseas conectar la API directa de Tidal para búsqueda de metadatos oficiales en la app, puedes crear una App gratuita en el Portal de Desarrolladores de Tidal e introducir tus claves:
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Tidal Client ID</label>
                            <input type="text" wire:model="tidal_client_id" placeholder="ej. tid_client_id_..." class="w-full bg-slate-950 border-slate-700 text-white rounded-lg shadow-sm focus:ring-cyan-500 focus:border-cyan-500 text-xs font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Tidal Client Secret</label>
                            <input type="password" wire:model="tidal_client_secret" placeholder="ej. tid_secret_..." class="w-full bg-slate-950 border-slate-700 text-white rounded-lg shadow-sm focus:ring-cyan-500 focus:border-cyan-500 text-xs font-mono">
                        </div>
                    </div>
                    <div class="flex items-center gap-3 pt-2">
                        <button type="button" wire:click="testTidal" class="px-4 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs transition flex items-center gap-1.5 shadow-sm">
                            <span>⚡</span> Probar Conexión Tidal API
                        </button>
                        @if($tidalConnectionStatus)
                            <span class="text-xs font-semibold {{ $tidalConnectionStatus['success'] ? 'text-emerald-400' : 'text-rose-400' }}">
                                {{ $tidalConnectionStatus['message'] }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 3: ALMACENAMIENTO EN LA NUBE (GOOGLE DRIVE & ONEDRIVE) -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 space-y-5">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-sky-100 text-sky-700 flex items-center justify-center font-bold text-2xl shadow-sm">
                            📁
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-gray-900">Almacenamiento en la Nube (Google Drive, OneDrive, Dropbox)</h3>
                            <p class="text-xs text-gray-500">Escaneo automático de carpetas de música para importar álbumes o canciones en bloque a la escaleta y reproducir en vivo.</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200 flex items-center gap-1.5">
                        Importación Automática Activa
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Guía Google Drive -->
                    <div class="p-4 bg-sky-50/60 rounded-2xl border border-sky-200 space-y-3">
                        <h4 class="text-xs font-black uppercase text-sky-900 tracking-wider flex items-center gap-1.5">
                            <span>Google Drive</span> &bull; Pasos para Escaneo Automático
                        </h4>
                        <ol class="text-xs text-sky-950 space-y-2 list-decimal list-inside leading-relaxed">
                            <li>Crea o sube tus archivos MP3 / WAV a una carpeta en <strong>Google Drive</strong>.</li>
                            <li>Haz clic derecho en la carpeta ➔ <strong>Compartir</strong> ➔ Cambiar acceso general a <strong>"Cualquier persona con el enlace puede ser lector"</strong>.</li>
                            <li>Copia el enlace de la carpeta y pégalo en el botón <strong>"📁 Importar Carpeta Nube"</strong> en la pestaña de música del evento.</li>
                        </ol>
                    </div>

                    <!-- Guía OneDrive & Dropbox -->
                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                        <h4 class="text-xs font-black uppercase text-slate-900 tracking-wider flex items-center gap-1.5">
                            <span>OneDrive & Dropbox</span> &bull; Enlaces de Streaming Directo
                        </h4>
                        <ol class="text-xs text-slate-700 space-y-2 list-decimal list-inside leading-relaxed">
                            <li>En <strong>OneDrive</strong> o <strong>Dropbox</strong>, comparte el archivo o carpeta de audio.</li>
                            <li>Asegúrate de que el enlace sea <strong>público para visualización</strong>.</li>
                            <li>El sistema convertirá automáticamente el enlace al formato de streaming continuo de alta velocidad compatible con el reproductor y modo cabina.</li>
                        </ol>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-2">
                    <!-- Campo Google Drive API Key -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-gray-800">
                            Google Drive API Key (Para escanear carpetas completas)
                        </label>
                        <input 
                            type="text" 
                            wire:model="google_drive_api_key" 
                            placeholder="AIzaSyB-..." 
                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-sky-500 focus:border-sky-500 text-sm font-mono"
                        >
                        <p class="text-[11px] text-gray-500 leading-normal">
                            Obtén tu clave en <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="text-sky-600 underline font-bold">Google Cloud Console</a> activando la <strong>Google Drive API</strong>.
                        </p>
                    </div>

                    <!-- Campo Carpeta Biblioteca General -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-gray-800">
                            Carpeta de Biblioteca General (Google Drive)
                        </label>
                        <input 
                            type="text" 
                            wire:model="google_drive_library_folder" 
                            placeholder="https://drive.google.com/drive/folders/1ABC... o ID de carpeta" 
                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-sky-500 focus:border-sky-500 text-sm font-mono"
                        >
                        <p class="text-[11px] text-gray-500 leading-normal">
                            Esta carpeta servirá como <strong>catálogo musical principal</strong>. Toda la música que esté aquí tendrá prioridad sobre Spotify / Apple Music en búsquedas y cabina.
                        </p>
                    </div>
                </div>

                <!-- Botón de Sincronización Manual de la Biblioteca General -->
                <div class="pt-3 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <button 
                                type="button" 
                                wire:click="syncDriveLibrary" 
                                wire:loading.attr="disabled"
                                class="px-4 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-black text-xs transition flex items-center gap-2 shadow-md shadow-sky-600/20 disabled:opacity-50 cursor-pointer"
                            >
                                <span wire:loading.remove wire:target="syncDriveLibrary">🔄 Sincronizar Biblioteca General con Google Drive</span>
                                <span wire:loading wire:target="syncDriveLibrary" class="inline-flex items-center gap-1.5">
                                    <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    Escaneando e indexando archivos...
                                </span>
                            </button>
                        </div>
                    </div>

                    @if(session()->has('drive_sync_success'))
                        <div class="p-3 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-xl text-xs font-bold flex items-center gap-2">
                            <span>✅</span> {{ session('drive_sync_success') }}
                        </div>
                    @endif

                    @if(session()->has('drive_sync_error'))
                        <div class="p-3 bg-rose-50 text-rose-800 border border-rose-200 rounded-xl text-xs font-bold flex items-center gap-2">
                            <span>⚠️</span> {{ session('drive_sync_error') }}
                        </div>
                    @endif
                </div>
            </div>

        </div>

        <!-- PESTAÑA: CORREO ELECTRÓNICO & SERVIDOR SMTP -->
        <div class="{{ $activeTab === 'email_smtp' ? 'block' : 'hidden' }} space-y-6">
            
            <!-- Diagnóstico de configuración actual -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 space-y-4">
                <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <span>✉️</span> Estado de Configuración del Servidor de Correo
                        </h3>
                        <p class="text-xs text-gray-500">Parámetros activos detectados en el entorno (<code>.env</code>) de este servidor.</p>
                    </div>
                    @php
                        $mailer = config('mail.default');
                        $isSmtp = $mailer === 'smtp';
                    @endphp
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ $isSmtp ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                        <span class="h-2 w-2 rounded-full {{ $isSmtp ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' }}"></span>
                        Driver: {{ strtoupper($mailer) }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <span class="text-[10px] uppercase font-bold text-slate-500 block mb-1">Servidor Host</span>
                        <span class="text-xs font-bold text-slate-800 font-mono">{{ config('mail.mailers.smtp.host') ?: 'No definido' }}</span>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <span class="text-[10px] uppercase font-bold text-slate-500 block mb-1">Puerto / Cifrado</span>
                        <span class="text-xs font-bold text-slate-800 font-mono">{{ config('mail.mailers.smtp.port') }} / {{ config('mail.mailers.smtp.encryption') ?: 'none' }}</span>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <span class="text-[10px] uppercase font-bold text-slate-500 block mb-1">Usuario / Remitente</span>
                        <span class="text-xs font-bold text-slate-800 font-mono truncate block" title="{{ config('mail.mailers.smtp.username') }}">{{ config('mail.mailers.smtp.username') ?: (config('mail.from.address') ?: 'Sin configurar') }}</span>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <span class="text-[10px] uppercase font-bold text-slate-500 block mb-1">Nombre Remitente</span>
                        <span class="text-xs font-bold text-slate-800 truncate block">{{ config('mail.from.name') ?: $company_name }}</span>
                    </div>
                </div>

                <!-- GUÍA PLESK -->
                <div class="p-4 rounded-xl bg-gradient-to-r from-indigo-50/70 to-purple-50/70 border border-indigo-100 text-xs text-indigo-950 space-y-2">
                    <h5 class="font-bold flex items-center gap-1.5 text-indigo-900">
                        <span>💡</span> ¿Cómo configurar el correo en tu servidor Plesk / Hosting?
                    </h5>
                    <p class="text-indigo-800/90 leading-relaxed">
                        En el archivo <code>.env</code> de la raíz del dominio o en las variables de entorno de Plesk, añade las credenciales de tu buzón:
                    </p>
                    <pre class="bg-slate-900 text-emerald-400 p-3 rounded-lg text-[11px] font-mono overflow-x-auto select-all">MAIL_MAILER=smtp
MAIL_HOST=localhost
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
MAIL_USERNAME=info@javnxdj.com
MAIL_PASSWORD=tu_contraseña_del_buzon
MAIL_FROM_ADDRESS="info@javnxdj.com"
MAIL_FROM_NAME="{{ $company_name }}"</pre>
                </div>
            </div>

            <!-- HERRAMIENTA DE TEST SMTP -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 space-y-4">
                <div class="border-b border-gray-100 pb-3">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <span>🧪</span> Probar Envío de Correo en Vivo
                    </h3>
                    <p class="text-xs text-gray-500">Envía un email de prueba inmediato para verificar si el servidor conecta y envía correctamente.</p>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3 max-w-xl">
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-gray-700 mb-1">Email de Destino para la Prueba</label>
                        <input type="email" wire:model="test_email_recipient" placeholder="tu-email@gmail.com" class="w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @error('test_email_recipient') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <button 
                        type="button" 
                        wire:click="testSmtpConnection" 
                        wire:loading.attr="disabled"
                        class="px-5 py-2.5 rounded-lg bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold text-xs shadow-md transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="testSmtpConnection">🚀 Enviar Email de Prueba</span>
                        <span wire:loading wire:target="testSmtpConnection" class="inline-flex items-center gap-1.5">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            Conectando con servidor...
                        </span>
                    </button>
                </div>

                @if($smtpTestStatus)
                    <div class="p-4 rounded-xl text-xs font-semibold {{ $smtpTestStatus['success'] ? 'bg-emerald-50 text-emerald-800 border border-emerald-300' : 'bg-rose-50 text-rose-800 border border-rose-300' }}">
                        <div class="flex items-start gap-2">
                            <span class="text-base">{{ $smtpTestStatus['success'] ? '✅' : '❌' }}</span>
                            <div class="flex-1">
                                <span class="block font-bold">{{ $smtpTestStatus['message'] }}</span>
                                @if(!$smtpTestStatus['success'])
                                    <p class="text-[11px] text-rose-700 mt-1">Revisa que <code>MAIL_HOST</code>, <code>MAIL_PORT</code>, <code>MAIL_USERNAME</code> y <code>MAIL_PASSWORD</code> en el <code>.env</code> coincidan exactamente con tu cuenta de correo en el VPS.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>

        </div>

        <!-- PESTAÑA: CORREOS ENTRANTES (IMAP LEADS) -->
        <div class="{{ $activeTab === 'email_imap' ? 'block' : 'hidden' }} space-y-6">
            
            <!-- Banner Explicativo & Sincronización Manual -->
            <div class="bg-gradient-to-r from-purple-900 to-indigo-900 text-white rounded-2xl p-6 shadow-md flex flex-col md:flex-row items-start md:items-center justify-between gap-6 border border-purple-800">
                <div class="space-y-2 max-w-2xl">
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black uppercase tracking-wider bg-purple-500/30 text-purple-200 border border-purple-400/30">
                            🤖 Automatización de Leads & Solicitudes
                        </span>
                    </div>
                    <h3 class="text-xl font-black">Captura Automática de Solicitudes de Información</h3>
                    <p class="text-xs text-purple-200 leading-relaxed">
                        Conecta la aplicación a los buzones de correo de tu VPS (Postfix/Dovecot/Roundcube). La app escanea los correos no leídos, extrae los datos del cliente (nombre, teléfono, fecha, mensaje, formato Bodas.net/formulario web) y <strong>crea automáticamente el evento en la app asignándole su marca correspondiente</strong> (Núñez & Son o JAVNX DJ).
                    </p>
                </div>
                <div class="shrink-0 flex flex-col items-end gap-2 w-full md:w-auto">
                    <button type="button" wire:click="fetchImapLeadsNow" wire:loading.attr="disabled" class="w-full md:w-auto bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-black px-5 py-3 rounded-xl shadow-lg transition cursor-pointer flex items-center justify-center gap-2 text-xs">
                        <span wire:loading.remove wire:target="fetchImapLeadsNow">📥 Sincronizar Correos Ahora</span>
                        <span wire:loading wire:target="fetchImapLeadsNow" class="flex items-center gap-1.5">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            Escaneando buzones...
                        </span>
                    </button>
                    <span class="text-[10px] text-purple-300 text-center md:text-right">El Cron del servidor también lo escanea cada 5 min.</span>
                </div>
            </div>

            <!-- Reporte del último escaneo si existe -->
            @if($imapFetchStatus)
                <div class="bg-white border border-purple-200 rounded-2xl p-5 shadow-sm space-y-3 animate-in fade-in duration-200">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <h4 class="font-extrabold text-sm text-gray-900 flex items-center gap-2">
                            <span>📊</span> Resultado del Último Escaneo IMAP
                        </h4>
                        <span class="text-xs text-gray-500 font-medium">Ejecutado hace un momento</span>
                    </div>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                        <div class="bg-purple-50 p-3 rounded-xl border border-purple-100">
                            <span class="text-2xl font-black text-purple-700 block">{{ $imapFetchStatus['total_scanned'] }}</span>
                            <span class="text-[11px] font-bold text-purple-900 uppercase">Correos Analizados</span>
                        </div>
                        <div class="bg-emerald-50 p-3 rounded-xl border border-emerald-100">
                            <span class="text-2xl font-black text-emerald-700 block">{{ $imapFetchStatus['total_leads_created'] }}</span>
                            <span class="text-[11px] font-bold text-emerald-900 uppercase">Nuevos Leads Creados</span>
                        </div>
                    </div>

                    @if(!empty($imapFetchStatus['details']))
                        <div class="space-y-2 pt-2">
                            @foreach($imapFetchStatus['details'] as $bKey => $detail)
                                <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-gray-900">{{ $bKey === 'javnx' ? '🎧 JAVNX DJ' : ($bKey === 'mago_leugim' ? '🎩 Mago Leugim' : '👑 Núñez & Son') }}</span>
                                        <span class="text-gray-500">({{ $detail['email'] }})</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-gray-600">Analizados: <strong>{{ $detail['scanned'] }}</strong></span>
                                        <span class="text-emerald-700 font-bold">Leads creados: <strong>{{ $detail['created'] }}</strong></span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            <!-- Configuración del Servidor IMAP Global -->
            <div class="bg-white shadow-sm rounded-2xl border border-gray-200 p-6 space-y-4">
                <h3 class="text-base font-extrabold text-gray-900 border-b border-gray-100 pb-3 flex items-center gap-2">
                    <span>⚙️</span> Parámetros del Servidor IMAP (Dovecot / Postfix en VPS)
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Servidor Host IMAP *</label>
                        <input type="text" wire:model="imap_host" placeholder="127.0.0.1 o localhost" class="w-full border-gray-300 rounded-xl shadow-xs text-xs font-mono">
                        <span class="text-[10px] text-gray-400 mt-1 block">En el mismo VPS suele ser <code>127.0.0.1</code></span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Puerto IMAP *</label>
                        <input type="number" wire:model="imap_port" placeholder="993" class="w-full border-gray-300 rounded-xl shadow-xs text-xs font-mono">
                        <span class="text-[10px] text-gray-400 mt-1 block">993 (SSL) o 143 (STARTTLS/TLS)</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Cifrado de Conexión *</label>
                        <select wire:model="imap_encryption" class="w-full border-gray-300 rounded-xl shadow-xs text-xs bg-white">
                            <option value="ssl">SSL (Recomendado - Puerto 993)</option>
                            <option value="tls">TLS / STARTTLS (Puerto 143)</option>
                            <option value="none">Sin cifrado (Local 127.0.0.1 Puerto 143)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-gray-100">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Estado Inicial del Evento Creado</label>
                        <select wire:model="imap_default_status" class="w-full border-gray-300 rounded-xl shadow-xs text-xs bg-white">
                            <option value="no_response">⏳ Sin Respuesta (Esperando contactar con cliente)</option>
                            <option value="draft">📝 Borrador / Pendiente de Presupuesto</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-3 pt-4">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="imap_mark_as_read" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                        </label>
                        <span class="text-xs font-bold text-gray-700">Marcar correos como leídos en el buzón tras procesarlos</span>
                    </div>
                </div>
            </div>

            <!-- BUZÓN 1: NÚÑEZ & SON -->
            <div class="bg-white shadow-sm rounded-2xl border-2 border-amber-200 p-6 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-amber-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <span class="text-2xl p-2 bg-amber-50 rounded-xl border border-amber-200">👑</span>
                        <div>
                            <h3 class="text-base font-black text-gray-900">Buzón 1: Núñez & Son</h3>
                            <p class="text-xs text-amber-700 font-semibold">Las solicitudes entrantes se crearán con la marca <strong>Núñez and Son</strong></p>
                        </div>
                    </div>
                    <label class="inline-flex items-center gap-2 cursor-pointer bg-amber-50 px-3 py-1.5 rounded-xl border border-amber-200">
                        <input type="checkbox" wire:model="imap_nunez_enabled" class="rounded text-amber-600 focus:ring-amber-500 h-4 w-4">
                        <span class="text-xs font-bold text-amber-900">Escanear este buzón</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Dirección de Email de Núñez & Son *</label>
                        <input type="email" wire:model="imap_nunez_email" placeholder="info@nunezadson.com" class="w-full border-gray-300 rounded-xl shadow-xs text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Contraseña del Buzón IMAP *</label>
                        <input type="password" wire:model="imap_nunez_password" placeholder="••••••••••••" class="w-full border-gray-300 rounded-xl shadow-xs text-xs">
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <button type="button" wire:click="testImapConnection('nunez_and_son')" wire:loading.attr="disabled" class="bg-amber-100 hover:bg-amber-200 text-amber-900 font-bold px-4 py-2 rounded-xl text-xs transition cursor-pointer inline-flex items-center gap-1.5 shadow-2xs">
                        <span wire:loading.remove wire:target="testImapConnection('nunez_and_son')">🔌 Probar Conexión Núñez & Son</span>
                        <span wire:loading wire:target="testImapConnection('nunez_and_son')">Verificando...</span>
                    </button>
                </div>

                @if(isset($imapTestStatus['nunez_and_son']))
                    <div class="p-3.5 rounded-xl text-xs font-semibold {{ $imapTestStatus['nunez_and_son']['success'] ? 'bg-emerald-50 text-emerald-800 border border-emerald-300' : 'bg-rose-50 text-rose-800 border border-rose-300' }}">
                        <span>{{ $imapTestStatus['nunez_and_son']['success'] ? '✅' : '❌' }}</span>
                        <span>{{ $imapTestStatus['nunez_and_son']['message'] }}</span>
                    </div>
                @endif
            </div>

            <!-- BUZÓN 2: JAVNX DJ -->
            <div class="bg-white shadow-sm rounded-2xl border-2 border-indigo-200 p-6 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-indigo-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <span class="text-2xl p-2 bg-indigo-50 rounded-xl border border-indigo-200">🎧</span>
                        <div>
                            <h3 class="text-base font-black text-gray-900">Buzón 2: JAVNX DJ</h3>
                            <p class="text-xs text-indigo-700 font-semibold">Las solicitudes entrantes se crearán con la marca <strong>JAVNX DJ</strong></p>
                        </div>
                    </div>
                    <label class="inline-flex items-center gap-2 cursor-pointer bg-indigo-50 px-3 py-1.5 rounded-xl border border-indigo-200">
                        <input type="checkbox" wire:model="imap_javnx_enabled" class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                        <span class="text-xs font-bold text-indigo-900">Escanear este buzón</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Dirección de Email de JAVNX DJ *</label>
                        <input type="email" wire:model="imap_javnx_email" placeholder="info@javnxdj.com" class="w-full border-gray-300 rounded-xl shadow-xs text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Contraseña del Buzón IMAP *</label>
                        <input type="password" wire:model="imap_javnx_password" placeholder="••••••••••••" class="w-full border-gray-300 rounded-xl shadow-xs text-xs">
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <button type="button" wire:click="testImapConnection('javnx')" wire:loading.attr="disabled" class="bg-indigo-100 hover:bg-indigo-200 text-indigo-900 font-bold px-4 py-2 rounded-xl text-xs transition cursor-pointer inline-flex items-center gap-1.5 shadow-2xs">
                        <span wire:loading.remove wire:target="testImapConnection('javnx')">🔌 Probar Conexión JAVNX DJ</span>
                        <span wire:loading wire:target="testImapConnection('javnx')">Verificando...</span>
                    </button>
                </div>

                @if(isset($imapTestStatus['javnx']))
                    <div class="p-3.5 rounded-xl text-xs font-semibold {{ $imapTestStatus['javnx']['success'] ? 'bg-emerald-50 text-emerald-800 border border-emerald-300' : 'bg-rose-50 text-rose-800 border border-rose-300' }}">
                        <span>{{ $imapTestStatus['javnx']['success'] ? '✅' : '❌' }}</span>
                        <span>{{ $imapTestStatus['javnx']['message'] }}</span>
                    </div>
                @endif
            </div>

            <!-- BUZÓN 3: MAGO LEUGIM (OPCIONAL) -->
            <div class="bg-white shadow-sm rounded-2xl border border-gray-200 p-6 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <span class="text-2xl p-2 bg-slate-50 rounded-xl border border-gray-200">🎩</span>
                        <div>
                            <h3 class="text-base font-black text-gray-900">Buzón 3: Mago Leugim (Opcional)</h3>
                            <p class="text-xs text-gray-500 font-semibold">Las solicitudes entrantes se crearán con la marca <strong>Mago Leugim</strong></p>
                        </div>
                    </div>
                    <label class="inline-flex items-center gap-2 cursor-pointer bg-gray-50 px-3 py-1.5 rounded-xl border border-gray-200">
                        <input type="checkbox" wire:model="imap_leugim_enabled" class="rounded text-purple-600 focus:ring-purple-500 h-4 w-4">
                        <span class="text-xs font-bold text-gray-700">Escanear este buzón</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Dirección de Email de Mago Leugim</label>
                        <input type="email" wire:model="imap_leugim_email" placeholder="info@magoleugim.com" class="w-full border-gray-300 rounded-xl shadow-xs text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Contraseña del Buzón IMAP</label>
                        <input type="password" wire:model="imap_leugim_password" placeholder="••••••••••••" class="w-full border-gray-300 rounded-xl shadow-xs text-xs">
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <button type="button" wire:click="testImapConnection('mago_leugim')" wire:loading.attr="disabled" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold px-4 py-2 rounded-xl text-xs transition cursor-pointer inline-flex items-center gap-1.5 shadow-2xs">
                        <span wire:loading.remove wire:target="testImapConnection('mago_leugim')">🔌 Probar Conexión Mago Leugim</span>
                        <span wire:loading wire:target="testImapConnection('mago_leugim')">Verificando...</span>
                    </button>
                </div>

                @if(isset($imapTestStatus['mago_leugim']))
                    <div class="p-3.5 rounded-xl text-xs font-semibold {{ $imapTestStatus['mago_leugim']['success'] ? 'bg-emerald-50 text-emerald-800 border border-emerald-300' : 'bg-rose-50 text-rose-800 border border-rose-300' }}">
                        <span>{{ $imapTestStatus['mago_leugim']['success'] ? '✅' : '❌' }}</span>
                        <span>{{ $imapTestStatus['mago_leugim']['message'] }}</span>
                    </div>
                @endif
            </div>

            <!-- INSTRUCCIONES VPS CRON -->
            <div class="bg-slate-900 text-white rounded-2xl p-6 space-y-3 shadow-md border border-slate-800 text-xs">
                <div class="flex items-center gap-2">
                    <span class="text-xl">⏱️</span>
                    <h4 class="text-sm font-bold text-amber-400">Automatización Periódica en tu VPS (Cron Job)</h4>
                </div>
                <p class="text-slate-300">
                    Para que la aplicación revise los correos de forma 100% automática cada 5 minutos sin que tengas que pulsar el botón manual, añade esta línea en el <code>crontab -e</code> de tu servidor:
                </p>
                <div class="bg-slate-950 p-3.5 rounded-xl border border-slate-800 font-mono text-[11px] text-emerald-400 select-all overflow-x-auto">
                    * * * * * cd /ruta/de/tu/app && php artisan schedule:run >> /dev/null 2>&1
                </div>
                <p class="text-slate-400 text-[11px]">
                    O si prefieres ejecutar directamente el comando del escáner de correos cada 5 minutos:
                </p>
                <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 font-mono text-[11px] text-amber-300 select-all overflow-x-auto">
                    */5 * * * * cd /ruta/de/tu/app && php artisan emails:fetch-leads >> /dev/null 2>&1
                </div>
            </div>

        </div>

        <!-- PESTAÑA: WHATSAPP GATEWAY (EVOLUTION API) -->
        <div class="{{ $activeTab === 'whatsapp_gateway' ? 'block' : 'hidden' }} space-y-6">
            
            <!-- Banner Explicativo -->
            <div class="bg-gradient-to-r from-emerald-950 via-teal-900 to-slate-900 text-white rounded-2xl p-6 shadow-md flex flex-col md:flex-row items-start md:items-center justify-between gap-6 border border-emerald-800">
                <div class="space-y-2 max-w-2xl">
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black uppercase tracking-wider bg-emerald-500/30 text-emerald-200 border border-emerald-400/30">
                            💬 Chat Bidireccional & Automatización
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/10 text-white border border-white/20">
                            Evolution API / Baileys
                        </span>
                    </div>
                    <h3 class="text-xl font-black">Integración Directa con WhatsApp</h3>
                    <p class="text-xs text-emerald-100/90 leading-relaxed">
                        Conecta un microservicio de <strong>Evolution API</strong> (Docker en tu VPS o servidor) para enviar mensajes de WhatsApp con un solo clic directamente desde la app, ver el historial de chat con el cliente y recibir sus respuestas en tiempo real mediante un webhook.
                    </p>
                    <p class="text-[11px] text-emerald-300 font-semibold">
                        💡 <strong>Modo Híbrido:</strong> Si no tienes el gateway activo o el servidor está apagado, la aplicación continuará funcionando exactamente igual mediante los enlaces <code>wa.me</code> abriendo WhatsApp Web o la app oficial de WhatsApp en tu teléfono u ordenador.
                    </p>
                </div>
                
                <div class="shrink-0 flex flex-col items-end gap-2 w-full md:w-auto">
                    <label class="relative inline-flex items-center cursor-pointer bg-emerald-900/60 p-3 rounded-2xl border border-emerald-700/50">
                        <input type="checkbox" wire:model.live="whatsapp_gateway_enabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[14px] after:left-[14px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                        <span class="ml-3 text-xs font-black text-white">Activar Gateway API</span>
                    </label>
                </div>
            </div>

            <!-- Estado de Conexión / Mensajes de Test -->
            @if($whatsappStatus)
                <div class="p-4 rounded-2xl border {{ ($whatsappStatus['state'] ?? '') === 'open' ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-amber-50 border-amber-200 text-amber-900' }} shadow-xs flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">{{ ($whatsappStatus['state'] ?? '') === 'open' ? '✅' : 'ℹ️' }}</span>
                        <div>
                            <div class="text-xs font-black uppercase tracking-wider">
                                Instancia: <span class="font-mono">{{ $whatsappStatus['instance'] ?? 'Principal' }}</span>
                            </div>
                            <div class="text-sm font-bold">{{ $whatsappStatus['message'] ?? 'Estado consultado' }}</div>
                        </div>
                    </div>
                    <button type="button" wire:click="$set('whatsappStatus', null)" class="text-gray-400 hover:text-gray-600 font-black text-lg">&times;</button>
                </div>
            @endif

            @if(session()->has('whatsapp_success'))
                <div class="p-4 rounded-2xl bg-emerald-50 border-2 border-emerald-300 text-emerald-950 shadow-xs flex items-center justify-between text-xs font-bold">
                    <div class="flex items-center gap-2">
                        <span class="text-base">✅</span>
                        <span>{{ session('whatsapp_success') }}</span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 font-black text-lg cursor-pointer">&times;</button>
                </div>
            @endif

            @if(session()->has('whatsapp_error'))
                <div class="p-4 rounded-2xl bg-rose-50 border-2 border-rose-300 text-rose-950 shadow-xs flex items-center justify-between text-xs font-bold">
                    <div class="flex items-center gap-2">
                        <span class="text-base">⚠️</span>
                        <span>{{ session('whatsapp_error') }}</span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-rose-400 hover:text-rose-600 font-black text-lg cursor-pointer">&times;</button>
                </div>
            @endif

            <!-- Modal / Visualizador de Código QR -->
            @if($whatsappQrBase64)
                <div class="bg-white border-2 border-emerald-500 rounded-3xl p-6 shadow-2xl space-y-5 animate-in fade-in zoom-in-95 duration-200">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <div class="flex items-center gap-2.5">
                            <span class="text-2xl p-2 bg-emerald-50 rounded-xl border border-emerald-200">📷</span>
                            <div>
                                <h4 class="font-black text-base text-gray-900">
                                    Vincular WhatsApp: <span class="text-emerald-700">{{ $whatsappQrPerson ?? 'Nuevo Dispositivo' }}</span>
                                </h4>
                                <p class="text-xs text-gray-500 font-mono">Instancia: {{ $whatsappQrInstance }}</p>
                            </div>
                        </div>
                        <button type="button" wire:click="closeWhatsAppQrModal" class="text-gray-400 hover:text-gray-700 font-black text-2xl p-1 leading-none cursor-pointer">&times;</button>
                    </div>

                    <div class="flex flex-col md:flex-row items-center gap-6">
                        <div class="bg-gray-50 p-4 rounded-2xl border-2 border-emerald-200 shadow-inner shrink-0">
                            <img src="{{ $whatsappQrBase64 }}" alt="QR WhatsApp" class="w-60 h-60 object-contain rounded-xl bg-white p-2">
                        </div>
                        <div class="space-y-3.5 text-xs text-gray-700">
                            <div class="bg-emerald-50/80 border border-emerald-200 text-emerald-950 p-3 rounded-xl font-bold">
                                📱 Escanea este código únicamente desde el móvil de: <u>{{ $whatsappQrPerson ?? 'la persona correspondiente' }}</u>.
                            </div>
                            <ol class="list-decimal list-inside space-y-2 font-medium">
                                <li>Abre <strong>WhatsApp</strong> en el teléfono móvil de <strong>{{ $whatsappQrPerson ?? 'la persona indicada' }}</strong>.</li>
                                <li>Ve a <strong>Ajustes</strong> (o toca los 3 puntos arriba a la derecha) &gt; <strong>Dispositivos vinculados</strong>.</li>
                                <li>Pulsa sobre <strong>Vincular un dispositivo</strong>.</li>
                                <li>Apunta la cámara del móvil a este código QR en la pantalla.</li>
                            </ol>
                            <p class="text-[11px] text-gray-500 pt-1">
                                ⏳ El código QR se actualiza cada pocos segundos. Una vez leído por el móvil, la sesión quedará guardada en el servidor y podrás cerrar este recuadro.
                            </p>
                            <div class="pt-2">
                                <button type="button" wire:click="closeWhatsAppQrModal" class="bg-slate-800 hover:bg-slate-900 text-white font-bold px-4 py-2 rounded-xl text-xs transition cursor-pointer shadow-xs">
                                    Cerrar Ventana del QR
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Parámetros del Servidor Evolution API -->
            <div class="bg-white shadow-sm rounded-2xl border border-gray-200 p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-base font-extrabold text-gray-900 flex items-center gap-2">
                        <span>⚙️</span> Parámetros del Servidor Evolution API
                    </h3>
                    <button type="button" wire:click="testWhatsAppGateway(null)" wire:loading.attr="disabled" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold px-3 py-1.5 rounded-xl text-xs transition cursor-pointer inline-flex items-center gap-1.5 border border-indigo-200">
                        <span wire:loading.remove wire:target="testWhatsAppGateway(null)">🔍 Comprobar Todos los Teléfonos</span>
                        <span wire:loading wire:target="testWhatsAppGateway(null)">Comprobando...</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">URL Base del Servidor (Gateway Evolution API) *</label>
                        <input type="url" wire:model="whatsapp_gateway_url" placeholder="http://217.160.67.246:8080" class="w-full border-gray-300 rounded-xl shadow-xs text-xs font-mono">
                        <span class="text-[10px] text-gray-400 mt-1 block">Dirección donde corre el servidor de Evolution API (ej: http://217.160.67.246:8080).</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">API Key Global (AUTHENTICATION_API_KEY) *</label>
                        <input type="password" wire:model="whatsapp_gateway_api_key" placeholder="••••••••••••••••••••" class="w-full border-gray-300 rounded-xl shadow-xs text-xs font-mono">
                        <span class="text-[10px] text-gray-400 mt-1 block">Clave de autenticación definida en el archivo .env de Evolution API.</span>
                    </div>
                </div>

                <div class="pt-2 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-indigo-900 mb-1">🎧 Avisos de JAVNX DJ (javnxdj.com) *</label>
                        <input type="text" wire:model="admin_notification_phones_javnx" placeholder="+34 622 63 47 90" class="w-full border-indigo-300 rounded-xl shadow-xs text-xs font-mono">
                        <span class="text-[10px] text-gray-500 mt-1 block">Solo estos números reciben avisos de leads, presupuestos y respuestas de <strong>javnxdj.com</strong> (solo Fran).</span>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-amber-900 mb-1">👑 Avisos de Núñez &amp; Son (nunezandson.com) *</label>
                        <input type="text" wire:model="admin_notification_phones_nunez" placeholder="+34 622 63 47 90, +34 674 37 89 93" class="w-full border-amber-300 rounded-xl shadow-xs text-xs font-mono">
                        <span class="text-[10px] text-gray-500 mt-1 block">Números separados por coma que reciben avisos de <strong>nunezandson.com</strong> (Fran y Miguel).</span>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Token Secreto de Webhook (Opcional)</label>
                        <input type="text" wire:model="whatsapp_webhook_token" placeholder="token_secreto_para_validar_peticiones" class="w-full border-gray-300 rounded-xl shadow-xs text-xs font-mono">
                        <span class="text-[10px] text-gray-400 mt-1 block">Si lo configuras, se verificará en la cabecera <code>x-webhook-token</code> al recibir mensajes.</span>
                    </div>
                </div>

                <!-- Botones de Prueba de Avisos a Administradores (Javnx y Núñez & Son) -->
                <div class="pt-3 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <span class="text-xs font-extrabold text-gray-800 block">🧪 Probar Envío de Avisos por WhatsApp (Fran y Miguel)</span>
                        <span class="text-[11px] text-gray-500">Envía un aviso de prueba real y muestra por qué instancia ha salido cada mensaje.</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" wire:click="testAdminWhatsAppNotification('javnx')" wire:loading.attr="disabled" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-3.5 py-2 rounded-xl text-xs transition cursor-pointer inline-flex items-center gap-1.5 shadow-xs">
                            <span wire:loading.remove wire:target="testAdminWhatsAppNotification('javnx')">🎧 Probar Aviso JAVNX DJ</span>
                            <span wire:loading wire:target="testAdminWhatsAppNotification('javnx')">Enviando...</span>
                        </button>
                        <button type="button" wire:click="testAdminWhatsAppNotification('nunez_and_son')" wire:loading.attr="disabled" class="bg-amber-600 hover:bg-amber-700 text-white font-bold px-3.5 py-2 rounded-xl text-xs transition cursor-pointer inline-flex items-center gap-1.5 shadow-xs">
                            <span wire:loading.remove wire:target="testAdminWhatsAppNotification('nunez_and_son')">👑 Probar Aviso Núñez &amp; Son</span>
                            <span wire:loading wire:target="testAdminWhatsAppNotification('nunez_and_son')">Enviando...</span>
                        </button>
                    </div>
                </div>

                @if($whatsappAdminTestReport)
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2 text-xs">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                            <span class="font-black text-slate-900">📋 Diagnóstico de Envío ({{ strtoupper($whatsappAdminTestReport['brand'] ?? '') }})</span>
                            <span class="text-[11px] font-mono text-slate-600">Instancias activas ('open'): {{ !empty($whatsappAdminTestReport['open_instances']) ? implode(', ', array_keys($whatsappAdminTestReport['open_instances'])) : 'Ninguna detectada' }}</span>
                        </div>
                        <div class="space-y-1.5">
                            @foreach(($whatsappAdminTestReport['recipients'] ?? []) as $phoneKey => $rec)
                                <div class="p-2.5 rounded-lg border flex flex-col sm:flex-row sm:items-center justify-between gap-2 {{ !empty($rec['success']) ? 'bg-emerald-50 border-emerald-200 text-emerald-950' : 'bg-rose-50 border-rose-200 text-rose-950' }}">
                                    <div class="flex items-center gap-2">
                                        <span>{{ !empty($rec['success']) ? '✅' : '❌' }}</span>
                                        <span class="font-mono font-bold">+{{ $rec['phone'] }} {{ str_ends_with($rec['phone'], '622634790') ? '(Fran)' : (str_ends_with($rec['phone'], '674378993') ? '(Miguel)' : '') }}</span>
                                    </div>
                                    <div class="text-[11px]">
                                        @if(!empty($rec['success']))
                                            Enviado desde: <code class="font-bold">{{ $rec['instance_used'] }}</code>
                                            @if(!empty($rec['self_message']))
                                                <span class="ml-1 px-2 py-0.5 rounded bg-amber-100 text-amber-900 font-bold" title="Cuando una instancia envía a su propio número, entra en 'Mensajes contigo mismo (Tú)' sin sonido push. Si vinculas también el QR de Miguel, a Fran le entrará desde el número de Miguel con notificación sonora.">⚠️ Auto-mensaje (chat 'Tú')</span>
                                            @endif
                                        @else
                                            <span class="text-rose-700 font-semibold">{{ $rec['error'] ?? 'Fallo de envío' }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Explicación de Conexión de 3 Teléfonos Simultáneos -->
            <div class="bg-gradient-to-r from-blue-50 via-indigo-50 to-emerald-50 border border-blue-200/80 rounded-2xl p-4.5 shadow-2xs text-xs text-blue-900 flex items-start gap-3">
                <span class="text-2xl p-2 bg-blue-100/70 rounded-xl">👥</span>
                <div class="space-y-1">
                    <strong class="font-black text-blue-950 text-sm">Organización de Teléfonos de WhatsApp (Miguel, Fran y Ezequiel)</strong>
                    <p class="text-blue-800 leading-relaxed text-xs">
                        Evolution API mantiene <strong>cada sesión abierta independientemente</strong> para que los tres podáis enviar y recibir mensajes de clientes sin interferir entre vosotros:
                    </p>
                    <ul class="list-disc list-inside space-y-1 text-blue-900/90 font-medium pt-1">
                        <li><strong>Miguel (Núñez & Son):</strong> Pulsa <em>«📱 Vincular / Ver QR»</em> en la tarjeta de Miguel y escanéalo con su móvil.</li>
                        <li><strong>Fran (JAVNX DJ & Núñez & Son):</strong> Instancia principal activa. Permite enviar desde el móvil de Fran tanto para JAVNX DJ como para Núñez & Son.</li>
                        <li><strong>Ezequiel (Núñez & Son):</strong> Pulsa <em>«📱 Vincular / Ver QR»</em> en la tarjeta de Ezequiel y escanéalo con su móvil.</li>
                        <li>En la ficha de cualquier evento, podréis <strong>seleccionar con 1 clic desde qué móvil enviar el WhatsApp</strong> al cliente o novios.</li>
                    </ul>
                </div>
            </div>

            <!-- Nombres de Instancia por Persona y Marca (3 Columnas) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- 1. INSTANCIA MIGUEL · NÚÑEZ & SON -->
                <div class="bg-white shadow-sm rounded-2xl border-2 border-amber-300 p-5 space-y-4 flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between border-b border-amber-100 pb-3">
                            <div class="flex items-center gap-2.5">
                                <span class="text-2xl p-2 bg-amber-50 rounded-xl border border-amber-200">👑</span>
                                <div>
                                    <h3 class="text-base font-black text-gray-900">Miguel</h3>
                                    <p class="text-xs text-amber-700 font-bold">Núñez & Son</p>
                                </div>
                            </div>
                            <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-200">
                                Responsable
                            </span>
                        </div>

                        <!-- Estado en vivo -->
                        @php
                            $miguelStatus = $whatsappStatuses['miguel'] ?? null;
                        @endphp
                        @if($miguelStatus)
                            <div class="p-2.5 rounded-xl border text-xs font-bold flex items-center justify-between {{ ($miguelStatus['state'] ?? '') === 'open' ? 'bg-emerald-50 border-emerald-300 text-emerald-900' : 'bg-amber-50 border-amber-300 text-amber-900' }}">
                                <span>{{ $miguelStatus['message'] ?? 'Estado verificado' }}</span>
                                @if(!empty($miguelStatus['owner']))
                                    <span class="font-mono text-[10px] bg-white/80 px-1.5 py-0.5 rounded">{{ $miguelStatus['owner'] }}</span>
                                @endif
                            </div>
                        @else
                            <div class="p-2.5 rounded-xl border border-gray-200 bg-gray-50 text-gray-600 text-xs font-medium">
                                ℹ️ Pulsa «Probar» para verificar si está conectado.
                            </div>
                        @endif

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Nombre de Instancia en Servidor</label>
                            <input type="text" wire:model="whatsapp_instance_miguel" placeholder="whatsapp_miguel" class="w-full border-gray-300 rounded-xl shadow-xs text-xs font-mono">
                        </div>
                    </div>

                    <div class="space-y-2 pt-3 border-t border-gray-100">
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="testWhatsAppGateway('miguel')" wire:loading.attr="disabled" class="flex-1 bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 font-bold py-2 rounded-xl text-xs transition cursor-pointer text-center shadow-2xs">
                                <span wire:loading.remove wire:target="testWhatsAppGateway('miguel')">🔌 Probar</span>
                                <span wire:loading wire:target="testWhatsAppGateway('miguel')">...</span>
                            </button>
                            <button type="button" wire:click="fetchWhatsAppQrCode('miguel')" wire:loading.attr="disabled" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 rounded-xl text-xs transition cursor-pointer text-center shadow-2xs">
                                <span wire:loading.remove wire:target="fetchWhatsAppQrCode('miguel')">📱 Vincular / QR</span>
                                <span wire:loading wire:target="fetchWhatsAppQrCode('miguel')">Generando...</span>
                            </button>
                        </div>
                        <button type="button" wire:click="disconnectWhatsAppInstance('miguel')" wire:confirm="¿Seguro que deseas desconectar o reiniciar la sesión de WhatsApp de Miguel?" class="w-full text-[11px] text-gray-400 hover:text-rose-600 font-semibold py-1 transition cursor-pointer text-center">
                            Desconectar / Reiniciar sesión
                        </button>
                    </div>
                </div>

                <!-- 2. INSTANCIA FRAN · JAVNX DJ (Y NÚÑEZ & SON) -->
                <div class="bg-white shadow-sm rounded-2xl border-2 border-indigo-300 p-5 space-y-4 flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between border-b border-indigo-100 pb-3">
                            <div class="flex items-center gap-2.5">
                                <span class="text-2xl p-2 bg-indigo-50 rounded-xl border border-indigo-200">🎧</span>
                                <div>
                                    <h3 class="text-base font-black text-gray-900">Fran</h3>
                                    <p class="text-xs text-indigo-700 font-bold">JAVNX DJ (y Núñez & Son)</p>
                                </div>
                            </div>
                            <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800 border border-indigo-200">
                                DJ & Gestión
                            </span>
                        </div>

                        <!-- Estado en vivo -->
                        @php
                            $franStatus = $whatsappStatuses['fran'] ?? null;
                        @endphp
                        @if($franStatus)
                            <div class="p-2.5 rounded-xl border text-xs font-bold flex items-center justify-between {{ ($franStatus['state'] ?? '') === 'open' ? 'bg-emerald-50 border-emerald-300 text-emerald-900' : 'bg-amber-50 border-amber-300 text-amber-900' }}">
                                <span>{{ $franStatus['message'] ?? 'Estado verificado' }}</span>
                                @if(!empty($franStatus['owner']))
                                    <span class="font-mono text-[10px] bg-white/80 px-1.5 py-0.5 rounded">{{ $franStatus['owner'] }}</span>
                                @endif
                            </div>
                        @else
                            <div class="p-2.5 rounded-xl border border-emerald-200 bg-emerald-50/60 text-emerald-900 text-xs font-bold flex items-center justify-between">
                                <span>🟢 Sesión Activa</span>
                                <span class="font-mono text-[10px] bg-white px-1.5 py-0.5 rounded border border-emerald-200">+34 622 63 47 90</span>
                            </div>
                        @endif

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Nombre de Instancia en Servidor</label>
                            <input type="text" wire:model="whatsapp_instance_fran" placeholder="whatsapp_principal" class="w-full border-gray-300 rounded-xl shadow-xs text-xs font-mono">
                        </div>
                    </div>

                    <div class="space-y-2 pt-3 border-t border-gray-100">
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="testWhatsAppGateway('fran')" wire:loading.attr="disabled" class="flex-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-900 border border-indigo-300 font-bold py-2 rounded-xl text-xs transition cursor-pointer text-center shadow-2xs">
                                <span wire:loading.remove wire:target="testWhatsAppGateway('fran')">🔌 Probar</span>
                                <span wire:loading wire:target="testWhatsAppGateway('fran')">...</span>
                            </button>
                            <button type="button" wire:click="fetchWhatsAppQrCode('fran')" wire:loading.attr="disabled" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 rounded-xl text-xs transition cursor-pointer text-center shadow-2xs">
                                <span wire:loading.remove wire:target="fetchWhatsAppQrCode('fran')">📱 Vincular / QR</span>
                                <span wire:loading wire:target="fetchWhatsAppQrCode('fran')">Generando...</span>
                            </button>
                        </div>
                        <button type="button" wire:click="disconnectWhatsAppInstance('fran')" wire:confirm="¿Seguro que deseas desconectar o reiniciar la sesión de WhatsApp de Fran (+34 622 63 47 90)?" class="w-full text-[11px] text-gray-400 hover:text-rose-600 font-semibold py-1 transition cursor-pointer text-center">
                            Desconectar / Reiniciar sesión
                        </button>
                    </div>
                </div>

                <!-- 3. INSTANCIA EZEQUIEL · NÚÑEZ & SON -->
                <div class="bg-white shadow-sm rounded-2xl border-2 border-sky-300 p-5 space-y-4 flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between border-b border-sky-100 pb-3">
                            <div class="flex items-center gap-2.5">
                                <span class="text-2xl p-2 bg-sky-50 rounded-xl border border-sky-200">👥</span>
                                <div>
                                    <h3 class="text-base font-black text-gray-900">Ezequiel</h3>
                                    <p class="text-xs text-sky-700 font-bold">Núñez & Son</p>
                                </div>
                            </div>
                            <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-sky-100 text-sky-800 border border-sky-200">
                                Miembro
                            </span>
                        </div>

                        <!-- Estado en vivo -->
                        @php
                            $ezequielStatus = $whatsappStatuses['ezequiel'] ?? null;
                        @endphp
                        @if($ezequielStatus)
                            <div class="p-2.5 rounded-xl border text-xs font-bold flex items-center justify-between {{ ($ezequielStatus['state'] ?? '') === 'open' ? 'bg-emerald-50 border-emerald-300 text-emerald-900' : 'bg-amber-50 border-amber-300 text-amber-900' }}">
                                <span>{{ $ezequielStatus['message'] ?? 'Estado verificado' }}</span>
                                @if(!empty($ezequielStatus['owner']))
                                    <span class="font-mono text-[10px] bg-white/80 px-1.5 py-0.5 rounded">{{ $ezequielStatus['owner'] }}</span>
                                @endif
                            </div>
                        @else
                            <div class="p-2.5 rounded-xl border border-gray-200 bg-gray-50 text-gray-600 text-xs font-medium">
                                ℹ️ Pulsa «Vincular / QR» para vincular el móvil de Ezequiel.
                            </div>
                        @endif

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Nombre de Instancia en Servidor</label>
                            <input type="text" wire:model="whatsapp_instance_ezequiel" placeholder="whatsapp_ezequiel" class="w-full border-gray-300 rounded-xl shadow-xs text-xs font-mono">
                        </div>
                    </div>

                    <div class="space-y-2 pt-3 border-t border-gray-100">
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="testWhatsAppGateway('ezequiel')" wire:loading.attr="disabled" class="flex-1 bg-sky-50 hover:bg-sky-100 text-sky-900 border border-sky-300 font-bold py-2 rounded-xl text-xs transition cursor-pointer text-center shadow-2xs">
                                <span wire:loading.remove wire:target="testWhatsAppGateway('ezequiel')">🔌 Probar</span>
                                <span wire:loading wire:target="testWhatsAppGateway('ezequiel')">...</span>
                            </button>
                            <button type="button" wire:click="fetchWhatsAppQrCode('ezequiel')" wire:loading.attr="disabled" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 rounded-xl text-xs transition cursor-pointer text-center shadow-2xs">
                                <span wire:loading.remove wire:target="fetchWhatsAppQrCode('ezequiel')">📱 Vincular / QR</span>
                                <span wire:loading wire:target="fetchWhatsAppQrCode('ezequiel')">Generando...</span>
                            </button>
                        </div>
                        <button type="button" wire:click="disconnectWhatsAppInstance('ezequiel')" wire:confirm="¿Seguro que deseas desconectar o reiniciar la sesión de WhatsApp de Ezequiel?" class="w-full text-[11px] text-gray-400 hover:text-rose-600 font-semibold py-1 transition cursor-pointer text-center">
                            Desconectar / Reiniciar sesión
                        </button>
                    </div>
                </div>
            </div>

            <!-- Configuración del Webhook para Respuestas Automáticas -->
            <div class="bg-slate-900 text-white rounded-2xl p-6 shadow-md border border-slate-800 space-y-4">
                <div class="flex items-center gap-2.5">
                    <span class="text-xl">🪝</span>
                    <div>
                        <h4 class="font-extrabold text-sm text-white">URL de Webhook para Respuestas Entrantes</h4>
                        <p class="text-xs text-slate-400">Configura esta URL en el panel de Evolution API o en la llamada <code>/webhook/set/{instance}</code></p>
                    </div>
                </div>

                <div class="bg-slate-950 p-3.5 rounded-xl border border-slate-800 font-mono text-xs text-emerald-400 select-all overflow-x-auto flex items-center justify-between">
                    <span>{{ url('/api/whatsapp/webhook') }}</span>
                    <span class="text-[10px] text-slate-500 uppercase font-sans font-bold">POST</span>
                </div>

                <div class="text-[11px] text-slate-400 space-y-1">
                    <p>✨ <strong>¿Qué hace el webhook automáticamente?</strong></p>
                    <ul class="list-disc list-inside space-y-0.5 text-slate-300">
                        <li>Cuando un cliente responde al WhatsApp de seguimiento, el mensaje se guarda en la ficha del evento.</li>
                        <li>Si el evento estaba en estado <strong>"Sin Respuesta" (no_response)</strong>, se reactiva automáticamente a <strong>"Borrador" (draft)</strong> y se reinicia el contador de días para recordatorios.</li>
                    </ul>
                </div>
            </div>

        </div>

        <!-- ERRORES DE VALIDACIÓN SI EXISTEN -->
        @if ($errors->any())
            <div class="mt-6 p-4 rounded-xl bg-rose-50 border-2 border-rose-300 text-rose-900 text-xs font-bold space-y-1.5 shadow-sm">
                <div class="flex items-center gap-2 text-sm text-rose-800 font-black">
                    <span>⚠️</span> <span>Hay datos pendientes de corregir para poder guardar:</span>
                </div>
                <ul class="list-disc list-inside text-[11px] text-rose-700 font-medium pl-2 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- BARRA FLOTANTE DE GUARDAR GLOBAL -->
        <div class="mt-8 sticky bottom-4 z-40 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md p-4 sm:p-5 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl flex flex-col sm:flex-row items-center justify-between gap-4 transition">
            <div class="flex items-center gap-3.5">
                <div class="h-11 w-11 rounded-2xl bg-indigo-50 dark:bg-indigo-950/70 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-black text-xl border border-indigo-200 dark:border-indigo-800 shrink-0 shadow-2xs">
                    💾
                </div>
                <div>
                    <span class="block text-sm font-extrabold text-gray-900 dark:text-white">
                        Guardado de Configuración General
                    </span>
                    <span class="block text-xs text-gray-500 dark:text-slate-400 mt-0.5">
                        Aplica a todas las fichas de eventos, portal de clientes y propuestas PDF oficiales.
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                @if (session()->has('message'))
                    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show" class="hidden sm:flex items-center gap-1.5 px-3.5 py-2 bg-emerald-50 dark:bg-emerald-950/70 text-emerald-800 dark:text-emerald-200 border border-emerald-300 dark:border-emerald-700 rounded-xl text-xs font-bold shadow-xs">
                        <span>✅</span> <span>¡Guardado con éxito!</span>
                    </div>
                @endif

                <div wire:loading wire:target="save" class="flex items-center gap-2 text-xs font-bold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/70 px-3.5 py-2 rounded-xl border border-indigo-200 dark:border-indigo-800">
                    <svg class="animate-spin h-4 w-4 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Guardando cambios...</span>
                </div>

                <button 
                    type="submit" 
                    wire:click="save"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-extrabold py-3 px-8 rounded-2xl shadow-lg shadow-indigo-600/30 transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 text-sm"
                >
                    <span wire:loading.remove wire:target="save">💾 Guardar Configuración</span>
                    <span wire:loading wire:target="save">Procesando...</span>
                </button>
            </div>
        </div>

    </form>
</div>

<script>
function adminSignaturePad() {
    return {
        mode: 'draw',
        canvas: null,
        ctx: null,
        isDrawing: false,
        hasDrawn: false,
        lastX: 0,
        lastY: 0,
        points: [],

        init() {
            this.$nextTick(() => {
                this.canvas = this.$refs.canvas;
                if (!this.canvas) return;

                this.ctx = this.canvas.getContext('2d');
                this.resizeCanvas();

                let resizeTimer;
                const debouncedResize = () => {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(() => this.resizeCanvas(), 100);
                };

                window.addEventListener('resize', debouncedResize);
                window.addEventListener('orientationchange', debouncedResize);

                if (window.ResizeObserver && this.canvas.parentElement) {
                    const ro = new ResizeObserver(debouncedResize);
                    ro.observe(this.canvas.parentElement);
                }

                // Eventos de ratón
                this.canvas.addEventListener('mousedown', (e) => this.start(e));
                this.canvas.addEventListener('mousemove', (e) => this.move(e));
                window.addEventListener('mouseup', () => this.stop());

                // Eventos táctiles para móviles y tablets
                this.canvas.addEventListener('touchstart', (e) => this.start(e), { passive: false });
                this.canvas.addEventListener('touchmove', (e) => this.move(e), { passive: false });
                window.addEventListener('touchend', (e) => this.stop(e), { passive: false });
                window.addEventListener('touchcancel', (e) => this.stop(e), { passive: false });
            });
        },

        getCoords(e) {
            if (!this.canvas) return { x: 0, y: 0 };
            const rect = this.canvas.getBoundingClientRect();
            
            let clientX = e.clientX;
            let clientY = e.clientY;

            if (e.touches && e.touches.length > 0) {
                clientX = e.touches[0].clientX;
                clientY = e.touches[0].clientY;
            } else if (e.changedTouches && e.changedTouches.length > 0) {
                clientX = e.changedTouches[0].clientX;
                clientY = e.changedTouches[0].clientY;
            }

            const scaleX = this.canvas.width / (rect.width || 1);
            const scaleY = this.canvas.height / (rect.height || 1);

            return {
                x: (clientX - rect.left) * scaleX,
                y: (clientY - rect.top) * scaleY
            };
        },

        resizeCanvas() {
            if (!this.canvas) return;
            const rect = this.canvas.getBoundingClientRect();
            const dpr = Math.max(window.devicePixelRatio || 1, 1);

            let tempImage = null;
            if (this.hasDrawn) {
                tempImage = this.canvas.toDataURL();
            }

            const w = Math.max(rect.width, 280);
            const h = Math.max(rect.height, 190);

            this.canvas.width = Math.round(w * dpr);
            this.canvas.height = Math.round(h * dpr);

            this.ctx = this.canvas.getContext('2d');
            this.ctx.lineWidth = Math.max(3 * dpr, 2.8);
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';
            this.ctx.strokeStyle = '#1e3a8a'; // Tinta azul marino oscuro de máxima legibilidad

            if (tempImage) {
                const img = new Image();
                img.onload = () => {
                    this.ctx.drawImage(img, 0, 0, this.canvas.width, this.canvas.height);
                };
                img.src = tempImage;
            }
        },

        start(e) {
            if (e.type.startsWith('touch')) {
                e.preventDefault();
            }
            this.isDrawing = true;
            this.hasDrawn = true;
            const coords = this.getCoords(e);
            this.lastX = coords.x;
            this.lastY = coords.y;
            this.points = [coords];

            // Punto inicial
            this.ctx.beginPath();
            this.ctx.arc(coords.x, coords.y, this.ctx.lineWidth / 2.2, 0, Math.PI * 2);
            this.ctx.fillStyle = '#1e3a8a';
            this.ctx.fill();
        },

        move(e) {
            if (!this.isDrawing) return;
            if (e.type.startsWith('touch')) {
                e.preventDefault();
            }
            const coords = this.getCoords(e);
            this.points.push(coords);

            // Suavizado por curvas cuadráticas de Bézier
            if (this.points.length >= 3) {
                const len = this.points.length;
                const p1 = this.points[len - 2];
                const p2 = this.points[len - 1];
                const midX = (p1.x + p2.x) / 2;
                const midY = (p1.y + p2.y) / 2;

                this.ctx.beginPath();
                this.ctx.moveTo(this.lastX, this.lastY);
                this.ctx.quadraticCurveTo(p1.x, p1.y, midX, midY);
                this.ctx.stroke();

                this.lastX = midX;
                this.lastY = midY;
            } else {
                this.ctx.beginPath();
                this.ctx.moveTo(this.lastX, this.lastY);
                this.ctx.lineTo(coords.x, coords.y);
                this.ctx.stroke();
                this.lastX = coords.x;
                this.lastY = coords.y;
            }
        },

        stop(e) {
            if (this.isDrawing) {
                if (e && e.type && e.type.startsWith('touch')) {
                    e.preventDefault();
                }
                this.isDrawing = false;
                this.points = [];
                this.syncSignature();
            }
        },

        clearSignature() {
            if (!this.canvas) return;
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
            this.hasDrawn = false;
            this.points = [];
            this.$wire.set('company_signature_canvas', '');
        },

        syncSignature() {
            if (!this.canvas || !this.hasDrawn) return;
            const dataUrl = this.canvas.toDataURL('image/png');
            this.$wire.set('company_signature_canvas', dataUrl);
        }
    };
}
</script>

