@php
    $gatewayActive = \App\Services\EvolutionWhatsAppService::isEnabled();
    $clientPhone = $event->client ? preg_replace('/[^0-9]/', '', $event->client->phone) : '';
    if (strlen($clientPhone) === 9 && in_array(substr($clientPhone, 0, 1), ['6', '7'])) {
        $clientPhone = '34' . $clientPhone;
    }
    
    $djPhone = $event->dj ? preg_replace('/[^0-9]/', '', $event->dj->phone) : '';
    if (strlen($djPhone) === 9 && in_array(substr($djPhone, 0, 1), ['6', '7'])) {
        $djPhone = '34' . $djPhone;
    }

    $assistantPhone = $event->assistant ? preg_replace('/[^0-9]/', '', $event->assistant->phone) : '';
    if (strlen($assistantPhone) === 9 && in_array(substr($assistantPhone, 0, 1), ['6', '7'])) {
        $assistantPhone = '34' . $assistantPhone;
    }

    $venuePhone = $event->venue_contact_phone ? preg_replace('/[^0-9]/', '', $event->venue_contact_phone) : '';
    if (strlen($venuePhone) === 9 && in_array(substr($venuePhone, 0, 1), ['6', '7'])) {
        $venuePhone = '34' . $venuePhone;
    }

    $latestQuote = $event->quotes()->latest()->first();
    $quoteAmount = $latestQuote ? (float)$latestQuote->amount : 0;
    
    if ($latestQuote) {
        $signalAmount = $latestQuote->signal_amount;
        $remainingAmount = $latestQuote->remaining_amount;
        $signalLabel = $latestQuote->signal_label;
    } else {
        $depType = \App\Models\Setting::get('deposit_type', 'percentage');
        if ($depType === 'fixed') {
            $signalAmount = min((float)\App\Models\Setting::get('deposit_fixed_amount', 200), $quoteAmount);
            $signalLabel = number_format($signalAmount, 2, ',', '.') . ' €';
        } else {
            $pct = \App\Models\Quote::calculateGradualDepositPercentage($event->event_date);
            $signalAmount = round(($quoteAmount * $pct) / 100, 2);
            $signalLabel = rtrim(rtrim(number_format($pct, 2, ',', '.'), '0'), ',') . '% (' . number_format($signalAmount, 2, ',', '.') . ' €)';
        }
        $remainingAmount = max(0, $quoteAmount - $signalAmount);
    }

    $iban = \App\Models\Setting::get('company_iban', '');
    $bizum = \App\Models\Setting::get('company_bizum', '');
    $companyName = \App\Models\Setting::get('company_name', 'Núñez & Son');

    // Desglose de propuesta comercial para WhatsApp
    $quoteSummaryLines = [];
    if ($latestQuote && $latestQuote->items) {
        foreach ($latestQuote->items as $item) {
            $quoteSummaryLines[] = "• " . $item->service_name . ($item->quantity > 1 ? " ({$item->quantity}x)" : "") . ": " . number_format($item->price * $item->quantity, 2, ',', '.') . " €";
        }
    }
    $quoteItemsText = implode("\n", $quoteSummaryLines);
    $quoteValidityNotice = "";
    if ($latestQuote && $latestQuote->expires_at) {
        $quoteValidityNotice = "\n\n⏳ *VALIDEZ DE LA OFERTA:*\nPropuesta válida hasta el *" . $latestQuote->expires_at->format('d/m/Y') . "* (" . $latestQuote->validity_days . " días naturales). Pasada esta fecha, tarifas y disponibilidad quedan sujetas a revisión.";
    }
    $eventDateFormatted = $event->event_date ? \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') : 'fecha por definir';
    $quoteOnlineNotice = ($latestQuote && $latestQuote->public_url) ? "\n\n🌐 *VER PROPUESTA ONLINE (Descargar PDF / Aceptar o Rechazar):*\n" . $latestQuote->public_url : "";
    $quotePropuestaMsg = "¡Hola " . ($event->client ? $event->client->name : '') . "! 👋\n\nOs compartimos la propuesta detallada para vuestro evento *" . $event->name . "* (" . $eventDateFormatted . " en " . $event->location . "):\n\n" . ($quoteItemsText ?: '• Servicios musicales y sonorización profesional.') . "\n\n💰 *TOTAL PRESUPUESTO:* " . number_format($quoteAmount, 2, ',', '.') . " €\n\n📌 *CONDICIONES DE PAGO:*\n• Señal de reserva (" . $signalLabel . "): " . number_format($signalAmount, 2, ',', '.') . " €\n• Restante el día del evento: " . number_format($remainingAmount, 2, ',', '.') . " €" . $quoteOnlineNotice . $quoteValidityNotice . "\n\nCualquier duda o ajuste, estamos a vuestra entera disposición. ¡Un saludo!";
    $quotePropuestaWaUrl = \App\Services\WhatsAppTemplateService::getLink($clientPhone, $quotePropuestaMsg);

    $signalMsg = "¡Hola " . ($event->client ? $event->client->name : '') . "! 👋\n\nPara confirmar la fecha de vuestro evento *" . $event->name . "* (" . $eventDateFormatted . "), podéis abonar la *Señal de reserva (" . number_format($signalAmount, 2, ',', '.') . " €)* mediante:\n\n" . ($iban ? "🏦 *Transferencia Bancaria:*\n" . $iban . "\n\n" : "") . ($bizum ? "📱 *Bizum:* " . $bizum . "\n\n" : "") . "Concepto: " . $event->name . "\n\n¡Muchas gracias y quedamos a vuestra disposición!";
    $signalWaUrl = \App\Services\WhatsAppTemplateService::getLink($clientPhone, $signalMsg);

    $cuestionarioMsg = \App\Services\WhatsAppTemplateService::cuestionario($event);
    $cuestionarioWaUrl = \App\Services\WhatsAppTemplateService::getLink($clientPhone, $cuestionarioMsg);
    
    $firstContract = $event->contracts()->latest()->first();
    $contractMsg = $firstContract ? \App\Services\WhatsAppTemplateService::contrato($firstContract) : '';
    $contractWaUrl = $firstContract ? \App\Services\WhatsAppTemplateService::getLink($clientPhone, $contractMsg) : '';

    $liquidacionMsg = \App\Services\WhatsAppTemplateService::liquidacion($event);
    $liquidacionWaUrl = \App\Services\WhatsAppTemplateService::getLink($clientPhone, $liquidacionMsg);

    // Mensajes tácticos de seguimiento si el cliente no responde (Técnica del NO y Psicología Inversa)
    $followupNoMsg = \App\Services\WhatsAppTemplateService::seguimientoPreguntaNo($event);
    $followupNoWaUrl = \App\Services\WhatsAppTemplateService::getLink($clientPhone, $followupNoMsg);

    $followupReverseMsg = \App\Services\WhatsAppTemplateService::seguimientoPsicologiaInversa($event);
    $followupReverseWaUrl = \App\Services\WhatsAppTemplateService::getLink($clientPhone, $followupReverseMsg);

    $finPlazoMsg = \App\Services\WhatsAppTemplateService::finPlazoPresupuesto($event);
    $finPlazoWaUrl = \App\Services\WhatsAppTemplateService::getLink($clientPhone, $finPlazoMsg);

    // Nuevos Guiones de Venta Bodas (Embudo Madrigal)
    // 1. Apertura y Primera Toma de Contacto (Etapa 1)
    $contactDispoMsg = \App\Services\WhatsAppTemplateService::primeraTomaContacto($event, 'disponibilidad');
    $contactDispoWaUrl = \App\Services\WhatsAppTemplateService::getLink($clientPhone, $contactDispoMsg);

    $contactFincaMsg = \App\Services\WhatsAppTemplateService::primeraTomaContacto($event, 'filtro_finca');
    $contactFincaWaUrl = \App\Services\WhatsAppTemplateService::getLink($clientPhone, $contactFincaMsg);

    $contactEstandarMsg = \App\Services\WhatsAppTemplateService::primeraTomaContacto($event, 'estandar');
    $contactEstandarWaUrl = \App\Services\WhatsAppTemplateService::getLink($clientPhone, $contactEstandarMsg);

    // 2. Conversación en curso y Propuesta de llamada (Etapas 2 y 3)
    $followupChatMsg = \App\Services\WhatsAppTemplateService::seguimientoConversacion($event, 'suave');
    $followupChatWaUrl = \App\Services\WhatsAppTemplateService::getLink($clientPhone, $followupChatMsg);

    $propuestaLlamadaMsg = \App\Services\WhatsAppTemplateService::propuestaLlamada($event);
    $propuestaLlamadaWaUrl = \App\Services\WhatsAppTemplateService::getLink($clientPhone, $propuestaLlamadaMsg);

    // 3. Envío de Dossier con Presupuesto mascadito y Seguimiento (Etapa 4)
    $envioDossierMsg = \App\Services\WhatsAppTemplateService::envioDossierPresupuesto($event, $latestQuote);
    $envioDossierWaUrl = \App\Services\WhatsAppTemplateService::getLink($clientPhone, $envioDossierMsg);

    $followupPostQuoteMsg = \App\Services\WhatsAppTemplateService::seguimientoPostPresupuesto($event);
    $followupPostQuoteWaUrl = \App\Services\WhatsAppTemplateService::getLink($clientPhone, $followupPostQuoteMsg);

    $followupBudgetMsg = \App\Services\WhatsAppTemplateService::seguimientoPresupuestoNo($event);
    $followupBudgetWaUrl = \App\Services\WhatsAppTemplateService::getLink($clientPhone, $followupBudgetMsg);

    $djMsg = \App\Services\WhatsAppTemplateService::hojaRutaDj($event);
    $djWaUrl = \App\Services\WhatsAppTemplateService::getLink($djPhone, $djMsg);
    $astWaUrl = \App\Services\WhatsAppTemplateService::getLink($assistantPhone, $djMsg);
@endphp

<div x-data="{ openWhatsappHub: false, openQrModal: false }">
    <!-- ============================================================== -->
    <!-- CABECERA PRINCIPAL DEL EVENTO: ESTRUCTURA LIMPIA Y ORDENADA   -->
    <!-- ============================================================== -->
    <div class="mb-6 space-y-3">
        <!-- 1. Barra Superior: Navegación y Acciones Clave -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <!-- Migas de pan / Volver -->
            <div class="flex items-center gap-2 text-xs">
                <a href="{{ route('admin.events') }}" class="inline-flex items-center gap-1.5 font-bold text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition">
                    <span>←</span>
                    <span>Agenda de Eventos</span>
                </a>
                <span class="text-slate-300 dark:text-slate-700">/</span>
                <span class="font-extrabold text-slate-800 dark:text-slate-200 truncate max-w-[220px] sm:max-w-none">{{ $event->name }}</span>
            </div>

            <!-- Botones de Acción de Cabecera (Agrupados y Alineados) -->
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.events.live', $event->id) }}" class="inline-flex items-center gap-1.5 bg-slate-900 hover:bg-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 text-amber-400 font-bold px-3.5 py-2 rounded-xl text-xs border border-slate-700/60 transition shadow-xs">
                    <span>🎧</span>
                    <span class="text-white">Modo Cabina DJ</span>
                </a>
                <button type="button" @click="openQrModal = true" class="inline-flex items-center gap-1.5 bg-gradient-to-r from-pink-600 to-indigo-600 hover:from-pink-700 hover:to-indigo-700 text-white font-bold px-3 py-2 rounded-xl text-xs shadow-xs transition cursor-pointer">
                    <span>📱</span>
                    <span>QR Invitados</span>
                </button>
                <button type="button" @click="openWhatsappHub = true" class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3.5 py-2 rounded-xl text-xs shadow-xs transition cursor-pointer">
                    <span>💬</span>
                    <span>WhatsApp Hub</span>
                </button>

                @if(auth()->user()->role === 'admin')
                    <!-- Menú de Acciones Administrativas (Discreto para no recargar) -->
                    <div class="relative" x-data="{ openMoreMenu: false }">
                        <button type="button" @click="openMoreMenu = !openMoreMenu" class="p-2 rounded-xl bg-white hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 transition cursor-pointer" title="Opciones avanzadas">
                            <span class="text-xs font-black">⚙️</span>
                        </button>
                        <div x-show="openMoreMenu" @click.away="openMoreMenu = false" class="absolute right-0 mt-1.5 w-48 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl z-30 p-1 divide-y divide-slate-100 dark:divide-slate-800 text-xs animate-in fade-in zoom-in-95 duration-100" style="display: none;">
                            <div class="p-1">
                                <button type="button" wire:click="openEditEventModal" @click="openMoreMenu = false" class="w-full text-left px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 font-semibold flex items-center gap-2 cursor-pointer">
                                    <span>✏️</span> Editar Datos Básicos
                                </button>
                                <button type="button" wire:click="openScheduleModal" @click="openMoreMenu = false" class="w-full text-left px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 font-semibold flex items-center gap-2 cursor-pointer">
                                    <span>⏰</span> Horarios & Montaje
                                </button>
                                <button type="button" wire:click="openStaffModal" @click="openMoreMenu = false" class="w-full text-left px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 font-semibold flex items-center gap-2 cursor-pointer">
                                    <span>👥</span> Asignar Personal
                                </button>
                            </div>
                            <div class="p-1">
                                <button type="button" wire:click="deleteEvent" wire:confirm="⚠️ ¿Estás seguro de que deseas eliminar permanentemente el evento '{{ $event->name }}' y todos sus presupuestos, contratos y música?" @click="openMoreMenu = false" class="w-full text-left px-3 py-2 rounded-xl hover:bg-rose-50 dark:hover:bg-rose-950/40 text-rose-600 dark:text-rose-400 font-semibold flex items-center gap-2 cursor-pointer">
                                    <span>🗑️</span> Eliminar Evento
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- 2. Tarjeta Principal: Título, Selectores y Panel de Datos Organizado -->
        <div x-data="{ showHeaderCards: {{ $activeTab === 'quotes' ? 'false' : 'true' }} }" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3.5 sm:p-4 shadow-sm transition">
            
            <!-- Fila A: Título del Evento e Identificadores (Marca, Tipo, Estado Único) -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 {{ $activeTab === 'quotes' ? '' : 'pb-3 border-b border-slate-100 dark:border-slate-800/80' }}" :class="showHeaderCards ? 'pb-3 border-b border-slate-100 dark:border-slate-800/80' : ''">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight">
                        {{ $event->name }}
                    </h1>
                    <button type="button" wire:click="openEditEventModal" class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-xl font-bold bg-slate-100 hover:bg-indigo-50 dark:bg-slate-800 dark:hover:bg-indigo-950/50 text-slate-700 hover:text-indigo-700 dark:text-slate-300 dark:hover:text-indigo-300 border border-slate-200 dark:border-slate-700 transition cursor-pointer" title="Editar nombre, fecha, lugar o notas">
                        <span>✏️</span> Editar
                    </button>
                    <!-- Resumen rápido cuando las tarjetas están plegadas -->
                    <div x-show="!showHeaderCards" class="hidden sm:inline-flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-800/70 px-2.5 py-1 rounded-xl border border-slate-200 dark:border-slate-700">
                        <span>👥 <strong>{{ $event->client ? $event->client->name : 'Sin cliente' }}</strong></span>
                        <span>&bull;</span>
                        <span>📅 <strong>{{ \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') }}</strong></span>
                        @if($event->location)
                            <span>&bull;</span>
                            <span class="truncate max-w-[180px]">📍 {{ $event->location }}</span>
                        @endif
                    </div>
                    <button type="button" @click="showHeaderCards = !showHeaderCards" class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition cursor-pointer">
                        <span x-text="showHeaderCards ? '🔼 Compactar cabecera' : '🔽 Ver ficha completa'"></span>
                    </button>
                </div>

                <!-- Selectores de Control (Marca, Tipo y Estado ÚNICO sin barra duplicada) -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Selector de Marca Comercial -->
                    <div class="relative" x-data="{ openBrandDropdown: false }">
                        <button type="button" @click="openBrandDropdown = !openBrandDropdown" class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-xl font-bold bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/50 dark:hover:bg-amber-900/60 text-amber-900 dark:text-amber-200 border border-amber-300 dark:border-amber-800 shadow-2xs transition cursor-pointer" title="Cambiar marca comercial">
                            <span>{{ $event->brand_icon }}</span>
                            <span>{{ $event->brand_label }}</span>
                            <span class="text-[9px] opacity-70">▼</span>
                        </button>
                        <div x-show="openBrandDropdown" @click.away="openBrandDropdown = false" class="absolute right-0 lg:right-auto lg:left-0 mt-1.5 w-60 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl z-30 p-1.5 divide-y divide-slate-100 dark:divide-slate-800 text-xs animate-in fade-in zoom-in-95 duration-100" style="display: none;">
                            <div class="space-y-0.5">
                                <button type="button" wire:click="changeBrand('nunez_and_son')" @click="openBrandDropdown = false" class="w-full text-left px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold flex items-center gap-2 cursor-pointer {{ $event->brand_clean === 'nunez_and_son' ? 'bg-amber-50 dark:bg-amber-950/50 text-amber-900 dark:text-amber-200 font-bold' : 'text-slate-700 dark:text-slate-300' }}">
                                    <span class="text-base">👑</span>
                                    <div>
                                        <div class="font-bold">Núñez and Son</div>
                                        <div class="text-[10px] text-slate-500">DJ & Sonido (Bodas & Eventos)</div>
                                    </div>
                                </button>
                                <button type="button" wire:click="changeBrand('javnx')" @click="openBrandDropdown = false" class="w-full text-left px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold flex items-center gap-2 cursor-pointer {{ $event->brand_clean === 'javnx' ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-900 dark:text-indigo-200 font-bold' : 'text-slate-700 dark:text-slate-300' }}">
                                    <span class="text-base">🎧</span>
                                    <div>
                                        <div class="font-bold">JAVNX DJ</div>
                                        <div class="text-[10px] text-slate-500">DJ & Producción de Eventos</div>
                                    </div>
                                </button>
                                <button type="button" wire:click="changeBrand('mago_leugim')" @click="openBrandDropdown = false" class="w-full text-left px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold flex items-center gap-2 cursor-pointer {{ $event->brand_clean === 'mago_leugim' ? 'bg-purple-50 dark:bg-purple-950/50 text-purple-900 dark:text-purple-200 font-bold' : 'text-slate-700 dark:text-slate-300' }}">
                                    <span class="text-base">🎩</span>
                                    <div>
                                        <div class="font-bold">Mago Leugim</div>
                                        <div class="text-[10px] text-slate-500">Ilusionismo & Magia Profesional</div>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Selector de Tipo de Evento -->
                    <div class="relative" x-data="{ openTypeDropdown: false }">
                        <button type="button" @click="openTypeDropdown = !openTypeDropdown" class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-xl font-bold bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/50 dark:hover:bg-indigo-900/60 text-indigo-800 dark:text-indigo-200 border border-indigo-200 dark:border-indigo-800 shadow-2xs transition cursor-pointer" title="Cambiar tipo de evento">
                            <span>{{ $event->event_type_icon }}</span>
                            <span>{{ $event->event_type_label }}</span>
                            <span class="text-[9px] opacity-70">▼</span>
                        </button>
                        <div x-show="openTypeDropdown" @click.away="openTypeDropdown = false" class="absolute right-0 lg:right-auto lg:left-0 mt-1.5 w-56 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl z-30 p-1.5 divide-y divide-slate-100 dark:divide-slate-800 text-xs animate-in fade-in zoom-in-95 duration-100" style="display: none;">
                            <div class="space-y-0.5">
                                <button type="button" wire:click="changeEventType('boda')" @click="openTypeDropdown = false" class="w-full text-left px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold flex items-center gap-2 cursor-pointer {{ $event->event_type_clean === 'boda' ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-bold' : 'text-slate-700 dark:text-slate-300' }}">
                                    <span>💍</span> Boda / Enlace
                                </button>
                                @if($event->event_type_clean === 'video' || \App\Models\Setting::isVideoServiceEnabled($event->brand_clean))
                                    <button type="button" wire:click="changeEventType('video')" @click="openTypeDropdown = false" class="w-full text-left px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold flex items-center gap-2 cursor-pointer {{ $event->event_type_clean === 'video' ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-bold' : 'text-slate-700 dark:text-slate-300' }}">
                                        <span>📹</span> Vídeo / Videógrafo
                                    </button>
                                @endif
                                <button type="button" wire:click="changeEventType('empresa')" @click="openTypeDropdown = false" class="w-full text-left px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold flex items-center gap-2 cursor-pointer {{ $event->event_type_clean === 'empresa' ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-bold' : 'text-slate-700 dark:text-slate-300' }}">
                                    <span>🏢</span> Evento de Empresa
                                </button>
                                <button type="button" wire:click="changeEventType('cumpleanos')" @click="openTypeDropdown = false" class="w-full text-left px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold flex items-center gap-2 cursor-pointer {{ $event->event_type_clean === 'cumpleanos' ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-bold' : 'text-slate-700 dark:text-slate-300' }}">
                                    <span>🎂</span> Cumpleaños / Aniversario
                                </button>
                                <button type="button" wire:click="changeEventType('comunion')" @click="openTypeDropdown = false" class="w-full text-left px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold flex items-center gap-2 cursor-pointer {{ $event->event_type_clean === 'comunion' ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-bold' : 'text-slate-700 dark:text-slate-300' }}">
                                    <span>🕊️</span> Comunión
                                </button>
                                <button type="button" wire:click="changeEventType('otro')" @click="openTypeDropdown = false" class="w-full text-left px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold flex items-center gap-2 cursor-pointer {{ $event->event_type_clean === 'otro' ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-bold' : 'text-slate-700 dark:text-slate-300' }}">
                                    <span>🎉</span> Fiesta Privada / Otro
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Selector ÚNICO de Estado del Evento -->
                    <div class="relative" x-data="{ openStatusDropdown: false }">
                        <button type="button" @click="openStatusDropdown = !openStatusDropdown" class="inline-flex items-center gap-1.5 text-xs px-3.5 py-1.5 rounded-xl font-black transition cursor-pointer shadow-2xs border {{ $event->status_badge_class }}" title="Cambiar estado del evento">
                            <span>{{ $event->status_icon }}</span>
                            <span>{{ $event->status_label }}</span>
                            <span class="text-[9px] opacity-70">▼</span>
                        </button>
                        <div x-show="openStatusDropdown" @click.away="openStatusDropdown = false" class="absolute right-0 mt-1.5 w-60 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl z-30 p-1.5 divide-y divide-slate-100 dark:divide-slate-800 text-xs animate-in fade-in zoom-in-95 duration-100" style="display: none;">
                            <div class="space-y-0.5">
                                <button type="button" wire:click="changeStatus('draft')" @click="openStatusDropdown = false" class="w-full text-left px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold flex items-center gap-2 cursor-pointer {{ $event->status === 'draft' ? 'bg-amber-50 dark:bg-amber-950/50 text-amber-800 dark:text-amber-300 font-bold' : 'text-slate-700 dark:text-slate-300' }}">
                                    <span>📝</span> Borrador / Presupuesto
                                </button>
                                <button type="button" wire:click="changeStatus('no_response')" @click="openStatusDropdown = false" class="w-full text-left px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold flex items-center gap-2 cursor-pointer {{ $event->status === 'no_response' ? 'bg-purple-50 dark:bg-purple-950/50 text-purple-800 dark:text-purple-300 font-bold' : 'text-slate-700 dark:text-slate-300' }}">
                                    <span>⏳</span> Sin Respuesta (Esperando)
                                </button>
                                <button type="button" wire:click="changeStatus('on_hold')" @click="openStatusDropdown = false" class="w-full text-left px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold flex items-center gap-2 cursor-pointer {{ $event->status === 'on_hold' ? 'bg-cyan-50 dark:bg-cyan-950/50 text-cyan-800 dark:text-cyan-300 font-bold' : 'text-slate-700 dark:text-slate-300' }}">
                                    <span>⏸️</span> En Espera (Ya avisarán)
                                </button>
                                <button type="button" wire:click="changeStatus('confirmed')" @click="openStatusDropdown = false" class="w-full text-left px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold flex items-center gap-2 cursor-pointer {{ $event->status === 'confirmed' ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-800 dark:text-emerald-300 font-bold' : 'text-slate-700 dark:text-slate-300' }}">
                                    <span>🟢</span> Confirmado (Señal Pagada)
                                </button>
                                <button type="button" wire:click="changeStatus('completed')" @click="openStatusDropdown = false" class="w-full text-left px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold flex items-center gap-2 cursor-pointer {{ $event->status === 'completed' ? 'bg-blue-50 dark:bg-blue-950/50 text-blue-800 dark:text-blue-300 font-bold' : 'text-slate-700 dark:text-slate-300' }}">
                                    <span>🔵</span> Completado (Finalizado)
                                </button>
                                <button type="button" wire:click="changeStatus('rejected')" @click="openStatusDropdown = false" class="w-full text-left px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold flex items-center gap-2 cursor-pointer {{ $event->status === 'rejected' ? 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-bold' : 'text-slate-700 dark:text-slate-300' }}">
                                    <span>❌</span> Rechazado (No Acepta)
                                </button>
                                <button type="button" wire:click="changeStatus('cancelled')" @click="openStatusDropdown = false" class="w-full text-left px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold flex items-center gap-2 cursor-pointer {{ $event->status === 'cancelled' ? 'bg-rose-50 dark:bg-rose-950/50 text-rose-800 dark:text-rose-300 font-bold' : 'text-slate-700 dark:text-slate-300' }}">
                                    <span>🔴</span> Cancelado (Anulación)
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Pastillas Financieras: Señal / Pendiente (Si aplica) -->
                    @if(($event->deposit_paid ?? false) && (float)($event->deposit_paid_amount ?? 0) > 0)
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-800 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-300 dark:border-emerald-800 px-3 py-1.5 rounded-xl shadow-2xs">
                            <span>{{ $event->deposit_method_icon }}</span>
                            <span>Señal: <strong>{{ number_format($event->deposit_paid_amount, 2, ',', '.') }} €</strong></span>
                            <button type="button" wire:click="openDepositModal('confirmed')" class="text-[11px] text-emerald-700 dark:text-emerald-400 hover:underline font-bold ml-1 cursor-pointer" title="Modificar señal">
                                ✏️
                            </button>
                        </span>
                        @if($quoteAmount > 0)
                            @php
                                $realRemaining = max(0, $quoteAmount - (float)($event->deposit_paid_amount ?? 0));
                            @endphp
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 px-3 py-1.5 rounded-xl">
                                <span>Pendiente:</span>
                                <strong class="{{ $realRemaining == 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white' }}">
                                    {{ number_format($realRemaining, 2, ',', '.') }} €
                                </strong>
                                @if($realRemaining == 0)
                                    <span class="text-[10px] bg-emerald-200 dark:bg-emerald-800 text-emerald-800 dark:text-emerald-200 font-black px-1.5 py-0.5 rounded-md">¡Pagado!</span>
                                @elseif($event->status === 'confirmed')
                                    <button type="button" wire:click="openFinalPaymentModal" class="text-[10px] font-bold text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/50 bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800 px-1.5 py-0.5 rounded ml-1 transition cursor-pointer" title="Cobrar restante y completar evento">
                                        🔵 Cobrar
                                    </button>
                                @endif
                            </span>
                        @endif
                    @elseif($event->status === 'confirmed')
                        <button type="button" wire:click="openDepositModal('confirmed')" class="inline-flex items-center gap-1 text-xs font-bold text-amber-800 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/50 hover:bg-amber-100 dark:hover:bg-amber-900/60 border border-amber-300 dark:border-amber-800 px-3 py-1.5 rounded-xl shadow-2xs transition cursor-pointer" title="Registrar el cobro de la señal">
                            <span>💳 + Registrar Señal</span>
                        </button>
                    @endif
                </div>
            </div>

            <!-- Fila B: Panel de Datos Organizado en 4 Bloques (Grid Limpio) -->
            <div x-show="showHeaderCards" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-3.5">
                
                <!-- Bloque 1: Pareja / Clientes -->
                <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-3.5 flex flex-col justify-between gap-2.5">
                    <div>
                        <div class="flex items-center justify-between text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                            <span class="flex items-center gap-1">👥 {{ $event->is_wedding ? 'Pareja / Clientes' : 'Cliente / Contacto' }}</span>
                            <button type="button" wire:click="openClientModal" class="text-indigo-600 dark:text-indigo-400 hover:underline font-bold text-xs cursor-pointer" title="Editar datos del cliente">
                                ✏️
                            </button>
                        </div>
                        @if($event->client || $event->partner_name)
                            <div class="space-y-1.5 text-xs">
                                <div class="flex items-center justify-between gap-1.5">
                                    <span class="font-extrabold text-slate-800 dark:text-slate-200 truncate" title="{{ $event->client ? $event->client->name : 'Sin cliente principal' }}">
                                        {{ $event->client ? $event->client->name : 'Sin cliente principal' }}
                                    </span>
                                    @if($clientPhone)
                                        <a href="https://wa.me/{{ $clientPhone }}" target="_blank" class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-100 hover:bg-emerald-200 dark:bg-emerald-950 dark:border dark:border-emerald-800 px-1.5 py-0.5 rounded transition shrink-0" title="WhatsApp Cliente Principal ({{ $event->client->phone }})">
                                            💬 WA
                                        </a>
                                    @endif
                                </div>
                                @if($event->partner_name)
                                    <div class="flex items-center justify-between gap-1.5 pt-1 border-t border-slate-200/60 dark:border-slate-700/60">
                                        <span class="font-bold text-pink-700 dark:text-pink-300 truncate" title="{{ $event->partner_name }}">
                                            💍 {{ $event->partner_name }}
                                        </span>
                                        @if($event->partner_clean_phone)
                                            <a href="https://wa.me/{{ $event->partner_clean_phone }}" target="_blank" class="inline-flex items-center gap-1 text-[10px] font-bold text-pink-700 dark:text-pink-300 bg-pink-100 hover:bg-pink-200 dark:bg-pink-950 dark:border dark:border-pink-800 px-1.5 py-0.5 rounded transition shrink-0" title="WhatsApp Pareja ({{ $event->partner_phone }})">
                                                💬 WA
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @else
                            <button type="button" wire:click="openClientModal" class="w-full py-2 border border-dashed border-slate-300 dark:border-slate-700 rounded-xl text-center text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition cursor-pointer">
                                + Asociar Cliente / Pareja
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Bloque 2: Fecha y Horarios (Montaje & Baile 24h) -->
                <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-3.5 flex flex-col justify-between gap-2.5">
                    <div>
                        <div class="flex items-center justify-between text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                            <span class="flex items-center gap-1">⏰ Horarios & Logística</span>
                            <button type="button" wire:click="openScheduleModal" class="text-indigo-600 dark:text-indigo-400 hover:underline font-bold text-xs cursor-pointer" title="Configurar horarios de montaje y baile">
                                ✏️
                            </button>
                        </div>
                        <div class="space-y-1.5 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 dark:text-slate-400">📅 Fecha:</span>
                                <strong class="text-slate-800 dark:text-slate-200">{{ \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') }}</strong>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 dark:text-slate-400">🚗 Montaje:</span>
                                <strong class="text-amber-800 dark:text-amber-300 truncate max-w-[140px] text-right" title="{{ $event->setup_schedule_label }}">
                                    {{ $event->setup_schedule_label }}
                                </strong>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 dark:text-slate-400">⏰ Baile (24h):</span>
                                <strong class="text-purple-800 dark:text-purple-300 truncate max-w-[140px] text-right" title="{{ $event->dance_schedule_label }}">
                                    {{ $event->dance_schedule_label }}
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bloque 3: Personal Técnico / Videógrafo -->
                <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-3.5 flex flex-col justify-between gap-2.5">
                    <div>
                        <div class="flex items-center justify-between text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                            <span class="flex items-center gap-1">
                                {{ $event->event_type_clean === 'video' ? '📹 Responsable Vídeo' : '🎧 Personal Técnico' }}
                            </span>
                            @if($event->event_type_clean !== 'video')
                            <button type="button" wire:click="openStaffModal" class="text-indigo-600 dark:text-indigo-400 hover:underline font-bold text-xs cursor-pointer" title="Asignar o cambiar DJ y asistentes">
                                ✏️
                            </button>
                            @endif
                        </div>
                        @if($event->event_type_clean === 'video')
                        <div class="space-y-1.5 text-xs">
                            <div class="flex items-center justify-between gap-1">
                                <span class="text-slate-500 dark:text-slate-400">Videógrafo:</span>
                                <strong class="text-indigo-700 dark:text-indigo-300 truncate">
                                    {{ $event->dj ? $event->dj->name : auth()->user()->name }}
                                </strong>
                            </div>
                            <div class="text-[11px] text-purple-700 dark:text-purple-300 font-semibold bg-purple-50 dark:bg-purple-950/40 p-1.5 rounded-lg border border-purple-100 dark:border-purple-900/40">
                                📹 Cobertura audiovisual y grabación
                            </div>
                        </div>
                        @else
                        <div class="space-y-1.5 text-xs">
                            <div class="flex items-center justify-between gap-1">
                                <span class="text-slate-500 dark:text-slate-400">DJ:</span>
                                <div class="flex items-center gap-1 truncate">
                                    <strong class="text-indigo-700 dark:text-indigo-300 truncate" title="{{ $event->dj ? $event->dj->name : 'Sin asignar' }}">
                                        {{ $event->dj ? $event->dj->name : 'Sin asignar' }}
                                    </strong>
                                    @if($djPhone)
                                        <a href="https://wa.me/{{ $djPhone }}" target="_blank" class="text-[10px] text-emerald-600 hover:text-emerald-700 font-bold" title="WhatsApp DJ">
                                            💬
                                        </a>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-1">
                                <span class="text-slate-500 dark:text-slate-400">Asistente:</span>
                                <div class="flex items-center gap-1 truncate">
                                    @php
                                        $allAssignedAssistants = $event->all_assistants;
                                    @endphp
                                    @if($allAssignedAssistants->isNotEmpty())
                                        <strong class="text-amber-800 dark:text-amber-300 truncate" title="{{ $allAssignedAssistants->pluck('name')->implode(', ') }}">
                                            {{ $allAssignedAssistants->pluck('name')->implode(', ') }}
                                        </strong>
                                    @else
                                        <span class="text-slate-400 italic">Sin asignar</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Bloque 4: Finca / Contacto Lugar -->
                <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-3.5 flex flex-col justify-between gap-2.5">
                    <div>
                        <div class="flex items-center justify-between text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                            <span class="flex items-center gap-1">🏢 Finca / Lugar</span>
                            <button type="button" wire:click="openVenueModal" class="text-indigo-600 dark:text-indigo-400 hover:underline font-bold text-xs cursor-pointer" title="Editar contacto de finca">
                                ✏️
                            </button>
                        </div>
                        <div class="space-y-1 text-xs">
                            <div class="font-extrabold text-slate-800 dark:text-slate-200 truncate" title="{{ $event->venue_contact_name ?: ($event->location ?: 'Sin contacto registrado') }}">
                                {{ $event->venue_contact_name ?: ($event->location ?: 'Sin contacto') }}
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate" title="{{ $event->location }}">
                                📍 {{ $event->location }}
                            </div>
                            @if($event->venue_contact_phone)
                                <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                    <a href="tel:{{ $event->venue_contact_phone }}" class="inline-flex items-center gap-1 text-[10px] font-bold text-blue-700 dark:text-blue-300 bg-blue-100 hover:bg-blue-200 dark:bg-blue-950 dark:border dark:border-blue-800 px-2 py-0.5 rounded transition">
                                        📞 {{ $event->venue_contact_phone }}
                                    </a>
                                    @if($venuePhone)
                                        <a href="https://wa.me/{{ $venuePhone }}" target="_blank" class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-100 hover:bg-emerald-200 dark:bg-emerald-950 dark:border dark:border-emerald-800 px-1.5 py-0.5 rounded transition" title="WhatsApp Finca">
                                            💬
                                        </a>
                                    @endif
                                </div>
                            @else
                                <div class="flex flex-wrap items-center gap-2 pt-0.5">
                                    <button type="button"
                                            wire:click="lookupVenuePhoneAutomatically"
                                            wire:loading.attr="disabled"
                                            class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:hover:bg-emerald-900/80 border border-emerald-200 dark:border-emerald-800 px-2 py-0.5 rounded-md transition cursor-pointer">
                                        <span wire:loading.remove wire:target="lookupVenuePhoneAutomatically">🔍 Buscar Teléfono en Web</span>
                                        <span wire:loading wire:target="lookupVenuePhoneAutomatically">⏳ Buscando...</span>
                                    </button>
                                    <button type="button" wire:click="openVenueModal" class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline cursor-pointer">
                                        + Añadir manual
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 p-3.5 mb-5 rounded-2xl shadow-xs flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="text-base">✅</span>
                <span class="text-xs sm:text-sm font-bold">{{ session('message') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-900 font-black p-1 text-sm cursor-pointer">&times;</button>
        </div>
    @endif

    <!-- RECORDATORIO & ALERTA: EVENTO SIN RESPUESTA -->
    @if($event->status === 'no_response')
        @php
            $daysWaiting = $event->days_without_response;
            $urgency = $event->no_response_urgency_level;
            $clientPhone = $event->client ? $event->client->phone : ($event->partner_phone ?? '');
            $clientEmail = $event->client_contact_email;
            $followupData = \App\Services\WhatsAppTemplateService::followupReminder($event, 'pregunta_no');
        @endphp
        <div class="bg-white dark:bg-slate-900 p-4 sm:p-5 mb-6 rounded-2xl border transition shadow-sm {{ $urgency === 'critical' ? 'border-rose-400 dark:border-rose-600/70' : ($urgency === 'warning' ? 'border-amber-400 dark:border-amber-600/70' : 'border-purple-300 dark:border-purple-600/60') }}">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-start gap-3.5">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shrink-0 {{ $urgency === 'critical' ? 'bg-rose-100 dark:bg-rose-900/60 text-rose-600 dark:text-rose-300' : ($urgency === 'warning' ? 'bg-amber-100 dark:bg-amber-900/60 text-amber-600 dark:text-amber-300' : 'bg-purple-100 dark:bg-purple-900/60 text-purple-600 dark:text-purple-300') }}">
                        ⏳
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h4 class="font-extrabold text-sm sm:text-base text-gray-900 dark:text-white">
                                Recordatorio de Seguimiento: Cliente Sin Respuesta
                            </h4>
                            <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full border {{ $urgency === 'critical' ? 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-900/60 dark:text-rose-200 dark:border-rose-700' : ($urgency === 'warning' ? 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-900/60 dark:text-amber-200 dark:border-amber-700' : 'bg-purple-100 text-purple-800 border-purple-300 dark:bg-purple-900/60 dark:text-purple-200 dark:border-purple-700') }}">
                                {{ $event->no_response_time_label }}
                            </span>
                            @if(empty($clientPhone) && !empty($clientEmail))
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-indigo-100 text-indigo-800 dark:bg-indigo-950/70 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                    📧 Solo Email ({{ $clientEmail }})
                                </span>
                            @endif
                            @if($urgency === 'critical')
                                <span class="text-[10px] uppercase font-black bg-rose-600 text-white px-2 py-0.5 rounded-md animate-pulse">
                                    ¡Reactivar Ahora!
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-600 dark:text-slate-300 mt-1 leading-relaxed">
                            @if($daysWaiting >= 4)
                                ⚠️ El cliente lleva <strong class="text-rose-600 dark:text-rose-400 font-bold">{{ $daysWaiting }} días</strong> sin responder tras el presupuesto. Utiliza la <strong>técnica del "NO" o psicología inversa</strong> para saber si liberamos la fecha sin parecer insistente.
                            @elseif($daysWaiting >= 2)
                                Han pasado <strong class="text-amber-600 dark:text-amber-400 font-bold">{{ $daysWaiting }} días</strong> desde que se marcó en espera. Es buen momento para enviar un toque rápido antes de que se enfríe la fecha.
                            @else
                                Evento marcado en espera de respuesta. Tienes disponibles las plantillas tácticas de Chris Voss y Método Madrigal por WhatsApp y Email para cuando toque hacer seguimiento.
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Botones de Acción Rápida -->
                <div class="flex flex-wrap items-center gap-2 w-full md:w-auto shrink-0">
                    <!-- Botón Enviar WhatsApp Táctico 1-Clic -->
                    @if(!empty($clientPhone))
                        @if($gatewayActive)
                            <button type="button" wire:click="sendDirectTemplate('tecnica_no')" wire:loading.attr="disabled" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold text-xs px-3.5 py-2.5 rounded-xl shadow-xs transition cursor-pointer" title="Enviar WhatsApp directamente vía API">
                                <span wire:loading.remove wire:target="sendDirectTemplate('tecnica_no')">🚀 Enviar WhatsApp (Técnica NO)</span>
                                <span wire:loading wire:target="sendDirectTemplate('tecnica_no')">⏳ Enviando...</span>
                            </button>
                        @else
                            <button type="button" onclick="window.open('{{ $followupData['url'] }}', '_blank')" wire:click="promptFollowupFromShow" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold text-xs px-3.5 py-2.5 rounded-xl shadow-xs transition cursor-pointer" title="Enviar WhatsApp y registrar seguimiento">
                                <span>💬</span>
                                <span>Enviar WhatsApp (Técnica NO)</span>
                            </button>
                        @endif
                    @endif

                    <!-- Botón Seguimiento por Email (Especialmente útil cuando el cliente no dio móvil) -->
                    <button type="button" wire:click="promptFollowupFromShow" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold text-xs px-3.5 py-2.5 rounded-xl shadow-xs transition cursor-pointer" title="Abrir guiones de seguimiento por Email o WhatsApp">
                        <span>📧</span>
                        <span>{{ empty($clientPhone) ? 'Enviar Seguimiento por Email' : 'Seguimiento Email / Guiones' }}</span>
                    </button>

                    <!-- Abrir todas las plantillas de seguimiento del Hub -->
                    <button type="button" @click="openWhatsappHub = true" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs px-3 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 transition cursor-pointer">
                        <span>🔥</span>
                        <span>Más Opciones</span>
                    </button>

                    <!-- Si responde: Cambiar a Borrador o Confirmar -->
                    <div class="relative inline-block text-left" x-data="{ openResolveMenu: false }">
                        <button type="button" @click="openResolveMenu = !openResolveMenu" class="inline-flex items-center gap-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/70 dark:hover:bg-indigo-900/80 text-indigo-700 dark:text-indigo-300 font-bold text-xs px-3 py-2.5 rounded-xl border border-indigo-200 dark:border-indigo-800 transition cursor-pointer">
                            <span>⚡ ¿Ha respondido?</span>
                            <span class="text-[9px]">▼</span>
                        </button>
                        <div x-show="openResolveMenu" @click.away="openResolveMenu = false" class="absolute right-0 mt-1.5 w-56 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl z-20 p-1 divide-y divide-slate-100 dark:divide-slate-800 text-xs" style="display: none;">
                            <div class="py-0.5">
                                <button type="button" wire:click="changeStatus('on_hold')" @click="openResolveMenu = false" class="w-full text-left px-3 py-2 rounded-lg hover:bg-cyan-50 dark:hover:bg-cyan-950/40 text-cyan-800 dark:text-cyan-300 font-semibold flex items-center gap-1.5 cursor-pointer">
                                    <span>⏸️</span> En Espera (Ya avisarán)
                                </button>
                                <button type="button" wire:click="changeStatus('draft')" @click="openResolveMenu = false" class="w-full text-left px-3 py-2 rounded-lg hover:bg-amber-50 dark:hover:bg-amber-950/40 text-amber-800 dark:text-amber-300 font-semibold flex items-center gap-1.5 cursor-pointer">
                                    <span>📝</span> Pasar a Borrador
                                </button>
                                <button type="button" wire:click="changeStatus('confirmed')" @click="openResolveMenu = false" class="w-full text-left px-3 py-2 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 font-semibold flex items-center gap-1.5 cursor-pointer">
                                    <span>🟢</span> Confirmar (Cobrar señal)
                                </button>
                            </div>
                            <div class="py-0.5">
                                <button type="button" wire:click="changeStatus('rejected')" @click="openResolveMenu = false" class="w-full text-left px-3 py-2 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/40 text-rose-800 dark:text-rose-300 font-semibold flex items-center gap-1.5 cursor-pointer">
                                    <span>❌</span> Descartar (Rechazado)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- BANNER: EVENTO EN ESPERA / EN PAUSA (EL CLIENTE AVISARÁ + CONTROL DE 30 DÍAS) -->
    @if($event->status === 'on_hold')
        @php
            $daysPaused = $event->days_on_hold;
            $isExpiredHold = $event->is_on_hold_expired;
            $clientPhoneHold = $event->client ? $event->client->phone : ($event->partner_phone ?? '');
            $clientEmailHold = $event->client_contact_email;
        @endphp
        <div class="bg-white dark:bg-slate-900 p-4 sm:p-5 mb-6 rounded-2xl border transition shadow-sm {{ $isExpiredHold ? 'border-amber-400 dark:border-amber-600/80 bg-amber-50/30 dark:bg-amber-950/20' : 'border-cyan-300 dark:border-cyan-700/70' }}">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-start gap-3.5">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shrink-0 {{ $isExpiredHold ? 'bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300' : 'bg-cyan-100 dark:bg-cyan-900/60 text-cyan-700 dark:text-cyan-300' }}">
                        {{ $isExpiredHold ? '⌛' : '⏸️' }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h4 class="font-extrabold text-sm sm:text-base text-gray-900 dark:text-white">
                                {{ $isExpiredHold ? 'Plazo de 30 Días Superado: Presupuesto en Espera Vencido' : 'Evento en Espera (El cliente indicó que se pondrá en contacto)' }}
                            </h4>
                            <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full border {{ $isExpiredHold ? 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-900/60 dark:text-amber-200 dark:border-amber-700' : 'bg-cyan-100 text-cyan-800 border-cyan-300 dark:bg-cyan-900/60 dark:text-cyan-200 dark:border-cyan-700' }}">
                                ⏸️ {{ $event->on_hold_time_label }}
                            </span>
                            @if($isExpiredHold)
                                <span class="text-[10px] uppercase font-black bg-amber-600 text-white px-2 py-0.5 rounded-md animate-pulse">
                                    +30 días · Fin de validez
                                </span>
                            @else
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                    Restan {{ max(0, 30 - $daysPaused) }} días de validez
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-600 dark:text-slate-300 mt-1 leading-relaxed">
                            @if($isExpiredHold)
                                ⚠️ Llevan <strong class="text-amber-700 dark:text-amber-300 font-bold">{{ $daysPaused }} días en pausa</strong> (más del límite de 30 días). Envíales el aviso de que <strong>finaliza el plazo del presupuesto</strong> y que, si más adelante desean contratar, tendrán que solicitar uno nuevo con las tarifas vigentes en ese momento.
                            @else
                                El evento está en pausa sin activar alertas de «Sin Respuesta». Si transcurren <strong>30 días en pausa</strong>, el sistema te avisará para enviarles el mensaje de finalización de validez del presupuesto.
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Botones de Acción para En Espera -->
                <div class="flex flex-wrap items-center gap-2 w-full md:w-auto shrink-0">
                    @if(!empty($clientPhoneHold))
                        @if($gatewayActive)
                            <button type="button" wire:click="sendDirectTemplate('fin_plazo_presupuesto')" wire:loading.attr="disabled" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 {{ $isExpiredHold ? 'bg-amber-600 hover:bg-amber-700 text-white' : 'bg-cyan-600 hover:bg-cyan-700 text-white' }} font-bold text-xs px-3.5 py-2.5 rounded-xl shadow-xs transition cursor-pointer" title="Enviar aviso de fin de plazo de presupuesto por WhatsApp">
                                <span wire:loading.remove wire:target="sendDirectTemplate('fin_plazo_presupuesto')">⌛ {{ $isExpiredHold ? 'Enviar Aviso Fin Plazo (30d)' : 'Aviso Fin Plazo Presupuesto' }}</span>
                                <span wire:loading wire:target="sendDirectTemplate('fin_plazo_presupuesto')">⏳ Enviando...</span>
                            </button>
                        @else
                            <button type="button" onclick="window.open('{{ $finPlazoWaUrl }}', '_blank')" wire:click="promptFollowupFromShow('fin_plazo_presupuesto')" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 {{ $isExpiredHold ? 'bg-amber-600 hover:bg-amber-700 text-white' : 'bg-cyan-600 hover:bg-cyan-700 text-white' }} font-bold text-xs px-3.5 py-2.5 rounded-xl shadow-xs transition cursor-pointer">
                                <span>⌛</span>
                                <span>{{ $isExpiredHold ? 'Enviar Aviso Fin Plazo (30d)' : 'Aviso Fin Plazo Presupuesto' }}</span>
                            </button>
                        @endif
                    @endif

                    <button type="button" wire:click="promptFollowupFromShow('fin_plazo_presupuesto')" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs px-3.5 py-2.5 rounded-xl shadow-xs transition cursor-pointer">
                        <span>📧</span>
                        <span>{{ empty($clientPhoneHold) ? 'Enviar Aviso Fin Plazo por Email' : 'Ver / Editar Aviso (WA o Email)' }}</span>
                    </button>

                    <button type="button" wire:click="changeStatus('confirmed')" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 font-bold text-xs px-3 py-2.5 rounded-xl border border-emerald-200 dark:border-emerald-800 transition cursor-pointer">
                        <span>🟢</span>
                        <span>Confirmar</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($showConfirmFollowupModal)
        @php
            $clientPhone = $event->client ? $event->client->phone : ($event->partner_phone ?? '');
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-xs animate-in fade-in duration-150">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-xl w-full p-6 shadow-2xl relative max-h-[90vh] overflow-y-auto">
                
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl font-bold">
                            {{ empty($clientPhone) ? '📧' : '💬' }}
                        </div>
                        <div>
                            <h3 class="text-base font-black text-slate-900 dark:text-white">
                                Seguimiento Madrigal · WhatsApp & Email
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                <strong class="text-slate-800 dark:text-slate-200">{{ $event->client ? $event->client->name : 'la pareja' }}</strong> &bull; {{ $event->name }}
                            </p>
                        </div>
                    </div>
                    @if($event->status === 'on_hold')
                        <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $event->is_on_hold_expired ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' : 'bg-cyan-100 text-cyan-800 dark:bg-cyan-950/60 dark:text-cyan-300' }}">
                            ⏸️ {{ $event->on_hold_time_label }}
                        </span>
                    @else
                        <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300">
                            {{ $event->no_response_time_label }}
                        </span>
                    @endif
                </div>

                    <!-- Selector de Plantilla Madrigal -->
                    <div class="mt-4 space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                            Selecciona el guion de seguimiento:
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            @foreach($confirmingFollowupOptions as $optKey => $opt)
                                <button type="button" 
                                    wire:click="selectFollowupTemplateFromShow('{{ $optKey }}')" 
                                    class="p-2.5 rounded-xl border text-left text-xs font-medium transition cursor-pointer relative {{ $confirmingFollowupSelectedKey === $optKey ? 'border-emerald-500 bg-emerald-50/70 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200 ring-2 ring-emerald-500/30' : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800/40' }}">
                                    <div class="flex items-center justify-between gap-1 mb-1">
                                        <span class="text-sm">{{ $opt['icon'] }}</span>
                                        @if($opt['is_recommended'])
                                            <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.2 rounded bg-amber-500/20 text-amber-600 dark:text-amber-300 border border-amber-500/30">
                                                Recomendado
                                            </span>
                                        @endif
                                    </div>
                                    <div class="font-bold text-[11px] truncate">{{ $opt['stage'] }}</div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 truncate">{{ $opt['title'] }}</div>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Vista previa del mensaje -->
                    <div class="mt-4 space-y-1.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">Mensaje a enviar (editable):</span>
                            <span class="text-[10px] text-slate-400">Válido para WhatsApp y Email</span>
                        </div>
                        <textarea wire:model.live="confirmingFollowupText" rows="5" class="w-full bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200/60 dark:border-emerald-800/40 rounded-2xl p-3 text-xs text-slate-800 dark:text-slate-200 font-sans leading-relaxed focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                    </div>

                    <!-- Selector de Marca / Remitente -->
                    <div class="mt-3 p-2.5 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700 flex flex-wrap items-center justify-between gap-2">
                        <span class="text-[11px] font-bold text-slate-600 dark:text-slate-300 flex items-center gap-1">
                            <span>📲</span> Firma / Remitente:
                        </span>
                        <div class="inline-flex rounded-lg bg-white dark:bg-slate-900 p-0.5 border border-slate-200 dark:border-slate-700 shadow-2xs">
                            <button type="button" wire:click="$set('whatsapp_sender_brand', 'miguel')" class="px-2.5 py-1 rounded-md text-[11px] font-bold transition cursor-pointer {{ $whatsapp_sender_brand === 'miguel' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100' }}">
                                👑 Miguel
                            </button>
                            <button type="button" wire:click="$set('whatsapp_sender_brand', 'fran')" class="px-2.5 py-1 rounded-md text-[11px] font-bold transition cursor-pointer {{ $whatsapp_sender_brand === 'fran' ? 'bg-fuchsia-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100' }}">
                                🎧 Fran
                            </button>
                            <button type="button" wire:click="$set('whatsapp_sender_brand', 'ezequiel')" class="px-2.5 py-1 rounded-md text-[11px] font-bold transition cursor-pointer {{ $whatsapp_sender_brand === 'ezequiel' ? 'bg-teal-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100' }}">
                                👥 Ezequiel
                            </button>
                        </div>
                    </div>

                    <!-- OPCIÓN 1: Envío por WhatsApp (si tiene teléfono) -->
                    @if(!empty($clientPhone))
                        <div class="mt-3 space-y-2">
                            @if($gatewayActive)
                                <button type="button" wire:click="sendDirectFollowupFromShow" wire:loading.attr="disabled" class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-600/30 transition flex items-center justify-center gap-2 cursor-pointer">
                                    <span wire:loading.remove wire:target="sendDirectFollowupFromShow">🚀 Enviar Ahora desde WhatsApp de {{ $this->getSenderBrandLabel() }}</span>
                                    <span wire:loading wire:target="sendDirectFollowupFromShow">⏳ Enviando mensaje...</span>
                                </button>
                            @endif
                            @if(!empty($confirmingFollowupUrl))
                                <a href="{{ $confirmingFollowupUrl }}" target="_blank" class="w-full py-2.5 px-4 {{ $gatewayActive ? 'bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-200' : 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-md shadow-emerald-600/30' }} font-bold text-xs rounded-xl transition flex items-center justify-center gap-2">
                                    <span>💬 Abrir en mi WhatsApp Web / App personal</span>
                                    <span class="text-xs">↗</span>
                                </a>
                            @endif
                        </div>
                    @else
                        <div class="mt-3 p-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/70 text-[11px] text-amber-800 dark:text-amber-300 font-semibold flex items-center gap-2">
                            <span>📵</span>
                            <span>Este cliente no dejó teléfono móvil. Puedes enviarle el seguimiento directamente por Email aquí abajo:</span>
                        </div>
                    @endif

                    <!-- OPCIÓN 2: Envío por Email (con registro en Conversación Registrada) -->
                    <div class="mt-3 p-3 bg-indigo-50/70 dark:bg-indigo-950/30 rounded-2xl border border-indigo-200 dark:border-indigo-800/80 space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[11px] font-extrabold text-indigo-900 dark:text-indigo-200 flex items-center gap-1">
                                <span>📧</span> Seguimiento por Email
                            </span>
                            <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold">Se registrará en el chat del evento</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-0.5">Email del cliente:</label>
                                <input type="email" wire:model.live="confirmingFollowupEmail" placeholder="cliente@email.com" class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-indigo-200 dark:border-indigo-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-semibold" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-0.5">Asunto del correo:</label>
                                <input type="text" wire:model.live="confirmingFollowupSubject" placeholder="Asunto del seguimiento..." class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-indigo-200 dark:border-indigo-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-semibold" />
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2 pt-1">
                            <button type="button" wire:click="sendDirectEmailFromShow" wire:loading.attr="disabled" class="flex-1 py-2.5 px-4 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-indigo-600/20 transition flex items-center justify-center gap-1.5 cursor-pointer">
                                <span wire:loading.remove wire:target="sendDirectEmailFromShow">📧 Enviar Seguimiento por Email Ahora</span>
                                <span wire:loading wire:target="sendDirectEmailFromShow">⏳ Enviando Email...</span>
                            </button>
                            @if(!empty($confirmingFollowupEmail))
                                <a href="{{ \App\Services\WhatsAppTemplateService::buildMailtoUrl($confirmingFollowupEmail, $confirmingFollowupSubject, $confirmingFollowupText) }}" target="_blank" class="py-2.5 px-3 bg-white dark:bg-slate-900 hover:bg-indigo-100 dark:hover:bg-slate-800 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-700 font-bold text-xs rounded-xl transition flex items-center justify-center gap-1 shrink-0" title="Abrir en tu aplicación de correo (Outlook, Gmail, Apple Mail)">
                                    <span>✉️ Abrir gestor correo</span>
                                    <span>↗</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Confirmaciones de acción posterior -->
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 space-y-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block text-center">
                            Una vez enviado o revisado:
                        </span>
                        <button type="button" wire:click="confirmFollowupSentFromShow" class="w-full py-2.5 px-3 bg-purple-600 hover:bg-purple-500 text-white font-extrabold text-xs rounded-xl transition flex items-center justify-center gap-2 cursor-pointer shadow-xs">
                            <span>✅ He enviado el seguimiento manualmente</span>
                            <span class="text-[10px] opacity-80">(Poner contador a 0 hoy)</span>
                        </button>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <button type="button" wire:click="markAsRespondedFromShow('on_hold')" class="py-2 px-3 bg-cyan-50 hover:bg-cyan-100 dark:bg-cyan-950/60 text-cyan-800 dark:text-cyan-300 font-bold text-xs rounded-xl border border-cyan-300 dark:border-cyan-800 transition flex items-center justify-center gap-1 cursor-pointer" title="El cliente indicó que ya avisará más adelante">
                                <span>⏸️ En Espera (Avisarán)</span>
                            </button>
                            <button type="button" wire:click="markAsRespondedFromShow('draft')" class="py-2 px-3 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-bold text-xs rounded-xl border border-indigo-200 dark:border-indigo-800 transition flex items-center justify-center gap-1 cursor-pointer" title="Marcar como atendido y pasar a borrador">
                                <span>📝 ¡Ha respondido!</span>
                            </button>
                            <button type="button" wire:click="markAsRespondedFromShow('confirmed')" class="py-2 px-3 bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 font-bold text-xs rounded-xl border border-amber-200 dark:border-amber-800 transition flex items-center justify-center gap-1 cursor-pointer" title="Marcar como confirmado">
                                <span>🟢 Confirmar Evento</span>
                            </button>
                        </div>

                        <button type="button" wire:click="cancelFollowupFromShow" class="w-full py-1.5 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 font-medium text-xs transition cursor-pointer">
                            Cerrar sin cambiar contador
                        </button>
                    </div>

                </div>
            </div>
    @endif

    @if(in_array($event->status, ['rejected', 'cancelled']))
        <div class="mb-6 rounded-2xl border {{ $event->status === 'rejected' ? 'border-slate-300 dark:border-slate-700 bg-gradient-to-r from-slate-100 via-rose-50/50 to-slate-100 dark:from-slate-900 dark:via-rose-950/30 dark:to-slate-900' : 'border-rose-300 dark:border-rose-800/80 bg-gradient-to-r from-rose-50 via-red-50/70 to-rose-50 dark:from-rose-950/50 dark:via-red-950/30 dark:to-slate-900' }} p-4 sm:p-5 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-start gap-3.5">
                    <div class="w-11 h-11 rounded-2xl {{ $event->status === 'rejected' ? 'bg-slate-800 text-white dark:bg-slate-700' : 'bg-rose-600 text-white' }} flex items-center justify-center text-xl shrink-0 shadow-xs">
                        {{ $event->status === 'rejected' ? '❌' : '🔴' }}
                    </div>
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-black uppercase tracking-wider {{ $event->status === 'rejected' ? 'text-slate-800 dark:text-slate-200' : 'text-rose-800 dark:text-rose-300' }}">
                                {{ $event->status === 'rejected' ? 'Evento Rechazado / Descartado' : 'Evento Cancelado' }}
                            </span>
                            <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full {{ $event->status === 'rejected' ? 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300' : 'bg-rose-200/80 text-rose-900 dark:bg-rose-900/60 dark:text-rose-200' }}">
                                Motivo registrado
                            </span>
                        </div>
                        <p class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white">
                            @if($event->cancellation_reason_display)
                                «{{ $event->cancellation_reason_display }}»
                            @else
                                <span class="text-slate-500 dark:text-slate-400 italic font-semibold">Sin motivo especificado todavía</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <button type="button"
                            wire:click="openCancellationModal('{{ $event->status }}')"
                            class="px-3.5 py-2 rounded-xl text-xs font-extrabold bg-white hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 shadow-2xs transition cursor-pointer inline-flex items-center gap-1.5">
                        <span>✏️</span>
                        <span>{{ $event->cancellation_reason_display ? 'Editar motivo' : 'Añadir motivo' }}</span>
                    </button>
                    <button type="button"
                            wire:click="changeStatus('draft')"
                            class="px-3.5 py-2 rounded-xl text-xs font-extrabold bg-indigo-600 hover:bg-indigo-500 text-white shadow-xs transition cursor-pointer inline-flex items-center gap-1.5">
                        <span>🔄</span>
                        <span>Reactivar a Borrador</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Banner Cuestionario Musical con WhatsApp (oculto en Presupuestos/Contratos/Facturas para ahorrar scroll) -->
    @if(!in_array($activeTab, ['quotes', 'contracts', 'invoices']))
    <div class="bg-white p-3.5 rounded-xl border border-gray-200 mb-4 shadow-sm flex flex-col lg:flex-row items-start lg:items-center justify-between gap-3">
        <div>
            <div class="flex items-center gap-2">
                <h4 class="font-bold text-gray-800 text-sm flex items-center gap-1.5">
                    <span>🎵 Enlace Cuestionario Musical (Para Clientes)</span>
                </h4>
                @if($event->is_dossier_completed)
                    <span class="text-[10px] bg-rose-100 text-rose-800 border border-rose-200 px-2 py-0.5 rounded-full font-bold">🔒 Cuestionario Bloqueado</span>
                @else
                    <span class="text-[10px] bg-emerald-100 text-emerald-800 border border-emerald-200 px-2 py-0.5 rounded-full font-bold">🔓 Abierto para el cliente</span>
                @endif
            </div>
            <p class="text-xs text-gray-500 mt-0.5">Comparte este enlace para que los novios o el cliente rellenen sus canciones y preferencias.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto" x-data="{ copiedLink: false }">
            <input type="text" readonly value="{{ route('guest.form', $event->token) }}" class="flex-1 md:w-56 border-gray-300 rounded-lg text-xs bg-gray-50 px-3 py-2 select-all focus:ring-indigo-500 font-mono" id="guestLink">
            
            <button type="button" @click="
                const input = document.getElementById('guestLink');
                input.select();
                input.setSelectionRange(0, 99999);
                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(input.value);
                    } else {
                        document.execCommand('copy');
                    }
                    copiedLink = true;
                    setTimeout(() => copiedLink = false, 2500);
                } catch (e) {
                    document.execCommand('copy');
                    copiedLink = true;
                    setTimeout(() => copiedLink = false, 2500);
                }
            " class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-xs font-semibold shadow-xs whitespace-nowrap transition min-w-[85px] text-center">
                <span x-show="!copiedLink">📋 Copiar</span>
                <span x-show="copiedLink" class="text-emerald-600 font-bold" style="display: none;">✅ ¡Copiado!</span>
            </button>
            
            <a href="{{ route('guest.form', $event->token) }}" target="_blank" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 px-3 py-2 rounded-lg text-xs font-bold shadow-xs whitespace-nowrap inline-flex items-center gap-1 transition" title="Abrir y probar cuestionario en nueva pestaña">
                🔗 Probar
            </a>

            <button type="button" wire:click="toggleDossierStatus" class="px-3 py-2 rounded-lg text-xs font-bold border transition shadow-xs whitespace-nowrap {{ $event->is_dossier_completed ? 'bg-amber-50 hover:bg-amber-100 text-amber-800 border-amber-300' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-300' }}" title="{{ $event->is_dossier_completed ? 'Permitir al cliente editar sus canciones de nuevo' : 'Bloquear cuestionario para que el cliente no haga más cambios' }}">
                {{ $event->is_dossier_completed ? '🔓 Reabrir' : '🔒 Bloquear' }}
            </button>

            <!-- Selector compacto de línea WhatsApp -->
            <div class="inline-flex items-center rounded-lg bg-slate-100 p-0.5 border border-slate-200" title="Elige desde qué número de WhatsApp enviar">
                <button type="button" wire:click="$set('whatsapp_sender_brand', 'miguel')" class="px-2 py-1 rounded-md text-[11px] font-bold transition cursor-pointer {{ $whatsapp_sender_brand === 'miguel' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}" title="Enviar desde WhatsApp de Miguel">
                    👑 Miguel
                </button>
                <button type="button" wire:click="$set('whatsapp_sender_brand', 'fran')" class="px-2 py-1 rounded-md text-[11px] font-bold transition cursor-pointer {{ $whatsapp_sender_brand === 'fran' ? 'bg-fuchsia-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}" title="Enviar desde WhatsApp de Fran">
                    🎧 Fran
                </button>
                <button type="button" wire:click="$set('whatsapp_sender_brand', 'ezequiel')" class="px-2 py-1 rounded-md text-[11px] font-bold transition cursor-pointer {{ $whatsapp_sender_brand === 'ezequiel' ? 'bg-teal-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}" title="Enviar desde WhatsApp de Ezequiel">
                    👥 Ezequiel
                </button>
            </div>
            
            @if($gatewayActive)
                <button type="button" wire:click="sendDirectTemplate('cuestionario')" wire:loading.attr="disabled" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 rounded-lg text-xs font-bold shadow-xs whitespace-nowrap inline-flex items-center gap-1.5 transition cursor-pointer" title="Enviar cuestionario desde el WhatsApp seleccionado">
                    <span wire:loading.remove wire:target="sendDirectTemplate('cuestionario')">🚀 Enviar WhatsApp</span>
                    <span wire:loading wire:target="sendDirectTemplate('cuestionario')">⏳ Enviando...</span>
                </button>
            @else
                <a href="{{ $cuestionarioWaUrl }}" target="_blank" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 rounded-lg text-xs font-bold shadow-xs whitespace-nowrap inline-flex items-center gap-1.5 transition">
                    💬 Enviar WhatsApp
                </a>
            @endif
        </div>
    </div>
    @endif

    <!-- Modal WhatsApp Hub Optimizado (Pestañas por Fase + Tarjetas Compactas 2 Columnas) -->
    <div x-show="openWhatsappHub" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true"
         x-data="{
            hubTab: '{{ in_array($event->status, ['no_response', 'on_hold']) ? 'followup' : (in_array($event->status, ['confirmed', 'completed']) ? 'booking' : ($latestQuote ? 'quote' : 'contact')) }}'
         }">
        <div class="flex items-center justify-center min-h-screen p-3 sm:p-4 text-center">
            <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-xs transition-opacity" @click="openWhatsappHub = false"></div>
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all w-full max-w-4xl border border-slate-200 dark:border-slate-800">
                <!-- Cabecera Compacta con Selector de Remitente Integrado -->
                <div class="bg-gradient-to-r from-emerald-700 to-teal-700 px-4 py-3 flex flex-wrap items-center justify-between gap-2 text-white">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="text-xl">💬</span>
                        <div class="truncate">
                            <h3 class="font-extrabold text-sm sm:text-base leading-tight truncate">Centro de Mensajes WhatsApp</h3>
                            <p class="text-[11px] text-emerald-100 truncate">{{ $event->name }} &bull; {{ $event->event_type_label }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 ml-auto">
                        <!-- Selector de Remitente Integrado -->
                        <div class="inline-flex items-center rounded-xl bg-slate-950/60 p-0.5 border border-emerald-500/40 text-[11px]">
                            <button type="button" wire:click="$set('whatsapp_sender_brand', 'miguel')" class="px-2.5 py-1 rounded-lg font-bold transition cursor-pointer {{ $whatsapp_sender_brand === 'miguel' ? 'bg-indigo-600 text-white shadow-xs' : 'text-emerald-100 hover:text-white' }}">
                                👑 Miguel
                            </button>
                            <button type="button" wire:click="$set('whatsapp_sender_brand', 'fran')" class="px-2.5 py-1 rounded-lg font-bold transition cursor-pointer {{ $whatsapp_sender_brand === 'fran' ? 'bg-fuchsia-600 text-white shadow-xs' : 'text-emerald-100 hover:text-white' }}">
                                🎧 Fran
                            </button>
                            <button type="button" wire:click="$set('whatsapp_sender_brand', 'ezequiel')" class="px-2.5 py-1 rounded-lg font-bold transition cursor-pointer {{ $whatsapp_sender_brand === 'ezequiel' ? 'bg-teal-600 text-white shadow-xs' : 'text-emerald-100 hover:text-white' }}">
                                👥 Ezequiel
                            </button>
                        </div>
                        <button type="button" @click="openWhatsappHub = false" class="text-emerald-100 hover:text-white text-2xl font-bold leading-none px-1.5 cursor-pointer">&times;</button>
                    </div>
                </div>

                <!-- Barra de Pestañas por Etapa (Sin Scroll Eterno) -->
                <div class="bg-slate-100 dark:bg-slate-950 px-3 py-2 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center gap-1.5">
                    <button type="button" @click="hubTab = 'contact'"
                            :class="hubTab === 'contact' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:bg-slate-200/70 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                            class="px-3 py-1.5 rounded-xl text-xs font-extrabold transition cursor-pointer inline-flex items-center gap-1.5">
                        <span>🟢</span> <span>1. Toma de Contacto & Charla</span>
                    </button>
                    <button type="button" @click="hubTab = 'quote'"
                            :class="hubTab === 'quote' ? 'bg-purple-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:bg-slate-200/70 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                            class="px-3 py-1.5 rounded-xl text-xs font-extrabold transition cursor-pointer inline-flex items-center gap-1.5">
                        <span>📑</span> <span>2. Propuesta, Dossier & Señal</span>
                    </button>
                    <button type="button" @click="hubTab = 'followup'"
                            :class="hubTab === 'followup' ? 'bg-rose-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:bg-slate-200/70 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                            class="px-3 py-1.5 rounded-xl text-xs font-extrabold transition cursor-pointer inline-flex items-center gap-1.5">
                        <span>🔥</span> <span>3. Si No Responden (Técnica NO)</span>
                        @if($event->status === 'no_response')
                            <span class="w-2 h-2 rounded-full bg-amber-300 animate-ping"></span>
                        @endif
                    </button>
                    <button type="button" @click="hubTab = 'booking'"
                            :class="hubTab === 'booking' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:bg-slate-200/70 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                            class="px-3 py-1.5 rounded-xl text-xs font-extrabold transition cursor-pointer inline-flex items-center gap-1.5">
                        <span>📜</span> <span>4. Contrato, Música & Staff</span>
                    </button>
                    <button type="button" @click="hubTab = 'all'"
                            :class="hubTab === 'all' ? 'bg-slate-700 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-500 dark:text-slate-400 hover:bg-slate-200/70 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                            class="ml-auto px-2.5 py-1.5 rounded-xl text-[11px] font-bold transition cursor-pointer">
                        Ver todas
                    </button>
                </div>

                <!-- Cuerpo Compacto en Grid de 2 Columnas -->
                <div class="p-4 max-h-[70vh] overflow-y-auto space-y-3">

                    <!-- ============================================================== -->
                    <!-- PESTAÑA 1: APERTURA, CONTACTO & VIDEOLLAMADA                   -->
                    <!-- ============================================================== -->
                    <div x-show="hubTab === 'contact' || hubTab === 'all'" class="space-y-2.5">
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">🟢 1. Primera Toma de Contacto & Conexión</span>
                            <div class="h-px bg-emerald-200 dark:bg-emerald-900/60 flex-1"></div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                            <!-- 1.1 Disponibilidad en Agenda -->
                            <div x-data="{ showText: false, copied: false }" class="p-3 rounded-xl border border-emerald-200 dark:border-emerald-800/70 bg-emerald-50/30 dark:bg-emerald-950/20 flex flex-col justify-between gap-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <h4 class="font-bold text-gray-900 dark:text-white text-xs">📅 Fecha Libre & Saludo Cálido</h4>
                                            <span class="text-[9px] bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-300 font-bold px-1.5 py-0.5 rounded-full">Lead In ⚡</span>
                                        </div>
                                        <p class="text-[11px] text-gray-600 dark:text-slate-400 mt-0.5 leading-snug">
                                            Confirma disponibilidad en calendario y rompe el hielo sin presionar.
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <button type="button" @click="showText = !showText" class="bg-white dark:bg-slate-800 hover:bg-emerald-100 text-emerald-700 dark:text-emerald-300 font-bold text-[11px] px-2 py-1.5 rounded-lg border border-emerald-300 dark:border-emerald-700 transition cursor-pointer" title="Ver o copiar texto">
                                            <span x-text="showText ? '▲' : '👁️'"></span>
                                        </button>
                                        @if($gatewayActive)
                                            <button type="button" wire:click="sendDirectTemplate('disponibilidad')" wire:loading.attr="disabled" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition cursor-pointer">
                                                <span wire:loading.remove wire:target="sendDirectTemplate('disponibilidad')">🚀 Enviar</span>
                                                <span wire:loading wire:target="sendDirectTemplate('disponibilidad')">⏳</span>
                                            </button>
                                        @else
                                            <a href="{{ $contactDispoWaUrl }}" target="_blank" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition">💬 Enviar</a>
                                        @endif
                                    </div>
                                </div>
                                <div x-show="showText" style="display: none;" class="bg-white dark:bg-slate-950 p-2.5 rounded-lg border border-emerald-200 dark:border-emerald-900 text-[11px] space-y-1.5">
                                    <div class="font-mono text-[10px] leading-relaxed whitespace-pre-line text-gray-800 dark:text-slate-200 max-h-32 overflow-y-auto">{{ $contactDispoMsg }}</div>
                                    <button type="button" @click="navigator.clipboard.writeText(@js($contactDispoMsg)); copied = true; setTimeout(() => copied = false, 2000)" class="w-full text-[10px] font-bold py-1 rounded border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 cursor-pointer">
                                        <span x-text="copied ? '✅ ¡Copiado!' : '📋 Copiar texto'"></span>
                                    </button>
                                </div>
                            </div>

                            <!-- 1.2 Filtro Espacio & Música -->
                            <div x-data="{ showText: false, copied: false }" class="p-3 rounded-xl border border-teal-200 dark:border-teal-800/70 bg-teal-50/30 dark:bg-teal-950/20 flex flex-col justify-between gap-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <h4 class="font-bold text-gray-900 dark:text-white text-xs">🏰 {{ $event->is_wedding ? 'Filtro Finca, Espacio & Música' : 'Filtro Lugar, Horario & Formato' }}</h4>
                                            <span class="text-[9px] bg-teal-100 dark:bg-teal-900/60 text-teal-800 dark:text-teal-300 font-bold px-1.5 py-0.5 rounded-full">Descubrimiento</span>
                                        </div>
                                        <p class="text-[11px] text-gray-600 dark:text-slate-400 mt-0.5 leading-snug">
                                            {{ $event->is_wedding ? 'Pregunta si tienen finca cerrada y qué fases buscan sonorizar.' : 'Pregunta qué idea de evento, música o espectáculo tienen en mente.' }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <button type="button" @click="showText = !showText" class="bg-white dark:bg-slate-800 hover:bg-teal-100 text-teal-700 dark:text-teal-300 font-bold text-[11px] px-2 py-1.5 rounded-lg border border-teal-300 dark:border-teal-700 transition cursor-pointer" title="Ver o copiar texto">
                                            <span x-text="showText ? '▲' : '👁️'"></span>
                                        </button>
                                        @if($gatewayActive)
                                            <button type="button" wire:click="sendDirectTemplate('filtro_finca')" wire:loading.attr="disabled" class="bg-teal-600 hover:bg-teal-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition cursor-pointer">
                                                <span wire:loading.remove wire:target="sendDirectTemplate('filtro_finca')">🚀 Enviar</span>
                                                <span wire:loading wire:target="sendDirectTemplate('filtro_finca')">⏳</span>
                                            </button>
                                        @else
                                            <a href="{{ $contactFincaWaUrl }}" target="_blank" class="bg-teal-600 hover:bg-teal-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition">💬 Enviar</a>
                                        @endif
                                    </div>
                                </div>
                                <div x-show="showText" style="display: none;" class="bg-white dark:bg-slate-950 p-2.5 rounded-lg border border-teal-200 dark:border-teal-900 text-[11px] space-y-1.5">
                                    <div class="font-mono text-[10px] leading-relaxed whitespace-pre-line text-gray-800 dark:text-slate-200 max-h-32 overflow-y-auto">{{ $contactFincaMsg }}</div>
                                    <button type="button" @click="navigator.clipboard.writeText(@js($contactFincaMsg)); copied = true; setTimeout(() => copied = false, 2000)" class="w-full text-[10px] font-bold py-1 rounded border border-teal-200 dark:border-teal-800 bg-teal-50 dark:bg-teal-950/50 text-teal-700 dark:text-teal-300 cursor-pointer">
                                        <span x-text="copied ? '✅ ¡Copiado!' : '📋 Copiar texto'"></span>
                                    </button>
                                </div>
                            </div>

                            <!-- 2.1 Retomar Conversación -->
                            <div x-data="{ showText: false, copied: false }" class="p-3 rounded-xl border border-blue-200 dark:border-blue-800/70 bg-blue-50/30 dark:bg-blue-950/20 flex flex-col justify-between gap-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <h4 class="font-bold text-gray-900 dark:text-white text-xs">💭 Retomar Conversación (Toque Ligero)</h4>
                                            <span class="text-[9px] bg-blue-100 dark:bg-blue-900/60 text-blue-800 dark:text-blue-300 font-bold px-1.5 py-0.5 rounded-full">Sin Presión</span>
                                        </div>
                                        <p class="text-[11px] text-gray-600 dark:text-slate-400 mt-0.5 leading-snug">
                                            Pregunta cómo van los preparativos y si surgió alguna duda.
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <button type="button" @click="showText = !showText" class="bg-white dark:bg-slate-800 hover:bg-blue-100 text-blue-700 dark:text-blue-300 font-bold text-[11px] px-2 py-1.5 rounded-lg border border-blue-300 dark:border-blue-700 transition cursor-pointer" title="Ver o copiar texto">
                                            <span x-text="showText ? '▲' : '👁️'"></span>
                                        </button>
                                        @if($gatewayActive)
                                            <button type="button" wire:click="sendDirectTemplate('conversacion_suave')" wire:loading.attr="disabled" class="bg-blue-600 hover:bg-blue-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition cursor-pointer">
                                                <span wire:loading.remove wire:target="sendDirectTemplate('conversacion_suave')">🚀 Enviar</span>
                                                <span wire:loading wire:target="sendDirectTemplate('conversacion_suave')">⏳</span>
                                            </button>
                                        @else
                                            <a href="{{ $followupChatWaUrl }}" target="_blank" class="bg-blue-600 hover:bg-blue-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition">💬 Enviar</a>
                                        @endif
                                    </div>
                                </div>
                                <div x-show="showText" style="display: none;" class="bg-white dark:bg-slate-950 p-2.5 rounded-lg border border-blue-200 dark:border-blue-900 text-[11px] space-y-1.5">
                                    <div class="font-mono text-[10px] leading-relaxed whitespace-pre-line text-gray-800 dark:text-slate-200 max-h-32 overflow-y-auto">{{ $followupChatMsg }}</div>
                                    <button type="button" @click="navigator.clipboard.writeText(@js($followupChatMsg)); copied = true; setTimeout(() => copied = false, 2000)" class="w-full text-[10px] font-bold py-1 rounded border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 cursor-pointer">
                                        <span x-text="copied ? '✅ ¡Copiado!' : '📋 Copiar texto'"></span>
                                    </button>
                                </div>
                            </div>

                            <!-- 2.2 Propuesta de llamada 10 min -->
                            <div x-data="{ showText: false, copied: false }" class="p-3 rounded-xl border border-indigo-200 dark:border-indigo-800/70 bg-indigo-50/30 dark:bg-indigo-950/20 flex flex-col justify-between gap-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <h4 class="font-bold text-gray-900 dark:text-white text-xs">💻 Charla / Llamada Rápida (10 min)</h4>
                                            <span class="text-[9px] bg-indigo-100 dark:bg-indigo-900/60 text-indigo-800 dark:text-indigo-300 font-extrabold px-1.5 py-0.5 rounded-full">Cierres ⭐</span>
                                        </div>
                                        <p class="text-[11px] text-gray-600 dark:text-slate-400 mt-0.5 leading-snug">
                                            Hablar 10 min por teléfono para afinar la propuesta sin malentendidos.
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <button type="button" @click="showText = !showText" class="bg-white dark:bg-slate-800 hover:bg-indigo-100 text-indigo-700 dark:text-indigo-300 font-bold text-[11px] px-2 py-1.5 rounded-lg border border-indigo-300 dark:border-indigo-700 transition cursor-pointer" title="Ver o copiar texto">
                                            <span x-text="showText ? '▲' : '👁️'"></span>
                                        </button>
                                        @if($gatewayActive)
                                            <button type="button" wire:click="sendDirectTemplate('propuesta_llamada')" wire:loading.attr="disabled" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition cursor-pointer">
                                                <span wire:loading.remove wire:target="sendDirectTemplate('propuesta_llamada')">🚀 Enviar</span>
                                                <span wire:loading wire:target="sendDirectTemplate('propuesta_llamada')">⏳</span>
                                            </button>
                                        @else
                                            <a href="{{ $propuestaLlamadaWaUrl }}" target="_blank" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition">💬 Enviar</a>
                                        @endif
                                    </div>
                                </div>
                                <div x-show="showText" style="display: none;" class="bg-white dark:bg-slate-950 p-2.5 rounded-lg border border-indigo-200 dark:border-indigo-900 text-[11px] space-y-1.5">
                                    <div class="font-mono text-[10px] leading-relaxed whitespace-pre-line text-gray-800 dark:text-slate-200 max-h-32 overflow-y-auto">{{ $propuestaLlamadaMsg }}</div>
                                    <button type="button" @click="navigator.clipboard.writeText(@js($propuestaLlamadaMsg)); copied = true; setTimeout(() => copied = false, 2000)" class="w-full text-[10px] font-bold py-1 rounded border border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 cursor-pointer">
                                        <span x-text="copied ? '✅ ¡Copiado!' : '📋 Copiar texto'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================== -->
                    <!-- PESTAÑA 2: PROPUESTA, DOSSIER & RESERVA                        -->
                    <!-- ============================================================== -->
                    <div x-show="hubTab === 'quote' || hubTab === 'all'" class="space-y-2.5">
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-black uppercase tracking-wider text-purple-600 dark:text-purple-400">📑 2. Presentación de Propuesta, Dossier & Señal</span>
                            <div class="h-px bg-purple-200 dark:bg-purple-900/60 flex-1"></div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                            <!-- 3.1 Envío de Dossier + Presupuesto -->
                            <div x-data="{ showText: false, copied: false }" class="p-3 rounded-xl border border-purple-200 dark:border-purple-800/70 bg-purple-50/30 dark:bg-purple-950/20 flex flex-col justify-between gap-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <h4 class="font-bold text-gray-900 dark:text-white text-xs">📑 Enviar Dossier & Presupuesto (PDF)</h4>
                                            <span class="text-[9px] bg-purple-100 dark:bg-purple-900/60 text-purple-800 dark:text-purple-300 font-extrabold px-1.5 py-0.5 rounded-full">Recomendado ⭐</span>
                                        </div>
                                        <p class="text-[11px] text-gray-600 dark:text-slate-400 mt-0.5 leading-snug">
                                            Enlace al Dossier PDF interactivo con montajes y presupuesto al final.
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <button type="button" @click="showText = !showText" class="bg-white dark:bg-slate-800 hover:bg-purple-100 text-purple-700 dark:text-purple-300 font-bold text-[11px] px-2 py-1.5 rounded-lg border border-purple-300 dark:border-purple-700 transition cursor-pointer" title="Ver o copiar texto">
                                            <span x-text="showText ? '▲' : '👁️'"></span>
                                        </button>
                                        @if($gatewayActive)
                                            <button type="button" wire:click="sendDirectTemplate('envio_dossier')" wire:loading.attr="disabled" class="bg-purple-600 hover:bg-purple-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition cursor-pointer">
                                                <span wire:loading.remove wire:target="sendDirectTemplate('envio_dossier')">🚀 Enviar</span>
                                                <span wire:loading wire:target="sendDirectTemplate('envio_dossier')">⏳</span>
                                            </button>
                                        @else
                                            <a href="{{ $envioDossierWaUrl }}" target="_blank" class="bg-purple-600 hover:bg-purple-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition">💬 Enviar</a>
                                        @endif
                                    </div>
                                </div>
                                <div x-show="showText" style="display: none;" class="bg-white dark:bg-slate-950 p-2.5 rounded-lg border border-purple-200 dark:border-purple-900 text-[11px] space-y-1.5">
                                    <div class="font-mono text-[10px] leading-relaxed whitespace-pre-line text-gray-800 dark:text-slate-200 max-h-32 overflow-y-auto">{{ $envioDossierMsg }}</div>
                                    <button type="button" @click="navigator.clipboard.writeText(@js($envioDossierMsg)); copied = true; setTimeout(() => copied = false, 2000)" class="w-full text-[10px] font-bold py-1 rounded border border-purple-200 dark:border-purple-800 bg-purple-50 dark:bg-purple-950/50 text-purple-700 dark:text-purple-300 cursor-pointer">
                                        <span x-text="copied ? '✅ ¡Copiado!' : '📋 Copiar texto'"></span>
                                    </button>
                                </div>
                            </div>

                            <!-- 3.2 Seguimiento a los 2-3 días -->
                            <div x-data="{ showText: false, copied: false }" class="p-3 rounded-xl border border-amber-200 dark:border-amber-800/70 bg-amber-50/30 dark:bg-amber-950/20 flex flex-col justify-between gap-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <h4 class="font-bold text-gray-900 dark:text-white text-xs">⏳ Seguimiento 2-3 Días (¿Lo visteis?)</h4>
                                            <span class="text-[9px] bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-300 font-bold px-1.5 py-0.5 rounded-full">Paso Clave</span>
                                        </div>
                                        <p class="text-[11px] text-gray-600 dark:text-slate-400 mt-0.5 leading-snug">
                                            Toque cercano tras enviar propuesta para resolver dudas.
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <button type="button" @click="showText = !showText" class="bg-white dark:bg-slate-800 hover:bg-amber-100 text-amber-700 dark:text-amber-300 font-bold text-[11px] px-2 py-1.5 rounded-lg border border-amber-300 dark:border-amber-700 transition cursor-pointer" title="Ver o copiar texto">
                                            <span x-text="showText ? '▲' : '👁️'"></span>
                                        </button>
                                        @if($gatewayActive)
                                            <button type="button" wire:click="sendDirectTemplate('post_presupuesto')" wire:loading.attr="disabled" class="bg-amber-600 hover:bg-amber-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition cursor-pointer">
                                                <span wire:loading.remove wire:target="sendDirectTemplate('post_presupuesto')">🚀 Enviar</span>
                                                <span wire:loading wire:target="sendDirectTemplate('post_presupuesto')">⏳</span>
                                            </button>
                                        @else
                                            <a href="{{ $followupPostQuoteWaUrl }}" target="_blank" class="bg-amber-600 hover:bg-amber-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition">💬 Enviar</a>
                                        @endif
                                    </div>
                                </div>
                                <div x-show="showText" style="display: none;" class="bg-white dark:bg-slate-950 p-2.5 rounded-lg border border-amber-200 dark:border-amber-900 text-[11px] space-y-1.5">
                                    <div class="font-mono text-[10px] leading-relaxed whitespace-pre-line text-gray-800 dark:text-slate-200 max-h-32 overflow-y-auto">{{ $followupPostQuoteMsg }}</div>
                                    <button type="button" @click="navigator.clipboard.writeText(@js($followupPostQuoteMsg)); copied = true; setTimeout(() => copied = false, 2000)" class="w-full text-[10px] font-bold py-1 rounded border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 cursor-pointer">
                                        <span x-text="copied ? '✅ ¡Copiado!' : '📋 Copiar texto'"></span>
                                    </button>
                                </div>
                            </div>

                            <!-- 3.3 Resumen en Texto Plano -->
                            <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <h4 class="font-bold text-gray-900 dark:text-white text-xs">📋 Resumen Desglosado en Texto</h4>
                                        <span class="text-[9px] bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold px-1.5 py-0.5 rounded-full">Texto Plano</span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 dark:text-slate-400 mt-0.5 leading-snug">
                                        @if($latestQuote)
                                            Total: <strong>{{ number_format($quoteAmount, 2, ',', '.') }} €</strong> | Señal: <strong>{{ number_format($signalAmount, 2, ',', '.') }} €</strong>
                                        @else
                                            <span class="text-amber-600">Genera primero un presupuesto.</span>
                                        @endif
                                    </p>
                                </div>
                                @if($latestQuote)
                                    @if($gatewayActive)
                                        <button type="button" wire:click="sendDirectTemplate('quote_propuesta')" wire:loading.attr="disabled" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition cursor-pointer shrink-0">
                                            <span wire:loading.remove wire:target="sendDirectTemplate('quote_propuesta')">🚀 Enviar</span>
                                            <span wire:loading wire:target="sendDirectTemplate('quote_propuesta')">⏳</span>
                                        </button>
                                    @else
                                        <a href="{{ $quotePropuestaWaUrl }}" target="_blank" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition shrink-0">💬 Enviar</a>
                                    @endif
                                @else
                                    <button disabled class="bg-slate-200 dark:bg-slate-800 text-slate-400 font-bold text-[10px] px-2.5 py-1.5 rounded-lg cursor-not-allowed shrink-0">Sin presupuesto</button>
                                @endif
                            </div>

                            <!-- 3.4 Solicitar Señal de Reserva -->
                            <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <h4 class="font-bold text-gray-900 dark:text-white text-xs">💶 Solicitar Señal ({{ $signalLabel }})</h4>
                                        <span class="text-[9px] bg-blue-100 dark:bg-blue-900/60 text-blue-800 dark:text-blue-300 font-bold px-1.5 py-0.5 rounded-full">IBAN / Bizum</span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 dark:text-slate-400 mt-0.5 leading-snug">
                                        Datos de transferencia y Bizum por <strong>{{ number_format($signalAmount, 2, ',', '.') }} €</strong>.
                                    </p>
                                </div>
                                @if($latestQuote)
                                    @if($gatewayActive)
                                        <button type="button" wire:click="sendDirectTemplate('signal')" wire:loading.attr="disabled" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition cursor-pointer shrink-0">
                                            <span wire:loading.remove wire:target="sendDirectTemplate('signal')">🚀 Enviar</span>
                                            <span wire:loading wire:target="sendDirectTemplate('signal')">⏳</span>
                                        </button>
                                    @else
                                        <a href="{{ $signalWaUrl }}" target="_blank" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition shrink-0">💬 Enviar</a>
                                    @endif
                                @else
                                    <button disabled class="bg-slate-200 dark:bg-slate-800 text-slate-400 font-bold text-[10px] px-2.5 py-1.5 rounded-lg cursor-not-allowed shrink-0">Sin presupuesto</button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================== -->
                    <!-- PESTAÑA 3: SI NO RESPONDEN (TÉCNICA DEL NO & PSICOLOGÍA INVERSA)-->
                    <!-- ============================================================== -->
                    <div x-show="hubTab === 'followup' || hubTab === 'all'" class="space-y-2.5">
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-black uppercase tracking-wider text-rose-600 dark:text-rose-400">🔥 3. Si No Responden (Técnica del "NO" & Psicología Inversa)</span>
                            <div class="h-px bg-rose-200 dark:bg-rose-900/60 flex-1"></div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                            <!-- Técnica del NO -->
                            <div x-data="{ showText: false, copied: false }" class="p-3 rounded-xl border border-rose-200 dark:border-rose-800/70 bg-rose-50/30 dark:bg-rose-950/20 flex flex-col justify-between gap-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <h4 class="font-bold text-gray-900 dark:text-white text-xs">🎯 ¿Descartáis la fecha? (Técnica "NO")</h4>
                                            <span class="text-[9px] bg-rose-100 dark:bg-rose-900/60 text-rose-800 dark:text-rose-300 font-extrabold px-1.5 py-0.5 rounded-full">Recomendado ⭐</span>
                                        </div>
                                        <p class="text-[11px] text-gray-600 dark:text-slate-400 mt-0.5 leading-snug">
                                            Activa la respuesta rápida («¡No lo descartamos!») sin presionar.
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <button type="button" @click="showText = !showText" class="bg-white dark:bg-slate-800 hover:bg-rose-100 text-rose-700 dark:text-rose-300 font-bold text-[11px] px-2 py-1.5 rounded-lg border border-rose-300 dark:border-rose-700 transition cursor-pointer">
                                            <span x-text="showText ? '▲' : '👁️'"></span>
                                        </button>
                                        @if($gatewayActive)
                                            <button type="button" wire:click="sendDirectTemplate('tecnica_no')" wire:loading.attr="disabled" class="bg-rose-600 hover:bg-rose-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition cursor-pointer">
                                                <span wire:loading.remove wire:target="sendDirectTemplate('tecnica_no')">🚀 Enviar</span>
                                                <span wire:loading wire:target="sendDirectTemplate('tecnica_no')">⏳</span>
                                            </button>
                                        @else
                                            <a href="{{ $followupNoWaUrl }}" target="_blank" class="bg-rose-600 hover:bg-rose-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition">💬 Enviar</a>
                                        @endif
                                    </div>
                                </div>
                                <div x-show="showText" style="display: none;" class="bg-white dark:bg-slate-950 p-2.5 rounded-lg border border-rose-200 dark:border-rose-900 text-[11px] space-y-1.5">
                                    <div class="font-mono text-[10px] leading-relaxed whitespace-pre-line text-gray-800 dark:text-slate-200 max-h-32 overflow-y-auto">{{ $followupNoMsg }}</div>
                                    <button type="button" @click="navigator.clipboard.writeText(@js($followupNoMsg)); copied = true; setTimeout(() => copied = false, 2000)" class="w-full text-[10px] font-bold py-1 rounded border border-rose-200 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 cursor-pointer">
                                        <span x-text="copied ? '✅ ¡Copiado!' : '📋 Copiar texto'"></span>
                                    </button>
                                </div>
                            </div>

                            <!-- Psicología Inversa -->
                            <div x-data="{ showText: false, copied: false }" class="p-3 rounded-xl border border-amber-200 dark:border-amber-800/70 bg-amber-50/30 dark:bg-amber-950/20 flex flex-col justify-between gap-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <h4 class="font-bold text-gray-900 dark:text-white text-xs">🧠 Cierre de Expediente (Psicología Inversa)</h4>
                                            <span class="text-[9px] bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-300 font-extrabold px-1.5 py-0.5 rounded-full">Sin ser pesado ⚡</span>
                                        </div>
                                        <p class="text-[11px] text-gray-600 dark:text-slate-400 mt-0.5 leading-snug">
                                            Cierra consulta con elegancia; provoca respuesta si aún les interesa.
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <button type="button" @click="showText = !showText" class="bg-white dark:bg-slate-800 hover:bg-amber-100 text-amber-700 dark:text-amber-300 font-bold text-[11px] px-2 py-1.5 rounded-lg border border-amber-300 dark:border-amber-700 transition cursor-pointer">
                                            <span x-text="showText ? '▲' : '👁️'"></span>
                                        </button>
                                        @if($gatewayActive)
                                            <button type="button" wire:click="sendDirectTemplate('psicologia_inversa')" wire:loading.attr="disabled" class="bg-amber-600 hover:bg-amber-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition cursor-pointer">
                                                <span wire:loading.remove wire:target="sendDirectTemplate('psicologia_inversa')">🚀 Enviar</span>
                                                <span wire:loading wire:target="sendDirectTemplate('psicologia_inversa')">⏳</span>
                                            </button>
                                        @else
                                            <a href="{{ $followupReverseWaUrl }}" target="_blank" class="bg-amber-600 hover:bg-amber-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition">💬 Enviar</a>
                                        @endif
                                    </div>
                                </div>
                                <div x-show="showText" style="display: none;" class="bg-white dark:bg-slate-950 p-2.5 rounded-lg border border-amber-200 dark:border-amber-900 text-[11px] space-y-1.5">
                                    <div class="font-mono text-[10px] leading-relaxed whitespace-pre-line text-gray-800 dark:text-slate-200 max-h-32 overflow-y-auto">{{ $followupReverseMsg }}</div>
                                    <button type="button" @click="navigator.clipboard.writeText(@js($followupReverseMsg)); copied = true; setTimeout(() => copied = false, 2000)" class="w-full text-[10px] font-bold py-1 rounded border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 cursor-pointer">
                                        <span x-text="copied ? '✅ ¡Copiado!' : '📋 Copiar texto'"></span>
                                    </button>
                                </div>
                            </div>

                            <!-- Objeción de Precio -->
                            <div x-data="{ showText: false, copied: false }" class="p-3 rounded-xl border border-indigo-200 dark:border-indigo-800/70 bg-indigo-50/30 dark:bg-indigo-950/20 flex flex-col justify-between gap-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <h4 class="font-bold text-gray-900 dark:text-white text-xs">💡 ¿Duda por Presupuesto? (Adaptar Pack / Horas)</h4>
                                            <span class="text-[9px] bg-indigo-100 dark:bg-indigo-900/60 text-indigo-800 dark:text-indigo-300 font-extrabold px-1.5 py-0.5 rounded-full">Objeción Precio</span>
                                        </div>
                                        <p class="text-[11px] text-gray-600 dark:text-slate-400 mt-0.5 leading-snug">
                                            Ofrece ajustar servicios u horas para encajar en su presupuesto sin perder calidad.
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <button type="button" @click="showText = !showText" class="bg-white dark:bg-slate-800 hover:bg-indigo-100 text-indigo-700 dark:text-indigo-300 font-bold text-[11px] px-2 py-1.5 rounded-lg border border-indigo-300 dark:border-indigo-700 transition cursor-pointer">
                                            <span x-text="showText ? '▲' : '👁️'"></span>
                                        </button>
                                        @if($gatewayActive)
                                            <button type="button" wire:click="sendDirectTemplate('followup_budget')" wire:loading.attr="disabled" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition cursor-pointer">
                                                <span wire:loading.remove wire:target="sendDirectTemplate('followup_budget')">🚀 Enviar</span>
                                                <span wire:loading wire:target="sendDirectTemplate('followup_budget')">⏳</span>
                                            </button>
                                        @else
                                            <a href="{{ $followupBudgetWaUrl }}" target="_blank" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition">💬 Enviar</a>
                                        @endif
                                    </div>
                                </div>
                                <div x-show="showText" style="display: none;" class="bg-white dark:bg-slate-950 p-2.5 rounded-lg border border-indigo-200 dark:border-indigo-900 text-[11px] space-y-1.5">
                                    <div class="font-mono text-[10px] leading-relaxed whitespace-pre-line text-gray-800 dark:text-slate-200 max-h-32 overflow-y-auto">{{ $followupBudgetMsg }}</div>
                                    <button type="button" @click="navigator.clipboard.writeText(@js($followupBudgetMsg)); copied = true; setTimeout(() => copied = false, 2000)" class="w-full text-[10px] font-bold py-1 rounded border border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 cursor-pointer">
                                        <span x-text="copied ? '✅ ¡Copiado!' : '📋 Copiar texto'"></span>
                                    </button>
                                </div>
                            </div>

                            <!-- Fin de Plazo de Presupuesto (+30 días en Espera / Pausa) -->
                            <div x-data="{ showText: false, copied: false }" class="p-3 rounded-xl border border-cyan-200 dark:border-cyan-800/70 bg-cyan-50/30 dark:bg-cyan-950/20 flex flex-col justify-between gap-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <h4 class="font-bold text-gray-900 dark:text-white text-xs">⌛ Fin de Plazo de Presupuesto (+30d en Pausa)</h4>
                                            <span class="text-[9px] bg-cyan-100 dark:bg-cyan-900/60 text-cyan-800 dark:text-cyan-300 font-extrabold px-1.5 py-0.5 rounded-full">Tarifas Vigentes</span>
                                        </div>
                                        <p class="text-[11px] text-gray-600 dark:text-slate-400 mt-0.5 leading-snug">
                                            Informa que vence el plazo de 30 días del presupuesto en espera y que deberán solicitar uno nuevo si retoman la contratación.
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <button type="button" @click="showText = !showText" class="bg-white dark:bg-slate-800 hover:bg-cyan-100 text-cyan-700 dark:text-cyan-300 font-bold text-[11px] px-2 py-1.5 rounded-lg border border-cyan-300 dark:border-cyan-700 transition cursor-pointer">
                                            <span x-text="showText ? '▲' : '👁️'"></span>
                                        </button>
                                        @if($gatewayActive)
                                            <button type="button" wire:click="sendDirectTemplate('fin_plazo_presupuesto')" wire:loading.attr="disabled" class="bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition cursor-pointer">
                                                <span wire:loading.remove wire:target="sendDirectTemplate('fin_plazo_presupuesto')">🚀 Enviar</span>
                                                <span wire:loading wire:target="sendDirectTemplate('fin_plazo_presupuesto')">⏳</span>
                                            </button>
                                        @else
                                            <a href="{{ $finPlazoWaUrl }}" target="_blank" class="bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition">💬 Enviar</a>
                                        @endif
                                    </div>
                                </div>
                                <div x-show="showText" style="display: none;" class="bg-white dark:bg-slate-950 p-2.5 rounded-lg border border-cyan-200 dark:border-cyan-900 text-[11px] space-y-1.5">
                                    <div class="font-mono text-[10px] leading-relaxed whitespace-pre-line text-gray-800 dark:text-slate-200 max-h-32 overflow-y-auto">{{ $finPlazoMsg }}</div>
                                    <button type="button" @click="navigator.clipboard.writeText(@js($finPlazoMsg)); copied = true; setTimeout(() => copied = false, 2000)" class="w-full text-[10px] font-bold py-1 rounded border border-cyan-200 dark:border-cyan-800 bg-cyan-50 dark:bg-cyan-950/50 text-cyan-700 dark:text-cyan-300 cursor-pointer">
                                        <span x-text="copied ? '✅ ¡Copiado!' : '📋 Copiar texto'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================== -->
                    <!-- PESTAÑA 4: CONTRATO, CUESTIONARIO MUSICAL, LIQUIDACIÓN Y STAFF -->
                    <!-- ============================================================== -->
                    <div x-show="hubTab === 'booking' || hubTab === 'all'" class="space-y-2.5">
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400">📜 4. Formalización, Música & Coordinación Staff</span>
                            <div class="h-px bg-indigo-200 dark:bg-indigo-900/60 flex-1"></div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                            <!-- Firma de Contrato Online -->
                            <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <h4 class="font-bold text-gray-900 dark:text-white text-xs">✍️ Firma de Contrato Digital</h4>
                                        <span class="text-[9px] bg-purple-100 dark:bg-purple-900/60 text-purple-700 dark:text-purple-300 font-bold px-1.5 py-0.5 rounded-full">Clientes</span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 dark:text-slate-400 mt-0.5 leading-snug">
                                        @if($firstContract)
                                            Enlace directo para revisar cláusulas y firmar online.
                                        @else
                                            <span class="text-amber-600">Genera primero el contrato.</span>
                                        @endif
                                    </p>
                                </div>
                                @if($firstContract)
                                    @if($gatewayActive)
                                        <button type="button" wire:click="sendDirectTemplate('contrato')" wire:loading.attr="disabled" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition cursor-pointer shrink-0">
                                            <span wire:loading.remove wire:target="sendDirectTemplate('contrato')">🚀 Enviar</span>
                                            <span wire:loading wire:target="sendDirectTemplate('contrato')">⏳</span>
                                        </button>
                                    @else
                                        <a href="{{ $contractWaUrl }}" target="_blank" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition shrink-0">💬 Enviar</a>
                                    @endif
                                @else
                                    <button disabled class="bg-slate-200 dark:bg-slate-800 text-slate-400 font-bold text-[10px] px-2.5 py-1.5 rounded-lg cursor-not-allowed shrink-0">Sin contrato</button>
                                @endif
                            </div>

                            <!-- Cuestionario Musical -->
                            <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <h4 class="font-bold text-gray-900 dark:text-white text-xs">🎵 Cuestionario Musical & Escaleta</h4>
                                        <span class="text-[9px] bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 font-bold px-1.5 py-0.5 rounded-full">Clientes</span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 dark:text-slate-400 mt-0.5 leading-snug">
                                        Enlace personalizado para que anoten sus canciones y momentos.
                                    </p>
                                </div>
                                @if($gatewayActive)
                                    <button type="button" wire:click="sendDirectTemplate('cuestionario')" wire:loading.attr="disabled" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition cursor-pointer shrink-0">
                                        <span wire:loading.remove wire:target="sendDirectTemplate('cuestionario')">🚀 Enviar</span>
                                        <span wire:loading wire:target="sendDirectTemplate('cuestionario')">⏳</span>
                                    </button>
                                @else
                                    <a href="{{ $cuestionarioWaUrl }}" target="_blank" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition shrink-0">💬 Enviar</a>
                                @endif
                            </div>

                            <!-- Liquidación Restante -->
                            <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <h4 class="font-bold text-gray-900 dark:text-white text-xs">💰 Recordatorio Liquidación Restante</h4>
                                        <span class="text-[9px] bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-300 font-bold px-1.5 py-0.5 rounded-full">{{ number_format($remainingAmount, 2, ',', '.') }} €</span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 dark:text-slate-400 mt-0.5 leading-snug">
                                        Recordatorio del importe pendiente antes o el día del evento.
                                    </p>
                                </div>
                                @if($gatewayActive)
                                    <button type="button" wire:click="sendDirectTemplate('liquidacion')" wire:loading.attr="disabled" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition cursor-pointer shrink-0">
                                        <span wire:loading.remove wire:target="sendDirectTemplate('liquidacion')">🚀 Enviar</span>
                                        <span wire:loading wire:target="sendDirectTemplate('liquidacion')">⏳</span>
                                    </button>
                                @else
                                    <a href="{{ $liquidacionWaUrl }}" target="_blank" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition shrink-0">💬 Enviar</a>
                                @endif
                            </div>

                            <!-- Hoja de Ruta DJ / Asistente -->
                            <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <h4 class="font-bold text-gray-900 dark:text-white text-xs">🎧 Hoja de Ruta Técnica & Horarios</h4>
                                        <span class="text-[9px] bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 font-bold px-1.5 py-0.5 rounded-full">Staff</span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 dark:text-slate-400 mt-0.5 leading-snug">
                                        Resumen de montaje, ubicación y enlace a Cabina en vivo.
                                    </p>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    @if($event->dj)
                                        @if($gatewayActive)
                                            <button type="button" wire:click="sendDirectTemplate('hoja_ruta', 'dj')" wire:loading.attr="disabled" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition cursor-pointer">
                                                <span wire:loading.remove wire:target="sendDirectTemplate('hoja_ruta', 'dj')">🎧 DJ</span>
                                                <span wire:loading wire:target="sendDirectTemplate('hoja_ruta', 'dj')">⏳</span>
                                            </button>
                                        @else
                                            <a href="{{ $djWaUrl }}" target="_blank" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition">🎧 DJ</a>
                                        @endif
                                    @endif
                                    @if($event->assistant)
                                        @if($gatewayActive)
                                            <button type="button" wire:click="sendDirectTemplate('hoja_ruta', 'assistant')" wire:loading.attr="disabled" class="bg-amber-600 hover:bg-amber-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition cursor-pointer">
                                                <span wire:loading.remove wire:target="sendDirectTemplate('hoja_ruta', 'assistant')">👷‍♂️ Asist.</span>
                                                <span wire:loading wire:target="sendDirectTemplate('hoja_ruta', 'assistant')">⏳</span>
                                            </button>
                                        @else
                                            <a href="{{ $astWaUrl }}" target="_blank" class="bg-amber-600 hover:bg-amber-500 text-white font-bold text-[11px] px-2.5 py-1.5 rounded-lg shadow-2xs inline-flex items-center gap-1 whitespace-nowrap transition">👷‍♂️ Asist.</a>
                                        @endif
                                    @endif
                                    @if(!$event->dj && !$event->assistant)
                                        <span class="text-[10px] text-slate-400 italic">Sin staff</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Pie Compacto -->
                <div class="bg-slate-50 dark:bg-slate-950 px-4 py-2.5 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-500">
                    <span>💡 Pulsa <strong>👁️</strong> en cualquier plantilla para previsualizar o copiar el texto antes de enviarlo.</span>
                    <button type="button" @click="openWhatsappHub = false" class="bg-white dark:bg-slate-800 hover:bg-slate-100 text-slate-700 dark:text-slate-200 font-bold text-xs px-4 py-1.5 border border-slate-300 dark:border-slate-700 rounded-xl transition cursor-pointer">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal QR Peticiones Invitados -->
    <div x-show="openQrModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="openQrModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-3xl text-center overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full p-6" x-data="{ copiedQrUrl: false }">
                <div class="w-16 h-16 bg-gradient-to-tr from-pink-500 to-indigo-600 rounded-2xl mx-auto flex items-center justify-center text-white text-3xl shadow-lg mb-4">
                    📱
                </div>
                
                <h3 class="text-xl font-extrabold text-gray-900">QR de Peticiones en Vivo</h3>
                <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">Coloca este QR en las mesas, barra o cabina para que los invitados pidan y voten canciones desde sus móviles.</p>

                <div class="my-6 p-4 bg-gray-50 rounded-2xl border border-gray-100 inline-block shadow-inner">
                    {{-- QR generado en el navegador: el enlace con token no se envía a servicios de terceros --}}
                    <div wire:ignore
                         x-data="{ url: @js(route('guest.requests', $event->guest_token)) }"
                         x-init="
                            const draw = () => { $refs.qr.innerHTML = ''; new QRCode($refs.qr, { text: url, width: 192, height: 192, correctLevel: QRCode.CorrectLevel.M }); };
                            if (window.QRCode) { draw(); } else {
                                const s = document.createElement('script');
                                s.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js';
                                s.onload = draw;
                                document.head.appendChild(s);
                            }
                         "
                         class="w-52 h-52 mx-auto rounded-xl shadow-md bg-white p-2 flex items-center justify-center">
                        <div x-ref="qr" role="img" aria-label="QR Peticiones"></div>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <input type="text" readonly value="{{ route('guest.requests', $event->guest_token) }}" class="flex-1 border-gray-300 rounded-xl text-xs bg-gray-50 px-3 py-2 select-all focus:ring-indigo-500" id="qrLinkInput">
                        <button type="button" @click="
                            const inp = document.getElementById('qrLinkInput');
                            inp.select();
                            inp.setSelectionRange(0, 99999);
                            if (navigator.clipboard && window.isSecureContext) {
                                navigator.clipboard.writeText(inp.value);
                            } else {
                                document.execCommand('copy');
                            }
                            copiedQrUrl = true;
                            setTimeout(() => copiedQrUrl = false, 2500);
                        " class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold px-3 py-2 rounded-xl text-xs border border-indigo-200 transition">
                            <span x-show="!copiedQrUrl">📋 Copiar</span>
                            <span x-show="copiedQrUrl" class="text-emerald-600" style="display: none;">✅ ¡Copiado!</span>
                        </button>
                    </div>

                    <div class="flex gap-2 justify-center">
                        <a href="{{ route('guest.requests', $event->guest_token) }}" target="_blank" class="flex-1 bg-gradient-to-r from-pink-600 to-indigo-600 hover:from-pink-700 hover:to-indigo-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs shadow-md transition">
                            🚀 Abrir Portal Invitados
                        </a>
                        <button type="button" @click="openQrModal = false" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 px-4 rounded-xl text-xs transition">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navegación por pestañas (Adaptable en Varias Líneas / Responsive) -->
    <div class="mb-4 border-b border-gray-200 dark:border-slate-800 -mx-4 px-4 sm:mx-0 sm:px-0">
        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center min-w-full gap-y-1">
            <li class="mr-1.5">
                <button wire:click="$set('activeTab', 'dossier')" class="inline-block px-3.5 py-2.5 rounded-t-lg border-b-2 font-bold text-xs sm:text-sm cursor-pointer {{ $activeTab == 'dossier' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400 bg-indigo-50/40 dark:bg-indigo-950/30' : 'border-transparent hover:text-gray-600 dark:hover:text-slate-300 hover:border-gray-300 dark:hover:border-slate-700 text-gray-500 dark:text-slate-400' }}">
                    📋 Dossier y Seguimiento
                </button>
            </li>
            @if(Auth::check() && Auth::user()->role === 'admin')
            <li class="mr-1.5">
                <button wire:click="$set('activeTab', 'quotes')" class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-t-lg border-b-2 font-bold text-xs sm:text-sm cursor-pointer {{ $activeTab == 'quotes' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400 bg-indigo-50/50 dark:bg-indigo-950/40' : 'border-transparent hover:text-gray-600 dark:hover:text-slate-300 hover:border-gray-300 dark:hover:border-slate-700 text-gray-500 dark:text-slate-400' }}">
                    <span>💶 Presupuestos</span>
                    @if($latestQuote)
                        @if($latestQuote->status === 'accepted')
                            <span class="text-[10px] bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 px-1.5 py-0.5 rounded-full font-black">✅ {{ number_format($latestQuote->amount, 0) }}€</span>
                        @elseif($latestQuote->status === 'rejected')
                            <span class="text-[10px] bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 px-1.5 py-0.5 rounded-full font-black">❌ Rechazado</span>
                        @else
                            <span class="text-[10px] bg-indigo-100 text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-300 px-1.5 py-0.5 rounded-full font-bold">{{ number_format($latestQuote->amount, 0) }}€</span>
                        @endif
                    @endif
                </button>
            </li>
            <li class="mr-1.5">
                <button wire:click="$set('activeTab', 'contracts')" class="inline-block px-3.5 py-2.5 rounded-t-lg border-b-2 font-bold text-xs sm:text-sm cursor-pointer {{ $activeTab == 'contracts' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400 bg-indigo-50/40 dark:bg-indigo-950/30' : 'border-transparent hover:text-gray-600 dark:hover:text-slate-300 hover:border-gray-300 dark:hover:border-slate-700 text-gray-500 dark:text-slate-400' }}">
                    ✍️ Contratos
                </button>
            </li>
            <li class="mr-1.5">
                <button wire:click="$set('activeTab', 'invoices')" class="inline-block px-3.5 py-2.5 rounded-t-lg border-b-2 font-bold text-xs sm:text-sm cursor-pointer {{ $activeTab == 'invoices' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400 bg-indigo-50/40 dark:bg-indigo-950/30' : 'border-transparent hover:text-gray-600 dark:hover:text-slate-300 hover:border-gray-300 dark:hover:border-slate-700 text-gray-500 dark:text-slate-400' }}">
                    🧾 Facturas
                </button>
            </li>
            @endif
            <li class="mr-1.5">
                <button wire:click="$set('activeTab', 'music')" class="inline-block px-3.5 py-2.5 rounded-t-lg border-b-2 font-bold text-xs sm:text-sm cursor-pointer {{ $activeTab == 'music' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400 bg-indigo-50/40 dark:bg-indigo-950/30' : 'border-transparent hover:text-gray-600 dark:hover:text-slate-300 hover:border-gray-300 dark:hover:border-slate-700 text-gray-500 dark:text-slate-400' }}">
                    🎵 Música y Momentos <span class="ml-1 text-xs bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 px-2 py-0.5 rounded-full font-bold">{{ $event->musicRequests->count() }}</span>
                </button>
            </li>
            <li class="mr-1.5">
                <button wire:click="$set('activeTab', 'equipment')" class="inline-block px-3.5 py-2.5 rounded-t-lg border-b-2 font-bold text-xs sm:text-sm cursor-pointer {{ $activeTab == 'equipment' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400 bg-indigo-50/40 dark:bg-indigo-950/30' : 'border-transparent hover:text-gray-600 dark:hover:text-slate-300 hover:border-gray-300 dark:hover:border-slate-700 text-gray-500 dark:text-slate-400' }}">
                    📦 Material / Carga
                </button>
            </li>
            <li class="mr-1.5">
                <button wire:click="$set('activeTab', 'meetings')" class="inline-block px-3.5 py-2.5 rounded-t-lg border-b-2 font-bold text-xs sm:text-sm cursor-pointer {{ $activeTab == 'meetings' ? 'border-purple-600 text-purple-600 dark:text-purple-400 dark:border-purple-400 bg-purple-50/40 dark:bg-purple-950/30' : 'border-transparent hover:text-gray-600 dark:hover:text-slate-300 hover:border-gray-300 dark:hover:border-slate-700 text-gray-500 dark:text-slate-400' }}">
                    📅 Citas y Reuniones <span class="ml-1 text-xs bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 px-2 py-0.5 rounded-full font-bold">{{ $event->meetings->count() }}</span>
                </button>
            </li>
        </ul>
    </div>

    <!-- Contenido Pestañas -->
    <div class="bg-white shadow sm:rounded-lg p-4 sm:p-5">
        
        {{-- DOSSIER Y ACUERDOS WHATSAPP --}}
        @if($activeTab == 'dossier')
            <div class="space-y-6">
                @if (session()->has('notes_message')) 
                    <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-xl text-xs font-bold shadow-xs">
                        ✓ {{ session('notes_message') }}
                    </div> 
                @endif
                @if (session()->has('dossier_message')) 
                    <div class="bg-indigo-50 border-l-4 border-indigo-500 text-indigo-800 p-4 rounded-xl text-xs font-bold shadow-xs">
                        ✓ {{ session('dossier_message') }}
                    </div> 
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Columna 1: Centro de Comunicación WhatsApp, Email & Notas del Evento -->
                    <div class="bg-slate-50/80 dark:bg-slate-900/90 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">💬</span>
                                <div>
                                    <h4 class="font-bold text-slate-900 dark:text-white text-sm">WhatsApp, Email & Acuerdos del Evento</h4>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Historial de WhatsApp y Emails con el cliente, plantillas de seguimiento y notas internas</p>
                                </div>
                            </div>
                            <button type="button" wire:click="saveEventNotes" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-xs transition cursor-pointer">
                                💾 Guardar Notas
                            </button>
                        </div>

                        <!-- Estado, Teléfono y Email del Cliente -->
                        @php
                            $clientPhone = $event->client ? $event->client->phone : $event->partner_phone;
                            $cleanPhone = \App\Services\WhatsAppTemplateService::cleanPhone($clientPhone ?? '');
                            $clientEmail = $event->client_contact_email;
                            $gatewayActive = \App\Services\EvolutionWhatsAppService::isEnabled();
                        @endphp
                        <div class="p-3 bg-white dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Cliente:</span>
                                <span class="text-xs font-black text-slate-900 dark:text-white">{{ $event->client ? $event->client->name : ($event->partner_name ?? 'Sin nombre') }}</span>
                                @if($cleanPhone)
                                    <a href="https://wa.me/{{ $cleanPhone }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 dark:text-emerald-300 hover:text-emerald-800 dark:hover:text-emerald-200 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-md border border-emerald-200 dark:border-emerald-800 transition">
                                        <span>📱</span> +{{ $cleanPhone }}
                                    </a>
                                @else
                                    <span class="text-[11px] text-amber-600 dark:text-amber-400 font-bold bg-amber-50 dark:bg-amber-950/50 px-2 py-0.5 rounded-md border border-amber-200 dark:border-amber-800">📵 Sin teléfono</span>
                                @endif

                                @if($clientEmail)
                                    <a href="mailto:{{ $clientEmail }}" class="inline-flex items-center gap-1 text-[11px] font-bold text-indigo-700 dark:text-indigo-300 hover:text-indigo-800 dark:hover:text-indigo-200 bg-indigo-50 dark:bg-indigo-950/60 px-2 py-0.5 rounded-md border border-indigo-200 dark:border-indigo-800 transition">
                                        <span>📧</span> {{ $clientEmail }}
                                    </a>
                                @else
                                    <span class="text-[11px] text-slate-400 dark:text-slate-500 font-medium bg-slate-50 dark:bg-slate-900 px-2 py-0.5 rounded-md border border-slate-200 dark:border-slate-800">Sin email</span>
                                @endif
                            </div>
                            <div>
                                @if($gatewayActive)
                                    <span class="inline-flex items-center gap-1.5 text-[10px] font-black text-emerald-800 dark:text-emerald-300 bg-emerald-100 dark:bg-emerald-950/80 px-2.5 py-0.5 rounded-full border border-emerald-300 dark:border-emerald-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Evolution API + Email
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-900 px-2.5 py-0.5 rounded-full border border-slate-200 dark:border-slate-800" title="Envía mediante enlaces wa.me o por Email">
                                        🔗 WhatsApp / Email Activo
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Visualizador / Editor principal de Notas del Cliente y del Evento (ARRIBA PARA VERLO DE INMEDIATO) -->
                        <div class="p-3.5 bg-amber-50/60 dark:bg-slate-950 rounded-xl border border-amber-200 dark:border-slate-800 shadow-2xs space-y-2.5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <label class="block text-xs font-extrabold text-amber-950 dark:text-amber-300 flex items-center gap-1.5">
                                    <span>📝</span> Notas del Cliente y Requisitos Generales del Evento
                                </label>
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-semibold text-amber-800/80 dark:text-slate-400">
                                        📌 Las notas del formulario web y acuerdos aparecen aquí arriba
                                    </span>
                                    <button type="button" wire:click="saveEventNotes" class="bg-emerald-600 hover:bg-emerald-700 text-white px-2.5 py-1 rounded-lg text-[11px] font-bold shadow-2xs transition cursor-pointer">
                                        💾 Guardar
                                    </button>
                                </div>
                            </div>
                            @if(in_array($event->status, ['rejected', 'cancelled']) && $event->cancellation_reason_display)
                                <div class="p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 flex items-center justify-between gap-2 text-xs">
                                    <div class="text-rose-900 dark:text-rose-200 font-bold">
                                        <span>{{ $event->status === 'rejected' ? '❌ Motivo de rechazo:' : '🔴 Motivo de cancelación:' }}</span>
                                        <span class="font-extrabold ml-1">{{ $event->cancellation_reason_display }}</span>
                                    </div>
                                    <button type="button" wire:click="openCancellationModal('{{ $event->status }}')" class="text-[11px] font-bold text-rose-700 dark:text-rose-300 hover:underline shrink-0 cursor-pointer">
                                        ✏️ Cambiar
                                    </button>
                                </div>
                            @endif
                            <textarea wire:model="event_notes" rows="6" class="w-full text-xs font-mono bg-white dark:bg-slate-900 border border-amber-300 dark:border-slate-700 rounded-xl p-3 focus:ring-indigo-500 focus:border-indigo-500 text-slate-900 dark:text-white leading-relaxed placeholder-slate-400 dark:placeholder-slate-500" placeholder="Aquí aparecen las notas que escribe el cliente al pedir presupuesto o rellenar el formulario, además de tus notas internas y servicios solicitados..."></textarea>

                            <!-- Pegar fragmento o nota rápida arriba -->
                            <div class="pt-1 border-t border-amber-200/70 dark:border-slate-800 flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                <input type="text" wire:model="whatsapp_chat_input" wire:keydown.enter="applyWhatsappTextToNotes" class="flex-1 text-xs border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 focus:ring-emerald-500 focus:border-emerald-500 bg-white dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500" placeholder="➕ Escribe o pega una nota rápida para añadirla arriba con fecha..." />
                                <button type="button" wire:click="applyWhatsappTextToNotes" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1 cursor-pointer shrink-0">
                                    <span>⬆️</span> Añadir arriba en Notas
                                </button>
                            </div>
                        </div>

                        <!-- Selector de Remitente de WhatsApp / Email (Miguel, Fran, Ezequiel) -->
                        <div class="p-2.5 bg-slate-100 dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-700 dark:text-slate-200">
                                <span class="text-emerald-600 dark:text-emerald-400">📲</span>
                                <span>Remitente / Firma:</span>
                            </div>
                            <div class="inline-flex rounded-xl p-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xs gap-1 flex-wrap">
                                <button type="button" 
                                    wire:click="setSenderBrand('miguel')"
                                    class="px-2.5 py-1 rounded-lg font-bold text-[11px] transition flex items-center gap-1 cursor-pointer {{ in_array(($whatsapp_sender_brand ?? 'miguel'), ['miguel', 'nunez', 'nunez_and_son']) ? 'bg-amber-500 text-white shadow-xs ring-1 ring-amber-400/40' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                                    <span>👑</span> Miguel (Núñez & Son)
                                </button>
                                <button type="button" 
                                    wire:click="setSenderBrand('fran')"
                                    class="px-2.5 py-1 rounded-lg font-bold text-[11px] transition flex items-center gap-1 cursor-pointer {{ in_array(($whatsapp_sender_brand ?? ''), ['fran', 'javnx', 'javnx_dj']) ? 'bg-indigo-600 text-white shadow-xs ring-1 ring-indigo-400/40' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                                    <span>🎧</span> Fran (JAVNX DJ / Núñez)
                                </button>
                                <button type="button" 
                                    wire:click="setSenderBrand('ezequiel')"
                                    class="px-2.5 py-1 rounded-lg font-bold text-[11px] transition flex items-center gap-1 cursor-pointer {{ ($whatsapp_sender_brand ?? '') === 'ezequiel' ? 'bg-sky-600 text-white shadow-xs ring-1 ring-sky-400/40' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                                    <span>👥</span> Ezequiel (Núñez & Son)
                                </button>
                            </div>
                        </div>

                        <!-- Historial de Chat WhatsApp & Email -->
                        <div wire:init="autoSyncWhatsAppOnLoad" class="bg-slate-100 dark:bg-slate-950 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 space-y-2.5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider flex items-center gap-1">
                                    <span>🗨️</span> Conversación Registrada (WhatsApp & Email)
                                </span>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <button type="button"
                                            wire:click="$toggle('showWhatsAppImportBox')"
                                            class="text-[10px] font-bold px-2.5 py-1 rounded-lg border transition cursor-pointer flex items-center gap-1 {{ $showWhatsAppImportBox ? 'bg-indigo-600 text-white border-indigo-500 shadow-xs' : 'bg-white dark:bg-slate-900 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800 hover:bg-indigo-50 dark:hover:bg-slate-800' }}">
                                        <span>📋</span>
                                        <span>{{ $showWhatsAppImportBox ? 'Cerrar importador' : 'Pegar / Añadir conversación' }}</span>
                                    </button>
                                    @if($this->whatsappMessages && $this->whatsappMessages->count() > 0)
                                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-900 px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-800">
                                            {{ $this->whatsappMessages->count() }} mensaje(s)
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Buscador de conversación previa por número de teléfono o email (Evolution API + IMAP + Base de Datos) -->
                            <div class="p-2 bg-white dark:bg-slate-900/90 rounded-lg border border-slate-200 dark:border-slate-800 flex flex-wrap items-center gap-1.5">
                                <span class="text-[10px] font-bold text-slate-600 dark:text-slate-400 flex items-center gap-1 shrink-0">
                                    <span>🔍</span> Buscar por Nº o Email:
                                </span>
                                <input type="text"
                                       wire:model="whatsapp_sync_phone"
                                       placeholder="Ej: 688868344 o cliente@email.com"
                                       class="flex-1 min-w-[140px] text-xs px-2.5 py-1 rounded-md border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-emerald-500 focus:border-emerald-500 font-semibold" />
                                <button type="button"
                                        wire:click="syncWhatsAppHistoryByPhone"
                                        wire:loading.attr="disabled"
                                        class="px-2.5 py-1 rounded-md text-[10px] font-extrabold bg-emerald-600 hover:bg-emerald-700 text-white border border-emerald-500 transition shadow-2xs cursor-pointer flex items-center gap-1 shrink-0">
                                    <span wire:loading.remove wire:target="syncWhatsAppHistoryByPhone">🔄 Buscar y Añadir al Evento</span>
                                    <span wire:loading wire:target="syncWhatsAppHistoryByPhone">⏳ Buscando mensajes...</span>
                                </button>
                            </div>

                            <!-- Panel desplegable para Pegar / Añadir conversación anterior manualmente -->
                            @if($showWhatsAppImportBox)
                                <div class="p-3 bg-indigo-50/70 dark:bg-indigo-950/30 rounded-xl border border-indigo-200 dark:border-indigo-800/80 space-y-2">
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <span class="text-[11px] font-extrabold text-indigo-900 dark:text-indigo-200 block">
                                                📥 Importar conversación anterior de WhatsApp o Email
                                            </span>
                                            <p class="text-[10px] text-indigo-700 dark:text-indigo-300/90 leading-snug">
                                                Puedes <strong>copiar y pegar varios mensajes de WhatsApp</strong> (con formato <code>[fecha, hora] Nombre: texto</code>), o pegar un <strong>correo electrónico / mensaje suelto</strong> eligiendo el canal y quién lo envió:
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2 pt-0.5">
                                        <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300">Canal:</span>
                                        <button type="button"
                                                wire:click="$set('whatsapp_import_channel', 'whatsapp')"
                                                class="px-2 py-0.5 rounded-md text-[10px] font-bold border transition cursor-pointer {{ ($whatsapp_import_channel ?? 'whatsapp') === 'whatsapp' ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border-slate-300 dark:border-slate-700' }}">
                                            💬 WhatsApp
                                        </button>
                                        <button type="button"
                                                wire:click="$set('whatsapp_import_channel', 'email')"
                                                class="px-2 py-0.5 rounded-md text-[10px] font-bold border transition cursor-pointer {{ ($whatsapp_import_channel ?? 'whatsapp') === 'email' ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border-slate-300 dark:border-slate-700' }}">
                                            📧 Email
                                        </button>

                                        <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300 ml-1">Dirección:</span>
                                        <button type="button"
                                                wire:click="$set('whatsapp_import_direction', 'inbound')"
                                                class="px-2 py-0.5 rounded-md text-[10px] font-bold border transition cursor-pointer {{ $whatsapp_import_direction === 'inbound' ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border-slate-300 dark:border-slate-700' }}">
                                            ⬅️ Recibido del Cliente
                                        </button>
                                        <button type="button"
                                                wire:click="$set('whatsapp_import_direction', 'outbound')"
                                                class="px-2 py-0.5 rounded-md text-[10px] font-bold border transition cursor-pointer {{ $whatsapp_import_direction === 'outbound' ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border-slate-300 dark:border-slate-700' }}">
                                            ➡️ Enviado por Nosotros
                                        </button>
                                        <input type="datetime-local"
                                               wire:model="whatsapp_import_date"
                                               class="text-[10px] px-2 py-0.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200" />
                                    </div>

                                    <textarea wire:model="whatsapp_import_text"
                                              rows="3"
                                              class="w-full text-xs border border-indigo-300 dark:border-indigo-800 rounded-lg p-2 bg-white dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400"
                                              placeholder="Pega aquí la conversación de WhatsApp o el contenido del Email recibido/enviado..."></textarea>

                                    <div class="flex justify-end gap-2">
                                        <button type="button"
                                                wire:click="$set('showWhatsAppImportBox', false)"
                                                class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border border-slate-300 dark:border-slate-700 cursor-pointer">
                                            Cancelar
                                        </button>
                                        <button type="button"
                                                wire:click="importWhatsAppConversationText"
                                                class="px-3 py-1 rounded-lg text-[10px] font-extrabold bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition cursor-pointer flex items-center gap-1">
                                            <span>➕</span> Añadir a la Conversación
                                        </button>
                                    </div>
                                </div>
                            @endif

                            <div class="max-h-64 overflow-y-auto space-y-2.5 p-2 bg-slate-50 dark:bg-slate-900/70 rounded-lg border border-slate-200 dark:border-slate-800 text-xs">
                                @forelse($this->whatsappMessages as $msg)
                                    @php
                                        $isEmailMsg = (($msg->payload['channel'] ?? '') === 'email')
                                            || str_contains((string) ($msg->phone ?? ''), '@')
                                            || str_starts_with((string) ($msg->message ?? ''), '📧');
                                    @endphp
                                    @if($msg->direction === 'outbound')
                                        <!-- Mensaje Saliente (Staff / DJ) -->
                                        <div class="flex flex-col items-end group">
                                            <div class="max-w-[85%] {{ $isEmailMsg ? 'bg-indigo-100/90 dark:bg-indigo-950/80 text-indigo-950 dark:text-indigo-100 border-indigo-200 dark:border-indigo-800' : 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-950 dark:text-emerald-100 border-emerald-200 dark:border-emerald-800' }} p-2.5 rounded-2xl rounded-tr-xs border shadow-2xs space-y-1 relative">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="inline-flex items-center gap-1 text-[9px] font-extrabold uppercase px-1.5 py-0.2 rounded {{ $isEmailMsg ? 'bg-indigo-200/80 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200' : 'bg-emerald-200/70 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200' }}">
                                                        {{ $isEmailMsg ? '📧 Email enviado' : '💬 WhatsApp' }}
                                                    </span>
                                                    @if(!empty($msg->payload['to'] ?? null))
                                                        <span class="text-[9px] opacity-75 truncate">{{ $msg->payload['to'] }}</span>
                                                    @endif
                                                </div>
                                                <p class="whitespace-pre-line leading-relaxed text-[11px]">{{ $msg->message }}</p>
                                                <div class="flex items-center justify-end gap-1.5 text-[9px] {{ $isEmailMsg ? 'text-indigo-800 dark:text-indigo-300' : 'text-emerald-800 dark:text-emerald-300' }} font-bold">
                                                    <span>{{ $msg->user ? $msg->user->name : ($msg->sender_name ?: 'Staff') }}</span>
                                                    <span>&bull;</span>
                                                    <span>{{ $msg->created_at->format('d/m/Y H:i') }}</span>
                                                    <span class="{{ $isEmailMsg ? 'text-indigo-600 dark:text-indigo-400' : 'text-emerald-600 dark:text-emerald-400' }} font-black">✓✓</span>
                                                    <button type="button"
                                                            wire:click="deleteWhatsAppMessage({{ $msg->id }})"
                                                            wire:confirm="¿Seguro que quieres quitar este mensaje del historial del evento?"
                                                            title="Quitar mensaje del historial"
                                                            class="opacity-0 group-hover:opacity-100 text-rose-500 hover:text-rose-400 ml-1 transition cursor-pointer">
                                                        &times;
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <!-- Mensaje Entrante (Cliente) -->
                                        <div class="flex flex-col items-start group">
                                            <div class="max-w-[85%] bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-100 p-2.5 rounded-2xl rounded-tl-xs border {{ $isEmailMsg ? 'border-indigo-200 dark:border-indigo-800/80' : 'border-gray-200 dark:border-slate-700' }} shadow-2xs space-y-1 relative">
                                                <div class="flex items-center justify-between gap-2 text-[10px] font-black text-indigo-700 dark:text-indigo-400">
                                                    <div class="flex items-center gap-1.5 flex-wrap">
                                                        <span class="inline-flex items-center gap-0.5 text-[9px] font-extrabold uppercase px-1.5 py-0.2 rounded {{ $isEmailMsg ? 'bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300' : 'bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300' }}">
                                                            {{ $isEmailMsg ? '📧 Email' : '💬 WhatsApp' }}
                                                        </span>
                                                        <span>{{ $msg->sender_name ?: ($event->client ? $event->client->name : 'Cliente') }}</span>
                                                    </div>
                                                    <button type="button"
                                                            wire:click="deleteWhatsAppMessage({{ $msg->id }})"
                                                            wire:confirm="¿Seguro que quieres quitar este mensaje del historial del evento?"
                                                            title="Quitar mensaje del historial"
                                                            class="opacity-0 group-hover:opacity-100 text-rose-500 hover:text-rose-400 transition cursor-pointer">
                                                        &times;
                                                    </button>
                                                </div>
                                                <p class="whitespace-pre-line leading-relaxed text-[11px]">{{ $msg->message }}</p>
                                                <div class="text-right text-[9px] text-gray-400 dark:text-slate-400 font-medium">
                                                    {{ $msg->created_at->format('d/m/Y H:i') }}
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @empty
                                    <div class="py-6 text-center text-gray-400 dark:text-slate-500 space-y-1">
                                        <span class="text-2xl block">💬 📧</span>
                                        <span class="text-xs font-semibold text-gray-600 dark:text-slate-300">Sin mensajes registrados aún</span>
                                        <p class="text-[10px] text-gray-400 dark:text-slate-400 max-w-xs mx-auto">Pulsa arriba en <strong>«🔄 Buscar y Añadir al Evento»</strong> (por teléfono o email) o usa una plantilla abajo para responder por WhatsApp o Email.</p>
                                    </div>
                                @endforelse
                            </div>

                            <!-- Selector Rápido de Plantillas Madrigal -->
                            <div class="space-y-1.5 pt-1">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 block">
                                        ⚡ Plantillas Rápidas Madrigal & Comerciales (para WhatsApp o Email):
                                    </span>
                                    <button type="button"
                                            wire:click="toggleTemplateEditor"
                                            class="text-[10px] font-bold px-2.5 py-0.5 rounded-lg border transition cursor-pointer flex items-center gap-1 {{ $showTemplateEditor ? 'bg-amber-500 text-slate-950 border-amber-400 shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border-slate-300 dark:border-slate-700 hover:border-amber-400 hover:text-amber-600 dark:hover:text-amber-400' }}">
                                        <span>✏️</span>
                                        <span>{{ $showTemplateEditor ? 'Cerrar editor de plantillas' : 'Personalizar textos de plantillas' }}</span>
                                    </button>
                                </div>

                                @if($showTemplateEditor)
                                    <div class="p-3 bg-amber-50/70 dark:bg-slate-900/95 rounded-xl border border-amber-300 dark:border-amber-700/70 space-y-2.5 my-1.5">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <div class="flex items-center gap-2 flex-1 min-w-[200px]">
                                                <label class="text-[10px] font-black uppercase text-amber-800 dark:text-amber-300 shrink-0">Plantilla a editar:</label>
                                                <select wire:model.live="editingTemplateKey" class="flex-1 text-xs px-2.5 py-1 rounded-lg border border-amber-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white font-bold">
                                                    @foreach(\App\Services\WhatsAppTemplateService::getEditableTemplatesCatalog() as $tplKey => $tplMeta)
                                                        <option value="{{ $tplKey }}">
                                                            {{ $tplMeta['label'] }}{{ \App\Services\WhatsAppTemplateService::hasCustomTemplate($tplKey) ? ' (Personalizada ✓)' : '' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <button type="button"
                                                    wire:click="resetTemplateToDefault"
                                                    wire:confirm="¿Restaurar esta plantilla a su texto original por defecto?"
                                                    class="text-[10px] font-bold px-2 py-1 rounded-lg bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-700 hover:text-rose-600 dark:hover:text-rose-400 transition cursor-pointer">
                                                🔄 Restaurar original
                                            </button>
                                        </div>

                                        <div class="text-[10px] text-slate-600 dark:text-slate-400 leading-relaxed">
                                            Variables automáticas que puedes usar:
                                            <code class="px-1 py-0.5 rounded bg-white dark:bg-slate-800 text-amber-700 dark:text-amber-300 font-bold">{nombre}</code>
                                            <code class="px-1 py-0.5 rounded bg-white dark:bg-slate-800 text-amber-700 dark:text-amber-300 font-bold">{firma}</code>
                                            <code class="px-1 py-0.5 rounded bg-white dark:bg-slate-800 text-amber-700 dark:text-amber-300 font-bold">{fecha}</code>
                                            <code class="px-1 py-0.5 rounded bg-white dark:bg-slate-800 text-amber-700 dark:text-amber-300 font-bold">{mes}</code>
                                            <code class="px-1 py-0.5 rounded bg-white dark:bg-slate-800 text-amber-700 dark:text-amber-300 font-bold">{lugar}</code>
                                            <code class="px-1 py-0.5 rounded bg-white dark:bg-slate-800 text-amber-700 dark:text-amber-300 font-bold">{evento}</code>
                                            <code class="px-1 py-0.5 rounded bg-white dark:bg-slate-800 text-amber-700 dark:text-amber-300 font-bold">{empresa}</code>
                                            <code class="px-1 py-0.5 rounded bg-white dark:bg-slate-800 text-amber-700 dark:text-amber-300 font-bold">{horario}</code>
                                            <code class="px-1 py-0.5 rounded bg-white dark:bg-slate-800 text-amber-700 dark:text-amber-300 font-bold">{importe_senal}</code>
                                            <code class="px-1 py-0.5 rounded bg-white dark:bg-slate-800 text-amber-700 dark:text-amber-300 font-bold">{importe_pendiente}</code>
                                            <code class="px-1 py-0.5 rounded bg-white dark:bg-slate-800 text-amber-700 dark:text-amber-300 font-bold">{fecha_limite_pago}</code>
                                            <code class="px-1 py-0.5 rounded bg-white dark:bg-slate-800 text-amber-700 dark:text-amber-300 font-bold">{enlace_presupuesto}</code>
                                            <code class="px-1 py-0.5 rounded bg-white dark:bg-slate-800 text-amber-700 dark:text-amber-300 font-bold">{enlace_dossier}</code>
                                            <code class="px-1 py-0.5 rounded bg-white dark:bg-slate-800 text-amber-700 dark:text-amber-300 font-bold">{enlace_cuestionario}</code>
                                            <code class="px-1 py-0.5 rounded bg-white dark:bg-slate-800 text-amber-700 dark:text-amber-300 font-bold">{enlace_contrato}</code>
                                        </div>

                                        <textarea wire:model="editingTemplateRawText"
                                                  rows="6"
                                                  class="w-full text-xs p-2.5 rounded-lg border border-amber-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-amber-500 focus:border-amber-500 leading-relaxed"></textarea>

                                        <div class="flex justify-end gap-2">
                                            <button type="button"
                                                    wire:click="saveTemplateCustomization"
                                                    class="px-3 py-1.5 rounded-lg text-[11px] font-extrabold bg-amber-500 hover:bg-amber-600 text-slate-950 shadow-xs transition cursor-pointer flex items-center gap-1">
                                                <span>💾</span> Guardar Plantilla para todos los Eventos
                                            </button>
                                        </div>
                                    </div>
                                @endif

                                <div class="flex flex-wrap gap-1.5">
                                    <button type="button" wire:click="loadWhatsAppTemplate('primer_contacto_presupuesto')" class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold bg-emerald-600 hover:bg-emerald-700 text-white border border-emerald-500 transition shadow-xs cursor-pointer flex items-center gap-1">
                                        <span>👋</span> 1er Contacto + /presupuesto
                                    </button>
                                    <button type="button" wire:click="loadWhatsAppTemplate('primer_contacto_madrigal')" class="px-2 py-1 rounded-lg text-[10px] font-bold bg-white dark:bg-slate-900 hover:bg-emerald-50 dark:hover:bg-slate-800 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/60 hover:border-emerald-300 transition shadow-2xs cursor-pointer flex items-center gap-1">
                                        <span>🟢</span> 1er Contacto (Madrigal)
                                    </button>
                                    <button type="button" wire:click="loadWhatsAppTemplate('verificacion_lead')" class="px-2 py-1 rounded-lg text-[10px] font-bold bg-white dark:bg-slate-900 hover:bg-orange-50 dark:hover:bg-slate-800 text-orange-700 dark:text-orange-400 border border-orange-200 dark:border-orange-800/60 hover:border-orange-300 transition shadow-2xs cursor-pointer flex items-center gap-1" title="Antes de enviar presupuesto: pedir nombre completo y confirmar teléfono">
                                        <span>🕵️</span> Verificación Lead
                                    </button>
                                    <button type="button" wire:click="loadWhatsAppTemplate('llamada')" class="px-2 py-1 rounded-lg text-[10px] font-bold bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-800 hover:border-slate-300 transition shadow-2xs cursor-pointer flex items-center gap-1">
                                        <span>📞</span> Proponer Llamada
                                    </button>
                                    <button type="button" wire:click="loadWhatsAppTemplate('dossier')" class="px-2 py-1 rounded-lg text-[10px] font-bold bg-white dark:bg-slate-900 hover:bg-cyan-50 dark:hover:bg-slate-800 text-cyan-700 dark:text-cyan-400 border border-slate-200 dark:border-slate-800 hover:border-cyan-300 transition shadow-2xs cursor-pointer flex items-center gap-1">
                                        <span>📑</span> Envío Dossier
                                    </button>
                                    <button type="button" wire:click="loadWhatsAppTemplate('dia3_dudas')" class="px-2 py-1 rounded-lg text-[10px] font-bold bg-white dark:bg-slate-900 hover:bg-indigo-50 dark:hover:bg-slate-800 text-indigo-700 dark:text-indigo-400 border border-slate-200 dark:border-slate-800 hover:border-indigo-300 transition shadow-2xs cursor-pointer flex items-center gap-1">
                                        <span>💡</span> Dudas (Día 3)
                                    </button>
                                    <button type="button" wire:click="loadWhatsAppTemplate('dia7_escasez')" class="px-2 py-1 rounded-lg text-[10px] font-bold bg-white dark:bg-slate-900 hover:bg-amber-50 dark:hover:bg-slate-800 text-amber-700 dark:text-amber-400 border border-slate-200 dark:border-slate-800 hover:border-amber-300 transition shadow-2xs cursor-pointer flex items-center gap-1">
                                        <span>⚡</span> Escasez (Día 7)
                                    </button>
                                    <button type="button" wire:click="loadWhatsAppTemplate('pregunta_no')" class="px-2 py-1 rounded-lg text-[10px] font-bold bg-white dark:bg-slate-900 hover:bg-rose-50 dark:hover:bg-slate-800 text-rose-700 dark:text-rose-400 border border-slate-200 dark:border-slate-800 hover:border-rose-300 transition shadow-2xs cursor-pointer flex items-center gap-1">
                                        <span>🎯</span> Técnica NO (Día 10)
                                    </button>
                                    <button type="button" wire:click="loadWhatsAppTemplate('psicologia_inversa')" class="px-2 py-1 rounded-lg text-[10px] font-bold bg-white dark:bg-slate-900 hover:bg-purple-50 dark:hover:bg-slate-800 text-purple-700 dark:text-purple-400 border border-slate-200 dark:border-slate-800 hover:border-purple-300 transition shadow-2xs cursor-pointer flex items-center gap-1">
                                        <span>🔒</span> Cierre de Expediente
                                    </button>
                                    <button type="button" wire:click="loadWhatsAppTemplate('presupuesto')" class="px-2 py-1 rounded-lg text-[10px] font-bold bg-white dark:bg-slate-900 hover:bg-teal-50 dark:hover:bg-slate-800 text-teal-700 dark:text-teal-400 border border-slate-200 dark:border-slate-800 hover:border-teal-300 transition shadow-2xs cursor-pointer flex items-center gap-1">
                                        <span>💰</span> Objeción Precio (Adaptar)
                                    </button>
                                    <button type="button" wire:click="loadWhatsAppTemplate('no_es_nuestro_cliente')" class="px-2 py-1 rounded-lg text-[10px] font-bold bg-white dark:bg-slate-900 hover:bg-amber-50 dark:hover:bg-slate-800 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700/70 hover:border-amber-400 transition shadow-2xs cursor-pointer flex items-center gap-1" title="Versión cordial: gracias por la sinceridad, cerramos la consulta y os deseamos suerte">
                                        <span>👑</span> No es nuestro cliente (Precio)
                                    </button>
                                    <button type="button" wire:click="loadWhatsAppTemplate('cuestionario')" class="px-2 py-1 rounded-lg text-[10px] font-bold bg-white dark:bg-slate-900 hover:bg-blue-50 dark:hover:bg-slate-800 text-blue-700 dark:text-blue-400 border border-slate-200 dark:border-slate-800 hover:border-blue-300 transition shadow-2xs cursor-pointer flex items-center gap-1">
                                        <span>📋</span> Cuestionario
                                    </button>
                                    <button type="button" wire:click="loadWhatsAppTemplate('contrato')" class="px-2 py-1 rounded-lg text-[10px] font-bold bg-white dark:bg-slate-900 hover:bg-emerald-50 dark:hover:bg-slate-800 text-emerald-700 dark:text-emerald-400 border border-slate-200 dark:border-slate-800 hover:border-emerald-300 transition shadow-2xs cursor-pointer flex items-center gap-1">
                                        <span>✍️</span> Enlace Contrato
                                    </button>
                                    <button type="button" wire:click="loadWhatsAppTemplate('confirmacion_senal')" class="px-2 py-1 rounded-lg text-[10px] font-bold bg-white dark:bg-slate-900 hover:bg-emerald-50 dark:hover:bg-slate-800 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/60 hover:border-emerald-300 transition shadow-2xs cursor-pointer flex items-center gap-1">
                                        <span>✅</span> Confirmación Señal
                                    </button>
                                    <button type="button" wire:click="loadWhatsAppTemplate('recordatorio_pago')" class="px-2 py-1 rounded-lg text-[10px] font-bold bg-white dark:bg-slate-900 hover:bg-yellow-50 dark:hover:bg-slate-800 text-yellow-700 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-800/60 hover:border-yellow-300 transition shadow-2xs cursor-pointer flex items-center gap-1">
                                        <span>⏳</span> Recordatorio Pago
                                    </button>
                                    <button type="button" wire:click="loadWhatsAppTemplate('pre_boda')" class="px-2 py-1 rounded-lg text-[10px] font-bold bg-white dark:bg-slate-900 hover:bg-pink-50 dark:hover:bg-slate-800 text-pink-700 dark:text-pink-400 border border-pink-200 dark:border-pink-800/60 hover:border-pink-300 transition shadow-2xs cursor-pointer flex items-center gap-1">
                                        <span>🎉</span> {{ $event->is_wedding ? 'Pre-boda (Logística)' : 'Pre-evento (Logística)' }}
                                    </button>
                                </div>
                            </div>

                            <!-- Caja de Redacción / Envío Directo por WhatsApp o por Email -->
                            <div class="space-y-2 pt-1">
                                <!-- Datos rápidos para envío por Email (Destinatario + Asunto) -->
                                <div class="p-2 bg-white dark:bg-slate-900 rounded-xl border border-indigo-200/80 dark:border-indigo-900/60 grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[10px] font-bold text-indigo-700 dark:text-indigo-300 shrink-0">📧 Para:</span>
                                        <input type="email"
                                               wire:model="email_chat_to"
                                               placeholder="Email del cliente (ej: cliente@gmail.com)"
                                               class="w-full text-[11px] px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-semibold focus:ring-indigo-500 focus:border-indigo-500" />
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[10px] font-bold text-indigo-700 dark:text-indigo-300 shrink-0">Asunto:</span>
                                        <input type="text"
                                               wire:model="email_chat_subject"
                                               placeholder="Asunto del correo..."
                                               class="w-full text-[11px] px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-semibold focus:ring-indigo-500 focus:border-indigo-500" />
                                    </div>
                                </div>

                                <textarea wire:model="whatsapp_chat_message" rows="3" class="w-full text-xs border border-slate-300 dark:border-slate-700 rounded-xl p-2.5 focus:ring-emerald-500 focus:border-emerald-500 bg-white dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500" placeholder="Escribe tu mensaje o seguimiento (o pulsa una plantilla arriba) y envíalo por WhatsApp o por Email..."></textarea>
                                
                                @if (session()->has('whatsapp_success'))
                                    <div class="text-[11px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/70 p-2 rounded-lg border border-emerald-200 dark:border-emerald-800 flex items-center justify-between">
                                        <span>{{ session('whatsapp_success') }}</span>
                                        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 font-bold">&times;</button>
                                    </div>
                                @endif

                                @if (session()->has('whatsapp_error'))
                                    <div class="text-[11px] font-bold text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-950/70 p-2 rounded-lg border border-rose-200 dark:border-rose-800 flex items-center justify-between">
                                        <span>⚠️ {{ session('whatsapp_error') }}</span>
                                        <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 font-bold">&times;</button>
                                    </div>
                                @endif

                                <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">
                                        @if(!$cleanPhone && $clientEmail)
                                            📧 Cliente sin móvil · Envía por Email y quedará registrado arriba
                                        @else
                                            {{ $gatewayActive ? '⚡ Envío directo por WhatsApp API o Email' : '💬 Envía por WhatsApp o Email con registro automático' }}
                                        @endif
                                    </span>
                                    <div class="flex flex-wrap items-center gap-2 ml-auto">
                                        <button type="button" wire:click="sendDirectEmailMessage" wire:loading.attr="disabled" class="bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white font-extrabold px-3.5 py-2 rounded-xl text-xs transition shadow-md cursor-pointer flex items-center gap-1.5">
                                            <span wire:loading.remove wire:target="sendDirectEmailMessage">📧 Enviar por Email</span>
                                            <span wire:loading wire:target="sendDirectEmailMessage">Enviando Email...</span>
                                        </button>
                                        <button type="button" wire:click="sendDirectWhatsAppMessage" wire:loading.attr="disabled" class="bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-extrabold px-3.5 py-2 rounded-xl text-xs transition shadow-md cursor-pointer flex items-center gap-1.5">
                                            <span wire:loading.remove wire:target="sendDirectWhatsAppMessage">🚀 Enviar WhatsApp</span>
                                            <span wire:loading wire:target="sendDirectWhatsAppMessage">Enviando...</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna 2: Dossier Técnico y Horarios de Montaje -->
                    <div class="bg-slate-50/80 dark:bg-slate-900/90 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-4 flex flex-col justify-between">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">📋</span>
                                    <div>
                                        <h4 class="font-bold text-slate-900 dark:text-white text-sm">Dossier Técnico y Horarios Staff</h4>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Planificación técnica visible para DJ y personal de cabina</p>
                                    </div>
                                </div>
                                <button wire:click="saveDossier" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-xs transition cursor-pointer">
                                    💾 Guardar Dossier
                                </button>
                            </div>

                            <div>
                                <textarea wire:model="dossier_content" rows="12" class="w-full text-xs font-mono bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl p-3 focus:ring-indigo-500 focus:border-indigo-500 text-slate-900 dark:text-white leading-relaxed placeholder-slate-400 dark:placeholder-slate-500" placeholder="Escribe aquí los horarios previstos, accesos a finca, ubicación de tomas de corriente, setup de sonido e iluminación, contactos de metres..."></textarea>
                            </div>

                            <div class="p-3 bg-white dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center">
                                <input type="checkbox" wire:model="is_dossier_completed" id="dossier_completed" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-slate-300 dark:border-slate-700 rounded">
                                <label for="dossier_completed" class="ml-2.5 block text-xs font-semibold text-slate-800 dark:text-slate-200 cursor-pointer">
                                    🔒 Cuestionario cerrado (Los novios ya han completado su dossier y momentos)
                                </label>
                            </div>
                        </div>

                        <div class="pt-2 text-right">
                            <button wire:click="saveDossier" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-bold text-xs shadow-md transition cursor-pointer">
                                Guardar Dossier y Planificación
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- MÚSICA Y MOMENTOS --}}
        @if($activeTab == 'music')
            <div wire:poll.3s="refreshMusicRequests">
                <!-- Cabecera de Peticiones y Escaleta -->
                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4 border-b pb-4">
                    <div>
                        <h4 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                            <span>🎤 Escaleta y Peticiones de Canciones</span>
                            <span class="text-xs bg-indigo-100 text-indigo-800 font-bold px-2.5 py-0.5 rounded-full">{{ $event->musicRequests->count() }} canciones</span>
                        </h4>
                        <p class="text-xs text-gray-500 mt-0.5">Gestiona las canciones para momentos especiales (Entrada, Regalos, Ramo, Tarta, Baile) con reproductor integrado.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button wire:click="openSongModal" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-3 py-2 rounded-lg shadow-sm inline-flex items-center gap-1.5 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Añadir Canción / Momento
                        </button>

                        <button 
                            type="button" 
                            wire:click="openCloudImportModal" 
                            class="bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold px-3 py-2 rounded-lg shadow-sm inline-flex items-center gap-1.5 transition cursor-pointer"
                            title="Importa en 1 clic todas las canciones de una carpeta compartida de Google Drive, OneDrive o Dropbox"
                        >
                            <span>📁</span>
                            Importar Carpeta Nube
                        </button>

                        <button 
                            type="button" 
                            wire:click="openImportModal" 
                            class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-3 py-2 rounded-lg shadow-sm inline-flex items-center gap-1.5 transition cursor-pointer"
                            title="Importa listas de canciones directamente desde un archivo PDF o pegando texto de WhatsApp / Word"
                        >
                            <span>📄</span>
                            Importar Lista (PDF / WhatsApp)
                        </button>

                        <button 
                            type="button" 
                            wire:click="openDjHistoryModal" 
                            class="bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold px-3 py-2 rounded-lg shadow-sm inline-flex items-center gap-1.5 transition cursor-pointer"
                            title="Importa el historial de la sesión DJ real desde tu controladora (Denon Engine DJ, Rekordbox, Serato, Traktor, M3U o CSV)"
                        >
                            <span>🎧</span>
                            Importar Sesión DJ (Engine / M3U)
                        </button>
                        
                        <a href="{{ route('admin.events.music_escaleta.pdf', $event->id) }}" target="_blank" class="bg-gray-800 hover:bg-gray-900 text-white text-xs font-bold px-3 py-2 rounded-lg shadow-sm inline-flex items-center gap-1.5 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Descargar Escaleta (PDF)
                        </a>

                        <div x-data="{ copiedDj: false }" class="inline-block">
                            <button type="button" @click="
                                let list = '';
                                @foreach($event->musicRequests as $req)
                                    list += '[{{ strtoupper($req->category) }} - {{ $req->moment }}] {{ $req->title }}{{ $req->artist ? ' - ' . $req->artist : '' }}{{ $req->notes ? ' (Nota: ' . $req->notes . ')' : '' }}\n';
                                @endforeach
                                
                                const ta = document.createElement('textarea');
                                ta.value = list;
                                ta.style.position = 'fixed';
                                ta.style.top = '0';
                                ta.style.left = '0';
                                ta.style.opacity = '0';
                                document.body.appendChild(ta);
                                ta.focus();
                                ta.select();
                                try {
                                    if (navigator.clipboard && window.isSecureContext) {
                                        navigator.clipboard.writeText(list);
                                    } else {
                                        document.execCommand('copy');
                                    }
                                    copiedDj = true;
                                    setTimeout(() => copiedDj = false, 2500);
                                } catch (e) {
                                    document.execCommand('copy');
                                    copiedDj = true;
                                    setTimeout(() => copiedDj = false, 2500);
                                }
                                document.body.removeChild(ta);
                            " class="bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-300 text-xs font-bold px-3 py-2 rounded-lg shadow-xs inline-flex items-center gap-1.5 transition">
                                <span x-show="!copiedDj">📋 Copiar Lista DJ</span>
                                <span x-show="copiedDj" class="text-emerald-600 font-bold" style="display: none;">✅ ¡Lista Copiada!</span>
                            </button>
                        </div>
                    </div>
                </div>

                @if (session()->has('venue_message'))
                    <div class="mb-4 bg-indigo-100 border-l-4 border-indigo-500 text-indigo-700 p-3 rounded-xl text-xs font-bold shadow-sm">
                        ✓ {{ session('venue_message') }}
                    </div>
                @endif

                @if (session()->has('music_message'))
                    <div class="mb-4 bg-emerald-100 border-l-4 border-emerald-500 text-emerald-800 p-3 rounded-xl text-xs font-bold shadow-sm">
                        ✓ {{ session('music_message') }}
                    </div>
                @endif

                @if (session()->has('music_error'))
                    <div class="mb-4 bg-rose-100 border-l-4 border-rose-500 text-rose-800 p-3 rounded-xl text-xs font-bold shadow-sm">
                        ⚠️ {{ session('music_error') }}
                    </div>
                @endif

                <!-- ========================================================= -->
                <!-- BARRA DE REPRODUCTOR CONTINUO & PLAYLIST DEL EVENTO      -->
                <!-- ========================================================= -->
                @php
                    $playlistJson = $event->musicRequests()->where('status', '!=', 'rejected')->get()->map(function($req) {
                        return [
                            'id' => $req->id,
                            'title' => $req->title,
                            'artist' => $req->artist ?: '',
                            'moment' => $req->moment ?: ucfirst($req->category),
                            'category' => $req->category,
                            'audio_src' => $req->audio_url ?: '',
                            'youtube_url' => $req->youtube_url ?: '',
                            'spotify_url' => $req->spotify_url ?: '',
                            'cue_time' => $req->cue_time ?: '',
                        ];
                    })->values()->toJson();
                @endphp

                <div 
                    class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 border border-indigo-500/30 rounded-2xl p-4 sm:p-5 text-white shadow-xl mb-6 relative overflow-hidden"
                    x-data="{
                        tracks: {{ $playlistJson }},
                        currentIndex: 0,
                        isPlaying: false,
                        currentTime: 0,
                        duration: 0,
                        audio: null,
                        continuousMode: true,
                        cachedSongIds: [],
                        cacheStorageUsage: { count: 0, bytes: 0, mb: '0.0' },
                        isDownloadingOffline: false,
                        offlineDownloadPercent: 0,
                        offlineStatusText: '',
                        showOfflineModal: false,
                        wakeLock: null,

                        async requestWakeLock() {
                            if ('wakeLock' in navigator) {
                                try {
                                    if (!this.wakeLock) {
                                        this.wakeLock = await navigator.wakeLock.request('screen');
                                        this.wakeLock.addEventListener('release', () => {
                                            this.wakeLock = null;
                                        });
                                    }
                                } catch (err) {
                                    console.warn('Wake Lock request:', err);
                                }
                            }
                        },

                        async releaseWakeLock() {
                            if (this.wakeLock) {
                                try {
                                    await this.wakeLock.release();
                                } catch(e) {}
                                this.wakeLock = null;
                            }
                        },

                        async refreshOfflineStatus() {
                            if (window.OfflineAudioCache) {
                                try {
                                    this.cachedSongIds = await window.OfflineAudioCache.getCachedSongIdsForEvent('{{ $event->id }}');
                                    this.cacheStorageUsage = await window.OfflineAudioCache.getEventStorageUsage('{{ $event->id }}');
                                } catch(e) {}
                            }
                        },

                        async init() {
                            this.audio = new Audio();
                            this.audio.addEventListener('timeupdate', () => {
                                this.currentTime = this.audio.currentTime;
                                this.duration = this.audio.duration || 0;
                            });
                            this.audio.addEventListener('ended', () => {
                                if (this.continuousMode && this.currentIndex < this.tracks.length - 1) {
                                    this.nextTrack();
                                } else {
                                    this.isPlaying = false;
                                    this.releaseWakeLock();
                                }
                            });
                            this.audio.addEventListener('play', () => {
                                this.isPlaying = true;
                                this.requestWakeLock();
                            });
                            this.audio.addEventListener('pause', () => {
                                this.isPlaying = false;
                                this.releaseWakeLock();
                            });

                            document.addEventListener('visibilitychange', async () => {
                                if (document.visibilityState === 'visible' && this.isPlaying) {
                                    await this.requestWakeLock();
                                }
                            });

                            await this.refreshOfflineStatus();
                        },

                        async playTrack(index) {
                            if (index < 0 || index >= this.tracks.length) return;
                            this.currentIndex = index;
                            const track = this.tracks[index];

                            // Prioridad 0: Reproducir desde memoria Offline / Blob sin conexión
                            if (window.OfflineAudioCache) {
                                try {
                                    const cachedBlobUrl = await window.OfflineAudioCache.getCachedAudioUrl(track.id);
                                    if (cachedBlobUrl) {
                                        this.audio.src = cachedBlobUrl;
                                        this.audio.load();
                                        await this.audio.play();
                                        this.isPlaying = true;
                                        this.requestWakeLock();
                                        return;
                                    }
                                } catch(e) {}
                            }

                            if (track.audio_src) {
                                let streamUrl = track.audio_src.trim();
                                if (streamUrl.includes('drive.google.com') || streamUrl.includes('/api/drive-stream/')) {
                                    const driveMatch = streamUrl.match(/\/d\/([a-zA-Z0-9_-]+)/) || streamUrl.match(/[?&]id=([a-zA-Z0-9_-]+)/) || streamUrl.match(/\/api\/drive-stream\/([a-zA-Z0-9_-]+)/);
                                    if (driveMatch && driveMatch[1]) {
                                        streamUrl = `/api/drive-stream/${driveMatch[1]}`;
                                    }
                                } else if (streamUrl.includes('dropbox.com')) {
                                    streamUrl = streamUrl.replace('dl=0', 'raw=1');
                                } else if (!streamUrl.startsWith('http')) {
                                    streamUrl = '/storage/' + streamUrl;
                                }

                                this.audio.src = streamUrl;
                                this.audio.load();
                                await this.audio.play();
                                this.isPlaying = true;
                                this.requestWakeLock();
                            } else if (track.youtube_url) {
                                window.open(track.youtube_url, '_blank');
                            } else if (track.spotify_url) {
                                window.open(track.spotify_url, '_blank');
                            }
                        },

                        togglePlay() {
                            if (!this.audio.src && this.tracks.length > 0) {
                                this.playTrack(0);
                                return;
                            }
                            if (this.isPlaying) {
                                this.audio.pause();
                                this.isPlaying = false;
                                this.releaseWakeLock();
                            } else {
                                this.audio.play();
                                this.isPlaying = true;
                                this.requestWakeLock();
                            }
                        },

                        nextTrack() {
                            if (this.currentIndex < this.tracks.length - 1) {
                                this.playTrack(this.currentIndex + 1);
                            }
                        },

                        prevTrack() {
                            if (this.currentIndex > 0) {
                                this.playTrack(this.currentIndex - 1);
                            }
                        },

                        async downloadAllForOffline() {
                            if (!window.OfflineAudioCache) return;
                            this.isDownloadingOffline = true;
                            this.offlineDownloadPercent = 0;
                            this.offlineStatusText = 'Descargando canciones para modo sin cobertura...';

                            try {
                                const songList = this.tracks.map(t => ({
                                    id: String(t.id),
                                    event_id: '{{ $event->id }}',
                                    title: t.title,
                                    artist: t.artist,
                                    moment: t.moment,
                                    category: t.category,
                                    audio_file: t.audio_src
                                }));

                                await window.OfflineAudioCache.cacheAllEventSongs(
                                    songList,
                                    '{{ $event->id }}',
                                    (progress) => {
                                        this.offlineDownloadPercent = progress.percent;
                                        if (progress.currentSong) {
                                            this.offlineStatusText = `Descargando (${progress.currentIndex}/${progress.totalSongs}): ${progress.currentSong.title}`;
                                        } else if (progress.status === 'done') {
                                            this.offlineStatusText = `¡Listo! ${progress.completed}/${progress.total} canciones guardadas para reproducir sin conexión.`;
                                        }
                                    }
                                );
                                await this.refreshOfflineStatus();
                                this.isDownloadingOffline = false;
                            } catch(err) {
                                this.isDownloadingOffline = false;
                                this.offlineStatusText = 'Error: ' + err.message;
                            }
                        },

                        async clearEventCache() {
                            if (!window.OfflineAudioCache) return;
                            if (!confirm('¿Deseas borrar las canciones descargadas de este evento en este dispositivo?')) return;
                            try {
                                await window.OfflineAudioCache.deleteEventCache('{{ $event->id }}');
                                await this.refreshOfflineStatus();
                                this.showOfflineModal = false;
                            } catch(e) {}
                        },

                        formatTime(sec) {
                            if (!sec || isNaN(sec)) return '0:00';
                            const m = Math.floor(sec / 60);
                            const s = Math.floor(sec % 60);
                            return m + ':' + (s < 10 ? '0' : '') + s;
                        }
                    }"
                >
                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                        
                        <!-- Track Info actual -->
                        <div class="flex items-center gap-3.5 min-w-0">
                            <button 
                                type="button" 
                                @click="togglePlay()"
                                class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-500 to-purple-600 hover:from-indigo-400 hover:to-purple-500 text-white flex items-center justify-center text-xl shadow-lg shadow-indigo-500/30 shrink-0 transition transform hover:scale-105 cursor-pointer"
                            >
                                <span x-show="!isPlaying">▶️</span>
                                <span x-show="isPlaying" style="display: none;">⏸️</span>
                            </button>

                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs uppercase tracking-wider font-extrabold text-indigo-400 flex items-center gap-1">
                                        <span>🎶</span> Reproductor Playlist en la App
                                    </span>
                                    <span class="text-[10px] bg-indigo-500/20 text-indigo-300 px-2 py-0.5 rounded-full font-bold">
                                        Modo Continuo Activo
                                    </span>
                                </div>
                                <h5 class="text-sm font-bold text-white truncate mt-0.5" x-text="tracks[currentIndex] ? tracks[currentIndex].title + (tracks[currentIndex].artist ? ' - ' + tracks[currentIndex].artist : '') : 'Playlist lista para reproducir'">
                                    Cargando canciones...
                                </h5>
                                <p class="text-[11px] text-slate-400 flex items-center gap-2">
                                    <span x-text="tracks[currentIndex] ? '📌 Momento: ' + tracks[currentIndex].moment : '{{ $event->musicRequests->count() }} canciones en cola'"></span>
                                    <template x-if="tracks[currentIndex] && tracks[currentIndex].cue_time">
                                        <span class="text-amber-400 font-bold" x-text="'⚡ CUE: ' + tracks[currentIndex].cue_time"></span>
                                    </template>
                                </p>
                            </div>
                        </div>

                        <!-- Botones de Control y Exportación -->
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Controles Anterior / Siguiente -->
                            <div class="flex items-center gap-1 bg-slate-900/80 p-1 rounded-xl border border-slate-700/60">
                                <button type="button" @click="prevTrack()" class="px-2.5 py-1.5 text-xs text-slate-300 hover:text-white rounded-lg hover:bg-slate-800 transition font-bold" title="Canción anterior">⏮️</button>
                                <button type="button" @click="togglePlay()" class="px-3 py-1.5 text-xs text-white rounded-lg bg-indigo-600 hover:bg-indigo-500 transition font-bold">
                                    <span x-show="!isPlaying">Play</span>
                                    <span x-show="isPlaying" style="display: none;">Pausa</span>
                                </button>
                                <button type="button" @click="nextTrack()" class="px-2.5 py-1.5 text-xs text-slate-300 hover:text-white rounded-lg hover:bg-slate-800 transition font-bold" title="Siguiente canción">⏭️</button>
                            </div>

                            <!-- Botón Gestor Modo Offline -->
                            <button 
                                type="button" 
                                @click="showOfflineModal = true"
                                class="px-3 py-1.5 rounded-xl text-xs font-black shadow-md inline-flex items-center gap-1.5 transition cursor-pointer border"
                                :class="cachedSongIds.length > 0 ? 'bg-emerald-950/90 text-emerald-300 border-emerald-500/60 hover:bg-emerald-900 shadow-emerald-950/50' : 'bg-slate-900 text-amber-300 border-amber-500/50 hover:bg-slate-800'"
                                title="Descargar canciones para reproducir sin conexión / sin cobertura"
                            >
                                <span x-show="cachedSongIds.length > 0" class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span x-show="cachedSongIds.length === 0">📥</span>
                                <span x-show="cachedSongIds.length > 0">
                                    Modo Offline (<span x-text="cachedSongIds.length"></span> desc. &bull; <span x-text="cacheStorageUsage.mb"></span> MB)
                                </span>
                                <span x-show="cachedSongIds.length === 0">
                                    Descargar para Offline
                                </span>
                            </button>

                            <!-- Botón Auto-completar todos los enlaces del evento -->
                            <button 
                                type="button" 
                                wire:click="autoResolveAllMusicLinks" 
                                wire:loading.attr="disabled"
                                class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-extrabold shadow-md shadow-indigo-600/20 inline-flex items-center gap-1.5 transition cursor-pointer"
                                title="Detecta y completa automáticamente los enlaces de Spotify, Apple Music y YouTube de todas las canciones del evento"
                            >
                                <span>🪄</span>
                                <span wire:loading.remove wire:target="autoResolveAllMusicLinks">Auto-detectar Enlaces</span>
                                <span wire:loading wire:target="autoResolveAllMusicLinks">Detectando...</span>
                            </button>

                            <!-- Botón Exportar Spotify -->
                            <button 
                                type="button" 
                                wire:click="createSpotifyPlaylist" 
                                wire:loading.attr="disabled"
                                class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-extrabold shadow-md shadow-emerald-600/20 inline-flex items-center gap-1.5 transition cursor-pointer"
                                title="Crea y añade todas las canciones de este evento en una playlist en tu Spotify"
                            >
                                <span>🟢</span>
                                <span wire:loading.remove wire:target="createSpotifyPlaylist">Exportar a Spotify</span>
                                <span wire:loading wire:target="createSpotifyPlaylist">Generando...</span>
                            </button>

                            <!-- Botón Descargar M3U para Rekordbox / Serato / VirtualDJ -->
                            <button 
                                type="button" 
                                wire:click="exportPlaylistM3u" 
                                class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white text-xs font-bold border border-slate-600/80 shadow-md inline-flex items-center gap-1.5 transition cursor-pointer"
                                title="Descargar archivo .M3U para importar en Rekordbox, Serato, Traktor o VirtualDJ"
                            >
                                <span>💿</span>
                                <span>Descargar .M3U (DJ)</span>
                            </button>
                        </div>
                    </div>

                    <!-- Barra de progreso para pistas con audio local -->
                    <template x-if="audio && audio.src">
                        <div class="mt-3 pt-3 border-t border-slate-800/80 flex items-center gap-3 text-xs text-slate-400">
                            <span x-text="formatTime(currentTime)">0:00</span>
                            <div class="flex-1 bg-slate-800 h-1.5 rounded-full overflow-hidden cursor-pointer" @click="
                                const rect = $el.getBoundingClientRect();
                                const pos = ($event.clientX - rect.left) / rect.width;
                                if (audio && duration) audio.currentTime = pos * duration;
                            ">
                                <div class="bg-indigo-500 h-full transition-all" :style="'width: ' + ((currentTime / (duration || 1)) * 100) + '%'"></div>
                            </div>
                            <span x-text="formatTime(duration)">0:00</span>
                        </div>
                    </template>

                    <!-- MODAL OFFLINE EN EVENT-SHOW -->
                    <div 
                        x-show="showOfflineModal" 
                        x-cloak
                        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
                        @keydown.escape.window="showOfflineModal = false"
                    >
                        <div 
                            class="bg-slate-900 border border-slate-700 rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-5 text-slate-100"
                            @click.away="showOfflineModal = false"
                        >
                            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="text-2xl">📥</span>
                                    <div>
                                        <h3 class="text-base font-black text-white">Modo Offline (Sin Cobertura)</h3>
                                        <p class="text-xs text-slate-400">Descarga los MP3 a tu navegador para sonar sin conexión a internet.</p>
                                    </div>
                                </div>
                                <button type="button" @click="showOfflineModal = false" class="text-slate-400 hover:text-white text-xl font-bold p-1">&times;</button>
                            </div>

                            <div class="bg-slate-950/80 border border-slate-800 rounded-2xl p-4 flex items-center justify-between">
                                <div>
                                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Canciones descargadas</div>
                                    <div class="text-xl font-black text-emerald-400 mt-0.5">
                                        <span x-text="cachedSongIds.length"></span> / <span x-text="tracks.length"></span> canciones
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Espacio Ocupado</div>
                                    <div class="text-xl font-black text-cyan-400 mt-0.5">
                                        <span x-text="cacheStorageUsage.mb"></span> MB
                                    </div>
                                </div>
                            </div>

                            <div x-show="isDownloadingOffline" class="space-y-2 bg-slate-950/60 p-3.5 rounded-2xl border border-cyan-900/50">
                                <div class="flex items-center justify-between text-xs font-bold text-cyan-300">
                                    <span class="truncate pr-2" x-text="offlineStatusText"></span>
                                    <span class="font-mono text-cyan-400" x-text="offlineDownloadPercent + '%'"></span>
                                </div>
                                <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                                    <div class="bg-gradient-to-r from-cyan-500 to-emerald-400 h-2 rounded-full transition-all duration-300" :style="`width: ${offlineDownloadPercent}%`"></div>
                                </div>
                            </div>

                            <div x-show="!isDownloadingOffline && offlineStatusText" class="text-xs font-bold text-slate-300 bg-slate-950/40 p-2.5 rounded-xl border border-slate-800/80">
                                <span x-text="offlineStatusText"></span>
                            </div>

                            <div class="flex flex-col sm:flex-row items-center gap-2.5 pt-2">
                                <button 
                                    type="button" 
                                    @click="downloadAllForOffline()" 
                                    :disabled="isDownloadingOffline || tracks.length === 0"
                                    class="w-full sm:flex-1 py-3 px-4 rounded-xl bg-gradient-to-r from-emerald-600 to-cyan-600 hover:from-emerald-500 hover:to-cyan-500 disabled:opacity-50 text-white font-black text-xs shadow-lg transition flex items-center justify-center gap-2 cursor-pointer"
                                >
                                    <span x-show="!isDownloadingOffline">📥 Descargar todas las canciones</span>
                                    <span x-show="isDownloadingOffline" class="inline-flex items-center gap-1.5">
                                        <span class="animate-spin text-sm">⏳</span> Descargando...
                                    </span>
                                </button>

                                <button 
                                    type="button" 
                                    x-show="cachedSongIds.length > 0"
                                    @click="clearEventCache()" 
                                    class="w-full sm:w-auto py-3 px-4 rounded-xl bg-slate-800 hover:bg-rose-950 text-slate-300 hover:text-rose-300 border border-slate-700 hover:border-rose-800 text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer"
                                    title="Eliminar archivos locales de este evento"
                                >
                                    <span>🗑️</span> Liberar espacio
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Píldoras de Filtro por Categorías / Fases -->
                <div class="flex flex-wrap items-center gap-2 mb-6">
                    <button wire:click="$set('music_active_category', 'all')" class="px-3.5 py-1.5 rounded-full text-xs font-bold transition {{ $music_active_category === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        🟣 Todas ({{ $event->musicRequests->count() }})
                    </button>
                    <button wire:click="$set('music_active_category', 'ceremonia')" class="px-3.5 py-1.5 rounded-full text-xs font-bold transition {{ $music_active_category === 'ceremonia' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-indigo-50 text-indigo-800 hover:bg-indigo-100' }}">
                        💍 Ceremonia ({{ $event->musicRequests->where('category', 'ceremonia')->count() }})
                    </button>
                    <button wire:click="$set('music_active_category', 'coctel')" class="px-3.5 py-1.5 rounded-full text-xs font-bold transition {{ $music_active_category === 'coctel' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100' }}">
                        🍸 Cóctel ({{ $event->musicRequests->where('category', 'coctel')->count() }})
                    </button>
                    <button wire:click="$set('music_active_category', 'banquete')" class="px-3.5 py-1.5 rounded-full text-xs font-bold transition {{ $music_active_category === 'banquete' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-amber-50 text-amber-800 hover:bg-amber-100' }}">
                        🍽️ Banquete / Regalos ({{ $event->musicRequests->where('category', 'banquete')->count() }})
                    </button>
                    <button wire:click="$set('music_active_category', 'baile')" class="px-3.5 py-1.5 rounded-full text-xs font-bold transition {{ $music_active_category === 'baile' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-purple-50 text-purple-800 hover:bg-purple-100' }}">
                        💃 Baile / Fiesta ({{ $event->musicRequests->where('category', 'baile')->count() }})
                    </button>
                    <button wire:click="$set('music_active_category', 'lista_negra')" class="px-3.5 py-1.5 rounded-full text-xs font-bold transition {{ $music_active_category === 'lista_negra' ? 'bg-rose-600 text-white shadow-sm' : 'bg-rose-50 text-rose-800 hover:bg-rose-100' }}">
                        🚫 Lista Negra ({{ $event->musicRequests->where('category', 'lista_negra')->count() }})
                    </button>
                </div>

                <!-- Lista de Canciones y Momentos -->
                @if($filteredMusicRequests->count() > 0)
                    <div class="space-y-4">
                        @foreach($filteredMusicRequests as $song)
                            <div class="bg-gray-900 text-white rounded-xl p-4 sm:p-5 shadow-md border border-gray-800 transition hover:border-gray-700">
                                
                                <!-- Fila Superior: Título, Artista, Momento y Acciones -->
                                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 mb-3">
                                    <div class="flex items-start gap-3">
                                        <!-- Botones de Orden -->
                                        <div class="flex flex-col gap-0.5 text-gray-500 pt-0.5">
                                            <button wire:click="moveSongOrder({{ $song->id }}, 'up')" class="hover:text-white" title="Subir orden">▲</button>
                                            <button wire:click="moveSongOrder({{ $song->id }}, 'down')" class="hover:text-white" title="Bajar orden">▼</button>
                                        </div>

                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h5 class="text-base sm:text-lg font-bold text-white">{{ $song->title }}</h5>
                                                @if($song->artist)
                                                    <span class="text-sm text-gray-400">— {{ $song->artist }}</span>
                                                @endif
                                            </div>

                                            <div class="flex flex-wrap items-center gap-2 mt-1.5">
                                                <!-- Badge Momento -->
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-amber-400/20 text-amber-300 border border-amber-400/30">
                                                    🎯 {{ $song->moment }}
                                                </span>

                                                <!-- Badge Categoría -->
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-800 text-gray-300">
                                                    📁 {{ ucfirst($song->category) }}
                                                </span>

                                                <!-- CUE Time -->
                                                @if($song->cue_time)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                                        ⏱️ CUE: {{ $song->cue_time }}
                                                    </span>
                                                @endif

                                                <!-- Solicitado por -->
                                                @if($song->requested_by)
                                                    <span class="inline-flex items-center text-xs text-emerald-400 font-medium">
                                                        👤 {{ $song->requested_by }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Estado y Botones de Edición -->
                                    <div class="flex items-center gap-2 self-end md:self-auto">
                                        <!-- Toggle Status -->
                                        <button wire:click="toggleSongStatus({{ $song->id }})" class="px-2.5 py-1 rounded-md text-xs font-bold transition {{ $song->status === 'played' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : ($song->status === 'ready' ? 'bg-blue-500/20 text-blue-300 border border-blue-500/30' : 'bg-gray-800 text-gray-400 border border-gray-700 hover:text-white') }}" title="Clic para cambiar estado (Pendiente / Listo / Sonada)">
                                            @if($song->status === 'played')
                                                ✅ Ya Sonó
                                            @elseif($song->status === 'ready')
                                                🟢 Lista
                                            @else
                                                ⏳ Pendiente
                                            @endif
                                        </button>

                                        <!-- Editar -->
                                        <button wire:click="openSongModal({{ $song->id }})" class="p-1.5 text-gray-400 hover:text-indigo-400 transition" title="Editar Canción">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </button>

                                        <!-- Eliminar -->
                                        <button wire:click="deleteSongRequest({{ $song->id }})" wire:confirm="¿Eliminar esta canción de la escaleta?" class="p-1.5 text-gray-400 hover:text-rose-400 transition" title="Eliminar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Notas / Instrucciones -->
                                @if($song->notes)
                                    <div class="bg-gray-800/80 border border-gray-700 rounded-lg px-3 py-2 text-xs text-gray-300 mb-3">
                                        <span class="font-bold text-amber-300">📝 Instrucciones / Nota:</span> {{ $song->notes }}
                                    </div>
                                @endif

                                <!-- Reproductor y Enlaces Multimedia -->
                                <div class="bg-gray-950/60 rounded-lg p-3 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border border-gray-800">
                                    <div class="flex-1 w-full sm:w-auto">
                                        @if($song->audio_url)
                                            <div class="flex items-center gap-2">
                                                <audio controls class="w-full h-8 rounded" style="filter: invert(0.85);" preload="metadata">
                                                    <source src="{{ $song->audio_url }}" type="audio/mpeg">
                                                    <source src="{{ $song->audio_url }}" type="audio/mp4">
                                                    <source src="{{ $song->audio_url }}" type="audio/aac">
                                                    Tu navegador no soporta el reproductor de audio.
                                                </audio>
                                            </div>
                                        @else
                                            <div class="flex items-center justify-between gap-2 text-xs text-slate-400 italic">
                                                <span>Sin audio local directo</span>
                                                <button 
                                                    type="button" 
                                                    wire:click="autoResolveSongAudio({{ $song->id }})" 
                                                    class="not-italic text-[11px] font-bold text-indigo-400 hover:text-indigo-300 underline cursor-pointer"
                                                    title="Detectar y vincular audio MP3 automáticamente"
                                                >
                                                    🪄 Detectar Audio
                                                </button>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Botones de Enlace Rápido -->
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if($song->youtube_url)
                                            <a href="{{ $song->youtube_url }}" target="_blank" class="bg-red-600/20 hover:bg-red-600/30 text-red-400 border border-red-500/30 text-xs font-semibold px-2.5 py-1.5 rounded-md inline-flex items-center gap-1.5 transition">
                                                <span>▶️ YouTube</span>
                                            </a>
                                        @else
                                            <a href="https://www.youtube.com/results?search_query={{ urlencode($song->title . ' ' . $song->artist) }}" target="_blank" class="bg-gray-800 hover:bg-gray-700 text-gray-300 text-xs px-2.5 py-1.5 rounded-md inline-flex items-center gap-1.5 transition" title="Buscar automáticamente en YouTube">
                                                <span>🔍 YouTube</span>
                                            </a>
                                        @endif

                                        @if($song->spotify_url)
                                            <a href="{{ $song->spotify_url }}" target="_blank" class="bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 border border-emerald-500/30 text-xs font-semibold px-2.5 py-1.5 rounded-md inline-flex items-center gap-1.5 transition">
                                                <span>🟢 Spotify</span>
                                            </a>
                                        @else
                                            <a href="https://open.spotify.com/search/{{ urlencode($song->title . ' ' . $song->artist) }}" target="_blank" class="bg-gray-800 hover:bg-gray-700 text-gray-300 text-xs px-2.5 py-1.5 rounded-md inline-flex items-center gap-1.5 transition" title="Buscar automáticamente en Spotify">
                                                <span>🔍 Spotify</span>
                                            </a>
                                        @endif

                                        @if($song->apple_music_url)
                                            <a href="{{ $song->apple_music_url }}" target="_blank" class="bg-pink-600/20 hover:bg-pink-600/30 text-pink-400 border border-pink-500/30 text-xs font-semibold px-2.5 py-1.5 rounded-md inline-flex items-center gap-1.5 transition">
                                                <span>🍎 Apple Music</span>
                                            </a>
                                        @else
                                            <a href="https://music.apple.com/search?term={{ urlencode($song->title . ' ' . $song->artist) }}" target="_blank" class="bg-pink-950/40 hover:bg-pink-900/40 text-pink-300 border border-pink-800/40 text-xs px-2.5 py-1.5 rounded-md inline-flex items-center gap-1.5 transition" title="Buscar automáticamente en Apple Music">
                                                <span>🍎 Apple Music</span>
                                            </a>
                                        @endif

                                        @if($song->tidal_url)
                                            <a href="{{ $song->tidal_url }}" target="_blank" class="bg-cyan-600/20 hover:bg-cyan-600/30 text-cyan-400 border border-cyan-500/30 text-xs font-semibold px-2.5 py-1.5 rounded-md inline-flex items-center gap-1.5 transition" title="Abrir en Tidal (Hi-Fi / Denon DJ)">
                                                <span>🌊 Tidal</span>
                                            </a>
                                        @else
                                            <a href="https://listen.tidal.com/search?q={{ urlencode($song->title . ' ' . $song->artist) }}" target="_blank" class="bg-cyan-950/40 hover:bg-cyan-900/40 text-cyan-300 border border-cyan-800/40 text-xs px-2.5 py-1.5 rounded-md inline-flex items-center gap-1.5 transition" title="Buscar automáticamente en Tidal">
                                                <span>🌊 Tidal</span>
                                            </a>
                                        @endif

                                        @if($song->audio_file)
                                            <a href="{{ route('admin.music.download', $song->id) }}" target="_blank" class="bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 text-xs font-semibold px-2.5 py-1.5 rounded-md inline-flex items-center gap-1 transition" title="Descargar MP3 para DJ">
                                                <span>⬇️ MP3</span>
                                            </a>
                                        @endif
                                    </div>
                                </div>

                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- ============================================== -->
                <!-- SECCIÓN: HISTORIAL DE SESIÓN DJ REAL (TRACKLIST) -->
                <!-- ============================================== -->
                <div class="mt-10 pt-8 border-t-2 border-indigo-100 dark:border-slate-800 space-y-4">
                    
                    <!-- Encabezado de la Sesión DJ -->
                    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-5 text-white shadow-md border border-indigo-900/60 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-purple-500/30 text-purple-200 border border-purple-400/30">
                                    🎧 Sesión DJ Real & Directo
                                </span>
                                @if($event->djHistories->count() > 0)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                        {{ $event->djHistories->count() }} Canciones Registradas
                                    </span>
                                @endif
                            </div>
                            <h3 class="text-lg font-black text-white flex items-center gap-2">
                                Tracklist Oficial de la Sesión
                            </h3>
                            <p class="text-xs text-slate-300 max-w-2xl">
                                Historial cronológico de la música que sonó en directo durante el evento, sincronizado desde tu controladora (<strong>Denon Engine DJ, Pioneer Rekordbox, Serato, Traktor o M3U</strong>).
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 w-full md:w-auto justify-start md:justify-end">
                            <button 
                                type="button" 
                                wire:click="openDjHistoryModal" 
                                class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs px-3.5 py-2.5 rounded-xl shadow-md transition cursor-pointer flex items-center gap-1.5"
                                title="Importar nuevo historial de sesión desde archivo CSV, M3U o texto"
                            >
                                <span>📥</span>
                                {{ $event->djHistories->count() > 0 ? 'Añadir / Reimportar' : 'Importar Historial DJ' }}
                            </button>

                            @if($event->djHistories->count() > 0)
                                <a 
                                    href="{{ route('pdf.dj-history', $event->id) }}" 
                                    target="_blank" 
                                    class="bg-white/10 hover:bg-white/20 text-white font-bold text-xs px-3.5 py-2.5 rounded-xl border border-white/20 shadow-sm transition inline-flex items-center gap-1.5"
                                    title="Descargar PDF con el Tracklist Oficial para enviar a los novios o guardar de recuerdo"
                                >
                                    <span>📄</span>
                                    PDF Tracklist
                                </a>

                                <button 
                                    type="button" 
                                    wire:click="deleteDjHistory" 
                                    wire:confirm="⚠️ ¿Estás seguro de que deseas eliminar todas las canciones del historial de sesión de este evento?"
                                    class="bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 border border-rose-500/30 font-bold text-xs px-3 py-2.5 rounded-xl transition cursor-pointer"
                                    title="Borrar todo el historial de la sesión"
                                >
                                    <span>🗑️</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    @if($event->djHistories->count() > 0)
                        <!-- Resumen y Filtros de Búsqueda -->
                        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <span class="text-slate-500 dark:text-slate-400 font-medium">Software:</span>
                                <span class="bg-purple-50 dark:bg-purple-950/50 text-purple-700 dark:text-purple-300 font-bold px-2.5 py-0.5 rounded-lg border border-purple-200 dark:border-purple-800">
                                    {{ $event->djHistories->first()->source_icon ?? 'Controladora DJ' }}
                                </span>

                                @php
                                    $matchedCount = $event->djHistories->whereNotNull('matched_request_id')->count();
                                @endphp
                                @if($matchedCount > 0)
                                    <span class="bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 font-bold px-2.5 py-0.5 rounded-lg border border-emerald-200 dark:border-emerald-800">
                                        💍 {{ $matchedCount }} peticiones sonadas en directo
                                    </span>
                                @endif
                            </div>

                            <!-- Buscador en tiempo real del Tracklist -->
                            <div class="w-full sm:w-64">
                                <input 
                                    type="text" 
                                    wire:model.live.debounce.250ms="djHistoryFilterQuery" 
                                    placeholder="🔍 Filtrar por título, artista o hora..." 
                                    class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-1.5 px-3 focus:ring-2 focus:ring-purple-500"
                                >
                            </div>
                        </div>

                        <!-- TABLA DE CANCIONES DE LA SESIÓN -->
                        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                            <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead class="sticky top-0 bg-slate-100 dark:bg-slate-950 text-slate-700 dark:text-slate-300 uppercase text-[10px] font-black border-b border-slate-200 dark:border-slate-800 z-10">
                                        <tr>
                                            <th class="py-2.5 px-3 text-center w-10">#</th>
                                            <th class="py-2.5 px-3 w-20">Hora</th>
                                            <th class="py-2.5 px-3">Canción / Título</th>
                                            <th class="py-2.5 px-3">Artista</th>
                                            <th class="py-2.5 px-3 text-center w-16">BPM</th>
                                            <th class="py-2.5 px-3 text-center w-16">Tono</th>
                                            <th class="py-2.5 px-3">Cruce Peticiones</th>
                                            <th class="py-2.5 px-3 text-right w-12">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-800 dark:text-slate-200">
                                        @forelse($allDjHistories as $track)
                                            <tr class="hover:bg-purple-50/40 dark:hover:bg-purple-950/20 transition group">
                                                <td class="py-2 px-3 text-center font-bold text-slate-400 group-hover:text-purple-600">
                                                    {{ $track->order }}
                                                </td>
                                                <td class="py-2 px-3 font-mono text-[11px] text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                                    @if($track->played_at_time)
                                                        <span class="bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-bold px-1.5 py-0.5 rounded">
                                                            ⏰ {{ $track->played_at_time }}
                                                        </span>
                                                    @else
                                                        <span class="text-slate-300 dark:text-slate-700">-</span>
                                                    @endif
                                                </td>
                                                <td class="py-2 px-3 font-bold text-slate-900 dark:text-white">
                                                    {{ $track->title }}
                                                </td>
                                                <td class="py-2 px-3 text-slate-600 dark:text-slate-300">
                                                    {{ $track->artist ?: '-' }}
                                                </td>
                                                <td class="py-2 px-3 text-center font-mono text-slate-600 dark:text-slate-400">
                                                    @if($track->bpm)
                                                        <span class="bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded font-bold">
                                                            {{ number_format($track->bpm, 0) }}
                                                        </span>
                                                    @else
                                                        <span class="text-slate-300 dark:text-slate-700">-</span>
                                                    @endif
                                                </td>
                                                <td class="py-2 px-3 text-center font-mono font-bold text-purple-700 dark:text-purple-400">
                                                    {{ $track->key ?: '-' }}
                                                </td>
                                                <td class="py-2 px-3">
                                                    @if($track->matchedRequest)
                                                        <span class="inline-flex items-center gap-1 bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 font-extrabold text-[10px] px-2 py-0.5 rounded-full border border-emerald-300 dark:border-emerald-800">
                                                            @if($track->matchedRequest->is_guest_request)
                                                                👥 {{ $track->matchedRequest->guest_name ?: 'Petición Invitado' }}
                                                            @else
                                                                💍 Petición Novios ({{ $track->matchedRequest->moment }})
                                                            @endif
                                                        </span>
                                                    @else
                                                        <span class="text-slate-400 text-[10px]">Sesión DJ</span>
                                                    @endif
                                                </td>
                                                <td class="py-2 px-3 text-right">
                                                    <button 
                                                        type="button" 
                                                        wire:click="deleteDjHistoryTrack({{ $track->id }})" 
                                                        class="opacity-0 group-hover:opacity-100 text-rose-500 hover:text-rose-700 p-1 rounded transition cursor-pointer"
                                                        title="Eliminar tema del historial"
                                                    >
                                                        🗑️
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center py-8 text-slate-400 text-xs">
                                                    No se encontraron canciones que coincidan con la búsqueda.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <!-- Estado Vacío -->
                        <div class="bg-white dark:bg-slate-900 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl p-8 text-center space-y-3">
                            <span class="text-4xl block">🎧</span>
                            <h4 class="font-extrabold text-sm text-slate-800 dark:text-slate-200">Aún no se ha importado la sesión real de este evento</h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto">
                                Al terminar el evento, exporta el historial desde <strong>Denon Engine DJ, Rekordbox, Serato o M3U</strong> e impórtalo aquí para guardar el tracklist oficial y cruzarlo con las peticiones.
                            </p>
                            <button 
                                type="button" 
                                wire:click="openDjHistoryModal" 
                                class="inline-flex items-center gap-1.5 bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-md transition cursor-pointer"
                            >
                                <span>📥</span>
                                Importar Historial de Sesión
                            </button>
                        </div>
                    @endif

                </div>

            </div>
        @endif

        {{-- MATERIAL / HOJA DE CARGA --}}
        @if($activeTab == 'equipment')
            <div>
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 border-b pb-4">
                    <div>
                        <h4 class="text-lg font-bold text-gray-800">Equipamiento Técnico y Hoja de Carga</h4>
                        <p class="text-xs text-gray-500">Asigna los equipos del inventario necesarios para este evento y genera el checklist de montaje/recogida.</p>
                    </div>
                    <div>
                        <a href="{{ route('admin.events.packing_list.pdf', $event->id) }}" target="_blank" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-md shadow transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Descargar Hoja de Carga (PDF)
                        </a>
                    </div>
                </div>

                @if (session()->has('equipment_message'))
                    <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-3 rounded text-sm shadow-sm">
                        {{ session('equipment_message') }}
                    </div>
                @endif

                <!-- Formulario para añadir material al evento -->
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6 shadow-inner">
                    <h5 class="font-bold text-gray-700 text-sm mb-3">➕ Añadir Material del Inventario</h5>
                    <form wire:submit.prevent="addEquipment" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                        <div class="md:col-span-6">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Seleccionar Equipo *</label>
                            <select wire:model="selected_equipment_id" required class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">-- Elige un aparato del inventario --</option>
                                @php
                                    $groupedEquip = $allEquipment->groupBy('category');
                                @endphp
                                @foreach($groupedEquip as $catName => $items)
                                    <optgroup label="📂 {{ $catName }}">
                                        @foreach($items as $eq)
                                            <option value="{{ $eq->id }}">
                                                {{ $eq->name }} {{ $eq->brand_model ? '('.$eq->brand_model.')' : '' }} [Stock: {{ $eq->quantity }}]
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('selected_equipment_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Cantidad *</label>
                            <input type="number" min="1" wire:model="equipment_quantity" required class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('equipment_quantity') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Notas / Ubicación</label>
                            <input type="text" wire:model="equipment_notes" placeholder="Ej: Ceremonia, Cabina DJ..." class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <div class="md:col-span-1">
                            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-3 rounded-md text-sm shadow transition-colors">
                                Añadir
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Listado de Material Asignado -->
                @php
                    $assignedGrouped = $event->equipment->groupBy('category');
                @endphp

                @if($assignedGrouped->count() > 0)
                    <div class="space-y-6">
                        @foreach($assignedGrouped as $catName => $assignedItems)
                            <div class="border border-gray-200 rounded-lg overflow-hidden shadow-sm">
                                <div class="bg-indigo-50 px-4 py-2 border-b border-indigo-100 flex justify-between items-center">
                                    <h6 class="font-bold text-indigo-900 text-sm uppercase tracking-wide">
                                        {{ $catName }} 
                                        <span class="ml-2 text-xs font-normal text-indigo-600">({{ $assignedItems->sum('pivot.quantity') }} uds)</span>
                                    </h6>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Equipo</th>
                                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Configuración / DMX</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Observaciones</th>
                                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($assignedItems as $eqItem)
                                                <tr>
                                                    <td class="px-4 py-3 text-sm">
                                                        <div class="font-bold text-gray-900">{{ $eqItem->name }}</div>
                                                        @if($eqItem->brand_model)
                                                            <div class="text-xs text-gray-500">{{ $eqItem->brand_model }}</div>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 text-sm text-center">
                                                        <div class="flex items-center justify-center space-x-2">
                                                            <button wire:click="updateEquipmentQuantity({{ $eqItem->pivot->id }}, -1)" class="text-gray-400 hover:text-red-500 focus:outline-none font-bold text-base px-1">
                                                                -
                                                            </button>
                                                            <span class="font-bold text-gray-900 px-2 py-0.5 bg-gray-100 rounded-full text-xs">
                                                                {{ $eqItem->pivot->quantity }}
                                                            </span>
                                                            <button wire:click="updateEquipmentQuantity({{ $eqItem->pivot->id }}, 1)" class="text-gray-400 hover:text-green-500 focus:outline-none font-bold text-base px-1">
                                                                +
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3 text-sm">
                                                        @if($eqItem->is_dmx)
                                                            @php
                                                                $start = (int)$eqItem->dmx_address;
                                                                $mode = (int)$eqItem->dmx_mode;
                                                                $qty = (int)$eqItem->pivot->quantity;
                                                            @endphp
                                                            @if($start > 0 && $mode > 0 && $qty > 0)
                                                                @php
                                                                    $addrs = [];
                                                                    for ($i = 0; $i < $qty; $i++) {
                                                                        $addrs[] = $start + ($i * $mode);
                                                                    }
                                                                @endphp
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-indigo-100 text-indigo-800">
                                                                    DMX: {{ implode(', ', $addrs) }}
                                                                </span>
                                                                <span class="text-xs text-gray-500 block mt-0.5">({{ $eqItem->dmx_mode }})</span>
                                                            @else
                                                                <span class="text-xs font-semibold text-indigo-600">
                                                                    {{ $eqItem->dmx_address ? 'CH ' . $eqItem->dmx_address : 'DMX Sí' }}
                                                                </span>
                                                            @endif
                                                        @else
                                                            <span class="text-xs text-gray-400">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 text-sm text-gray-600">
                                                        @if($eqItem->pivot->notes)
                                                            <span class="inline-flex items-center text-xs bg-amber-50 text-amber-800 border border-amber-200 px-2 py-0.5 rounded">
                                                                📍 {{ $eqItem->pivot->notes }}
                                                            </span>
                                                        @else
                                                            <span class="text-xs text-gray-400">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 text-sm text-right font-medium">
                                                        <button wire:click="removeEquipment({{ $eqItem->pivot->id }})" wire:confirm="¿Quitar este material del evento?" class="text-red-500 hover:text-red-700 text-xs font-semibold">
                                                            Quitar
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center p-8 border-2 border-dashed border-gray-300 rounded-lg bg-white">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Sin material asignado</h3>
                        <p class="mt-1 text-sm text-gray-500">Selecciona arriba el equipamiento técnico necesario para este evento.</p>
                    </div>
                @endif
            </div>
        @endif

        {{-- PRESUPUESTOS Y PACKS --}}
        @if($activeTab == 'quotes')
            <div class="space-y-3.5">
                @if (session()->has('quote_message')) 
                    <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-3 rounded-xl text-xs font-bold shadow-xs">
                        ✓ {{ session('quote_message') }}
                    </div> 
                @endif

                @if(!empty(trim((string) $event_notes)))
                    <div x-data="{ openClientNotes: true }" class="bg-amber-50/80 dark:bg-slate-900 border border-amber-300 dark:border-amber-800/70 rounded-2xl p-3.5 shadow-2xs">
                        <div class="flex flex-wrap items-center justify-between gap-2 cursor-pointer" @click="openClientNotes = !openClientNotes">
                            <div class="flex items-center gap-2 text-xs font-extrabold text-amber-950 dark:text-amber-300">
                                <span>📝</span>
                                <span>Notas del Cliente y Solicitud del Evento</span>
                                <span class="text-[10px] font-semibold bg-amber-200/70 dark:bg-amber-950 text-amber-900 dark:text-amber-200 px-2 py-0.5 rounded-full">
                                    Ver lo que pidió el cliente
                                </span>
                            </div>
                            <button type="button" class="text-[11px] font-bold text-amber-800 dark:text-amber-300 hover:underline">
                                <span x-text="openClientNotes ? '▲ Ocultar notas' : '▼ Mostrar notas'"></span>
                            </button>
                        </div>
                        <div x-show="openClientNotes" x-transition class="mt-2.5 pt-2.5 border-t border-amber-200/80 dark:border-slate-800">
                            <div class="text-xs font-mono text-slate-800 dark:text-slate-200 whitespace-pre-line max-h-44 overflow-y-auto bg-white/90 dark:bg-slate-950 p-3 rounded-xl border border-amber-200 dark:border-slate-800">{{ $event_notes }}</div>
                        </div>
                    </div>
                @endif
                
                <!-- BARRA SUPERIOR COMPACTA: PACKS RÁPIDOS (1-CLIC) + GUARDAR TARIFAS POR TIPO DE EVENTO -->
                @php
                    $isMagicBrand = ($event->brand_clean === 'mago_leugim');
                    $hasAnyMagicSelected = !empty($quote_services['magic_kids']['selected'])
                        || !empty($quote_services['magic_adults']['selected'])
                        || !empty($quote_services['magic_mixed']['selected'])
                        || !empty($quote_services['magic_closeup']['selected']);
                    $hasAnyDjPackSelected = !empty($quote_services['pack_basic']['selected'])
                        || !empty($quote_services['pack_medium']['selected'])
                        || !empty($quote_services['pack_premium']['selected']);
                @endphp
                <div class="bg-gradient-to-r {{ $isMagicBrand ? 'from-purple-950 via-purple-900 to-indigo-950' : 'from-indigo-900 via-indigo-800 to-purple-900' }} text-white p-3.5 rounded-2xl shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h5 class="font-black text-xs uppercase tracking-wider text-indigo-200 flex items-center gap-1.5">
                                <span>⚡</span>
                                @if($isMagicBrand)
                                    Packs Rápidos (🎩 Mago Leugim · {{ $event->event_type_label }}):
                                @else
                                    Packs Rápidos ({{ $event->event_type_icon }} {{ $event->event_type_label }}):
                                @endif
                            </h5>
                            <button type="button" wire:click="selectPack('clear')" class="text-[11px] text-indigo-300 hover:text-white underline cursor-pointer">
                                Limpiar selección
                            </button>
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <button type="button" wire:click="saveCurrentPricesAsDefault" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-white/15 hover:bg-white/25 text-white border border-white/20 transition cursor-pointer" title="Guardar los precios y textos actuales como predeterminados para eventos de tipo {{ $event->event_type_label }}">
                                <span>💾</span> Fijar precios para {{ $event->event_type_label }}
                            </button>
                            <a href="{{ route('admin.settings') }}" class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-bold bg-white/10 hover:bg-white/20 text-indigo-100 border border-white/15 transition">
                                <span>⚙️</span> Tarifas
                            </a>
                        </div>
                    </div>

                    @if($isMagicBrand)
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2">
                            <button type="button" wire:click="selectPack('magic_kids')" class="px-2.5 py-2 rounded-xl border text-left transition flex items-center justify-between gap-1 cursor-pointer {{ $selected_pack_type === 'magic_kids' ? 'bg-white text-purple-950 border-white shadow-md ring-2 ring-purple-400' : 'bg-white/10 hover:bg-white/20 border-white/20 text-white' }}">
                                <div class="min-w-0">
                                    <span class="block text-[9px] uppercase font-bold opacity-75 truncate">Show Infantil</span>
                                    <h6 class="font-extrabold text-[11px] truncate">🎈 Magia Infantil</h6>
                                </div>
                                <span class="font-black text-xs shrink-0">{{ number_format($quote_services['magic_kids']['price'], 0) }}€</span>
                            </button>

                            <button type="button" wire:click="selectPack('magic_adults')" class="px-2.5 py-2 rounded-xl border text-left transition flex items-center justify-between gap-1 cursor-pointer {{ $selected_pack_type === 'magic_adults' ? 'bg-white text-purple-950 border-white shadow-md ring-2 ring-purple-400' : 'bg-white/10 hover:bg-white/20 border-white/20 text-white' }}">
                                <div class="min-w-0">
                                    <span class="block text-[9px] uppercase font-bold opacity-75 truncate">Salón / Mentalismo</span>
                                    <h6 class="font-extrabold text-[11px] truncate">🎩 Magia Adultos</h6>
                                </div>
                                <span class="font-black text-xs shrink-0">{{ number_format($quote_services['magic_adults']['price'], 0) }}€</span>
                            </button>

                            <button type="button" wire:click="selectPack('magic_mixed')" class="px-2.5 py-2 rounded-xl border text-left transition relative flex items-center justify-between gap-1 cursor-pointer {{ $selected_pack_type === 'magic_mixed' ? 'bg-white text-purple-950 border-white shadow-md ring-2 ring-emerald-400' : 'bg-white/10 hover:bg-white/20 border-white/20 text-white' }}">
                                <span class="absolute -top-1.5 right-2 bg-emerald-500 text-white text-[8px] font-black px-1.5 py-0.2 rounded-full uppercase">Top</span>
                                <div class="min-w-0">
                                    <span class="block text-[9px] uppercase font-bold opacity-75 truncate">Niños + Adultos</span>
                                    <h6 class="font-extrabold text-[11px] truncate">✨ Magia Mixta ⭐</h6>
                                </div>
                                <span class="font-black text-xs shrink-0">{{ number_format($quote_services['magic_mixed']['price'], 0) }}€</span>
                            </button>

                            <button type="button" wire:click="selectPack('magic_mixed_music')" class="px-2.5 py-2 rounded-xl border text-left transition flex items-center justify-between gap-1 cursor-pointer {{ $selected_pack_type === 'magic_mixed_music' ? 'bg-white text-purple-950 border-white shadow-md ring-2 ring-amber-400' : 'bg-white/10 hover:bg-white/20 border-white/20 text-white' }}">
                                <div class="min-w-0">
                                    <span class="block text-[9px] uppercase font-bold opacity-75 truncate">Show Mixto + 2h DJ</span>
                                    <h6 class="font-extrabold text-[11px] truncate">✨+🎶 Magia + Música</h6>
                                </div>
                                <span class="font-black text-[11px] shrink-0">Combo</span>
                            </button>

                            <button type="button" wire:click="selectPack('magic_kids_music')" class="px-2.5 py-2 rounded-xl border text-left transition flex items-center justify-between gap-1 cursor-pointer {{ $selected_pack_type === 'magic_kids_music' ? 'bg-white text-purple-950 border-white shadow-md ring-2 ring-pink-400' : 'bg-white/10 hover:bg-white/20 border-white/20 text-white' }}">
                                <div class="min-w-0">
                                    <span class="block text-[9px] uppercase font-bold opacity-75 truncate">Infantil + 2h Música</span>
                                    <h6 class="font-extrabold text-[11px] truncate">🎈+🎶 Infantil + Fiesta</h6>
                                </div>
                                <span class="font-black text-[11px] shrink-0">Combo</span>
                            </button>
                        </div>
                    @else
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2">
                            <button type="button" wire:click="selectPack('basic')" class="px-2.5 py-2 rounded-xl border text-left transition flex items-center justify-between gap-1 cursor-pointer {{ $selected_pack_type === 'basic' ? 'bg-white text-indigo-900 border-white shadow-md ring-2 ring-indigo-400' : 'bg-white/10 hover:bg-white/20 border-white/20 text-white' }}">
                                <div class="min-w-0">
                                    <span class="block text-[9px] uppercase font-bold opacity-75 truncate">{{ $quote_services['pack_basic']['hours'] ?? 4 }}h DJ</span>
                                    <h6 class="font-extrabold text-[11px] truncate">{{ $quote_services['pack_basic']['name'] ?? 'Pack Esencial' }}</h6>
                                </div>
                                <span class="font-black text-xs shrink-0">{{ number_format($quote_services['pack_basic']['price'], 0) }}€</span>
                            </button>

                            <button type="button" wire:click="selectPack('medium')" class="px-2.5 py-2 rounded-xl border text-left transition relative flex items-center justify-between gap-1 cursor-pointer {{ $selected_pack_type === 'medium' ? 'bg-white text-indigo-900 border-white shadow-md ring-2 ring-emerald-400' : 'bg-white/10 hover:bg-white/20 border-white/20 text-white' }}">
                                <span class="absolute -top-1.5 right-2 bg-emerald-500 text-white text-[8px] font-black px-1.5 py-0.2 rounded-full uppercase">Top</span>
                                <div class="min-w-0">
                                    <span class="block text-[9px] uppercase font-bold opacity-75 truncate">{{ $quote_services['pack_medium']['hours'] ?? 5 }}h + Sub + Humo</span>
                                    <h6 class="font-extrabold text-[11px] truncate">{{ $quote_services['pack_medium']['name'] ?? 'Pack Signature' }} ⭐</h6>
                                </div>
                                <span class="font-black text-xs shrink-0">{{ number_format($quote_services['pack_medium']['price'], 0) }}€</span>
                            </button>

                            <button type="button" wire:click="selectPack('premium')" class="px-2.5 py-2 rounded-xl border text-left transition flex items-center justify-between gap-1 cursor-pointer {{ $selected_pack_type === 'premium' ? 'bg-white text-indigo-900 border-white shadow-md ring-2 ring-amber-400' : 'bg-white/10 hover:bg-white/20 border-white/20 text-white' }}">
                                <div class="min-w-0">
                                    <span class="block text-[9px] uppercase font-bold opacity-75 truncate">{{ $quote_services['pack_premium']['hours'] ?? 6 }}h + Humo & Chispas</span>
                                    <h6 class="font-extrabold text-[11px] truncate">{{ $quote_services['pack_premium']['name'] ?? 'Pack Deluxe Show' }}</h6>
                                </div>
                                <span class="font-black text-xs shrink-0">{{ number_format($quote_services['pack_premium']['price'], 0) }}€</span>
                            </button>

                            @if($event->is_wedding)
                                <button type="button" wire:click="selectPack('wedding_dj')" class="px-2.5 py-2 rounded-xl border text-left transition flex items-center justify-between gap-1 cursor-pointer {{ $selected_pack_type === 'wedding_dj' ? 'bg-white text-indigo-900 border-white shadow-md ring-2 ring-indigo-400' : 'bg-white/10 hover:bg-white/20 border-white/20 text-white' }}">
                                    <div class="min-w-0">
                                        <span class="block text-[9px] uppercase font-bold opacity-75 truncate">Cóctel+Banquete+4h</span>
                                        <h6 class="font-extrabold text-[11px] truncate">Boda Solo DJ</h6>
                                    </div>
                                    <span class="font-black text-[11px] shrink-0">Pack</span>
                                </button>

                                <button type="button" wire:click="selectPack('wedding_full')" class="px-2.5 py-2 rounded-xl border text-left transition flex items-center justify-between gap-1 cursor-pointer {{ $selected_pack_type === 'wedding_full' ? 'bg-white text-indigo-900 border-white shadow-md ring-2 ring-purple-400' : 'bg-white/10 hover:bg-white/20 border-white/20 text-white' }}">
                                    <div class="min-w-0">
                                        <span class="block text-[9px] uppercase font-bold opacity-75 truncate">Pack 5h + Fotos</span>
                                        <h6 class="font-extrabold text-[11px] truncate">Boda Completa 📸</h6>
                                    </div>
                                    <span class="font-black text-[11px] shrink-0">DJ+Foto</span>
                                </button>
                            @else
                                <button type="button" wire:click="selectPack('event_sound_dj')" class="px-2.5 py-2 rounded-xl border text-left transition flex items-center justify-between gap-1 cursor-pointer {{ $selected_pack_type === 'event_sound_dj' ? 'bg-white text-indigo-900 border-white shadow-md ring-2 ring-indigo-400' : 'bg-white/10 hover:bg-white/20 border-white/20 text-white' }}">
                                    <div class="min-w-0">
                                        <span class="block text-[9px] uppercase font-bold opacity-75 truncate">Sonorización + 2h DJ</span>
                                        <h6 class="font-extrabold text-[11px] truncate">Acto + Fiesta 🎧</h6>
                                    </div>
                                    <span class="font-black text-[11px] shrink-0">Combo</span>
                                </button>

                                @if(in_array($event->event_type_clean, ['comunion', 'cumpleanos', 'empresa'], true))
                                    <button type="button" wire:click="selectPack('event_meal_dj')" class="px-2.5 py-2 rounded-xl border text-left transition flex items-center justify-between gap-1 cursor-pointer {{ $selected_pack_type === 'event_meal_dj' ? 'bg-white text-indigo-900 border-white shadow-md ring-2 ring-purple-400' : 'bg-white/10 hover:bg-white/20 border-white/20 text-white' }}">
                                        <div class="min-w-0">
                                            <span class="block text-[9px] uppercase font-bold opacity-75 truncate">Comida/Cena + 4h DJ</span>
                                            <h6 class="font-extrabold text-[11px] truncate">Comida + Fiesta 🍽️</h6>
                                        </div>
                                        <span class="font-black text-[11px] shrink-0">Pack</span>
                                    </button>
                                @else
                                    <button type="button" wire:click="selectPack('event_sound_only')" class="px-2.5 py-2 rounded-xl border text-left transition flex items-center justify-between gap-1 cursor-pointer {{ $selected_pack_type === 'event_sound_only' ? 'bg-white text-indigo-900 border-white shadow-md ring-2 ring-purple-400' : 'bg-white/10 hover:bg-white/20 border-white/20 text-white' }}">
                                        <div class="min-w-0">
                                            <span class="block text-[9px] uppercase font-bold opacity-75 truncate">Equipo + Micros</span>
                                            <h6 class="font-extrabold text-[11px] truncate">Solo Sonorización 🔊</h6>
                                        </div>
                                        <span class="font-black text-[11px] shrink-0">{{ number_format($quote_services['ceremony']['price'], 0) }}€</span>
                                    </button>
                                @endif
                            @endif
                        </div>
                    @endif
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                    <!-- CONFIGURADOR DE LÍNEAS DE PRESUPUESTO -->
                    <div class="lg:col-span-7 bg-gray-50 p-3.5 rounded-2xl border border-gray-200 shadow-xs space-y-3">
                        @if($editing_quote_id)
                            <div class="bg-amber-50 border-l-4 border-amber-500 p-2.5 rounded-xl flex items-center justify-between gap-2 shadow-xs">
                                <div class="flex items-center gap-2">
                                    <span class="text-base">✏️</span>
                                    <div class="font-black text-xs text-amber-900 uppercase tracking-wide flex items-center gap-1.5">
                                        <span>Modificando Propuesta #{{ $editing_quote_id }}</span>
                                        <span class="bg-amber-200 text-amber-900 text-[10px] px-1.5 py-0.2 rounded font-bold">Edición</span>
                                    </div>
                                </div>
                                <button type="button" wire:click="cancelQuoteEditing" class="text-[11px] bg-white border border-amber-300 text-amber-800 px-2.5 py-1 rounded-lg font-bold hover:bg-amber-100 transition cursor-pointer">
                                    ✕ Cancelar
                                </button>
                            </div>
                        @endif

                        <form wire:submit.prevent="{{ $editing_quote_id ? 'updateQuote' : 'createQuote' }}" class="space-y-3">
                            
                            <!-- BLOQUE DE ESPECTÁCULOS DE MAGIA (MAGO LEUGIM): ABIERTO ARRIBA SI LA MARCA ES MAGO LEUGIM, O DESPLEGABLE SI ES OTRA MARCA -->
                            <div x-data="{ openMagic: {{ ($isMagicBrand || $hasAnyMagicSelected) ? 'true' : 'false' }} }" class="bg-white rounded-xl border {{ $isMagicBrand ? 'border-purple-300 ring-1 ring-purple-200' : 'border-purple-200' }} shadow-xs overflow-hidden">
                                <button type="button" @click="openMagic = !openMagic" class="w-full p-3 flex items-center justify-between gap-2 text-left {{ $isMagicBrand ? 'bg-purple-50/70 hover:bg-purple-100/60' : 'hover:bg-purple-50/40' }} transition cursor-pointer">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-[11px] font-black uppercase text-purple-950 tracking-wider flex items-center gap-1.5">
                                            <span>🎩</span> Espectáculos de Magia por Bloques (Infantil / Adultos / Mixto / Close-Up)
                                        </span>
                                        @if($hasAnyMagicSelected)
                                            <span class="text-[10px] font-black bg-purple-600 text-white px-2 py-0.5 rounded-full">Activo</span>
                                        @elseif(!$isMagicBrand)
                                            <span class="text-[10px] font-semibold text-gray-400">(Clic para añadir Magia)</span>
                                        @endif
                                    </div>
                                    <span class="text-xs font-bold text-purple-700" x-text="openMagic ? '🔼 Ocultar' : '🔽 Desplegar'"></span>
                                </button>

                                <div x-show="openMagic" class="p-3 pt-2 border-t border-purple-100 space-y-2">
                                    @php
                                        $magicBlocksMeta = [
                                            'magic_kids' => [
                                                'badge' => '🎈 INFANTIL',
                                                'badge_class' => 'bg-pink-100 text-pink-800 border-pink-200',
                                                'active_class' => 'border-pink-400 bg-pink-50/40',
                                                'placeholder' => 'Ej: Espectáculo de Magia Infantil (60 min)',
                                            ],
                                            'magic_adults' => [
                                                'badge' => '🎩 ADULTOS',
                                                'badge_class' => 'bg-purple-100 text-purple-900 border-purple-200',
                                                'active_class' => 'border-purple-400 bg-purple-50/40',
                                                'placeholder' => 'Ej: Show de Magia y Humor para Adultos',
                                            ],
                                            'magic_mixed' => [
                                                'badge' => '✨ MIXTO / FAMILIAR',
                                                'badge_class' => 'bg-emerald-100 text-emerald-900 border-emerald-200',
                                                'active_class' => 'border-emerald-400 bg-emerald-50/40',
                                                'placeholder' => 'Ej: Espectáculo de Magia Mixto Familiar (Niños y Adultos)',
                                            ],
                                            'magic_closeup' => [
                                                'badge' => '🃏 MAGIA DE CERCA',
                                                'badge_class' => 'bg-amber-100 text-amber-900 border-amber-200',
                                                'active_class' => 'border-amber-400 bg-amber-50/40',
                                                'placeholder' => 'Ej: Magia de Cerca (Close-Up en Recepción / Mesas)',
                                            ],
                                        ];
                                    @endphp

                                    @foreach($magicBlocksMeta as $mKey => $mMeta)
                                        @php
                                            $mSelected = !empty($quote_services[$mKey]['selected']);
                                        @endphp
                                        <div class="p-2 rounded-lg border {{ $mSelected ? $mMeta['active_class'] : 'border-gray-200 hover:bg-gray-50' }} space-y-1.5 transition">
                                            <div class="flex items-center justify-between gap-2">
                                                <label class="flex items-center cursor-pointer flex-1 gap-2 min-w-0">
                                                    <input type="checkbox" wire:model.live="quote_services.{{ $mKey }}.selected" class="rounded text-purple-600 focus:ring-purple-500 h-4 w-4 shrink-0">
                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex items-center gap-1.5 flex-wrap">
                                                            <span class="text-[9px] font-black uppercase px-1.5 py-0.2 rounded border {{ $mMeta['badge_class'] }}">{{ $mMeta['badge'] }}</span>
                                                            <span class="text-xs font-bold text-gray-900 truncate">{{ $quote_services[$mKey]['name'] }}</span>
                                                        </div>
                                                        <p class="text-[10px] text-gray-500 truncate" title="{{ $quote_services[$mKey]['desc'] }}">{{ $quote_services[$mKey]['desc'] }}</p>
                                                    </div>
                                                </label>
                                                <div class="flex items-center gap-1 shrink-0">
                                                    <input type="number" wire:model.live="quote_services.{{ $mKey }}.quantity" min="1" max="10" class="w-10 border rounded p-1 text-xs text-center" title="Número de pases / bloques">
                                                    <span class="text-xs text-gray-400">x</span>
                                                    <input type="number" step="0.01" wire:model.live="quote_services.{{ $mKey }}.price" class="w-16 border rounded p-1 text-xs text-right font-bold focus:ring-purple-500">
                                                    <span class="text-xs font-bold text-gray-600">€</span>
                                                </div>
                                            </div>

                                            @if($mSelected)
                                                <div class="pt-1.5 border-t border-purple-200/70 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                    <div>
                                                        <label class="block text-[10px] font-bold text-purple-950 mb-0.5">📌 Título en Dossier PDF:</label>
                                                        <input type="text" wire:model.live.debounce.400ms="quote_services.{{ $mKey }}.name" placeholder="{{ $mMeta['placeholder'] }}" class="w-full text-[11px] border-purple-200 rounded-lg p-1.5 bg-white font-semibold text-gray-800">
                                                    </div>
                                                    <div>
                                                        <label class="block text-[10px] font-bold text-purple-950 mb-0.5">📝 Detalle del espectáculo (sale en PDF):</label>
                                                        <textarea rows="2" wire:model.live.debounce.400ms="quote_services.{{ $mKey }}.desc" class="w-full text-[11px] border-purple-200 rounded-lg p-1.5 bg-white text-gray-700 leading-snug"></textarea>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- SECCIÓN: PACKS DE SONIDO & DJ (PLEGADA SI ES MAGO LEUGIM PARA NO ESTORBAR; ABIERTA SI ES MARCA DJ) -->
                            <div x-data="{ openDjPacks: {{ (!$isMagicBrand || $hasAnyDjPackSelected) ? 'true' : 'false' }} }" class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
                                <button type="button" @click="openDjPacks = !openDjPacks" class="w-full p-3 flex items-center justify-between gap-2 text-left hover:bg-gray-50 transition cursor-pointer">
                                    <h6 class="text-[11px] font-black uppercase text-indigo-900 tracking-wider flex items-center gap-1.5">
                                        <span>🎛️</span> {{ $isMagicBrand ? 'Packs DJ de Larga Duración (4h a 6h) — Opcional' : 'Packs de Sonido, Iluminación y DJ' }}
                                    </h6>
                                    <span class="text-[10px] font-bold text-indigo-600" x-text="openDjPacks ? '🔼 Ocultar' : '🔽 Desplegar'"></span>
                                </button>

                                <div x-show="openDjPacks" class="p-3 pt-1.5 border-t border-gray-100 space-y-1.5">
                                    <!-- Pack Básico -->
                                    <label class="flex items-center justify-between p-2 rounded-lg border hover:bg-gray-50 cursor-pointer transition gap-2 {{ $quote_services['pack_basic']['selected'] ? 'border-indigo-500 bg-indigo-50/50' : 'border-gray-200' }}">
                                        <div class="flex items-center gap-2 min-w-0 flex-1">
                                            <input type="checkbox" wire:model.live="quote_services.pack_basic.selected" class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 shrink-0">
                                            <div class="min-w-0 flex-1">
                                                <span class="font-bold text-xs text-gray-900">{{ $quote_services['pack_basic']['name'] }}</span>
                                                <p class="text-[10px] text-gray-500 truncate" title="{{ $quote_services['pack_basic']['desc'] }}">{{ $quote_services['pack_basic']['desc'] }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1 shrink-0">
                                            <input type="number" step="0.01" wire:model.live="quote_services.pack_basic.price" class="w-16 border rounded p-1 text-xs text-right font-bold focus:ring-indigo-500">
                                            <span class="text-xs font-bold text-gray-600">€</span>
                                        </div>
                                    </label>

                                    <!-- Pack Medio -->
                                    <label class="flex items-center justify-between p-2 rounded-lg border hover:bg-gray-50 cursor-pointer transition gap-2 {{ $quote_services['pack_medium']['selected'] ? 'border-emerald-500 bg-emerald-50/50' : 'border-gray-200' }}">
                                        <div class="flex items-center gap-2 min-w-0 flex-1">
                                            <input type="checkbox" wire:model.live="quote_services.pack_medium.selected" class="rounded text-emerald-600 focus:ring-emerald-500 h-4 w-4 shrink-0">
                                            <div class="min-w-0 flex-1">
                                                <span class="font-bold text-xs text-gray-900">{{ $quote_services['pack_medium']['name'] }}</span>
                                                <p class="text-[10px] text-gray-500 truncate" title="{{ $quote_services['pack_medium']['desc'] }}">{{ $quote_services['pack_medium']['desc'] }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1 shrink-0">
                                            <input type="number" step="0.01" wire:model.live="quote_services.pack_medium.price" class="w-16 border rounded p-1 text-xs text-right font-bold focus:ring-indigo-500">
                                            <span class="text-xs font-bold text-gray-600">€</span>
                                        </div>
                                    </label>

                                    <!-- Pack Premium -->
                                    <label class="flex items-center justify-between p-2 rounded-lg border hover:bg-gray-50 cursor-pointer transition gap-2 {{ $quote_services['pack_premium']['selected'] ? 'border-amber-500 bg-amber-50/50' : 'border-gray-200' }}">
                                        <div class="flex items-center gap-2 min-w-0 flex-1">
                                            <input type="checkbox" wire:model.live="quote_services.pack_premium.selected" class="rounded text-amber-600 focus:ring-amber-500 h-4 w-4 shrink-0">
                                            <div class="min-w-0 flex-1">
                                                <span class="font-bold text-xs text-gray-900">{{ $quote_services['pack_premium']['name'] }}</span>
                                                <p class="text-[10px] text-gray-500 truncate" title="{{ $quote_services['pack_premium']['desc'] }}">{{ $quote_services['pack_premium']['desc'] }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1 shrink-0">
                                            <input type="number" step="0.01" wire:model.live="quote_services.pack_premium.price" class="w-16 border rounded p-1 text-xs text-right font-bold focus:ring-indigo-500">
                                            <span class="text-xs font-bold text-gray-600">€</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- SECCIÓN: MÚSICA DESPUÉS DE LA MAGIA / SERVICIOS MUSICALES ADAPTADOS -->
                            @php
                                $evTypeClean = $event->event_type_clean;
                                $cSelected = !empty($quote_services['cocktail']['selected']);
                                $rSelected = !empty($quote_services['restaurant']['selected']);
                                $cPrice = (float)($quote_services['cocktail']['price'] ?? 0);
                                $rPrice = (float)($quote_services['restaurant']['price'] ?? 0);
                                $packDiscountInfo = \App\Models\Setting::getCocktailRestaurantPackInfo($cPrice, $rPrice);
                            @endphp
                            <div x-data="{ showMealOptions: {{ (!$isMagicBrand && ($event->is_wedding || in_array($evTypeClean, ['empresa', 'comunion'], true)) || $cSelected || $rSelected) ? 'true' : 'false' }} }" class="bg-white p-3 rounded-xl border border-gray-200 space-y-2 shadow-xs">
                                <div class="flex items-center justify-between border-b pb-1.5 gap-2">
                                    <h6 class="text-[11px] font-black uppercase text-indigo-900 tracking-wider flex items-center gap-1.5">
                                        <span>🎶</span> {{ $isMagicBrand ? 'Música después de la Magia, Sonorización & Animación' : 'Servicios Musicales (' . $event->event_type_label . ')' }}
                                    </h6>
                                    @if($isMagicBrand || (!$event->is_wedding && !in_array($evTypeClean, ['empresa', 'comunion'], true)))
                                        <button type="button" @click="showMealOptions = !showMealOptions" class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-2 py-0.5 rounded-full border border-indigo-200 transition cursor-pointer">
                                            <span x-text="showMealOptions ? '🔼 Ocultar comida/aperitivo' : '➕ Añadir comida/aperitivo'"></span>
                                        </button>
                                    @endif
                                </div>

                                <div class="space-y-1.5">
                                    @if($isMagicBrand)
                                        <!-- En Mago Leugim ponemos PRIMERO la Música después de la Magia -->
                                        <div class="p-2 rounded-lg border {{ !empty($quote_services['dj_custom']['selected']) ? 'border-indigo-300 bg-indigo-50/30' : 'border-gray-100 hover:bg-gray-50' }} space-y-1.5">
                                            <div class="flex items-center justify-between gap-2">
                                                <label class="flex items-center cursor-pointer flex-1 gap-2 min-w-0">
                                                    <input type="checkbox" wire:model.live="quote_services.dj_custom.selected" class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 shrink-0">
                                                    <div class="min-w-0">
                                                        <span class="text-xs font-bold text-gray-800">{{ $quote_services['dj_custom']['name'] ?? 'Música y Fiesta después de la Magia (DJ / Discomóvil)' }}</span>
                                                        <span class="block text-[10px] text-gray-500 truncate">{{ $quote_services['dj_custom']['desc'] }}</span>
                                                    </div>
                                                </label>
                                                <div class="flex items-center gap-1 shrink-0">
                                                    <input type="number" wire:model.live="quote_services.dj_custom.quantity" min="1" max="12" class="w-10 border rounded p-1 text-xs text-center">
                                                    <span class="text-xs text-gray-400">h @</span>
                                                    <input type="number" step="0.01" wire:model.live="quote_services.dj_custom.price" class="w-16 border rounded p-1 text-xs text-right font-bold">
                                                    <span class="text-xs text-gray-600">€</span>
                                                </div>
                                            </div>
                                            @if(!empty($quote_services['dj_custom']['selected']))
                                                <div class="pt-1.5 border-t border-indigo-100 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                    <div>
                                                        <label class="block text-[10px] font-bold text-indigo-900 mb-0.5">📌 Título en PDF:</label>
                                                        <input type="text" wire:model.live.debounce.400ms="quote_services.dj_custom.name" placeholder="Ej: Música y Animación después de la Magia" class="w-full text-[11px] border-indigo-200 rounded-lg p-1.5 bg-white font-semibold text-gray-800">
                                                    </div>
                                                    <div>
                                                        <label class="block text-[10px] font-bold text-indigo-900 mb-0.5">📝 Detalle en PDF:</label>
                                                        <textarea rows="2" wire:model.live.debounce.400ms="quote_services.dj_custom.desc" class="w-full text-[11px] border-indigo-200 rounded-lg p-1.5 bg-white text-gray-700 leading-snug"></textarea>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    <!-- 1. Ceremonia (Boda) / Sonorización Extra de Acto o Escenario -->
                                    <div class="p-2 rounded-lg border {{ !empty($quote_services['ceremony']['selected']) ? 'border-indigo-300 bg-indigo-50/30' : 'border-gray-100 hover:bg-gray-50' }} space-y-1.5">
                                        <div class="flex items-center justify-between gap-2">
                                            <label class="flex items-center cursor-pointer flex-1 gap-2 min-w-0">
                                                <input type="checkbox" wire:model.live="quote_services.ceremony.selected" class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 shrink-0">
                                                <div class="min-w-0 flex-1">
                                                    <span class="text-xs font-bold text-gray-800">{{ $quote_services['ceremony']['name'] ?? ($event->is_wedding ? 'Ceremonia Civil / Religiosa' : 'Sonorización de Evento / Acto / Escenario') }}</span>
                                                    <span class="block text-[10px] text-gray-500 truncate">{{ $quote_services['ceremony']['desc'] }}</span>
                                                </div>
                                            </label>
                                            <div class="flex items-center gap-1 shrink-0">
                                                <input type="number" wire:model.live="quote_services.ceremony.quantity" class="w-10 border rounded p-1 text-xs text-center">
                                                <span class="text-xs text-gray-400">x</span>
                                                <input type="number" step="0.01" wire:model.live="quote_services.ceremony.price" class="w-16 border rounded p-1 text-xs text-right font-bold">
                                                <span class="text-xs text-gray-600">€</span>
                                            </div>
                                        </div>
                                        @if(!empty($quote_services['ceremony']['selected']))
                                            <div class="pt-1.5 border-t border-indigo-100 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                <div>
                                                    <label class="block text-[10px] font-bold text-indigo-900 mb-0.5">📌 Título en Dossier PDF:</label>
                                                    <input type="text" wire:model.live.debounce.400ms="quote_services.ceremony.name" placeholder="Ej: Sonorización de Evento / Acto / Festival" class="w-full text-[11px] border-indigo-200 rounded-lg p-1.5 bg-white font-semibold text-gray-800">
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-bold text-indigo-900 mb-0.5">📝 Detalle / Qué incluye (sale en PDF):</label>
                                                    <textarea rows="2" wire:model.live.debounce.400ms="quote_services.ceremony.desc" placeholder="Ej: Incluye montaje, prueba de sonido y microfonía inalámbrica." class="w-full text-[11px] border-indigo-200 rounded-lg p-1.5 bg-white text-gray-700 leading-snug"></textarea>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- 2. Cóctel/Recepción y Banquete/Comida -->
                                    <div x-show="showMealOptions" class="space-y-1.5">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                            <!-- Cóctel / Recepción / Aperitivo -->
                                            <div class="p-2 rounded-lg border {{ $cSelected ? 'border-indigo-300 bg-indigo-50/30' : 'border-gray-100 hover:bg-gray-50' }} space-y-1.5">
                                                <div class="flex items-center justify-between gap-1.5">
                                                    <label class="flex items-center cursor-pointer flex-1 gap-1.5 min-w-0">
                                                        <input type="checkbox" wire:model.live="quote_services.cocktail.selected" class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 shrink-0">
                                                        <div class="min-w-0">
                                                            <span class="text-xs font-bold text-gray-800 block truncate">{{ $quote_services['cocktail']['name'] ?? ($event->is_wedding ? 'Música en Cóctel' : 'Ambientación Recepción / Aperitivo') }}</span>
                                                            <span class="block text-[9px] text-gray-500 truncate">{{ $quote_services['cocktail']['desc'] }}</span>
                                                        </div>
                                                    </label>
                                                    <div class="flex items-center gap-0.5 shrink-0">
                                                        <input type="number" step="0.01" wire:model.live="quote_services.cocktail.price" class="w-14 border rounded p-1 text-xs text-right font-bold">
                                                        <span class="text-[11px] text-gray-600">€</span>
                                                    </div>
                                                </div>
                                                @if($cSelected)
                                                    <div class="pt-1 border-t border-indigo-100 space-y-1">
                                                        <input type="text" wire:model.live.debounce.400ms="quote_services.cocktail.name" placeholder="Título en PDF" class="w-full text-[10px] border-indigo-200 rounded p-1 bg-white font-semibold text-gray-800">
                                                        <input type="text" wire:model.live.debounce.400ms="quote_services.cocktail.desc" placeholder="Descripción en PDF" class="w-full text-[10px] border-indigo-200 rounded p-1 bg-white text-gray-600">
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Restaurante / Banquete / Comida-Cena -->
                                            <div class="p-2 rounded-lg border {{ $rSelected ? 'border-indigo-300 bg-indigo-50/30' : 'border-gray-100 hover:bg-gray-50' }} space-y-1.5">
                                                <div class="flex items-center justify-between gap-1.5">
                                                    <label class="flex items-center cursor-pointer flex-1 gap-1.5 min-w-0">
                                                        <input type="checkbox" wire:model.live="quote_services.restaurant.selected" class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 shrink-0">
                                                        <div class="min-w-0">
                                                            <span class="text-xs font-bold text-gray-800 block truncate">{{ $quote_services['restaurant']['name'] ?? ($event->is_wedding ? 'Regalos & Banquete' : 'Ambientación Comida / Cena') }}</span>
                                                            <span class="block text-[9px] text-gray-500 truncate">{{ $quote_services['restaurant']['desc'] }}</span>
                                                        </div>
                                                    </label>
                                                    <div class="flex items-center gap-0.5 shrink-0">
                                                        <input type="number" step="0.01" wire:model.live="quote_services.restaurant.price" class="w-14 border rounded p-1 text-xs text-right font-bold">
                                                        <span class="text-[11px] text-gray-600">€</span>
                                                    </div>
                                                </div>
                                                @if($rSelected)
                                                    <div class="pt-1 border-t border-indigo-100 space-y-1">
                                                        <input type="text" wire:model.live.debounce.400ms="quote_services.restaurant.name" placeholder="Título en PDF" class="w-full text-[10px] border-indigo-200 rounded p-1 bg-white font-semibold text-gray-800">
                                                        <input type="text" wire:model.live.debounce.400ms="quote_services.restaurant.desc" placeholder="Descripción en PDF" class="w-full text-[10px] border-indigo-200 rounded p-1 bg-white text-gray-600">
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        @if($cSelected && $rSelected && !empty($packDiscountInfo['enabled']) && $packDiscountInfo['discount_amount'] > 0)
                                            <div class="p-2 rounded-lg bg-emerald-50 border border-emerald-200 flex items-center justify-between text-xs text-emerald-800">
                                                <span class="font-bold">🎁 ¡Descuento Pack Combinado Activado!</span>
                                                <span class="font-extrabold text-emerald-700">-{{ number_format($packDiscountInfo['discount_amount'], 2, ',', '.') }} € ({{ round($packDiscountInfo['discount_percentage']) }}% dto)</span>
                                            </div>
                                        @endif
                                    </div>

                                    @if(!$isMagicBrand)
                                        <!-- 3. Sesión DJ / Baile (Horas sueltas) -->
                                        <div class="p-2 rounded-lg border {{ !empty($quote_services['dj_custom']['selected']) ? 'border-indigo-300 bg-indigo-50/30' : 'border-gray-100 hover:bg-gray-50' }} space-y-1.5">
                                            <div class="flex items-center justify-between gap-2">
                                                <label class="flex items-center cursor-pointer flex-1 gap-2 min-w-0">
                                                    <input type="checkbox" wire:model.live="quote_services.dj_custom.selected" class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 shrink-0">
                                                    <div class="min-w-0">
                                                        <span class="text-xs font-bold text-gray-800">{{ $quote_services['dj_custom']['name'] ?? ($event->is_wedding ? 'DJ Baile (Horas sueltas)' : 'Sesión DJ Fiesta / Baile (Horas sueltas)') }}</span>
                                                        <span class="block text-[10px] text-gray-500 truncate">{{ $quote_services['dj_custom']['desc'] }}</span>
                                                    </div>
                                                </label>
                                                <div class="flex items-center gap-1 shrink-0">
                                                    <input type="number" wire:model.live="quote_services.dj_custom.quantity" min="1" max="12" class="w-10 border rounded p-1 text-xs text-center">
                                                    <span class="text-xs text-gray-400">h @</span>
                                                    <input type="number" step="0.01" wire:model.live="quote_services.dj_custom.price" class="w-16 border rounded p-1 text-xs text-right font-bold">
                                                    <span class="text-xs text-gray-600">€</span>
                                                </div>
                                            </div>
                                            @if(!empty($quote_services['dj_custom']['selected']))
                                                <div class="pt-1 border-t border-indigo-100">
                                                    <textarea rows="2" wire:model.live.debounce.400ms="quote_services.dj_custom.desc" placeholder="Ej: Incluye prueba de sonido y {{ $quote_services['dj_custom']['quantity'] ?? 2 }} horas de baile/espectáculo. Hora adicional a {{ $quote_services['extra_hours']['price'] ?? 140 }} €/h." class="w-full text-[11px] border-indigo-200 rounded-lg p-1.5 bg-white text-gray-700 leading-snug"></textarea>
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    <!-- 4. Horas Extra + Karaoke en 2 columnas -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                        <!-- Horas Extra -->
                                        <div class="flex items-center justify-between p-2 rounded-lg border border-amber-200 bg-amber-50/50 gap-1.5">
                                            <div class="min-w-0">
                                                <span class="text-xs font-bold text-amber-900 block truncate">{{ $isMagicBrand ? 'Horas Extra Música / Fiesta' : ($event->is_wedding ? 'Horas Extra Baile' : 'Horas Extra de Fiesta') }}</span>
                                                <span class="block text-[9px] text-amber-700 truncate">Opción de ampliar horario</span>
                                            </div>
                                            <div class="flex items-center gap-0.5 shrink-0">
                                                <input type="number" wire:model.live="quote_services.extra_hours.quantity" min="0" max="10" class="w-9 border rounded p-1 text-xs text-center font-bold">
                                                <span class="text-[10px] text-gray-400">x</span>
                                                <input type="number" step="0.01" wire:model.live="quote_services.extra_hours.price" class="w-14 border rounded p-1 text-xs text-right font-bold">
                                                <span class="text-[10px] text-gray-600">€</span>
                                            </div>
                                        </div>

                                        <!-- Karaoke -->
                                        <div class="flex items-center justify-between p-2 rounded-lg border {{ !empty($quote_services['karaoke']['selected']) ? 'border-indigo-300 bg-indigo-50/30' : 'border-gray-100 hover:bg-gray-50' }} gap-1.5">
                                            <label class="flex items-center cursor-pointer flex-1 gap-1.5 min-w-0">
                                                <input type="checkbox" wire:model.live="quote_services.karaoke.selected" class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 shrink-0">
                                                <div class="min-w-0">
                                                    <span class="text-xs font-bold text-gray-800 block truncate">Karaoke Interactivo</span>
                                                    <span class="block text-[9px] text-gray-500 truncate">Proyector + micros</span>
                                                </div>
                                            </label>
                                            <div class="flex items-center gap-0.5 shrink-0">
                                                <input type="number" step="0.01" wire:model.live="quote_services.karaoke.price" class="w-14 border rounded p-1 text-xs text-right font-bold">
                                                <span class="text-[11px] text-gray-600">€</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN ACCESO DIRECTO: NOTAS / OBSERVACIONES DEL PRESUPUESTO (VISIBLE ARRIBA SIN SCROLL) -->
                            <div class="bg-amber-50/60 p-3 rounded-xl border border-amber-200 space-y-1.5 shadow-xs">
                                <div class="flex items-center justify-between">
                                    <h6 class="text-[11px] font-black uppercase text-amber-900 tracking-wider flex items-center gap-1.5">
                                        <span>📝</span> Notas y Condiciones Especiales en el Presupuesto
                                    </h6>
                                    <span class="text-[9px] bg-amber-100 text-amber-800 font-bold px-2 py-0.5 rounded-full">Sale en PDF y Enlace Cliente</span>
                                </div>
                                <textarea
                                    rows="2"
                                    wire:model.live.debounce.400ms="quote_admin_notes"
                                    placeholder="Ej: Incluye instalación y prueba de sonido con ensayo general + 2 horas de baile/espectáculo. En caso de ampliar horario, cada hora adicional será a 140 €/h."
                                    class="w-full border-amber-300 rounded-lg p-2 text-xs text-gray-800 focus:ring-amber-500 focus:border-amber-500 bg-white placeholder:text-gray-400 leading-snug"
                                ></textarea>
                            </div>

                            <!-- SECCIÓN UNIFICADA COMPACTA: SEÑAL + IVA + MÉTODOS DE PAGO (EN 1 SOLA TARJETA) -->
                            <div class="bg-white p-3 rounded-xl border border-indigo-200 shadow-xs space-y-2.5">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <!-- Señal -->
                                    <div>
                                        @php
                                            $gradualDepInfo = \App\Models\Quote::getGradualDepositInfo($event->event_date);
                                        @endphp
                                        <div class="flex items-center justify-between gap-2 mb-1">
                                            <label class="block text-[10px] font-black uppercase text-indigo-900">💶 Señal de Reserva</label>
                                            <button type="button" wire:click="applyGradualDeposit" title="Aplicar porcentaje gradual automático según tiempo restante hasta el evento (25% a +10 meses hasta 50% a ≤3 meses)" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-bold transition cursor-pointer {{ ($quote_deposit_type === 'percentage' && (float)$quote_deposit_percentage == (float)$gradualDepInfo['percentage']) ? 'bg-indigo-100 text-indigo-800 border border-indigo-200' : 'bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100' }}">
                                                ⚡ {{ $gradualDepInfo['label'] }}
                                            </button>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <button type="button" wire:click="$set('quote_deposit_type', 'none')" class="px-2 py-1 rounded-lg border text-[11px] font-bold transition cursor-pointer {{ $quote_deposit_type === 'none' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100' }}">
                                                🚫 0€
                                            </button>
                                            <button type="button" wire:click="$set('quote_deposit_type', 'percentage')" class="px-2 py-1 rounded-lg border text-[11px] font-bold transition cursor-pointer {{ $quote_deposit_type === 'percentage' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100' }}">
                                                % Porcentaje
                                            </button>
                                            <button type="button" wire:click="$set('quote_deposit_type', 'fixed')" class="px-2 py-1 rounded-lg border text-[11px] font-bold transition cursor-pointer {{ $quote_deposit_type === 'fixed' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100' }}">
                                                € Fijo
                                            </button>
                                            @if($quote_deposit_type === 'percentage')
                                                <div class="flex items-center gap-0.5 ml-1">
                                                    <input type="number" step="1" min="0" max="100" wire:model.live="quote_deposit_percentage" class="w-14 border rounded-lg p-1 text-xs font-black text-center text-indigo-700">
                                                    <span class="text-xs font-bold text-gray-500">%</span>
                                                </div>
                                            @elseif($quote_deposit_type === 'fixed')
                                                <div class="flex items-center gap-0.5 ml-1">
                                                    <input type="number" step="1" min="0" wire:model.live="quote_deposit_fixed_amount" class="w-16 border rounded-lg p-1 text-xs font-black text-right text-indigo-700">
                                                    <span class="text-xs font-bold text-gray-500">€</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Régimen IVA -->
                                    <div>
                                        <label class="block text-[10px] font-black uppercase text-indigo-900 mb-1">🏛️ Régimen de IVA</label>
                                        <div class="grid grid-cols-3 gap-1">
                                            <button type="button" wire:click="$set('quote_tax_type', 'included')" class="px-2 py-1 rounded-lg border text-[11px] font-bold transition cursor-pointer {{ $quote_tax_type === 'included' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100' }}">
                                                IVA Incl.
                                            </button>
                                            <button type="button" wire:click="$set('quote_tax_type', 'excluded')" class="px-2 py-1 rounded-lg border text-[11px] font-bold transition cursor-pointer {{ $quote_tax_type === 'excluded' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100' }}">
                                                +21% IVA
                                            </button>
                                            <button type="button" wire:click="$set('quote_tax_type', 'none')" class="px-2 py-1 rounded-lg border text-[11px] font-bold transition cursor-pointer {{ $quote_tax_type === 'none' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100' }}">
                                                Sin IVA
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Métodos de Pago en 1 fila -->
                                <div class="pt-2 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2">
                                    <span class="text-[10px] font-black uppercase text-gray-500">💳 Formas de Pago:</span>
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <label class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg border cursor-pointer text-[11px] {{ in_array('transfer', $quote_payment_methods) ? 'bg-emerald-50 border-emerald-400 font-bold text-emerald-900' : 'bg-gray-50 border-gray-200 text-gray-500' }}">
                                            <input type="checkbox" wire:model.live="quote_payment_methods" value="transfer" class="rounded text-emerald-600 h-3 w-3">
                                            <span>🏦 Transf.</span>
                                        </label>
                                        <label class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg border cursor-pointer text-[11px] {{ in_array('bizum', $quote_payment_methods) ? 'bg-emerald-50 border-emerald-400 font-bold text-emerald-900' : 'bg-gray-50 border-gray-200 text-gray-500' }}">
                                            <input type="checkbox" wire:model.live="quote_payment_methods" value="bizum" class="rounded text-emerald-600 h-3 w-3">
                                            <span>📱 Bizum</span>
                                        </label>
                                        <label class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg border cursor-pointer text-[11px] {{ in_array('cash', $quote_payment_methods) ? 'bg-emerald-50 border-emerald-400 font-bold text-emerald-900' : 'bg-gray-50 border-gray-200 text-gray-500' }}">
                                            <input type="checkbox" wire:model.live="quote_payment_methods" value="cash" class="rounded text-emerald-600 h-3 w-3">
                                            <span>💵 Efectivo</span>
                                        </label>
                                        <label class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg border cursor-pointer text-[11px] {{ in_array('card', $quote_payment_methods) ? 'bg-emerald-50 border-emerald-400 font-bold text-emerald-900' : 'bg-gray-50 border-gray-200 text-gray-500' }}">
                                            <input type="checkbox" wire:model.live="quote_payment_methods" value="card" class="rounded text-emerald-600 h-3 w-3">
                                            <span>💳 Tarjeta</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN DESPLEGABLE 1: FOTOMATÓN, PLATAFORMA 360º, PACK DÚO Y MESAS DULCES / GOFRES -->
                            @php
                                $pbProviders = \App\Models\Setting::getPhotoboothProviders();
                                $activeProv = isset($pbProviders[$photobooth_provider])
                                    ? $photobooth_provider
                                    : (array_key_first($pbProviders) ?: 'logrono');
                                $currentProvData = $pbProviders[$activeProv] ?? [];
                                $provServiceMeta = [
                                    'pack_duo_enabled' => ['icon' => '🔹', 'label' => 'Pack Dúo'],
                                    'photobooth_enabled' => ['icon' => '📸', 'label' => 'Fotomatón'],
                                    'video_360_enabled' => ['icon' => '🎥', 'label' => 'Plataforma 360º'],
                                    'chocolate_fountain_enabled' => ['icon' => '🍫', 'label' => 'Fuente Chocolate'],
                                    'candy_bar_enabled' => ['icon' => '🍬', 'label' => 'Mesa Chuches'],
                                    'waffle_cart_enabled' => ['icon' => '🧇', 'label' => 'Carrito Gofres'],
                                ];
                                $hasExternalSelected = !empty($quote_services['pack_duo_360']['selected'])
                                    || !empty($quote_services['photobooth']['selected'])
                                    || !empty($quote_services['video_360']['selected'])
                                    || !empty($quote_services['chocolate_fountain']['selected'])
                                    || !empty($quote_services['candy_bar']['selected'])
                                    || !empty($quote_services['waffle_cart']['selected']);
                            @endphp
                            <div x-data="{ openExternal: {{ $hasExternalSelected ? 'true' : 'false' }} }" class="bg-white rounded-xl border border-indigo-200 shadow-xs overflow-hidden">
                                <button type="button" @click="openExternal = !openExternal" class="w-full p-3 flex items-center justify-between gap-2 text-left hover:bg-indigo-50/40 transition cursor-pointer">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-xs font-black uppercase text-indigo-950 tracking-wider flex items-center gap-1.5">
                                            <span>📸🎥🍫</span> Fotomatón, 360º, Fuentes de Chocolate, Chuches & Gofres
                                        </span>
                                        @if($hasExternalSelected)
                                            <span class="text-[10px] font-black bg-indigo-600 text-white px-2 py-0.5 rounded-full">Activo</span>
                                        @else
                                            <span class="text-[10px] font-semibold text-gray-400">(Clic para desplegar)</span>
                                        @endif
                                    </div>
                                    <span class="text-xs font-bold text-indigo-600" x-text="openExternal ? '🔼 Ocultar' : '🔽 Desplegar'"></span>
                                </button>
                                <div x-show="openExternal" class="p-3.5 pt-0 border-t border-indigo-100 space-y-3">
                                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-2.5 border-b border-indigo-100 pb-2.5">
                                    <div>
                                        <h6 class="text-xs font-black uppercase text-indigo-950 tracking-wider flex items-center gap-1.5">
                                            <span>📸🎥🍫</span> Empresas Externas: Fotomatón, 360º, Fuentes de Chocolate, Chuches & Gofres
                                        </h6>
                                        <p class="text-[10px] text-gray-500">
                                            Selecciona el proveedor externo. Solo se muestran los servicios que ofrece <strong>{{ $currentProvData['name'] ?? ucfirst($activeProv) }}</strong>.
                                        </p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <div class="inline-flex flex-wrap items-center bg-indigo-50/80 p-1 rounded-lg border border-indigo-200 gap-1" title="Selector interno de proveedor (no visible para el cliente)">
                                            <span class="text-[9px] font-black uppercase text-indigo-700 px-1.5">Proveedor:</span>
                                            @foreach($pbProviders as $pKey => $pInfo)
                                                <button
                                                    type="button"
                                                    wire:key="quote-prov-btn-{{ $pKey }}"
                                                    wire:click="setPhotoboothProvider('{{ $pKey }}')"
                                                    class="px-2.5 py-1 rounded-md text-[10px] font-black transition flex items-center gap-1 cursor-pointer {{ $activeProv === $pKey ? 'bg-indigo-600 text-white shadow-xs' : 'text-indigo-900 hover:bg-indigo-100' }}"
                                                >
                                                    <span>📍</span> {{ $pInfo['name'] ?? ucfirst($pKey) }}
                                                </button>
                                            @endforeach
                                            <a href="{{ route('admin.settings', ['tab' => 'pricing']) }}" class="px-2 py-1 rounded-md text-[10px] font-bold text-indigo-700 hover:bg-indigo-100 transition" title="Añadir o gestionar proveedores externos en Configuración">
                                                ➕/⚙️
                                            </a>
                                        </div>
                                        <button type="button" wire:click="sendDirectTemplate('fotomaton_360')" class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-2.5 py-1 rounded-lg transition shrink-0">
                                            <span>📸</span> Info Fotomatón/360 WA
                                        </button>
                                        @if(!empty($currentProvData['chocolate_fountain_enabled']) || !empty($currentProvData['candy_bar_enabled']) || !empty($currentProvData['waffle_cart_enabled']))
                                            <button type="button" wire:click="sendDirectTemplate('mesa_dulce_gofres')" class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-900 bg-amber-50 hover:bg-amber-100 border border-amber-300 px-2.5 py-1 rounded-lg transition shrink-0">
                                                <span>🍫</span> Info Mesa Dulce/Gofres WA
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                <!-- Barra rápida para activar/desactivar qué servicios ofrece este proveedor -->
                                <div class="flex flex-wrap items-center justify-between gap-2 bg-slate-50 px-3 py-2 rounded-lg border border-slate-200">
                                    <span class="text-[10px] font-bold text-slate-600">
                                        🎚️ Servicios de <strong>{{ $currentProvData['name'] ?? ucfirst($activeProv) }}</strong> (clic para activar/ocultar):
                                    </span>
                                    <div class="flex flex-wrap items-center gap-1">
                                        @foreach($provServiceMeta as $flagKey => $meta)
                                            @php $isEnabled = !empty($currentProvData[$flagKey]); @endphp
                                            <button
                                                type="button"
                                                wire:key="quote-srv-toggle-{{ $activeProv }}-{{ $flagKey }}"
                                                wire:click="toggleProviderServiceFromQuote('{{ $flagKey }}')"
                                                class="px-2 py-0.5 rounded-md text-[10px] font-bold border transition cursor-pointer flex items-center gap-1 {{ $isEnabled ? 'bg-emerald-50 border-emerald-300 text-emerald-900' : 'bg-white border-slate-200 text-slate-400 line-through hover:text-slate-600' }}"
                                                title="{{ $isEnabled ? 'Activo en este proveedor (clic para desactivar)' : 'Desactivado en este proveedor (clic para activar)' }}"
                                            >
                                                <span>{{ $meta['icon'] }}</span>
                                                <span>{{ $meta['label'] }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                @if(!empty($currentProvData['pack_duo_enabled']) && !empty($quote_services['photobooth']['selected']) && !empty($quote_services['video_360']['selected']) && empty($quote_services['pack_duo_360']['selected']))
                                    @php
                                        $suggestedDuoHours = ((int)($quote_services['photobooth']['hours'] ?? 2) >= 3 || (int)($quote_services['video_360']['hours'] ?? 2) >= 3) ? 3 : 2;
                                        $duoPriceSuggested = $suggestedDuoHours === 3 ? ($quote_services['pack_duo_360']['price_3h'] ?? 850) : ($quote_services['pack_duo_360']['price_2h'] ?? 690);
                                        $separateSum = (float)($quote_services['photobooth']['price'] ?? 0) + (float)($quote_services['video_360']['price'] ?? 0);
                                    @endphp
                                    <div class="p-2.5 rounded-lg bg-amber-50 border border-amber-300 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                        <div class="text-xs text-amber-900">
                                            <span class="font-black">💡 Recomendación Pack Dúo:</span> Tienes Fotomatón + Plataforma 360º por separado ({{ number_format($separateSum, 0) }} €). Con el <strong>Pack Dúo de {{ $suggestedDuoHours }}h ({{ number_format($duoPriceSuggested, 0) }} €)</strong> el cliente ahorra <strong>{{ number_format(max(0, $separateSum - $duoPriceSuggested), 0) }} €</strong>.
                                        </div>
                                        <button type="button" wire:click="setPackDuoHours({{ $suggestedDuoHours }})" class="px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-black shrink-0 transition shadow-xs">
                                            ✨ Aplicar Pack Dúo {{ $suggestedDuoHours }}h ({{ number_format($duoPriceSuggested, 0) }} €)
                                        </button>
                                    </div>
                                @endif

                                <div wire:key="quote-ext-services-wrap-{{ $activeProv }}" class="space-y-2.5">
                                    @if(!empty($currentProvData['pack_duo_enabled']) || !empty($quote_services['pack_duo_360']['selected']))
                                        <!-- Pack Dúo Todo Incluido (Fotomatón + Plataforma 360) -->
                                        <div wire:key="quote-card-duo-{{ $activeProv }}" class="p-3 rounded-xl border-2 transition {{ !empty($quote_services['pack_duo_360']['selected']) ? 'border-indigo-500 bg-indigo-50/40 shadow-xs' : 'border-gray-200 hover:bg-gray-50' }}">
                                            <div class="flex flex-col gap-2">
                                                <div class="flex items-start justify-between gap-3">
                                                    <label class="flex items-start cursor-pointer flex-1 min-w-0 gap-2">
                                                        <input type="checkbox" wire:key="q-in-duo-sel-{{ $activeProv }}" wire:model.live="quote_services.pack_duo_360.selected" class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 shrink-0">
                                                        <div class="min-w-0">
                                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                                <span class="text-xs font-black text-gray-900">🔹 Pack Dúo Todo Incluido (Fotomatón + Plataforma 360º)</span>
                                                                <span class="text-[9px] font-extrabold uppercase bg-indigo-600 text-white px-2 py-0.5 rounded-full">Ahorro Pack</span>
                                                            </div>
                                                            <span class="block text-[10px] text-gray-500 mt-0.5">Incluye ambos servicios completos con animadores, álbum de firmas, fotos y vídeos 360 ilimitados.</span>
                                                        </div>
                                                    </label>

                                                    <div class="flex items-center gap-1 shrink-0">
                                                        <input type="number" min="1" max="10" wire:key="q-in-duo-hrs-{{ $activeProv }}" wire:model.live="quote_services.pack_duo_360.hours" class="w-11 border rounded p-1 text-xs text-center font-bold" title="Horas de servicio">
                                                        <span class="text-[10px] text-gray-500 font-bold">h</span>
                                                        <input type="number" step="0.01" wire:key="q-in-duo-prc-{{ $activeProv }}-{{ $quote_services['pack_duo_360']['hours'] ?? 3 }}" wire:model.live.debounce.400ms="quote_services.pack_duo_360.price" class="w-16 border rounded p-1 text-xs text-right font-black text-indigo-700">
                                                        <span class="text-xs font-bold text-gray-600">€</span>
                                                    </div>
                                                </div>

                                                <div class="flex flex-wrap items-center gap-1.5 pl-6">
                                                    <button type="button" wire:key="q-btn-duo2-{{ $activeProv }}" wire:click="setPackDuoHours(2)" class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold border transition {{ !empty($quote_services['pack_duo_360']['selected']) && (int)($quote_services['pack_duo_360']['hours'] ?? 3) === 2 ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs' : 'bg-white text-gray-700 border-gray-300 hover:bg-indigo-50' }}">
                                                        2 Horas ({{ number_format($quote_services['pack_duo_360']['price_2h'] ?? 690, 0) }}€)
                                                    </button>
                                                    <button type="button" wire:key="q-btn-duo3-{{ $activeProv }}" wire:click="setPackDuoHours(3)" class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold border transition {{ !empty($quote_services['pack_duo_360']['selected']) && (int)($quote_services['pack_duo_360']['hours'] ?? 3) === 3 ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs' : 'bg-white text-gray-700 border-gray-300 hover:bg-indigo-50' }}">
                                                        3 Horas ({{ number_format($quote_services['pack_duo_360']['price_3h'] ?? 850, 0) }}€)
                                                    </button>
                                                </div>
                                            </div>
                                            @if(!empty($quote_services['pack_duo_360']['selected']))
                                                <div class="mt-2 pt-2 border-t border-indigo-200/60 space-y-1.5">
                                                    @if((int)($quote_services['pack_duo_360']['hours'] ?? 3) === 2)
                                                        @php
                                                            $duo3h = (float)($quote_services['pack_duo_360']['price_3h'] ?? 850);
                                                            $duoDiff = max(0, $duo3h - (float)($quote_services['pack_duo_360']['price'] ?? 690));
                                                        @endphp
                                                        <div class="bg-amber-50 border border-amber-200 rounded-lg px-2.5 py-1.5 text-[10px] font-bold text-amber-900 flex items-center gap-1.5">
                                                            <span>✨</span>
                                                            <span>En el Dossier PDF se mostrará automáticamente la opción de elegir <strong>3 Horas por {{ number_format($duo3h, 0) }} €</strong> (solo +{{ number_format($duoDiff, 0) }} € más).</span>
                                                        </div>
                                                    @endif
                                                    <label class="block text-[10px] font-bold text-indigo-900 mb-1">Descripción en el presupuesto (editable):</label>
                                                    <textarea rows="2" wire:key="q-in-duo-dsc-{{ $activeProv }}" wire:model.live.debounce.400ms="quote_services.pack_duo_360.desc" class="w-full text-[11px] border-indigo-200 rounded-lg p-1.5 bg-white text-gray-700 focus:ring-indigo-500"></textarea>
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    @if(!empty($currentProvData['photobooth_enabled']) || !empty($quote_services['photobooth']['selected']))
                                        <!-- Fotomatón & Photocall por horas -->
                                        <div wire:key="quote-card-pb-{{ $activeProv }}" class="p-3 rounded-xl border transition {{ !empty($quote_services['photobooth']['selected']) ? 'border-indigo-400 bg-indigo-50/40' : 'border-gray-200 hover:bg-gray-50' }}">
                                            <div class="flex flex-col gap-2">
                                                <div class="flex items-start justify-between gap-3">
                                                    <label class="flex items-start cursor-pointer flex-1 min-w-0 gap-2">
                                                        <input type="checkbox" wire:key="q-in-pb-sel-{{ $activeProv }}" wire:model.live="quote_services.photobooth.selected" class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 shrink-0">
                                                        <div class="min-w-0">
                                                            <span class="text-xs font-bold text-gray-900">📸 Fotomatón & Photocall</span>
                                                            <span class="block text-[10px] text-gray-500">Cámara réflex, copias ilimitadas, libro de firmas de imprenta, atrezzo, USB y animador (+{{ number_format($quote_services['photobooth']['extra_hour'] ?? 50, 0) }}€/hora extra).</span>
                                                        </div>
                                                    </label>

                                                    <div class="flex items-center gap-1 shrink-0">
                                                        <input type="number" min="1" max="10" wire:key="q-in-pb-hrs-{{ $activeProv }}" wire:model.live="quote_services.photobooth.hours" class="w-11 border rounded p-1 text-xs text-center font-bold" title="Horas de Fotomatón">
                                                        <span class="text-[10px] text-gray-500 font-bold">h</span>
                                                        <input type="number" step="0.01" wire:key="q-in-pb-prc-{{ $activeProv }}-{{ $quote_services['photobooth']['hours'] ?? 2 }}" wire:model.live.debounce.400ms="quote_services.photobooth.price" class="w-16 border rounded p-1 text-xs text-right font-bold text-indigo-700">
                                                        <span class="text-xs text-gray-600">€</span>
                                                    </div>
                                                </div>

                                                <div class="flex flex-wrap items-center gap-1.5 pl-6">
                                                    <button type="button" wire:key="q-btn-pb2-{{ $activeProv }}" wire:click="setPhotoboothHours(2)" class="px-2.5 py-1 rounded-lg text-[10px] font-bold border transition {{ !empty($quote_services['photobooth']['selected']) && (int)($quote_services['photobooth']['hours'] ?? 2) === 2 ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-indigo-50' }}">
                                                        2 Horas ({{ number_format($quote_services['photobooth']['price_2h'] ?? 380, 0) }}€)
                                                    </button>
                                                    <button type="button" wire:key="q-btn-pb3-{{ $activeProv }}" wire:click="setPhotoboothHours(3)" class="px-2.5 py-1 rounded-lg text-[10px] font-bold border transition {{ !empty($quote_services['photobooth']['selected']) && (int)($quote_services['photobooth']['hours'] ?? 2) === 3 ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-indigo-50' }}">
                                                        3 Horas ({{ number_format($quote_services['photobooth']['price_3h'] ?? 430, 0) }}€)
                                                    </button>
                                                    <button type="button" wire:key="q-btn-pbext-{{ $activeProv }}" wire:click="setPhotoboothHours({{ max(4, (int)($quote_services['photobooth']['hours'] ?? 3) + 1) }})" class="px-2 py-1 rounded-lg text-[10px] font-bold border transition {{ !empty($quote_services['photobooth']['selected']) && (int)($quote_services['photobooth']['hours'] ?? 2) >= 4 ? 'bg-amber-600 text-white border-amber-600' : 'bg-amber-50 text-amber-800 border-amber-300 hover:bg-amber-100' }}" title="Añadir 1 hora extra de Fotomatón">
                                                        +1h Extra (+{{ number_format($quote_services['photobooth']['extra_hour'] ?? 50, 0) }}€)
                                                    </button>
                                                </div>
                                            </div>
                                            @if(!empty($quote_services['photobooth']['selected']))
                                                <div class="mt-2 pt-2 border-t border-indigo-100 space-y-1.5">
                                                    @php
                                                        $pb3h = (float)($quote_services['photobooth']['price_3h'] ?? 430);
                                                        $pbDiff = max(0, $pb3h - (float)($quote_services['photobooth']['price'] ?? 380));
                                                        $pbExtraRate = (float)($quote_services['photobooth']['extra_hour'] ?? 50);
                                                    @endphp
                                                    @if((int)($quote_services['photobooth']['hours'] ?? 2) === 2)
                                                        <div class="bg-amber-50 border border-amber-200 rounded-lg px-2.5 py-1.5 text-[10px] font-bold text-amber-900 flex items-center gap-1.5">
                                                            <span>✨</span>
                                                            <span>En el Dossier PDF se mostrará la opción de elegir <strong>3 Horas por {{ number_format($pb3h, 0) }} €</strong> (+{{ number_format($pbDiff, 0) }} €) y la posibilidad de añadir <strong>horas extra (+{{ number_format($pbExtraRate, 0) }} €/h)</strong>.</span>
                                                        </div>
                                                    @else
                                                        <div class="bg-amber-50 border border-amber-200 rounded-lg px-2.5 py-1.5 text-[10px] font-bold text-amber-900 flex items-center gap-1.5">
                                                            <span>✨</span>
                                                            <span>En el Dossier PDF se indicará que tienen la opción de ampliar con <strong>horas extra adicionales (+{{ number_format($pbExtraRate, 0) }} €/hora extra)</strong> por si el baile es largo.</span>
                                                        </div>
                                                    @endif
                                                    <label class="block text-[10px] font-bold text-gray-600 mb-1">Descripción incluida en PDF (editable):</label>
                                                    <textarea rows="2" wire:key="q-in-pb-dsc-{{ $activeProv }}" wire:model.live.debounce.400ms="quote_services.photobooth.desc" class="w-full text-[11px] border-gray-200 rounded-lg p-1.5 bg-white text-gray-700 focus:ring-indigo-500"></textarea>
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    @if(!empty($currentProvData['video_360_enabled']) || !empty($quote_services['video_360']['selected']))
                                        <!-- Plataforma Vídeo 360º por horas -->
                                        <div wire:key="quote-card-360-{{ $activeProv }}" class="p-3 rounded-xl border transition {{ !empty($quote_services['video_360']['selected']) ? 'border-purple-400 bg-purple-50/40' : 'border-gray-200 hover:bg-gray-50' }}">
                                            <div class="flex flex-col gap-2">
                                                <div class="flex items-start justify-between gap-3">
                                                    <label class="flex items-start cursor-pointer flex-1 min-w-0 gap-2">
                                                        <input type="checkbox" wire:key="q-in-360-sel-{{ $activeProv }}" wire:model.live="quote_services.video_360.selected" class="mt-0.5 rounded text-purple-600 focus:ring-purple-500 h-4 w-4 shrink-0">
                                                        <div class="min-w-0">
                                                            <span class="text-xs font-bold text-gray-900">🎥 Plataforma Vídeo 360º</span>
                                                            <span class="block text-[10px] text-gray-500">Plataforma 100cm (4-5 pers.), vídeos slow motion/boomerang ilimitados, confeti, atrezzo y animadores (+{{ number_format($quote_services['video_360']['extra_hour'] ?? 60, 0) }}€/hora extra).</span>
                                                        </div>
                                                    </label>

                                                    <div class="flex items-center gap-1 shrink-0">
                                                        <input type="number" min="1" max="10" wire:key="q-in-360-hrs-{{ $activeProv }}" wire:model.live="quote_services.video_360.hours" class="w-11 border rounded p-1 text-xs text-center font-bold" title="Horas de Plataforma 360">
                                                        <span class="text-[10px] text-gray-500 font-bold">h</span>
                                                        <input type="number" step="0.01" wire:key="q-in-360-prc-{{ $activeProv }}-{{ $quote_services['video_360']['hours'] ?? 2 }}" wire:model.live.debounce.400ms="quote_services.video_360.price" class="w-16 border rounded p-1 text-xs text-right font-bold text-purple-700">
                                                        <span class="text-xs text-gray-600">€</span>
                                                    </div>
                                                </div>

                                                <div class="flex flex-wrap items-center gap-1.5 pl-6">
                                                    <button type="button" wire:key="q-btn-3602-{{ $activeProv }}" wire:click="setVideo360Hours(2)" class="px-2.5 py-1 rounded-lg text-[10px] font-bold border transition {{ !empty($quote_services['video_360']['selected']) && (int)($quote_services['video_360']['hours'] ?? 2) === 2 ? 'bg-purple-600 text-white border-purple-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-purple-50' }}">
                                                        2 Horas ({{ number_format($quote_services['video_360']['price_2h'] ?? 430, 0) }}€)
                                                    </button>
                                                    <button type="button" wire:key="q-btn-3603-{{ $activeProv }}" wire:click="setVideo360Hours(3)" class="px-2.5 py-1 rounded-lg text-[10px] font-bold border transition {{ !empty($quote_services['video_360']['selected']) && (int)($quote_services['video_360']['hours'] ?? 2) === 3 ? 'bg-purple-600 text-white border-purple-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-purple-50' }}">
                                                        3 Horas ({{ number_format($quote_services['video_360']['price_3h'] ?? 490, 0) }}€)
                                                    </button>
                                                    <button type="button" wire:key="q-btn-360ext-{{ $activeProv }}" wire:click="setVideo360Hours({{ max(4, (int)($quote_services['video_360']['hours'] ?? 3) + 1) }})" class="px-2 py-1 rounded-lg text-[10px] font-bold border transition {{ !empty($quote_services['video_360']['selected']) && (int)($quote_services['video_360']['hours'] ?? 2) >= 4 ? 'bg-amber-600 text-white border-amber-600' : 'bg-amber-50 text-amber-800 border-amber-300 hover:bg-amber-100' }}" title="Añadir 1 hora extra de Plataforma 360º">
                                                        +1h Extra (+{{ number_format($quote_services['video_360']['extra_hour'] ?? 60, 0) }}€)
                                                    </button>
                                                </div>
                                            </div>
                                            @if(!empty($quote_services['video_360']['selected']))
                                                <div class="mt-2 pt-2 border-t border-purple-100 space-y-1.5">
                                                    @php
                                                        $v360_3h = (float)($quote_services['video_360']['price_3h'] ?? 490);
                                                        $v360Diff = max(0, $v360_3h - (float)($quote_services['video_360']['price'] ?? 430));
                                                        $v360ExtraRate = (float)($quote_services['video_360']['extra_hour'] ?? 60);
                                                    @endphp
                                                    @if((int)($quote_services['video_360']['hours'] ?? 2) === 2)
                                                        <div class="bg-amber-50 border border-amber-200 rounded-lg px-2.5 py-1.5 text-[10px] font-bold text-amber-900 flex items-center gap-1.5">
                                                            <span>✨</span>
                                                            <span>En el Dossier PDF se mostrará la opción de elegir <strong>3 Horas por {{ number_format($v360_3h, 0) }} €</strong> (+{{ number_format($v360Diff, 0) }} €) y la posibilidad de añadir <strong>horas extra (+{{ number_format($v360ExtraRate, 0) }} €/h)</strong>.</span>
                                                        </div>
                                                    @else
                                                        <div class="bg-amber-50 border border-amber-200 rounded-lg px-2.5 py-1.5 text-[10px] font-bold text-amber-900 flex items-center gap-1.5">
                                                            <span>✨</span>
                                                            <span>En el Dossier PDF se indicará que tienen la opción de ampliar con <strong>horas extra adicionales (+{{ number_format($v360ExtraRate, 0) }} €/hora extra)</strong>.</span>
                                                        </div>
                                                    @endif
                                                    <label class="block text-[10px] font-bold text-gray-600 mb-1">Descripción incluida en PDF (editable):</label>
                                                    <textarea rows="2" wire:key="q-in-360-dsc-{{ $activeProv }}" wire:model.live.debounce.400ms="quote_services.video_360.desc" class="w-full text-[11px] border-gray-200 rounded-lg p-1.5 bg-white text-gray-700 focus:ring-purple-500"></textarea>
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    @if(!empty($currentProvData['chocolate_fountain_enabled']) || !empty($quote_services['chocolate_fountain']['selected']))
                                        <!-- Mesa Dulce con Fuente de Chocolate (1 o 2 fuentes · 2h o 3h) -->
                                        <div wire:key="quote-card-choc-{{ $activeProv }}" class="p-3 rounded-xl border transition {{ !empty($quote_services['chocolate_fountain']['selected']) ? 'border-amber-400 bg-amber-50/40' : 'border-gray-200 hover:bg-gray-50' }}">
                                            <div class="flex flex-col gap-2">
                                                <div class="flex items-start justify-between gap-3">
                                                    <label class="flex items-start cursor-pointer flex-1 min-w-0 gap-2">
                                                        <input type="checkbox" wire:key="q-in-choc-sel-{{ $activeProv }}" wire:model.live="quote_services.chocolate_fountain.selected" class="mt-0.5 rounded text-amber-600 focus:ring-amber-500 h-4 w-4 shrink-0">
                                                        <div class="min-w-0">
                                                            <span class="text-xs font-bold text-gray-900">🍫 Mesa Dulce con Fuente de Chocolate</span>
                                                            <span class="block text-[10px] text-gray-500">Chocolate blanco/negro/con leche, fruta variada, donuts, gofres cortados, profiteroles, galletas, chocolatinas, chuches y arco de globos (+rincón gofres opcional a {{ number_format($quote_services['chocolate_fountain']['waffle_extra_unit'] ?? 2.90, 2, ',', '.') }}€/ud).</span>
                                                        </div>
                                                    </label>

                                                    <div class="flex items-center gap-1.5 shrink-0">
                                                        <label class="flex items-center gap-1 cursor-pointer text-[10px] font-bold text-amber-800 bg-amber-50 hover:bg-amber-100 px-2 py-1 rounded-lg border border-amber-200">
                                                            <input type="checkbox" wire:key="q-in-choc-con-{{ $activeProv }}" wire:model.live="quote_services.chocolate_fountain.consult" class="rounded text-amber-600 focus:ring-amber-500 h-3.5 w-3.5">
                                                            <span>⏳ A consultar</span>
                                                        </label>
                                                        @if(empty($quote_services['chocolate_fountain']['consult']))
                                                            <div class="flex items-center gap-1">
                                                                <input type="number" step="0.01" wire:key="q-in-choc-prc-{{ $activeProv }}-{{ $quote_services['chocolate_fountain']['fountains'] ?? 1 }}-{{ $quote_services['chocolate_fountain']['hours'] ?? 2 }}" wire:model.live.debounce.400ms="quote_services.chocolate_fountain.price" class="w-16 border rounded p-1 text-xs text-right font-bold text-amber-900">
                                                                <span class="text-xs text-gray-600">€</span>
                                                            </div>
                                                        @else
                                                            <span class="text-[11px] font-bold text-amber-700 bg-amber-100 px-2 py-1 rounded">Precio a consultar</span>
                                                        @endif
                                                    </div>
                                                </div>

                                                @php
                                                    $cfFountains = (int)($quote_services['chocolate_fountain']['fountains'] ?? 1);
                                                    $cfHours = (int)($quote_services['chocolate_fountain']['hours'] ?? 2);
                                                    $cfSel = !empty($quote_services['chocolate_fountain']['selected']) && empty($quote_services['chocolate_fountain']['consult']);
                                                @endphp
                                                <div class="flex flex-wrap items-center gap-1.5 pl-6">
                                                    <button type="button" wire:key="q-btn-choc12-{{ $activeProv }}" wire:click="setChocolateFountainOption(1, 2)" class="px-2.5 py-1 rounded-lg text-[10px] font-bold border transition {{ $cfSel && $cfFountains === 1 && $cfHours === 2 ? 'bg-amber-600 text-white border-amber-600 shadow-xs' : 'bg-white text-gray-700 border-gray-300 hover:bg-amber-50' }}">
                                                        1 Fuente · 2h ({{ number_format($quote_services['chocolate_fountain']['price_1_2h'] ?? 420, 0) }}€)
                                                    </button>
                                                    <button type="button" wire:key="q-btn-choc13-{{ $activeProv }}" wire:click="setChocolateFountainOption(1, 3)" class="px-2.5 py-1 rounded-lg text-[10px] font-bold border transition {{ $cfSel && $cfFountains === 1 && $cfHours === 3 ? 'bg-amber-600 text-white border-amber-600 shadow-xs' : 'bg-white text-gray-700 border-gray-300 hover:bg-amber-50' }}">
                                                        1 Fuente · 3h ({{ number_format($quote_services['chocolate_fountain']['price_1_3h'] ?? 520, 0) }}€)
                                                    </button>
                                                    <button type="button" wire:key="q-btn-choc22-{{ $activeProv }}" wire:click="setChocolateFountainOption(2, 2)" class="px-2.5 py-1 rounded-lg text-[10px] font-bold border transition {{ $cfSel && $cfFountains === 2 && $cfHours === 2 ? 'bg-amber-700 text-white border-amber-700 shadow-xs' : 'bg-white text-gray-700 border-gray-300 hover:bg-amber-50' }}">
                                                        2 Fuentes · 2h ({{ number_format($quote_services['chocolate_fountain']['price_2_2h'] ?? 490, 0) }}€)
                                                    </button>
                                                    <button type="button" wire:key="q-btn-choc23-{{ $activeProv }}" wire:click="setChocolateFountainOption(2, 3)" class="px-2.5 py-1 rounded-lg text-[10px] font-bold border transition {{ $cfSel && $cfFountains === 2 && $cfHours === 3 ? 'bg-amber-700 text-white border-amber-700 shadow-xs' : 'bg-white text-gray-700 border-gray-300 hover:bg-amber-50' }}">
                                                        2 Fuentes · 3h ({{ number_format($quote_services['chocolate_fountain']['price_2_3h'] ?? 590, 0) }}€)
                                                    </button>
                                                </div>
                                            </div>
                                            @if(!empty($quote_services['chocolate_fountain']['selected']))
                                                <div class="mt-2 pt-2 border-t border-amber-100">
                                                    <label class="block text-[10px] font-bold text-gray-600 mb-1">Descripción incluida en PDF (editable):</label>
                                                    <textarea rows="2" wire:key="q-in-choc-dsc-{{ $activeProv }}" wire:model.live.debounce.400ms="quote_services.chocolate_fountain.desc" class="w-full text-[11px] border-gray-200 rounded-lg p-1.5 bg-white text-gray-700 focus:ring-amber-500"></textarea>
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    @if(!empty($currentProvData['candy_bar_enabled']) || !empty($quote_services['candy_bar']['selected']))
                                        <!-- Mesa de Solo Chuches -->
                                        <div wire:key="quote-card-candy-{{ $activeProv }}" class="p-3 rounded-xl border transition {{ !empty($quote_services['candy_bar']['selected']) ? 'border-pink-400 bg-pink-50/40' : 'border-gray-200 hover:bg-gray-50' }}">
                                            <div class="flex items-start justify-between gap-3">
                                                <label class="flex items-start cursor-pointer flex-1 min-w-0 gap-2">
                                                    <input type="checkbox" wire:key="q-in-candy-sel-{{ $activeProv }}" wire:model.live="quote_services.candy_bar.selected" class="mt-0.5 rounded text-pink-600 focus:ring-pink-500 h-4 w-4 shrink-0">
                                                    <div class="min-w-0">
                                                        <span class="text-xs font-bold text-gray-900">🍬 Mesa de Solo Chuches (Candy Bar)</span>
                                                        <span class="block text-[10px] text-gray-500">Gran variado de chuches (favoritas del anfitrión), decoración a elegir y globos decorativos. Montaje 2h antes y recogida a la noche o al día siguiente.</span>
                                                    </div>
                                                </label>

                                                <div class="flex items-center gap-1.5 shrink-0">
                                                    <label class="flex items-center gap-1 cursor-pointer text-[10px] font-bold text-pink-800 bg-pink-50 hover:bg-pink-100 px-2 py-1 rounded-lg border border-pink-200">
                                                        <input type="checkbox" wire:key="q-in-candy-con-{{ $activeProv }}" wire:model.live="quote_services.candy_bar.consult" class="rounded text-pink-600 focus:ring-pink-500 h-3.5 w-3.5">
                                                        <span>⏳ A consultar</span>
                                                    </label>
                                                    @if(empty($quote_services['candy_bar']['consult']))
                                                        <div class="flex items-center gap-1">
                                                            <input type="number" step="0.01" wire:key="q-in-candy-prc-{{ $activeProv }}" wire:model.live.debounce.400ms="quote_services.candy_bar.price" class="w-16 border rounded p-1 text-xs text-right font-bold text-pink-800">
                                                            <span class="text-xs text-gray-600">€</span>
                                                        </div>
                                                    @else
                                                        <span class="text-[11px] font-bold text-pink-700 bg-pink-100 px-2 py-1 rounded">Precio a consultar</span>
                                                    @endif
                                                </div>
                                            </div>
                                            @if(!empty($quote_services['candy_bar']['selected']))
                                                <div class="mt-2 pt-2 border-t border-pink-100">
                                                    <label class="block text-[10px] font-bold text-gray-600 mb-1">Descripción incluida en PDF (editable):</label>
                                                    <textarea rows="2" wire:key="q-in-candy-dsc-{{ $activeProv }}" wire:model.live.debounce.400ms="quote_services.candy_bar.desc" class="w-full text-[11px] border-gray-200 rounded-lg p-1.5 bg-white text-gray-700 focus:ring-pink-500"></textarea>
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    @if(!empty($currentProvData['waffle_cart_enabled']) || !empty($quote_services['waffle_cart']['selected']))
                                        <!-- Carrito de Gofres hechos al momento -->
                                        <div wire:key="quote-card-waffle-{{ $activeProv }}" class="p-3 rounded-xl border transition {{ !empty($quote_services['waffle_cart']['selected']) ? 'border-orange-400 bg-orange-50/40' : 'border-gray-200 hover:bg-gray-50' }}">
                                            <div class="flex flex-col gap-2">
                                                <div class="flex items-start justify-between gap-3">
                                                    <label class="flex items-start cursor-pointer flex-1 min-w-0 gap-2">
                                                        <input type="checkbox" wire:key="q-in-waffle-sel-{{ $activeProv }}" wire:model.live="quote_services.waffle_cart.selected" class="mt-0.5 rounded text-orange-600 focus:ring-orange-500 h-4 w-4 shrink-0">
                                                        <div class="min-w-0">
                                                            <span class="text-xs font-bold text-gray-900">🧇 Carrito de Gofres hechos al momento</span>
                                                            <span class="block text-[10px] text-gray-500">Gofres recién hechos + 4 siropes (Kinder, Lotus, Nutella, pistacho...) + 4 toppings secos (Lotus, Oreo, Lacasitos, nubes) + globos y banderín con nombre.</span>
                                                        </div>
                                                    </label>

                                                    <div class="flex items-center gap-1.5 shrink-0">
                                                        <label class="flex items-center gap-1 cursor-pointer text-[10px] font-bold text-orange-800 bg-orange-50 hover:bg-orange-100 px-2 py-1 rounded-lg border border-orange-200">
                                                            <input type="checkbox" wire:key="q-in-waffle-con-{{ $activeProv }}" wire:model.live="quote_services.waffle_cart.consult" class="rounded text-orange-600 focus:ring-orange-500 h-3.5 w-3.5">
                                                            <span>⏳ A consultar</span>
                                                        </label>
                                                        @if(empty($quote_services['waffle_cart']['consult']))
                                                            <div class="flex items-center gap-1">
                                                                <input type="number" step="0.01" wire:key="q-in-waffle-prc-{{ $activeProv }}-{{ $quote_services['waffle_cart']['hours'] ?? 2 }}" wire:model.live.debounce.400ms="quote_services.waffle_cart.price" class="w-16 border rounded p-1 text-xs text-right font-bold text-orange-800">
                                                                <span class="text-xs text-gray-600">€</span>
                                                            </div>
                                                        @else
                                                            <span class="text-[11px] font-bold text-orange-700 bg-orange-100 px-2 py-1 rounded">Precio a consultar</span>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="flex flex-wrap items-center gap-1.5 pl-6">
                                                    <button type="button" wire:key="q-btn-waffle2-{{ $activeProv }}" wire:click="setWaffleCartHours(2)" class="px-2.5 py-1 rounded-lg text-[10px] font-bold border transition {{ !empty($quote_services['waffle_cart']['selected']) && empty($quote_services['waffle_cart']['consult']) && (int)($quote_services['waffle_cart']['hours'] ?? 2) === 2 ? 'bg-orange-600 text-white border-orange-600 shadow-xs' : 'bg-white text-gray-700 border-gray-300 hover:bg-orange-50' }}">
                                                        2h · ~60 gofres ({{ number_format($quote_services['waffle_cart']['price_2h'] ?? 420, 0) }}€)
                                                    </button>
                                                    <button type="button" wire:key="q-btn-waffle3-{{ $activeProv }}" wire:click="setWaffleCartHours(3)" class="px-2.5 py-1 rounded-lg text-[10px] font-bold border transition {{ !empty($quote_services['waffle_cart']['selected']) && empty($quote_services['waffle_cart']['consult']) && (int)($quote_services['waffle_cart']['hours'] ?? 2) === 3 ? 'bg-orange-600 text-white border-orange-600 shadow-xs' : 'bg-white text-gray-700 border-gray-300 hover:bg-orange-50' }}">
                                                        3h · ~100 gofres ({{ number_format($quote_services['waffle_cart']['price_3h'] ?? 550, 0) }}€)
                                                    </button>
                                                </div>
                                            </div>
                                            @if(!empty($quote_services['waffle_cart']['selected']))
                                                <div class="mt-2 pt-2 border-t border-orange-100">
                                                    <label class="block text-[10px] font-bold text-gray-600 mb-1">Descripción incluida en PDF (editable):</label>
                                                    <textarea rows="2" wire:key="q-in-waffle-dsc-{{ $activeProv }}" wire:model.live.debounce.400ms="quote_services.waffle_cart.desc" class="w-full text-[11px] border-gray-200 rounded-lg p-1.5 bg-white text-gray-700 focus:ring-orange-500"></textarea>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                </div>
                            </div>

                            <!-- SECCIÓN DESPLEGABLE: EFECTOS ESPECIALES & SHOW -->
                            <div x-data="{ openFx: {{ (!empty($quote_services['smoke_machine']['selected']) || !empty($quote_services['cold_sparks']['selected'])) ? 'true' : 'false' }} }" class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
                                <button type="button" @click="openFx = !openFx" class="w-full flex items-center justify-between p-3 text-left hover:bg-gray-50 transition">
                                    <h6 class="text-xs font-black uppercase text-indigo-900 tracking-wider flex items-center gap-1.5">
                                        <span>✨</span> Efectos Especiales & Ambientación (Humo, Fuego Frío)
                                        @if(!empty($quote_services['smoke_machine']['included']) || !empty($quote_services['cold_sparks']['included']))
                                            <span class="text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 px-2 py-0.5 rounded-full normal-case">
                                                🎁 Incluidos en Pack DJ
                                            </span>
                                        @endif
                                    </h6>
                                    <span class="text-[11px] font-bold text-indigo-700 bg-indigo-50 px-2.5 py-0.5 rounded-full border border-indigo-200" x-text="openFx ? '🔼 Plegar' : '🔽 Desplegar'"></span>
                                </button>

                                <div x-show="openFx" class="p-3.5 pt-0 border-t border-gray-100 space-y-2">
                                    <!-- Máquina de Humo -->
                                    <div class="flex items-center justify-between p-2.5 rounded-lg border {{ !empty($quote_services['smoke_machine']['included']) ? 'bg-emerald-50/50 border-emerald-300' : ($quote_services['smoke_machine']['selected'] ? 'bg-indigo-50/50 border-indigo-300' : 'border-gray-100 hover:bg-gray-50') }} gap-2">
                                        <label class="flex items-center cursor-pointer flex-1 gap-2">
                                            <input type="checkbox" wire:model.live="quote_services.smoke_machine.selected" class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-bold text-gray-800">💨 Máquina de Humo</span>
                                                    @if(!empty($quote_services['smoke_machine']['included']))
                                                        <span class="text-[9px] font-bold bg-emerald-600 text-white px-1.5 py-0.2 rounded uppercase">✅ Incluido en Pack (0 €)</span>
                                                    @endif
                                                </div>
                                                <span class="block text-[10px] text-gray-500">{{ $quote_services['smoke_machine']['desc'] }}</span>
                                            </div>
                                        </label>
                                        <div class="flex items-center gap-1">
                                            @if(!empty($quote_services['smoke_machine']['included']))
                                                <span class="text-xs font-black text-emerald-700 bg-emerald-100 px-2 py-1 rounded">0,00 €</span>
                                            @else
                                                <input type="number" step="0.01" wire:model.live="quote_services.smoke_machine.price" class="w-16 border rounded p-1 text-xs text-right font-bold">
                                                <span class="text-xs text-gray-600">€</span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Fuego Frío / Chispas Frías -->
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between p-2.5 rounded-lg border {{ !empty($quote_services['cold_sparks']['included']) ? 'bg-amber-50/50 border-amber-300' : ($quote_services['cold_sparks']['selected'] ? 'bg-indigo-50/50 border-indigo-300' : 'border-gray-100 hover:bg-gray-50') }} gap-2">
                                        <label class="flex items-center cursor-pointer flex-1 gap-2">
                                            <input type="checkbox" wire:model.live="quote_services.cold_sparks.selected" class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-bold text-gray-800">✨ Fuego Frío / Chispas Frías (Cold Sparks)</span>
                                                    @if(!empty($quote_services['cold_sparks']['included']))
                                                        <span class="text-[9px] font-bold bg-amber-600 text-white px-1.5 py-0.2 rounded uppercase">✅ 2 Máq. en Pack (0 €)</span>
                                                    @endif
                                                </div>
                                                <span class="block text-[10px] text-gray-500">{{ $quote_services['cold_sparks']['desc'] }}</span>
                                            </div>
                                        </label>
                                        <div class="flex items-center justify-end gap-2 shrink-0">
                                            <div class="flex items-center gap-1">
                                                <input type="number" min="1" max="20" step="1" wire:model.live="quote_services.cold_sparks.quantity" class="w-12 border rounded p-1 text-xs text-center font-bold" title="Número de máquinas">
                                                <span class="text-[11px] text-gray-600 font-medium">máq. @</span>
                                                <input type="number" step="0.01" wire:model.live="quote_services.cold_sparks.price" class="w-16 border rounded p-1 text-xs text-right font-bold" title="Precio por máquina">
                                                <span class="text-xs text-gray-600">€/ud</span>
                                            </div>
                                            <div class="text-right min-w-[70px]">
                                                @php
                                                    $sparksQty = (int)($quote_services['cold_sparks']['quantity'] ?: 1);
                                                    $sparksUnit = (float)($quote_services['cold_sparks']['price'] ?: 0);
                                                    $sparksIncluded = !empty($quote_services['cold_sparks']['included']);
                                                    $sparksTotal = $sparksIncluded ? (max(0, $sparksQty - 2) * $sparksUnit) : ($sparksQty * $sparksUnit);
                                                @endphp
                                                @if($sparksIncluded && $sparksQty <= 2)
                                                    <span class="text-xs font-black text-amber-700 bg-amber-100 px-2 py-0.5 rounded">0,00 €</span>
                                                @elseif($sparksIncluded && $sparksQty > 2)
                                                    <span class="text-xs font-black text-amber-800 bg-amber-100 px-1.5 py-0.5 rounded" title="2 en pack (0 €) + {{ $sparksQty - 2 }} extra">+{{ number_format($sparksTotal, 2) }} €</span>
                                                @else
                                                    <span class="text-xs font-bold text-gray-700">{{ number_format($sparksTotal, 2) }} €</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN DESPLEGABLE: FOTOGRAFÍA -->
                            <div x-data="{ openPhoto: {{ (!empty($quote_services['photo_full_pack']['selected']) || !empty($quote_services['photo_ceremony']['selected']) || !empty($quote_services['photo_restaurant']['selected']) || !empty($quote_services['photo_party']['selected']) || !empty($quote_services['photo_album']['selected'])) ? 'true' : 'false' }} }" class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
                                <button type="button" @click="openPhoto = !openPhoto" class="w-full flex items-center justify-between p-3 text-left hover:bg-gray-50 transition">
                                    <h6 class="text-xs font-black uppercase text-purple-900 tracking-wider flex items-center gap-1.5">
                                        <span>📸</span> Servicios de Fotografía & Álbum
                                    </h6>
                                    <span class="text-[11px] font-bold text-purple-700 bg-purple-50 px-2.5 py-0.5 rounded-full border border-purple-200" x-text="openPhoto ? '🔼 Plegar' : '🔽 Desplegar'"></span>
                                </button>

                                <div x-show="openPhoto" class="p-3.5 pt-0 border-t border-gray-100 space-y-2">
                                    <!-- Pack Completo Fotografía -->
                                    <label class="flex items-start justify-between p-2.5 rounded-lg border hover:bg-gray-50 cursor-pointer transition {{ $quote_services['photo_full_pack']['selected'] ? 'border-purple-500 bg-purple-50/50' : 'border-purple-200 bg-purple-50/20' }}">
                                        <div class="flex items-start gap-2.5">
                                            <input type="checkbox" wire:model.live="quote_services.photo_full_pack.selected" class="mt-0.5 rounded text-purple-600 focus:ring-purple-500 h-4 w-4">
                                            <div>
                                                <span class="font-bold text-xs text-purple-950">Pack Completo Fotografía ⭐</span>
                                                <p class="text-[11px] text-purple-800">{{ $quote_services['photo_full_pack']['desc'] }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <input type="number" step="0.01" wire:model.live="quote_services.photo_full_pack.price" class="w-18 border rounded p-1 text-xs text-right font-bold focus:ring-purple-500">
                                            <span class="text-xs font-bold text-purple-900">€</span>
                                        </div>
                                    </label>

                                    <!-- Desglose individual de fotografía -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                                        <label class="flex items-center justify-between p-2 rounded-lg border border-gray-100 hover:bg-gray-50 cursor-pointer">
                                            <div class="flex items-center gap-2">
                                                <input type="checkbox" wire:model.live="quote_services.photo_ceremony.selected" class="rounded text-purple-600 focus:ring-purple-500 h-4 w-4">
                                                <span class="text-xs font-semibold text-gray-800">Foto Ceremonia</span>
                                            </div>
                                            <div class="flex items-center gap-0.5">
                                                <input type="number" step="0.01" wire:model.live="quote_services.photo_ceremony.price" class="w-14 border rounded p-0.5 text-xs text-right font-bold">
                                                <span class="text-xs text-gray-500">€</span>
                                            </div>
                                        </label>

                                        <label class="flex items-center justify-between p-2 rounded-lg border border-gray-100 hover:bg-gray-50 cursor-pointer">
                                            <div class="flex items-center gap-2">
                                                <input type="checkbox" wire:model.live="quote_services.photo_restaurant.selected" class="rounded text-purple-600 focus:ring-purple-500 h-4 w-4">
                                                <span class="text-xs font-semibold text-gray-800">Foto Banquete</span>
                                            </div>
                                            <div class="flex items-center gap-0.5">
                                                <input type="number" step="0.01" wire:model.live="quote_services.photo_restaurant.price" class="w-14 border rounded p-0.5 text-xs text-right font-bold">
                                                <span class="text-xs text-gray-500">€</span>
                                            </div>
                                        </label>

                                        <label class="flex items-center justify-between p-2 rounded-lg border border-gray-100 hover:bg-gray-50 cursor-pointer">
                                            <div class="flex items-center gap-2">
                                                <input type="checkbox" wire:model.live="quote_services.photo_party.selected" class="rounded text-purple-600 focus:ring-purple-500 h-4 w-4">
                                                <span class="text-xs font-semibold text-gray-800">Foto Fiesta / Baile</span>
                                            </div>
                                            <div class="flex items-center gap-0.5">
                                                <input type="number" step="0.01" wire:model.live="quote_services.photo_party.price" class="w-14 border rounded p-0.5 text-xs text-right font-bold">
                                                <span class="text-xs text-gray-500">€</span>
                                            </div>
                                        </label>

                                        <label class="flex items-center justify-between p-2 rounded-lg border border-gray-100 hover:bg-gray-50 cursor-pointer">
                                            <div class="flex items-center gap-2">
                                                <input type="checkbox" wire:model.live="quote_services.photo_album.selected" class="rounded text-purple-600 focus:ring-purple-500 h-4 w-4">
                                                <span class="text-xs font-semibold text-gray-800">Maquetación Álbum</span>
                                            </div>
                                            <div class="flex items-center gap-0.5">
                                                <input type="number" step="0.01" wire:model.live="quote_services.photo_album.price" class="w-14 border rounded p-0.5 text-xs text-right font-bold">
                                                <span class="text-xs text-gray-500">€</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN DESPLEGABLE: EXTRAS PERSONALIZADOS (MÚLTIPLES LÍNEAS) -->
                            <div x-data="{ openExtras: {{ (!empty($custom_extras[0]['name'])) ? 'true' : 'false' }} }" class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
                                <button type="button" @click="openExtras = !openExtras" class="w-full flex items-center justify-between p-3 text-left hover:bg-gray-50 transition">
                                    <h6 class="text-xs font-black uppercase text-gray-700 tracking-wider flex items-center gap-1.5">
                                        <span>➕</span> Extras Personalizados / Conceptos Libres Adicionales
                                    </h6>
                                    <span class="text-[11px] font-bold text-gray-700 bg-gray-100 px-2.5 py-0.5 rounded-full border border-gray-200" x-text="openExtras ? '🔼 Plegar' : '🔽 Desplegar'"></span>
                                </button>

                                <div x-show="openExtras" class="p-3.5 pt-2 border-t border-gray-100 space-y-3">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-[11px] text-gray-500">Añade tantas líneas libres como necesites (con importe o "Precio a consultar").</p>
                                        <button type="button" wire:click="addCustomExtra" class="inline-flex items-center gap-1 text-xs font-bold text-indigo-700 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 px-2.5 py-1 rounded-lg transition shadow-xs">
                                            <span>➕</span> Añadir línea
                                        </button>
                                    </div>

                                    <div class="space-y-2.5">
                                        @foreach($custom_extras as $index => $extra)
                                            <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200 space-y-2 relative" wire:key="custom-extra-{{ $index }}">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="text-[11px] font-extrabold uppercase text-slate-600 flex items-center gap-1">
                                                        <span>🔹</span> Extra #{{ $index + 1 }}
                                                    </span>
                                                    
                                                    <div class="flex items-center gap-2">
                                                        <label class="flex items-center gap-1.5 cursor-pointer text-[11px] font-bold text-amber-800 bg-amber-50 hover:bg-amber-100 px-2 py-0.5 rounded-lg border border-amber-200 transition">
                                                            <input type="checkbox" wire:model.live="custom_extras.{{ $index }}.consult" class="rounded text-amber-600 focus:ring-amber-500 h-3.5 w-3.5">
                                                            <span>Precio a consultar</span>
                                                        </label>
                                                        @if(count($custom_extras) > 1)
                                                            <button type="button" wire:click="removeCustomExtra({{ $index }})" class="text-rose-500 hover:text-rose-700 p-1 hover:bg-rose-50 rounded-lg transition" title="Eliminar este extra">
                                                                🗑️
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                                    <input type="text" wire:model.live="custom_extras.{{ $index }}.name" placeholder="Ej: Fotomatón / Fuego frío / Plataforma 360..." class="flex-1 border-gray-300 rounded-lg p-1.5 text-xs focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                                    
                                                    @if(empty($extra['consult']))
                                                        <div class="flex items-center gap-1">
                                                            <input type="number" step="0.01" wire:model.live="custom_extras.{{ $index }}.price" placeholder="0.00" class="w-24 border-gray-300 rounded-lg p-1.5 text-xs text-right font-bold focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                                            <span class="text-xs font-bold text-gray-600">€</span>
                                                        </div>
                                                    @else
                                                        <span class="text-xs font-bold text-amber-800 bg-amber-100/90 px-2.5 py-1.5 rounded-lg border border-amber-300 text-center whitespace-nowrap">
                                                            ⚠️ Precio a consultar
                                                        </span>
                                                    @endif
                                                </div>

                                                <input type="text" wire:model.live="custom_extras.{{ $index }}.desc" placeholder="Descripción / detalle opcional (ej: Servicio 3 horas con atrezzo, libro de firmas y fotos ilimitadas)" class="w-full border-gray-300 rounded-lg p-1.5 text-[11px] text-gray-600 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            @if($editing_quote_id)
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1">
                                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-black py-3 px-4 rounded-xl shadow-md transition flex items-center justify-center gap-2 text-xs">
                                        <span>💾</span> Actualizar Propuesta #{{ $editing_quote_id }}
                                    </button>
                                    <button type="button" wire:click="createQuote" class="bg-indigo-600 hover:bg-indigo-700 text-white font-black py-3 px-4 rounded-xl shadow-md transition flex items-center justify-center gap-2 text-xs">
                                        <span>➕</span> Guardar como Nueva Versión
                                    </button>
                                </div>
                            @else
                                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-3 px-4 rounded-xl shadow-md transition flex items-center justify-center gap-2 text-sm">
                                    <span>📄</span> Generar Propuesta Comercial en PDF
                                </button>
                            @endif
                        </form>
                    </div>

                    <!-- COLUMNA DERECHA: RESUMEN EN VIVO Y HISTORIAL DE PROPUESTAS (STICKY) -->
                    <div class="lg:col-span-5 lg:sticky lg:top-4 self-start space-y-4">
                        
                        <!-- TARJETA RESUMEN EN VIVO + BOTÓN DE GENERACIÓN DIRECTA -->
                        <div class="bg-slate-900 text-white p-4 rounded-2xl shadow-xl space-y-3 border border-slate-800">
                            <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                                <h5 class="font-black text-xs uppercase tracking-wider text-slate-300 flex items-center gap-1.5">
                                    <span>📊</span> Resumen Económico en Vivo
                                </h5>
                                <div class="flex items-center gap-1">
                                    <span class="text-[10px] bg-indigo-500/20 text-indigo-300 px-2 py-0.5 rounded-full font-bold border border-indigo-400/30">
                                        {{ $quote_tax_type === 'included' ? 'IVA Incluido' : ($quote_tax_type === 'excluded' ? '+21% IVA' : 'Sin IVA') }}
                                    </span>
                                    <span class="text-[10px] bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded-full font-bold border border-emerald-400/30">
                                        Señal {{ $quote_deposit_type === 'none' ? '0 €' : ($quote_deposit_type === 'percentage' ? ($quote_deposit_percentage . '%') : 'Fija') }}
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-2.5">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="space-y-0.5 text-xs text-slate-300">
                                        <div>
                                            <span class="text-slate-400">Base:</span>
                                            <span class="font-mono font-bold text-slate-200 ml-1">{{ number_format($this->calculated_subtotal, 2) }} €</span>
                                            @if($quote_tax_type !== 'none')
                                                <span class="text-slate-500 mx-1">·</span>
                                                <span class="text-slate-400">IVA ({{ $quote_tax_rate }}%):</span>
                                                <span class="font-mono font-bold text-slate-300 ml-1">{{ number_format($this->calculated_tax_amount, 2) }} €</span>
                                            @endif
                                        </div>
                                        <span class="block text-[10px] text-slate-400">
                                            {{ $quote_tax_type === 'included' ? 'Total con IVA 21% incluido' : ($quote_tax_type === 'excluded' ? 'Total Base + 21% IVA' : 'Total Exento de IVA') }}
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-2xl font-black text-emerald-400 leading-none">{{ number_format($this->calculated_total, 2) }} €</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2.5 pt-2 border-t border-slate-800">
                                    <div class="bg-slate-800/80 p-2.5 rounded-xl border border-slate-700 flex items-center justify-between">
                                        <div>
                                            <span class="block text-[9px] uppercase font-black text-emerald-400">
                                                Señal {{ $quote_deposit_type === 'none' ? '(0€)' : ($quote_deposit_type === 'percentage' ? '(' . $quote_deposit_percentage . '%)' : '(Fija)') }}
                                            </span>
                                            <span class="text-[9px] text-slate-400">A la firma</span>
                                        </div>
                                        <span class="text-sm font-black text-emerald-400">{{ number_format($this->calculated_signal, 2) }} €</span>
                                    </div>

                                    <div class="bg-slate-800/80 p-2.5 rounded-xl border border-slate-700 flex items-center justify-between">
                                        <div>
                                            <span class="block text-[9px] uppercase font-black text-amber-400">Restante</span>
                                            <span class="text-[9px] text-slate-400">Día evento</span>
                                        </div>
                                        <span class="text-sm font-black text-amber-300">{{ number_format($this->calculated_remaining, 2) }} €</span>
                                    </div>
                                </div>

                                <!-- Botón directo de generación/actualización sin bajar al final -->
                                <div class="pt-1">
                                    @if($editing_quote_id)
                                        <div class="grid grid-cols-2 gap-2">
                                            <button type="button" wire:click="updateQuote" class="bg-emerald-600 hover:bg-emerald-500 text-white font-black py-2.5 px-3 rounded-xl shadow-md transition flex items-center justify-center gap-1.5 text-xs cursor-pointer">
                                                <span>💾</span> Actualizar #{{ $editing_quote_id }}
                                            </button>
                                            <button type="button" wire:click="createQuote" class="bg-indigo-600 hover:bg-indigo-500 text-white font-black py-2.5 px-3 rounded-xl shadow-md transition flex items-center justify-center gap-1.5 text-xs cursor-pointer">
                                                <span>➕</span> Nueva Versión
                                            </button>
                                        </div>
                                    @else
                                        <button type="button" wire:click="updateQuote" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-black py-2.5 px-4 rounded-xl shadow-md transition flex items-center justify-center gap-2 text-xs cursor-pointer">
                                            <span>📄</span> Generar Propuesta Comercial en PDF
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- HISTORIAL DE PROPUESTAS GENERADAS (ORDENADAS POR LA MÁS RECIENTE) -->
                        <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs space-y-3">
                            <div class="border-b pb-2.5 flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <h5 class="font-bold text-gray-900 text-sm">📜 Historial de Propuestas</h5>
                                    <span class="text-xs bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded-full font-bold">{{ $event->quotes->count() }} emitidas</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="inline-flex items-center bg-slate-100 p-0.5 rounded-lg border border-slate-200 text-[10px]">
                                        <span class="px-1.5 font-bold text-slate-500">📱 Enviar desde:</span>
                                        <button type="button" wire:click="$set('whatsapp_sender_brand', 'miguel')" class="px-2 py-0.5 rounded-md font-bold transition cursor-pointer {{ $whatsapp_sender_brand === 'miguel' ? 'bg-amber-500 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">👑 Miguel</button>
                                        <button type="button" wire:click="$set('whatsapp_sender_brand', 'fran')" class="px-2 py-0.5 rounded-md font-bold transition cursor-pointer {{ $whatsapp_sender_brand === 'fran' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">🎧 Fran</button>
                                        <button type="button" wire:click="$set('whatsapp_sender_brand', 'ezequiel')" class="px-2 py-0.5 rounded-md font-bold transition cursor-pointer {{ $whatsapp_sender_brand === 'ezequiel' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">👥 Ezequiel</button>
                                    </div>
                                    <span class="text-[11px] text-gray-400 font-medium">Más reciente arriba</span>
                                </div>
                            </div>

                            <div class="space-y-3">
                                @php
                                    $sortedQuotes = $event->quotes->sortByDesc('id')->values();
                                    $totalQuotesCount = $sortedQuotes->count();
                                @endphp

                                @forelse($sortedQuotes as $index => $quote)
                                    @php
                                        $qSignal = $quote->signal_amount;
                                        $qRemaining = $quote->remaining_amount;
                                        $qLabel = $quote->signal_label;
                                        $isLatest = ($index === 0);
                                        $isEditingThis = ($editing_quote_id === $quote->id);
                                        $versionNumber = $totalQuotesCount - $index;
                                        $taxLabel = $quote->tax_label;
                                        $quoteExpiresNotice = $quote->expires_at ? "\n\n⏳ *Validez:* Oferta vigente hasta el " . $quote->expires_at->format('d/m/Y') . " (" . $quote->validity_days . " días naturales)." : "";
                                        $quoteOnlineUrl = $quote->public_url;
                                        $quoteClientName = \App\Services\WhatsAppTemplateService::cleanClientDisplayName($event->client ? $event->client->name : null, $event->is_wedding ? 'pareja' : '', $event);
                                        $quoteGreeting = $quoteClientName !== '' ? "¡Hola {$quoteClientName}!" : "¡Hola!";
                                        $quoteMsg = $quoteGreeting . " 👋\n\nTe compartimos la propuesta detallada para *" . $event->name . "* por un importe total de *" . number_format($quote->amount, 2, ',', '.') . " €* (" . $taxLabel . " - Señal de reserva: *" . number_format($qSignal, 2, ',', '.') . " €* - " . $qLabel . ")." . $quoteExpiresNotice . "\n\n👉 *Ver propuesta online, descargar Dossier en PDF y Aceptar o Rechazar:*\n" . $quoteOnlineUrl . "\n\nPodéis consultarlo con calma y avisarnos para cualquier duda o cambio. ¡Un saludo!";
                                        $qWaUrl = $clientPhone ? "https://wa.me/{$clientPhone}?text=" . rawurlencode($quoteMsg) : "https://api.whatsapp.com/send?text=" . rawurlencode($quoteMsg);
                                    @endphp
                                    <div class="p-4 border rounded-xl transition shadow-xs space-y-3 {{ $isEditingThis ? 'border-amber-400 bg-amber-50/40 ring-2 ring-amber-300' : ($quote->status === 'accepted' ? 'border-emerald-400 bg-emerald-50/30 ring-1 ring-emerald-300' : ($quote->status === 'rejected' ? 'border-rose-300 bg-rose-50/20' : ($isLatest ? 'border-emerald-300 bg-emerald-50/20' : 'border-gray-200 bg-gray-50/60 opacity-90'))) }}">
                                        
                                        <!-- Cabecera de la Propuesta -->
                                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2.5">
                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="text-lg font-black text-gray-900">{{ number_format($quote->amount, 2) }} €</span>
                                                    
                                                    @if($isEditingThis)
                                                        <span class="bg-amber-100 text-amber-900 border border-amber-300 text-[10px] font-black px-2 py-0.5 rounded-full uppercase">
                                                            ✏️ Editando
                                                        </span>
                                                    @endif

                                                    @if($quote->status === 'accepted')
                                                        <span class="bg-emerald-600 text-white text-[10px] font-black px-2.5 py-0.5 rounded-full uppercase flex items-center gap-1 shadow-2xs">
                                                            ✅ Aceptado por Cliente {{ $quote->responded_at ? '(' . $quote->responded_at->format('d/m H:i') . ')' : '' }}
                                                        </span>
                                                    @elseif($quote->status === 'rejected')
                                                        <span class="bg-rose-600 text-white text-[10px] font-black px-2.5 py-0.5 rounded-full uppercase flex items-center gap-1 shadow-2xs">
                                                            ❌ Rechazado por Cliente {{ $quote->responded_at ? '(' . $quote->responded_at->format('d/m H:i') . ')' : '' }}
                                                        </span>
                                                    @endif

                                                    @if($isLatest)
                                                        <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] font-black px-2 py-0.5 rounded-full uppercase flex items-center gap-1">
                                                            ⭐ Versión Actual (V{{ $versionNumber }})
                                                        </span>
                                                    @else
                                                        <span class="bg-gray-200 text-gray-700 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase">
                                                            🕒 Versión Anterior (V{{ $versionNumber }})
                                                        </span>
                                                    @endif

                                                    <span class="text-xs text-emerald-700 font-bold bg-emerald-100 px-1.5 py-0.5 rounded">
                                                        Señal: {{ number_format($qSignal, 2) }} €
                                                    </span>

                                                    <span class="text-[10px] bg-slate-100 text-slate-700 font-bold px-1.5 py-0.5 rounded">
                                                        {{ $taxLabel }}
                                                    </span>

                                                    @if($quote->is_expired)
                                                        <span class="text-[10px] bg-rose-100 text-rose-800 border border-rose-300 font-bold px-2 py-0.5 rounded-full flex items-center gap-1" title="Caducó el {{ $quote->expires_at_formatted }}">
                                                            ⚠️ Caducada ({{ $quote->expires_at ? $quote->expires_at->format('d/m/Y') : '' }})
                                                        </span>
                                                    @else
                                                        <span class="text-[10px] bg-amber-100 text-amber-800 border border-amber-300 font-bold px-2 py-0.5 rounded-full flex items-center gap-1" title="Válida hasta el {{ $quote->expires_at_formatted }}">
                                                            ⏳ Válida hasta {{ $quote->expires_at ? $quote->expires_at->format('d/m/Y') : '' }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <p class="text-[11px] text-gray-500 mt-1">
                                                    <strong>Propuesta #{{ $quote->id }}</strong> &bull; Emitida: {{ $quote->created_at->format('d/m/Y H:i') }} &bull; Validez: {{ $quote->expires_at ? $quote->expires_at->format('d/m/Y') : '' }} ({{ $quote->validity_days }} días) &bull; Señal: {{ $qLabel }}
                                                </p>

                                                @if($quote->status === 'rejected' && $quote->rejection_reason)
                                                    <div class="mt-2 p-2.5 rounded-lg bg-rose-100/80 border border-rose-300 text-xs text-rose-900">
                                                        <strong>📝 Motivo del rechazo indicado por el cliente:</strong> {{ $quote->rejection_reason }}
                                                    </div>
                                                @endif

                                                @if(!empty($quote->client_notes))
                                                    <div class="mt-2 p-2.5 rounded-lg {{ $quote->status === 'accepted' ? 'bg-emerald-100/80 border border-emerald-300 text-emerald-900' : 'bg-amber-50 border border-amber-300 text-amber-950' }} text-xs whitespace-pre-line">
                                                        <strong>💬 {{ $quote->status === 'accepted' ? 'Comentario / Notas del cliente:' : 'Notas del cliente en la solicitud:' }}</strong> {{ $quote->client_notes }}
                                                    </div>
                                                @endif

                                                @php
                                                    $linkedDocs = $event->invoices->where('quote_id', $quote->id);
                                                @endphp
                                                @if($linkedDocs->count() > 0)
                                                    <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                                        @foreach($linkedDocs as $ldoc)
                                                            <a href="{{ route('admin.invoice.pdf', $ldoc->id) }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-md border {{ $ldoc->isReceipt() ? 'bg-emerald-50 text-emerald-800 border-emerald-300 hover:bg-emerald-100' : 'bg-blue-50 text-blue-800 border-blue-300 hover:bg-blue-100' }} transition">
                                                                <span>{{ $ldoc->isReceipt() ? '🧾 Recibo' : '📄 Factura' }}:</span>
                                                                <span>{{ $ldoc->invoice_number }}</span>
                                                                <span class="text-[9px] opacity-75">({{ number_format($ldoc->total, 2) }}€)</span>
                                                                <span>⬇️</span>
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Botones de Acción -->
                                            <div class="flex flex-wrap items-center gap-1.5 self-start sm:self-auto">
                                                <!-- BOTONES CONVERTIR A RECIBO / FACTURA -->
                                                @if($quote->tax_type === 'none')
                                                    <button type="button" wire:click="convertQuoteToInvoice({{ $quote->id }}, 'recibo')" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-2.5 py-1.5 rounded-lg flex items-center gap-1 transition shadow-2xs" title="Pasar directamente a Recibo de Pago (Sin IVA)">
                                                        🧾 Pasar a Recibo
                                                    </button>
                                                    <button type="button" wire:click="convertQuoteToInvoice({{ $quote->id }}, 'factura')" class="bg-white hover:bg-blue-50 text-blue-700 border border-blue-300 text-xs font-bold px-2.5 py-1.5 rounded-lg flex items-center gap-1 transition shadow-2xs" title="Pasar a Factura oficial con IVA">
                                                        📄 A Factura (+IVA)
                                                    </button>
                                                @else
                                                    <button type="button" wire:click="convertQuoteToInvoice({{ $quote->id }}, 'factura')" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-2.5 py-1.5 rounded-lg flex items-center gap-1 transition shadow-2xs" title="Pasar directamente a Factura Oficial con IVA">
                                                        📄 Pasar a Factura
                                                    </button>
                                                    <button type="button" wire:click="convertQuoteToInvoice({{ $quote->id }}, 'recibo')" class="bg-white hover:bg-emerald-50 text-emerald-700 border border-emerald-300 text-xs font-bold px-2.5 py-1.5 rounded-lg flex items-center gap-1 transition shadow-2xs" title="Pasar a Recibo (Sin IVA)">
                                                        🧾 A Recibo
                                                    </button>
                                                @endif

                                                <button type="button" wire:click="loadQuoteIntoCalculator({{ $quote->id }})" class="bg-white hover:bg-amber-50 text-amber-800 border border-amber-300 text-xs font-bold px-2.5 py-1.5 rounded-lg flex items-center gap-1 transition shadow-2xs" title="Cargar servicios de esta propuesta en el calculador para editar">
                                                    ✏️ Editar
                                                </button>

                                                <button type="button" wire:click="sendQuoteByEmail({{ $quote->id }})" wire:loading.attr="disabled" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-800 border border-indigo-300 text-xs font-bold px-2.5 py-1.5 rounded-lg flex items-center gap-1 transition shadow-2xs cursor-pointer" title="Enviar Propuesta oficial en PDF por Email al cliente">
                                                    <span>✉️</span> Email
                                                </button>

                                                <button type="button" wire:click="sendDirectQuoteWhatsApp({{ $quote->id }})" wire:loading.attr="disabled" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-2.5 py-1.5 rounded-lg flex items-center gap-1 transition shadow-2xs cursor-pointer" title="Enviar Propuesta por WhatsApp desde el número de {{ ucfirst($whatsapp_sender_brand) }}">
                                                    <span wire:loading.remove wire:target="sendDirectQuoteWhatsApp({{ $quote->id }})">🚀 WA ({{ ucfirst($whatsapp_sender_brand) }})</span>
                                                    <span wire:loading wire:target="sendDirectQuoteWhatsApp({{ $quote->id }})">⏳</span>
                                                </button>
                                                <a href="{{ $qWaUrl }}" target="_blank" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 text-xs font-bold px-2 py-1.5 rounded-lg flex items-center gap-1 transition shadow-2xs" title="Abrir en mi WhatsApp Web personal">
                                                    💬
                                                </a>

                                                <a href="{{ $quoteOnlineUrl }}" target="_blank" class="bg-purple-50 hover:bg-purple-100 text-purple-800 border border-purple-300 text-xs font-bold px-2.5 py-1.5 rounded-lg flex items-center gap-1 transition shadow-2xs" title="Abrir enlace público donde el cliente descarga el PDF y acepta o rechaza el presupuesto">
                                                    🌐 Enlace Cliente
                                                </a>

                                                <a href="{{ route('admin.quote.pdf', $quote->id) }}" target="_blank" class="bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-300 text-xs font-bold px-2.5 py-1.5 rounded-lg flex items-center gap-1 transition shadow-2xs" title="Descargar Dossier Completo con Presupuesto al final (4 Páginas)">
                                                    📑 Dossier + Presupuesto
                                                </a>
                                                <a href="{{ route('admin.quote.pdf', $quote->id) }}?single=1" target="_blank" class="bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 text-xs font-bold px-2.5 py-1.5 rounded-lg flex items-center gap-1 transition shadow-2xs" title="Descargar Presupuesto de 1 Página (Mascadito)">
                                                    📄 1 Pág. Express
                                                </a>

                                                <button type="button" wire:click="deleteQuote({{ $quote->id }})" wire:confirm="¿Estás seguro de que deseas eliminar la Propuesta #{{ $quote->id }}?" class="bg-white hover:bg-red-50 text-red-600 border border-gray-200 hover:border-red-300 text-xs font-bold px-2 py-1.5 rounded-lg transition" title="Eliminar Propuesta">
                                                    🗑️
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Desglose de Servicios -->
                                        @if($quote->items && $quote->items->count() > 0)
                                            <div class="pt-2 border-t border-gray-200/80 text-[11px] text-gray-600 space-y-1">
                                                @foreach($quote->items as $item)
                                                    <div>
                                                        <div class="flex justify-between">
                                                            <span class="font-medium text-gray-800">&bull; {{ $item->concept ?: $item->service_name }} {{ $item->quantity > 1 ? '('.$item->quantity.'x)' : '' }}</span>
                                                            <span class="font-mono font-bold text-gray-800">{{ number_format($item->subtotal ?: ($item->price * $item->quantity), 2) }} €</span>
                                                        </div>
                                                        @if(!empty($item->description))
                                                            <div class="text-[10px] text-gray-500 pl-3 leading-snug">{{ $item->description }}</div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        @if(!empty($quote->admin_notes))
                                            <div class="p-2.5 rounded-lg bg-amber-50/90 border border-amber-200 text-[11px] text-amber-950">
                                                <strong class="font-bold text-amber-900 block mb-0.5">📝 Notas / Condiciones incluidas en la propuesta:</strong>
                                                <span class="whitespace-pre-line">{{ $quote->admin_notes }}</span>
                                            </div>
                                        @endif

                                        <!-- Métodos de pago aceptados en esta propuesta -->
                                        <div class="pt-1.5 border-t border-gray-100 flex flex-wrap items-center gap-1 text-[10px] text-gray-500">
                                            <span class="font-bold text-gray-400">Métodos de pago:</span>
                                            @foreach($quote->active_payment_methods as $pm)
                                                <span class="bg-gray-100 text-gray-700 px-1.5 py-0.2 rounded font-medium">
                                                    {{ match($pm) { 'transfer' => '🏦 Transferencia', 'bizum' => '📱 Bizum', 'cash' => '💵 Efectivo', 'card' => '💳 Tarjeta', default => $pm } }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center p-6 border-2 border-dashed border-gray-200 rounded-xl text-gray-400 text-xs">
                                        <span class="text-2xl block mb-1">📄</span>
                                        No se ha generado ninguna propuesta todavía. Selecciona los servicios a la izquierda y pulsa en "Generar Propuesta Comercial en PDF".
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- CONTRATOS --}}
        @if($activeTab == 'contracts')
            <div class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-200">
                    <div>
                        <h4 class="text-lg font-bold text-gray-800">Contratos del Evento y Firma Digital</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Envía el enlace interactivo al cliente para que revise las cláusulas y firme digitalmente desde su móvil u ordenador.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <div class="inline-flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200 text-[11px]">
                            <span class="px-2 font-bold text-slate-500">📱 Enviar desde:</span>
                            <button type="button" wire:click="$set('whatsapp_sender_brand', 'miguel')" class="px-2.5 py-1 rounded-lg font-bold transition cursor-pointer {{ $whatsapp_sender_brand === 'miguel' ? 'bg-amber-500 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">👑 Miguel</button>
                            <button type="button" wire:click="$set('whatsapp_sender_brand', 'fran')" class="px-2.5 py-1 rounded-lg font-bold transition cursor-pointer {{ $whatsapp_sender_brand === 'fran' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">🎧 Fran</button>
                            <button type="button" wire:click="$set('whatsapp_sender_brand', 'ezequiel')" class="px-2.5 py-1 rounded-lg font-bold transition cursor-pointer {{ $whatsapp_sender_brand === 'ezequiel' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">👥 Ezequiel</button>
                        </div>
                        <button wire:click="createContract" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-md transition cursor-pointer">
                            <span>➕</span> Generar Nuevo Contrato
                        </button>
                    </div>
                </div>

                @if (session()->has('contract_message')) 
                    <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-xl text-xs font-bold shadow-xs">
                        ✓ {{ session('contract_message') }}
                    </div> 
                @endif

                <div class="space-y-4">
                    @forelse($event->contracts as $contract)
                        @php
                            $signToken = $contract->token ?: $event->token;
                            $signUrl = url('/contrato/' . $signToken);
                            $contractMsg = "¡Hola " . ($event->client ? $event->client->name : 'pareja') . "! 👋\n\nOs compartimos el enlace para revisar y firmar online vuestro contrato de servicios para *" . $event->name . "*:\n\n🔗 " . $signUrl . "\n\nPodéis rellenar vuestros datos y firmar directamente desde el móvil. ¡Un saludo!";
                            $contractWaUrl = $clientPhone ? "https://wa.me/{$clientPhone}?text=" . rawurlencode($contractMsg) : "https://api.whatsapp.com/send?text=" . rawurlencode($contractMsg);
                        @endphp
                        
                        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-xs hover:border-indigo-200 transition">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if($editing_contract_id === $contract->id)
                                            <div class="flex items-center gap-1.5 my-0.5">
                                                <input 
                                                    type="text" 
                                                    wire:model="editing_contract_number" 
                                                    wire:keydown.enter="saveContractNumber({{ $contract->id }})"
                                                    wire:keydown.escape="cancelEditContractNumber"
                                                    class="border border-indigo-500 rounded-lg px-2.5 py-1 text-xs font-mono font-bold text-slate-800 bg-white focus:ring-2 focus:ring-indigo-500 w-64 shadow-xs"
                                                    placeholder="Ej: CTR-2026-FIESTA-HALLOWEEN"
                                                    autofocus
                                                >
                                                <button type="button" wire:click="saveContractNumber({{ $contract->id }})" class="p-1 px-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition shadow-xs cursor-pointer" title="Guardar cambios">
                                                    ✓
                                                </button>
                                                <button type="button" wire:click="cancelEditContractNumber" class="p-1 px-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs font-bold transition cursor-pointer" title="Cancelar">
                                                    ✕
                                                </button>
                                            </div>
                                        @else
                                            <span class="text-sm font-extrabold text-slate-800 flex items-center gap-1.5">
                                                <span>📄 {{ $contract->contract_number }}</span>
                                                <button type="button" wire:click="startEditContractNumber({{ $contract->id }})" class="text-slate-400 hover:text-indigo-600 text-xs p-1 rounded-md hover:bg-slate-100 transition cursor-pointer" title="Personalizar o renombrar código del contrato">
                                                    ✏️
                                                </button>
                                            </span>
                                        @endif

                                        @if($contract->status == 'signed')
                                            <span class="px-2.5 py-0.5 text-[11px] rounded-full bg-emerald-100 text-emerald-800 font-bold flex items-center gap-1">
                                                ✓ Firmado Digitalmente
                                            </span>
                                        @else
                                            <span class="px-2.5 py-0.5 text-[11px] rounded-full bg-amber-100 text-amber-800 font-bold flex items-center gap-1">
                                                ⏳ Pendiente de Firma
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1">
                                        Emitido el {{ $contract->created_at->format('d/m/Y') }}
                                        @if($contract->signed_at)
                                            &bull; <strong class="text-emerald-700">Firmado el {{ $contract->signed_at->format('d/m/Y \a \l\a\s H:i\h') }}</strong>
                                            @if($contract->client_name_signed)
                                                por <strong>{{ $contract->client_name_signed }}</strong> (DNI: {{ $contract->client_dni_signed }})
                                            @endif
                                        @endif
                                    </p>
                                </div>

                                <!-- ACCIONES -->
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('guest.contract', $signToken) }}" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition" title="Abrir portal de firma">
                                        👁️ Ver Formulario
                                    </a>

                                    <button type="button" wire:click="sendDirectContractWhatsApp({{ $contract->id }})" wire:loading.attr="disabled" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-2xs cursor-pointer" title="Enviar contrato por WhatsApp desde el número de {{ ucfirst($whatsapp_sender_brand) }}">
                                        <span wire:loading.remove wire:target="sendDirectContractWhatsApp({{ $contract->id }})">🚀 WA ({{ ucfirst($whatsapp_sender_brand) }})</span>
                                        <span wire:loading wire:target="sendDirectContractWhatsApp({{ $contract->id }})">⏳</span>
                                    </button>
                                    <a href="{{ $contractWaUrl }}" target="_blank" class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-2xs" title="Abrir en mi WhatsApp Web personal">
                                        💬
                                    </a>

                                    <a href="{{ route('admin.contract.pdf', $contract->id) }}" target="_blank" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold transition flex items-center gap-1" title="Descargar contrato oficial en PDF">
                                        📄 PDF
                                    </a>

                                    @if($contract->status == 'pending')
                                        <button wire:click="signContract({{ $contract->id }})" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-semibold transition cursor-pointer" title="Marcar como firmado manualmente">
                                            ✓ Marcar Firmado
                                        </button>
                                    @endif

                                    <button type="button" wire:click="deleteContract({{ $contract->id }})" wire:confirm="¿Estás seguro de que deseas eliminar este contrato?" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-xs font-bold transition flex items-center gap-1 cursor-pointer" title="Eliminar contrato">
                                        🗑️ Eliminar
                                    </button>
                                </div>
                            </div>

                            <!-- ENLACE DIRECTO DE FIRMA -->
                            <div class="mt-4 pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs" x-data="{ copied: false }">
                                <div class="flex items-center gap-2 truncate text-slate-500">
                                    <span class="font-bold text-slate-700">Enlace de Firma Online:</span>
                                    <span class="font-mono text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded truncate select-all">{{ $signUrl }}</span>
                                </div>
                                <button type="button" 
                                    @click="navigator.clipboard.writeText('{{ $signUrl }}'); copied = true; setTimeout(() => copied = false, 2500)"
                                    class="px-2.5 py-1 bg-white hover:bg-indigo-50 border border-slate-200 hover:border-indigo-300 text-slate-700 hover:text-indigo-700 rounded-lg text-xs font-bold transition flex items-center gap-1 self-start sm:self-auto cursor-pointer">
                                    <span x-text="copied ? '✓ ¡Copiado!' : '📋 Copiar Enlace'"></span>
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="bg-white rounded-2xl p-8 border border-gray-200 text-center text-slate-400 text-xs">
                            <span class="text-3xl block mb-2">📜</span>
                            No hay contratos generados para este evento todavía. Pulsa en "Generar Nuevo Contrato" para crear uno.
                        </div>
                    @endforelse
                </div>
            </div>
        @endif

        {{-- FACTURAS Y RECIBOS --}}
        @if($activeTab == 'invoices')
            <div class="space-y-6">
                <!-- Cabecera de la sección -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-200 pb-4">
                    <div>
                        <h4 class="text-lg font-black text-gray-900 flex items-center gap-2">
                            <span>📄 Facturación y 🧾 Recibos de Pago</span>
                        </h4>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Emite Facturas oficiales con desglose de IVA o Recibos / Justificantes sin IVA directamente a partir de cualquier presupuesto.
                        </p>
                    </div>

                    @php
                        $invoicesTotal = $event->invoices->sum('total');
                        $invoicesPaid = $event->invoices->where('status', 'paid')->sum('total');
                        $invoicesPending = $event->invoices->where('status', '!=', 'paid')->sum('total');
                    @endphp

                    <div class="flex items-center gap-2">
                        <div class="bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-xl text-right">
                            <span class="text-[10px] uppercase font-bold text-emerald-700 block">Cobrado</span>
                            <span class="text-xs font-black text-emerald-800">{{ number_format($invoicesPaid, 2) }} €</span>
                        </div>
                        <div class="bg-amber-50 border border-amber-200 px-3 py-1.5 rounded-xl text-right">
                            <span class="text-[10px] uppercase font-bold text-amber-700 block">Pendiente</span>
                            <span class="text-xs font-black text-amber-800">{{ number_format($invoicesPending, 2) }} €</span>
                        </div>
                        <div class="bg-slate-100 border border-slate-200 px-3 py-1.5 rounded-xl text-right">
                            <span class="text-[10px] uppercase font-bold text-slate-600 block">Total Emitido</span>
                            <span class="text-xs font-black text-slate-900">{{ number_format($invoicesTotal, 2) }} €</span>
                        </div>
                    </div>
                </div>

                @if (session()->has('invoice_message')) 
                    <div class="bg-emerald-50 border border-emerald-300 text-emerald-800 text-xs font-bold p-3 rounded-xl flex items-center gap-2 shadow-xs">
                        <span>✓</span> {{ session('invoice_message') }}
                    </div> 
                @endif

                <!-- ACCESO RÁPIDO: PASAR PROPUESTA A FACTURA / RECIBO -->
                @if($event->quotes && $event->quotes->count() > 0)
                    <div class="bg-gradient-to-r from-indigo-50/70 via-purple-50/70 to-emerald-50/70 border border-indigo-100 rounded-2xl p-4 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-1.5">
                                <span>⚡</span> Emitir Documento Rápido desde Propuestas:
                            </span>
                            <span class="text-[11px] text-slate-500">1-Clic y listo</span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @foreach($event->quotes as $q)
                                <div class="inline-flex items-center bg-white border border-slate-200 rounded-xl p-1 shadow-2xs text-xs">
                                    <span class="font-bold px-2 text-slate-800">Propuesta #{{ $q->id }} ({{ number_format($q->amount, 2) }}€)</span>
                                    <div class="flex items-center gap-1 pl-1 border-l border-slate-100">
                                        @if($q->tax_type === 'none')
                                            <button type="button" wire:click="convertQuoteToInvoice({{ $q->id }}, 'recibo')" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-2 py-1 rounded-lg text-[11px] transition" title="Generar Recibo de Pago Sin IVA">
                                                🧾 Recibo (Sin IVA)
                                            </button>
                                            <button type="button" wire:click="convertQuoteToInvoice({{ $q->id }}, 'factura')" class="bg-slate-100 hover:bg-blue-50 text-blue-700 hover:text-blue-900 font-bold px-2 py-1 rounded-lg text-[11px] transition" title="Generar Factura Oficial con IVA">
                                                📄 Factura (+IVA)
                                            </button>
                                        @else
                                            <button type="button" wire:click="convertQuoteToInvoice({{ $q->id }}, 'factura')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-2 py-1 rounded-lg text-[11px] transition" title="Generar Factura Oficial con IVA">
                                                📄 Factura (Con IVA)
                                            </button>
                                            <button type="button" wire:click="convertQuoteToInvoice({{ $q->id }}, 'recibo')" class="bg-slate-100 hover:bg-emerald-50 text-emerald-700 hover:text-emerald-900 font-bold px-2 py-1 rounded-lg text-[11px] transition" title="Generar Recibo Sin IVA">
                                                🧾 Recibo (Sin IVA)
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- LISTADO DE DOCUMENTOS EMITIDOS -->
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h5 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Documentos Emitidos ({{ $event->invoices->count() }})</h5>
                        <div class="inline-flex items-center bg-slate-100 p-0.5 rounded-lg border border-slate-200 text-[10px]">
                            <span class="px-1.5 font-bold text-slate-500">📱 Enviar desde:</span>
                            <button type="button" wire:click="$set('whatsapp_sender_brand', 'miguel')" class="px-2 py-0.5 rounded-md font-bold transition cursor-pointer {{ $whatsapp_sender_brand === 'miguel' ? 'bg-amber-500 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">👑 Miguel</button>
                            <button type="button" wire:click="$set('whatsapp_sender_brand', 'fran')" class="px-2 py-0.5 rounded-md font-bold transition cursor-pointer {{ $whatsapp_sender_brand === 'fran' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">🎧 Fran</button>
                            <button type="button" wire:click="$set('whatsapp_sender_brand', 'ezequiel')" class="px-2 py-0.5 rounded-md font-bold transition cursor-pointer {{ $whatsapp_sender_brand === 'ezequiel' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">👥 Ezequiel</button>
                        </div>
                    </div>

                    @forelse($event->invoices->sortByDesc('id') as $invoice)
                        @php
                            $invMsg = \App\Services\WhatsAppTemplateService::facturaRecibo($event, $invoice);
                            $invWaUrl = $clientPhone ? "https://wa.me/{$clientPhone}?text=" . rawurlencode($invMsg) : "https://api.whatsapp.com/send?text=" . rawurlencode($invMsg);
                        @endphp
                        <div class="bg-white p-4 rounded-2xl border {{ $invoice->isReceipt() ? 'border-emerald-200 bg-emerald-50/10' : 'border-blue-200 bg-blue-50/10' }} shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 transition">
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-xs font-black px-2 py-0.5 rounded-full uppercase {{ $invoice->isReceipt() ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-blue-100 text-blue-800 border border-blue-300' }}">
                                        {{ $invoice->isReceipt() ? '🧾 RECIBO' : '📄 FACTURA' }}
                                    </span>
                                    <span class="text-sm font-black text-slate-900 font-mono">{{ $invoice->invoice_number }}</span>
                                    <span class="text-xs text-slate-500">&bull; {{ $invoice->issue_date ? $invoice->issue_date->format('d/m/Y') : '' }}</span>

                                    @if($invoice->quote_id)
                                        <span class="text-[10px] bg-slate-100 text-slate-600 font-bold px-1.5 py-0.5 rounded">
                                            Origen: Propuesta #{{ $invoice->quote_id }}
                                        </span>
                                    @endif
                                </div>

                                <div class="text-xs text-slate-600 flex flex-wrap items-center gap-x-3 gap-y-1 pt-0.5">
                                    @if($invoice->isInvoice())
                                        <span>Base: <strong class="text-slate-800">{{ number_format($invoice->amount, 2) }} €</strong></span>
                                        <span>IVA ({{ number_format($invoice->tax_rate ?: 21, 0) }}%): <strong class="text-slate-800">{{ number_format($invoice->tax, 2) }} €</strong></span>
                                    @else
                                        <span>Servicios: <strong class="text-slate-800">{{ number_format($invoice->amount, 2) }} €</strong></span>
                                        <span class="text-emerald-700 font-semibold">Sin IVA</span>
                                    @endif
                                    <span class="text-sm font-black text-slate-900 bg-white px-2 py-0.5 rounded border border-slate-200">
                                        Total: {{ number_format($invoice->total, 2) }} €
                                    </span>
                                </div>

                                @if($invoice->notes)
                                    <p class="text-[11px] text-slate-500 italic">{{ $invoice->notes }}</p>
                                @endif
                            </div>

                            <!-- Acciones del Documento -->
                            <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
                                <!-- Botón Toggle de Estado de Cobro -->
                                <button 
                                    type="button" 
                                    wire:click="toggleInvoiceStatus({{ $invoice->id }})" 
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold border transition cursor-pointer flex items-center gap-1 {{ $invoice->status === 'paid' ? 'bg-emerald-100 text-emerald-800 border-emerald-300 hover:bg-emerald-200' : 'bg-amber-100 text-amber-800 border-amber-300 hover:bg-amber-200' }}"
                                    title="Pulsar para cambiar estado entre Cobrado y Pendiente"
                                >
                                    <span>{{ $invoice->status === 'paid' ? '✅ COBRADO' : '⏳ PENDIENTE' }}</span>
                                </button>

                                <!-- Botón Enviar por WhatsApp desde línea seleccionada -->
                                <button 
                                    type="button" 
                                    wire:click="sendDirectInvoiceWhatsApp({{ $invoice->id }})" 
                                    wire:loading.attr="disabled"
                                    class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-2.5 py-1.5 rounded-lg flex items-center gap-1 transition shadow-2xs cursor-pointer"
                                    title="Enviar {{ $invoice->isReceipt() ? 'Recibo' : 'Factura' }} por WhatsApp desde el número de {{ ucfirst($whatsapp_sender_brand) }}"
                                >
                                    <span wire:loading.remove wire:target="sendDirectInvoiceWhatsApp({{ $invoice->id }})">🚀 WA ({{ ucfirst($whatsapp_sender_brand) }})</span>
                                    <span wire:loading wire:target="sendDirectInvoiceWhatsApp({{ $invoice->id }})">⏳</span>
                                </button>
                                <a href="{{ $invWaUrl }}" target="_blank" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 text-xs font-bold px-2 py-1.5 rounded-lg flex items-center gap-1 transition shadow-2xs" title="Abrir en mi WhatsApp Web personal">
                                    💬
                                </a>

                                <!-- Botón Descargar PDF Oficial -->
                                <a 
                                    href="{{ route('admin.invoice.pdf', $invoice->id) }}" 
                                    target="_blank" 
                                    class="bg-white hover:bg-slate-50 text-indigo-700 border border-indigo-200 hover:border-indigo-300 text-xs font-bold px-3 py-1.5 rounded-lg flex items-center gap-1 transition shadow-2xs"
                                >
                                    <span>⬇️</span> PDF Oficial
                                </a>

                                <!-- Botón Eliminar -->
                                <button 
                                    type="button" 
                                    wire:click="deleteInvoice({{ $invoice->id }})" 
                                    wire:confirm="¿Estás seguro de que deseas eliminar el documento {{ $invoice->invoice_number }}?" 
                                    class="bg-white hover:bg-red-50 text-red-600 border border-slate-200 hover:border-red-300 text-xs font-bold px-2.5 py-1.5 rounded-lg transition" 
                                    title="Eliminar Documento"
                                >
                                    🗑️
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="bg-white p-8 rounded-2xl border border-gray-200 text-center text-slate-400 text-xs">
                            <span class="text-3xl block mb-2">🧾</span>
                            No hay facturas ni recibos emitidos para este evento.<br>
                            Puedes generar uno con 1-clic a partir de una propuesta superior o rellenar el formulario inferior.
                        </div>
                    @endforelse
                </div>

                <!-- FORMULARIO DE EMISIÓN MANUAL / PERSONALIZADA -->
                <div class="bg-white p-5 sm:p-6 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                    <div class="border-b border-gray-100 pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h5 class="font-bold text-gray-900 text-sm">✍️ Emitir Nueva Factura o Recibo Manual</h5>
                            <p class="text-[11px] text-gray-500">Configura los importes, régimen fiscal y numeración correlativa.</p>
                        </div>

                        <!-- Selector rápido desde Propuesta -->
                        @if($event->quotes && $event->quotes->count() > 0)
                            <div class="flex items-center gap-1.5">
                                <label class="text-xs text-slate-500 font-medium whitespace-nowrap">Cargar propuesta:</label>
                                <select 
                                    wire:change="loadQuoteIntoInvoice($event.target.value)" 
                                    class="border-slate-300 rounded-lg text-xs py-1 px-2 text-slate-700 bg-slate-50"
                                >
                                    <option value="">-- Seleccionar --</option>
                                    @foreach($event->quotes as $q)
                                        <option value="{{ $q->id }}">Propuesta #{{ $q->id }} ({{ number_format($q->amount, 2) }} €)</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>

                    <!-- SELECTOR DE TIPO DE DOCUMENTO (FACTURA VS RECIBO) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Tipo de Documento Fiscal</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition {{ $invoice_type === 'factura' ? 'bg-blue-50/70 border-blue-400 ring-2 ring-blue-200 text-blue-950 font-bold' : 'bg-slate-50 border-slate-200 text-slate-700' }}">
                                <input type="radio" wire:model.live="invoice_type" value="factura" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                                <div class="text-xs">
                                    <strong class="block text-blue-900">📄 Factura Oficial (Con IVA 21%)</strong>
                                    <span class="text-[11px] text-slate-500 font-normal">Desglosa Base Imponible + Cuota de IVA oficial. Recomendada para empresas o clientes que la soliciten.</span>
                                </div>
                            </label>

                            <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition {{ $invoice_type === 'recibo' ? 'bg-emerald-50/70 border-emerald-400 ring-2 ring-emerald-200 text-emerald-950 font-bold' : 'bg-slate-50 border-slate-200 text-slate-700' }}">
                                <input type="radio" wire:model.live="invoice_type" value="recibo" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                                <div class="text-xs">
                                    <strong class="block text-emerald-900">🧾 Recibo de Pago / Justificante (Sin IVA)</strong>
                                    <span class="text-[11px] text-slate-500 font-normal">Documento justificante de cobro sin desglose de IVA. Ideal para particulares o presupuestos netos.</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- CAMPOS DEL FORMULARIO -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-1">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1 flex items-center justify-between">
                                <span>Nº Documento *</span>
                                <button type="button" wire:click="$set('invoice_number', '{{ \App\Models\Invoice::nextNumber($invoice_type) }}')" class="text-[10px] text-indigo-600 hover:underline">
                                    🔄 Siguiente
                                </button>
                            </label>
                            <input 
                                type="text" 
                                wire:model="invoice_number" 
                                class="w-full border-slate-300 rounded-xl text-sm font-mono font-bold text-slate-900 p-2.5 bg-slate-50 focus:bg-white" 
                                placeholder="{{ $invoice_type === 'recibo' ? 'REC-2026-001' : 'FAC-2026-001' }}"
                            >
                            @error('invoice_number') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                {{ $invoice_type === 'recibo' ? 'Importe Total (€) *' : 'Base Imponible (€) *' }}
                            </label>
                            <input 
                                type="number" 
                                step="0.01" 
                                wire:model.live.debounce.300ms="invoice_amount" 
                                class="w-full border-slate-300 rounded-xl text-sm font-bold text-slate-900 p-2.5" 
                                placeholder="0.00"
                            >
                            @error('invoice_amount') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                        </div>

                        @if($invoice_type === 'factura')
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1 flex items-center justify-between">
                                    <span>IVA (€)</span>
                                    <span class="text-[10px] text-slate-500 font-normal">({{ number_format($invoice_tax_rate, 0) }}%)</span>
                                </label>
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    wire:model="invoice_tax" 
                                    class="w-full border-slate-300 rounded-xl text-sm font-bold text-slate-900 p-2.5" 
                                    placeholder="0.00"
                                >
                                @error('invoice_tax') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                            </div>
                        @else
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Régimen IVA</label>
                                <input 
                                    type="text" 
                                    disabled 
                                    value="Exento / Sin IVA (0,00 €)" 
                                    class="w-full border-slate-200 bg-slate-100 rounded-xl text-xs font-bold text-slate-500 p-2.5 cursor-not-allowed"
                                >
                            </div>
                        @endif

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Fecha Emisión *</label>
                            <input 
                                type="date" 
                                wire:model="invoice_issue_date" 
                                class="w-full border-slate-300 rounded-xl text-sm font-semibold text-slate-900 p-2.5"
                            >
                            @error('invoice_issue_date') <span class="text-rose-600 text-xs block font-bold mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Notas / Concepto Especial</label>
                            <input 
                                type="text" 
                                wire:model="invoice_notes" 
                                class="w-full border-slate-300 rounded-xl text-xs text-slate-800 p-2.5" 
                                placeholder="Ej: Servicios de sonorización e iluminación para boda..."
                            >
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Estado de Pago Inicial</label>
                            <select wire:model="invoice_status" class="w-full border-slate-300 rounded-xl text-xs font-bold p-2.5">
                                <option value="unpaid">⏳ Pendiente de Cobro</option>
                                <option value="paid">✅ Pagado / Cobrado</option>
                            </select>
                        </div>

                        <div class="flex items-end">
                            <button 
                                type="button" 
                                wire:click="createInvoice" 
                                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold p-3 rounded-xl text-xs shadow-md transition flex items-center justify-center gap-1.5 cursor-pointer"
                            >
                                <span>💾</span> Guardar y Emitir {{ $invoice_type === 'recibo' ? 'Recibo' : 'Factura' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- TAB 6: CITAS Y REUNIONES CON CLIENTES --}}
        @if($activeTab == 'meetings')
            <div class="space-y-6">
                <!-- Header de Citas -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">📅</span>
                            <h2 class="text-lg font-black text-slate-900 dark:text-white">Citas y Reuniones con el Cliente</h2>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Planifica reuniones presenciales, videollamadas o visitas técnicas, registra acuerdos y confirma por WhatsApp.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <div class="inline-flex items-center bg-slate-100 dark:bg-slate-800 p-1 rounded-xl border border-slate-200 dark:border-slate-700 text-[11px]">
                            <span class="px-2 font-bold text-slate-500 dark:text-slate-400">📱 Enviar desde:</span>
                            <button type="button" wire:click="$set('whatsapp_sender_brand', 'miguel')" class="px-2.5 py-1 rounded-lg font-bold transition cursor-pointer {{ $whatsapp_sender_brand === 'miguel' ? 'bg-amber-500 text-white shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900' }}">👑 Miguel</button>
                            <button type="button" wire:click="$set('whatsapp_sender_brand', 'fran')" class="px-2.5 py-1 rounded-lg font-bold transition cursor-pointer {{ $whatsapp_sender_brand === 'fran' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900' }}">🎧 Fran</button>
                            <button type="button" wire:click="$set('whatsapp_sender_brand', 'ezequiel')" class="px-2.5 py-1 rounded-lg font-bold transition cursor-pointer {{ $whatsapp_sender_brand === 'ezequiel' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900' }}">👥 Ezequiel</button>
                        </div>
                        <button 
                            type="button" 
                            wire:click="openCreateMeetingModal" 
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold text-xs shadow-md hover:shadow-lg transition cursor-pointer"
                        >
                            <span>➕</span> Programar Nueva Cita / Reunión
                        </button>
                    </div>
                </div>

                @if(session()->has('meeting_message'))
                    <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl text-emerald-800 dark:text-emerald-200 text-xs font-bold flex items-center justify-between">
                        <span>{{ session('meeting_message') }}</span>
                    </div>
                @endif

                <!-- Listado de Reuniones -->
                @php
                    $meetings = $event->meetings->sortBy('meeting_date');
                    $clientPhone = $event->client ? preg_replace('/[^0-9]/', '', $event->client->phone) : '';
                    if (strlen($clientPhone) === 9) $clientPhone = '34' . $clientPhone;
                @endphp

                @if($meetings->isEmpty())
                    <div class="bg-white dark:bg-slate-900 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl p-10 text-center">
                        <div class="w-16 h-16 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center text-3xl mx-auto mb-3">
                            📅
                        </div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">No hay citas ni reuniones programadas</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto mt-1 mb-5">
                            Registra la primera toma de contacto, visita técnica a la finca, reunión de escaleta musical o guion de magia.
                        </p>
                        <button 
                            type="button" 
                            wire:click="openCreateMeetingModal" 
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md transition cursor-pointer"
                        >
                            <span>➕</span> Programar Primera Cita
                        </button>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($meetings as $meeting)
                            @php
                                $confirmText = \App\Services\WhatsAppTemplateService::meetingConfirmation($meeting);
                                $confirmUrl = \App\Services\WhatsAppTemplateService::buildUrl($clientPhone, $confirmText);
                                $reminderText = \App\Services\WhatsAppTemplateService::meetingReminder($meeting);
                                $reminderUrl = \App\Services\WhatsAppTemplateService::buildUrl($clientPhone, $reminderText);
                            @endphp
                            <div class="bg-white dark:bg-slate-900 border {{ $meeting->status === 'completed' ? 'border-emerald-200 dark:border-emerald-800/60 bg-emerald-50/20' : ($meeting->status === 'cancelled' ? 'border-rose-200 dark:border-rose-900/40 opacity-70' : 'border-slate-200 dark:border-slate-800') }} rounded-2xl p-5 shadow-xs flex flex-col justify-between transition hover:shadow-md">
                                <div>
                                    <!-- Header Card -->
                                    <div class="flex items-start justify-between gap-3 mb-3">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <!-- Tipo de Reunión Badge -->
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-black {{ $meeting->meeting_type_badge_color }}">
                                                {{ $meeting->meeting_type_label }}
                                            </span>
                                            <!-- Estado Badge -->
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-extrabold {{ $meeting->status_badge_color }}">
                                                {{ $meeting->status_label }}
                                            </span>
                                        </div>

                                        <!-- Acciones Rápidas -->
                                        <div class="flex items-center gap-1">
                                            @if($meeting->status !== 'completed')
                                                <button 
                                                    type="button" 
                                                    wire:click="markMeetingCompleted({{ $meeting->id }})" 
                                                    class="p-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 text-emerald-700 dark:text-emerald-300 text-xs font-bold transition cursor-pointer"
                                                    title="Marcar como realizada"
                                                >
                                                    ✅
                                                </button>
                                            @endif
                                            <button 
                                                type="button" 
                                                wire:click="editMeeting({{ $meeting->id }})" 
                                                class="p-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 text-xs transition cursor-pointer"
                                                title="Editar cita"
                                            >
                                                ✏️
                                            </button>
                                            <button 
                                                type="button" 
                                                wire:confirm="¿Seguro que deseas eliminar esta cita?" 
                                                wire:click="deleteMeeting({{ $meeting->id }})" 
                                                class="p-1.5 rounded-lg bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 text-rose-700 dark:text-rose-300 text-xs transition cursor-pointer"
                                                title="Eliminar cita"
                                            >
                                                🗑️
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Título -->
                                    <h3 class="text-sm font-black text-slate-900 dark:text-white mb-2 flex items-center gap-1.5">
                                        <span>{{ $meeting->location_icon }}</span>
                                        <span>{{ $meeting->title }}</span>
                                    </h3>

                                    <!-- Detalles Clave -->
                                    <div class="space-y-1.5 text-xs text-slate-600 dark:text-slate-300 mb-4 bg-slate-50 dark:bg-slate-800/50 p-3 rounded-xl border border-slate-100 dark:border-slate-800">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-slate-400 font-semibold">📅 Fecha y Hora:</span>
                                            <span class="font-bold text-slate-800 dark:text-slate-200">
                                                {{ $meeting->meeting_date ? $meeting->meeting_date->translatedFormat('d/m/Y \a \l\a\s H:i\h') : 'Sin fecha' }} 
                                                <span class="text-slate-400 font-normal">({{ $meeting->duration_minutes }} min)</span>
                                            </span>
                                        </div>

                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-slate-400 font-semibold">📍 Modalidad:</span>
                                            <span class="font-bold text-slate-800 dark:text-slate-200">
                                                @if($meeting->location_type === 'video_call')
                                                    💻 Videollamada
                                                    @if($meeting->video_call_url)
                                                        <a href="{{ $meeting->video_call_url }}" target="_blank" class="text-blue-600 dark:text-blue-400 underline ml-1 font-normal">Abrir enlace 🔗</a>
                                                    @endif
                                                @elseif($meeting->location_type === 'phone')
                                                    📱 Llamada telefónica
                                                @else
                                                    📍 Presencial: {{ $meeting->location ?: 'Lugar acordado' }}
                                                @endif
                                            </span>
                                        </div>

                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-slate-400 font-semibold">👤 Atiende:</span>
                                            <span class="font-bold text-indigo-600 dark:text-indigo-400">
                                                {{ $meeting->staff ? $meeting->staff->name : 'Cualquiera del equipo' }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Notas Previas / Temario -->
                                    @if($meeting->notes)
                                        <div class="mb-3">
                                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">📝 Temas a tratar / Notas:</p>
                                            <p class="text-xs text-slate-700 dark:text-slate-300 bg-amber-50/50 dark:bg-amber-950/20 border border-amber-200/60 dark:border-amber-900/40 p-2.5 rounded-xl whitespace-pre-line">
                                                {{ $meeting->notes }}
                                            </p>
                                        </div>
                                    @endif

                                    <!-- Acuerdos / Conclusiones -->
                                    @if($meeting->summary)
                                        <div class="mb-3">
                                            <p class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-1">💡 Acuerdos y Decisiones tomadas:</p>
                                            <div class="text-xs text-slate-800 dark:text-slate-200 bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 p-2.5 rounded-xl whitespace-pre-line font-medium">
                                                {{ $meeting->summary }}
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <!-- Footer con Botones de WhatsApp -->
                                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2 mt-2">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        @if($clientPhone)
                                            <button 
                                                type="button"
                                                wire:click="sendDirectMeetingWhatsApp({{ $meeting->id }}, 'confirm')"
                                                wire:loading.attr="disabled"
                                                class="inline-flex items-center gap-1 text-[11px] font-bold text-white bg-emerald-600 hover:bg-emerald-700 px-2.5 py-1.5 rounded-lg transition shadow-2xs cursor-pointer"
                                                title="Enviar confirmación de cita desde el número de {{ ucfirst($whatsapp_sender_brand) }}"
                                            >
                                                <span wire:loading.remove wire:target="sendDirectMeetingWhatsApp({{ $meeting->id }}, 'confirm')">🚀 Confirmar ({{ ucfirst($whatsapp_sender_brand) }})</span>
                                                <span wire:loading wire:target="sendDirectMeetingWhatsApp({{ $meeting->id }}, 'confirm')">⏳</span>
                                            </button>

                                            <button 
                                                type="button"
                                                wire:click="sendDirectMeetingWhatsApp({{ $meeting->id }}, 'reminder')"
                                                wire:loading.attr="disabled"
                                                class="inline-flex items-center gap-1 text-[11px] font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/50 hover:bg-blue-100 border border-blue-200 dark:border-blue-800 px-2.5 py-1.5 rounded-lg transition cursor-pointer"
                                                title="Enviar recordatorio previo desde el número de {{ ucfirst($whatsapp_sender_brand) }}"
                                            >
                                                <span wire:loading.remove wire:target="sendDirectMeetingWhatsApp({{ $meeting->id }}, 'reminder')">🔔 Recordatorio ({{ ucfirst($whatsapp_sender_brand) }})</span>
                                                <span wire:loading wire:target="sendDirectMeetingWhatsApp({{ $meeting->id }}, 'reminder')">⏳</span>
                                            </button>

                                            <a 
                                                href="{{ $confirmUrl }}" 
                                                target="_blank" 
                                                class="inline-flex items-center text-[11px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/50 hover:bg-emerald-100 border border-emerald-200 dark:border-emerald-800 px-2 py-1.5 rounded-lg transition"
                                                title="Abrir en mi WhatsApp Web personal"
                                            >
                                                💬
                                            </a>
                                        @else
                                            <span class="text-[10px] text-slate-400 italic">Cliente sin teléfono para WhatsApp</span>
                                        @endif
                                    </div>

                                    <button 
                                        type="button" 
                                        wire:click="editMeeting({{ $meeting->id }})" 
                                        class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline cursor-pointer"
                                    >
                                        {{ $meeting->summary ? 'Editar Conclusiones' : '+ Anotar Acuerdos' }}
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

    </div>

    <!-- Modal Asignar / Cambiar Personal (DJ y Asistente) -->
    @if($showStaffModal)
    <div class="fixed z-30 inset-0 overflow-y-auto" aria-labelledby="modal-staff-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="$set('showStaffModal', false)"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                
                <form wire:submit.prevent="saveStaff">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex justify-between items-center mb-4 border-b pb-2">
                            <h3 class="text-lg font-bold text-gray-900" id="modal-staff-title">
                                👥 Asignar Personal del Evento
                            </h3>
                            <button type="button" wire:click="$set('showStaffModal', false)" class="text-gray-400 hover:text-gray-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-indigo-900 mb-1">🎧 DJ Principal (Baile / Fiesta)</label>
                                <select wire:model="assigned_dj_id" class="w-full border-gray-300 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500 bg-white p-2.5">
                                    <option value="">-- Sin DJ asignado --</option>
                                    @foreach($allDjs as $d)
                                        <option value="{{ $d->id }}">{{ $d->name }} {{ $d->phone ? '('.$d->phone.')' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-bold text-amber-900">👷‍♂️ Asistentes / Personal de Apoyo</label>
                                    <span class="text-[10px] font-bold bg-amber-100 text-amber-900 px-2 py-0.5 rounded-full">
                                        {{ count($assigned_assistant_ids) }} {{ count($assigned_assistant_ids) === 1 ? 'asistente' : 'asistentes' }}
                                    </span>
                                </div>
                                <div class="space-y-1 max-h-48 overflow-y-auto border border-amber-200 rounded-xl p-2 bg-amber-50/40">
                                    @foreach($allAssistants as $a)
                                        <label class="flex items-center gap-2.5 p-2 rounded-lg cursor-pointer hover:bg-white transition text-xs font-semibold text-gray-800 border border-transparent hover:border-amber-200 shadow-2xs">
                                            <input type="checkbox" wire:model="assigned_assistant_ids" value="{{ $a->id }}" class="rounded text-amber-600 focus:ring-amber-500 border-gray-300 h-4 w-4">
                                            <div class="flex-1 min-w-0">
                                                <div class="font-bold text-gray-900 truncate">{{ $a->name }}</div>
                                                @if($a->phone)<div class="text-[10px] text-gray-500 font-normal">📞 {{ $a->phone }}</div>@endif
                                            </div>
                                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded {{ $a->role === 'assistant' ? 'bg-amber-100 text-amber-800' : 'bg-indigo-100 text-indigo-800' }}">
                                                {{ ucfirst($a->role) }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <span class="text-[10px] text-gray-500 mt-1 block">Puedes seleccionar varios asistentes (ej: Ezequiel, montadores, etc.).</span>
                            </div>

                            <!-- Resumen del Equipo y Manutención -->
                            <div class="p-3 bg-purple-50 rounded-xl border border-purple-200 text-xs text-purple-950 space-y-1">
                                <div class="flex items-center justify-between font-bold">
                                    <span class="flex items-center gap-1.5">🍽️ Manutención / Menú de Staff:</span>
                                    <span class="text-purple-700 text-sm font-black">{{ 1 + count($assigned_assistant_ids) }} {{ (1 + count($assigned_assistant_ids)) === 1 ? 'persona' : 'personas' }}</span>
                                </div>
                                <p class="text-[11px] text-purple-800 leading-relaxed">
                                    1 DJ Titular + {{ count($assigned_assistant_ids) }} {{ count($assigned_assistant_ids) === 1 ? 'Asistente' : 'Asistentes' }}. Esta cantidad se reflejará automáticamente en la cláusula de comida del contrato para fases de cóctel/banquete.
                                </p>
                            </div>
                        </div>

                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-xl gap-2">
                        <button type="submit" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-sm font-bold text-white hover:bg-indigo-700 focus:outline-none transition">
                            Guardar Personal
                        </button>
                        <button type="button" wire:click="$set('showStaffModal', false)" class="w-full sm:w-auto mt-2 sm:mt-0 inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none transition">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Añadir / Editar Canción -->
    @if($showSongModal)
    <div class="fixed z-30 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="$set('showSongModal', false)"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full">
                
                <form wire:submit.prevent="saveSongRequest" novalidate>
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex justify-between items-center mb-4 border-b pb-2">
                            <h3 class="text-lg font-bold text-gray-900" id="modal-title">
                                {{ $editingSongId ? '✏️ Editar Canción / Momento' : '➕ Añadir Canción a la Escaleta' }}
                            </h3>
                            <button type="button" wire:click="$set('showSongModal', false)" class="text-gray-400 hover:text-gray-500 cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        @if ($errors->any())
                            <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-xs font-semibold">
                                <p class="font-bold flex items-center gap-1"><span>⚠️</span> Corrige los siguientes campos antes de guardar:</p>
                                <ul class="list-disc pl-4 mt-1 space-y-0.5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        
                        <div class="space-y-4">
                            <!-- Búsqueda rápida e instantánea de Spotify / Apple Music -->
                            <div class="bg-indigo-50/70 border border-indigo-100 rounded-xl p-3 relative">
                                <label class="block text-xs font-bold text-indigo-900 mb-1 flex items-center gap-1.5">
                                    <span>🔍</span> Autocompletar con Spotify / Apple Music
                                </label>
                                <input 
                                    type="text" 
                                    wire:model.live.debounce.300ms="musicSearchQuery" 
                                    placeholder="Escribe el nombre de la canción o artista (ej: Coldplay, Viva la Vida)..." 
                                    class="w-full border-indigo-200 rounded-lg text-xs focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                                >
                                
                                @if(!empty($musicSearchResults))
                                    <div class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-xl shadow-2xl z-50 max-h-56 overflow-y-auto divide-y divide-gray-100">
                                        @foreach($musicSearchResults as $track)
                                            <div 
                                                wire:click="selectTrackFromSearch({{ json_encode($track) }})"
                                                class="p-2.5 hover:bg-indigo-50/70 cursor-pointer flex items-center justify-between gap-3 transition"
                                            >
                                                <div class="flex items-center gap-2.5 truncate">
                                                    @if(!empty($track['cover_url']))
                                                        <img src="{{ $track['cover_url'] }}" class="w-8 h-8 rounded-lg object-cover shadow-sm shrink-0" alt="Cover">
                                                    @else
                                                        <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold shrink-0">🎵</div>
                                                    @endif
                                                    <div class="truncate">
                                                        <div class="text-xs font-bold text-gray-900 truncate">{{ $track['title'] }}</div>
                                                        <div class="text-[11px] text-gray-500 truncate">{{ $track['artist'] }}</div>
                                                    </div>
                                                </div>
                                                <span class="text-[10px] font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full whitespace-nowrap">
                                                    Seleccionar ↵
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <!-- Categoría y Momento (Adaptados dinámicamente al tipo de evento) -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300 mb-1">Fase / Categoría *</label>
                                    <select wire:model.live="req_category" class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        @foreach($event->suggested_categories as $catKey => $catLabel)
                                            <option value="{{ $catKey }}">{{ $catLabel }}</option>
                                        @endforeach
                                    </select>
                                    @error('req_category') <span class="text-red-500 text-xs font-semibold">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-slate-300">Momento Clave / Etiqueta *</label>
                                        <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold">Sugerencias: {{ $event->event_type_label }}</span>
                                    </div>
                                    <input type="text" list="moments_list" wire:model="req_moment" placeholder="Ej: Baile / Fiesta, Entrada, Tarta..." class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 font-medium">
                                    
                                    <!-- Datalist dinámico por tipo de evento -->
                                    <datalist id="moments_list">
                                        @foreach($event->suggested_moments as $sugMoment)
                                            <option value="{{ $sugMoment }}">
                                        @endforeach
                                    </datalist>
                                    @error('req_moment') <span class="text-red-500 text-xs font-semibold">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Botones rápidos de 1-Clic para elegir Momento -->
                            <div class="pt-0.5">
                                <span class="text-[10px] font-bold text-gray-400 dark:text-gray-400 uppercase tracking-wider block mb-1">Accesos rápidos de momento:</span>
                                <div class="flex flex-wrap gap-1.5">
                                    <button type="button" wire:click="$set('req_moment', 'Baile / Fiesta')" class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-purple-100 hover:bg-purple-200 text-purple-800 dark:bg-purple-950/70 dark:text-purple-300 dark:hover:bg-purple-900 border border-purple-300 dark:border-purple-800 transition cursor-pointer">
                                        💃 Baile / Fiesta (General)
                                    </button>
                                    @foreach(array_slice($event->suggested_moments, 1, 5) as $quickM)
                                        <button type="button" wire:click="$set('req_moment', '{{ addslashes($quickM) }}')" class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 border border-gray-200 dark:border-slate-700 transition cursor-pointer">
                                            {{ $quickM }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Título y Artista -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Título de la Canción *</label>
                                    <input type="text" wire:model="req_title" placeholder="Ej: Será Porque Te Amo" class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    @error('req_title') <span class="text-red-500 text-xs font-semibold">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Artista / Versión</label>
                                    <input type="text" wire:model="req_artist" placeholder="Ej: DJ Matrix / Ricchi e Poveri" class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    @error('req_artist') <span class="text-red-500 text-xs font-semibold">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Solicitado por y CUE time -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Quién lo pide / Dedicatoria</label>
                                    <input type="text" wire:model="req_requested_by" placeholder="Ej: Novios, Amigos de la novia..." class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Minuto de Inicio (CUE)</label>
                                    <input type="text" wire:model="req_cue_time" placeholder="Ej: 01:15 o Estribillo" class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                            </div>

                            <!-- Enlaces YouTube, Spotify y Apple Music -->
                            <div class="space-y-1.5 pt-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-gray-700">Enlaces y Plataformas Streaming</span>
                                    <button 
                                        type="button" 
                                        wire:click="autoFillTrackLinks" 
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 transition cursor-pointer shadow-2xs"
                                        title="Buscar y rellenar automáticamente los enlaces de Spotify, Apple Music y YouTube"
                                    >
                                        <span wire:loading.remove wire:target="autoFillTrackLinks">🪄 Auto-detectar enlaces</span>
                                        <span wire:loading wire:target="autoFillTrackLinks" class="animate-pulse">Detectando enlaces...</span>
                                    </button>
                                </div>

                                @if (session()->has('music_modal_message'))
                                    <div class="text-[11px] font-bold text-emerald-600 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-lg">
                                        {{ session('music_modal_message') }}
                                    </div>
                                @endif

                                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Enlace YouTube</label>
                                        <input type="text" wire:model="req_youtube_url" placeholder="https://youtube.com/watch?v=..." class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        @error('req_youtube_url') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Enlace Spotify</label>
                                        <input type="text" wire:model="req_spotify_url" placeholder="https://open.spotify.com/track/..." class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        @error('req_spotify_url') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">🍎 Apple Music</label>
                                        <input type="text" wire:model="req_apple_music_url" placeholder="https://music.apple.com/..." class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        @error('req_apple_music_url') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">🌊 Tidal URL</label>
                                        <input type="text" wire:model="req_tidal_url" placeholder="https://listen.tidal.com/..." class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        @error('req_tidal_url') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Subir Audio MP3 o Gestión de Archivo -->
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700/80 space-y-2">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold text-gray-700 dark:text-slate-300">
                                        📁 Archivo de Audio (MP3 / WAV) o Nube
                                    </label>
                                    @if($existing_audio_file)
                                        <button 
                                            type="button" 
                                            wire:click="removeExistingAudioFile"
                                            class="text-[11px] font-bold text-rose-600 hover:text-rose-700 dark:text-rose-400 hover:underline cursor-pointer flex items-center gap-1"
                                            title="Desvincular o eliminar el archivo/preescucha actual"
                                        >
                                            <span>🗑️ Quitar archivo / preescucha</span>
                                        </button>
                                    @endif
                                </div>

                                @if($existing_audio_file)
                                    <div class="text-xs rounded-lg p-2.5 flex items-start justify-between gap-2 {{ str_contains($existing_audio_file, 'itunes.apple.com') || str_contains($existing_audio_file, 'p.scdn.co') ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60' : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60' }}">
                                        <div class="flex items-center gap-2 truncate">
                                            @if(str_contains($existing_audio_file, 'drive.google.com') || str_contains($existing_audio_file, '/api/drive-stream/'))
                                                <span>☁️</span>
                                                <span class="font-bold truncate">Google Drive vinculado</span>
                                            @elseif(str_contains($existing_audio_file, 'itunes.apple.com') || str_contains($existing_audio_file, 'p.scdn.co'))
                                                <span>⚠️</span>
                                                <div>
                                                    <span class="font-bold">Preescucha de 30 segundos (iTunes / Apple)</span>
                                                    <div class="text-[10px] text-amber-700 dark:text-amber-400">Sube un MP3 completo o usa Spotify/Apple/YouTube para que suene entera.</div>
                                                </div>
                                            @else
                                                <span>🎵</span>
                                                <span class="font-bold truncate">Archivo guardado: {{ basename($existing_audio_file) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <div class="space-y-1">
                                    <input type="file" wire:model="req_audio_file" accept="audio/*" class="w-full text-xs text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                                    <div wire:loading wire:target="req_audio_file" class="text-xs text-indigo-600 font-bold mt-1">⏳ Subiendo archivo de audio...</div>
                                    @error('req_audio_file') <span class="text-red-500 text-xs font-bold block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Notas / Instrucciones -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Notas / Instrucciones para el DJ</label>
                                <textarea wire:model="req_notes" rows="2" placeholder="Ej: Lanzar cuando entren con el regalo y bajar volumen al dar el micrófono..." class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                            </div>
                        </div>

                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-xl gap-2">
                        <button type="submit" wire:loading.attr="disabled" class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-lg border border-transparent shadow-sm px-5 py-2.5 bg-indigo-600 text-sm font-bold text-white hover:bg-indigo-700 focus:outline-none transition cursor-pointer disabled:opacity-50">
                            <span wire:loading.remove wire:target="saveSongRequest">💾 Guardar Canción</span>
                            <span wire:loading wire:target="saveSongRequest" class="animate-pulse">Guardando...</span>
                        </button>
                        <button type="button" wire:click="$set('showSongModal', false)" class="w-full sm:w-auto mt-2 sm:mt-0 inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none transition cursor-pointer">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Editar Datos Generales del Evento (Nombre, Fecha, Lugar, Marca, Tipo, Estado, Notas) -->
    @if($showEditEventModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-edit-event-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-3 sm:p-4 text-center">
            <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs transition-opacity" wire:click="closeEditEventModal"></div>
            
            <div class="relative bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all w-full max-w-lg max-h-[90vh] flex flex-col border border-gray-200 dark:border-slate-800">
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 flex items-center justify-between text-white shrink-0">
                    <div class="flex items-center gap-2.5">
                        <span class="text-2xl">✏️</span>
                        <div>
                            <h3 class="font-extrabold text-base sm:text-lg leading-tight" id="modal-edit-event-title">Editar Detalles del Evento</h3>
                            <p class="text-xs text-indigo-100">Modifica el nombre, fecha, lugar o marca comercial</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeEditEventModal" class="text-indigo-200 hover:text-white text-2xl font-bold leading-none cursor-pointer">&times;</button>
                </div>

                <form wire:submit.prevent="saveEventDetails" class="flex flex-col flex-1 overflow-hidden">
                    <div class="p-6 space-y-4 overflow-y-auto flex-1 text-xs">
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Nombre del Evento *</label>
                            <input type="text" wire:model="event_edit_name" placeholder="Ej: Boda Laura y Carlos / Enlace Marta y Javier" class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white rounded-xl text-sm font-bold focus:ring-indigo-500 focus:border-indigo-500 p-2.5">
                            @error('event_edit_name') <span class="text-rose-500 text-[11px] font-semibold">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Fecha del Evento *</label>
                                <input type="date" wire:model="event_edit_date" class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white rounded-xl text-sm font-bold focus:ring-indigo-500 focus:border-indigo-500 p-2.5">
                                @error('event_edit_date') <span class="text-rose-500 text-[11px] font-semibold">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Lugar / Finca / Salón *</label>
                                <input type="text" wire:model="event_edit_location" placeholder="Ej: Andere Vitoria / Bodega Hika" class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white rounded-xl text-sm font-semibold focus:ring-indigo-500 focus:border-indigo-500 p-2.5">
                                @error('event_edit_location') <span class="text-rose-500 text-[11px] font-semibold">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Tipo de Evento *</label>
                                <select wire:model="event_edit_type" class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white rounded-xl text-sm font-bold p-2.5">
                                    <option value="boda">💍 Boda / Enlace</option>
                                    @if($event_edit_type === 'video' || \App\Models\Setting::isVideoServiceEnabled($event_edit_brand))
                                        <option value="video">📹 Vídeo / Videógrafo</option>
                                    @endif
                                    <option value="empresa">🏢 Evento de Empresa</option>
                                    <option value="cumpleanos">🎂 Cumpleaños / Aniversario</option>
                                    <option value="comunion">🕊️ Comunión</option>
                                    <option value="otro">🎉 Fiesta Privada / Otro</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Marca Comercial *</label>
                                <select wire:model.live="event_edit_brand" class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white rounded-xl text-sm font-bold p-2.5">
                                    <option value="nunez_and_son">👑 Núñez and Son</option>
                                    <option value="javnx">🎧 JAVNX DJ</option>
                                    <option value="mago_leugim">🎩 Mago Leugim</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Estado del Evento *</label>
                            <select wire:model="event_edit_status" class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white rounded-xl text-sm font-bold p-2.5">
                                <option value="draft">🟡 Borrador / En preparación</option>
                                <option value="no_response">⏳ Sin Respuesta (Esperando cliente)</option>
                                <option value="on_hold">⏸️ En Espera (El cliente avisará)</option>
                                <option value="confirmed">🟢 Confirmado</option>
                                <option value="completed">🔵 Finalizado / Realizado</option>
                                <option value="rejected">❌ Rechazado (No acepta)</option>
                                <option value="cancelled">🔴 Cancelado</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Notas / Observaciones del Evento</label>
                            <textarea wire:model="event_edit_notes" rows="2" placeholder="Detalles o anotaciones generales sobre el evento..." class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white rounded-xl text-xs p-2.5"></textarea>
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-slate-950 px-6 py-3.5 border-t border-gray-100 dark:border-slate-800 flex justify-end gap-2 shrink-0">
                        <button type="button" wire:click="closeEditEventModal" class="bg-white dark:bg-slate-900 hover:bg-gray-100 text-gray-700 dark:text-gray-300 font-bold text-xs px-4 py-2 border border-gray-300 dark:border-slate-700 rounded-xl transition cursor-pointer">
                            Cancelar
                        </button>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs px-5 py-2 rounded-xl shadow-xs transition cursor-pointer">
                            💾 Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Contacto Finca / Bodega -->
    @if($showVenueModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeVenueModal"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-indigo-700 px-6 py-4 flex items-center justify-between text-white">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">🏢</span>
                        <div>
                            <h3 class="font-bold text-lg leading-tight">Contacto de Finca / Bodega / Salón</h3>
                            <p class="text-xs text-indigo-200">{{ $event->name }} &bull; Datos para coordinación técnica</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeVenueModal" class="text-indigo-200 hover:text-white text-2xl font-bold leading-none">&times;</button>
                </div>

                <form wire:submit.prevent="saveVenueContact">
                    <div class="p-6 space-y-4">
                        @if (session()->has('venue_lookup_error'))
                            <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-semibold">
                                ℹ️ {{ session('venue_lookup_error') }}
                            </div>
                        @endif

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nombre del Responsable / Coordinador</label>
                            <input type="text" wire:model="venue_contact_name" placeholder="Ej: Marta (Coordinadora Eventos) o Juan (Maître)" class="w-full border-gray-300 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Teléfono Directo de Contacto</label>
                                <button type="button"
                                        wire:click="lookupVenuePhoneAutomatically"
                                        wire:loading.attr="disabled"
                                        class="text-[11px] font-bold text-emerald-700 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-2.5 py-0.5 rounded-lg transition cursor-pointer flex items-center gap-1">
                                    <span wire:loading.remove wire:target="lookupVenuePhoneAutomatically">🔍 Buscar en Web / Google («{{ $event->location }}»)</span>
                                    <span wire:loading wire:target="lookupVenuePhoneAutomatically">⏳ Buscando teléfono...</span>
                                </button>
                            </div>
                            <input type="tel" wire:model="venue_contact_phone" placeholder="Ej: 943 14 27 09 o 612 345 678" class="w-full border-gray-300 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <p class="text-[11px] text-gray-500 mt-1">Este número tendrá botones de llamada directa y WhatsApp en 1-clic.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Notas de Montaje, Acceso & Electricidad</label>
                            <textarea wire:model="venue_notes" rows="3" placeholder="Ej: Acceso por puerta trasera nave 3. Montaje a partir de las 11:30h. Tomas Schuko a 15 metros del escenario..." class="w-full border-gray-300 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-3 border-t border-gray-100 flex justify-end gap-2">
                        <button type="button" wire:click="closeVenueModal" class="bg-white hover:bg-gray-100 text-gray-700 font-bold text-xs px-4 py-2 border border-gray-300 rounded-xl transition">
                            Cancelar
                        </button>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs px-4 py-2 rounded-xl shadow-xs transition">
                            💾 Guardar Contacto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Horarios del Evento y Baile -->
    @if($showScheduleModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" wire:click="closeScheduleModal"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 dark:border-slate-800">
                <div class="bg-gradient-to-r from-purple-700 to-indigo-700 px-6 py-4 flex items-center justify-between text-white">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">⏰</span>
                        <div>
                            <h3 class="font-bold text-lg leading-tight">Horarios del Evento y Baile</h3>
                            <p class="text-xs text-purple-200">{{ $event->name }} &bull; Planificación horaria para DJ y equipo</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeScheduleModal" class="text-purple-200 hover:text-white text-2xl font-bold leading-none">&times;</button>
                </div>

                <form wire:submit.prevent="saveSchedule">
                    <div class="p-6 space-y-5">
                        <!-- Sección Destacada: Baile / Barra Libre (24 Horas) -->
                        <div class="bg-purple-50 dark:bg-purple-950/40 p-4 rounded-2xl border border-purple-200 dark:border-purple-800 space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-black uppercase text-purple-900 dark:text-purple-300 tracking-wider flex items-center gap-1.5">
                                    <span>🎧 Horario del Baile / Barra Libre (DJ)</span>
                                </h4>
                                <span class="text-[10px] font-bold bg-purple-200 dark:bg-purple-900 text-purple-800 dark:text-purple-200 px-2 py-0.5 rounded-full">
                                    Formato 24 Horas @if($event->suggested_dance_hours) &bull; Contrato: {{ $event->suggested_dance_hours }}h @endif
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Hora Inicio (24h)</label>
                                    <input type="time" wire:model.live="dance_start_time" class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white rounded-xl text-sm font-bold focus:ring-purple-500 focus:border-purple-500 p-2.5">
                                    <span class="text-[10px] text-gray-500 mt-0.5 block">Ej: 23:30 o 00:30</span>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Duración (Horas)</label>
                                    <div class="relative">
                                        <input type="number" step="0.5" min="0.5" max="24" wire:model.live="dance_duration_hours" class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white rounded-xl text-sm font-bold focus:ring-purple-500 focus:border-purple-500 p-2.5 pr-7">
                                        <span class="absolute right-2.5 top-2.5 text-xs text-gray-400 font-bold">h</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Fin Previsto (24h)</label>
                                    <input type="time" wire:model="dance_end_time" class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white rounded-xl text-sm font-bold focus:ring-purple-500 focus:border-purple-500 p-2.5">
                                    <span class="text-[10px] text-gray-500 mt-0.5 block">Calculado automáticamente</span>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Hora Límite / Máx.</label>
                                    <input type="time" wire:model="max_end_time" class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white rounded-xl text-sm font-bold focus:ring-purple-500 focus:border-purple-500 p-2.5">
                                    <span class="text-[10px] text-purple-700 dark:text-purple-400 mt-0.5 block font-medium">Opcional (Límite finca)</span>
                                </div>
                            </div>

                            <!-- Botones rápidos de duración -->
                            <div>
                                <label class="block text-[10px] font-bold text-purple-800 dark:text-purple-300 uppercase mb-1">Presets de duración:</label>
                                <div class="flex flex-wrap gap-1.5">
                                    <button type="button" wire:click="setPresetDanceDuration(3)" class="text-xs px-2.5 py-1 rounded-lg font-bold transition {{ (float)$dance_duration_hours === 3.0 ? 'bg-purple-600 text-white shadow-xs' : 'bg-white dark:bg-slate-800 text-purple-900 dark:text-purple-200 border border-purple-200 dark:border-purple-700 hover:bg-purple-100' }}">3h</button>
                                    <button type="button" wire:click="setPresetDanceDuration(4)" class="text-xs px-2.5 py-1 rounded-lg font-bold transition {{ (float)$dance_duration_hours === 4.0 ? 'bg-purple-600 text-white shadow-xs' : 'bg-white dark:bg-slate-800 text-purple-900 dark:text-purple-200 border border-purple-200 dark:border-purple-700 hover:bg-purple-100' }}">4h (Estándar)</button>
                                    <button type="button" wire:click="setPresetDanceDuration(5)" class="text-xs px-2.5 py-1 rounded-lg font-bold transition {{ (float)$dance_duration_hours === 5.0 ? 'bg-purple-600 text-white shadow-xs' : 'bg-white dark:bg-slate-800 text-purple-900 dark:text-purple-200 border border-purple-200 dark:border-purple-700 hover:bg-purple-100' }}">5h</button>
                                    <button type="button" wire:click="setPresetDanceDuration(6)" class="text-xs px-2.5 py-1 rounded-lg font-bold transition {{ (float)$dance_duration_hours === 6.0 ? 'bg-purple-600 text-white shadow-xs' : 'bg-white dark:bg-slate-800 text-purple-900 dark:text-purple-200 border border-purple-200 dark:border-purple-700 hover:bg-purple-100' }}">6h</button>
                                </div>
                            </div>
                        </div>

                        <!-- Sección de Montaje (Fecha y Hora específica) -->
                        <div class="bg-amber-50/80 dark:bg-amber-950/30 p-4 rounded-2xl border border-amber-200 dark:border-amber-800 space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-black uppercase text-amber-900 dark:text-amber-300 tracking-wider flex items-center gap-1.5">
                                    <span>🚗 Planificación de Montaje & Acceso</span>
                                </h4>
                                <span class="text-[10px] text-amber-700 dark:text-amber-300 font-semibold">
                                    ¿Se monta el mismo día o el día anterior?
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Fecha de Montaje</label>
                                    <input type="date" wire:model="setup_date" class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white rounded-xl text-sm font-semibold p-2.5">
                                    
                                    <!-- Botones rápidos de fecha de montaje -->
                                    <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                        @if($event->event_date)
                                            <button type="button" wire:click="setPresetSetupDate('same_day')" class="text-[11px] px-2 py-0.5 rounded-lg bg-white dark:bg-slate-800 text-amber-900 dark:text-amber-200 border border-amber-300 dark:border-amber-700 font-bold hover:bg-amber-100">
                                                📅 Mismo día ({{ $event->event_date->format('d/m') }})
                                            </button>
                                            <button type="button" wire:click="setPresetSetupDate('day_before')" class="text-[11px] px-2 py-0.5 rounded-lg bg-white dark:bg-slate-800 text-amber-900 dark:text-amber-200 border border-amber-300 dark:border-amber-700 font-bold hover:bg-amber-100">
                                                ⬅️ Víspera ({{ \Carbon\Carbon::parse($event->event_date)->subDay()->format('d/m') }})
                                            </button>
                                        @endif
                                        @if($setup_date)
                                            <button type="button" wire:click="setPresetSetupDate('clear')" class="text-[10px] text-gray-500 hover:text-rose-600 underline">
                                                Quitar
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Hora Llegada / Montaje (24h)</label>
                                    <input type="time" wire:model="start_time" class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white rounded-xl text-sm font-bold p-2.5">
                                    <span class="text-[10px] text-gray-500 mt-0.5 block">Formato 24h (ej: 11:30 o 17:00)</span>
                                </div>
                            </div>
                        </div>

                        <!-- Cronograma General de las Fases del Evento (Formato 24h) -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-black uppercase text-gray-700 dark:text-gray-300 tracking-wider flex items-center justify-between">
                                <span>📅 Cronograma de Fases del Evento (Formato 24 Horas)</span>
                                <span class="text-[10px] text-gray-400 font-normal">Opcional</span>
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-600 dark:text-gray-400 mb-1">💍 Ceremonia (24h)</label>
                                    <input type="time" wire:model="ceremony_time" class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white rounded-xl text-xs p-2">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-600 dark:text-gray-400 mb-1">🍸 Cóctel (24h)</label>
                                    <input type="time" wire:model="cocktail_time" class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white rounded-xl text-xs p-2">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-600 dark:text-gray-400 mb-1">🍽️ Banquete / Cena (24h)</label>
                                    <input type="time" wire:model="banquet_time" class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white rounded-xl text-xs p-2">
                                </div>
                            </div>
                        </div>

                        <!-- Observaciones de Horarios -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Notas / Observaciones de Horario y Timing</label>
                            <textarea wire:model="schedule_notes" rows="2" placeholder="Ej: Montaje listo antes de las 18:00. Los novios quieren abrir baile a las 23:30 puntuales tras el corte de tarta..." class="w-full border-gray-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white rounded-xl text-xs p-2.5"></textarea>
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-slate-800/80 px-6 py-3 border-t border-gray-100 dark:border-slate-800 flex justify-end gap-2">
                        <button type="button" wire:click="closeScheduleModal" class="bg-white dark:bg-slate-900 hover:bg-gray-100 text-gray-700 dark:text-gray-300 font-bold text-xs px-4 py-2 border border-gray-300 dark:border-slate-700 rounded-xl transition">
                            Cancelar
                        </button>
                        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs px-4 py-2 rounded-xl shadow-xs transition">
                            💾 Guardar Horarios & Montaje
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Editar Datos y Contraseña del Cliente -->
    @if($showClientModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-client-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" wire:click="$set('showClientModal', false)"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-slate-200 dark:border-slate-800">
                <div class="bg-emerald-600 px-6 py-4 flex items-center justify-between text-white">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">👤</span>
                        <div>
                            <h3 class="font-extrabold text-base leading-tight" id="modal-client-title">Gestionar Cliente del Evento</h3>
                            <p class="text-xs text-emerald-100">Evento: {{ $event->name }}</p>
                        </div>
                    </div>
                    <button type="button" wire:click="$set('showClientModal', false)" class="text-emerald-100 hover:text-white text-2xl font-bold leading-none">&times;</button>
                </div>

                <form wire:submit.prevent="saveClientDetails">
                    <div class="p-6 space-y-4 text-xs">
                        <!-- Selector de Cliente Registrado o Crear Nuevo -->
                        <div class="p-3 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-1.5">
                            <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider text-[11px]">
                                Seleccionar Cliente Registrado (O crear nuevo)
                            </label>
                            <select wire:model.live="selected_client_id" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs font-bold text-slate-800 dark:text-white bg-white dark:bg-slate-900">
                                <option value="">+ Crear Nuevo Cliente para este Evento</option>
                                @foreach($allClients as $cItem)
                                    <option value="{{ $cItem->id }}">👤 {{ $cItem->name }} {{ $cItem->phone ? '('.$cItem->phone.')' : '' }} {{ $cItem->email && !str_ends_with($cItem->email, '@eventosmusicales.local') ? '- '.$cItem->email : '' }}</option>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-slate-500">
                                Selecciona un cliente registrado para vincularlo a este evento o mantén "+ Crear Nuevo Cliente" para darlo de alta.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Nombre Completo *</label>
                                <input type="text" wire:model="client_edit_name" required class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs font-semibold text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950">
                                @error('client_edit_name') <span class="text-rose-600 text-[11px] block font-bold mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Teléfono (WhatsApp)</label>
                                <input type="text" wire:model="client_edit_phone" placeholder="612345678" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950">
                                @error('client_edit_phone') <span class="text-rose-600 text-[11px] block font-bold mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Correo Electrónico</label>
                                <input type="email" wire:model="client_edit_email" placeholder="cliente@email.com" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950">
                                @error('client_edit_email') <span class="text-rose-600 text-[11px] block font-bold mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider text-xs">DNI / NIF</label>
                                    @if(!empty($client_edit_dni) && isset($dniValidations['client_edit_dni']))
                                        @if($dniValidations['client_edit_dni']['valid'])
                                            <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/80 px-2 py-0.5 rounded border border-emerald-300 dark:border-emerald-800 flex items-center gap-1">
                                                <span>✓</span> {{ $dniValidations['client_edit_dni']['type'] ?? 'DNI' }} Válido
                                            </span>
                                        @endif
                                    @endif
                                </div>
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        wire:model.live.debounce.300ms="client_edit_dni" 
                                        data-dni-input 
                                        placeholder="12345678Z" 
                                        class="w-full border {{ !empty($client_edit_dni) && isset($dniValidations['client_edit_dni']) ? ($dniValidations['client_edit_dni']['valid'] ? 'border-emerald-500 focus:border-emerald-500 focus:ring-emerald-500' : 'border-amber-400 dark:border-amber-600 focus:border-amber-500 focus:ring-amber-500 bg-amber-50/30') : 'border-slate-300 dark:border-slate-700' }} rounded-xl p-2.5 text-xs font-mono font-bold text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950 uppercase transition"
                                    >
                                    @if(!empty($client_edit_dni) && isset($dniValidations['client_edit_dni']) && $dniValidations['client_edit_dni']['valid'])
                                        <div class="absolute right-3 top-1/2 -translate-y-1/2 text-emerald-500 font-bold text-sm pointer-events-none">
                                            ✓
                                        </div>
                                    @endif
                                </div>
                                @if(!empty($client_edit_dni) && isset($dniValidations['client_edit_dni']) && !$dniValidations['client_edit_dni']['valid'])
                                    <div class="mt-1.5 p-2 bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 rounded-xl flex items-center justify-between text-xs gap-2">
                                        <div class="flex items-center gap-1.5 text-amber-800 dark:text-amber-300 font-semibold leading-tight text-[11px]">
                                            <span>⚠️</span>
                                            <span>{{ $dniValidations['client_edit_dni']['message'] ?? 'Letra de DNI incorrecta.' }}</span>
                                        </div>
                                        @if(!empty($dniValidations['client_edit_dni']['suggested']))
                                            <button 
                                                type="button" 
                                                wire:click="applyDniSuggestion('client_edit_dni', '{{ $dniValidations['client_edit_dni']['suggested'] }}')" 
                                                class="px-2.5 py-1 bg-amber-600 hover:bg-amber-700 active:scale-95 text-white font-bold rounded-lg text-[10px] shadow-xs cursor-pointer transition shrink-0 whitespace-nowrap"
                                                title="Pulsar para sustituir la letra errónea por la letra oficial calculada"
                                            >
                                                Corregir a {{ $dniValidations['client_edit_dni']['suggested'] }}
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Datos de la Pareja / Cónyuge (Novio/Novia) -->
                        <div class="p-3 bg-pink-50/70 dark:bg-pink-950/40 rounded-2xl border border-pink-200 dark:border-pink-900/50 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-pink-900 dark:text-pink-200 uppercase tracking-wider block text-[11px] flex items-center gap-1">
                                    <span>💍</span> Datos de la Pareja / Cónyuge (Novio / Novia)
                                </span>
                                <span class="text-[10px] text-pink-700 dark:text-pink-300 font-bold bg-pink-100 dark:bg-pink-900/60 px-2 py-0.5 rounded-md">2º Contacto</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 mb-1">Nombre Completo Pareja</label>
                                    <input type="text" wire:model="partner_edit_name" placeholder="Ej: Laura / Carlos" class="w-full border-pink-200 dark:border-pink-800 rounded-xl p-2 text-xs font-semibold text-slate-800 dark:text-white bg-white dark:bg-slate-900">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 mb-1">Teléfono Pareja (WhatsApp)</label>
                                    <input type="text" wire:model="partner_edit_phone" placeholder="612345678" class="w-full border-pink-200 dark:border-pink-800 rounded-xl p-2 text-xs text-slate-800 dark:text-white bg-white dark:bg-slate-900">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300 mb-1">Correo Pareja (Opcional)</label>
                                    <input type="email" wire:model="partner_edit_email" placeholder="pareja@email.com" class="w-full border-pink-200 dark:border-pink-800 rounded-xl p-2 text-xs text-slate-800 dark:text-white bg-white dark:bg-slate-900">
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-[10px] font-bold text-slate-700 dark:text-slate-300">DNI / NIF Pareja (Opcional)</label>
                                        @if(!empty($partner_edit_dni) && isset($dniValidations['partner_edit_dni']))
                                            @if($dniValidations['partner_edit_dni']['valid'])
                                                <span class="text-[9px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/80 px-1.5 py-0.2 rounded border border-emerald-300 dark:border-emerald-800 flex items-center gap-0.5">
                                                    <span>✓</span> {{ $dniValidations['partner_edit_dni']['type'] ?? 'DNI' }} Válido
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                    <div class="relative">
                                        <input 
                                            type="text" 
                                            wire:model.live.debounce.300ms="partner_edit_dni" 
                                            data-dni-input 
                                            placeholder="12345678Z" 
                                            class="w-full border {{ !empty($partner_edit_dni) && isset($dniValidations['partner_edit_dni']) ? ($dniValidations['partner_edit_dni']['valid'] ? 'border-emerald-500 focus:border-emerald-500 focus:ring-emerald-500' : 'border-amber-400 dark:border-amber-600 focus:border-amber-500 focus:ring-amber-500 bg-amber-50/30') : 'border-pink-200 dark:border-pink-800' }} rounded-xl p-2 text-xs font-mono font-bold text-slate-800 dark:text-white bg-white dark:bg-slate-900 uppercase transition"
                                        >
                                        @if(!empty($partner_edit_dni) && isset($dniValidations['partner_edit_dni']) && $dniValidations['partner_edit_dni']['valid'])
                                            <div class="absolute right-2.5 top-1/2 -translate-y-1/2 text-emerald-500 font-bold text-xs pointer-events-none">
                                                ✓
                                            </div>
                                        @endif
                                    </div>
                                    @if(!empty($partner_edit_dni) && isset($dniValidations['partner_edit_dni']) && !$dniValidations['partner_edit_dni']['valid'])
                                        <div class="mt-1 p-1.5 bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 rounded-lg flex items-center justify-between text-[10px] gap-1.5">
                                            <div class="text-amber-800 dark:text-amber-300 font-semibold leading-tight">
                                                <span>⚠️ {{ $dniValidations['partner_edit_dni']['message'] ?? 'Letra incorrecta.' }}</span>
                                            </div>
                                            @if(!empty($dniValidations['partner_edit_dni']['suggested']))
                                                <button 
                                                    type="button" 
                                                    wire:click="applyDniSuggestion('partner_edit_dni', '{{ $dniValidations['partner_edit_dni']['suggested'] }}')" 
                                                    class="px-2 py-0.5 bg-amber-600 hover:bg-amber-700 active:scale-95 text-white font-bold rounded text-[9px] shadow-xs cursor-pointer transition shrink-0 whitespace-nowrap"
                                                >
                                                    Corregir a {{ $dniValidations['partner_edit_dni']['suggested'] }}
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Dirección y Población -->
                        <div class="p-3 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-2">
                            <span class="font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider block text-[11px]">Dirección y Residencia</span>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-1">Código Postal (CP)</label>
                                    <input type="text" wire:model.live.debounce.300ms="client_edit_postal_code" placeholder="26370" maxlength="5" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2 text-xs font-bold text-slate-800 dark:text-white bg-white dark:bg-slate-900">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-1">Población / Ciudad</label>
                                    <input type="text" wire:model="client_edit_city" placeholder="Navarrete" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2 text-xs text-slate-800 dark:text-white bg-white dark:bg-slate-900">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-1">Provincia</label>
                                    <input type="text" wire:model="client_edit_province" placeholder="La Rioja" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2 text-xs text-slate-800 dark:text-white bg-white dark:bg-slate-900">
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-1">Dirección (Calle, Piso...)</label>
                                    <input type="text" wire:model="client_edit_address" placeholder="Calle Mayor 12" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2 text-xs text-slate-800 dark:text-white bg-white dark:bg-slate-900">
                                </div>
                            </div>
                        </div>

                        <!-- Contraseña (Opcional) -->
                        <div class="p-3 bg-indigo-50/50 dark:bg-indigo-950/30 rounded-2xl border border-indigo-200 dark:border-indigo-900/60 space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="font-bold text-indigo-950 dark:text-indigo-200 uppercase tracking-wider text-[11px] flex items-center gap-1">
                                    <span>🔑</span> Contraseña del Cliente (Opcional)
                                </label>
                                <span class="text-[10px] text-indigo-500 font-normal">Dejar en blanco para no cambiar</span>
                            </div>
                            <input 
                                type="text" 
                                wire:model="client_edit_password" 
                                placeholder="Escribe nueva contraseña..." 
                                class="w-full border-indigo-200 dark:border-indigo-800 rounded-xl p-2.5 text-xs text-slate-800 dark:text-white bg-white dark:bg-slate-900 font-mono"
                            >
                            @error('client_edit_password') <span class="text-rose-600 text-[11px] block font-bold mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-slate-950 px-6 py-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                        @if($event->client_id)
                            <button type="button" wire:click="unlinkClient" wire:confirm="¿Seguro que deseas desvincular al cliente de este evento?" class="bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs px-3 py-2 rounded-xl transition cursor-pointer">
                                🔗 Desvincular
                            </button>
                        @else
                            <div></div>
                        @endif

                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="$set('showClientModal', false)" class="bg-white dark:bg-slate-800 hover:bg-slate-100 text-slate-700 dark:text-slate-300 font-bold text-xs px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl transition">
                                Cancelar
                            </button>
                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl shadow-md transition cursor-pointer">
                                💾 Asignar / Guardar Cliente
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Importar Carpeta de Google Drive / OneDrive -->
    @if($showCloudImportModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-cloud-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-xs transition-opacity" wire:click="$set('showCloudImportModal', false)"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-200 dark:border-slate-800">
                <div class="bg-sky-600 px-6 py-4 flex items-center justify-between text-white">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">📁</span>
                        <div>
                            <h3 class="font-black text-base leading-tight" id="modal-cloud-title">Importar Carpeta de Música desde la Nube</h3>
                            <p class="text-xs text-sky-100">Google Drive &bull; OneDrive &bull; Dropbox</p>
                        </div>
                    </div>
                    <button type="button" wire:click="$set('showCloudImportModal', false)" class="text-sky-100 hover:text-white text-2xl font-bold leading-none">&times;</button>
                </div>

                <div class="p-6 space-y-5 text-xs">
                    <!-- Instrucciones rápidas -->
                    <div class="p-3.5 bg-sky-50 dark:bg-sky-950/40 rounded-2xl border border-sky-200 dark:border-sky-900/60 text-sky-950 dark:text-sky-200 space-y-1">
                        <p class="font-bold flex items-center gap-1.5">
                            <span>💡</span> ¿Cómo importar una carpeta de Google Drive en 1 clic?
                        </p>
                        <p class="text-[11px] leading-relaxed text-sky-900 dark:text-sky-300">
                            1. Sube tus canciones (MP3/WAV) a una carpeta de Google Drive.<br>
                            2. Haz clic derecho en la carpeta ➔ Compartir ➔ <strong>"Cualquier persona con el enlace puede ver"</strong>.<br>
                            3. Pega el enlace aquí abajo y pulsa <strong>"Escanear Carpeta"</strong>.
                        </p>
                    </div>

                    <!-- Input Enlace de la Carpeta -->
                    <div class="space-y-1.5">
                        <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider text-[11px]">
                            Enlace o ID de la Carpeta de Google Drive *
                        </label>
                        <div class="flex gap-2">
                            <input 
                                type="url" 
                                wire:model="cloudFolderUrl" 
                                placeholder="https://drive.google.com/drive/folders/1ABC123_xyz..." 
                                class="flex-1 border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950 font-mono"
                            >
                            <button 
                                type="button" 
                                wire:click="scanCloudFolder" 
                                wire:loading.attr="disabled"
                                class="px-4 py-2.5 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-xl shadow-md transition flex items-center gap-1.5 cursor-pointer shrink-0"
                            >
                                <span wire:loading.remove wire:target="scanCloudFolder">🔍 Escanear</span>
                                <span wire:loading wire:target="scanCloudFolder" class="animate-pulse">Escaneando...</span>
                            </button>
                        </div>
                        @error('cloudFolderUrl') <span class="text-rose-600 text-[11px] block font-bold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Mensaje de Estado / Resultado del escaneo -->
                    @if($cloudImportStatus)
                        <div class="p-3 rounded-xl border text-xs font-semibold {{ $cloudImportStatus['success'] ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' }}">
                            {{ $cloudImportStatus['message'] }}
                        </div>
                    @endif

                    <!-- Categoría y Momento destino (Adaptados al tipo de evento) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3.5 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider text-[10px] mb-1">
                                Categoría de Destino
                            </label>
                            <select wire:model="cloudImportCategory" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2 text-xs font-bold text-slate-800 dark:text-white bg-white dark:bg-slate-900">
                                @foreach($event->suggested_categories as $cKey => $cLabel)
                                    <option value="{{ $cKey }}">{{ $cLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider text-[10px] mb-1">
                                Momento / Etiqueta
                            </label>
                            <input 
                                type="text" 
                                list="cloud_moments_list"
                                wire:model="cloudImportMoment" 
                                placeholder="Ej: Baile / Fiesta, Ambiente..." 
                                class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2 text-xs font-semibold text-slate-800 dark:text-white bg-white dark:bg-slate-900"
                            >
                            <datalist id="cloud_moments_list">
                                @foreach($event->suggested_moments as $sMoment)
                                    <option value="{{ $sMoment }}">
                                @endforeach
                            </datalist>
                        </div>
                    </div>

                    <!-- Vista previa de canciones detectadas -->
                    @if(!empty($cloudScannedFiles))
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-black text-slate-800 dark:text-slate-200 uppercase tracking-wider text-[11px]">
                                    Canciones Detectadas ({{ count($cloudScannedFiles) }})
                                </span>
                                <span class="text-[11px] text-emerald-600 font-bold">Listas para importar con audio 100% completo</span>
                            </div>
                            <div class="max-h-48 overflow-y-auto rounded-xl border border-slate-200 dark:border-slate-800 divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-950 p-1">
                                @foreach($cloudScannedFiles as $file)
                                    <div class="p-2 flex items-center justify-between gap-3 text-xs">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span class="text-base">🎵</span>
                                            <div class="truncate">
                                                <p class="font-bold text-slate-900 dark:text-white truncate">{{ $file['title'] }}</p>
                                                @if(!empty($file['artist']))
                                                    <p class="text-[10px] text-slate-400 truncate">{{ $file['artist'] }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        <span class="text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-mono px-2 py-0.5 rounded-full shrink-0">
                                            Direct MP3
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="bg-gray-50 dark:bg-slate-950 px-6 py-3 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-2">
                    <button type="button" wire:click="$set('showCloudImportModal', false)" class="bg-white dark:bg-slate-800 hover:bg-slate-100 text-slate-700 dark:text-slate-300 font-bold text-xs px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl transition">
                        Cancelar
                    </button>
                    @if(!empty($cloudScannedFiles))
                        <button 
                            type="button" 
                            wire:click="confirmImportCloudFolder" 
                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs px-5 py-2.5 rounded-xl shadow-md transition flex items-center gap-1.5 cursor-pointer"
                        >
                            <span>📥</span>
                            <span>Importar {{ count($cloudScannedFiles) }} Canciones a la Escaleta</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal de Registro de Señal / Pago de Reserva -->
    @if($showDepositModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl max-w-lg w-full border border-slate-100 dark:border-slate-800 overflow-hidden animate-in fade-in zoom-in duration-200">
            <!-- Cabecera del Modal -->
            <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 text-white px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">💳</span>
                    <div>
                        <h3 class="font-black text-base leading-tight">Registrar Cobro de Reserva / Señal</h3>
                        <p class="text-xs text-emerald-100">{{ $event->name }} &bull; {{ $event->client ? $event->client->name : 'Cliente' }}</p>
                    </div>
                </div>
                <button type="button" wire:click="closeDepositModal" class="text-emerald-100 hover:text-white text-2xl font-bold leading-none cursor-pointer">&times;</button>
            </div>

            <!-- Contenido del Formulario -->
            <div class="p-6 space-y-4 text-xs">
                <!-- Info resumen rápido -->
                <div class="p-3 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 grid grid-cols-2 gap-3 text-center">
                    <div>
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Total Presupuesto</span>
                        <strong class="text-sm font-black text-slate-800 dark:text-slate-100">{{ number_format($quoteAmount, 2, ',', '.') }} €</strong>
                    </div>
                    <div>
                        <span class="block text-[10px] uppercase font-bold text-emerald-600">Señal Propuesta</span>
                        <strong class="text-sm font-black text-emerald-600">{{ number_format($signalAmount, 2, ',', '.') }} €</strong>
                    </div>
                </div>

                <!-- Input Cantidad Pagada -->
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider text-[11px] mb-1">
                        Importe Cobrado de la Señal (€) *
                    </label>
                    <div class="relative rounded-xl shadow-2xs">
                        <input 
                            type="number" 
                            step="0.01" 
                            min="0"
                            wire:model.live="deposit_amount_input" 
                            class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-3 pr-10 text-base font-black text-emerald-700 dark:text-emerald-400 bg-white dark:bg-slate-950 focus:ring-2 focus:ring-emerald-500" 
                            placeholder="Ej: 200.00"
                        >
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400 font-bold">
                            €
                        </div>
                    </div>
                    @error('deposit_amount_input') <span class="text-rose-600 text-[11px] block font-bold mt-1">{{ $message }}</span> @enderror

                    <!-- Accesos rápidos de importe -->
                    @if($quoteAmount > 0)
                        <div class="flex flex-wrap items-center gap-1.5 mt-2">
                            <span class="text-[10px] text-slate-400 font-bold">Atajos:</span>
                            <button type="button" wire:click="$set('deposit_amount_input', 0)" class="text-[10px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 px-2 py-0.5 rounded-lg transition cursor-pointer">
                                Sin Señal (0 €)
                            </button>
                            @if($signalAmount > 0)
                                <button type="button" wire:click="$set('deposit_amount_input', {{ $signalAmount }})" class="text-[10px] font-bold bg-slate-100 hover:bg-emerald-100 text-slate-700 hover:text-emerald-800 border border-slate-200 px-2 py-0.5 rounded-lg transition cursor-pointer">
                                    Señal Propuesta ({{ number_format($signalAmount, 2, ',', '.') }} €)
                                </button>
                            @endif
                            @if($quoteAmount != $signalAmount)
                                <button type="button" wire:click="$set('deposit_amount_input', {{ round($quoteAmount * 0.5, 2) }})" class="text-[10px] font-bold bg-slate-100 hover:bg-emerald-100 text-slate-700 hover:text-emerald-800 border border-slate-200 px-2 py-0.5 rounded-lg transition cursor-pointer">
                                    50% ({{ number_format($quoteAmount * 0.5, 2, ',', '.') }} €)
                                </button>
                                <button type="button" wire:click="$set('deposit_amount_input', {{ $quoteAmount }})" class="text-[10px] font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 px-2 py-0.5 rounded-lg transition cursor-pointer">
                                    100% Total ({{ number_format($quoteAmount, 2, ',', '.') }} €)
                                </button>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Método de Pago -->
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider text-[11px] mb-1.5">
                        Método de Pago Utilizado *
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition {{ $deposit_method_input === 'bizum' ? 'bg-emerald-50 border-emerald-400 text-emerald-900 font-bold shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100' }}">
                            <input type="radio" wire:model.live="deposit_method_input" value="bizum" class="text-emerald-600 focus:ring-emerald-500">
                            <span>📱 Bizum</span>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition {{ $deposit_method_input === 'transfer' ? 'bg-emerald-50 border-emerald-400 text-emerald-900 font-bold shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100' }}">
                            <input type="radio" wire:model.live="deposit_method_input" value="transfer" class="text-emerald-600 focus:ring-emerald-500">
                            <span>🏦 Transferencia</span>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition {{ $deposit_method_input === 'cash' ? 'bg-emerald-50 border-emerald-400 text-emerald-900 font-bold shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100' }}">
                            <input type="radio" wire:model.live="deposit_method_input" value="cash" class="text-emerald-600 focus:ring-emerald-500">
                            <span>💵 Efectivo</span>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition {{ $deposit_method_input === 'card' ? 'bg-emerald-50 border-emerald-400 text-emerald-900 font-bold shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100' }}">
                            <input type="radio" wire:model.live="deposit_method_input" value="card" class="text-emerald-600 focus:ring-emerald-500">
                            <span>💳 Tarjeta / TPV</span>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition {{ $deposit_method_input === 'other' ? 'bg-emerald-50 border-emerald-400 text-emerald-900 font-bold shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100' }}">
                            <input type="radio" wire:model.live="deposit_method_input" value="other" class="text-emerald-600 focus:ring-emerald-500">
                            <span>💶 Otro</span>
                        </label>
                    </div>
                    @error('deposit_method_input') <span class="text-rose-600 text-[11px] block font-bold mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Fecha del Pago -->
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider text-[11px] mb-1">
                        Fecha en que se Realizó el Pago *
                    </label>
                    <input 
                        type="date" 
                        wire:model="deposit_date_input" 
                        class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs font-semibold text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950"
                    >
                    @error('deposit_date_input') <span class="text-rose-600 text-[11px] block font-bold mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Notas del Pago -->
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider text-[11px] mb-1">
                        Notas u Observaciones del Pago (Opcional)
                    </label>
                    <input 
                        type="text" 
                        wire:model="deposit_notes_input" 
                        placeholder="Ej: Recibido por Bizum del novio, ref #49823" 
                        class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950"
                    >
                </div>

                <!-- Checkbox Generar Recibo Automático -->
                <div class="p-3 bg-emerald-50/60 dark:bg-emerald-950/30 rounded-xl border border-emerald-200 dark:border-emerald-800/50 flex items-start gap-2.5">
                    <input 
                        type="checkbox" 
                        id="gen_rec_chk" 
                        wire:model="generate_receipt_checkbox" 
                        class="mt-0.5 rounded text-emerald-600 focus:ring-emerald-500"
                    >
                    <label for="gen_rec_chk" class="text-xs text-emerald-950 dark:text-emerald-200 cursor-pointer">
                        <strong class="font-bold block">🧾 Generar Recibo de Cobro Pagado automáticamente</strong>
                        <span class="text-[11px] text-emerald-800 dark:text-emerald-400">Creará un documento de recibo oficial pagado en la pestaña de Facturas para entregar al cliente.</span>
                    </label>
                </div>

                <!-- Resumen de Restante -->
                @if($quoteAmount > 0)
                    @php
                        $tempPaid = (float)($deposit_amount_input ?: 0);
                        $tempRemaining = max(0, $quoteAmount - $tempPaid);
                    @endphp
                    <div class="p-3 bg-slate-100 dark:bg-slate-800 rounded-xl flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-600 dark:text-slate-300">Pendiente para el día del evento:</span>
                        <strong class="font-black text-sm {{ $tempRemaining == 0 ? 'text-emerald-600' : 'text-slate-900 dark:text-white' }}">
                            {{ number_format($tempRemaining, 2, ',', '.') }} €
                            @if($tempRemaining == 0)
                                <span class="text-[10px] font-bold text-emerald-700 ml-1">(Pagado al 100%)</span>
                            @endif
                        </strong>
                    </div>
                @endif
            </div>

            <!-- Footer del Modal -->
            <div class="bg-gray-50 dark:bg-slate-950 px-6 py-3.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    @if($event->deposit_paid && $event->deposit_paid_amount > 0)
                        <button 
                            type="button" 
                            wire:click="removeDeposit" 
                            wire:confirm="¿Seguro que deseas anular/quitar el registro de la señal cobrada?"
                            class="text-[11px] text-rose-600 hover:text-rose-800 font-bold hover:underline cursor-pointer"
                        >
                            🗑️ Resetear Señal
                        </button>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <button 
                        type="button" 
                        wire:click="closeDepositModal" 
                        class="bg-white dark:bg-slate-800 hover:bg-slate-100 text-slate-700 dark:text-slate-300 font-bold text-xs px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl transition cursor-pointer"
                    >
                        Cancelar
                    </button>
                    <button 
                        type="button" 
                        wire:click="saveDepositAndStatus" 
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs px-5 py-2.5 rounded-xl shadow-md transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <span>✅</span>
                        <span>Confirmar y Guardar Cobro</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Cobro Final / Liquidación al Completar Evento -->
    @if($showFinalPaymentModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-final-pay-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" wire:click="closeFinalPaymentModal"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 dark:border-slate-800">
                <div class="bg-blue-600 px-6 py-4 flex items-center justify-between text-white">
                    <div class="flex items-center gap-2.5">
                        <span class="text-2xl">🔵</span>
                        <div>
                            <h3 class="font-extrabold text-base leading-tight" id="modal-final-pay-title">Completar Evento y Cobro Restante</h3>
                            <p class="text-xs text-blue-100">Evento: {{ $event->name }}</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeFinalPaymentModal" class="text-blue-100 hover:text-white text-2xl font-bold leading-none cursor-pointer">&times;</button>
                </div>

                <div class="p-6 space-y-4 text-xs">
                    <p class="text-slate-600 dark:text-slate-300">
                        Al marcar el evento como <strong>Completado</strong>, puedes registrar el importe restante/liquidación cobrada el día del evento y generar automáticamente el <strong>recibo oficial pagado</strong> (sin alterar la numeración consecutiva de las facturas).
                    </p>

                    <!-- Importe Cobro Final -->
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                            Importe Cobrado Restante (€) *
                        </label>
                        <div class="relative rounded-xl shadow-xs">
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                wire:model="final_payment_amount" 
                                class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-3 pr-10 text-base font-black text-slate-900 dark:text-white bg-slate-50 dark:bg-slate-950"
                                placeholder="0.00"
                            >
                            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400 font-bold">€</div>
                        </div>
                        @error('final_payment_amount') <span class="text-rose-600 text-[11px] block font-bold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Método de Pago -->
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                            Método de Pago *
                        </label>
                        <select wire:model="final_payment_method" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs font-bold text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950">
                            <option value="cash">💵 Efectivo en mano (al DJ/Técnico)</option>
                            <option value="bizum">📱 Bizum</option>
                            <option value="transfer">🏦 Transferencia Bancaria</option>
                            <option value="card">💳 Tarjeta / TPV</option>
                            <option value="other">📝 Otro método</option>
                        </select>
                        @error('final_payment_method') <span class="text-rose-600 text-[11px] block font-bold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Fecha de Cobro -->
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                            Fecha de Liquidación *
                        </label>
                        <input 
                            type="date" 
                            wire:model="final_payment_date" 
                            class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs font-semibold text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950"
                        >
                        @error('final_payment_date') <span class="text-rose-600 text-[11px] block font-bold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Notas del Pago -->
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                            Observaciones / Referencia (Opcional)
                        </label>
                        <input 
                            type="text" 
                            wire:model="final_payment_notes" 
                            placeholder="Ej: Pago restante en efectivo entregado al DJ al finalizar el evento" 
                            class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950"
                        >
                    </div>

                    <!-- Checkbox Generar Recibo Automático -->
                    <div class="p-3 bg-blue-50/60 dark:bg-blue-950/30 rounded-xl border border-blue-200 dark:border-blue-800/50 flex items-start gap-2.5">
                        <input 
                            type="checkbox" 
                            id="gen_final_rec_chk" 
                            wire:model="generate_final_receipt_checkbox" 
                            class="mt-0.5 rounded text-blue-600 focus:ring-blue-500"
                        >
                        <label for="gen_final_rec_chk" class="text-xs text-blue-950 dark:text-blue-200 cursor-pointer">
                            <strong class="font-bold block">🧾 Generar Recibo de Cobro Pagado automáticamente</strong>
                            <span class="text-[11px] text-blue-800 dark:text-blue-400">Creará un documento oficial de recibo (REC-XXXXX) en Facturas. No afecta a las facturas oficiales para que sigan su orden correlativo.</span>
                        </label>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-slate-950 px-6 py-3.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2">
                    <button 
                        type="button" 
                        wire:click="closeFinalPaymentModal" 
                        class="bg-white dark:bg-slate-800 hover:bg-slate-100 text-slate-700 dark:text-slate-300 font-bold text-xs px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl transition cursor-pointer"
                    >
                        Cancelar
                    </button>
                    <button 
                        type="button" 
                        wire:click="saveFinalPaymentAndComplete" 
                        class="bg-blue-600 hover:bg-blue-700 text-white font-black text-xs px-5 py-2.5 rounded-xl shadow-md transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <span>🔵</span>
                        <span>Completar Evento y Guardar</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- ========================================================= -->
    <!-- MODAL IMPORTADOR INTELIGENTE DE CANCIONES (PDF / WHATSAPP)-->
    <!-- ========================================================= -->
    @if($showImportModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4">
        <div class="relative w-full max-w-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200 flex flex-col max-h-[90vh]">
            
            <!-- HEADER -->
            <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-gradient-to-r from-emerald-50 via-teal-50 to-white dark:from-emerald-950/40 dark:via-slate-900 dark:to-slate-900">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 dark:bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-2xl">
                        📄
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <span>Importador Inteligente de Canciones</span>
                            <span class="text-[10px] bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 font-bold px-2 py-0.5 rounded-full uppercase">Auto-Detección</span>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Sube un PDF enviado por el cliente o pega el mensaje de WhatsApp. El sistema extraerá títulos y artistas automáticamente.
                        </p>
                    </div>
                </div>
                <button type="button" wire:click="$set('showImportModal', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-2xl p-1 cursor-pointer">&times;</button>
            </div>

            <!-- CONTENIDO SCROLLABLE -->
            <div class="p-6 overflow-y-auto space-y-5 flex-1">
                
                @if (session()->has('import_error'))
                    <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl text-xs font-bold flex items-center gap-2 shadow-xs">
                        <span>⚠️</span> {{ session('import_error') }}
                    </div>
                @endif

                <!-- SELECTOR DE ENTRADA: ARCHIVO O TEXTO -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <!-- OPCIÓN 1: SUBIR PDF / DOCUMENTO -->
                    <div class="p-4 rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-950/40 space-y-2 flex flex-col justify-between">
                        <div>
                            <span class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider block mb-1">
                                📎 Opción A: Subir Archivo PDF / TXT
                            </span>
                            <p class="text-[11px] text-slate-400">
                                Sube el PDF que te envió el cliente con la lista de canciones.
                            </p>
                        </div>

                        <div class="pt-2">
                            <input 
                                type="file" 
                                wire:model="importFile" 
                                accept=".pdf,.txt,.csv" 
                                class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-600 file:text-white hover:file:bg-emerald-500 cursor-pointer"
                            >
                            <div wire:loading wire:target="importFile" class="text-[11px] text-emerald-600 font-bold mt-1.5 flex items-center gap-1.5">
                                <span class="animate-spin text-sm">⏳</span> Leyendo documento...
                            </div>
                        </div>
                    </div>

                    <!-- OPCIÓN 2: CONFIGURACIÓN POR DEFECTO -->
                    <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950/60 space-y-3">
                        <span class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider block">
                            🎯 Destino de las Canciones
                        </span>
                        
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 mb-1">Fase / Categoría</label>
                                <select wire:model="importDefaultCategory" class="w-full text-xs border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 font-medium">
                                    <option value="baile">🎉 Baile & Fiesta</option>
                                    <option value="coctel">🍷 Cóctel</option>
                                    <option value="banquete">🍽️ Banquete</option>
                                    <option value="ceremonia">💍 Ceremonia</option>
                                    <option value="general">🎵 General</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 mb-1">Momento / Hito</label>
                                <input type="text" wire:model="importDefaultMoment" placeholder="Ej: Baile & Fiesta" class="w-full text-xs border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 font-medium">
                            </div>
                        </div>
                    </div>

                </div>

                <!-- TEXTAREA PARA PEGAR TEXTO DE WHATSAPP -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider">
                            📋 Opción B: Pegar Texto Directo (WhatsApp / Word / Email)
                        </label>
                        @if(!empty($importRawText))
                            <button type="button" wire:click="$set('importRawText', '')" class="text-[11px] text-rose-500 hover:underline">
                                Borrar texto
                            </button>
                        @endif
                    </div>
                    <textarea 
                        wire:model.live.debounce.400ms="importRawText" 
                        wire:change="parseImportInput"
                        rows="5" 
                        placeholder="Pega aquí el texto... Ej:&#10;1 La morocha Luck Ra y BM&#10;2 Fiesta pagana Mägo de Oz&#10;3 Y volar La Pegatina&#10;O también: Mecano - Cruz de navajas" 
                        class="w-full border-slate-200 dark:border-slate-700 rounded-2xl p-3.5 text-xs font-mono bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500"
                    ></textarea>
                </div>

                <!-- BOTÓN PROCESAR TEXTO -->
                <div class="flex items-center justify-between">
                    <button 
                        type="button" 
                        wire:click="parseImportInput" 
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <span>🔍</span> Analizar y Extraer Canciones
                    </button>
                    @if(count($parsedImportTracks) > 0)
                        <span class="text-xs font-black text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/80 px-3 py-1 rounded-xl border border-emerald-200 dark:border-emerald-800">
                            ✨ {{ count($parsedImportTracks) }} canciones detectadas
                        </span>
                    @endif
                </div>

                <!-- NOTAS ADICIONALES DETECTADAS (ESTILOS / COMENTARIOS) -->
                @if(!empty($parsedImportNotes))
                    <div class="p-4 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 rounded-2xl space-y-1.5">
                        <strong class="text-xs font-black text-amber-900 dark:text-amber-300 flex items-center gap-1.5">
                            <span>💡</span> Estilos y Notas Especiales Detectadas:
                        </strong>
                        <ul class="list-disc list-inside text-xs text-amber-800 dark:text-amber-400 space-y-0.5 pl-2">
                            @foreach($parsedImportNotes as $note)
                                <li>{{ $note }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- PREVISUALIZACIÓN DE CANCIONES A IMPORTAR -->
                @if(count($parsedImportTracks) > 0)
                    <div class="space-y-2.5">
                        <h4 class="text-xs font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                            Lista de Canciones Extraídas (Marca o desmarca las que quieras)
                        </h4>

                        <div class="max-h-64 overflow-y-auto border border-slate-200 dark:border-slate-800 rounded-2xl divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-950 shadow-inner">
                            @foreach($parsedImportTracks as $idx => $item)
                                <div class="p-3 flex items-center justify-between gap-3 hover:bg-slate-50 dark:hover:bg-slate-900/60 transition">
                                    <div class="flex items-center gap-3 min-w-0 flex-1">
                                        <input 
                                            type="checkbox" 
                                            wire:model="parsedImportTracks.{{ $idx }}.selected" 
                                            class="rounded text-emerald-600 focus:ring-emerald-500 cursor-pointer h-4 w-4 shrink-0"
                                        >
                                        <span class="text-xs font-mono font-bold text-slate-400 w-6 shrink-0">#{{ $loop->iteration }}</span>
                                        <div class="truncate">
                                            <input 
                                                type="text" 
                                                wire:model="parsedImportTracks.{{ $idx }}.title" 
                                                placeholder="Título" 
                                                class="text-xs font-bold text-slate-900 dark:text-white bg-transparent border-0 p-0 focus:ring-0 w-full"
                                            >
                                            <input 
                                                type="text" 
                                                wire:model="parsedImportTracks.{{ $idx }}.artist" 
                                                placeholder="Artista (Opcional)" 
                                                class="text-[11px] text-slate-500 bg-transparent border-0 p-0 focus:ring-0 w-full"
                                            >
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0">
                                        <input 
                                            type="text" 
                                            wire:model="parsedImportTracks.{{ $idx }}.moment" 
                                            placeholder="Momento" 
                                            class="text-[11px] border border-slate-200 dark:border-slate-700 rounded-lg px-2 py-1 bg-slate-50 dark:bg-slate-900 text-slate-700 dark:text-slate-300 w-28"
                                        >
                                        <button 
                                            type="button" 
                                            wire:click="removeParsedTrack({{ $idx }})" 
                                            class="text-rose-500 hover:text-rose-700 text-xs font-bold px-2 py-1 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950 transition cursor-pointer"
                                            title="Quitar de la lista"
                                        >
                                            &times;
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>

            <!-- FOOTER -->
            <div class="p-4 bg-slate-50 dark:bg-slate-950 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3">
                <button 
                    type="button" 
                    wire:click="$set('showImportModal', false)" 
                    class="px-4 py-2.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-100 text-slate-700 dark:text-slate-300 font-bold text-xs border border-slate-200 dark:border-slate-700 transition cursor-pointer"
                >
                    Cancelar
                </button>

                <button 
                    type="button" 
                    wire:click="executeImport" 
                    wire:loading.attr="disabled"
                    :disabled="{{ count($parsedImportTracks) === 0 ? 'true' : 'false' }}"
                    class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 disabled:opacity-50 text-white font-black text-xs shadow-lg transition flex items-center gap-2 cursor-pointer"
                >
                    <span wire:loading.remove wire:target="executeImport">
                        ⚡ Importar a la Escaleta ({{ count(array_filter($parsedImportTracks, fn($t) => !empty($t['selected']))) }})
                    </span>
                    <span wire:loading wire:target="executeImport" class="inline-flex items-center gap-1.5">
                        <span class="animate-spin text-sm">⏳</span> Procesando y buscando carátulas...
                    </span>
                </button>
            </div>

        </div>
    </div>
    @endif

    <!-- Modal Programar / Editar Cita o Reunión -->
    @if($showMeetingModal)
    <div class="fixed z-40 inset-0 overflow-y-auto" aria-labelledby="modal-meeting-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-xs transition-opacity" aria-hidden="true" wire:click="$set('showMeetingModal', false)"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-200 dark:border-slate-800">
                
                <form wire:submit.prevent="saveMeeting">
                    <!-- HEADER -->
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 flex items-center justify-between text-white">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">📅</span>
                            <div>
                                <h3 class="text-base font-black" id="modal-meeting-title">
                                    {{ $editing_meeting_id ? '✏️ Modificar Cita / Reunión' : '📅 Programar Cita / Reunión' }}
                                </h3>
                                <p class="text-[11px] text-blue-100 font-medium">
                                    Evento: {{ $event->name }}
                                </p>
                            </div>
                        </div>
                        <button type="button" wire:click="$set('showMeetingModal', false)" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <!-- BODY -->
                    <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                        <!-- Fila 1: Título y Tipo -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Título de la Cita *
                                </label>
                                <input 
                                    type="text" 
                                    wire:model="meeting_title" 
                                    placeholder="Ej: Reunión Escaleta Musical"
                                    class="w-full border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl text-xs font-medium p-2.5 focus:ring-2 focus:ring-blue-500"
                                    required
                                >
                                @error('meeting_title') <span class="text-rose-500 text-[10px]">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Tipo de Reunión *
                                </label>
                                <select 
                                    wire:model="meeting_type" 
                                    class="w-full border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5 focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="escaleta_musica">🎵 Escaleta Musical & Momentos</option>
                                    <option value="primera_toma">☕ Primera Toma de Contacto</option>
                                    <option value="visita_tecnica">🏰 Visita Técnica a Finca / Salón</option>
                                    <option value="magia_guion">🎩 Guion & Magia (Mago Leugim)</option>
                                    <option value="otro">📅 Reunión General</option>
                                </select>
                                @error('meeting_type') <span class="text-rose-500 text-[10px]">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Fila 2: Fecha, Hora y Duración -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    📅 Fecha *
                                </label>
                                <input 
                                    type="date" 
                                    wire:model="meeting_date" 
                                    class="w-full border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl text-xs font-medium p-2.5 focus:ring-2 focus:ring-blue-500"
                                    required
                                >
                                @error('meeting_date') <span class="text-rose-500 text-[10px]">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    ⏰ Hora *
                                </label>
                                <input 
                                    type="time" 
                                    wire:model="meeting_time" 
                                    class="w-full border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl text-xs font-medium p-2.5 focus:ring-2 focus:ring-blue-500"
                                    required
                                >
                                @error('meeting_time') <span class="text-rose-500 text-[10px]">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    ⏱️ Duración
                                </label>
                                <select 
                                    wire:model="meeting_duration_minutes" 
                                    class="w-full border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl text-xs font-medium p-2.5 focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="15">15 minutos</option>
                                    <option value="30">30 minutos</option>
                                    <option value="45">45 minutos</option>
                                    <option value="60">1 hora</option>
                                    <option value="90">1 hora y media</option>
                                    <option value="120">2 horas</option>
                                </select>
                            </div>
                        </div>

                        <!-- Fila 3: Modalidad y Ubicación / Enlace -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    📍 Modalidad *
                                </label>
                                <select 
                                    wire:model.live="meeting_location_type" 
                                    class="w-full border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5 focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="in_person">📍 Presencial (En persona / Finca / Oficina)</option>
                                    <option value="video_call">💻 Videollamada Online (Meet / Zoom)</option>
                                    <option value="phone">📱 Llamada Telefónica</option>
                                </select>
                            </div>

                            <div>
                                @if($meeting_location_type === 'video_call')
                                    <label class="block text-xs font-bold text-blue-600 dark:text-blue-400 mb-1">
                                        🔗 Enlace de Videollamada
                                    </label>
                                    <input 
                                        type="url" 
                                        wire:model="meeting_video_call_url" 
                                        placeholder="https://meet.google.com/... o Zoom"
                                        class="w-full border-blue-300 dark:border-blue-700 bg-blue-50/40 dark:bg-blue-950/30 text-slate-900 dark:text-white rounded-xl text-xs font-medium p-2.5 focus:ring-2 focus:ring-blue-500"
                                    >
                                @elseif($meeting_location_type === 'phone')
                                    <label class="block text-xs font-bold text-slate-500 mb-1">
                                        📱 Teléfono Cliente
                                    </label>
                                    <div class="w-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-bold p-2.5 border border-slate-200 dark:border-slate-700">
                                        {{ $event->client ? ($event->client->phone ?: 'Sin teléfono registrado') : 'Sin cliente' }}
                                    </div>
                                @else
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                        📍 Lugar / Dirección
                                    </label>
                                    <input 
                                        type="text" 
                                        wire:model="meeting_location" 
                                        placeholder="Ej: Finca El Olivar o Cafetería Central"
                                        class="w-full border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl text-xs font-medium p-2.5 focus:ring-2 focus:ring-blue-500"
                                    >
                                @endif
                            </div>
                        </div>

                        <!-- Fila 4: Staff asignado y Estado -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    👤 Miembro del Equipo que atiende
                                </label>
                                <select 
                                    wire:model="meeting_staff_id" 
                                    class="w-full border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl text-xs font-medium p-2.5 focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="">-- Cualquiera / Sin asignar --</option>
                                    @foreach($allStaff as $staffMember)
                                        <option value="{{ $staffMember->id }}">{{ $staffMember->name }} ({{ strtoupper($staffMember->role) }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Estado de la Cita *
                                </label>
                                <select 
                                    wire:model="meeting_status" 
                                    class="w-full border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl text-xs font-bold p-2.5 focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="scheduled">🔵 Programada / Pendiente</option>
                                    <option value="completed">🟢 Realizada / Completada</option>
                                    <option value="cancelled">🔴 Cancelada</option>
                                </select>
                            </div>
                        </div>

                        <!-- Fila 5: Notas Previas -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                📝 Temas a tratar / Notas Previas
                            </label>
                            <textarea 
                                wire:model="meeting_notes" 
                                rows="2" 
                                placeholder="Ej: Repasar canciones de la entrada al banquete, corte de tarta y sorpresa especial del novio..."
                                class="w-full border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl text-xs font-medium p-2.5 focus:ring-2 focus:ring-blue-500"
                            ></textarea>
                        </div>

                        <!-- Fila 6: Acuerdos y Decisiones (Conclusiones) -->
                        <div class="bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800 p-4 rounded-xl">
                            <label class="block text-xs font-black text-emerald-800 dark:text-emerald-300 mb-1 flex items-center gap-1.5">
                                <span>💡</span> Acuerdos, Decisiones y Conclusiones tomadas
                            </label>
                            <p class="text-[10px] text-emerald-600 dark:text-emerald-400 mb-2">
                                Anota aquí todo lo acordado con el cliente durante o después de la reunión para no olvidarlo y que todo el equipo lo consulte.
                            </p>
                            <textarea 
                                wire:model="meeting_summary" 
                                rows="3" 
                                placeholder="Ej: Acordado que la entrada será con la canción de Coldplay al segundo 35. El ramo se entregará a la hermana de la novia..."
                                class="w-full border-emerald-300 dark:border-emerald-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl text-xs font-medium p-2.5 focus:ring-2 focus:ring-emerald-500"
                            ></textarea>
                        </div>
                    </div>

                    <!-- FOOTER -->
                    <div class="p-4 bg-slate-50 dark:bg-slate-950 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <button 
                            type="button" 
                            wire:click="$set('showMeetingModal', false)" 
                            class="px-4 py-2.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-100 text-slate-700 dark:text-slate-300 font-bold text-xs border border-slate-200 dark:border-slate-700 transition cursor-pointer"
                        >
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold text-xs shadow-md hover:shadow-lg transition cursor-pointer flex items-center gap-1.5"
                        >
                            <span>💾</span> Guardar Cita
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
    @endif

    <!-- ============================================== -->
    <!-- MODAL: IMPORTAR HISTORIAL DE SESIÓN DJ        -->
    <!-- ============================================== -->
    @if($showDjHistoryModal)
    <div class="fixed z-40 inset-0 overflow-y-auto" aria-labelledby="modal-dj-history-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-xs transition-opacity" aria-hidden="true" wire:click="$set('showDjHistoryModal', false)"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-200 dark:border-slate-800">
                
                <form wire:submit.prevent="importDjHistory">
                    <!-- HEADER -->
                    <div class="p-5 bg-gradient-to-r from-purple-900 via-indigo-900 to-purple-950 text-white flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl p-2 bg-white/10 rounded-xl">🎧</span>
                            <div>
                                <h3 class="font-extrabold text-base leading-tight" id="modal-dj-history-title">
                                    Importar Historial de Sesión DJ
                                </h3>
                                <p class="text-xs text-purple-200 mt-0.5">
                                    Exporta tu sesión desde <strong>Engine DJ, Rekordbox, Serato o M3U</strong> y cárgala aquí.
                                </p>
                            </div>
                        </div>
                        <button type="button" wire:click="$set('showDjHistoryModal', false)" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <!-- BODY -->
                    <div class="p-6 space-y-5 text-xs text-slate-700 dark:text-slate-300">

                        <!-- Badges de compatibilidad -->
                        <div class="flex flex-wrap items-center gap-1.5 p-3 bg-purple-50/60 dark:bg-purple-950/30 rounded-xl border border-purple-100 dark:border-purple-900/40">
                            <span class="text-[10px] font-bold text-purple-900 dark:text-purple-300 uppercase tracking-wider block w-full mb-1">Formatos y Softwares Soportados:</span>
                            <span class="px-2 py-0.5 rounded-md bg-white dark:bg-slate-800 font-bold text-purple-700 dark:text-purple-300 shadow-2xs border border-purple-200 dark:border-purple-800">Denon Engine DJ (.csv / .m3u)</span>
                            <span class="px-2 py-0.5 rounded-md bg-white dark:bg-slate-800 font-bold text-indigo-700 dark:text-indigo-300 shadow-2xs border border-indigo-200 dark:border-indigo-800">Pioneer Rekordbox (.csv / .txt)</span>
                            <span class="px-2 py-0.5 rounded-md bg-white dark:bg-slate-800 font-bold text-blue-700 dark:text-blue-300 shadow-2xs border border-blue-200 dark:border-blue-800">Serato DJ (.csv)</span>
                            <span class="px-2 py-0.5 rounded-md bg-white dark:bg-slate-800 font-bold text-emerald-700 dark:text-emerald-300 shadow-2xs border border-emerald-200 dark:border-emerald-800">M3U / M3U8 Playlists</span>
                            <span class="px-2 py-0.5 rounded-md bg-white dark:bg-slate-800 font-bold text-slate-700 dark:text-slate-300 shadow-2xs border border-slate-200 dark:border-slate-800">Texto / Tracklist Copiado</span>
                        </div>

                        <!-- Nombre de la Sesión / Momento -->
                        <div>
                            <label class="block font-bold text-slate-900 dark:text-white mb-1">Nombre o Fase de la Sesión *</label>
                            <input 
                                type="text" 
                                wire:model="djHistorySessionName" 
                                placeholder="Ej: Sesión Baile & Barra Libre, Cocktail, etc." 
                                class="w-full border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl text-xs font-medium p-2.5 focus:ring-2 focus:ring-purple-500"
                                required
                            >
                        </div>

                        <!-- SUBIDA DE ARCHIVO -->
                        <div class="space-y-1.5">
                            <label class="block font-bold text-slate-900 dark:text-white">Opción 1: Subir Archivo (.csv, .m3u, .m3u8, .txt)</label>
                            <div class="relative border-2 border-dashed border-purple-300 dark:border-purple-800 hover:border-purple-500 rounded-2xl p-4 text-center bg-purple-50/30 dark:bg-purple-950/20 transition">
                                <input 
                                    type="file" 
                                    wire:model="djHistoryFile" 
                                    accept=".csv,.m3u,.m3u8,.txt,.nml" 
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                >
                                <div class="space-y-1">
                                    <span class="text-2xl block">📂</span>
                                    <span class="font-bold text-purple-900 dark:text-purple-300">
                                        {{ $djHistoryFile ? $djHistoryFile->getClientOriginalName() : 'Haz clic o arrastra aquí el archivo de historial' }}
                                    </span>
                                    <span class="text-[10px] text-slate-500 block">Exportado desde Engine DJ, Rekordbox, Serato o M3U</span>
                                </div>
                            </div>
                            @error('djHistoryFile') <span class="text-rose-500 text-[10px] font-bold">{{ $message }}</span> @enderror
                        </div>

                        <!-- O PEGAR TEXTO PLANO -->
                        <div class="space-y-1.5">
                            <label class="block font-bold text-slate-900 dark:text-white">Opción 2: O pega aquí el listado de canciones (1 por línea)</label>
                            <textarea 
                                wire:model="djHistoryRawText" 
                                rows="4" 
                                placeholder="00:45 Avicii - Levels&#10;00:48 David Guetta - Titanium&#10;00:52 Quevedo - Columbia&#10;..." 
                                class="w-full border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl text-xs font-mono p-2.5 focus:ring-2 focus:ring-purple-500"
                            ></textarea>
                        </div>

                        <!-- Checkbox Auto-Cruce de Peticiones -->
                        <div class="flex items-start gap-2.5 p-3 bg-emerald-50/70 dark:bg-emerald-950/30 rounded-xl border border-emerald-200 dark:border-emerald-900/40">
                            <input 
                                type="checkbox" 
                                id="syncRequestsCheck" 
                                wire:model="djHistorySyncRequests" 
                                class="mt-0.5 rounded text-emerald-600 focus:ring-emerald-500 h-4 w-4"
                            >
                            <label for="syncRequestsCheck" class="text-[11px] text-emerald-900 dark:text-emerald-200 cursor-pointer">
                                <strong>Cruzar y marcar peticiones como sonadas:</strong> Si alguna canción del historial coincide con las peticiones de los novios o invitados, marcarla automáticamente como <code>✅ Sonada en directo</code>.
                            </label>
                        </div>

                    </div>

                    <!-- FOOTER -->
                    <div class="p-4 bg-slate-50 dark:bg-slate-950 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <button 
                            type="button" 
                            wire:click="$set('showDjHistoryModal', false)" 
                            class="px-4 py-2.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-100 text-slate-700 dark:text-slate-300 font-bold text-xs border border-slate-200 dark:border-slate-700 transition cursor-pointer"
                        >
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            wire:loading.attr="disabled"
                            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-md hover:shadow-lg transition cursor-pointer flex items-center gap-1.5"
                        >
                            <span wire:loading.remove wire:target="importDjHistory">🎧 Importar y Procesar Historial</span>
                            <span wire:loading wire:target="importDjHistory">Procesando pistas...</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
    @endif

    <!-- ============================================================== -->
    <!-- MODAL: MOTIVO DE RECHAZO / CANCELACIÓN DEL EVENTO              -->
    <!-- ============================================================== -->
    @if($showCancellationModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/75 backdrop-blur-xs animate-fadeIn">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-5 overflow-y-auto max-h-[92vh]">
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-2xl {{ $cancellation_target_status === 'rejected' ? 'bg-slate-800 text-white' : 'bg-rose-600 text-white' }} flex items-center justify-center text-xl shrink-0 shadow-sm">
                            {{ $cancellation_target_status === 'rejected' ? '❌' : '🔴' }}
                        </div>
                        <div>
                            <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">
                                {{ $cancellation_target_status === 'rejected' ? 'Motivo del Rechazo / Descarte' : 'Motivo de la Cancelación' }}
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Indica por qué se {{ $cancellation_target_status === 'rejected' ? 'rechaza' : 'cancela' }} <span class="font-bold text-slate-700 dark:text-slate-200">{{ $event->name }}</span>. Se mostrará arriba en la ficha y en la primera línea de las notas.
                            </p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeCancellationModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold leading-none cursor-pointer">&times;</button>
                </div>

                <!-- Selector entre Rechazado y Cancelado -->
                <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800">
                    <button type="button"
                            wire:click="$set('cancellation_target_status', 'rejected')"
                            class="py-2 px-3 rounded-xl text-xs font-extrabold transition flex items-center justify-center gap-1.5 cursor-pointer {{ $cancellation_target_status === 'rejected' ? 'bg-slate-800 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                        <span>❌</span>
                        <span>Rechazado (No contrataron)</span>
                    </button>
                    <button type="button"
                            wire:click="$set('cancellation_target_status', 'cancelled')"
                            class="py-2 px-3 rounded-xl text-xs font-extrabold transition flex items-center justify-center gap-1.5 cursor-pointer {{ $cancellation_target_status === 'cancelled' ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                        <span>🔴</span>
                        <span>Cancelado (Anulado)</span>
                    </button>
                </div>

                <!-- Motivos rápidos de 1 clic -->
                <div class="space-y-2">
                    <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        ⚡ Motivos frecuentes (pulsa uno o escribe debajo):
                    </label>
                    <div class="flex flex-wrap gap-1.5">
                        @php
                            $presetReasons = [
                                '💸 Fuera de presupuesto / Precio' => 'Fuera de presupuesto / Precio elevado',
                                '🎧 Contrataron otro DJ / proveedor' => 'Ya han contratado a otro DJ o proveedor',
                                '👻 Dejaron de responder' => 'Dejaron de responder tras varios seguimientos',
                                '📅 Sin disponibilidad de fecha' => 'Sin disponibilidad para esa fecha',
                                '🏰 Exclusividad de la finca / restaurante' => 'La finca o restaurante impone exclusividad con otro proveedor',
                                '💔 Se cancela / aplaza el evento' => 'Cancelación o aplazamiento de la boda / evento por parte de los clientes',
                                '🎯 No encaja con nuestro perfil' => 'El tipo de evento no encaja con nuestros servicios',
                            ];
                        @endphp
                        @foreach($presetReasons as $chipLabel => $chipValue)
                            <button type="button"
                                    wire:click="setPresetCancellationReason('{{ $chipValue }}')"
                                    class="px-2.5 py-1.5 rounded-xl text-xs font-bold border transition cursor-pointer {{ $cancellation_reason_input === $chipValue ? 'bg-rose-600 text-white border-rose-600 shadow-2xs' : 'bg-slate-50 hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700' }}">
                                {{ $chipLabel }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Campo de texto del motivo -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        📝 Motivo detallado *
                    </label>
                    <textarea wire:model="cancellation_reason_input"
                              rows="3"
                              placeholder="Escribe el motivo por el que se rechaza o cancela (ej: Les parecía caro el pack completo, contrataron al DJ de la finca...)"
                              class="w-full text-xs sm:text-sm rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-3 focus:ring-2 focus:ring-rose-500 focus:border-rose-500"></textarea>
                    @if (session()->has('cancellation_error'))
                        <p class="text-xs font-bold text-rose-600 dark:text-rose-400">
                            ⚠️ {{ session('cancellation_error') }}
                        </p>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button"
                            wire:click="closeCancellationModal"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer">
                        Cancelar
                    </button>
                    <button type="button"
                            wire:click="confirmCancellationStatus"
                            class="px-5 py-2.5 rounded-xl text-xs font-black text-white shadow-md transition cursor-pointer flex items-center gap-1.5 {{ $cancellation_target_status === 'rejected' ? 'bg-slate-900 hover:bg-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600' : 'bg-rose-600 hover:bg-rose-500' }}">
                        <span>{{ $cancellation_target_status === 'rejected' ? '❌ Confirmar Rechazo' : '🔴 Confirmar Cancelación' }}</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
