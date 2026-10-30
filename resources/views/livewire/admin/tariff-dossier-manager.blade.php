<div class="space-y-6 pb-16">
    <!-- CABECERA EJECUTIVA Y BOTONES DE ACCIÓN (VIDEOLLAMADA + EXPORTAR PDF + ENVIAR WHATSAPP) -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-900 to-indigo-950 text-white rounded-2xl p-6 shadow-lg border border-amber-500/30">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/20 border border-amber-400/40 text-amber-300 text-xs font-extrabold uppercase tracking-wider mb-2">
                    <span>📘 Dossier General de Tarifas ({{ $config['tariff_year'] ?? '2027' }})</span>
                    <span>&bull;</span>
                    <span>{{ $report['plan']['total_pages'] }} páginas</span>
                </div>
                <h1 class="text-2xl font-black text-white tracking-tight">
                    Generador de Dossier General de Tarifas
                </h1>
                <p class="text-xs sm:text-sm text-slate-300 mt-1">
                    Dossier general único para todas las parejas (independiente del presupuesto personalizado por boda). Orden fijo de 8 a 10 páginas optimizado para móvil (&lt; 10 MB).
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <!-- Botón Guardar -->
                <button
                    type="button"
                    wire:click="save"
                    class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-600 text-white text-xs font-bold transition cursor-pointer flex items-center gap-1.5"
                >
                    <span>💾</span> Guardar Cambios
                </button>

                <!-- Botón Compartir en Videollamada -->
                <a
                    href="{{ route('admin.tariff-dossier.presentation', ['brand' => $config['brand'] ?? 'nunez_and_son']) }}"
                    target="_blank"
                    class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-black shadow-md transition flex items-center gap-1.5"
                >
                    <span>🖥️</span> Compartir en videollamada
                </a>

                <!-- Botón Enviar PDF por WhatsApp -->
                <button
                    type="button"
                    wire:click="openSendModal"
                    class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-md transition cursor-pointer flex items-center gap-1.5"
                >
                    <span>📲</span> Enviar PDF
                </button>

                <!-- Botón Generar / Exportar PDF -->
                <button
                    type="button"
                    wire:click="exportPdf"
                    class="px-5 py-2.5 rounded-xl font-black text-xs shadow-lg transition cursor-pointer flex items-center gap-1.5 {{ $report['can_export'] ? 'bg-amber-500 hover:bg-amber-400 text-slate-950' : 'bg-rose-600 hover:bg-rose-500 text-white' }}"
                >
                    <span>📄</span> {{ $report['can_export'] ? 'Generar y Descargar PDF' : 'Revisar Checklist para Exportar PDF' }}
                </button>
            </div>
        </div>

        @if(session()->has('dossier_saved'))
            <div class="mt-4 p-3 rounded-xl bg-emerald-500/20 border border-emerald-400/40 text-emerald-200 text-xs font-bold">
                {{ session('dossier_saved') }}
            </div>
        @endif

        @if(session()->has('dossier_error'))
            <div class="mt-4 p-3.5 rounded-xl bg-rose-500/25 border border-rose-400 text-rose-100 text-xs font-bold">
                {{ session('dossier_error') }}
            </div>
        @endif
    </div>

    <!-- ========================================================================= -->
    <!-- CHECKLIST DE VALIDACIÓN ANTES DE GENERAR (10 PUNTOS VISIBLES EN PANTALLA) -->
    <!-- ========================================================================= -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border {{ $report['can_export'] ? 'border-emerald-300 dark:border-emerald-800' : 'border-amber-300 dark:border-amber-700' }} p-5 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
            <div>
                <h2 class="text-sm font-black uppercase tracking-wider text-slate-900 dark:text-white flex items-center gap-2">
                    <span>{{ $report['can_export'] ? '✅' : '⚠️' }}</span>
                    <span>Checklist de Validación antes de Generar el PDF</span>
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Si algún punto obligatorio falla, corrígelo abajo antes de exportar el PDF final.
                </p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-extrabold {{ $report['can_export'] ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' }}">
                {{ $report['can_export'] ? '✓ Todo validado: Listo para exportar PDF' : 'Pendiente de completar puntos en rojo' }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach($report['checks'] as $key => $check)
                <div class="p-3 rounded-xl border flex items-start gap-2.5 {{ $check['ok'] ? (!empty($check['warning']) ? 'bg-amber-50/60 border-amber-200 dark:bg-amber-950/20 dark:border-amber-800/50' : 'bg-emerald-50/50 border-emerald-200 dark:bg-emerald-950/20 dark:border-emerald-800/40') : 'bg-rose-50 border-rose-300 dark:bg-rose-950/40 dark:border-rose-800' }}">
                    <span class="text-sm shrink-0 mt-0.5">
                        @if($check['ok'])
                            {{ !empty($check['warning']) ? 'ℹ️' : '✅' }}
                        @else
                            ❌
                        @endif
                    </span>
                    <div class="text-xs">
                        <div class="font-extrabold {{ $check['ok'] ? 'text-slate-900 dark:text-white' : 'text-rose-800 dark:text-rose-300' }}">
                            {{ $check['title'] }}
                        </div>
                        <div class="text-slate-600 dark:text-slate-300 mt-0.5 leading-snug">
                            {{ $check['detail'] }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 1. PORTADA                                                                -->
    <!-- ========================================================================= -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <h3 class="text-base font-black text-slate-900 dark:text-white">
                1. Portada (Página 1)
            </h3>
            <span class="text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-0.5 rounded-full">
                Degradado azul oscuro + acentos dorados
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Marca del Dossier</label>
                <select wire:model.live="config.brand" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs font-bold p-2.5">
                    <option value="nunez_and_son">Núñez and Son</option>
                    <option value="javnx">JAVNX DJ</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Año de las Tarifas (visible en portada)</label>
                <input type="text" wire:model.live="config.tariff_year" placeholder="2027" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs font-bold p-2.5">
                <span class="text-[11px] text-slate-400">Se mostrará como «Tarifas {{ $config['tariff_year'] ?: '2027' }}».</span>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Foto grande de portada (de la galería)</label>
                <select wire:model.live="config.cover_photo_index" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs font-bold p-2.5">
                    @foreach(($config['gallery_photos'] ?? []) as $gIdx => $gPhoto)
                        <option value="{{ $gIdx }}">
                            Foto #{{ $gIdx + 1 }} {{ !empty($gPhoto['wedding_name']) ? ('— ' . $gPhoto['wedding_name']) : '' }} {{ !empty($gPhoto['has_permission']) ? '(Con permiso)' : '(Sin permiso)' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Frase principal (qué hacemos y dónde) *
                </label>
                <input type="text" wire:model.live="config.cover_phrase" placeholder="DJ para bodas en La Rioja, Álava, Burgos y Navarra" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs font-bold p-2.5">
                <span class="text-[11px] text-slate-400">Evita frases genéricas tipo «momentos que duran para siempre».</span>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Subtítulo de apoyo en portada (opcional)
                </label>
                <input type="text" wire:model.live="config.cover_subtitle" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs p-2.5">
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. QUIÉNES SOMOS                                                          -->
    <!-- ========================================================================= -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <h3 class="text-base font-black text-slate-900 dark:text-white">
                2. Quiénes somos (Página 2)
            </h3>
            <span class="text-xs font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2.5 py-0.5 rounded-full">
                Primera persona, basado en hechos y locales reales
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="space-y-2">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Foto nuestra (Fran y Miguel) o del equipo</label>
                @if(!empty($config['about_photo']))
                    <div class="relative rounded-xl overflow-hidden border border-slate-300 dark:border-slate-700 h-44 bg-slate-950">
                        <img src="{{ asset('storage/' . ltrim($config['about_photo'], '/')) }}" class="w-full h-full object-cover">
                        <button type="button" wire:click="clearAboutPhoto" class="absolute top-2 right-2 bg-rose-600 text-white text-[10px] font-bold px-2 py-1 rounded-md cursor-pointer">
                            ✕ Quitar
                        </button>
                    </div>
                @else
                    <div class="rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-700 h-36 flex items-center justify-center text-xs text-slate-400 text-center p-4">
                        Sube una foto vuestra (Fran y Miguel) trabajando o en cabina
                    </div>
                @endif
                <input type="file" wire:model="about_photo_upload" accept="image/*" class="w-full text-xs text-slate-600 dark:text-slate-300 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 cursor-pointer">
            </div>

            <div class="lg:col-span-2 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Texto corto en primera persona (quiénes somos, qué hacemos y por qué trabajamos así)
                    </label>
                    <textarea wire:model.live="config.about_text" rows="5" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs p-3 leading-relaxed"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Puntos concretos de cómo trabajamos (1 por línea)
                    </label>
                    <textarea wire:model.live="about_facts_text" rows="4" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs p-3 leading-relaxed"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Locales, fincas y bodegas en los que hemos trabajado
                    </label>
                    <textarea wire:model.live="config.about_venues" rows="2" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs p-2.5"></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. NUESTRO TRABAJO (GALERÍA DE 6 A 9 FOTOS DE BODAS DISTINTAS CON PERMISO)-->
    <!-- ========================================================================= -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
            <div>
                <h3 class="text-base font-black text-slate-900 dark:text-white">
                    3. Nuestro trabajo (Galería de 6 a 9 fotos de bodas reales)
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Cada foto debe ser de una boda distinta y tener marcados «Boda/Evento», «Fecha» y «Permiso de la pareja = Sí». Las imágenes <code>.webp</code> o pesadas se convierten automáticamente a <code>.jpg</code> y se comprimen.
                </p>
            </div>
            <button type="button" wire:click="addGallerySlot" class="px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold cursor-pointer shrink-0">
                + Añadir Foto a la Galería
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach(($config['gallery_photos'] ?? []) as $idx => $photo)
                @php
                    $isPhotoReady = !empty($photo['has_permission']) && !empty($photo['image_path']);
                @endphp
                <div class="p-4 rounded-2xl border {{ $isPhotoReady ? 'border-emerald-300 bg-emerald-50/20 dark:border-emerald-800/60 dark:bg-slate-800/70' : 'border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/40' }} space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black text-slate-800 dark:text-white">Foto #{{ $idx + 1 }}</span>
                        <div class="flex items-center gap-2">
                            @if($isPhotoReady)
                                <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] font-extrabold">✓ Válida para PDF</span>
                            @elseif(empty($photo['image_path']))
                                <span class="px-2 py-0.5 rounded bg-slate-200 text-slate-700 text-[10px] font-bold">Sin foto</span>
                            @else
                                <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 text-[10px] font-bold">Marca permiso pareja</span>
                            @endif
                            @if(count($config['gallery_photos']) > 6)
                                <button type="button" wire:click="removeGallerySlot({{ $idx }})" class="text-rose-600 text-xs font-bold cursor-pointer">✕</button>
                            @endif
                        </div>
                    </div>

                    @if(!empty($photo['image_path']))
                        <div class="rounded-xl overflow-hidden border border-slate-300 dark:border-slate-700 h-36 bg-slate-950">
                            <img src="{{ asset('storage/' . ltrim($photo['image_path'], '/')) }}" class="w-full h-full object-cover">
                        </div>
                    @else
                        <div class="rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-700 h-36 flex items-center justify-center text-xs text-slate-400">
                            Sin foto subida
                        </div>
                    @endif

                    <input type="file" wire:model="gallery_uploads.{{ $idx }}" accept="image/*" class="w-full text-[11px] text-slate-600 dark:text-slate-300 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-bold file:bg-indigo-50 file:text-indigo-700 cursor-pointer">

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">Boda / Evento (opcional, bodas distintas)</label>
                        <input type="text" wire:model.live="config.gallery_photos.{{ $idx }}.wedding_name" placeholder="Ej: Boda de Marta y Javi (Finca Los Arandinos)" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-xs p-2">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">Fecha (opcional)</label>
                            <input type="date" wire:model.live="config.gallery_photos.{{ $idx }}.wedding_date" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-xs p-2">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">Permiso pareja *</label>
                            <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 cursor-pointer">
                                <input type="checkbox" wire:model.live="config.gallery_photos.{{ $idx }}.has_permission" class="rounded text-emerald-600">
                                <span class="text-xs font-bold {{ !empty($photo['has_permission']) ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-500' }}">
                                    {{ !empty($photo['has_permission']) ? 'Sí, autorizado' : 'No' }}
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. CÓMO TRABAJAMOS (PASO A PASO CON PLAZO CONCRETO OBLIGATORIO)           -->
    <!-- ========================================================================= -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
            <div>
                <h3 class="text-base font-black text-slate-900 dark:text-white">
                    4. Cómo trabajamos (Paso a paso)
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Empieza por lo que la pareja gana (<strong>disponibilidad y propuesta</strong>) y deja la señal como <strong>paso 3</strong> (el pago unido al valor de bloquear su fecha). Títulos cortos para que no se corten; los detalles van en la explicación.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <button type="button" wire:click="applySevenWorkStepsPreset" class="px-3 py-1.5 rounded-xl bg-amber-500/15 hover:bg-amber-500/25 border border-amber-500/40 text-amber-800 dark:text-amber-300 text-xs font-extrabold cursor-pointer">
                    ↺ 7 pasos recomendados
                </button>
                <button type="button" wire:click="applyCompactWorkStepsPreset" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold cursor-pointer">
                    ↺ Versión compacta («Consulta y propuesta» unidos)
                </button>
                <button type="button" wire:click="addWorkStep" class="px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold cursor-pointer">
                    + Añadir Paso
                </button>
            </div>
        </div>

        <div class="space-y-3">
            @foreach(($config['work_steps'] ?? []) as $sIdx => $step)
                @php
                    $missingTf = empty(trim((string) ($step['timeframe'] ?? '')));
                @endphp
                <div class="p-4 rounded-xl border {{ $missingTf ? 'border-rose-300 bg-rose-50/40 dark:border-rose-800 dark:bg-rose-950/20' : 'border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/50' }} space-y-2.5">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-start">
                        <div class="md:col-span-7">
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Paso {{ $sIdx + 1 }} — Título corto *
                            </label>
                            <input type="text" wire:model.live="config.work_steps.{{ $sIdx }}.title" placeholder="Ej: Consulta y disponibilidad" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-xs font-bold p-2">
                        </div>
                        <div class="md:col-span-5">
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-[11px] font-black {{ $missingTf ? 'text-rose-600 dark:text-rose-400' : 'text-amber-700 dark:text-amber-400' }}">
                                    ⏰ Plazo concreto (pon solo el que puedas cumplir siempre) *
                                </label>
                                @if(count($config['work_steps']) > 1)
                                    <button type="button" wire:click="removeWorkStep({{ $sIdx }})" class="text-[11px] text-rose-600 font-bold cursor-pointer">Quitar paso</button>
                                @endif
                            </div>
                            <input type="text" wire:model.live="config.work_steps.{{ $sIdx }}.timeframe" placeholder="Ej: En menos de 24 h / Llegada 2 h antes del primer servicio" class="w-full rounded-lg {{ $missingTf ? 'border-rose-400 ring-1 ring-rose-400' : 'border-slate-300 dark:border-slate-700' }} dark:bg-slate-900 dark:text-white text-xs font-bold p-2">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Explicación detallada (se muestra completa sin cortarse)
                        </label>
                        <textarea wire:model.live="config.work_steps.{{ $sIdx }}.description" rows="2" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-xs p-2 leading-relaxed"></textarea>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 5. PACKS Y PRECIO (PÁGINA CENTRAL, 3 PACKS, IVA INCLUIDO SIEMPRE)         -->
    <!-- ========================================================================= -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
            <div>
                <h3 class="text-base font-black text-slate-900 dark:text-white">
                    5. Packs y precio (Página central — La más importante)
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Exactamente 3 packs en columnas con el del medio destacado («El más elegido»). En el PDF siempre se muestra el precio final con la leyenda <strong>«IVA incluido»</strong>.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" wire:click="applyOrientativeLightingToPacks" class="px-3 py-2 rounded-xl bg-amber-500/15 hover:bg-amber-500/25 border border-amber-500/40 text-amber-800 dark:text-amber-300 text-xs font-extrabold cursor-pointer">
                    💡 Poner iluminación orientativa según el local
                </button>
                <label class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 cursor-pointer">
                    <input type="checkbox" wire:model.live="config.prices_include_vat" class="rounded text-indigo-600">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                        Los precios escritos abajo ya llevan IVA incluido (si lo desmarcas, la app les suma el {{ $config['vat_rate'] ?? 21 }}% de IVA automáticamente)
                    </span>
                </label>
            </div>
        </div>

        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                Nota visible en la página de Packs (aclaración de que la iluminación y el equipo son orientativos y se adaptan a cada salón/finca)
            </label>
            <textarea wire:model.live="config.packs_section_note" rows="2" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-xs p-2 leading-relaxed"></textarea>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            @foreach(($config['packs'] ?? []) as $pIdx => $pack)
                @php
                    $isMid = ($pIdx === 1);
                    $finalPackPrice = \App\Services\GeneralTariffDossierService::computeFinalPriceWithVat(
                        $pack['price_input'] ?? 0,
                        !empty($config['prices_include_vat']),
                        (float) ($config['vat_rate'] ?? 21)
                    );
                @endphp
                <div class="p-5 rounded-2xl border {{ $isMid ? 'border-2 border-amber-400 bg-amber-50/20 dark:bg-slate-800' : 'border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/40' }} space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black uppercase tracking-wider {{ $isMid ? 'text-amber-700 dark:text-amber-400' : 'text-slate-500' }}">
                            {{ $isMid ? '★ Pack Central Destacado (El más elegido)' : ('Pack Columna ' . ($pIdx + 1)) }}
                        </span>
                        <span class="px-2.5 py-0.5 rounded-md bg-emerald-100 text-emerald-800 text-xs font-black">
                            Final en PDF: {{ number_format($finalPackPrice, 0, ',', '.') }} € (IVA incluido)
                        </span>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div class="col-span-2">
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">Nombre del Pack *</label>
                            <input type="text" wire:model.live="config.packs.{{ $pIdx }}.name" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-xs font-black p-2">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">Horas fiesta *</label>
                            <input type="number" min="1" max="15" wire:model.live="config.packs.{{ $pIdx }}.hours" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-xs font-bold p-2 text-center">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">Precio ({{ !empty($config['prices_include_vat']) ? 'con IVA incluido' : 'base sin IVA' }}) € *</label>
                        <input type="number" step="1" min="1" wire:model.live="config.packs.{{ $pIdx }}.price_input" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-sm font-black p-2">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">Subtítulo descriptivo *</label>
                        <textarea wire:model.live="config.packs.{{ $pIdx }}.subtitle" rows="2" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-xs p-2"></textarea>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">Lista detallada de lo que incluye con números (1 ítem por línea) *</label>
                        <textarea wire:model.live="pack_includes_text.{{ $pIdx }}" rows="6" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-xs p-2 leading-relaxed"></textarea>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 6. EXTRAS (TOMADOS DE PRESUPUESTOS; SI ESTÁ VACÍO SE OMITE Y AVISA)       -->
    <!-- ========================================================================= -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
            <div>
                <h3 class="text-base font-black text-slate-900 dark:text-white">
                    6. Extras (Sincronizados con precios de Presupuestos)
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Toma las tarifas almacenadas en Ajustes de Presupuestos (incluyendo tramos por horas de Fotomatón/360 y opciones de Mesa Dulce según mesas y contenidos). Si dejas un precio vacío se omite del PDF.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <button type="button" wire:click="syncExtrasFromStoredQuotes('logrono')" class="px-3 py-1.5 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 text-xs font-extrabold cursor-pointer">
                    🔄 Sincronizar de Presupuestos (Logroño)
                </button>
                <button type="button" wire:click="syncExtrasFromStoredQuotes('vitoria')" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-600 text-xs font-bold cursor-pointer">
                    🔄 Sincronizar (Vitoria / Álava)
                </button>
                <button type="button" wire:click="addExtra" class="px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold cursor-pointer">
                    + Añadir Extra
                </button>
            </div>
        </div>

        @if(!empty($report['plan']['omitted_extras']))
            <div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 text-xs text-amber-900 dark:text-amber-300 font-semibold">
                ℹ️ <strong>Extras omitidos actualmente del PDF por no tener precio cerrado:</strong> {{ implode(', ', $report['plan']['omitted_extras']) }}. (En cuanto les pongas un precio numérico cerrado se incluirán automáticamente).
            </div>
        @endif

        <!-- Regalo Álbum de Fotos QR al contratar Ceremonia o Cóctel -->
        <div class="p-4 rounded-xl border border-emerald-300 dark:border-emerald-800 bg-emerald-50/50 dark:bg-emerald-950/20 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <label class="inline-flex items-center gap-2.5 cursor-pointer">
                    <input type="checkbox" wire:model.live="config.qr_photos_gift_enabled" class="rounded text-emerald-600 focus:ring-emerald-500 h-4 w-4">
                    <span class="text-xs font-black text-emerald-900 dark:text-emerald-300">
                        🎁 Destacar regalo de Álbum de Fotos QR (0 €) al contratar Ceremonia o Cóctel
                    </span>
                </label>
                <span class="px-2.5 py-0.5 rounded-md bg-emerald-600 text-white text-[10px] font-black uppercase tracking-wider self-start sm:self-auto">
                    Valor añadido 0 €
                </span>
            </div>

            @if(!empty($config['qr_photos_gift_enabled']))
                <div class="grid grid-cols-1 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-bold text-emerald-900 dark:text-emerald-300 mb-0.5">Título del regalo en página Extras</label>
                        <input type="text" wire:model.live="config.qr_photos_gift_title" class="w-full rounded-lg border-emerald-300 dark:border-emerald-800 dark:bg-slate-900 dark:text-white text-xs font-bold p-2">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-emerald-900 dark:text-emerald-300 mb-0.5">Descripción del regalo</label>
                        <textarea wire:model.live="config.qr_photos_gift_text" rows="2" class="w-full rounded-lg border-emerald-300 dark:border-emerald-800 dark:bg-slate-900 dark:text-white text-xs p-2"></textarea>
                    </div>
                </div>
            @endif
        </div>

        <div class="space-y-2.5">
            @foreach(($config['extras'] ?? []) as $eIdx => $extra)
                @php
                    $hasClosedPrice = preg_match('/\d/', (string) ($extra['price'] ?? '')) && !str_contains(mb_strtolower((string) ($extra['price'] ?? '')), 'consultar');
                @endphp
                <div class="p-3.5 rounded-xl border {{ $hasClosedPrice ? 'border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/50' : 'border-amber-200 bg-amber-50/30 dark:border-amber-800/50 dark:bg-amber-950/10' }} grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                    <div class="md:col-span-4">
                        <input type="text" wire:model.live="config.extras.{{ $eIdx }}.name" placeholder="Nombre del extra" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-xs font-bold p-2">
                    </div>
                    <div class="md:col-span-5">
                        <input type="text" wire:model.live="config.extras.{{ $eIdx }}.description" placeholder="Descripción breve de qué incluye" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-xs p-2">
                    </div>
                    <div class="md:col-span-3 flex items-center gap-2">
                        <input type="text" wire:model.live="config.extras.{{ $eIdx }}.price" placeholder="Ej: 120 o 350 € (2 h) / 420 € (3 h)" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-xs font-black p-2 text-right">
                        <span class="text-xs font-bold text-slate-500">€</span>
                        <button type="button" wire:click="removeExtra({{ $eIdx }})" class="text-rose-600 text-xs font-bold px-2 cursor-pointer">✕</button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                Nota aclaratoria al pie de los Extras (horas de Fotomatón/360 y mesas/contenidos de Mesa Dulce)
            </label>
            <textarea wire:model.live="config.extras_section_note" rows="2" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs p-2.5 leading-relaxed"></textarea>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 7. OPINIONES (4-5 RESEÑAS REALES CON PERMISO O PÁGINA OMITIDA)            -->
    <!-- ========================================================================= -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
            <div>
                <h3 class="text-base font-black text-slate-900 dark:text-white">
                    7. Opiniones (4 o 5 reseñas reales con permiso)
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Si hay menos de 2 reseñas con permiso para publicar, esta página se omite automáticamente del PDF. Nunca se generan reseñas de ejemplo.
                </p>
            </div>
            <button type="button" wire:click="addReview" class="px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold cursor-pointer shrink-0">
                + Añadir Reseña Real
            </button>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Enlace a nuestras reseñas de Google</label>
            <input type="url" wire:model.live="config.google_reviews_url" placeholder="https://g.page/r/..." class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs p-2.5">
        </div>

        <div class="space-y-3">
            @forelse(($config['reviews'] ?? []) as $rIdx => $rev)
                <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">Nombre de la pareja *</label>
                            <input type="text" wire:model.live="config.reviews.{{ $rIdx }}.couple_name" placeholder="Ej: Sara y Rubén" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-xs font-bold p-2">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">Lugar / Finca *</label>
                            <input type="text" wire:model.live="config.reviews.{{ $rIdx }}.location" placeholder="Ej: Bodegas Franco-Españolas" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-xs p-2">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-0.5">Fecha de la boda *</label>
                            <input type="date" wire:model.live="config.reviews.{{ $rIdx }}.event_date" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-xs p-2">
                        </div>
                        <div class="flex items-end justify-between gap-2">
                            <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 cursor-pointer flex-1">
                                <input type="checkbox" wire:model.live="config.reviews.{{ $rIdx }}.has_permission" class="rounded text-emerald-600">
                                <span class="text-xs font-bold">Permiso para publicar</span>
                            </label>
                            <button type="button" wire:click="removeReview({{ $rIdx }})" class="text-rose-600 text-xs font-bold p-2 cursor-pointer">✕</button>
                        </div>
                    </div>
                    <div>
                        <input type="text" wire:model.live="config.reviews.{{ $rIdx }}.text" placeholder="Texto corto de la reseña real de la pareja..." class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white text-xs p-2">
                    </div>
                </div>
            @empty
                <div class="p-4 rounded-xl border border-dashed border-slate-300 dark:border-slate-700 text-center text-xs text-slate-400">
                    No hay reseñas añadidas todavía. La página de Opiniones se omitirá automáticamente del PDF hasta que añadas al menos 2 reseñas reales con permiso.
                </div>
            @endforelse
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 8. CÓMO RESERVAR                                                          -->
    <!-- ========================================================================= -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <h3 class="text-base font-black text-slate-900 dark:text-white">
                8. Cómo reservar (Última página)
            </h3>
            <span class="text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full">
                Condiciones claras y cierre sin presión
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Señal para reservar (%) *</label>
                <div class="flex items-center gap-2">
                    <input type="number" min="5" max="95" wire:model.live="config.deposit_percentage" class="w-24 rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-sm font-black p-2.5 text-center">
                    <span class="text-xs text-slate-500">% señal y {{ 100 - (int) ($config['deposit_percentage'] ?? 40) }} % el día del evento o antes</span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Formas de pago *</label>
                <input type="text" wire:model.live="config.payment_methods_text" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs font-bold p-2.5">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Tarifas válidas hasta (obligatorio) *</label>
                <input type="date" wire:model.live="config.valid_until" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs font-bold p-2.5">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Qué pasa después de reservar</label>
            <textarea wire:model.live="config.after_booking_text" rows="2" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs p-2.5"></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Teléfono Fran</label>
                <input type="text" wire:model.live="config.phone_fran" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs font-bold p-2.5">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Teléfono Miguel</label>
                <input type="text" wire:model.live="config.phone_miguel" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs font-bold p-2.5">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Correo ({{ ($config['brand'] ?? 'nunez_and_son') === 'javnx' ? 'JAVNX' : 'Núñez and Son' }})</label>
                @if(($config['brand'] ?? 'nunez_and_son') === 'javnx')
                    <input type="text" wire:model.live="config.contact_email_javnx" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs p-2.5">
                @else
                    <input type="text" wire:model.live="config.contact_email_nunez" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs p-2.5">
                @endif
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Web ({{ ($config['brand'] ?? 'nunez_and_son') === 'javnx' ? 'JAVNX' : 'Núñez and Son' }})</label>
                @if(($config['brand'] ?? 'nunez_and_son') === 'javnx')
                    <input type="text" wire:model.live="config.contact_website_javnx" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs p-2.5">
                @else
                    <input type="text" wire:model.live="config.contact_website_nunez" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs p-2.5">
                @endif
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Frase de cierre tranquila (sin presión ni falsa urgencia)</label>
            <input type="text" wire:model.live="config.closing_phrase" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs font-bold p-2.5">
        </div>
    </div>

    <!-- MODAL PARA ENVIAR EL DOSSIER DE TARIFAS POR WHATSAPP -->
    @if($showSendModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/75 p-4 backdrop-blur-xs">
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 max-w-lg w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-base font-black text-slate-900 dark:text-white">
                        📲 Enviar Dossier de Tarifas {{ $config['tariff_year'] ?? '2027' }}
                    </h3>
                    <button type="button" wire:click="$set('showSendModal', false)" class="text-slate-400 hover:text-slate-600 cursor-pointer">✕</button>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Seleccionar pareja / evento reciente (opcional)</label>
                    <select wire:model.live="selected_event_id" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs p-2.5">
                        <option value="">— Escribir nombre y teléfono manualmente —</option>
                        @foreach($recentEvents as $ev)
                            <option value="{{ $ev->id }}">
                                {{ $ev->name }} ({{ $ev->client?->phone ?: 'Sin tel.' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nombre ({nombre})</label>
                        <input type="text" wire:model.live="recipient_name" placeholder="Ej: Laura y Carlos" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs p-2">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Teléfono WhatsApp</label>
                        <input type="text" wire:model.live="recipient_phone" placeholder="+34 600..." class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs p-2">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Firma</label>
                        <select wire:model.live="sender_name" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs font-bold p-2">
                            <option value="Fran">Fran</option>
                            <option value="Miguel">Miguel</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Mensaje de WhatsApp</label>
                    <textarea wire:model="whatsapp_preview_message" rows="5" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white text-xs p-3 leading-relaxed"></textarea>
                </div>

                @if(session()->has('wa_send_ok'))
                    <div class="p-2.5 rounded-lg bg-emerald-100 text-emerald-800 text-xs font-bold">{{ session('wa_send_ok') }}</div>
                @endif
                @if(session()->has('wa_send_error'))
                    <div class="p-2.5 rounded-lg bg-rose-100 text-rose-800 text-xs font-bold">{{ session('wa_send_error') }}</div>
                @endif

                <div class="flex flex-wrap items-center justify-end gap-2 pt-2">
                    <button type="button" wire:click="sendDirectEvolutionWhatsApp" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold cursor-pointer">
                        ⚡ Enviar directo por Gateway
                    </button>
                    <button type="button" wire:click="sendViaWhatsAppWeb" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black cursor-pointer">
                        📲 Abrir en WhatsApp
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
