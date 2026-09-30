@php
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
            $pct = (float)\App\Models\Setting::get('deposit_percentage', 40);
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
    $quotePropuestaMsg = "¡Hola " . ($event->client ? $event->client->name : '') . "! 👋\n\nOs compartimos la propuesta detallada para vuestro evento *" . $event->name . "* (" . \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') . " en " . $event->location . "):\n\n" . ($quoteItemsText ?: '• Servicios musicales y sonorización profesional.') . "\n\n💰 *TOTAL PRESUPUESTO:* " . number_format($quoteAmount, 2, ',', '.') . " €\n\n📌 *CONDICIONES DE PAGO:*\n• Señal de reserva (" . $signalLabel . "): " . number_format($signalAmount, 2, ',', '.') . " €\n• Restante el día del evento: " . number_format($remainingAmount, 2, ',', '.') . " €\n\nCualquier duda o ajuste, estamos a vuestra entera disposición. ¡Un saludo!";
    $quotePropuestaWaUrl = \App\Services\WhatsAppTemplateService::getLink($clientPhone, $quotePropuestaMsg);

    $signalMsg = "¡Hola " . ($event->client ? $event->client->name : '') . "! 👋\n\nPara confirmar la fecha de vuestro evento *" . $event->name . "* (" . \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') . "), podéis abonar la *Señal de reserva (" . number_format($signalAmount, 2, ',', '.') . " €)* mediante:\n\n" . ($iban ? "🏦 *Transferencia Bancaria:*\n" . $iban . "\n\n" : "") . ($bizum ? "📱 *Bizum:* " . $bizum . "\n\n" : "") . "Concepto: " . $event->name . "\n\n¡Muchas gracias y quedamos a vuestra disposición!";
    $signalWaUrl = \App\Services\WhatsAppTemplateService::getLink($clientPhone, $signalMsg);

    $cuestionarioMsg = \App\Services\WhatsAppTemplateService::cuestionario($event);
    $cuestionarioWaUrl = \App\Services\WhatsAppTemplateService::getLink($clientPhone, $cuestionarioMsg);
    
    $firstContract = $event->contracts()->latest()->first();
    $contractMsg = $firstContract ? \App\Services\WhatsAppTemplateService::contrato($firstContract) : '';
    $contractWaUrl = $firstContract ? \App\Services\WhatsAppTemplateService::getLink($clientPhone, $contractMsg) : '';

    $liquidacionMsg = \App\Services\WhatsAppTemplateService::liquidacion($event);
    $liquidacionWaUrl = \App\Services\WhatsAppTemplateService::getLink($clientPhone, $liquidacionMsg);

    $djMsg = \App\Services\WhatsAppTemplateService::hojaRutaDj($event);
    $djWaUrl = \App\Services\WhatsAppTemplateService::getLink($djPhone, $djMsg);
    $astWaUrl = \App\Services\WhatsAppTemplateService::getLink($assistantPhone, $djMsg);
@endphp

