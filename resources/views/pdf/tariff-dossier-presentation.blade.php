<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modo Presentación (Videollamada) — Dossier de Tarifas {{ $config['tariff_year'] ?? '2027' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #050811; color: #f8fafc; }
        [x-cloak] { display: none !important; }
    </style>
</head>
@php
    $year = trim((string) ($config['tariff_year'] ?? '2027')) ?: '2027';
    $pricesIncludeVat = !empty($config['prices_include_vat']);
    $vatRate = (float) ($config['vat_rate'] ?? 21);
    $validPhotos = \App\Services\GeneralTariffDossierService::getValidGalleryPhotos($config);
    $coverIdx = (int) ($config['cover_photo_index'] ?? 0);
    $coverPhotoUrl = !empty($config['cover_custom_photo'])
        ? asset('storage/' . ltrim($config['cover_custom_photo'], '/'))
        : ($validPhotos[$coverIdx]['public_url'] ?? ($validPhotos[0]['public_url'] ?? null));
    $aboutPhotoUrl = !empty($config['about_photo'])
        ? asset('storage/' . ltrim($config['about_photo'], '/'))
        : ($validPhotos[0]['public_url'] ?? null);
    $brandKey = $config['brand'] ?? 'nunez_and_son';
    $contactEmail = $brandKey === 'javnx'
        ? ($config['contact_email_javnx'] ?? 'info@javnxdj.com')
        : ($config['contact_email_nunez'] ?? 'info@nunezandson.com');
    $contactWeb = $brandKey === 'javnx'
        ? ($config['contact_website_javnx'] ?? 'www.javnxdj.com')
        : ($config['contact_website_nunez'] ?? 'www.nunezandson.com');
    $totalPages = $plan['total_pages'];
@endphp
<body
    x-data="{
        current: 1,
        total: {{ $totalPages }},
        next() { if (this.current < this.total) this.current++; },
        prev() { if (this.current > 1) this.current--; },
        toggleFull() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
            } else {
                document.exitFullscreen().catch(() => {});
            }
        }
    }"
    @keydown.window.arrow-right="next()"
    @keydown.window.arrow-left="prev()"
    class="min-h-screen flex flex-col justify-between select-none"
