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

    <!-- FLASH MESSAGES -->
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

    <!-- TABS -->
    <div class="flex border-b border-gray-200 bg-white rounded-t-xl px-4 pt-3 gap-2 overflow-x-auto">
        <button type="button" wire:click="$set('activeTab', 'general')" class="py-3 px-4 text-xs font-bold uppercase tracking-wider border-b-2 transition-all flex items-center gap-2 {{ $activeTab === 'general' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            <span>🏢</span> Datos Generales
        </button>
        <button type="button" wire:click="$set('activeTab', 'pricing')" class="py-3 px-4 text-xs font-bold uppercase tracking-wider border-b-2 transition-all flex items-center gap-2 {{ $activeTab === 'pricing' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            <span>💶</span> Tarifas y Precios
        </button>
        <button type="button" wire:click="$set('activeTab', 'contracts')" class="py-3 px-4 text-xs font-bold uppercase tracking-wider border-b-2 transition-all flex items-center gap-2 {{ $activeTab === 'contracts' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            <span>📜</span> Editor de Contratos
        </button>
        <button type="button" wire:click="$set('activeTab', 'dossier')" class="py-3 px-4 text-xs font-bold uppercase tracking-wider border-b-2 transition-all flex items-center gap-2 {{ $activeTab === 'dossier' ? 'border-amber-600 text-amber-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            <span>📖</span> Dossier y Propuestas
        </button>
        <button type="button" wire:click="$set('activeTab', 'music_integrations')" class="py-3 px-4 text-xs font-bold uppercase tracking-wider border-b-2 transition-all flex items-center gap-2 {{ $activeTab === 'music_integrations' ? 'border-pink-600 text-pink-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            <span>🍎🟢</span> Apple Music & Spotify
        </button>
        <button type="button" wire:click="$set('activeTab', 'email_smtp')" class="py-3 px-4 text-xs font-bold uppercase tracking-wider border-b-2 transition-all flex items-center gap-2 {{ $activeTab === 'email_smtp' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            <span>✉️</span> Correo (SMTP)
        </button>
    </div>

    <!-- FORM GLOBAL -->
    <form wire:submit.prevent="save">
        
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

                <!-- SECCIÓN MULTI-MARCA (NÚÑEZ AND SON & JAVNX DJ) -->
                <div class="pt-6 border-t border-gray-200 space-y-4">
                    <div>
                        <h4 class="text-sm font-extrabold text-gray-900 flex items-center gap-2">
                            <span>👑🎧</span> Configuración de Marcas Comerciales (Multi-Marca)
                        </h4>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Define los datos que aparecerán automáticamente en las propuestas según la marca que selecciones en cada evento.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <!-- PERFIL 1: NÚÑEZ AND SON -->
                        <div class="p-4 bg-amber-50/60 rounded-2xl border border-amber-200 space-y-3">
                            <div class="flex items-center gap-2 border-b border-amber-200 pb-2">
                                <span class="text-xl">👑</span>
                                <div>
                                    <strong class="text-xs font-black text-amber-950 block">Marca 1: Núñez and Son</strong>
                                    <span class="text-[11px] text-amber-800">Bodas, eventos familiares y sonorización tradicional</span>
                                </div>
                            </div>

                            <div class="space-y-2.5 text-xs">
                                <div>
                                    <label class="block font-bold text-amber-900 mb-0.5">Nombre Comercial</label>
                                    <input type="text" wire:model="brand_nunez_name" class="w-full border-amber-300 rounded-lg p-2 text-xs bg-white text-slate-800 font-bold">
                                </div>
                                <div>
                                    <label class="block font-bold text-amber-900 mb-0.5">Subtítulo / Especialidad</label>
                                    <input type="text" wire:model="brand_nunez_subtitle" class="w-full border-amber-300 rounded-lg p-2 text-xs bg-white text-slate-800">
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block font-bold text-amber-900 mb-0.5">Teléfono 1 (Fran)</label>
                                        <input type="text" wire:model="brand_nunez_phone" class="w-full border-amber-300 rounded-lg p-2 text-xs bg-white text-slate-800">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-amber-900 mb-0.5">Teléfono 2 (Miguel)</label>
                                        <input type="text" wire:model="brand_nunez_phone_2" class="w-full border-amber-300 rounded-lg p-2 text-xs bg-white text-slate-800">
                                    </div>
                                </div>
                                <div>
                                    <label class="block font-bold text-amber-900 mb-0.5">Página Web / Landing</label>
                                    <input type="text" wire:model="brand_nunez_website" class="w-full border-amber-300 rounded-lg p-2 text-xs bg-white text-slate-800">
                                </div>
                                <div>
                                    <label class="block font-bold text-amber-900 mb-0.5">Email de Contacto</label>
                                    <input type="email" wire:model="brand_nunez_email" class="w-full border-amber-300 rounded-lg p-2 text-xs bg-white text-slate-800">
                                </div>
                            </div>
                        </div>

                        <!-- PERFIL 2: JAVNX DJ -->
                        <div class="p-4 bg-indigo-50/60 rounded-2xl border border-indigo-200 space-y-3">
                            <div class="flex items-center gap-2 border-b border-indigo-200 pb-2">
                                <span class="text-xl">🎧</span>
                                <div>
                                    <strong class="text-xs font-black text-indigo-950 block">Marca 2: JAVNX DJ</strong>
                                    <span class="text-[11px] text-indigo-800">Fiestas, sesiones DJ, eventos de empresa y festivales</span>
                                </div>
                            </div>

                            <div class="space-y-2.5 text-xs">
                                <div>
                                    <label class="block font-bold text-indigo-900 mb-0.5">Nombre Comercial</label>
                                    <input type="text" wire:model="brand_javnx_name" class="w-full border-indigo-300 rounded-lg p-2 text-xs bg-white text-slate-800 font-bold">
                                </div>
                                <div>
                                    <label class="block font-bold text-indigo-900 mb-0.5">Subtítulo / Especialidad</label>
                                    <input type="text" wire:model="brand_javnx_subtitle" class="w-full border-indigo-300 rounded-lg p-2 text-xs bg-white text-slate-800">
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block font-bold text-indigo-900 mb-0.5">Teléfono Principal</label>
                                        <input type="text" wire:model="brand_javnx_phone" class="w-full border-indigo-300 rounded-lg p-2 text-xs bg-white text-slate-800">
                                    </div>
                                    <div>
                                        <label class="block font-bold text-indigo-900 mb-0.5">Teléfono Secundario (Opcional)</label>
                                        <input type="text" wire:model="brand_javnx_phone_2" class="w-full border-indigo-300 rounded-lg p-2 text-xs bg-white text-slate-800">
                                    </div>
                                </div>
                                <div>
                                    <label class="block font-bold text-indigo-900 mb-0.5">Página Web / Redes</label>
                                    <input type="text" wire:model="brand_javnx_website" class="w-full border-indigo-300 rounded-lg p-2 text-xs bg-white text-slate-800">
                                </div>
                                <div>
                                    <label class="block font-bold text-indigo-900 mb-0.5">Email de Contacto</label>
                                    <input type="email" wire:model="brand_javnx_email" class="w-full border-indigo-300 rounded-lg p-2 text-xs bg-white text-slate-800">
                                </div>
                            </div>
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
                    <!-- Pack Básico -->
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase text-slate-600">Pack Básico</span>
                            <span class="text-xs bg-slate-200 text-slate-800 font-bold px-2 py-0.5 rounded">Estándar</span>
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

                    <!-- Pack Medio -->
                    <div class="p-4 bg-indigo-50/60 rounded-xl border-2 border-indigo-200 space-y-3 relative">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase text-indigo-800">Pack Medio</span>
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

                    <!-- Pack Premium -->
                    <div class="p-4 bg-amber-50/60 rounded-xl border border-amber-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase text-amber-800">Pack Premium</span>
                            <span class="text-xs bg-amber-200 text-amber-900 font-bold px-2 py-0.5 rounded">👑 Alta Gama</span>
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
            
            <!-- HEADER DE SECCIÓN CON PRESETS -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 space-y-4">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-gray-100 pb-4">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <span>📖</span> Personalización de Dossier y Propuestas PDF
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Configura los textos de presentación, bloques de equipamiento y fotos reales de tus montajes que verán tus clientes al descargar o recibir la propuesta comercial.</p>
                    </div>

                    <!-- BOTONES DE PRESETS / PLANTILLAS RÁPIDAS -->
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-bold text-gray-500 mr-1">Cargar Plantilla:</span>
                        @foreach($dossierPresets as $key => $preset)
                            <button type="button" wire:click="loadDossierPreset('{{ $key }}')" class="px-3 py-1.5 text-xs font-bold rounded-xl border transition flex items-center gap-1.5 shadow-2xs {{ $selectedDossierPreset === $key ? 'bg-amber-100 text-amber-900 border-amber-400 font-black' : 'bg-white text-gray-700 border-gray-300 hover:bg-amber-50 hover:text-amber-800' }}">
                                {{ $preset['name'] }}
                            </button>
                        @endforeach
                    </div>
                </div>

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

            <!-- SECCIÓN 3: MOSAICO DE EQUIPAMIENTO Y SUBIDA DE FOTOS REALES -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6 space-y-6">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h4 class="text-sm font-black text-gray-900 flex items-center gap-2">
                            <span>📸</span> 3. Bloques de Equipamiento y Fotos Reales
                        </h4>
                        <p class="text-xs text-gray-500 mt-0.5">Puedes subir fotos de tus montajes reales para cada bloque. Si no subes foto, se mostrará el icono representativo.</p>
                    </div>
                    <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full">
                        Fotos de Alta Calidad
                    </span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- BLOQUE 1: SONIDO PROFESIONAL -->
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 flex flex-col justify-between space-y-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black text-slate-800 uppercase tracking-wider">🔊 Bloque 1 (Sonido)</span>
                                @if($dossier_block1_image)
                                    <button type="button" wire:click="deleteDossierImage('block1')" class="text-[11px] text-rose-600 hover:text-rose-800 font-bold">
                                        ✕ Quitar Foto
                                    </button>
                                @endif
                            </div>

                            <!-- Preview o Subida de Foto -->
                            <div class="space-y-2">
                                @if ($dossier_block1_image_upload)
                                    <div class="relative rounded-lg overflow-hidden border-2 border-amber-500 h-32 bg-slate-900">
                                        <img src="{{ $dossier_block1_image_upload->temporaryUrl() }}" class="w-full h-full object-cover">
                                        <span class="absolute bottom-1 right-1 bg-amber-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded">Nueva Foto</span>
                                    </div>
                                @elseif ($dossier_block1_image)
                                    <div class="relative rounded-lg overflow-hidden border border-slate-300 h-32 bg-slate-900">
                                        <img src="{{ asset('storage/' . $dossier_block1_image) }}" class="w-full h-full object-cover">
                                        <span class="absolute bottom-1 right-1 bg-slate-900/80 text-white text-[10px] font-bold px-1.5 py-0.5 rounded">Foto Actual</span>
                                    </div>
                                @else
                                    <div class="rounded-lg border-2 border-dashed border-slate-300 h-32 flex flex-col items-center justify-center text-slate-400 bg-white">
                                        <span class="text-2xl">🔊</span>
                                        <span class="text-[11px] font-medium mt-1">Sin foto (Usa icono)</span>
                                    </div>
                                @endif

                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Subir / Cambiar Foto de Sonido:</label>
                                    <input type="file" wire:model="dossier_block1_image_upload" accept="image/*" class="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                    @error('dossier_block1_image_upload') <span class="text-rose-500 text-xs block mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Título del Bloque 1</label>
                                <input type="text" wire:model="dossier_block1_title" class="w-full border-gray-300 rounded-lg shadow-sm text-xs font-bold">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Descripción del Bloque 1</label>
                                <textarea wire:model="dossier_block1_desc" rows="3" class="w-full border-gray-300 rounded-lg shadow-sm text-xs text-gray-700"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- BLOQUE 2: ILUMINACIÓN -->
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 flex flex-col justify-between space-y-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black text-slate-800 uppercase tracking-wider">💡 Bloque 2 (Iluminación)</span>
                                @if($dossier_block2_image)
                                    <button type="button" wire:click="deleteDossierImage('block2')" class="text-[11px] text-rose-600 hover:text-rose-800 font-bold">
                                        ✕ Quitar Foto
                                    </button>
                                @endif
                            </div>

                            <!-- Preview o Subida de Foto -->
                            <div class="space-y-2">
                                @if ($dossier_block2_image_upload)
                                    <div class="relative rounded-lg overflow-hidden border-2 border-amber-500 h-32 bg-slate-900">
                                        <img src="{{ $dossier_block2_image_upload->temporaryUrl() }}" class="w-full h-full object-cover">
                                        <span class="absolute bottom-1 right-1 bg-amber-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded">Nueva Foto</span>
                                    </div>
                                @elseif ($dossier_block2_image)
                                    <div class="relative rounded-lg overflow-hidden border border-slate-300 h-32 bg-slate-900">
                                        <img src="{{ asset('storage/' . $dossier_block2_image) }}" class="w-full h-full object-cover">
                                        <span class="absolute bottom-1 right-1 bg-slate-900/80 text-white text-[10px] font-bold px-1.5 py-0.5 rounded">Foto Actual</span>
                                    </div>
                                @else
                                    <div class="rounded-lg border-2 border-dashed border-slate-300 h-32 flex flex-col items-center justify-center text-slate-400 bg-white">
                                        <span class="text-2xl">💡</span>
                                        <span class="text-[11px] font-medium mt-1">Sin foto (Usa icono)</span>
                                    </div>
                                @endif

                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Subir / Cambiar Foto de Luces:</label>
                                    <input type="file" wire:model="dossier_block2_image_upload" accept="image/*" class="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                    @error('dossier_block2_image_upload') <span class="text-rose-500 text-xs block mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Título del Bloque 2</label>
                                <input type="text" wire:model="dossier_block2_title" class="w-full border-gray-300 rounded-lg shadow-sm text-xs font-bold">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Descripción del Bloque 2</label>
                                <textarea wire:model="dossier_block2_desc" rows="3" class="w-full border-gray-300 rounded-lg shadow-sm text-xs text-gray-700"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- BLOQUE 3: SESIÓN DJ EN DIRECTO -->
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 flex flex-col justify-between space-y-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black text-slate-800 uppercase tracking-wider">🎧 Bloque 3 (DJ / Cabina)</span>
                                @if($dossier_block3_image)
                                    <button type="button" wire:click="deleteDossierImage('block3')" class="text-[11px] text-rose-600 hover:text-rose-800 font-bold">
                                        ✕ Quitar Foto
                                    </button>
                                @endif
                            </div>

                            <!-- Preview o Subida de Foto -->
                            <div class="space-y-2">
                                @if ($dossier_block3_image_upload)
                                    <div class="relative rounded-lg overflow-hidden border-2 border-amber-500 h-32 bg-slate-900">
                                        <img src="{{ $dossier_block3_image_upload->temporaryUrl() }}" class="w-full h-full object-cover">
                                        <span class="absolute bottom-1 right-1 bg-amber-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded">Nueva Foto</span>
                                    </div>
                                @elseif ($dossier_block3_image)
                                    <div class="relative rounded-lg overflow-hidden border border-slate-300 h-32 bg-slate-900">
                                        <img src="{{ asset('storage/' . $dossier_block3_image) }}" class="w-full h-full object-cover">
                                        <span class="absolute bottom-1 right-1 bg-slate-900/80 text-white text-[10px] font-bold px-1.5 py-0.5 rounded">Foto Actual</span>
                                    </div>
                                @else
                                    <div class="rounded-lg border-2 border-dashed border-slate-300 h-32 flex flex-col items-center justify-center text-slate-400 bg-white">
                                        <span class="text-2xl">🎧</span>
                                        <span class="text-[11px] font-medium mt-1">Sin foto (Usa icono)</span>
                                    </div>
                                @endif

                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Subir / Cambiar Foto de Cabina/DJ:</label>
                                    <input type="file" wire:model="dossier_block3_image_upload" accept="image/*" class="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                    @error('dossier_block3_image_upload') <span class="text-rose-500 text-xs block mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Título del Bloque 3</label>
                                <input type="text" wire:model="dossier_block3_title" class="w-full border-gray-300 rounded-lg shadow-sm text-xs font-bold">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Descripción del Bloque 3</label>
                                <textarea wire:model="dossier_block3_desc" rows="3" class="w-full border-gray-300 rounded-lg shadow-sm text-xs text-gray-700"></textarea>
                            </div>
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
                        <div class="flex items-center gap-2 self-start sm:self-auto" wire:ignore>
                            @if(!empty($spotifyUser['connected']))
                                <form method="POST" action="{{ route('spotify.disconnect') }}" onsubmit="return confirm('¿Seguro que deseas desconectar tu cuenta de Spotify?')">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs rounded-xl transition cursor-pointer">
                                        Desconectar Cuenta
                                    </button>
                                </form>
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
                                    <p class="text-[11px] text-rose-700 mt-1">Revisa que <code>MAIL_HOST</code>, <code>MAIL_PORT</code>, <code>MAIL_USERNAME</code> y <code>MAIL_PASSWORD</code> en el <code>.env</code> coincidan exactamente con tu cuenta de correo en Plesk.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>

        </div>

        <!-- BOTÓN DE GUARDAR GLOBAL -->
        <div class="mt-6 flex items-center justify-between bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <span class="text-xs text-gray-500">
                Los cambios aplicados se guardarán para todas las fichas de evento, modo cabina y peticiones QR.
            </span>
            <button type="submit" class="bg-indigo-600 text-white font-bold py-2.5 px-8 rounded-lg shadow-md hover:bg-indigo-700 transition-all flex items-center gap-2 cursor-pointer">
                <span>💾</span> Guardar Configuración
            </button>
        </div>

    </form>
</div>
