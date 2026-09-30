@php
    $types = \App\Livewire\Guest\QuoteCalculator::getEventTypes();
    $currentType = $types[$event_type] ?? $types['boda'];
@endphp

<div class="min-h-screen bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 py-10 px-4 sm:px-6 lg:px-8 text-white">
    <div class="max-w-5xl mx-auto">
        
        <!-- Header / Hero -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold uppercase tracking-wider mb-4 shadow-inner">
                <span>✨</span> Presupuesto a Medida &bull; {{ $currentType['badge'] }}
            </div>
            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-white mb-3">
                {{ $currentType['hero_title'] }} <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 via-pink-400 to-indigo-400">{{ $currentType['hero_highlight'] }}</span>
            </h1>
            <p class="text-base sm:text-lg text-slate-400 max-w-2xl mx-auto">
                {{ $currentType['hero_subtitle'] }}
            </p>
        </div>

        @if(!$is_submitted)
            <!-- SELECTOR DE TIPO DE EVENTO -->
            <div class="mb-8">
                <div class="text-center mb-3">
                    <span class="text-[11px] uppercase font-black tracking-widest text-slate-400">¿Qué tipo de celebración estás organizando?</span>
                </div>
                <div class="flex flex-wrap items-center justify-center gap-2 max-w-3xl mx-auto">
                    @foreach($types as $key => $type)
                        <button type="button" 
                                wire:click="setEventType('{{ $key }}')"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl text-xs sm:text-sm font-bold transition-all transform hover:scale-102 cursor-pointer {{ $event_type === $key ? 'bg-gradient-to-r from-indigo-600 via-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-600/30 border border-indigo-400/50' : 'bg-slate-800/80 hover:bg-slate-700/80 text-slate-300 border border-slate-700/80 hover:text-white' }}">
                            <span class="text-base">{{ $type['icon'] }}</span>
                            <span>{{ $type['name'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        @if($is_submitted)
            <!-- Success / Confirmation Card -->
            <div class="bg-slate-800/80 backdrop-blur-xl border border-emerald-500/30 rounded-3xl p-8 sm:p-12 text-center shadow-2xl max-w-2xl mx-auto animate-fade-in">
                <div class="w-20 h-20 bg-emerald-500/20 text-emerald-400 rounded-full flex items-center justify-center text-4xl mx-auto mb-6 ring-8 ring-emerald-500/10">
                    ✓
                </div>
                <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-xs font-bold mb-3 border border-indigo-500/30">
                    <span>{{ $currentType['icon'] }}</span> <span>{{ $currentType['name'] }}</span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-bold text-white mb-3">¡Solicitud Recibida con Éxito!</h2>
                <p class="text-slate-300 text-base mb-6 leading-relaxed">
                    Muchas gracias <strong class="text-white">{{ $client_name }}</strong>. Hemos registrado tu solicitud de presupuesto para el <strong class="text-emerald-400">{{ \Carbon\Carbon::parse($event_date)->format('d/m/Y') }}</strong> en <strong class="text-white">{{ $event_location }}</strong>.
                </p>
                <div class="bg-slate-900/60 rounded-2xl p-5 border border-slate-700/50 text-left mb-8 space-y-2">
                    <p class="text-xs uppercase font-bold tracking-wider text-slate-400 mb-2">Servicios solicitados:</p>
                    @foreach($services as $key => $serv)
                        @if($serv['selected'])
                            <div class="flex items-center gap-2 text-sm text-slate-200">
                                <span>{{ $serv['icon'] }}</span>
                                <span class="font-medium">{{ $serv['name'] }}</span>
                                @if($key === 'dj')
                                    <span class="text-xs bg-indigo-500/20 text-indigo-300 px-2 py-0.5 rounded-full">({{ $serv['quantity'] }} horas)</span>
                                @endif
                                @if(($key === 'cocktail' || $key === 'restaurant') && $this->has_cocktail_restaurant_pack && $this->cocktail_restaurant_pack_info['enabled'])
                                    <span class="text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-2 py-0.5 rounded-full">🎁 En Pack Cóctel+Banquete</span>
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
                    @if($this->has_cocktail_restaurant_pack && $this->cocktail_restaurant_pack_info['enabled'])
                        <div class="pt-2 border-t border-slate-800 text-xs text-emerald-400 font-semibold flex items-center gap-1.5">
                            <span>✨</span> <span>Pack Cóctel + Banquete aplicado ({{ $this->cocktail_restaurant_pack_info['savings_label'] }})</span>
                        </div>
                    @endif
                </div>
                <p class="text-sm text-slate-400 mb-8">
                    Nuestro equipo comprobará la disponibilidad de la fecha y te contactará muy pronto por teléfono o WhatsApp para enviarte todos los detalles.
                </p>
                <a href="/" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg transition-all transform hover:-translate-y-0.5">
                    <span>🏠</span> Volver a la página principal
                </a>
            </div>
        @else
            <!-- Main Interactive Form Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Left Side: Service Selector Cards (No Prices) -->
                <div class="lg:col-span-7 space-y-4">
                    <div class="flex items-center justify-between px-1">
                        <h2 class="text-lg font-bold text-white flex items-center gap-2">
                            <span>1.</span> Elige las Fases y Servicios
                        </h2>
                        <span class="text-xs text-slate-400">Toca para seleccionar / deseleccionar</span>
                    </div>

                    @if($this->has_cocktail_restaurant_pack && $this->cocktail_restaurant_pack_info['enabled'])
                        <div class="p-3.5 bg-gradient-to-r from-emerald-950/80 via-indigo-950/80 to-purple-950/80 border border-emerald-500/50 rounded-2xl flex items-center justify-between gap-3 shadow-lg shadow-emerald-950/30 animate-pulse">
                            <div class="flex items-center gap-2.5">
                                <span class="text-2xl">🎁</span>
                                <div>
                                    <span class="text-xs font-black text-emerald-300 uppercase tracking-wider block">¡Pack Cóctel + Banquete Activado!</span>
                                    <span class="text-[11px] text-slate-300">Has desbloqueado una tarifa especial combinada con descuento en tu presupuesto.</span>
                                </div>
                            </div>
                            <span class="shrink-0 text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-2.5 py-1 rounded-full">
                                {{ round($this->cocktail_restaurant_pack_info['discount_percentage']) }}% Dto
                            </span>
                        </div>
                    @endif

                    <div class="space-y-3">
                        @foreach($services as $key => $service)
                            <div wire:key="service-{{ $key }}"
                                 wire:click="$toggle('services.{{ $key }}.selected')"
                                 class="relative p-5 rounded-2xl border transition-all cursor-pointer select-none {{ $service['selected'] ? 'bg-indigo-950/60 border-indigo-500/60 shadow-lg shadow-indigo-950/50 ring-1 ring-indigo-500/50' : 'bg-slate-800/40 border-slate-700/60 hover:border-slate-600 hover:bg-slate-800/70' }}">
                                
                                <div class="flex items-start gap-4">
                                    <!-- Icon Box -->
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl shrink-0 {{ $service['selected'] ? 'bg-indigo-500/20 text-indigo-300 ring-2 ring-indigo-500/30' : 'bg-slate-700/40 text-slate-400' }}">
                                        {{ $service['icon'] }}
                                    </div>

                                    <!-- Details -->
                                    <div class="flex-1 min-w-0 pr-8">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h3 class="font-bold text-base {{ $service['selected'] ? 'text-white' : 'text-slate-300' }}">
                                                {{ $service['name'] }}
                                            </h3>
                                            @if($service['selected'])
                                                @if(($key === 'cocktail' || $key === 'restaurant') && $this->has_cocktail_restaurant_pack && $this->cocktail_restaurant_pack_info['enabled'])
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/30 text-emerald-300 border border-emerald-500/40">
                                                        🎁 EN PACK CON DESCUENTO
                                                    </span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/30 text-indigo-300 border border-indigo-500/40">
                                                        INCLUIDO
                                                    </span>
                                                @endif
                                            @endif
                                        </div>
                                        <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                            {{ $service['description'] }}
                                        </p>

                                        @if($key === 'cocktail' && $service['selected'] && empty($services['restaurant']['selected']) && $this->cocktail_restaurant_pack_info['enabled'])
                                            <div class="mt-2 text-[11px] text-amber-300 font-semibold flex items-center gap-1">
                                                <span>💡</span> <span>Añade Banquete para desbloquear el precio especial de Pack Cóctel + Banquete.</span>
                                            </div>
                                        @elseif($key === 'restaurant' && $service['selected'] && empty($services['cocktail']['selected']) && $this->cocktail_restaurant_pack_info['enabled'])
                                            <div class="mt-2 text-[11px] text-amber-300 font-semibold flex items-center gap-1">
                                                <span>💡</span> <span>Añade Cóctel para desbloquear el precio especial de Pack Cóctel + Banquete.</span>
                                            </div>
                                        @endif

                                        <!-- Sub-controls (e.g. DJ Hours) if selected -->
                                        @if($key === 'dj' && $service['selected'])
                                            <div class="mt-4 pt-3 border-t border-indigo-500/20 flex items-center gap-3" wire:click.stop>
                                                <span class="text-xs font-semibold text-slate-300">Duración estimada del baile:</span>
                                                <div class="inline-flex items-center bg-slate-900/80 rounded-lg p-1 border border-indigo-500/30">
                                                    <button type="button" wire:click="$set('services.dj.quantity', {{ max(2, $service['quantity'] - 1) }})" class="w-7 h-7 flex items-center justify-center rounded bg-slate-800 hover:bg-slate-700 text-white font-bold text-sm transition">
                                                        -
                                                    </button>
                                                    <span class="px-3 text-xs font-bold text-indigo-300">{{ $service['quantity'] }} Horas</span>
                                                    <button type="button" wire:click="$set('services.dj.quantity', {{ min(8, $service['quantity'] + 1) }})" class="w-7 h-7 flex items-center justify-center rounded bg-slate-800 hover:bg-slate-700 text-white font-bold text-sm transition">
                                                        +
                                                    </button>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Checkmark Status Bubble -->
                                    <div class="absolute top-5 right-5 w-6 h-6 rounded-full flex items-center justify-center border transition-colors {{ $service['selected'] ? 'bg-indigo-500 border-indigo-400 text-white shadow-md shadow-indigo-500/40' : 'border-slate-600 bg-slate-900/40 text-transparent' }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <!-- Servicio / Efecto Especial Personalizado en Blanco -->
                        <div class="p-5 rounded-2xl border transition-all select-none {{ $custom_service_selected ? 'bg-indigo-950/60 border-indigo-500/60 shadow-lg shadow-indigo-950/50 ring-1 ring-indigo-500/50' : 'bg-slate-800/40 border-slate-700/60 hover:border-slate-600 hover:bg-slate-800/70' }}">
                            <div class="flex items-start gap-4 cursor-pointer" wire:click="$toggle('custom_service_selected')">
                                <!-- Icon Box -->
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl shrink-0 {{ $custom_service_selected ? 'bg-indigo-500/20 text-indigo-300 ring-2 ring-indigo-500/30' : 'bg-slate-700/40 text-slate-400' }}">
                                    ✨
                                </div>

                                <!-- Details -->
                                <div class="flex-1 min-w-0 pr-8">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="font-bold text-base {{ $custom_service_selected ? 'text-white' : 'text-slate-300' }}">
                                            ¿Quieres añadir otro servicio o efecto especial?
                                        </h3>
                                        @if($custom_service_selected)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/30 text-amber-300 border border-amber-500/40">
                                                A CONSULTAR
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                        Fuego frío, máquinas de humo bajo, plataforma 360, iluminación decorativa de jardín... Dinos qué necesitas y te lo presupuestamos a medida.
                                    </p>
                                </div>

                                <!-- Checkmark Status Bubble -->
                                <div class="w-6 h-6 rounded-full flex items-center justify-center border transition-colors shrink-0 {{ $custom_service_selected ? 'bg-indigo-500 border-indigo-400 text-white shadow-md shadow-indigo-500/40' : 'border-slate-600 bg-slate-900/40 text-transparent' }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </div>

                            @if($custom_service_selected)
                                <div class="mt-4 pt-4 border-t border-indigo-500/20 space-y-3" wire:click.stop>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-300 mb-1">Nombre del servicio o extra deseado *</label>
                                        <input type="text" wire:model.defer="custom_service_name" placeholder="Ej: Fuego frío para el baile / Máquina de humo bajo / Plataforma 360"
                                               class="w-full bg-slate-900/80 border border-indigo-500/40 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-300 mb-1">Detalles o especificaciones (opcional)</label>
                                        <textarea wire:model.defer="custom_service_description" rows="2" placeholder="Momento en el que te gustaría incluirlo, horario o cualquier detalle que nos ayude a cotizarlo..."
                                                  class="w-full bg-slate-900/80 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Right Side: Contact & Event Form -->
                <div class="lg:col-span-5">
                    <div class="bg-slate-800/70 backdrop-blur-xl border border-slate-700/80 rounded-3xl p-6 sm:p-7 shadow-2xl sticky top-8">
                        <h2 class="text-lg font-bold text-white flex items-center gap-2 mb-1">
                            <span>2.</span> Datos del Evento
                        </h2>
                        <p class="text-xs text-slate-400 mb-5">
                            Indícanos dónde y cuándo será para comprobar disponibilidad de fecha y técnicos.
                        </p>

                        <form wire:submit.prevent="submitRequest" class="space-y-4">
                            <!-- Name -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">{{ $currentType['name_label'] }}</label>
                                <input type="text" wire:model.defer="client_name" placeholder="{{ $currentType['name_placeholder'] }}"
                                       class="w-full bg-slate-900/80 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                @error('client_name') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Phone & Email Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">Teléfono / WhatsApp *</label>
                                    <input type="tel" wire:model.defer="client_phone" placeholder="600 000 000"
                                           class="w-full bg-slate-900/80 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                    @error('client_phone') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">Email *</label>
                                    <input type="email" wire:model.defer="client_email" placeholder="email@ejemplo.com"
                                           class="w-full bg-slate-900/80 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                    @error('client_email') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Date & Location Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">Fecha del evento *</label>
                                    <input type="date" wire:model.defer="event_date"
                                           class="w-full bg-slate-900/80 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition [color-scheme:dark]">
                                    @error('event_date') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">Lugar / Finca *</label>
                                    <input type="text" wire:model.defer="event_location" placeholder="Finca, Restaurante..."
                                           class="w-full bg-slate-900/80 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                    @error('event_location') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">Detalles adicionales u observaciones (opcional)</label>
                                <textarea wire:model.defer="client_notes" rows="3" placeholder="Horarios aproximados, estilo musical preferido, si el cóctel es al aire libre..."
                                          class="w-full bg-slate-900/80 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition resize-none"></textarea>
                                @error('client_notes') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Privacy / Assurance Note -->
                            <div class="flex items-center gap-2 py-1 text-xs text-slate-400">
                                <span class="text-emerald-400 text-sm">🔒</span>
                                <span>Tus datos son 100% confidenciales. Sin spam.</span>
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
        <div class="mt-16 pt-8 border-t border-slate-800 text-center text-xs text-slate-500 flex flex-wrap items-center justify-center gap-6">
            <span class="flex items-center gap-1.5">🎧 <span>Sonido Profesional de Alta Fidelidad</span></span>
            <span class="flex items-center gap-1.5">💡 <span>Iluminación Robotizada y Ambiental</span></span>
            <span class="flex items-center gap-1.5">🎵 <span>Repertorio 100% Personalizado</span></span>
            <span class="flex items-center gap-1.5">📑 <span>Contrato y Garantía de Servicio</span></span>
        </div>

    </div>
</div>