<div x-data="{ openWhatsappHub: false, openQrModal: false }">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-2xl font-bold text-gray-800">{{ $event->name }}</h2>
                <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-bold {{ $event->status === 'confirmed' ? 'bg-emerald-100 text-emerald-800' : ($event->status === 'completed' ? 'bg-blue-100 text-blue-800' : ($event->status === 'cancelled' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800')) }}">
                    {{ ucfirst($event->status) }}
                </span>

                @if($event->deposit_paid && (float)$event->deposit_paid_amount > 0)
                    <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-800 bg-emerald-50 border border-emerald-300 px-3 py-1 rounded-xl shadow-2xs">
                        <span>{{ $event->deposit_method_icon }}</span>
                        <span>Señal: <strong>{{ number_format($event->deposit_paid_amount, 2, ',', '.') }} €</strong> ({{ $event->deposit_method_label }})</span>
                        @if($event->deposit_paid_at)
                            <span class="text-[11px] text-emerald-600 font-normal">el {{ \Carbon\Carbon::parse($event->deposit_paid_at)->format('d/m/Y') }}</span>
                        @endif
                        <button type="button" wire:click="openDepositModal('confirmed')" class="text-[11px] text-indigo-600 hover:text-indigo-800 font-bold ml-1 hover:underline cursor-pointer" title="Modificar importe, método o fecha de la señal">
                            ✏️ Modificar
                        </button>
                    </span>
                    @if($quoteAmount > 0)
                        @php
                            $realRemaining = max(0, $quoteAmount - (float)$event->deposit_paid_amount);
                        @endphp
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-700 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-xl">
                            <span>Pendiente:</span>
                            <strong class="{{ $realRemaining == 0 ? 'text-emerald-700' : 'text-slate-900' }}">
                                {{ number_format($realRemaining, 2, ',', '.') }} €
                            </strong>
                            @if($realRemaining == 0)
                                <span class="text-[10px] bg-emerald-200 text-emerald-800 font-black px-1.5 py-0.5 rounded-md">¡100% Pagado!</span>
                            @endif
                        </span>
                    @endif
                @elseif($event->status === 'confirmed')
                    <button type="button" wire:click="openDepositModal('confirmed')" class="inline-flex items-center gap-1 text-xs font-bold text-amber-800 bg-amber-50 hover:bg-amber-100 border border-amber-300 px-3 py-1 rounded-xl shadow-2xs transition cursor-pointer" title="Registrar el cobro de la señal">
                        <span>💳 + Registrar Señal</span>
                    </button>
                @endif
            </div>
            
            <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 mt-2">
                <span>📅 {{ \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') }}</span>
                <span>📍 {{ $event->location }}</span>
                @if($event->client)
                    <span class="inline-flex items-center gap-1.5 text-gray-700 bg-emerald-50/70 border border-emerald-200 px-2.5 py-1 rounded-lg">
                        👤 <strong>{{ $event->client->name }}</strong>
                        @if($clientPhone)
                            <a href="https://wa.me/{{ $clientPhone }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-white hover:bg-emerald-100 border border-emerald-300 px-1.5 py-0.5 rounded transition" title="Abrir chat de WhatsApp">
                                💬
                            </a>
                        @endif
                        <button type="button" wire:click="openClientModal" class="inline-flex items-center gap-0.5 text-[11px] font-bold text-amber-800 bg-amber-100 hover:bg-amber-200 border border-amber-300 px-1.5 py-0.5 rounded transition cursor-pointer" title="Editar datos o contraseña del cliente">
                            ✏️ Editar
                        </button>
                    </span>
                @endif

                <!-- DJ Principal (Baile) -->
                <span class="inline-flex items-center gap-1.5 text-indigo-700 font-semibold bg-indigo-50 px-2.5 py-1 rounded-lg border border-indigo-100">
                    🎧 DJ: {{ $event->dj ? $event->dj->name : 'Sin asignar' }}
                    @if($djPhone)
                        <a href="https://wa.me/{{ $djPhone }}" target="_blank" class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 bg-white hover:bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded transition" title="Contactar DJ por WhatsApp">
                            💬
                        </a>
                    @endif
                </span>

                <!-- Asistente (Ceremonia, Cóctel, Banquete) -->
                <span class="inline-flex items-center gap-1.5 text-amber-800 font-semibold bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200">
                    👷‍♂️ Asistente: {{ $event->assistant ? $event->assistant->name : 'Sin asignar' }}
                    @if($assistantPhone)
                        <a href="https://wa.me/{{ $assistantPhone }}" target="_blank" class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 bg-white hover:bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded transition" title="Contactar Asistente por WhatsApp">
                            💬
                        </a>
                    @endif
                </span>

                <!-- Contacto Finca / Bodega / Restaurante -->
                <span class="inline-flex items-center gap-1.5 text-slate-800 dark:text-slate-200 font-semibold bg-slate-100 dark:bg-slate-800 px-2.5 py-1 rounded-lg border border-slate-200 dark:border-slate-700 shadow-2xs">
                    🏢 <strong>Finca/Bodega:</strong> {{ $event->venue_contact_name ?: ($event->location ?: 'Sin contacto') }}
                    @if($event->venue_contact_phone)
                        <a href="tel:{{ $event->venue_contact_phone }}" class="inline-flex items-center gap-1 text-xs font-bold text-blue-700 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/60 dark:text-blue-300 border border-blue-300 dark:border-blue-700 px-2 py-0.5 rounded transition" title="Llamar directamente">
                            📞 {{ $event->venue_contact_phone }}
                        </a>
                        @if($venuePhone)
                            <a href="https://wa.me/{{ $venuePhone }}" target="_blank" class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 bg-emerald-100 hover:bg-emerald-200 dark:bg-emerald-900/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700 px-1.5 py-0.5 rounded transition" title="Enviar WhatsApp a la finca">
                                💬
                            </a>
                        @endif
                    @endif
                    <button wire:click="openVenueModal" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-bold ml-1 cursor-pointer" title="Editar contacto y notas de la finca/bodega">
                        {{ $event->venue_contact_name || $event->venue_contact_phone ? '✏️' : '+ Añadir Teléfono' }}
                    </button>
                </span>

                <!-- Botón rápido cambiar DJ / Asistente -->
                <button wire:click="openStaffModal" class="text-xs bg-white hover:bg-gray-100 text-gray-700 border border-gray-300 font-bold px-2 py-1 rounded-lg shadow-xs transition">
                    👥 Personal
                </button>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Botón Cabina En Vivo -->
            <a href="{{ route('admin.events.live', $event->id) }}" class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-amber-400 font-bold px-3.5 py-2 rounded-xl text-xs shadow-md border border-slate-700 transition">
                🎧 <span class="text-white">Modo Cabina DJ</span>
            </a>

            <!-- Botón QR Invitados -->
            <button type="button" @click="openQrModal = true" class="inline-flex items-center gap-1.5 bg-gradient-to-r from-pink-600 to-indigo-600 hover:from-pink-700 hover:to-indigo-700 text-white font-bold px-3 py-2 rounded-xl text-xs shadow-md transition">
                📱 QR Invitados
            </button>

            <!-- Botón WhatsApp Hub -->
            <button type="button" @click="openWhatsappHub = true" class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-2 rounded-xl text-xs shadow-md transition">
                💬 WhatsApp Hub
            </button>

            <div class="flex items-center text-sm shadow-xs rounded-lg overflow-hidden border border-gray-200 ml-2">
                <button wire:click="changeStatus('draft')" class="px-2.5 py-1.5 text-xs font-medium {{ $event->status == 'draft' ? 'bg-amber-100 text-amber-900 font-bold' : 'bg-white hover:bg-gray-50' }}">Borrador</button>
                <button wire:click="changeStatus('confirmed')" class="px-2.5 py-1.5 text-xs font-medium border-l border-r border-gray-200 {{ $event->status == 'confirmed' ? 'bg-emerald-100 text-emerald-900 font-bold' : 'bg-white hover:bg-gray-50' }}">Confirmado</button>
                <button wire:click="changeStatus('completed')" class="px-2.5 py-1.5 text-xs font-medium border-r border-gray-200 {{ $event->status == 'completed' ? 'bg-blue-100 text-blue-900 font-bold' : 'bg-white hover:bg-gray-50' }}">Completado</button>
                <button wire:click="changeStatus('cancelled')" class="px-2.5 py-1.5 text-xs font-medium {{ $event->status == 'cancelled' ? 'bg-rose-100 text-rose-900 font-bold' : 'bg-white hover:bg-gray-50' }}">Cancelado</button>
            </div>
            
            <a href="{{ route('admin.events') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium ml-2">← Volver</a>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
            {{ session('message') }}
        </div>
    @endif

    <!-- Banner Cuestionario Musical con WhatsApp -->
    <div class="bg-white p-4 rounded-xl border border-gray-200 mb-6 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <h4 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                <span>🎵 Enlace Cuestionario Musical (Para Clientes)</span>
                @if($event->is_dossier_completed)
                    <span class="text-[10px] bg-green-100 text-green-800 px-2 py-0.5 rounded-full font-bold">Completado por cliente</span>
                @endif
            </h4>
            <p class="text-xs text-gray-500 mt-0.5">Comparte este enlace para que los novios rellenen sus canciones y preferencias.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 w-full md:w-auto" x-data="{ copiedLink: false }">
            <input type="text" readonly value="{{ route('guest.form', $event->token) }}" class="flex-1 md:w-64 border-gray-300 rounded-lg text-xs bg-gray-50 px-3 py-2 select-all focus:ring-indigo-500" id="guestLink">
            
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
            
            <a href="{{ $cuestionarioWaUrl }}" target="_blank" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 rounded-lg text-xs font-bold shadow-xs whitespace-nowrap inline-flex items-center gap-1.5 transition">
                💬 Enviar WhatsApp
            </a>
        </div>
    </div>

    <!-- Modal WhatsApp Hub -->
    <div x-show="openWhatsappHub" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="openWhatsappHub = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-emerald-700 px-6 py-4 flex items-center justify-between text-white">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">💬</span>
                        <div>
                            <h3 class="font-bold text-lg leading-tight">Centro de Mensajes WhatsApp</h3>
                            <p class="text-xs text-emerald-200">{{ $event->name }} &bull; Plantillas rápidas de 1-Clic</p>
                        </div>
                    </div>
                    <button type="button" @click="openWhatsappHub = false" class="text-emerald-200 hover:text-white text-2xl font-bold leading-none">&times;</button>
                </div>

                <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                    <!-- Opción 1: Propuesta y Desglose -->
                    <div class="p-4 rounded-xl border border-gray-200 hover:border-emerald-300 bg-gray-50 hover:bg-emerald-50/40 transition">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h4 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                    <span>📋 Enviar Resumen de Presupuesto & Desglose</span>
                                    <span class="text-[10px] bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded-full">Clientes</span>
                                </h4>
                                <p class="text-xs text-gray-500 mt-1">
                                    @if($latestQuote)
                                        Envía el desglose de servicios contratados (Total: <strong>{{ number_format($quoteAmount, 2) }} €</strong> | Señal: <strong>{{ number_format($signalAmount, 2) }} €</strong> - {{ $signalLabel }}).
                                    @else
                                        <span class="text-amber-600">Genera primero una propuesta en la pestaña de Presupuestos.</span>
                                    @endif
                                </p>
                            </div>
                            @if($latestQuote)
                                <a href="{{ $quotePropuestaWaUrl }}" target="_blank" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-3.5 py-2 rounded-xl shadow-xs inline-flex items-center gap-1.5 whitespace-nowrap transition">
                                    💬 Enviar
                                </a>
                            @else
                                <button disabled class="bg-gray-300 text-gray-500 font-bold text-xs px-3.5 py-2 rounded-xl cursor-not-allowed">Sin propuesta</button>
                            @endif
                        </div>
                    </div>

                    <!-- Opción 2: Solicitar Señal de Reserva -->
                    <div class="p-4 rounded-xl border border-gray-200 hover:border-emerald-300 bg-gray-50 hover:bg-emerald-50/40 transition">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h4 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                    <span>💶 Solicitar Señal de Reserva ({{ $signalLabel }})</span>
                                    <span class="text-[10px] bg-blue-100 text-blue-800 font-bold px-2 py-0.5 rounded-full">Clientes</span>
                                </h4>
                                <p class="text-xs text-gray-500 mt-1">
                                    Solicita el abono de la reserva (<strong>{{ number_format($signalAmount, 2) }} €</strong>) con datos bancarios y Bizum.
                                </p>
                            </div>
                            @if($latestQuote)
                                <a href="{{ $signalWaUrl }}" target="_blank" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-3.5 py-2 rounded-xl shadow-xs inline-flex items-center gap-1.5 whitespace-nowrap transition">
                                    💬 Enviar
                                </a>
                            @else
                                <button disabled class="bg-gray-300 text-gray-500 font-bold text-xs px-3.5 py-2 rounded-xl cursor-not-allowed">Sin propuesta</button>
                            @endif
                        </div>
                    </div>

                    <!-- Opción 3: Firma de Contrato Online -->
                    <div class="p-4 rounded-xl border border-gray-200 hover:border-emerald-300 bg-gray-50 hover:bg-emerald-50/40 transition">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h4 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                    <span>✍️ Enlace de Firma de Contrato Digital</span>
                                    <span class="text-[10px] bg-purple-100 text-purple-700 font-bold px-2 py-0.5 rounded-full">Clientes</span>
                                </h4>
                                <p class="text-xs text-gray-500 mt-1">
                                    @if($firstContract)
                                        Enlace interactivo para que el cliente revise las cláusulas y firme online desde su móvil o PC.
                                    @else
                                        <span class="text-amber-600">Aún no has generado ningún contrato para este evento.</span>
                                    @endif
                                </p>
                            </div>
                            @if($firstContract)
                                <a href="{{ $contractWaUrl }}" target="_blank" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-3.5 py-2 rounded-xl shadow-xs inline-flex items-center gap-1.5 whitespace-nowrap transition">
                                    💬 Enviar
                                </a>
                            @else
                                <button disabled class="bg-gray-300 text-gray-500 font-bold text-xs px-3.5 py-2 rounded-xl cursor-not-allowed">Sin contrato</button>
                            @endif
                        </div>
                    </div>

                    <!-- Opción 4: Cuestionario Musical -->
                    <div class="p-4 rounded-xl border border-gray-200 hover:border-emerald-300 bg-gray-50 hover:bg-emerald-50/40 transition">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h4 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                    <span>🎵 Cuestionario Musical & Dossier</span>
                                    <span class="text-[10px] bg-indigo-100 text-indigo-700 font-bold px-2 py-0.5 rounded-full">Clientes</span>
                                </h4>
                                <p class="text-xs text-gray-500 mt-1">Envía a los novios su enlace personalizado para completar momentos clave y canciones.</p>
                            </div>
                            <a href="{{ $cuestionarioWaUrl }}" target="_blank" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-3.5 py-2 rounded-xl shadow-xs inline-flex items-center gap-1.5 whitespace-nowrap transition">
                                💬 Enviar
                            </a>
                        </div>
                    </div>

                    <!-- Opción 5: Liquidación Restante -->
                    <div class="p-4 rounded-xl border border-gray-200 hover:border-emerald-300 bg-gray-50 hover:bg-emerald-50/40 transition">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h4 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                    <span>💰 Recordatorio de Pago / Liquidación Restante</span>
                                    <span class="text-[10px] bg-amber-100 text-amber-800 font-bold px-2 py-0.5 rounded-full">Clientes</span>
                                </h4>
                                <p class="text-xs text-gray-500 mt-1">Recordatorio del pago del importe pendiente (<strong>{{ number_format($remainingAmount, 2) }} €</strong>) antes o el día del evento.</p>
                            </div>
                            <a href="{{ $liquidacionWaUrl }}" target="_blank" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-3.5 py-2 rounded-xl shadow-xs inline-flex items-center gap-1.5 whitespace-nowrap transition">
                                💬 Enviar
                            </a>
                        </div>
                    </div>

                    <!-- Opción 6: Hoja de Ruta para DJ y Asistente -->
                    <div class="p-4 rounded-xl border border-gray-200 hover:border-emerald-300 bg-gray-50 hover:bg-emerald-50/40 transition">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h4 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                    <span>🎧 Hoja de Ruta Técnica & Horarios</span>
                                    <span class="text-[10px] bg-blue-100 text-blue-700 font-bold px-2 py-0.5 rounded-full">Staff DJ / Asistente</span>
                                </h4>
                                <p class="text-xs text-gray-500 mt-1">Resumen completo del montaje, localización, horarios y enlace al Modo Cabina en vivo.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($event->dj)
                                    <a href="{{ $djWaUrl }}" target="_blank" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs px-3 py-2 rounded-xl shadow-xs inline-flex items-center gap-1 whitespace-nowrap transition">
                                        🎧 DJ
                                    </a>
                                @endif
                                @if($event->assistant)
                                    <a href="{{ $astWaUrl }}" target="_blank" class="bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs px-3 py-2 rounded-xl shadow-xs inline-flex items-center gap-1 whitespace-nowrap transition">
                                        👷‍♂️ Asistente
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-3 border-t border-gray-100 text-right">
                    <button type="button" @click="openWhatsappHub = false" class="bg-white hover:bg-gray-100 text-gray-700 font-bold text-xs px-4 py-2 border border-gray-300 rounded-xl transition">
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
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode(route('guest.requests', $event->token)) }}&margin=10" alt="QR Peticiones" class="w-52 h-52 mx-auto rounded-xl shadow-md">
                </div>

                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <input type="text" readonly value="{{ route('guest.requests', $event->token) }}" class="flex-1 border-gray-300 rounded-xl text-xs bg-gray-50 px-3 py-2 select-all focus:ring-indigo-500" id="qrLinkInput">
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
                        <a href="{{ route('guest.requests', $event->token) }}" target="_blank" class="flex-1 bg-gradient-to-r from-pink-600 to-indigo-600 hover:from-pink-700 hover:to-indigo-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs shadow-md transition">
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

    <!-- Navegación por pestañas -->
    <div class="mb-6 border-b border-gray-200">
        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
            <li class="mr-2">
                <button wire:click="$set('activeTab', 'dossier')" class="inline-block p-4 rounded-t-lg border-b-2 {{ $activeTab == 'dossier' ? 'border-indigo-600 text-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">
                    Dossier y Planificación
                </button>
            </li>
            <li class="mr-2">
                <button wire:click="$set('activeTab', 'music')" class="inline-block p-4 rounded-t-lg border-b-2 {{ $activeTab == 'music' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">
                    🎵 Música y Momentos <span class="ml-1 text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-bold">{{ $event->musicRequests->count() }}</span>
                </button>
            </li>
            <li class="mr-2">
                <button wire:click="$set('activeTab', 'equipment')" class="inline-block p-4 rounded-t-lg border-b-2 {{ $activeTab == 'equipment' ? 'border-indigo-600 text-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">
                    📦 Material / Carga
                </button>
            </li>
            @if(Auth::check() && Auth::user()->role === 'admin')
            <li class="mr-2">
                <button wire:click="$set('activeTab', 'quotes')" class="inline-block p-4 rounded-t-lg border-b-2 {{ $activeTab == 'quotes' ? 'border-indigo-600 text-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">
                    Presupuestos (Propuestas)
                </button>
            </li>
            <li class="mr-2">
                <button wire:click="$set('activeTab', 'contracts')" class="inline-block p-4 rounded-t-lg border-b-2 {{ $activeTab == 'contracts' ? 'border-indigo-600 text-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">
                    Contratos
                </button>
            </li>
            <li class="mr-2">
                <button wire:click="$set('activeTab', 'invoices')" class="inline-block p-4 rounded-t-lg border-b-2 {{ $activeTab == 'invoices' ? 'border-indigo-600 text-indigo-600' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">
                    Facturas
                </button>
            </li>
            @endif
        </ul>
    </div>

    <!-- Contenido Pestañas -->
    <div class="bg-white shadow sm:rounded-lg p-6">
        
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
                    <!-- Columna 1: Acuerdos Rápidos & Importador de Conversación WhatsApp -->
                    <div class="bg-gray-50 p-5 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                        <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">💬</span>
                                <div>
                                    <h4 class="font-bold text-gray-900 text-sm">Acuerdos & Notas de WhatsApp</h4>
                                    <p class="text-[11px] text-gray-500">Pega fragmentos de chats o apuntes acordados con el cliente</p>
                                </div>
                            </div>
                            <button wire:click="saveEventNotes" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-xs transition">
                                💾 Guardar Notas
                            </button>
                        </div>

                        <!-- Importador rápido -->
                        <div class="bg-white p-3.5 rounded-xl border border-emerald-200 shadow-xs space-y-2">
                            <label class="block text-xs font-bold text-emerald-900">
                                📥 Pegar texto directo del chat de WhatsApp
                            </label>
                            <textarea wire:model="whatsapp_chat_input" rows="3" class="w-full text-xs border border-gray-300 rounded-lg p-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="Pega aquí el mensaje o audio transcrito del cliente con los requisitos acordados..."></textarea>
                            <div class="flex justify-end">
                                <button type="button" wire:click="applyWhatsappTextToNotes" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1">
                                    <span>➕</span> Añadir a las Notas del Evento
                                </button>
                            </div>
                        </div>

                        <!-- Visualizador / Editor completo de notas -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                📝 Notas y Requisitos Generales del Evento
                            </label>
                            <textarea wire:model="event_notes" rows="8" class="w-full text-xs font-mono bg-white border border-gray-300 rounded-xl p-3 focus:ring-indigo-500 focus:border-indigo-500 leading-relaxed" placeholder="Notas internas, servicios acordados, peticiones especiales..."></textarea>
                        </div>
                    </div>

                    <!-- Columna 2: Dossier Técnico y Horarios de Montaje -->
                    <div class="bg-gray-50 p-5 rounded-2xl border border-gray-200 shadow-xs space-y-4 flex flex-col justify-between">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">📋</span>
                                    <div>
                                        <h4 class="font-bold text-gray-900 text-sm">Dossier Técnico y Horarios Staff</h4>
                                        <p class="text-[11px] text-gray-500">Planificación técnica visible para DJ y personal de cabina</p>
                                    </div>
                                </div>
                                <button wire:click="saveDossier" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-xs transition">
                                    💾 Guardar Dossier
                                </button>
                            </div>

                            <div>
                                <textarea wire:model="dossier_content" rows="12" class="w-full text-xs font-mono bg-white border border-gray-300 rounded-xl p-3 focus:ring-indigo-500 focus:border-indigo-500 leading-relaxed" placeholder="Escribe aquí los horarios previstos, accesos a finca, ubicación de tomas de corriente, setup de sonido e iluminación, contactos de metres..."></textarea>
                            </div>

                            <div class="p-3 bg-white rounded-xl border border-gray-200 flex items-center">
                                <input type="checkbox" wire:model="is_dossier_completed" id="dossier_completed" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="dossier_completed" class="ml-2.5 block text-xs font-semibold text-gray-800 cursor-pointer">
                                    🔒 Cuestionario cerrado (Los novios ya han completado su dossier y momentos)
                                </label>
                            </div>
                        </div>

                        <div class="pt-2 text-right">
                            <button wire:click="saveDossier" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-bold text-xs shadow-md transition">
                                Guardar Dossier y Planificación
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- MÚSICA Y MOMENTOS --}}
        @if($activeTab == 'music')
            <div>
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
                        init() {
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
                                }
                            });
                        },
                        playTrack(index) {
                            if (index < 0 || index >= this.tracks.length) return;
                            this.currentIndex = index;
                            const track = this.tracks[index];
                            if (track.audio_src) {
                                this.audio.src = track.audio_src;
                                this.audio.play();
                                this.isPlaying = true;
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
                            } else {
                                this.audio.play();
                                this.isPlaying = true;
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
                @else
                    <div class="text-center p-12 border-2 border-dashed border-gray-300 rounded-xl bg-white">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No hay canciones en esta categoría</h3>
                        <p class="mt-1 text-sm text-gray-500">Añade los momentos especiales de la boda pulsando arriba en "Añadir Canción / Momento".</p>
                    </div>
                @endif
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
            <div class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-200">
                    <div>
                        <h4 class="text-lg font-bold text-gray-800">Generador de Presupuestos, Packs y Propuestas</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Configura el paquete de servicios contratados, calcula automáticamente la señal del 40% y genera el PDF oficial.</p>
                    </div>
                    @if($latestQuote)
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-gray-600">Última Propuesta:</span>
                            <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-lg text-xs font-black">{{ number_format($latestQuote->amount, 2) }} €</span>
                        </div>
                    @endif
                </div>

                @if (session()->has('quote_message')) 
                    <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-xl text-xs font-bold shadow-xs">
                        ✓ {{ session('quote_message') }}
                    </div> 
                @endif
                
                <!-- SELECTOR DE PACKS RÁPIDOS (1-CLIC) -->
                <div class="bg-gradient-to-r from-indigo-900 via-indigo-800 to-purple-900 text-white p-5 rounded-2xl shadow-md">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                        <h5 class="font-black text-sm uppercase tracking-wider text-indigo-200 flex items-center gap-2">
                            <span>⚡</span> Cargar Pack Preconfigurado (1-Clic)
                        </h5>
                        <button type="button" wire:click="selectPack('clear')" class="text-xs text-indigo-300 hover:text-white underline self-start sm:self-auto">
                            Limpiar Selección
                        </button>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5">
                        <button type="button" wire:click="selectPack('basic')" class="p-3 rounded-xl border text-left transition flex flex-col justify-between {{ $selected_pack_type === 'basic' ? 'bg-white text-indigo-900 border-white shadow-lg ring-2 ring-indigo-400' : 'bg-white/10 hover:bg-white/20 border-white/20 text-white' }}">
                            <div>
                                <span class="text-[10px] uppercase font-bold tracking-wider opacity-80">4h DJ</span>
                                <h6 class="font-extrabold text-xs mt-0.5">Pack Básico</h6>
                            </div>
                            <span class="font-black text-sm mt-2">{{ number_format($quote_services['pack_basic']['price'], 0) }} €</span>
                        </button>

                        <button type="button" wire:click="selectPack('medium')" class="p-3 rounded-xl border text-left transition relative flex flex-col justify-between {{ $selected_pack_type === 'medium' ? 'bg-white text-indigo-900 border-white shadow-lg ring-2 ring-emerald-400' : 'bg-white/10 hover:bg-white/20 border-white/20 text-white' }}">
                            <span class="absolute -top-2 right-2 bg-emerald-500 text-white text-[9px] font-black px-1.5 py-0.5 rounded-full uppercase">Top</span>
                            <div>
                                <span class="text-[10px] uppercase font-bold tracking-wider opacity-80">5h + Humo</span>
                                <h6 class="font-extrabold text-xs mt-0.5">Pack Medio ⭐</h6>
                            </div>
                            <span class="font-black text-sm mt-2">{{ number_format($quote_services['pack_medium']['price'], 0) }} €</span>
                        </button>

                        <button type="button" wire:click="selectPack('premium')" class="p-3 rounded-xl border text-left transition flex flex-col justify-between {{ $selected_pack_type === 'premium' ? 'bg-white text-indigo-900 border-white shadow-lg ring-2 ring-amber-400' : 'bg-white/10 hover:bg-white/20 border-white/20 text-white' }}">
                            <div>
                                <span class="text-[10px] uppercase font-bold tracking-wider opacity-80">6h + FX Pro</span>
                                <h6 class="font-extrabold text-xs mt-0.5">Pack Premium</h6>
                            </div>
                            <span class="font-black text-sm mt-2">{{ number_format($quote_services['pack_premium']['price'], 0) }} €</span>
                        </button>

                        <button type="button" wire:click="selectPack('wedding_dj')" class="p-3 rounded-xl border text-left transition flex flex-col justify-between {{ $selected_pack_type === 'wedding_dj' ? 'bg-white text-indigo-900 border-white shadow-lg ring-2 ring-indigo-400' : 'bg-white/10 hover:bg-white/20 border-white/20 text-white' }}">
                            <div>
                                <span class="text-[10px] uppercase font-bold tracking-wider opacity-80">Cóctel + Banquete + 2h</span>
                                <h6 class="font-extrabold text-xs mt-0.5">Boda Solo DJ</h6>
                            </div>
                            <span class="font-black text-sm mt-2">Personalizado</span>
                        </button>

                        <button type="button" wire:click="selectPack('wedding_full')" class="p-3 rounded-xl border text-left transition flex flex-col justify-between {{ $selected_pack_type === 'wedding_full' ? 'bg-white text-indigo-900 border-white shadow-lg ring-2 ring-purple-400' : 'bg-white/10 hover:bg-white/20 border-white/20 text-white' }}">
                            <div>
                                <span class="text-[10px] uppercase font-bold tracking-wider opacity-80">DJ + Pack Fotos</span>
                                <h6 class="font-extrabold text-xs mt-0.5">Boda Completa 📸</h6>
                            </div>
                            <span class="font-black text-sm mt-2">DJ + Fotos</span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <!-- CONFIGURADOR DE LÍNEAS DE PRESUPUESTO -->
                    <div class="lg:col-span-7 bg-gray-50 p-5 rounded-2xl border border-gray-200 shadow-xs space-y-6">
                        @if($editing_quote_id)
                            <div class="bg-amber-50 border-l-4 border-amber-500 p-3.5 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
                                <div class="flex items-center gap-2.5">
                                    <span class="text-xl">✏️</span>
                                    <div>
                                        <div class="font-black text-xs text-amber-900 uppercase tracking-wide flex items-center gap-2">
                                            <span>Modificando Propuesta #{{ $editing_quote_id }}</span>
                                            <span class="bg-amber-200 text-amber-900 text-[10px] px-1.5 py-0.2 rounded font-bold">Modo Edición</span>
                                        </div>
                                        <p class="text-[11px] text-amber-700 mt-0.5">Puedes actualizar esta propuesta directamente o guardarla como una nueva versión.</p>
                                    </div>
                                </div>
                                <button type="button" wire:click="cancelQuoteEditing" class="text-xs bg-white border border-amber-300 text-amber-800 px-3 py-1.5 rounded-lg font-bold hover:bg-amber-100 transition self-start sm:self-auto">
                                    ✕ Cancelar
                                </button>
                            </div>
                        @endif

                        <form wire:submit.prevent="{{ $editing_quote_id ? 'updateQuote' : 'createQuote' }}" class="space-y-5">
                            
                            <!-- SECCIÓN: PACKS DE SONIDO & DJ -->
                            <div class="bg-white p-4 rounded-xl border border-gray-200 space-y-3 shadow-xs">
                                <h6 class="text-xs font-black uppercase text-indigo-900 tracking-wider flex items-center gap-1.5 border-b pb-2">
                                    <span>🎛️</span> Packs de Sonido, Iluminación y DJ
                                </h6>

                                <div class="space-y-2">
                                    <!-- Pack Básico -->
                                    <label class="flex items-start justify-between p-2.5 rounded-lg border hover:bg-gray-50 cursor-pointer transition {{ $quote_services['pack_basic']['selected'] ? 'border-indigo-500 bg-indigo-50/50' : 'border-gray-200' }}">
                                        <div class="flex items-start gap-2.5">
                                            <input type="checkbox" wire:model.live="quote_services.pack_basic.selected" class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                            <div>
                                                <span class="font-bold text-xs text-gray-900">{{ $quote_services['pack_basic']['name'] }}</span>
                                                <p class="text-[11px] text-gray-500">{{ $quote_services['pack_basic']['desc'] }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <input type="number" step="0.01" wire:model.live="quote_services.pack_basic.price" class="w-18 border rounded p-1 text-xs text-right font-bold focus:ring-indigo-500">
                                            <span class="text-xs font-bold text-gray-600">€</span>
                                        </div>
                                    </label>

                                    <!-- Pack Medio -->
                                    <label class="flex items-start justify-between p-2.5 rounded-lg border hover:bg-gray-50 cursor-pointer transition {{ $quote_services['pack_medium']['selected'] ? 'border-emerald-500 bg-emerald-50/50' : 'border-gray-200' }}">
                                        <div class="flex items-start gap-2.5">
                                            <input type="checkbox" wire:model.live="quote_services.pack_medium.selected" class="mt-0.5 rounded text-emerald-600 focus:ring-emerald-500 h-4 w-4">
                                            <div>
                                                <span class="font-bold text-xs text-gray-900">{{ $quote_services['pack_medium']['name'] }}</span>
                                                <p class="text-[11px] text-gray-500">{{ $quote_services['pack_medium']['desc'] }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <input type="number" step="0.01" wire:model.live="quote_services.pack_medium.price" class="w-18 border rounded p-1 text-xs text-right font-bold focus:ring-indigo-500">
                                            <span class="text-xs font-bold text-gray-600">€</span>
                                        </div>
                                    </label>

                                    <!-- Pack Premium -->
                                    <label class="flex items-start justify-between p-2.5 rounded-lg border hover:bg-gray-50 cursor-pointer transition {{ $quote_services['pack_premium']['selected'] ? 'border-amber-500 bg-amber-50/50' : 'border-gray-200' }}">
                                        <div class="flex items-start gap-2.5">
                                            <input type="checkbox" wire:model.live="quote_services.pack_premium.selected" class="mt-0.5 rounded text-amber-600 focus:ring-amber-500 h-4 w-4">
                                            <div>
                                                <span class="font-bold text-xs text-gray-900">{{ $quote_services['pack_premium']['name'] }}</span>
                                                <p class="text-[11px] text-gray-500">{{ $quote_services['pack_premium']['desc'] }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <input type="number" step="0.01" wire:model.live="quote_services.pack_premium.price" class="w-18 border rounded p-1 text-xs text-right font-bold focus:ring-indigo-500">
                                            <span class="text-xs font-bold text-gray-600">€</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- SECCIÓN: SERVICIOS MUSICALES & AMBIENTACIÓN -->
                            <div class="bg-white p-4 rounded-xl border border-gray-200 space-y-3 shadow-xs">
                                <h6 class="text-xs font-black uppercase text-indigo-900 tracking-wider flex items-center gap-1.5 border-b pb-2">
                                    <span>🎵</span> Servicios Musicales & Momentos Clave
                                </h6>

                                <div class="space-y-2">
                                    <!-- Ceremonia -->
                                    <div class="flex items-center justify-between p-2 rounded-lg border border-gray-100 hover:bg-gray-50 gap-2">
                                        <label class="flex items-center cursor-pointer flex-1 gap-2">
                                            <input type="checkbox" wire:model.live="quote_services.ceremony.selected" class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                            <div>
                                                <span class="text-xs font-bold text-gray-800">Ceremonia</span>
                                                <span class="block text-[10px] text-gray-500">{{ $quote_services['ceremony']['desc'] }}</span>
                                            </div>
                                        </label>
                                        <div class="flex items-center gap-1">
                                            <input type="number" wire:model.live="quote_services.ceremony.quantity" class="w-10 border rounded p-1 text-xs text-center">
                                            <span class="text-xs text-gray-400">x</span>
                                            <input type="number" step="0.01" wire:model.live="quote_services.ceremony.price" class="w-16 border rounded p-1 text-xs text-right font-bold">
                                            <span class="text-xs text-gray-600">€</span>
                                        </div>
                                    </div>

                                    <!-- Cóctel -->
                                    <div class="flex items-center justify-between p-2 rounded-lg border border-gray-100 hover:bg-gray-50 gap-2">
                                        <label class="flex items-center cursor-pointer flex-1 gap-2">
                                            <input type="checkbox" wire:model.live="quote_services.cocktail.selected" class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                            <div>
                                                <span class="text-xs font-bold text-gray-800">Música en el Cóctel</span>
                                                <span class="block text-[10px] text-gray-500">{{ $quote_services['cocktail']['desc'] }}</span>
                                            </div>
                                        </label>
                                        <div class="flex items-center gap-1">
                                            <input type="number" wire:model.live="quote_services.cocktail.quantity" class="w-10 border rounded p-1 text-xs text-center">
                                            <span class="text-xs text-gray-400">x</span>
                                            <input type="number" step="0.01" wire:model.live="quote_services.cocktail.price" class="w-16 border rounded p-1 text-xs text-right font-bold">
                                            <span class="text-xs text-gray-600">€</span>
                                        </div>
                                    </div>

                                    <!-- Restaurante / Banquete -->
                                    <div class="flex items-center justify-between p-2 rounded-lg border border-gray-100 hover:bg-gray-50 gap-2">
                                        <label class="flex items-center cursor-pointer flex-1 gap-2">
                                            <input type="checkbox" wire:model.live="quote_services.restaurant.selected" class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                            <div>
                                                <span class="text-xs font-bold text-gray-800">Sonorización de Regalos & Banquete</span>
                                                <span class="block text-[10px] text-gray-500">{{ $quote_services['restaurant']['desc'] }}</span>
                                            </div>
                                        </label>
                                        <div class="flex items-center gap-1">
                                            <input type="number" wire:model.live="quote_services.restaurant.quantity" class="w-10 border rounded p-1 text-xs text-center">
                                            <span class="text-xs text-gray-400">x</span>
                                            <input type="number" step="0.01" wire:model.live="quote_services.restaurant.price" class="w-16 border rounded p-1 text-xs text-right font-bold">
                                            <span class="text-xs text-gray-600">€</span>
                                        </div>
                                    </div>

                                    @php
                                        $cSelected = !empty($quote_services['cocktail']['selected']);
                                        $rSelected = !empty($quote_services['restaurant']['selected']);
                                        $cPrice = (float)($quote_services['cocktail']['price'] ?? 0);
                                        $rPrice = (float)($quote_services['restaurant']['price'] ?? 0);
                                        $packDiscountInfo = \App\Models\Setting::getCocktailRestaurantPackInfo($cPrice, $rPrice);
                                    @endphp
                                    @if($cSelected && $rSelected && !empty($packDiscountInfo['enabled']) && $packDiscountInfo['discount_amount'] > 0)
                                        <div class="p-2.5 rounded-lg bg-emerald-50 border border-emerald-200 flex items-center justify-between text-xs text-emerald-800">
                                            <div class="flex items-center gap-1.5 font-bold">
                                                <span>🎁</span>
                                                <span>¡Pack Cóctel + Banquete Activado!</span>
                                            </div>
                                            <div class="font-extrabold text-emerald-700">
                                                -{{ number_format($packDiscountInfo['discount_amount'], 2, ',', '.') }} € ({{ round($packDiscountInfo['discount_percentage']) }}% dto)
                                            </div>
                                        </div>
                                    @endif

                                    <!-- DJ Baile (Horas sueltas) -->
                                    <div class="flex items-center justify-between p-2 rounded-lg border border-gray-100 hover:bg-gray-50 gap-2">
                                        <label class="flex items-center cursor-pointer flex-1 gap-2">
                                            <input type="checkbox" wire:model.live="quote_services.dj_custom.selected" class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                            <div>
                                                <span class="text-xs font-bold text-gray-800">DJ Baile (Horas)</span>
                                                <span class="block text-[10px] text-gray-500">{{ $quote_services['dj_custom']['desc'] }}</span>
                                            </div>
                                        </label>
                                        <div class="flex items-center gap-1">
                                            <input type="number" wire:model.live="quote_services.dj_custom.quantity" min="1" max="12" class="w-10 border rounded p-1 text-xs text-center">
                                            <span class="text-xs text-gray-400">h @</span>
                                            <input type="number" step="0.01" wire:model.live="quote_services.dj_custom.price" class="w-16 border rounded p-1 text-xs text-right font-bold">
                                            <span class="text-xs text-gray-600">€</span>
                                        </div>
                                    </div>

                                    <!-- Horas Extra Baile -->
                                    <div class="flex items-center justify-between p-2 rounded-lg border border-amber-200 bg-amber-50/50 gap-2">
                                        <div class="flex-1">
                                            <span class="text-xs font-bold text-amber-900">Horas Extra Baile (Opcionales)</span>
                                            <span class="block text-[10px] text-amber-700">Con opción a ampliar si el restaurante lo permite</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <input type="number" wire:model.live="quote_services.extra_hours.quantity" min="0" max="10" class="w-10 border rounded p-1 text-xs text-center font-bold">
                                            <span class="text-xs text-gray-400">h x</span>
                                            <input type="number" step="0.01" wire:model.live="quote_services.extra_hours.price" class="w-16 border rounded p-1 text-xs text-right font-bold">
                                            <span class="text-xs text-gray-600">€/h</span>
                                        </div>
                                    </div>

                                    <!-- Karaoke -->
                                    <div class="flex items-center justify-between p-2 rounded-lg border border-gray-100 hover:bg-gray-50 gap-2">
                                        <label class="flex items-center cursor-pointer flex-1 gap-2">
                                            <input type="checkbox" wire:model.live="quote_services.karaoke.selected" class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                            <div>
                                                <span class="text-xs font-bold text-gray-800">Karaoke Interactivo</span>
                                                <span class="block text-[10px] text-gray-500">{{ $quote_services['karaoke']['desc'] }}</span>
                                            </div>
                                        </label>
                                        <div class="flex items-center gap-1">
                                            <input type="number" step="0.01" wire:model.live="quote_services.karaoke.price" class="w-16 border rounded p-1 text-xs text-right font-bold">
                                            <span class="text-xs text-gray-600">€</span>
                                        </div>
                                    </div>

                                    <!-- Fotomatón & Photocall -->
                                    <div class="flex items-center justify-between p-2 rounded-lg border border-indigo-100 bg-indigo-50/30 hover:bg-indigo-50/60 gap-2">
                                        <label class="flex items-center cursor-pointer flex-1 gap-2">
                                            <input type="checkbox" wire:model.live="quote_services.photobooth.selected" class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                            <div>
                                                <span class="text-xs font-bold text-indigo-950">📸 Fotomatón & Photocall</span>
                                                <span class="block text-[10px] text-indigo-700">{{ $quote_services['photobooth']['desc'] }}</span>
                                            </div>
                                        </label>
                                        <div class="flex items-center gap-1">
                                            <input type="number" wire:model.live="quote_services.photobooth.quantity" min="1" class="w-10 border rounded p-1 text-xs text-center font-bold">
                                            <span class="text-xs text-gray-400">x</span>
                                            <input type="number" step="0.01" wire:model.live="quote_services.photobooth.price" class="w-16 border rounded p-1 text-xs text-right font-bold text-indigo-700">
                                            <span class="text-xs text-gray-600">€</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN: FOTOGRAFÍA -->
                            <div class="bg-white p-4 rounded-xl border border-gray-200 space-y-3 shadow-xs">
                                <h6 class="text-xs font-black uppercase text-purple-900 tracking-wider flex items-center gap-1.5 border-b pb-2">
                                    <span>📸</span> Servicios de Fotografía & Álbum
                                </h6>

                                <div class="space-y-2">
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

                            <!-- SECCIÓN: EXTRAS PERSONALIZADOS (MÚLTIPLES LÍNEAS) -->
                            <div class="bg-white p-4 rounded-xl border border-gray-200 space-y-4 shadow-xs">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b pb-2">
                                    <div>
                                        <h6 class="text-xs font-black uppercase text-gray-700 tracking-wider flex items-center gap-1.5">
                                            <span>✨</span> Extras Personalizados / Conceptos Adicionales
                                        </h6>
                                        <p class="text-[11px] text-gray-500">Añade tantas líneas como necesites (ej: Fuego frío con precio y Fotomatón a consultar).</p>
                                    </div>
                                    <button type="button" wire:click="addCustomExtra" class="inline-flex items-center gap-1 text-xs font-bold text-indigo-700 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 px-3 py-1.5 rounded-lg transition shadow-xs self-start sm:self-auto">
                                        <span>➕</span> Añadir Otro Extra
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    @foreach($custom_extras as $index => $extra)
                                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2 relative" wire:key="custom-extra-{{ $index }}">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-[11px] font-extrabold uppercase text-slate-600 flex items-center gap-1">
                                                    <span>🔹</span> Extra #{{ $index + 1 }}
                                                </span>
                                                
                                                <div class="flex items-center gap-2">
                                                    <label class="flex items-center gap-1.5 cursor-pointer text-xs font-bold text-amber-800 bg-amber-50 hover:bg-amber-100 px-2.5 py-1 rounded-lg border border-amber-200 transition">
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
                                                <input type="text" wire:model.live="custom_extras.{{ $index }}.name" placeholder="Ej: Fotomatón / Fuego frío / Plataforma 360..." class="flex-1 border-gray-300 rounded-lg p-2 text-xs focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                                
                                                @if(empty($extra['consult']))
                                                    <div class="flex items-center gap-1">
                                                        <input type="number" step="0.01" wire:model.live="custom_extras.{{ $index }}.price" placeholder="0.00" class="w-28 border-gray-300 rounded-lg p-2 text-xs text-right font-bold focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                                        <span class="text-xs font-bold text-gray-600">€</span>
                                                    </div>
                                                @else
                                                    <span class="text-xs font-bold text-amber-800 bg-amber-100/90 px-3 py-2 rounded-lg border border-amber-300 text-center whitespace-nowrap">
                                                        ⚠️ Precio a consultar
                                                    </span>
                                                @endif
                                            </div>

                                            <input type="text" wire:model.live="custom_extras.{{ $index }}.desc" placeholder="Descripción / detalle opcional (ej: Servicio 3 horas con atrezzo, libro de firmas y fotos ilimitadas)" class="w-full border-gray-300 rounded-lg p-1.5 text-[11px] text-gray-600 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- SECCIÓN: CONDICIONES DE SEÑAL DE RESERVA -->
                            <div class="bg-white p-4 rounded-xl border border-indigo-200 space-y-3 shadow-xs">
                                <div class="flex items-center justify-between border-b pb-2">
                                    <h6 class="text-xs font-black uppercase text-indigo-900 tracking-wider flex items-center gap-1.5">
                                        <span>💶</span> Señal de Reserva para esta Propuesta
                                    </h6>
                                    <span class="text-[10px] bg-indigo-100 text-indigo-800 font-bold px-2 py-0.5 rounded-full">Personalizable</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Modalidad de Señal</label>
                                        <div class="grid grid-cols-2 gap-1.5">
                                            <label class="flex items-center gap-1.5 p-2 rounded-lg border cursor-pointer transition text-xs {{ $quote_deposit_type === 'percentage' ? 'bg-indigo-50 border-indigo-500 font-bold text-indigo-900' : 'bg-gray-50 border-gray-200 text-gray-600' }}">
                                                <input type="radio" wire:model.live="quote_deposit_type" value="percentage" class="text-indigo-600 focus:ring-indigo-500">
                                                <span>Porcentaje (%)</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 p-2 rounded-lg border cursor-pointer transition text-xs {{ $quote_deposit_type === 'fixed' ? 'bg-indigo-50 border-indigo-500 font-bold text-indigo-900' : 'bg-gray-50 border-gray-200 text-gray-600' }}">
                                                <input type="radio" wire:model.live="quote_deposit_type" value="fixed" class="text-indigo-600 focus:ring-indigo-500">
                                                <span>Importe Fijo (€)</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div>
                                        @if($quote_deposit_type === 'percentage')
                                            <label class="block text-[11px] font-bold text-gray-700 mb-1">Porcentaje de Señal (%)</label>
                                            <div class="flex items-center gap-1.5">
                                                <input type="number" step="1" min="0" max="100" wire:model.live="quote_deposit_percentage" class="w-24 border rounded-lg p-2 text-xs font-black text-indigo-700 focus:ring-indigo-500">
                                                <span class="text-xs font-bold text-gray-500">% sobre el total</span>
                                            </div>
                                        @else
                                            <label class="block text-[11px] font-bold text-gray-700 mb-1">Importe de Señal (€)</label>
                                            <div class="flex items-center gap-1.5">
                                                <input type="number" step="1" min="0" wire:model.live="quote_deposit_fixed_amount" class="w-24 border rounded-lg p-2 text-xs font-black text-indigo-700 focus:ring-indigo-500">
                                                <span class="text-xs font-bold text-gray-500">€ fijos de reserva</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN: RÉGIMEN DE IVA & CONDICIONES FISCALES -->
                            <div class="bg-white p-4 rounded-xl border border-gray-200 space-y-3 shadow-xs">
                                <div class="flex items-center justify-between border-b pb-2">
                                    <h6 class="text-xs font-black uppercase text-indigo-900 tracking-wider flex items-center gap-1.5">
                                        <span>🏛️</span> Régimen de IVA y Desglose Fiscal
                                    </h6>
                                    <span class="text-[10px] bg-slate-100 text-slate-700 font-bold px-2 py-0.5 rounded-full">Fiscalidad</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                    <label class="flex items-center gap-1.5 p-2 rounded-lg border cursor-pointer transition text-xs {{ $quote_tax_type === 'included' ? 'bg-indigo-50 border-indigo-500 font-bold text-indigo-900 ring-1 ring-indigo-400' : 'bg-gray-50 border-gray-200 text-gray-600' }}">
                                        <input type="radio" wire:model.live="quote_tax_type" value="included" class="text-indigo-600 focus:ring-indigo-500">
                                        <div>
                                            <span class="block font-bold">IVA Incluido</span>
                                            <span class="text-[10px] text-gray-500">Recomendado (Novios / B2C)</span>
                                        </div>
                                    </label>

                                    <label class="flex items-center gap-1.5 p-2 rounded-lg border cursor-pointer transition text-xs {{ $quote_tax_type === 'excluded' ? 'bg-indigo-50 border-indigo-500 font-bold text-indigo-900 ring-1 ring-indigo-400' : 'bg-gray-50 border-gray-200 text-gray-600' }}">
                                        <input type="radio" wire:model.live="quote_tax_type" value="excluded" class="text-indigo-600 focus:ring-indigo-500">
                                        <div>
                                            <span class="block font-bold">Base + 21% IVA</span>
                                            <span class="text-[10px] text-gray-500">Empresas / B2B</span>
                                        </div>
                                    </label>

                                    <label class="flex items-center gap-1.5 p-2 rounded-lg border cursor-pointer transition text-xs {{ $quote_tax_type === 'none' ? 'bg-indigo-50 border-indigo-500 font-bold text-indigo-900 ring-1 ring-indigo-400' : 'bg-gray-50 border-gray-200 text-gray-600' }}">
                                        <input type="radio" wire:model.live="quote_tax_type" value="none" class="text-indigo-600 focus:ring-indigo-500">
                                        <div>
                                            <span class="block font-bold">Exento / Sin IVA</span>
                                            <span class="text-[10px] text-gray-500">Sin recargo</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- SECCIÓN: MÉTODOS DE PAGO PERMITIDOS -->
                            <div class="bg-white p-4 rounded-xl border border-gray-200 space-y-3 shadow-xs">
                                <div class="flex items-center justify-between border-b pb-2">
                                    <h6 class="text-xs font-black uppercase text-indigo-900 tracking-wider flex items-center gap-1.5">
                                        <span>💳</span> Métodos de Pago Permitidos en este Presupuesto
                                    </h6>
                                    <span class="text-[10px] text-gray-400">Selecciona los que aparecerán en el PDF</span>
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                    <label class="flex items-center gap-2 p-2 rounded-lg border cursor-pointer transition text-xs {{ in_array('transfer', $quote_payment_methods) ? 'bg-emerald-50 border-emerald-400 font-bold text-emerald-900' : 'bg-gray-50 border-gray-200 text-gray-500' }}">
                                        <input type="checkbox" wire:model.live="quote_payment_methods" value="transfer" class="rounded text-emerald-600 focus:ring-emerald-500">
                                        <span>🏦 Transferencia</span>
                                    </label>

                                    <label class="flex items-center gap-2 p-2 rounded-lg border cursor-pointer transition text-xs {{ in_array('bizum', $quote_payment_methods) ? 'bg-emerald-50 border-emerald-400 font-bold text-emerald-900' : 'bg-gray-50 border-gray-200 text-gray-500' }}">
                                        <input type="checkbox" wire:model.live="quote_payment_methods" value="bizum" class="rounded text-emerald-600 focus:ring-emerald-500">
                                        <span>📱 Bizum</span>
                                    </label>

                                    <label class="flex items-center gap-2 p-2 rounded-lg border cursor-pointer transition text-xs {{ in_array('cash', $quote_payment_methods) ? 'bg-emerald-50 border-emerald-400 font-bold text-emerald-900' : 'bg-gray-50 border-gray-200 text-gray-500' }}">
                                        <input type="checkbox" wire:model.live="quote_payment_methods" value="cash" class="rounded text-emerald-600 focus:ring-emerald-500">
                                        <span>💵 Efectivo</span>
                                    </label>

                                    <label class="flex items-center gap-2 p-2 rounded-lg border cursor-pointer transition text-xs {{ in_array('card', $quote_payment_methods) ? 'bg-emerald-50 border-emerald-400 font-bold text-emerald-900' : 'bg-gray-50 border-gray-200 text-gray-500' }}">
                                        <input type="checkbox" wire:model.live="quote_payment_methods" value="card" class="rounded text-emerald-600 focus:ring-emerald-500">
                                        <span>💳 Tarjeta</span>
                                    </label>
                                </div>
                            </div>

                            @if($editing_quote_id)
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-2">
                                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-black py-3.5 px-4 rounded-xl shadow-md transition flex items-center justify-center gap-2">
                                        <span>💾</span> Actualizar Propuesta #{{ $editing_quote_id }}
                                    </button>
                                    <button type="button" wire:click="createQuote" class="bg-indigo-600 hover:bg-indigo-700 text-white font-black py-3.5 px-4 rounded-xl shadow-md transition flex items-center justify-center gap-2">
                                        <span>➕</span> Guardar como Nueva Versión
                                    </button>
                                </div>
                            @else
                                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-3.5 px-4 rounded-xl shadow-md transition flex items-center justify-center gap-2">
                                    <span>📄</span> Generar Propuesta Comercial en PDF
                                </button>
                            @endif
                        </form>
                    </div>

                    <!-- COLUMNA DERECHA: RESUMEN EN VIVO Y HISTORIAL DE PROPUESTAS -->
                    <div class="lg:col-span-5 space-y-6">
                        
                        <!-- TARJETA RESUMEN EN VIVO -->
                        <div class="bg-slate-900 text-white p-5 rounded-2xl shadow-xl space-y-4 border border-slate-800">
                            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                                <h5 class="font-black text-xs uppercase tracking-wider text-slate-300 flex items-center gap-2">
                                    <span>📊</span> Resumen Económico en Vivo
                                </h5>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[10px] bg-indigo-500/20 text-indigo-300 px-2 py-0.5 rounded-full font-bold border border-indigo-400/30">
                                        {{ $quote_tax_type === 'included' ? 'IVA 21% Incluido' : ($quote_tax_type === 'excluded' ? '+21% IVA' : 'Sin IVA') }}
                                    </span>
                                    <span class="text-[10px] bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded-full font-bold border border-emerald-400/30">
                                        Señal {{ $quote_deposit_type === 'percentage' ? ($quote_deposit_percentage . '%') : 'Fija' }}
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="space-y-1.5 text-xs text-slate-300">
                                    <div class="flex justify-between">
                                        <span class="text-slate-400">Base Imponible:</span>
                                        <span class="font-mono font-bold text-slate-200">{{ number_format($this->calculated_subtotal, 2) }} €</span>
                                    </div>

                                    @if($quote_tax_type !== 'none')
                                        <div class="flex justify-between">
                                            <span class="text-slate-400">IVA ({{ $quote_tax_rate }}%):</span>
                                            <span class="font-mono font-bold text-slate-300">{{ number_format($this->calculated_tax_amount, 2) }} €</span>
                                        </div>
                                    @endif

                                    <div class="flex items-baseline justify-between pt-2 border-t border-slate-800">
                                        <div>
                                            <span class="text-sm font-black text-white">TOTAL PROPUESTA:</span>
                                            <span class="block text-[10px] text-slate-400">
                                                {{ $quote_tax_type === 'included' ? '(IVA 21% incluido)' : ($quote_tax_type === 'excluded' ? '(Base + IVA)' : '(Exento de IVA)') }}
                                            </span>
                                        </div>
                                        <span class="text-2xl font-black text-emerald-400">{{ number_format($this->calculated_total, 2) }} €</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-800">
                                    <div class="bg-slate-800/80 p-3 rounded-xl border border-slate-700">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-[10px] uppercase font-black text-emerald-400">
                                                Señal {{ $quote_deposit_type === 'percentage' ? '(' . $quote_deposit_percentage . '%)' : '(Fija)' }}
                                            </span>
                                            <span class="text-[10px] text-slate-400">A la firma</span>
                                        </div>
                                        <span class="text-lg font-black text-emerald-400">{{ number_format($this->calculated_signal, 2) }} €</span>
                                    </div>

                                    <div class="bg-slate-800/80 p-3 rounded-xl border border-slate-700">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-[10px] uppercase font-black text-amber-400">Restante</span>
                                            <span class="text-[10px] text-slate-400">Día del evento</span>
                                        </div>
                                        <span class="text-lg font-black text-amber-300">{{ number_format($this->calculated_remaining, 2) }} €</span>
                                    </div>
                                </div>

                                <!-- Métodos de pago activos -->
                                <div class="pt-2 border-t border-slate-800 flex flex-wrap items-center gap-1.5 text-[11px] text-slate-400">
                                    <span class="text-[10px] text-slate-500 font-bold mr-1">Cobro por:</span>
                                    @if(in_array('transfer', $quote_payment_methods))
                                        <span class="bg-slate-800 text-slate-300 px-2 py-0.5 rounded border border-slate-700">🏦 Transferencia</span>
                                    @endif
                                    @if(in_array('bizum', $quote_payment_methods))
                                        <span class="bg-slate-800 text-slate-300 px-2 py-0.5 rounded border border-slate-700">📱 Bizum</span>
                                    @endif
                                    @if(in_array('cash', $quote_payment_methods))
                                        <span class="bg-slate-800 text-slate-300 px-2 py-0.5 rounded border border-slate-700">💵 Efectivo</span>
                                    @endif
                                    @if(in_array('card', $quote_payment_methods))
                                        <span class="bg-slate-800 text-slate-300 px-2 py-0.5 rounded border border-slate-700">💳 Tarjeta</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- HISTORIAL DE PROPUESTAS GENERADAS (ORDENADAS POR LA MÁS RECIENTE) -->
                        <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                            <div class="border-b pb-2 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <h5 class="font-bold text-gray-900 text-sm">📜 Historial de Propuestas</h5>
                                    <span class="text-xs bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded-full font-bold">{{ $event->quotes->count() }} emitidas</span>
                                </div>
                                <span class="text-[11px] text-gray-400 font-medium">Más reciente arriba</span>
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
                                        $quoteMsg = "¡Hola " . ($event->client ? $event->client->name : 'pareja') . "! 👋\n\nTe adjuntamos el presupuesto detallado para *" . $event->name . "* por un importe total de *" . number_format($quote->amount, 2, ',', '.') . " €* (" . $taxLabel . " - Señal de reserva: *" . number_format($qSignal, 2, ',', '.') . " €* - " . $qLabel . ").\n\nPodéis consultarlo con calma y avisarnos para cualquier duda o cambio. ¡Un saludo!";
                                        $qWaUrl = $clientPhone ? "https://wa.me/{$clientPhone}?text=" . rawurlencode($quoteMsg) : "https://api.whatsapp.com/send?text=" . rawurlencode($quoteMsg);
                                    @endphp
                                    <div class="p-4 border rounded-xl transition shadow-xs space-y-3 {{ $isEditingThis ? 'border-amber-400 bg-amber-50/40 ring-2 ring-amber-300' : ($isLatest ? 'border-emerald-300 bg-emerald-50/20' : 'border-gray-200 bg-gray-50/60 opacity-90') }}">
                                        
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
                                                </div>
                                                <p class="text-[11px] text-gray-500 mt-1">
                                                    <strong>Propuesta #{{ $quote->id }}</strong> &bull; Emitida: {{ $quote->created_at->format('d/m/Y H:i') }} &bull; Señal: {{ $qLabel }}
                                                </p>

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

                                                <a href="{{ $qWaUrl }}" target="_blank" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 text-xs font-bold px-2.5 py-1.5 rounded-lg flex items-center gap-1 transition shadow-2xs" title="Compartir por WhatsApp">
                                                    💬 WhatsApp
                                                </a>

                                                <a href="{{ route('admin.quote.pdf', $quote->id) }}" target="_blank" class="bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-300 text-xs font-bold px-2.5 py-1.5 rounded-lg flex items-center gap-1 transition shadow-2xs" title="Descargar o Ver PDF de Propuesta">
                                                    📑 PDF
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
                                                    <div class="flex justify-between">
                                                        <span>&bull; {{ $item->concept ?: $item->service_name }} {{ $item->quantity > 1 ? '('.$item->quantity.'x)' : '' }}</span>
                                                        <span class="font-mono font-bold text-gray-800">{{ number_format($item->subtotal ?: ($item->price * $item->quantity), 2) }} €</span>
                                                    </div>
                                                @endforeach
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
                    <button wire:click="createContract" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-md transition">
                        <span>➕</span> Generar Nuevo Contrato
                    </button>
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
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-extrabold text-slate-800">Contrato #CTR-{{ str_pad($contract->id, 5, '0', STR_PAD_LEFT) }}</span>
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

                                    <a href="{{ $contractWaUrl }}" target="_blank" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-2xs" title="Enviar enlace de firma por WhatsApp">
                                        💬 WhatsApp
                                    </a>

                                    <a href="{{ route('admin.contract.pdf', $contract->id) }}" target="_blank" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold transition flex items-center gap-1" title="Descargar contrato oficial en PDF">
                                        📄 PDF
                                    </a>

                                    @if($contract->status == 'pending')
                                        <button wire:click="signContract({{ $contract->id }})" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-semibold transition cursor-pointer" title="Marcar como firmado manualmente">
                                            ✓ Marcar Firmado
                                        </button>
                                    @endif
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
                    <h5 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Documentos Emitidos ({{ $event->invoices->count() }})</h5>

                    @forelse($event->invoices->sortByDesc('id') as $invoice)
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
                                <select wire:model="assigned_dj_id" class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">-- Sin DJ asignado --</option>
                                    @foreach($allDjs as $d)
                                        <option value="{{ $d->id }}">{{ $d->name }} {{ $d->phone ? '('.$d->phone.')' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-amber-900 mb-1">👷‍♂️ Asistente (Ceremonia, Cóctel, Banquete)</label>
                                <select wire:model="assigned_assistant_id" class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">-- Sin asistente asignado --</option>
                                    @foreach($allAssistants as $a)
                                        <option value="{{ $a->id }}">{{ $a->name }} {{ $a->phone ? '('.$a->phone.')' : '' }}</option>
                                    @endforeach
                                </select>
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
                
                <form wire:submit.prevent="saveSongRequest">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex justify-between items-center mb-4 border-b pb-2">
                            <h3 class="text-lg font-bold text-gray-900" id="modal-title">
                                {{ $editingSongId ? '✏️ Editar Canción / Momento' : '➕ Añadir Canción a la Escaleta' }}
                            </h3>
                            <button type="button" wire:click="$set('showSongModal', false)" class="text-gray-400 hover:text-gray-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        
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

                            <!-- Categoría y Momento -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Fase / Categoría *</label>
                                    <select wire:model.live="req_category" required class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="ceremonia">💍 Ceremonia</option>
                                        <option value="coctel">🍸 Cóctel</option>
                                        <option value="banquete">🍽️ Banquete / Regalos</option>
                                        <option value="baile">💃 Baile / Fiesta</option>
                                        <option value="lista_negra">🚫 Lista Negra</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Momento Clave *</label>
                                    <input type="text" list="moments_list" wire:model="req_moment" placeholder="Ej: Entrada Comedor, Regalos Padres..." required class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <datalist id="moments_list">
                                        <option value="Entrada Novios">
                                        <option value="Entrada Comedor">
                                        <option value="Regalos Padres">
                                        <option value="Entrega de Ramo">
                                        <option value="Regalo Amigos">
                                        <option value="Corte de Tarta">
                                        <option value="Baile Nupcial">
                                        <option value="Apertura Baile">
                                        <option value="Hora Loca">
                                        <option value="Prohibida">
                                    </datalist>
                                    @error('req_moment') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Título y Artista -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Título de la Canción *</label>
                                    <input type="text" wire:model="req_title" placeholder="Ej: Será Porque Te Amo" required class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    @error('req_title') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Artista / Versión</label>
                                    <input type="text" wire:model="req_artist" placeholder="Ej: DJ Matrix / Ricchi e Poveri" class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
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

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Enlace YouTube</label>
                                        <input type="url" wire:model="req_youtube_url" placeholder="https://youtube.com/watch?v=..." class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Enlace Spotify</label>
                                        <input type="url" wire:model="req_spotify_url" placeholder="https://open.spotify.com/track/..." class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">🍎 Apple Music</label>
                                        <input type="url" wire:model="req_apple_music_url" placeholder="https://music.apple.com/..." class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                </div>
                            </div>

                            <!-- Subir Audio MP3 -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Subir Archivo de Audio (MP3 / WAV)</label>
                                <input type="file" wire:model="req_audio_file" accept="audio/*" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                <div wire:loading wire:target="req_audio_file" class="text-xs text-indigo-600 mt-1">Subiendo audio...</div>
                                @if($existing_audio_file)
                                    <div class="text-[11px] text-emerald-600 mt-1">✓ Ya existe un archivo de audio guardado. Sube otro solo si quieres reemplazarlo.</div>
                                @endif
                                @error('req_audio_file') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Notas / Instrucciones -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Notas / Instrucciones para el DJ</label>
                                <textarea wire:model="req_notes" rows="2" placeholder="Ej: Lanzar cuando entren con el regalo y bajar volumen al dar el micrófono..." class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                            </div>
                        </div>

                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-xl gap-2">
                        <button type="submit" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-sm font-bold text-white hover:bg-indigo-700 focus:outline-none transition">
                            Guardar Canción
                        </button>
                        <button type="button" wire:click="$set('showSongModal', false)" class="w-full sm:w-auto mt-2 sm:mt-0 inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none transition">
                            Cancelar
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
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nombre del Responsable / Coordinador</label>
                            <input type="text" wire:model="venue_contact_name" placeholder="Ej: Marta (Coordinadora Eventos) o Juan (Maître)" class="w-full border-gray-300 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Teléfono Directo de Contacto</label>
                            <input type="tel" wire:model="venue_contact_phone" placeholder="Ej: 612 345 678" class="w-full border-gray-300 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500">
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
                            <h3 class="font-extrabold text-base leading-tight" id="modal-client-title">Editar Ficha y Credenciales del Cliente</h3>
                            <p class="text-xs text-emerald-100">Evento: {{ $event->name }}</p>
                        </div>
                    </div>
                    <button type="button" wire:click="$set('showClientModal', false)" class="text-emerald-100 hover:text-white text-2xl font-bold leading-none">&times;</button>
                </div>

                <form wire:submit.prevent="saveClientDetails">
                    <div class="p-6 space-y-4 text-xs">
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
                                <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">DNI / NIF</label>
                                <input type="text" wire:model="client_edit_dni" placeholder="12345678Z" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2.5 text-xs font-mono font-bold text-slate-800 dark:text-white bg-slate-50 dark:bg-slate-950 uppercase">
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

                    <div class="bg-gray-50 dark:bg-slate-950 px-6 py-3 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-2">
                        <button type="button" wire:click="$set('showClientModal', false)" class="bg-white dark:bg-slate-800 hover:bg-slate-100 text-slate-700 dark:text-slate-300 font-bold text-xs px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl transition">
                            Cancelar
                        </button>
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl shadow-md transition cursor-pointer">
                            💾 Guardar Datos del Cliente
                        </button>
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

                    <!-- Categoría y Momento destino -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3.5 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider text-[10px] mb-1">
                                Categoría de Destino
                            </label>
                            <select wire:model="cloudImportCategory" class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2 text-xs font-bold text-slate-800 dark:text-white bg-white dark:bg-slate-900">
                                <option value="ceremonia">💍 Ceremonia</option>
                                <option value="coctel">🍸 Cóctel</option>
                                <option value="banquete">🍽️ Banquete</option>
                                <option value="baile">💃 Baile / Fiesta</option>
                                <option value="lista_negra">🚫 Lista Negra</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider text-[10px] mb-1">
                                Momento / Etiqueta
                            </label>
                            <input 
                                type="text" 
                                wire:model="cloudImportMoment" 
                                placeholder="Ej: Entrada Novios, Banquete, Ambiente..." 
                                class="w-full border-slate-300 dark:border-slate-700 rounded-xl p-2 text-xs font-semibold text-slate-800 dark:text-white bg-white dark:bg-slate-900"
                            >
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
                            <button type="button" wire:click="$set('deposit_amount_input', {{ $signalAmount }})" class="text-[10px] font-bold bg-slate-100 hover:bg-emerald-100 text-slate-700 hover:text-emerald-800 border border-slate-200 px-2 py-0.5 rounded-lg transition cursor-pointer">
                                Señal Propuesta ({{ number_format($signalAmount, 2, ',', '.') }} €)
                            </button>
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

</div>
