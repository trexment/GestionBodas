@php
    $types = \App\Livewire\Guest\QuoteCalculator::getEventTypes();
    $currentType = $types[$event_type] ?? $types['boda'];

    $serviceGroups = [
        [
            'title' => '1. Sonorización, Fases del Evento y Producción DJ',
            'subtitle' => 'Seleccionad las áreas o tramos del evento que deseáis cubrir',
            'keys' => ['ceremony', 'cocktail', 'restaurant', 'dj'],
        ],
        [
            'title' => '2. Efectos Especiales, Fotos QR y Ampliación de Horario',
            'subtitle' => 'Efectos escénicos de pista, álbum colaborativo QR, karaoke y horas adicionales',
            'keys' => ['smoke_machine', 'cold_sparks', 'photo_qr', 'karaoke', 'extra_hours'],
        ],
        [
            'title' => '3. Fotomatón, Plataforma 360º y Estaciones Dulces',
            'subtitle' => 'Servicios complementarios para invitados',
            'keys' => ['pack_duo_360', 'photobooth', 'video_360', 'chocolate_fountain', 'candy_bar', 'waffle_cart'],
        ],
    ];

    $selectedCount = collect($services)->where('selected', true)->count() + ($custom_service_selected ? 1 : 0);
@endphp

<div class="min-h-screen bg-zinc-950 py-6 sm:py-10 px-3 sm:px-6 lg:px-8 text-zinc-100 antialiased">
    <div class="max-w-6xl mx-auto">
        
        <!-- Cabecera Ejecutiva -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-zinc-900 border border-zinc-800 text-amber-400 text-[11px] font-semibold uppercase tracking-[0.18em] mb-3">
                Solicitud de Propuesta &bull; {{ $currentType['badge'] }}
            </div>
            <h1 class="text-2xl sm:text-4xl font-bold tracking-tight text-white mb-2">
                {{ $currentType['hero_title'] }} <span class="text-amber-400">{{ $currentType['hero_highlight'] }}</span>
            </h1>
            <p class="text-xs sm:text-sm text-zinc-400 max-w-2xl mx-auto leading-relaxed">
                {{ $currentType['hero_subtitle'] }}
            </p>
        </div>

        @if(!$is_submitted)
            <!-- SELECTOR DE TIPO DE EVENTO -->
            <div class="mb-7">
                <div class="flex flex-wrap items-center justify-center gap-2 max-w-4xl mx-auto">
                    @foreach($types as $key => $type)
                        <button type="button" 
                                wire:click="setEventType('{{ $key }}')"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold transition-all cursor-pointer {{ $event_type === $key ? 'bg-amber-500 text-zinc-950 shadow-sm border border-amber-400' : 'bg-zinc-900 hover:bg-zinc-800 text-zinc-300 border border-zinc-800 hover:text-white' }}">
                            <span>{{ $type['name'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        @if($is_submitted)
            <!-- Tarjeta de Confirmación Sobria -->
            <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-8 sm:p-10 text-center shadow-xl max-w-2xl mx-auto">
                <div class="w-12 h-12 bg-amber-500/15 text-amber-400 border border-amber-500/30 rounded-xl flex items-center justify-center mx-auto mb-5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-zinc-800 text-zinc-300 text-[11px] font-semibold uppercase tracking-wider mb-3 border border-zinc-700">
                    <span>{{ $currentType['name'] }}</span>
                </div>
                <h2 class="text-2xl font-bold text-white mb-2">Solicitud Registrada Correctamente</h2>
                <p class="text-zinc-300 text-sm mb-6 leading-relaxed">
                    Muchas gracias, <strong class="text-white">{{ $client_name }}</strong>. Hemos registrado vuestra solicitud para el <strong class="text-amber-400">{{ \Carbon\Carbon::parse($event_date)->format('d/m/Y') }}</strong> en <strong class="text-white">{{ $event_location }}</strong>.
                </p>
                <div class="bg-zinc-950 rounded-xl p-5 border border-zinc-800 text-left mb-6 space-y-2.5">
                    <p class="text-[11px] uppercase font-bold tracking-widest text-zinc-400 pb-2 border-b border-zinc-800">Resumen de servicios solicitados</p>
                    @foreach($services as $key => $serv)
                        @if($serv['selected'])
                            <div class="flex items-center justify-between gap-2 flex-wrap text-xs sm:text-sm text-zinc-200 py-0.5">
                                <span class="font-medium">{{ $serv['name'] }}</span>
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    @if($key === 'dj')
                                        <span class="text-[11px] bg-zinc-800 text-zinc-300 px-2 py-0.5 rounded border border-zinc-700">{{ $this->dj_pack_info['badge'] }}</span>
                                    @endif
                                    @if($key === 'smoke_machine' && !empty($serv['included']))
                                        <span class="text-[10px] font-semibold bg-zinc-800 text-amber-400 border border-amber-500/30 px-2 py-0.5 rounded">Incluido en {{ $this->dj_pack_info['base_name'] ?? 'Pack' }}</span>
                                    @endif
                                    @if($key === 'cold_sparks')
                                        <span class="text-[11px] bg-zinc-800 text-zinc-300 px-2 py-0.5 rounded">{{ $serv['quantity'] }} {{ $serv['quantity'] == 1 ? 'máquina' : 'máquinas' }}</span>
                                        @if(!empty($serv['included']))
                                            <span class="text-[10px] font-semibold bg-zinc-800 text-amber-400 border border-amber-500/30 px-2 py-0.5 rounded">2 máq. en {{ $this->dj_pack_info['base_name'] ?? 'Pack' }}</span>
                                        @endif
                                    @endif
                                    @if(($key === 'cocktail' || $key === 'restaurant') && $this->has_cocktail_restaurant_pack && $this->cocktail_restaurant_pack_info['enabled'])
                                        <span class="text-[10px] font-semibold bg-zinc-800 text-amber-400 border border-amber-500/30 px-2 py-0.5 rounded">Pack Cóctel + Banquete</span>
                                    @endif
                                    @if($key === 'extra_hours')
                                        <span class="text-[11px] bg-zinc-800 text-amber-400 px-2 py-0.5 rounded font-semibold">+{{ $serv['quantity'] ?? 1 }} {{ ($serv['quantity'] ?? 1) == 1 ? 'hora extra' : 'horas extra' }}</span>
                                    @endif
                                    @if(in_array($key, ['pack_duo_360', 'photobooth', 'video_360']))
                                        <span class="text-[11px] bg-zinc-800 text-zinc-300 px-2 py-0.5 rounded">{{ $serv['hours'] ?? 2 }} Horas</span>
                                    @endif
                                    @if($key === 'chocolate_fountain')
                                        <span class="text-[11px] bg-zinc-800 text-zinc-300 px-2 py-0.5 rounded">
                                            {{ ($serv['fountains'] ?? 1) == 2 ? '2 Fuentes' : '1 Fuente' }} &bull; {{ $serv['hours'] ?? 2 }} Horas
                                        </span>
                                        @if(!empty($serv['waffle_corner']))
                                            <span class="text-[10px] bg-zinc-800 text-amber-400 px-2 py-0.5 rounded">+ Rincón de Gofres</span>
                                        @endif
                                    @endif
                                    @if($key === 'waffle_cart')
                                        <span class="text-[11px] bg-zinc-800 text-zinc-300 px-2 py-0.5 rounded">
                                            {{ $serv['hours'] ?? 2 }} Horas
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                    @if($custom_service_selected && !empty($custom_service_name))
                        <div class="flex items-center justify-between gap-2 text-xs sm:text-sm text-zinc-200 py-0.5">
                            <span class="font-medium">{{ $custom_service_name }}</span>
                            <span class="text-[10px] font-semibold bg-zinc-800 text-amber-400 border border-amber-500/30 px-2 py-0.5 rounded">A valorar</span>
                        </div>
                    @endif
                    @if(!empty($event_schedule))
                        <div class="pt-2 border-t border-zinc-800 text-xs text-zinc-300">
                            <strong class="text-zinc-400">Horario indicado:</strong> {{ $event_schedule }}
                        </div>
                    @endif
                    @if($needs_prior_setup)
                        <div class="text-xs text-zinc-300">
                            <strong class="text-zinc-400">Montaje técnico:</strong> Requiere montaje previo / ensayo / varias jornadas
                        </div>
                    @endif
                    @if(!empty($client_notes))
                        <div class="pt-2 mt-2 border-t border-zinc-800 text-xs text-zinc-300">
                            <strong class="text-zinc-400 block mb-0.5">Observaciones indicadas:</strong>
                            <p class="whitespace-pre-line">{{ $client_notes }}</p>
                        </div>
                    @endif
                </div>
                <p class="text-xs text-zinc-400 mb-6">
                    Nuestro equipo revisará la disponibilidad de agenda y os remitirá a la mayor brevedad el dossier y presupuesto formal.
                </p>
                <a href="/" class="inline-flex items-center gap-2 px-6 py-3 bg-amber-500 hover:bg-amber-400 text-zinc-950 font-semibold rounded-xl text-xs sm:text-sm transition">
                    Volver al inicio
                </a>
            </div>
        @else
            <!-- Grid Principal -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                <!-- Columna Izquierda: Configuración de Servicios (7 cols) -->
                <div class="lg:col-span-7 space-y-4">
                    <div class="flex items-center justify-between px-1">
                        <h2 class="text-sm sm:text-base font-bold text-white uppercase tracking-wider">
                            Configuración de Servicios
                        </h2>
                        @if($selectedCount > 0)
                            <span class="text-[11px] font-semibold bg-zinc-900 text-amber-400 border border-zinc-800 px-2.5 py-0.5 rounded-md">
                                {{ $selectedCount }} {{ $selectedCount === 1 ? 'servicio seleccionado' : 'servicios seleccionados' }}
                            </span>
                        @else
                            <span class="text-[11px] text-zinc-500 hidden sm:inline">Seleccionad las opciones requeridas</span>
                        @endif
                    </div>

                    @if($this->has_cocktail_restaurant_pack && $this->cocktail_restaurant_pack_info['enabled'])
                        <div class="p-3.5 bg-zinc-900 border border-amber-500/40 rounded-xl flex items-center justify-between gap-3">
                            <div>
                                <span class="text-xs font-bold text-amber-400 uppercase tracking-wider block">Bonificación Pack Cóctel + Banquete Aplicada</span>
                                <span class="text-[11px] text-zinc-400">Tarifa conjunta reducida por contratar ambas fases de sonorización.</span>
                            </div>
                            <span class="shrink-0 text-xs font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30 px-2.5 py-1 rounded-md">
                                -{{ round($this->cocktail_restaurant_pack_info['discount_percentage']) }}%
                            </span>
                        </div>
                    @endif

                    @foreach($serviceGroups as $group)
                        <div class="bg-zinc-900/70 border border-zinc-800/90 rounded-2xl p-4 space-y-3">
                            <div class="flex items-center justify-between border-b border-zinc-800 pb-2">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-200">{{ $group['title'] }}</h3>
                                <span class="text-[11px] text-zinc-500 hidden sm:inline">{{ $group['subtitle'] }}</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                @foreach($group['keys'] as $key)
                                    @if(isset($services[$key]))
                                        @php
                                            $service = $services[$key];
                                            $hasSubControls = in_array($key, ['dj', 'cold_sparks', 'extra_hours', 'pack_duo_360', 'photobooth', 'video_360', 'chocolate_fountain', 'waffle_cart'], true);
                                            $spanFull = $service['selected'] && $hasSubControls;
                                        @endphp
                                        <div wire:key="service-{{ $key }}"
                                             wire:click="toggleService('{{ $key }}')"
                                             class="{{ $spanFull ? 'sm:col-span-2' : '' }} relative p-3.5 rounded-xl border transition-all cursor-pointer select-none {{ $service['selected'] ? 'bg-zinc-900 border-amber-500/70 ring-1 ring-amber-500/30' : 'bg-zinc-950/60 border-zinc-800 hover:border-zinc-700 hover:bg-zinc-900/50' }}">
                                            
                                            <div class="flex items-start gap-3">
                                                <!-- Indicador Tipográfico Sobrio -->
                                                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-[11px] font-bold tracking-tighter shrink-0 border {{ $service['selected'] ? 'bg-amber-500 text-zinc-950 border-amber-400' : 'bg-zinc-900 text-zinc-400 border-zinc-800' }}">
                                                    {{ $service['icon'] }}
                                                </div>

                                                <!-- Detalles -->
                                                <div class="flex-1 min-w-0 pr-6">
                                                    <div class="flex items-center gap-1.5 flex-wrap">
                                                        <h4 class="font-semibold text-xs sm:text-sm leading-tight {{ $service['selected'] ? 'text-white' : 'text-zinc-200' }}">
                                                            {{ $service['name'] }}
                                                        </h4>
                                                        @if($service['selected'])
                                                            @if($key === 'smoke_machine' && !empty($service['included']))
                                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-semibold bg-zinc-800 text-amber-400 border border-amber-500/30">
                                                                    INCLUIDO EN PACK
                                                                </span>
                                                            @elseif($key === 'photo_qr' && !empty($service['included']))
                                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-semibold bg-emerald-950/80 text-emerald-400 border border-emerald-500/40">
                                                                    REGALO INCLUIDO (0 €)
                                                                </span>
                                                            @elseif($key === 'cold_sparks' && !empty($service['included']))
                                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-semibold bg-zinc-800 text-amber-400 border border-amber-500/30">
                                                                    2 MÁQ. EN PACK
                                                                </span>
                                                            @elseif(($key === 'cocktail' || $key === 'restaurant') && $this->has_cocktail_restaurant_pack && $this->cocktail_restaurant_pack_info['enabled'])
                                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-semibold bg-zinc-800 text-amber-400 border border-amber-500/30">
                                                                    TARIFA PACK
                                                                </span>
                                                            @elseif($key === 'extra_hours')
                                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-semibold bg-zinc-800 text-amber-400 border border-amber-500/30">
                                                                    +{{ $service['quantity'] ?? 1 }}H EXTRA
                                                                </span>
                                                            @elseif(in_array($key, ['pack_duo_360', 'photobooth', 'video_360']))
                                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-semibold bg-zinc-800 text-zinc-300 border border-zinc-700">
                                                                    {{ $service['hours'] ?? 2 }}H
                                                                </span>
                                                            @elseif($key === 'chocolate_fountain')
                                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-semibold bg-zinc-800 text-zinc-300 border border-zinc-700">
                                                                    {{ ($service['fountains'] ?? 1) == 2 ? '2 FUENTES' : '1 FUENTE' }} &bull; {{ $service['hours'] ?? 2 }}H
                                                                </span>
                                                            @elseif($key === 'waffle_cart')
                                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-semibold bg-zinc-800 text-zinc-300 border border-zinc-700">
                                                                    {{ $service['hours'] ?? 2 }}H
                                                                </span>
                                                            @endif
                                                        @else
                                                            @if($key === 'smoke_machine')
                                                                <span class="text-[10px] text-zinc-400 font-medium">Incluido desde 5h DJ</span>
                                                            @elseif($key === 'photo_qr' && \App\Models\Setting::get('photo_qr_free_with_ceremony_cocktail', '1') !== '0')
                                                                <span class="text-[10px] text-emerald-400 font-medium">Gratis con Ceremonia/Cóctel</span>
                                                            @elseif($key === 'cold_sparks')
                                                                <span class="text-[10px] text-zinc-400 font-medium">2 incl. en Pack Deluxe</span>
                                                            @elseif($key === 'pack_duo_360')
                                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-semibold bg-zinc-800 text-amber-400 border border-zinc-700">PACK CONJUNTO</span>
                                                            @elseif(($key === 'photobooth' || $key === 'video_360') && !empty($services['pack_duo_360']['selected']))
                                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-semibold bg-zinc-800 text-amber-400 border border-amber-500/30">EN PACK DÚO</span>
                                                            @endif
                                                        @endif
                                                    </div>
                                                    <p class="text-[11px] text-zinc-400 mt-1 leading-snug {{ $service['selected'] ? '' : 'line-clamp-2' }}">
                                                        {{ $service['description'] }}
                                                    </p>

                                                    @if($key === 'cocktail' && $service['selected'] && empty($services['restaurant']['selected']) && $this->cocktail_restaurant_pack_info['enabled'])
                                                        <div class="mt-1.5 text-[10px] text-amber-400/90 font-medium">
                                                            Nota: Al combinar Cóctel + Banquete se aplica tarifa reducida de Pack.
                                                        </div>
                                                    @elseif($key === 'restaurant' && $service['selected'] && empty($services['cocktail']['selected']) && $this->cocktail_restaurant_pack_info['enabled'])
                                                        <div class="mt-1.5 text-[10px] text-amber-400/90 font-medium">
                                                            Nota: Al combinar Banquete + Cóctel se aplica tarifa reducida de Pack.
                                                        </div>
                                                    @endif
                                                </div>

                                                <!-- Checkmark -->
                                                <div class="absolute top-3.5 right-3.5 w-5 h-5 rounded-md flex items-center justify-center border transition-colors {{ $service['selected'] ? 'bg-amber-500 border-amber-400 text-zinc-950' : 'border-zinc-700 bg-zinc-900 text-transparent' }}">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </div>
                                            </div>

                                            <!-- SUB-CONTROLES AL SELECCIONAR -->
                                            @if($service['selected'])
                                                @if($key === 'dj')
                                                    <div class="mt-3 pt-3 border-t border-zinc-800 space-y-2.5" wire:click.stop>
                                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                                            <span class="text-xs font-medium text-zinc-300">Duración estimada de sesión DJ / barra libre:</span>
                                                            <div class="inline-flex items-center bg-zinc-950 rounded-lg p-1 border border-zinc-800">
                                                                <button type="button" wire:click="setDjHours({{ max(2, $service['quantity'] - 1) }})" class="w-7 h-7 flex items-center justify-center rounded bg-zinc-900 hover:bg-zinc-800 text-white font-bold text-sm transition cursor-pointer">-</button>
                                                                <span class="px-3 text-xs font-bold text-amber-400">{{ $service['quantity'] }} Horas</span>
                                                                <button type="button" wire:click="setDjHours({{ min(9, $service['quantity'] + 1) }})" class="w-7 h-7 flex items-center justify-center rounded bg-zinc-900 hover:bg-zinc-800 text-white font-bold text-sm transition cursor-pointer">+</button>
                                                            </div>
                                                        </div>
                                                        <div class="p-3 bg-zinc-950 rounded-lg border border-zinc-800 text-xs text-zinc-300">
                                                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                                                <strong class="text-white font-semibold">{{ $this->dj_pack_info['name'] }}</strong>
                                                                @if(!empty($this->dj_pack_info['effects_badge']))
                                                                    <span class="text-[10px] font-medium text-amber-400 bg-zinc-900 border border-zinc-800 px-2 py-0.5 rounded">Incluye: {{ $this->dj_pack_info['effects_badge'] }}</span>
                                                                @endif
                                                            </div>
                                                            <p class="text-[11px] text-zinc-400 mt-1 leading-snug">{{ $this->dj_pack_info['description'] }}</p>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if($key === 'cold_sparks')
                                                    <div class="mt-3 pt-3 border-t border-zinc-800 flex flex-wrap items-center justify-between gap-2" wire:click.stop>
                                                        <div>
                                                            <span class="text-xs font-medium text-zinc-300">Número de unidades de fuego frío:</span>
                                                            @if(!empty($service['included']))
                                                                <span class="block text-[10px] text-amber-400">2 unidades incluidas en la configuración seleccionada</span>
                                                            @endif
                                                        </div>
                                                        <div class="inline-flex items-center bg-zinc-950 rounded-lg p-1 border border-zinc-800">
                                                            <button type="button" wire:click="setColdSparksQuantity({{ max(1, ($service['quantity'] ?? 2) - 1) }})" class="w-7 h-7 flex items-center justify-center rounded bg-zinc-900 hover:bg-zinc-800 text-white font-bold text-sm transition cursor-pointer">-</button>
                                                            <span class="px-3 text-xs font-bold text-amber-400">{{ $service['quantity'] ?? 2 }} {{ ($service['quantity'] ?? 2) == 1 ? 'Unidad' : 'Unidades' }}</span>
                                                            <button type="button" wire:click="setColdSparksQuantity({{ min(10, ($service['quantity'] ?? 2) + 1) }})" class="w-7 h-7 flex items-center justify-center rounded bg-zinc-900 hover:bg-zinc-800 text-white font-bold text-sm transition cursor-pointer">+</button>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if($key === 'extra_hours')
                                                    <div class="mt-3 pt-3 border-t border-zinc-800 space-y-2" wire:click.stop>
                                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                                            <span class="text-xs font-medium text-zinc-300">Horas adicionales a presupuestar:</span>
                                                            <div class="inline-flex items-center bg-zinc-950 rounded-lg p-1 border border-zinc-800">
                                                                <button type="button" wire:click="setExtraHoursQuantity({{ max(1, ($service['quantity'] ?? 1) - 1) }})" class="w-7 h-7 flex items-center justify-center rounded bg-zinc-900 hover:bg-zinc-800 text-white font-bold text-sm transition cursor-pointer">-</button>
                                                                <span class="px-3 text-xs font-bold text-amber-400">+{{ $service['quantity'] ?? 1 }} {{ ($service['quantity'] ?? 1) == 1 ? 'Hora' : 'Horas' }}</span>
                                                                <button type="button" wire:click="setExtraHoursQuantity({{ min(6, ($service['quantity'] ?? 1) + 1) }})" class="w-7 h-7 flex items-center justify-center rounded bg-zinc-900 hover:bg-zinc-800 text-white font-bold text-sm transition cursor-pointer">+</button>
                                                            </div>
                                                        </div>
                                                        @php $recPack = $this->recommended_dj_pack_for_extra_hours; @endphp
                                                        @if($recPack)
                                                            <div class="p-2.5 bg-zinc-950 rounded-lg border border-zinc-800 flex flex-wrap items-center justify-between gap-2 text-xs">
                                                                <span class="text-zinc-300 text-[11px]">
                                                                    Para <strong>{{ $recPack['combined_total_hours'] }}h</strong> resulta más ventajoso seleccionar directamente el <strong>{{ $recPack['base_name'] }}</strong>
                                                                </span>
                                                                <button type="button" wire:click="applyRecommendedDjPack" class="px-2.5 py-1 rounded bg-amber-500 hover:bg-amber-400 text-zinc-950 font-semibold text-[11px] cursor-pointer">
                                                                    Aplicar {{ $recPack['base_name'] }}
                                                                </button>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif

                                                @if($key === 'pack_duo_360')
                                                    @php $duoH = (int)($service['hours'] ?? 2); @endphp
                                                    <div class="mt-3 pt-3 border-t border-zinc-800 flex flex-wrap items-center justify-between gap-2" wire:click.stop>
                                                        <span class="text-[11px] font-medium text-zinc-300">Duración del Pack Dúo:</span>
                                                        <div class="flex flex-wrap items-center gap-1.5">
                                                            <button type="button" wire:click="setPackDuoHours(2)" class="px-2.5 py-1 rounded text-xs font-semibold border transition cursor-pointer {{ $duoH === 2 ? 'bg-amber-500 text-zinc-950 border-amber-400' : 'bg-zinc-950 text-zinc-300 border-zinc-800' }}">2 Horas</button>
                                                            <button type="button" wire:click="setPackDuoHours(3)" class="px-2.5 py-1 rounded text-xs font-semibold border transition cursor-pointer {{ $duoH === 3 ? 'bg-amber-500 text-zinc-950 border-amber-400' : 'bg-zinc-950 text-zinc-300 border-zinc-800' }}">3 Horas</button>
                                                            <button type="button" wire:click="setPackDuoHours({{ $duoH >= 4 ? min(6, $duoH + 1) : 4 }})" class="px-2.5 py-1 rounded text-xs font-semibold border transition cursor-pointer {{ $duoH >= 4 ? 'bg-amber-500 text-zinc-950 border-amber-400' : 'bg-zinc-950 text-zinc-300 border-zinc-800' }}">{{ $duoH >= 4 ? "{$duoH}h (+1h)" : '+ Horas' }}</button>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if($key === 'photobooth')
                                                    @php $pbH = (int)($service['hours'] ?? 2); @endphp
                                                    <div class="mt-3 pt-3 border-t border-zinc-800 flex flex-wrap items-center justify-between gap-2" wire:click.stop>
                                                        <span class="text-[11px] font-medium text-zinc-300">Duración del servicio de Fotomatón:</span>
                                                        <div class="flex flex-wrap items-center gap-1.5">
                                                            <button type="button" wire:click="setPhotoboothHours(2)" class="px-2.5 py-1 rounded text-xs font-semibold border transition cursor-pointer {{ $pbH === 2 ? 'bg-amber-500 text-zinc-950 border-amber-400' : 'bg-zinc-950 text-zinc-300 border-zinc-800' }}">2 Horas</button>
                                                            <button type="button" wire:click="setPhotoboothHours(3)" class="px-2.5 py-1 rounded text-xs font-semibold border transition cursor-pointer {{ $pbH === 3 ? 'bg-amber-500 text-zinc-950 border-amber-400' : 'bg-zinc-950 text-zinc-300 border-zinc-800' }}">3 Horas</button>
                                                            <button type="button" wire:click="setPhotoboothHours({{ $pbH >= 4 ? min(6, $pbH + 1) : 4 }})" class="px-2.5 py-1 rounded text-xs font-semibold border transition cursor-pointer {{ $pbH >= 4 ? 'bg-amber-500 text-zinc-950 border-amber-400' : 'bg-zinc-950 text-zinc-300 border-zinc-800' }}">{{ $pbH >= 4 ? "{$pbH}h (+1h)" : '+ Horas' }}</button>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if($key === 'video_360')
                                                    @php $v360H = (int)($service['hours'] ?? 2); @endphp
                                                    <div class="mt-3 pt-3 border-t border-zinc-800 flex flex-wrap items-center justify-between gap-2" wire:click.stop>
                                                        <span class="text-[11px] font-medium text-zinc-300">Duración Plataforma 360º:</span>
                                                        <div class="flex flex-wrap items-center gap-1.5">
                                                            <button type="button" wire:click="setVideo360Hours(2)" class="px-2.5 py-1 rounded text-xs font-semibold border transition cursor-pointer {{ $v360H === 2 ? 'bg-amber-500 text-zinc-950 border-amber-400' : 'bg-zinc-950 text-zinc-300 border-zinc-800' }}">2 Horas</button>
                                                            <button type="button" wire:click="setVideo360Hours(3)" class="px-2.5 py-1 rounded text-xs font-semibold border transition cursor-pointer {{ $v360H === 3 ? 'bg-amber-500 text-zinc-950 border-amber-400' : 'bg-zinc-950 text-zinc-300 border-zinc-800' }}">3 Horas</button>
                                                            <button type="button" wire:click="setVideo360Hours({{ $v360H >= 4 ? min(6, $v360H + 1) : 4 }})" class="px-2.5 py-1 rounded text-xs font-semibold border transition cursor-pointer {{ $v360H >= 4 ? 'bg-amber-500 text-zinc-950 border-amber-400' : 'bg-zinc-950 text-zinc-300 border-zinc-800' }}">{{ $v360H >= 4 ? "{$v360H}h (+1h)" : '+ Horas' }}</button>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if($key === 'chocolate_fountain')
                                                    @php
                                                        $cfF = (int)($service['fountains'] ?? 1);
                                                        $cfH = (int)($service['hours'] ?? 2);
                                                        $cfWaffle = !empty($service['waffle_corner']);
                                                    @endphp
                                                    <div class="mt-3 pt-3 border-t border-zinc-800 space-y-2" wire:click.stop>
                                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
                                                            <button type="button" wire:click="setChocolateFountainOption(1, 2)" class="px-2 py-1.5 rounded text-xs font-semibold border transition cursor-pointer {{ $cfF === 1 && $cfH === 2 ? 'bg-amber-500 text-zinc-950 border-amber-400' : 'bg-zinc-950 text-zinc-300 border-zinc-800' }}">1 Fuente &bull; 2h</button>
                                                            <button type="button" wire:click="setChocolateFountainOption(1, 3)" class="px-2 py-1.5 rounded text-xs font-semibold border transition cursor-pointer {{ $cfF === 1 && $cfH === 3 ? 'bg-amber-500 text-zinc-950 border-amber-400' : 'bg-zinc-950 text-zinc-300 border-zinc-800' }}">1 Fuente &bull; 3h</button>
                                                            <button type="button" wire:click="setChocolateFountainOption(2, 2)" class="px-2 py-1.5 rounded text-xs font-semibold border transition cursor-pointer {{ $cfF === 2 && $cfH === 2 ? 'bg-amber-500 text-zinc-950 border-amber-400' : 'bg-zinc-950 text-zinc-300 border-zinc-800' }}">2 Fuentes &bull; 2h</button>
                                                            <button type="button" wire:click="setChocolateFountainOption(2, 3)" class="px-2 py-1.5 rounded text-xs font-semibold border transition cursor-pointer {{ $cfF === 2 && $cfH === 3 ? 'bg-amber-500 text-zinc-950 border-amber-400' : 'bg-zinc-950 text-zinc-300 border-zinc-800' }}">2 Fuentes &bull; 3h</button>
                                                        </div>
                                                        <button type="button" wire:click="toggleChocolateWaffleCorner" class="inline-flex items-center gap-1.5 px-3 py-1 rounded text-[11px] font-semibold border transition cursor-pointer {{ $cfWaffle ? 'bg-zinc-800 text-amber-400 border-amber-500/40' : 'bg-zinc-950 text-zinc-400 border-zinc-800' }}">
                                                            <span>{{ $cfWaffle ? 'Incluido:' : 'Añadir:' }} Estación adicional de gofres con siropes</span>
                                                        </button>
                                                    </div>
                                                @endif

                                                @if($key === 'waffle_cart')
                                                    @php $wcH = (int)($service['hours'] ?? 2); @endphp
                                                    <div class="mt-3 pt-3 border-t border-zinc-800 flex flex-wrap items-center justify-between gap-2" wire:click.stop>
                                                        <span class="text-[11px] font-medium text-zinc-300">Duración Estación de Gofres:</span>
                                                        <div class="flex flex-wrap items-center gap-1.5">
                                                            <button type="button" wire:click="setWaffleCartHours(2)" class="px-2.5 py-1 rounded text-xs font-semibold border transition cursor-pointer {{ $wcH === 2 ? 'bg-amber-500 text-zinc-950 border-amber-400' : 'bg-zinc-950 text-zinc-300 border-zinc-800' }}">2 Horas (~60 uds.)</button>
                                                            <button type="button" wire:click="setWaffleCartHours(3)" class="px-2.5 py-1 rounded text-xs font-semibold border transition cursor-pointer {{ $wcH === 3 ? 'bg-amber-500 text-zinc-950 border-amber-400' : 'bg-zinc-950 text-zinc-300 border-zinc-800' }}">3 Horas (~100 uds.)</button>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <!-- Bloque Especial: Otro Servicio / Montaje a Medida -->
                    <div class="p-4 rounded-2xl border transition-all select-none {{ $custom_service_selected ? 'bg-zinc-900 border-amber-500/70 ring-1 ring-amber-500/30' : 'bg-zinc-900/70 border-zinc-800 hover:border-zinc-700' }}">
                        <div class="flex items-center justify-between gap-3 cursor-pointer" wire:click="$toggle('custom_service_selected')">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold shrink-0 border {{ $custom_service_selected ? 'bg-amber-500 text-zinc-950 border-amber-400' : 'bg-zinc-950 text-zinc-400 border-zinc-800' }}">
                                    +
                                </div>
                                <div class="min-w-0">
                                    <h3 class="font-semibold text-xs sm:text-sm {{ $custom_service_selected ? 'text-white' : 'text-zinc-200' }}">
                                        {{ $currentType['custom_service_title'] ?? 'Requerimiento técnico adicional o servicio a medida' }}
                                    </h3>
                                    <p class="text-[11px] text-zinc-400 truncate">
                                        {{ $currentType['custom_service_subtitle'] ?? 'Sonorización adicional, iluminación extra, necesidades específicas...' }}
                                    </p>
                                </div>
                            </div>
                            <div class="w-5 h-5 rounded-md flex items-center justify-center border transition-colors shrink-0 {{ $custom_service_selected ? 'bg-amber-500 border-amber-400 text-zinc-950' : 'border-zinc-700 bg-zinc-950 text-transparent' }}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>

                        @if($custom_service_selected)
                            <div class="mt-3 pt-3 border-t border-zinc-800 grid grid-cols-1 sm:grid-cols-2 gap-2.5" wire:click.stop>
                                <div>
                                    <label class="block text-[11px] font-medium text-zinc-300 mb-1">Concepto o servicio requerido *</label>
                                    <input type="text" wire:model.live.debounce.300ms="custom_service_name" placeholder="{{ $currentType['custom_service_placeholder'] ?? 'Ej: Sonorización adicional / Iluminación...' }}"
                                           class="w-full bg-zinc-950 border border-zinc-700 rounded-lg px-3 py-2 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-medium text-zinc-300 mb-1">Especificaciones (opcional)</label>
                                    <input type="text" wire:model.live.debounce.300ms="custom_service_description" placeholder="{{ $currentType['custom_service_desc_placeholder'] ?? 'Ej: Detalles técnicos...' }}"
                                           class="w-full bg-zinc-950 border border-zinc-700 rounded-lg px-3 py-2 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500">
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Columna Derecha: Datos de Contacto y Detalles (5 cols) -->
                <div class="lg:col-span-5">
                    <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-5 sm:p-6 shadow-xl sticky top-4">
                        <div class="border-b border-zinc-800 pb-3 mb-4">
                            <h2 class="text-sm sm:text-base font-bold text-white uppercase tracking-wider">
                                {{ $currentType['form_title'] ?? 'Datos del Evento' }}
                            </h2>
                            <p class="text-xs text-zinc-400 mt-0.5">
                                {{ $currentType['form_subtitle'] ?? 'Completad vuestros datos para recibir la propuesta económica.' }}
                            </p>
                        </div>

                        <form wire:submit.prevent="submitRequest" class="space-y-3.5">
                            <!-- Nombre -->
                            <div>
                                <label class="block text-xs font-medium text-zinc-300 mb-1">{{ $currentType['name_label'] }}</label>
                                <input type="text" wire:model.live.debounce.300ms="client_name" placeholder="{{ $currentType['name_placeholder'] }}"
                                       class="w-full bg-zinc-950 border border-zinc-800 rounded-lg px-3.5 py-2 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500 transition">
                                @error('client_name') <span class="text-amber-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Teléfono y Email -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block text-xs font-medium text-zinc-300 mb-1">Teléfono / WhatsApp *</label>
                                    <input type="tel" wire:model.live.debounce.300ms="client_phone" placeholder="600 000 000"
                                           class="w-full bg-zinc-950 border border-zinc-800 rounded-lg px-3.5 py-2 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500 transition">
                                    @error('client_phone') <span class="text-amber-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-zinc-300 mb-1">Correo electrónico *</label>
                                    <input type="email" wire:model.live.debounce.300ms="client_email" placeholder="nombre@dominio.com"
                                           class="w-full bg-zinc-950 border border-zinc-800 rounded-lg px-3.5 py-2 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500 transition">
                                    @error('client_email') <span class="text-amber-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Fecha y Ubicación -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block text-xs font-medium text-zinc-300 mb-1">Fecha del evento *</label>
                                    <input type="date" wire:model.live="event_date"
                                           class="w-full bg-zinc-950 border border-zinc-800 rounded-lg px-3.5 py-2 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500 transition [color-scheme:dark]">
                                    @error('event_date') <span class="text-amber-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-zinc-300 mb-1">{{ $currentType['location_label'] ?? 'Lugar / Recinto / Localidad *' }}</label>
                                    <input type="text" wire:model.live.debounce.300ms="event_location" placeholder="{{ $currentType['location_placeholder'] ?? 'Finca, Restaurante, Hotel...' }}"
                                           class="w-full bg-zinc-950 border border-zinc-800 rounded-lg px-3.5 py-2 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500 transition">
                                    @error('event_location') <span class="text-amber-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Horario Aproximado + Montaje previo (cuando aplica según tipo de evento) -->
                            @if(!empty($currentType['show_schedule_box']) || !empty($currentType['show_prior_setup']))
                                <div class="p-3.5 rounded-xl bg-zinc-950 border border-zinc-800 space-y-2.5">
                                    @if(!empty($currentType['show_schedule_box']))
                                        <div>
                                            <label class="block text-xs font-medium text-zinc-300 mb-1">{{ $currentType['schedule_label'] ?? 'Horario aproximado o tramo del evento (opcional)' }}</label>
                                            <input type="text" wire:model.live.debounce.300ms="event_schedule" placeholder="{{ $currentType['schedule_placeholder'] ?? 'Ej: De 17:00 a 20:30h...' }}"
                                                   class="w-full bg-zinc-900 border border-zinc-800 rounded-lg px-3 py-1.5 text-xs sm:text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500 transition">
                                        </div>
                                    @endif

                                    @if(!empty($currentType['show_prior_setup']))
                                        <label class="flex items-start gap-2.5 pt-1 cursor-pointer select-none">
                                            <input type="checkbox" wire:model.live="needs_prior_setup"
                                                   class="mt-0.5 w-4 h-4 rounded border-zinc-700 bg-zinc-900 text-amber-500 focus:ring-amber-500 cursor-pointer">
                                            <span class="text-xs text-zinc-300 leading-snug">
                                                <strong class="text-zinc-100">{{ $currentType['prior_setup_label'] ?? 'Requiere montaje el día anterior, ensayo previo o varias jornadas' }}</strong> {{ $currentType['prior_setup_sublabel'] ?? '' }}
                                            </span>
                                        </label>
                                    @endif
                                </div>
                            @endif

                            <!-- Observaciones / Notas del Evento -->
                            <div class="pt-1">
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-xs font-semibold text-zinc-200">
                                        {{ $currentType['notes_label'] ?? 'Observaciones o detalles del evento (opcional)' }}
                                    </label>
                                </div>
                                <p class="text-[11px] text-zinc-400 mb-2 leading-snug">
                                    {{ $currentType['notes_help'] ?? 'Podéis detallar horarios previstos o requisitos específicos:' }}
                                </p>
                                <textarea wire:model.live.debounce.300ms="client_notes" rows="4"
                                          placeholder="{{ $currentType['notes_placeholder'] ?? 'Indicaciones sobre horarios, espacios o necesidades técnicas...' }}"
                                          class="w-full bg-zinc-950 border border-zinc-800 rounded-lg px-3.5 py-2.5 text-xs sm:text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500 transition resize-y"></textarea>
                                @error('client_notes') <span class="text-amber-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Confidencialidad -->
                            <div class="text-[11px] text-zinc-500">
                                Información tratada con estricta confidencialidad para la elaboración de vuestra propuesta.
                            </div>

                            <!-- Botón de Envío Ejecutivo -->
                            <button type="submit" wire:loading.attr="disabled"
                                    class="w-full py-3.5 px-6 rounded-xl bg-amber-500 hover:bg-amber-400 font-bold text-zinc-950 text-xs sm:text-sm uppercase tracking-wider shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50">
                                <span wire:loading.remove>Solicitar Propuesta Personalizada</span>
                                <span wire:loading class="inline-flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-zinc-950" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    Procesando solicitud...
                                </span>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        @endif

        <!-- Pie Corporativo -->
        <div class="mt-12 pt-6 border-t border-zinc-900 text-center text-[11px] text-zinc-500 flex flex-wrap items-center justify-center gap-x-8 gap-y-2 uppercase tracking-wider">
            <span>Sonido Profesional de Alta Fidelidad</span>
            <span>&bull;</span>
            <span>Iluminación Escénica y Ambiental</span>
            <span>&bull;</span>
            <span>Producción Técnica Integral</span>
            <span>&bull;</span>
            <span>Contrato Oficial y Garantía de Servicio</span>
        </div>

    </div>
</div>
