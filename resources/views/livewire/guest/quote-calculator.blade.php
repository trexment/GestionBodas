@php
    $types = \App\Livewire\Guest\QuoteCalculator::getEventTypes();
    $currentType = $types[$event_type] ?? $types['boda'];

    $serviceGroups = [
        [
            'title' => '🎵 Sonido, Fases y Sesión DJ',
            'subtitle' => 'Selecciona las partes de tu evento que quieres sonorizar',
            'keys' => ['ceremony', 'cocktail', 'restaurant', 'dj'],
        ],
        [
            'title' => '✨ Efectos, Animación y Horas Extra',
            'subtitle' => 'Efectos de pista, karaoke y ampliación de horario',
            'keys' => ['smoke_machine', 'cold_sparks', 'karaoke', 'extra_hours'],
        ],
        [
            'title' => '📸 Fotomatón, Plataforma 360º y Rincón Dulce',
            'subtitle' => 'Recuerdos divertidos y mesas dulces para tus invitados',
            'keys' => ['pack_duo_360', 'photobooth', 'video_360', 'chocolate_fountain', 'candy_bar', 'waffle_cart'],
        ],
    ];

    $selectedCount = collect($services)->where('selected', true)->count() + ($custom_service_selected ? 1 : 0);
@endphp

<div class="min-h-screen bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 py-5 sm:py-7 px-3 sm:px-6 lg:px-8 text-white">
    <div class="max-w-6xl mx-auto">
        
        <!-- Compact Header + Event Type Selector -->
        <div class="text-center mb-5">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-[11px] font-semibold uppercase tracking-wider mb-2 shadow-inner">
                <span>✨</span> Presupuesto a Medida &bull; {{ $currentType['badge'] }}
            </div>
            <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight text-white mb-1.5">
                {{ $currentType['hero_title'] }} <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 via-pink-400 to-indigo-400">{{ $currentType['hero_highlight'] }}</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 max-w-2xl mx-auto">
                {{ $currentType['hero_subtitle'] }}
            </p>
        </div>

        @if(!$is_submitted)
            <!-- SELECTOR DE TIPO DE EVENTO COMPACTO -->
            <div class="mb-5">
                <div class="flex flex-wrap items-center justify-center gap-1.5 sm:gap-2 max-w-4xl mx-auto">
                    @foreach($types as $key => $type)
                        <button type="button" 
                                wire:click="setEventType('{{ $key }}')"
                                class="inline-flex items-center gap-1.5 px-3 sm:px-3.5 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer {{ $event_type === $key ? 'bg-gradient-to-r from-indigo-600 via-indigo-500 to-purple-600 text-white shadow-md shadow-indigo-600/30 border border-indigo-400/50' : 'bg-slate-800/80 hover:bg-slate-700/80 text-slate-300 border border-slate-700/80 hover:text-white' }}">
                            <span class="text-sm">{{ $type['icon'] }}</span>
                            <span>{{ $type['name'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        @if($is_submitted)
            <!-- Success / Confirmation Card -->
            <div class="bg-slate-800/80 backdrop-blur-xl border border-emerald-500/30 rounded-3xl p-8 sm:p-10 text-center shadow-2xl max-w-2xl mx-auto animate-fade-in">
                <div class="w-16 h-16 bg-emerald-500/20 text-emerald-400 rounded-full flex items-center justify-center text-3xl mx-auto mb-5 ring-8 ring-emerald-500/10">
                    ✓
                </div>
                <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-xs font-bold mb-3 border border-indigo-500/30">
                    <span>{{ $currentType['icon'] }}</span> <span>{{ $currentType['name'] }}</span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-bold text-white mb-2">¡Solicitud Recibida con Éxito!</h2>
                <p class="text-slate-300 text-sm sm:text-base mb-5 leading-relaxed">
                    Muchas gracias <strong class="text-white">{{ $client_name }}</strong>. Hemos registrado tu solicitud de presupuesto para el <strong class="text-emerald-400">{{ \Carbon\Carbon::parse($event_date)->format('d/m/Y') }}</strong> en <strong class="text-white">{{ $event_location }}</strong>.
                </p>
                <div class="bg-slate-900/60 rounded-2xl p-4 sm:p-5 border border-slate-700/50 text-left mb-6 space-y-2">
                    <p class="text-xs uppercase font-bold tracking-wider text-slate-400 mb-2">Resumen de tu solicitud:</p>
                    @foreach($services as $key => $serv)
                        @if($serv['selected'])
                            <div class="flex items-center gap-2 flex-wrap text-sm text-slate-200">
                                <span>{{ $serv['icon'] }}</span>
                                <span class="font-medium">{{ $serv['name'] }}</span>
                                @if($key === 'dj')
                                    <span class="text-xs bg-indigo-500/20 text-indigo-300 px-2 py-0.5 rounded-full">{{ $this->dj_pack_info['badge'] }}</span>
                                @endif
                                @if($key === 'smoke_machine' && !empty($serv['included']))
                                    <span class="text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-2 py-0.5 rounded-full">✅ Incluido en {{ $this->dj_pack_info['base_name'] ?? 'Pack' }}</span>
                                @endif
                                @if($key === 'cold_sparks')
                                    <span class="text-xs bg-indigo-500/20 text-indigo-300 px-2 py-0.5 rounded-full font-bold">{{ $serv['quantity'] }} {{ $serv['quantity'] == 1 ? 'máquina' : 'máquinas' }}</span>
                                    @if(!empty($serv['included']))
                                        <span class="text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-2 py-0.5 rounded-full">✅ 2 máq. en {{ $this->dj_pack_info['base_name'] ?? 'Pack' }}</span>
                                    @endif
                                @endif
                                @if(($key === 'cocktail' || $key === 'restaurant') && $this->has_cocktail_restaurant_pack && $this->cocktail_restaurant_pack_info['enabled'])
                                    <span class="text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-2 py-0.5 rounded-full">🎁 En Pack Cóctel+Banquete</span>
                                @endif
                                @if($key === 'extra_hours')
                                    <span class="text-xs bg-amber-500/20 text-amber-300 px-2 py-0.5 rounded-full font-bold">+{{ $serv['quantity'] ?? 1 }} {{ ($serv['quantity'] ?? 1) == 1 ? 'hora extra' : 'horas extra' }}</span>
                                    @if(!empty($services['dj']['selected']))
                                        <span class="text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 px-2 py-0.5 rounded-full">Total: {{ $this->total_party_hours }}h de baile</span>
                                    @endif
                                @endif
                                @if(in_array($key, ['pack_duo_360', 'photobooth', 'video_360']))
                                    <span class="text-xs bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 px-2 py-0.5 rounded-full font-bold">{{ $serv['hours'] ?? 2 }} Horas</span>
                                @endif
                                @if($key === 'chocolate_fountain')
                                    <span class="text-xs bg-amber-500/20 text-amber-300 border border-amber-500/30 px-2 py-0.5 rounded-full font-bold">
                                        {{ ($serv['fountains'] ?? 1) == 2 ? '2 Fuentes' : '1 Fuente' }} &bull; {{ $serv['hours'] ?? 2 }} Horas
                                    </span>
                                    @if(!empty($serv['waffle_corner']))
                                        <span class="text-[10px] font-bold bg-orange-500/20 text-orange-300 border border-orange-500/30 px-2 py-0.5 rounded-full">+ Rincón de Gofres</span>
                                    @endif
                                @endif
                                @if($key === 'waffle_cart')
                                    <span class="text-xs bg-orange-500/20 text-orange-300 border border-orange-500/30 px-2 py-0.5 rounded-full font-bold">
                                        {{ $serv['hours'] ?? 2 }} Horas ({{ ($serv['hours'] ?? 2) == 3 ? '~100 gofres' : '~60 gofres' }})
                                    </span>
                                @endif
                            </div>
                        @endif
                    @endforeach
                    @if($custom_service_selected && !empty($custom_service_name))
                        <div class="flex items-center gap-2 text-sm text-slate-200">
                            <span>✨</span>
                            <span class="font-medium">{{ $custom_service_name }}</span>
                            <span class="text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 px-2 py-0.5 rounded-full">A consultar</span>
                        </div>
                    @endif
                    @if(!empty($event_schedule))
                        <div class="flex items-center gap-2 text-xs text-indigo-200 pt-1">
                            <span>⏰</span>
                            <span><strong>Horario / Tramo:</strong> {{ $event_schedule }}</span>
                        </div>
                    @endif
                    @if($needs_prior_setup)
                        <div class="flex items-center gap-2 text-xs text-amber-300">
                            <span>🔧</span>
                            <span><strong>Montaje especial:</strong> Requiere montaje día anterior / ensayo previo / varios días</span>
                        </div>
                    @endif
                    @if(!empty($client_notes))
                        <div class="pt-2 mt-2 border-t border-slate-800 text-xs text-slate-300">
                            <strong class="text-slate-400 block mb-0.5">📝 Tus notas / comentarios:</strong>
                            <p class="whitespace-pre-line">{{ $client_notes }}</p>
                        </div>
                    @endif
                    @if($this->has_cocktail_restaurant_pack && $this->cocktail_restaurant_pack_info['enabled'])
                        <div class="pt-2 border-t border-slate-800 text-xs text-emerald-400 font-semibold flex items-center gap-1.5">
                            <span>✨</span> <span>Pack Cóctel + Banquete aplicado ({{ $this->cocktail_restaurant_pack_info['savings_label'] }})</span>
                        </div>
                    @endif
                </div>
                <p class="text-xs sm:text-sm text-slate-400 mb-6">
                    Nuestro equipo comprobará la disponibilidad y te contactará muy pronto por teléfono o WhatsApp con tu presupuesto personalizado.
                </p>
                <a href="/" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg transition-all transform hover:-translate-y-0.5">
                    <span>🏠</span> Volver a la página principal
                </a>
            </div>
        @else
            <!-- Main Interactive Form Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 lg:gap-6 items-start">
                
                <!-- Left Side: Compact Service Selector Grid (7 cols) -->
                <div class="lg:col-span-7 space-y-4">
                    <div class="flex items-center justify-between px-1">
                        <h2 class="text-base sm:text-lg font-bold text-white flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-indigo-500/20 border border-indigo-500/40 text-indigo-300 text-xs flex items-center justify-center font-black">1</span>
                            <span>Elige los Servicios (o descríbelo en Notas)</span>
                        </h2>
                        @if($selectedCount > 0)
                            <span class="text-[11px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 px-2.5 py-0.5 rounded-full">
                                {{ $selectedCount }} {{ $selectedCount === 1 ? 'seleccionado' : 'seleccionados' }}
                            </span>
                        @else
                            <span class="text-[11px] text-slate-400 hidden sm:inline">Toca cualquier tarjeta para marcarla</span>
                        @endif
                    </div>

                    @if($this->has_cocktail_restaurant_pack && $this->cocktail_restaurant_pack_info['enabled'])
                        <div class="p-3 bg-gradient-to-r from-emerald-950/80 via-indigo-950/80 to-purple-950/80 border border-emerald-500/50 rounded-2xl flex items-center justify-between gap-3 shadow-lg">
                            <div class="flex items-center gap-2.5">
                                <span class="text-xl">🎁</span>
                                <div>
                                    <span class="text-xs font-black text-emerald-300 uppercase tracking-wider block">¡Pack Cóctel + Banquete Activado!</span>
                                    <span class="text-[11px] text-slate-300">Tarifa especial combinada con descuento aplicada en tu presupuesto.</span>
                                </div>
                            </div>
                            <span class="shrink-0 text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-2.5 py-1 rounded-full">
                                {{ round($this->cocktail_restaurant_pack_info['discount_percentage']) }}% Dto
                            </span>
                        </div>
                    @endif

                    @foreach($serviceGroups as $group)
                        <div class="bg-slate-900/40 border border-slate-800/80 rounded-2xl p-3 sm:p-3.5 space-y-2.5">
                            <div class="flex items-center justify-between px-1">
                                <h3 class="text-xs sm:text-sm font-extrabold text-indigo-200 tracking-wide">{{ $group['title'] }}</h3>
                                <span class="text-[10px] text-slate-400 hidden sm:inline">{{ $group['subtitle'] }}</span>
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
                                             class="{{ $spanFull ? 'sm:col-span-2' : '' }} relative p-3 sm:p-3.5 rounded-xl border transition-all cursor-pointer select-none {{ $service['selected'] ? 'bg-indigo-950/70 border-indigo-500/70 shadow-md shadow-indigo-950/50 ring-1 ring-indigo-500/50' : 'bg-slate-800/50 border-slate-700/60 hover:border-slate-500 hover:bg-slate-800/80' }}">
                                            
                                            <div class="flex items-start gap-2.5">
                                                <!-- Compact Icon Box -->
                                                <div class="w-9 h-9 rounded-lg flex items-center justify-center text-lg shrink-0 {{ $service['selected'] ? 'bg-indigo-500/20 text-indigo-300 ring-1 ring-indigo-500/40' : 'bg-slate-700/50 text-slate-300' }}">
                                                    {{ $service['icon'] }}
                                                </div>

                                                <!-- Details -->
                                                <div class="flex-1 min-w-0 pr-6">
                                                    <div class="flex items-center gap-1.5 flex-wrap">
                                                        <h4 class="font-bold text-xs sm:text-sm leading-tight {{ $service['selected'] ? 'text-white' : 'text-slate-200' }}">
                                                            {{ $service['name'] }}
                                                        </h4>
                                                        @if($service['selected'])
                                                            @if($key === 'smoke_machine' && !empty($service['included']))
                                                                <span class="px-1.5 py-0.5 rounded-md text-[9px] font-bold bg-emerald-500/30 text-emerald-300 border border-emerald-500/40">
                                                                    ✅ INCLUIDO EN PACK
                                                                </span>
                                                            @elseif($key === 'cold_sparks' && !empty($service['included']))
                                                                <span class="px-1.5 py-0.5 rounded-md text-[9px] font-bold bg-emerald-500/30 text-emerald-300 border border-emerald-500/40">
                                                                    ✅ 2 MÁQ. EN PACK
                                                                </span>
                                                            @elseif(($key === 'cocktail' || $key === 'restaurant') && $this->has_cocktail_restaurant_pack && $this->cocktail_restaurant_pack_info['enabled'])
                                                                <span class="px-1.5 py-0.5 rounded-md text-[9px] font-bold bg-emerald-500/30 text-emerald-300 border border-emerald-500/40">
                                                                    🎁 PACK DTO
                                                                </span>
                                                            @elseif($key === 'extra_hours')
                                                                <span class="px-1.5 py-0.5 rounded-md text-[9px] font-bold bg-amber-500/30 text-amber-200 border border-amber-500/40">
                                                                    +{{ $service['quantity'] ?? 1 }}H EXTRA
                                                                </span>
                                                            @elseif(in_array($key, ['pack_duo_360', 'photobooth', 'video_360']))
                                                                <span class="px-1.5 py-0.5 rounded-md text-[9px] font-bold bg-indigo-500/30 text-indigo-200 border border-indigo-400/40">
                                                                    ⏱️ {{ $service['hours'] ?? 2 }}H
                                                                </span>
                                                            @elseif($key === 'chocolate_fountain')
                                                                <span class="px-1.5 py-0.5 rounded-md text-[9px] font-bold bg-amber-500/30 text-amber-200 border border-amber-400/40">
                                                                    🍫 {{ ($service['fountains'] ?? 1) == 2 ? '2 FUENTES' : '1 FUENTE' }} &bull; {{ $service['hours'] ?? 2 }}H
                                                                </span>
                                                            @elseif($key === 'waffle_cart')
                                                                <span class="px-1.5 py-0.5 rounded-md text-[9px] font-bold bg-orange-500/30 text-orange-200 border border-orange-400/40">
                                                                    🧇 {{ $service['hours'] ?? 2 }}H
                                                                </span>
                                                            @endif
                                                        @else
                                                            @if($key === 'smoke_machine')
                                                                <span class="text-[9px] text-emerald-400/90 font-semibold">✨ Gratis desde 5h DJ</span>
                                                            @elseif($key === 'cold_sparks')
                                                                <span class="text-[9px] text-amber-400/90 font-semibold">👑 2 incl. en Pack Deluxe</span>
                                                            @elseif($key === 'pack_duo_360')
                                                                <span class="px-1.5 py-0.5 rounded-md text-[9px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">🔥 AHORRO</span>
                                                            @elseif(($key === 'photobooth' || $key === 'video_360') && !empty($services['pack_duo_360']['selected']))
                                                                <span class="px-1.5 py-0.5 rounded-md text-[9px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">✅ EN PACK DÚO</span>
                                                            @endif
                                                        @endif
                                                    </div>
                                                    <p class="text-[11px] text-slate-400 mt-0.5 leading-snug {{ $service['selected'] ? '' : 'line-clamp-2' }}">
                                                        {{ $service['description'] }}
                                                    </p>

                                                    @if($key === 'cocktail' && $service['selected'] && empty($services['restaurant']['selected']) && $this->cocktail_restaurant_pack_info['enabled'])
                                                        <div class="mt-1.5 text-[10px] text-amber-300 font-semibold">
                                                            💡 Añade Banquete para descuento de Pack.
                                                        </div>
                                                    @elseif($key === 'restaurant' && $service['selected'] && empty($services['cocktail']['selected']) && $this->cocktail_restaurant_pack_info['enabled'])
                                                        <div class="mt-1.5 text-[10px] text-amber-300 font-semibold">
                                                            💡 Añade Cóctel para descuento de Pack.
                                                        </div>
                                                    @endif
                                                </div>

                                                <!-- Checkmark Status Bubble -->
                                                <div class="absolute top-3 right-3 w-5 h-5 rounded-full flex items-center justify-center border transition-colors {{ $service['selected'] ? 'bg-indigo-500 border-indigo-400 text-white shadow-sm shadow-indigo-500/40' : 'border-slate-600 bg-slate-900/40 text-transparent' }}">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </div>
                                            </div>

                                            <!-- SUB-CONTROLS: ONLY SHOWN WHEN SELECTED -->
                                            @if($service['selected'])
                                                @if($key === 'dj')
                                                    <div class="mt-3 pt-2.5 border-t border-indigo-500/20 space-y-2" wire:click.stop>
                                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                                            <span class="text-xs font-semibold text-slate-200">⏱️ Duración estimada de baile / DJ:</span>
                                                            <div class="inline-flex items-center bg-slate-900/90 rounded-xl p-1 border border-indigo-500/40">
                                                                <button type="button" wire:click="setDjHours({{ max(2, $service['quantity'] - 1) }})" class="w-7 h-7 flex items-center justify-center rounded-lg bg-slate-800 hover:bg-indigo-600 text-white font-bold text-sm transition cursor-pointer">-</button>
                                                                <span class="px-3 text-xs font-extrabold text-indigo-200">{{ $service['quantity'] }} Horas</span>
                                                                <button type="button" wire:click="setDjHours({{ min(9, $service['quantity'] + 1) }})" class="w-7 h-7 flex items-center justify-center rounded-lg bg-slate-800 hover:bg-indigo-600 text-white font-bold text-sm transition cursor-pointer">+</button>
                                                            </div>
                                                        </div>
                                                        <div class="p-2.5 bg-slate-900/80 rounded-xl border border-indigo-400/30 flex items-start gap-2 text-xs text-slate-200">
                                                            <span class="text-sm shrink-0">
                                                                @if($this->dj_pack_info['pack_key'] === 'premium') 👑 @elseif($this->dj_pack_info['pack_key'] === 'medium') 🌟 @else ✨ @endif
                                                            </span>
                                                            <div class="flex-1 min-w-0">
                                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                                    <strong class="text-indigo-300 font-bold">{{ $this->dj_pack_info['name'] }}</strong>
                                                                    @if(!empty($this->dj_pack_info['effects_badge']))
                                                                        <span class="text-[10px] font-semibold text-emerald-300 bg-emerald-500/10 border border-emerald-500/30 px-1.5 py-0.5 rounded">✨ Incluye: {{ $this->dj_pack_info['effects_badge'] }}</span>
                                                                    @endif
                                                                </div>
                                                                <p class="text-[11px] text-slate-300 mt-0.5 leading-snug">{{ $this->dj_pack_info['description'] }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if($key === 'cold_sparks')
                                                    <div class="mt-3 pt-2.5 border-t border-indigo-500/20 flex flex-wrap items-center justify-between gap-2" wire:click.stop>
                                                        <div>
                                                            <span class="text-xs font-semibold text-slate-200">Nº de máquinas de chispas:</span>
                                                            @if(!empty($service['included']))
                                                                <span class="block text-[10px] text-emerald-300">👑 2 máquinas incluidas en tu Pack (0 €)</span>
                                                            @endif
                                                        </div>
                                                        <div class="inline-flex items-center bg-slate-900/90 rounded-xl p-1 border border-indigo-500/40">
                                                            <button type="button" wire:click="setColdSparksQuantity({{ max(1, ($service['quantity'] ?? 2) - 1) }})" class="w-7 h-7 flex items-center justify-center rounded-lg bg-slate-800 hover:bg-indigo-600 text-white font-bold text-sm transition cursor-pointer">-</button>
                                                            <span class="px-3 text-xs font-extrabold text-indigo-200">{{ $service['quantity'] ?? 2 }} {{ ($service['quantity'] ?? 2) == 1 ? 'Máquina' : 'Máquinas' }}</span>
                                                            <button type="button" wire:click="setColdSparksQuantity({{ min(10, ($service['quantity'] ?? 2) + 1) }})" class="w-7 h-7 flex items-center justify-center rounded-lg bg-slate-800 hover:bg-indigo-600 text-white font-bold text-sm transition cursor-pointer">+</button>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if($key === 'extra_hours')
                                                    <div class="mt-3 pt-2.5 border-t border-indigo-500/20 space-y-2" wire:click.stop>
                                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                                            <span class="text-xs font-semibold text-slate-200">Horas adicionales a añadir:</span>
                                                            <div class="inline-flex items-center bg-slate-900/90 rounded-xl p-1 border border-indigo-500/40">
                                                                <button type="button" wire:click="setExtraHoursQuantity({{ max(1, ($service['quantity'] ?? 1) - 1) }})" class="w-7 h-7 flex items-center justify-center rounded-lg bg-slate-800 hover:bg-indigo-600 text-white font-bold text-sm transition cursor-pointer">-</button>
                                                                <span class="px-3 text-xs font-extrabold text-amber-300">+{{ $service['quantity'] ?? 1 }} {{ ($service['quantity'] ?? 1) == 1 ? 'Hora' : 'Horas' }}</span>
                                                                <button type="button" wire:click="setExtraHoursQuantity({{ min(6, ($service['quantity'] ?? 1) + 1) }})" class="w-7 h-7 flex items-center justify-center rounded-lg bg-slate-800 hover:bg-indigo-600 text-white font-bold text-sm transition cursor-pointer">+</button>
                                                            </div>
                                                        </div>
                                                        @php $recPack = $this->recommended_dj_pack_for_extra_hours; @endphp
                                                        @if($recPack)
                                                            <div class="p-2.5 bg-amber-950/60 rounded-xl border border-amber-400/40 flex flex-wrap items-center justify-between gap-2 text-xs">
                                                                <span class="text-amber-100 text-[11px]">
                                                                    💡 Para <strong>{{ $recPack['combined_total_hours'] }}h</strong> te sale mejor el <strong>{{ $recPack['base_name'] }}</strong>
                                                                </span>
                                                                <button type="button" wire:click="applyRecommendedDjPack" class="px-2.5 py-1 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 font-extrabold text-[11px] cursor-pointer">
                                                                    Cambiar al {{ $recPack['base_name'] }}
                                                                </button>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif

                                                @if($key === 'pack_duo_360')
                                                    @php $duoH = (int)($service['hours'] ?? 2); @endphp
                                                    <div class="mt-3 pt-2.5 border-t border-indigo-500/20 flex flex-wrap items-center justify-between gap-2" wire:click.stop>
                                                        <span class="text-[11px] font-semibold text-slate-200">Duración del Pack Dúo:</span>
                                                        <div class="flex flex-wrap items-center gap-1.5">
                                                            <button type="button" wire:click="setPackDuoHours(2)" class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer {{ $duoH === 2 ? 'bg-indigo-600 text-white border-indigo-400' : 'bg-slate-900/80 text-slate-300 border-slate-700' }}">⏱️ 2 Horas</button>
                                                            <button type="button" wire:click="setPackDuoHours(3)" class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer {{ $duoH === 3 ? 'bg-indigo-600 text-white border-indigo-400' : 'bg-slate-900/80 text-slate-300 border-slate-700' }}">⏱️ 3 Horas</button>
                                                            <button type="button" wire:click="setPackDuoHours({{ $duoH >= 4 ? min(6, $duoH + 1) : 4 }})" class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer {{ $duoH >= 4 ? 'bg-amber-600 text-white border-amber-400' : 'bg-slate-900/80 text-amber-300 border-amber-500/40' }}">{{ $duoH >= 4 ? "⏱️ {$duoH}h (+1h)" : '+ Horas' }}</button>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if($key === 'photobooth')
                                                    @php $pbH = (int)($service['hours'] ?? 2); @endphp
                                                    <div class="mt-3 pt-2.5 border-t border-indigo-500/20 flex flex-wrap items-center justify-between gap-2" wire:click.stop>
                                                        <span class="text-[11px] font-semibold text-slate-200">Duración del Fotomatón:</span>
                                                        <div class="flex flex-wrap items-center gap-1.5">
                                                            <button type="button" wire:click="setPhotoboothHours(2)" class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer {{ $pbH === 2 ? 'bg-indigo-600 text-white border-indigo-400' : 'bg-slate-900/80 text-slate-300 border-slate-700' }}">⏱️ 2 Horas</button>
                                                            <button type="button" wire:click="setPhotoboothHours(3)" class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer {{ $pbH === 3 ? 'bg-indigo-600 text-white border-indigo-400' : 'bg-slate-900/80 text-slate-300 border-slate-700' }}">⏱️ 3 Horas</button>
                                                            <button type="button" wire:click="setPhotoboothHours({{ $pbH >= 4 ? min(6, $pbH + 1) : 4 }})" class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer {{ $pbH >= 4 ? 'bg-amber-600 text-white border-amber-400' : 'bg-slate-900/80 text-amber-300 border-amber-500/40' }}">{{ $pbH >= 4 ? "⏱️ {$pbH}h (+1h)" : '+ Horas' }}</button>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if($key === 'video_360')
                                                    @php $v360H = (int)($service['hours'] ?? 2); @endphp
                                                    <div class="mt-3 pt-2.5 border-t border-indigo-500/20 flex flex-wrap items-center justify-between gap-2" wire:click.stop>
                                                        <span class="text-[11px] font-semibold text-slate-200">Duración Plataforma 360º:</span>
                                                        <div class="flex flex-wrap items-center gap-1.5">
                                                            <button type="button" wire:click="setVideo360Hours(2)" class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer {{ $v360H === 2 ? 'bg-indigo-600 text-white border-indigo-400' : 'bg-slate-900/80 text-slate-300 border-slate-700' }}">⏱️ 2 Horas</button>
                                                            <button type="button" wire:click="setVideo360Hours(3)" class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer {{ $v360H === 3 ? 'bg-indigo-600 text-white border-indigo-400' : 'bg-slate-900/80 text-slate-300 border-slate-700' }}">⏱️ 3 Horas</button>
                                                            <button type="button" wire:click="setVideo360Hours({{ $v360H >= 4 ? min(6, $v360H + 1) : 4 }})" class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer {{ $v360H >= 4 ? 'bg-amber-600 text-white border-amber-400' : 'bg-slate-900/80 text-amber-300 border-amber-500/40' }}">{{ $v360H >= 4 ? "⏱️ {$v360H}h (+1h)" : '+ Horas' }}</button>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if($key === 'chocolate_fountain')
                                                    @php
                                                        $cfF = (int)($service['fountains'] ?? 1);
                                                        $cfH = (int)($service['hours'] ?? 2);
                                                        $cfWaffle = !empty($service['waffle_corner']);
                                                    @endphp
                                                    <div class="mt-3 pt-2.5 border-t border-indigo-500/20 space-y-2" wire:click.stop>
                                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
                                                            <button type="button" wire:click="setChocolateFountainOption(1, 2)" class="px-2 py-1.5 rounded-lg text-xs font-bold border transition cursor-pointer {{ $cfF === 1 && $cfH === 2 ? 'bg-amber-600 text-white border-amber-400' : 'bg-slate-900/80 text-slate-300 border-slate-700' }}">🍫 1 Fuente · 2h</button>
                                                            <button type="button" wire:click="setChocolateFountainOption(1, 3)" class="px-2 py-1.5 rounded-lg text-xs font-bold border transition cursor-pointer {{ $cfF === 1 && $cfH === 3 ? 'bg-amber-600 text-white border-amber-400' : 'bg-slate-900/80 text-slate-300 border-slate-700' }}">🍫 1 Fuente · 3h</button>
                                                            <button type="button" wire:click="setChocolateFountainOption(2, 2)" class="px-2 py-1.5 rounded-lg text-xs font-bold border transition cursor-pointer {{ $cfF === 2 && $cfH === 2 ? 'bg-amber-600 text-white border-amber-400' : 'bg-slate-900/80 text-slate-300 border-slate-700' }}">🍫🍫 2 Fuentes · 2h</button>
                                                            <button type="button" wire:click="setChocolateFountainOption(2, 3)" class="px-2 py-1.5 rounded-lg text-xs font-bold border transition cursor-pointer {{ $cfF === 2 && $cfH === 3 ? 'bg-amber-600 text-white border-amber-400' : 'bg-slate-900/80 text-slate-300 border-slate-700' }}">🍫🍫 2 Fuentes · 3h</button>
                                                        </div>
                                                        <button type="button" wire:click="toggleChocolateWaffleCorner" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold border transition cursor-pointer {{ $cfWaffle ? 'bg-orange-500/30 text-orange-200 border-orange-400' : 'bg-slate-900/60 text-slate-400 border-slate-700' }}">
                                                            <span>{{ $cfWaffle ? '✅' : '➕' }}</span>
                                                            <span>Añadir rincón extra de gofres con siropes y nata</span>
                                                        </button>
                                                    </div>
                                                @endif

                                                @if($key === 'waffle_cart')
                                                    @php $wcH = (int)($service['hours'] ?? 2); @endphp
                                                    <div class="mt-3 pt-2.5 border-t border-indigo-500/20 flex flex-wrap items-center justify-between gap-2" wire:click.stop>
                                                        <span class="text-[11px] font-semibold text-slate-200">Duración Carrito de Gofres:</span>
                                                        <div class="flex flex-wrap items-center gap-1.5">
                                                            <button type="button" wire:click="setWaffleCartHours(2)" class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer {{ $wcH === 2 ? 'bg-orange-600 text-white border-orange-400' : 'bg-slate-900/80 text-slate-300 border-slate-700' }}">🧇 2 Horas (~60 gofres)</button>
                                                            <button type="button" wire:click="setWaffleCartHours(3)" class="px-2.5 py-1 rounded-lg text-xs font-bold border transition cursor-pointer {{ $wcH === 3 ? 'bg-orange-600 text-white border-orange-400' : 'bg-slate-900/80 text-slate-300 border-slate-700' }}">🧇 3 Horas (~100 gofres)</button>
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

                    <!-- Bloque Especial: Otro Servicio / Montaje Especial / Evento a Medida -->
                    <div class="p-3.5 rounded-2xl border transition-all select-none {{ $custom_service_selected ? 'bg-indigo-950/60 border-indigo-500/60 shadow-lg ring-1 ring-indigo-500/50' : 'bg-slate-900/40 border-slate-800/80 hover:border-slate-600' }}">
                        <div class="flex items-center justify-between gap-3 cursor-pointer" wire:click="$toggle('custom_service_selected')">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center text-lg shrink-0 {{ $custom_service_selected ? 'bg-indigo-500/20 text-indigo-300' : 'bg-slate-800 text-slate-400' }}">
                                    ✨
                                </div>
                                <div class="min-w-0">
                                    <h3 class="font-bold text-xs sm:text-sm {{ $custom_service_selected ? 'text-white' : 'text-slate-200' }}">
                                        {{ $currentType['custom_service_title'] ?? '¿Necesitas un montaje especial, sonorización a medida u otro extra?' }}
                                    </h3>
                                    <p class="text-[11px] text-slate-400 truncate">
                                        {{ $currentType['custom_service_subtitle'] ?? 'Sonorización adicional, iluminación extra, necesidades especiales...' }}
                                    </p>
                                </div>
                            </div>
                            <div class="w-5 h-5 rounded-full flex items-center justify-center border transition-colors shrink-0 {{ $custom_service_selected ? 'bg-indigo-500 border-indigo-400 text-white' : 'border-slate-600 bg-slate-900/40 text-transparent' }}">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>

                        @if($custom_service_selected)
                            <div class="mt-3 pt-3 border-t border-indigo-500/20 grid grid-cols-1 sm:grid-cols-2 gap-2.5" wire:click.stop>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">¿Qué servicio o montaje necesitas? *</label>
                                    <input type="text" wire:model.live.debounce.300ms="custom_service_name" placeholder="{{ $currentType['custom_service_placeholder'] ?? 'Ej: Sonorización extra / Iluminación especial...' }}"
                                           class="w-full bg-slate-900/80 border border-indigo-500/40 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Detalles rápidos (opcional)</label>
                                    <input type="text" wire:model.live.debounce.300ms="custom_service_description" placeholder="{{ $currentType['custom_service_desc_placeholder'] ?? 'Ej: Detalles del servicio adicional...' }}"
                                           class="w-full bg-slate-900/80 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Right Side: Contact, Schedule & Notes Form (5 cols) -->
                <div class="lg:col-span-5">
                    <div class="bg-slate-800/75 backdrop-blur-xl border border-slate-700/80 rounded-3xl p-5 sm:p-6 shadow-2xl sticky top-4">
                        <h2 class="text-base sm:text-lg font-bold text-white flex items-center gap-2 mb-1">
                            <span class="w-6 h-6 rounded-lg bg-indigo-500/20 border border-indigo-500/40 text-indigo-300 text-xs flex items-center justify-center font-black">2</span>
                            <span>{{ $currentType['form_title'] ?? 'Datos y Detalles del Evento' }}</span>
                        </h2>
                        <p class="text-xs text-slate-400 mb-4">
                            {{ $currentType['form_subtitle'] ?? 'Rellena tus datos para recibir tu presupuesto personalizado.' }}
                        </p>

                        <form wire:submit.prevent="submitRequest" class="space-y-3.5">
                            <!-- Name -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">{{ $currentType['name_label'] }}</label>
                                <input type="text" wire:model.live.debounce.300ms="client_name" placeholder="{{ $currentType['name_placeholder'] }}"
                                       class="w-full bg-slate-900/80 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                @error('client_name') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Phone & Email Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">Teléfono / WhatsApp *</label>
                                    <input type="tel" wire:model.live.debounce.300ms="client_phone" placeholder="600 000 000"
                                           class="w-full bg-slate-900/80 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                    @error('client_phone') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">Email *</label>
                                    <input type="email" wire:model.live.debounce.300ms="client_email" placeholder="email@ejemplo.com"
                                           class="w-full bg-slate-900/80 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                    @error('client_email') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Date & Location Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">Fecha del evento *</label>
                                    <input type="date" wire:model.live="event_date"
                                           class="w-full bg-slate-900/80 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition [color-scheme:dark]">
                                    @error('event_date') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">{{ $currentType['location_label'] ?? 'Lugar / Recinto / Localidad *' }}</label>
                                    <input type="text" wire:model.live.debounce.300ms="event_location" placeholder="{{ $currentType['location_placeholder'] ?? 'Finca, Restaurante, Hotel...' }}"
                                           class="w-full bg-slate-900/80 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                    @error('event_location') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Horario Aproximado + Check Montaje Día Anterior / Ensayo (Solo cuando aplica según tipo de evento, NO en bodas) -->
                            @if(!empty($currentType['show_schedule_box']) || !empty($currentType['show_prior_setup']))
                                <div class="p-3 rounded-2xl bg-slate-900/70 border border-indigo-500/30 space-y-2.5">
                                    @if(!empty($currentType['show_schedule_box']))
                                        <div>
                                            <label class="block text-xs font-bold text-indigo-200 mb-1">{{ $currentType['schedule_label'] ?? '⏰ Horario aproximado o tramo del evento (opcional)' }}</label>
                                            <input type="text" wire:model.live.debounce.300ms="event_schedule" placeholder="{{ $currentType['schedule_placeholder'] ?? 'Ej: De 17:00 a 20:30h / Tarde...' }}"
                                                   class="w-full bg-slate-950/80 border border-slate-700 rounded-xl px-3 py-1.5 text-xs sm:text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                                        </div>
                                    @endif

                                    @if(!empty($currentType['show_prior_setup']))
                                        <label class="flex items-start gap-2.5 pt-1 cursor-pointer select-none group">
                                            <input type="checkbox" wire:model.live="needs_prior_setup"
                                                   class="mt-0.5 w-4 h-4 rounded border-slate-600 bg-slate-900 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                                            <span class="text-xs text-slate-300 group-hover:text-white leading-snug">
                                                🔧 <strong class="text-amber-300">{{ $currentType['prior_setup_label'] ?? 'Necesitamos montaje el día anterior, ensayo previo o varios días' }}</strong> {{ $currentType['prior_setup_sublabel'] ?? '(ej. festivales de colegio, pruebas de sonido previas, etc.)' }}
                                            </span>
                                        </label>
                                    @endif
                                </div>
                            @endif

                            <!-- Highlighted Notes / Free Explanation Section -->
                            <div class="p-3.5 rounded-2xl bg-indigo-950/30 border border-indigo-500/40">
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-xs font-extrabold text-amber-300 flex items-center gap-1.5">
                                        <span>📝</span>
                                        <span>{{ $currentType['notes_label'] ?? 'Notas o detalles de tu evento (opcional)' }}</span>
                                    </label>
                                    <span class="text-[10px] text-indigo-300 font-semibold">Espacio libre</span>
                                </div>
                                <p class="text-[11px] text-slate-300 mb-2 leading-snug">
                                    {{ $currentType['notes_help'] ?? 'Cuéntanos cualquier detalle, horario o idea especial para tu evento:' }}
                                </p>
                                <textarea wire:model.live.debounce.300ms="client_notes" rows="4"
                                          placeholder="{{ $currentType['notes_placeholder'] ?? 'Escribe aquí cualquier detalle o comentario...' }}"
                                          class="w-full bg-slate-900/90 border border-indigo-500/40 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition resize-y"></textarea>
                                @error('client_notes') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Privacy / Assurance Note -->
                            <div class="flex items-center gap-2 text-[11px] text-slate-400">
                                <span class="text-emerald-400 text-xs">🔒</span>
                                <span>Tus datos son 100% confidenciales. Te responderemos con tu presupuesto a medida.</span>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" wire:loading.attr="disabled"
                                    class="w-full py-3.5 px-6 rounded-2xl bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 hover:from-indigo-600 hover:via-purple-600 hover:to-pink-600 font-bold text-white shadow-xl shadow-indigo-500/25 transition-all transform hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2 group cursor-pointer disabled:opacity-50">
                                <span wire:loading.remove>📩 Solicitar Presupuesto Gratuito</span>
                                <span wire:loading class="inline-flex items-center gap-2">
                                    <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    Enviando solicitud...
                                </span>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        @endif

        <!-- Footer assurances -->
        <div class="mt-10 pt-6 border-t border-slate-800 text-center text-xs text-slate-500 flex flex-wrap items-center justify-center gap-6">
            <span class="flex items-center gap-1.5">🎧 <span>Sonido Profesional de Alta Fidelidad</span></span>
            <span class="flex items-center gap-1.5">💡 <span>Iluminación Robotizada y Ambiental</span></span>
            <span class="flex items-center gap-1.5">🎵 <span>Adaptado a Bodas, Colegios, Empresas y Fiestas</span></span>
            <span class="flex items-center gap-1.5">📑 <span>Contrato y Garantía de Servicio</span></span>
        </div>

    </div>
</div>
