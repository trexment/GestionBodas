<div class="max-w-6xl mx-auto px-4 sm:px-6 py-4" x-data="{ copiedAlbumUrl: false }">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap');
        .poster-serif { font-family: 'Playfair Display', Georgia, 'Times New Roman', serif; }
    </style>

    <!-- Barra superior de navegación -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <a href="{{ route('guest.form', ['token' => $event->token]) }}"
           class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 shadow-xs transition">
            <span>&larr;</span>
            <span>Volver al Portal Musical</span>
        </a>

        @if($guestUrl !== '')
            <div class="flex flex-wrap items-center gap-2">
                <button type="button"
                        @click="navigator.clipboard.writeText(@js($guestUrl)); copiedAlbumUrl = true; setTimeout(() => copiedAlbumUrl = false, 2200)"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 shadow-xs transition cursor-pointer">
                    <span x-text="copiedAlbumUrl ? '✅ ¡Enlace copiado!' : '📋 Copiar enlace de fotos'">📋 Copiar enlace de fotos</span>
                </button>
                <a href="{{ $guestUrl }}" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-extrabold bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition">
                    <span>📸 Abrir Álbum de Fotos</span>
                </a>
            </div>
        @endif
    </div>

    <!-- Cabecera -->
    <div class="text-center max-w-2xl mx-auto mb-7">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-100/90 dark:bg-amber-950/60 border border-amber-300/80 dark:border-amber-700/60 text-amber-900 dark:text-amber-300 text-xs font-extrabold mb-2.5">
            <span>✨</span>
            <span>Cartel QR Personalizable · Listo para Imprimir</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
            Diseñad vuestro Cartel QR de Fotos
        </h1>
        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1.5 leading-relaxed">
            Personalizad vuestros nombres, las iniciales del centro del código QR, el estilo y la frase para los invitados de <strong>{{ $event->name }}</strong>. Al descargar tendréis el PDF en máxima calidad listo para imprimir en casa o en copistería.
        </p>
    </div>

    @if($guestUrl === '')
        <div class="max-w-xl mx-auto bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 rounded-2xl p-6 text-center shadow-sm">
            <div class="text-3xl mb-2">📸</div>
            <h2 class="text-base font-extrabold text-amber-950 dark:text-amber-200">El Álbum de Fotos QR aún se está preparando</h2>
            <p class="text-xs text-amber-800 dark:text-amber-300/90 mt-1.5 leading-relaxed">
                Todavía no está activado el enlace del álbum para este evento. Avisadnos por WhatsApp y os lo dejamos activo en un segundo.
            </p>
            <a href="{{ route('guest.form', ['token' => $event->token]) }}"
               class="mt-4 inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-extrabold bg-indigo-600 hover:bg-indigo-700 text-white transition">
                &larr; Volver al cuestionario musical
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- COLUMNA IZQUIERDA: CONTROLES DE PERSONALIZACIÓN -->
            <div class="lg:col-span-6 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-5 sm:p-6 shadow-sm space-y-5">

                @if($statusMessage)
                    <div class="p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-300 dark:border-emerald-700 text-emerald-900 dark:text-emerald-200 text-xs font-bold flex items-start gap-2">
                        <span>✅</span>
                        <span>{{ $statusMessage }}</span>
                    </div>
                @endif

                <!-- 1. Formato de impresión en hoja A4 -->
                <div>
                    <label class="block text-xs font-extrabold text-slate-900 dark:text-white mb-2">
                        1. ¿Cómo lo queréis imprimir? (Siempre en folio A4 estándar)
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        <button type="button"
                                wire:click="selectLayout('a4')"
                                class="text-left p-3 rounded-2xl border-2 transition cursor-pointer {{ $layout === 'a4' ? 'border-amber-500 bg-amber-50/70 dark:bg-amber-950/30 shadow-xs' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700' }}">
                            <div class="text-base mb-0.5">📄 <span class="text-xs font-extrabold text-slate-900 dark:text-white">1 × A4 Grande</span></div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 leading-snug">Cartel completo para entrada, barra libre o photocall.</div>
                        </button>

                        <button type="button"
                                wire:click="selectLayout('2xa5')"
                                class="text-left p-3 rounded-2xl border-2 transition cursor-pointer {{ $layout === '2xa5' ? 'border-amber-500 bg-amber-50/70 dark:bg-amber-950/30 shadow-xs' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700' }}">
                            <div class="text-base mb-0.5">🖼️ <span class="text-xs font-extrabold text-slate-900 dark:text-white">2 × A5 Marcos</span></div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 leading-snug">2 carteles medianos en 1 hoja A4 con línea de corte.</div>
                        </button>

                        <button type="button"
                                wire:click="selectLayout('4xa6')"
                                class="text-left p-3 rounded-2xl border-2 transition cursor-pointer {{ $layout === '4xa6' ? 'border-amber-500 bg-amber-50/70 dark:bg-amber-950/30 shadow-xs' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700' }}">
                            <div class="text-base mb-0.5">🪧 <span class="text-xs font-extrabold text-slate-900 dark:text-white">4 × A6 Mesas</span></div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 leading-snug">4 tarjetas por folio A4 para poner en todas las mesas.</div>
                        </button>
                    </div>
                </div>

                <!-- 2. Estilo y colores -->
                <div>
                    <label class="block text-xs font-extrabold text-slate-900 dark:text-white mb-2">
                        2. Estilo y colores del cartel
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($styles as $stKey => $stItem)
                            <button type="button"
                                    wire:click="selectStyle('{{ $stKey }}')"
                                    class="flex items-center gap-2.5 p-2.5 rounded-2xl border-2 text-left transition cursor-pointer {{ $style === $stKey ? 'border-indigo-500 bg-indigo-50/60 dark:bg-indigo-950/30' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700' }}">
                                <span class="flex items-center -space-x-1 shrink-0">
                                    <span class="w-4 h-4 rounded-full border border-slate-300" style="background-color: {{ $stItem['bg'] }};"></span>
                                    <span class="w-4 h-4 rounded-full border border-white" style="background-color: {{ $stItem['outer_border'] }};"></span>
                                    <span class="w-4 h-4 rounded-full border border-white" style="background-color: {{ $stItem['qr_eye'] }};"></span>
                                </span>
                                <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200 leading-tight">{{ $stItem['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- 3. Nombres e iniciales en el centro del QR -->
                <div class="space-y-2.5">
                    <label class="block text-xs font-extrabold text-slate-900 dark:text-white">
                        3. Vuestros nombres e iniciales dentro del QR
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Nombres en grande</label>
                            <input type="text"
                                   wire:model.live.debounce.250ms="names"
                                   maxlength="60"
                                   placeholder="Ej: Laura & Carlos"
                                   class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800 dark:text-white text-xs py-2.5 px-3 font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Letras centro QR</label>
                            <input type="text"
                                   wire:model.live.debounce.250ms="initials"
                                   maxlength="6"
                                   placeholder="Ej: L&C"
                                   class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800 dark:text-white text-xs py-2.5 px-3 font-extrabold text-center uppercase focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <label class="inline-flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer select-none">
                        <input type="checkbox" wire:model.live="show_initials" class="rounded text-indigo-600">
                        <span>Mostrar el medallón con nuestras iniciales dentro del código QR</span>
                    </label>
                </div>

                <!-- 4. Frase llamativa e instrucciones -->
                <div class="space-y-2.5">
                    <label class="block text-xs font-extrabold text-slate-900 dark:text-white">
                        4. Frase para que los invitados suban todas sus fotos
                    </label>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Ideas listas para usar (o escribid la vuestra debajo)</label>
                        <select wire:model.live="preset"
                                class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800 dark:text-white text-xs py-2.5 px-3 font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @foreach($presets as $pKey => $pData)
                                <option value="{{ $pKey }}">{{ $pData['label'] }}</option>
                            @endforeach
                            <option value="custom">✍️ Frase personalizada escrita por nosotros</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Frase principal en grande</label>
                        <input type="text"
                               wire:model.live.debounce.250ms="headline"
                               maxlength="95"
                               placeholder="Ej: ¡Capturad nuestra boda desde vuestros ojos!"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800 dark:text-white text-xs py-2.5 px-3 font-bold focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Explicación debajo de la frase</label>
                        <textarea wire:model.live.debounce.250ms="subtitle"
                                  rows="2"
                                  maxlength="190"
                                  placeholder="Ej: Foto que hagas hoy, escanea este código QR con tu móvil y súbela al instante."
                                  class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800 dark:text-white text-xs py-2 px-3 leading-relaxed focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Texto superior (opcional)</label>
                            <input type="text"
                                   wire:model.live.debounce.250ms="kicker"
                                   maxlength="48"
                                   placeholder="Ej: NUESTRA BODA · ÁLBUM DE FOTOS"
                                   class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800 dark:text-white text-xs py-2 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Fecha o hashtag al pie</label>
                            <input type="text"
                                   wire:model.live.debounce.250ms="footer"
                                   maxlength="48"
                                   placeholder="Ej: 20 de Noviembre de 2026 · #Boda..."
                                   class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800 dark:text-white text-xs py-2 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <label class="inline-flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer select-none">
                        <input type="checkbox" wire:model.live="show_url" class="rounded text-indigo-600">
                        <span>Mostrar también la dirección web corta al pie del cartel</span>
                    </label>
                </div>

                <!-- Botones de acción principales -->
                <div class="pt-3 border-t border-slate-200 dark:border-slate-800 space-y-2.5">
                    <a href="{{ $downloadPdfUrl }}"
                       wire:click="saveDesign"
                       class="w-full inline-flex items-center justify-center gap-2 py-3.5 px-5 rounded-2xl text-sm font-black bg-gradient-to-r from-amber-500 via-orange-400 to-amber-500 hover:from-amber-600 hover:to-orange-500 text-slate-950 shadow-md transition">
                        <span>📥 Guardar y Descargar PDF Listo para Imprimir</span>
                    </a>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button"
                                wire:click="saveDesign"
                                wire:loading.attr="disabled"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 px-3.5 rounded-xl text-xs font-extrabold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition cursor-pointer">
                            <span>💾 Guardar nuestro diseño</span>
                        </button>

                        <a href="{{ $previewPdfUrl }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="inline-flex items-center justify-center gap-1.5 py-2.5 px-3.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 transition">
                            <span>👁️ Abrir PDF</span>
                        </a>

                        <button type="button"
                                wire:click="resetDefaults"
                                class="inline-flex items-center justify-center gap-1 py-2.5 px-3 rounded-xl text-xs font-bold text-slate-600 hover:text-rose-600 dark:text-slate-300 dark:hover:text-rose-400 bg-slate-100 hover:bg-rose-50 dark:bg-slate-800 dark:hover:bg-slate-700 transition cursor-pointer"
                                title="Recargar nombres y textos originales del evento">
                            <span>🔄 Restaurar</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA: VISTA PREVIA EN VIVO DEL CARTEL -->
            <div class="lg:col-span-6 lg:sticky lg:top-6 space-y-3">
                <div class="flex items-center justify-between gap-2 px-1">
                    <div class="inline-flex items-center gap-2 text-xs font-extrabold text-slate-700 dark:text-slate-300">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>VISTA PREVIA EN TIEMPO REAL</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        @if($has_saved_custom)
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-300/70 dark:border-emerald-800">
                                ✨ Guardado
                            </span>
                        @endif
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-200/80 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                            @if($layout === 'a4')
                                1 Cartel A4
                            @elseif($layout === '2xa5')
                                2 × A5 en 1 A4
                            @else
                                4 × A6 en 1 A4
                            @endif
                        </span>
                    </div>
                </div>

                <!-- Simulación fiel del cartel PDF -->
                <div class="max-w-md mx-auto rounded-2xl shadow-2xl p-4 sm:p-5 transition-colors duration-200"
                     style="background-color: {{ $theme['bg'] }};">
                    <div class="relative rounded-xl p-2 transition-colors duration-200"
                         style="border: 2px solid {{ $theme['outer_border'] }};">
                        <div class="relative rounded-lg px-5 py-6 sm:px-7 sm:py-7 text-center transition-colors duration-200"
                             style="border: 1px solid {{ $theme['inner_border'] }};">

                            <!-- Rombos ornamentales en las 4 esquinas -->
                            <span class="absolute top-1.5 left-2 text-[10px] select-none" style="color: {{ $theme['outer_border'] }};">&#9670;</span>
                            <span class="absolute top-1.5 right-2 text-[10px] select-none" style="color: {{ $theme['outer_border'] }};">&#9670;</span>
                            <span class="absolute bottom-1.5 left-2 text-[10px] select-none" style="color: {{ $theme['outer_border'] }};">&#9670;</span>
                            <span class="absolute bottom-1.5 right-2 text-[10px] select-none" style="color: {{ $theme['outer_border'] }};">&#9670;</span>

                            @if(trim($kicker) !== '')
                                <div class="inline-block px-3.5 py-1 rounded-full text-[10px] font-extrabold tracking-widest uppercase mb-3"
                                     style="background-color: {{ $theme['accent_soft'] }}; color: {{ $theme['accent'] }}; border: 1px solid {{ $theme['inner_border'] }};">
                                    {{ $kicker }}
                                </div>
                            @endif

                            <h2 class="text-2xl sm:text-3xl font-bold leading-tight break-words {{ !empty($theme['serif']) ? 'poster-serif' : '' }}"
                                style="color: {{ $theme['title_color'] }};">
                                {{ trim($names) !== '' ? $names : $event->name }}
                            </h2>

                            <!-- Separador ornamental -->
                            <div class="flex items-center justify-center gap-2.5 my-3">
                                <span class="h-px w-12" style="background-color: {{ $theme['inner_border'] }};"></span>
                                <span class="text-xs" style="color: {{ $theme['outer_border'] }};">&#9670;</span>
                                <span class="h-px w-12" style="background-color: {{ $theme['inner_border'] }};"></span>
                            </div>

                            <h3 class="text-base sm:text-lg font-extrabold leading-snug break-words px-1 {{ !empty($theme['serif']) ? 'poster-serif' : '' }}"
                                style="color: {{ $theme['title_color'] }};">
                                {{ $headline }}
                            </h3>

                            @if(trim($subtitle) !== '')
                                <p class="text-xs leading-relaxed mt-1.5 max-w-xs mx-auto break-words"
                                   style="color: {{ $theme['text_color'] }};">
                                    {{ $subtitle }}
                                </p>
                            @endif

                            <!-- Marco del Código QR con iniciales -->
                            <div class="mt-5 mb-6 relative inline-block">
                                <div class="p-3.5 rounded-2xl bg-white shadow-sm mx-auto"
                                     style="border: 1.5px solid {{ $theme['outer_border'] }};">
                                    <div class="w-44 h-44 sm:w-48 sm:h-48 mx-auto">
                                        {!! $qrSvg !!}
                                    </div>
                                </div>
                                <div class=" -mt-3 relative z-10">
                                    <span class="inline-block px-4 py-1 rounded-full text-[10px] font-black tracking-wider uppercase text-white shadow-xs"
                                          style="background-color: {{ $theme['accent'] }};">
                                        ESCANEA AQUÍ CON TU CÁMARA
                                    </span>
                                </div>
                            </div>

                            <!-- Tira de 3 pasos -->
                            <div class="grid grid-cols-3 gap-1.5 rounded-xl px-2.5 py-2.5 text-center"
                                 style="background-color: {{ $theme['accent_soft'] }}; border: 1px solid {{ $theme['inner_border'] }};">
                                <div>
                                    <div class="text-[10px] font-black uppercase" style="color: {{ $theme['accent'] }};">1. Cámara</div>
                                    <div class="text-[10px] leading-tight mt-0.5" style="color: {{ $theme['text_color'] }};">Abre la cámara de tu móvil</div>
                                </div>
                                <div>
                                    <div class="text-[10px] font-black uppercase" style="color: {{ $theme['accent'] }};">2. Escanea</div>
                                    <div class="text-[10px] leading-tight mt-0.5" style="color: {{ $theme['text_color'] }};">Apunta al código QR</div>
                                </div>
                                <div>
                                    <div class="text-[10px] font-black uppercase" style="color: {{ $theme['accent'] }};">3. Sube fotos</div>
                                    <div class="text-[10px] leading-tight mt-0.5" style="color: {{ $theme['text_color'] }};">Sin instalar ninguna app</div>
                                </div>
                            </div>

                            @if(trim($footer) !== '')
                                <div class="mt-4 text-xs font-bold tracking-wide {{ !empty($theme['serif']) ? 'poster-serif italic' : '' }}"
                                     style="color: {{ $theme['accent'] }};">
                                    {{ $footer }}
                                </div>
                            @endif

                            @if($show_url && $shortDisplayUrl !== '')
                                <div class="mt-1 text-[10px] font-mono break-all"
                                     style="color: {{ $theme['muted_color'] }};">
                                    {{ $shortDisplayUrl }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Explicación visual de cómo sale impreso en el folio A4 -->
                <div class="max-w-md mx-auto rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-3.5 text-xs text-slate-600 dark:text-slate-300 flex items-center gap-3 shadow-xs">
                    @if($layout === 'a4')
                        <div class="w-9 h-12 rounded border-2 border-amber-500 bg-amber-50 dark:bg-amber-950/40 shrink-0 flex items-center justify-center text-[10px] font-black text-amber-800 dark:text-amber-300">
                            A4
                        </div>
                        <div>
                            <strong class="text-slate-900 dark:text-white">Impresión 1 × A4 completo:</strong> El PDF genera 1 cartel grande a página completa listo para enmarcar (21 × 29,7 cm).
                        </div>
                    @elseif($layout === '2xa5')
                        <div class="w-12 h-9 rounded border-2 border-amber-500 bg-amber-50 dark:bg-amber-950/40 shrink-0 grid grid-cols-2 divide-x divide-dashed divide-amber-500 text-[9px] font-black text-amber-800 dark:text-amber-300">
                            <span class="flex items-center justify-center">A5</span>
                            <span class="flex items-center justify-center">A5</span>
                        </div>
                        <div>
                            <strong class="text-slate-900 dark:text-white">Impresión 2 × A5 por folio:</strong> Salen 2 carteles iguales en cada hoja A4 con línea punteada central para cortar por la mitad (14,8 × 21 cm).
                        </div>
                    @else
                        <div class="w-9 h-12 rounded border-2 border-amber-500 bg-amber-50 dark:bg-amber-950/40 shrink-0 grid grid-cols-2 grid-rows-2 text-[8px] font-black text-amber-800 dark:text-amber-300 relative">
                            <span class="flex items-center justify-center border-r border-b border-dashed border-amber-500">A6</span>
                            <span class="flex items-center justify-center border-b border-dashed border-amber-500">A6</span>
                            <span class="flex items-center justify-center border-r border-dashed border-amber-500">A6</span>
                            <span class="flex items-center justify-center">A6</span>
                        </div>
                        <div>
                            <strong class="text-slate-900 dark:text-white">Impresión 4 × A6 para mesas:</strong> Salen 4 tarjetas idénticas en cada folio A4 con guías de corte. Con 5 folios tenéis 20 tarjetas de mesa.
                        </div>
                    @endif
                </div>
            </div>

        </div>
    @endif
</div>