>
    <!-- BARRA SUPERIOR DE CONTROL PARA VIDEOLLAMADA -->
    <header class="bg-slate-950/90 border-b border-amber-500/30 px-4 sm:px-8 py-3 flex flex-wrap items-center justify-between gap-3 sticky top-0 z-50 backdrop-blur-md">
        <div class="flex items-center gap-3">
            <span class="px-2.5 py-1 rounded-md bg-amber-500/20 border border-amber-400/50 text-amber-300 text-xs font-extrabold uppercase tracking-wider">
                🖥️ Modo Videollamada &bull; Tarifas {{ $year }}
            </span>
            <span class="text-sm font-extrabold text-white">{{ $brandInfo['name'] }}</span>
        </div>

        <!-- Selector rápido de diapositivas -->
        <div class="flex items-center gap-1.5 flex-wrap">
            @foreach($plan['pages'] as $p)
                <button
                    type="button"
                    @click="current = {{ $p['page_number'] }}"
                    :class="current === {{ $p['page_number'] }} ? 'bg-amber-500 text-slate-950 font-black border-amber-400' : 'bg-slate-900 text-slate-300 border-slate-700 hover:bg-slate-800'"
                    class="px-2.5 py-1 rounded-lg border text-xs transition cursor-pointer"
                >
                    {{ $p['page_number'] }}. {{ preg_replace('/^\d+\.\s*/', '', $p['title']) }}
                </button>
            @endforeach
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="toggleFull()" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-600 text-xs font-bold text-white cursor-pointer">
                ⛶ Pantalla Completa
            </button>
            <a href="{{ route('admin.tariff-dossier.pdf', ['brand' => $brandKey]) }}" class="px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black">
                ⬇ Descargar PDF
            </a>
            <a href="{{ route('admin.tariff-dossier') }}" class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-700 text-xs font-bold text-slate-300">
                ✕ Volver al Editor
            </a>
        </div>
    </header>

    <!-- ÁREA DE DIAPOSITIVAS -->
    <main class="flex-1 flex items-center justify-center p-4 sm:p-8">
        <div class="w-full max-w-6xl bg-slate-900 border-2 border-amber-500/40 rounded-3xl shadow-2xl overflow-hidden min-h-[76vh] flex flex-col justify-between">
            @foreach($plan['pages'] as $page)
                <div x-show="current === {{ $page['page_number'] }}" x-cloak class="flex-1 flex flex-col justify-between p-6 sm:p-12">
                    @if($page['type'] === 'cover')
                        <div class="space-y-6">
                            <div class="flex items-center justify-between border-b border-amber-500/30 pb-4">
                                <div>
                                    <h2 class="text-2xl sm:text-3xl font-black text-white">{{ $brandInfo['name'] }}</h2>
                                    <p class="text-xs uppercase tracking-widest text-amber-400 font-bold">{{ $brandInfo['subtitle'] }}</p>
                                </div>
                                <span class="px-4 py-1.5 rounded-lg bg-amber-500/20 border border-amber-400 text-amber-300 font-black text-sm uppercase tracking-widest">
                                    Tarifas {{ $year }}
                                </span>
                            </div>

                            @if($coverPhotoUrl)
                                <div class="w-full h-72 sm:h-96 rounded-2xl overflow-hidden border-2 border-amber-500/50 bg-slate-950">
                                    <img src="{{ $coverPhotoUrl }}" class="w-full h-full object-cover">
                                </div>
                            @endif

                            <div class="border-l-4 border-amber-400 pl-5 py-1">
                                <h1 class="text-2xl sm:text-4xl font-black text-white leading-tight">
                                    {{ $config['cover_phrase'] }}
                                </h1>
                                @if(!empty($config['cover_subtitle']))
                                    <p class="text-base text-slate-300 mt-2">{{ $config['cover_subtitle'] }}</p>
                                @endif
                            </div>
                        </div>

                    @elseif($page['type'] === 'about')
                        <div class="space-y-6">
                            <div class="border-b border-amber-500/30 pb-3">
                                <span class="text-xs font-extrabold uppercase tracking-widest text-amber-400">Página {{ $page['page_number'] }} &bull; {{ $brandInfo['name'] }}</span>
                                <h2 class="text-2xl sm:text-3xl font-black text-white">Quiénes somos</h2>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                                @if($aboutPhotoUrl)
                                    <div class="lg:col-span-5 rounded-2xl overflow-hidden border-2 border-amber-500/40 h-72 bg-slate-950">
                                        <img src="{{ $aboutPhotoUrl }}" class="w-full h-full object-cover">
                                    </div>
                                @endif
                                <div class="{{ $aboutPhotoUrl ? 'lg:col-span-7' : 'lg:col-span-12' }} space-y-4">
                                    <div class="bg-slate-950/80 border border-slate-800 rounded-2xl p-5 text-sm sm:text-base text-slate-200 leading-relaxed whitespace-pre-line">{{ $config['about_text'] }}</div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                        @foreach(($config['about_facts'] ?? []) as $fact)
                                            @if(trim((string) $fact) !== '')
                                                <div class="p-3 rounded-xl bg-slate-800/70 border border-slate-700 text-xs sm:text-sm text-slate-100 font-semibold">
                                                    <span class="text-amber-400 font-black">✓</span> {{ $fact }}
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            @if(!empty($config['about_venues']))
                                <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30">
                                    <div class="text-xs font-black uppercase tracking-wider text-amber-400 mb-1">{{ $config['about_venues_title'] }}</div>
                                    <p class="text-xs sm:text-sm text-slate-200 leading-relaxed">{{ $config['about_venues'] }}</p>
                                </div>
                            @endif
                        </div>

                    @elseif($page['type'] === 'gallery')
                        <div class="space-y-5">
                            <div class="border-b border-amber-500/30 pb-3 flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-extrabold uppercase tracking-widest text-amber-400">Bodas Reales Verificadas</span>
                                    <h2 class="text-2xl sm:text-3xl font-black text-white">{{ $page['title'] }}</h2>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                                @foreach(($page['photos'] ?? []) as $photo)
                                    <div class="rounded-2xl overflow-hidden border border-slate-700 bg-slate-950 flex flex-col justify-between">
                                        <img src="{{ $photo['public_url'] }}" class="w-full h-56 object-cover">
                                        <div class="p-3.5">
                                            <div class="text-sm font-black text-amber-400">{{ $photo['wedding_name'] }}</div>
                                            <div class="text-xs text-slate-400 mt-0.5">
                                                {{ \App\Services\GeneralTariffDossierService::formatSpanishDate($photo['wedding_date']) }}
                                                @if(!empty($photo['caption'])) &bull; {{ $photo['caption'] }} @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                    @elseif($page['type'] === 'how_we_work')
                        <div class="space-y-5">
                            <div class="border-b border-amber-500/30 pb-3">
                                <span class="text-xs font-extrabold uppercase tracking-widest text-amber-400">Proceso Paso a Paso</span>
                                <h2 class="text-2xl sm:text-3xl font-black text-white">Cómo trabajamos</h2>
                            </div>

                            <div class="space-y-3">
                                @foreach(($config['work_steps'] ?? []) as $idx => $step)
                                    @if(!empty(trim((string) ($step['title'] ?? ''))))
                                        <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                            <div class="flex items-start gap-3.5">
                                                <span class="w-8 h-8 rounded-full bg-amber-500 text-slate-950 font-black text-sm flex items-center justify-center shrink-0">
                                                    {{ $idx + 1 }}
                                                </span>
                                                <div>
                                                    <div class="text-base font-extrabold text-white">{{ $step['title'] }}</div>
                                                    @if(!empty($step['description']))
                                                        <div class="text-xs sm:text-sm text-slate-400 mt-0.5">{{ $step['description'] }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                            <span class="px-3 py-1 rounded-lg bg-amber-500/20 border border-amber-400/50 text-amber-300 text-xs font-black shrink-0">
                                                ⏰ Plazo: {{ $step['timeframe'] ?: 'Pendiente de rellenar' }}
                                            </span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                    @elseif($page['type'] === 'packs')
                        <div class="space-y-5">
                            <div class="border-b border-amber-500/30 pb-3 flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-extrabold uppercase tracking-widest text-amber-400">Página Central &bull; Precios Finales Cerrados</span>
                                    <h2 class="text-2xl sm:text-3xl font-black text-white">Packs y precio (IVA incluido)</h2>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-stretch">
                                @foreach(($config['packs'] ?? []) as $pIdx => $pack)
                                    @php
                                        $isFeatured = ($pIdx === 1);
                                        $finalPrice = \App\Services\GeneralTariffDossierService::computeFinalPriceWithVat($pack['price_input'] ?? 0, $pricesIncludeVat, $vatRate);
                                    @endphp
                                    <div class="rounded-2xl p-6 flex flex-col justify-between {{ $isFeatured ? 'bg-slate-950 border-2 border-amber-400 ring-2 ring-amber-400/20 lg:scale-105' : 'bg-slate-950/60 border border-slate-800' }}">
                                        <div>
                                            <div class="flex items-center justify-between mb-3">
                                                <span class="text-xs font-bold uppercase tracking-wider {{ $isFeatured ? 'bg-amber-400 text-slate-950 px-2.5 py-0.5 rounded-md font-black' : 'text-slate-400' }}">
                                                    {{ $isFeatured ? ('★ ' . ($pack['badge'] ?: 'El más elegido')) : (($pack['hours'] ?? 4) . ' h de fiesta') }}
                                                </span>
                                            </div>
                                            <h3 class="text-xl font-black text-white">{{ $pack['name'] }}</h3>
                                            <p class="text-xs text-slate-300 mt-1 mb-4">{{ $pack['subtitle'] }}</p>

                                            <div class="p-4 rounded-xl bg-slate-900 border {{ $isFeatured ? 'border-amber-500/50' : 'border-slate-800' }} text-center mb-4">
                                                <div class="text-3xl font-black {{ $isFeatured ? 'text-amber-400' : 'text-white' }}">
                                                    {{ number_format($finalPrice, 0, ',', '.') }} €
                                                </div>
                                                <div class="text-xs font-bold text-emerald-400 mt-1">
                                                    IVA incluido &bull; {{ $pack['hours'] ?? 4 }} horas de fiesta
                                                </div>
                                            </div>

                                            <ul class="space-y-2 text-xs sm:text-sm text-slate-200">
                                                @foreach(($pack['includes'] ?? []) as $inc)
                                                    @if(trim((string) $inc) !== '')
                                                        <li class="flex items-start gap-2">
                                                            <span class="text-amber-400 font-black">✓</span>
                                                            <span>{{ $inc }}</span>
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                    @elseif($page['type'] === 'extras' || $page['type'] === 'extras_cont')
                        <div class="space-y-5">
                            <div class="border-b border-amber-500/30 pb-3">
                                <span class="text-xs font-extrabold uppercase tracking-widest text-amber-400">Opciones con Precio Cerrado</span>
                                <h2 class="text-2xl sm:text-3xl font-black text-white">Extras</h2>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach(($page['extras'] ?? []) as $extra)
                                    <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 flex items-center justify-between gap-4">
                                        <div>
                                            <div class="text-base font-extrabold text-white">{{ $extra['name'] }}</div>
                                            @if(!empty($extra['description']))
                                                <div class="text-xs text-slate-400 mt-1">{{ $extra['description'] }}</div>
                                            @endif
                                        </div>
                                        <div class="text-right shrink-0">
                                            <div class="text-xl font-black text-amber-400">{{ number_format((float) $extra['final_price'], 0, ',', '.') }} €</div>
                                            <span class="text-[10px] font-bold text-emerald-400 uppercase">IVA incluido</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                    @elseif($page['type'] === 'reviews')
                        <div class="space-y-5">
                            <div class="border-b border-amber-500/30 pb-3">
                                <span class="text-xs font-extrabold uppercase tracking-widest text-amber-400">Parejas Reales</span>
                                <h2 class="text-2xl sm:text-3xl font-black text-white">Opiniones</h2>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach(($page['reviews'] ?? []) as $rev)
                                    <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-2">
                                        <p class="text-sm text-slate-200 italic">«{{ $rev['text'] }}»</p>
                                        <div class="text-xs font-bold text-amber-400">
                                            {{ $rev['couple_name'] }}
                                            @if(!empty($rev['location'])) &bull; {{ $rev['location'] }} @endif
                                            &bull; {{ \App\Services\GeneralTariffDossierService::formatSpanishDate($rev['event_date']) }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if(!empty($config['google_reviews_url']))
                                <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-center">
                                    <span class="text-xs font-bold text-amber-300 uppercase block mb-1">Reseñas en Google</span>
                                    <a href="{{ $config['google_reviews_url'] }}" target="_blank" class="text-sm font-black text-white underline">{{ $config['google_reviews_url'] }}</a>
                                </div>
                            @endif
                        </div>

                    @elseif($page['type'] === 'how_to_book')
                        @php
                            $depPct = (float) ($config['deposit_percentage'] ?? 40);
                            $restPct = max(0, 100 - $depPct);
                        @endphp
                        <div class="space-y-5">
                            <div class="border-b border-amber-500/30 pb-3">
                                <span class="text-xs font-extrabold uppercase tracking-widest text-amber-400">Reserva y Contacto</span>
                                <h2 class="text-2xl sm:text-3xl font-black text-white">Cómo reservar</h2>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="p-5 rounded-2xl bg-slate-950 border-2 border-amber-400 text-center">
                                    <div class="text-3xl font-black text-amber-400">{{ $depPct }} %</div>
                                    <div class="text-sm font-bold text-white mt-1">Señal para reservar la fecha</div>
                                    <div class="text-xs text-slate-400">A la firma del contrato</div>
                                </div>
                                <div class="p-5 rounded-2xl bg-slate-950 border border-slate-800 text-center">
                                    <div class="text-3xl font-black text-white">{{ $restPct }} %</div>
                                    <div class="text-sm font-bold text-slate-200 mt-1">Resto del importe</div>
                                    <div class="text-xs text-slate-400">El día del evento o antes</div>
                                </div>
                            </div>

                            <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-2 text-sm text-slate-200">
                                <div><strong class="text-amber-400">Formas de pago:</strong> {{ $config['payment_methods_text'] }}</div>
                                <div><strong class="text-amber-400">Qué pasa después de reservar:</strong> {{ $config['after_booking_text'] }}</div>
                                <div><strong class="text-amber-400">Validez de las tarifas:</strong> Tarifas válidas hasta el {{ \App\Services\GeneralTariffDossierService::formatSpanishDate($config['valid_until'] ?? '', true) }}</div>
                            </div>

                            <div class="p-5 rounded-2xl bg-slate-950 border border-amber-500/40 flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div class="space-y-1 text-sm">
                                    <div><strong class="text-amber-400">Fran:</strong> {{ $config['phone_fran'] }}</div>
                                    <div><strong class="text-amber-400">Miguel:</strong> {{ $config['phone_miguel'] }}</div>
                                </div>
                                <div class="space-y-1 text-sm sm:text-right">
                                    <div><strong class="text-amber-400">Email:</strong> {{ $contactEmail }}</div>
                                    <div><strong class="text-amber-400">Web:</strong> {{ $contactWeb }}</div>
                                </div>
                            </div>

                            <div class="text-center text-lg font-extrabold text-amber-300 pt-2">
                                «{{ $config['closing_phrase'] }}»
                            </div>
                        </div>
                    @endif

                    <!-- Pie de diapositiva con botones Anterior / Siguiente -->
                    <div class="pt-6 mt-6 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                        <button
                            type="button"
                            @click="prev()"
                            :disabled="current === 1"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 disabled:opacity-30 text-white font-bold cursor-pointer"
                        >
                            ← Página anterior
                        </button>
                        <span>Página <strong x-text="current" class="text-white"></strong> de {{ $totalPages }}</span>
                        <button
                            type="button"
                            @click="next()"
                            :disabled="current === total"
                            class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 disabled:opacity-30 text-slate-950 font-black cursor-pointer"
                        >
                            Página siguiente →
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </main>
</body>
</html>
