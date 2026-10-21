<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8 font-sans">

    <!-- CABECERA DE MARCA Y EVENTO -->
    <div class="text-center mb-8">
        <div class="w-16 h-16 bg-gradient-to-tr from-indigo-600 to-purple-600 rounded-3xl mx-auto flex items-center justify-center text-white text-3xl shadow-xl shadow-indigo-500/30 mb-4">
            📑
        </div>
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 text-xs font-extrabold uppercase tracking-wider mb-2">
            {{ $brand['name'] ?? 'Núñez & Son' }} &bull; Propuesta Oficial
        </div>
        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
            {{ $event->name }}
        </h1>
        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1.5 flex items-center justify-center gap-2 flex-wrap">
            @if($event->event_date)
                <span>📅 <strong>{{ \Carbon\Carbon::parse($event->event_date)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</strong></span>
            @endif
            @if($event->location)
                <span>&bull; 📍 <strong>{{ $event->location }}</strong></span>
            @endif
        </p>
    </div>

    @if(session()->has('quote_response_success'))
        <div class="mb-6 p-4 rounded-2xl bg-emerald-600 text-white font-bold text-sm shadow-lg flex items-center gap-3">
            <span class="text-2xl">🎉</span>
            <span>{{ session('quote_response_success') }}</span>
        </div>
    @endif

    @if(session()->has('quote_response_rejected'))
        <div class="mb-6 p-4 rounded-2xl bg-slate-800 text-white font-bold text-sm shadow-lg flex items-center gap-3">
            <span class="text-2xl">📝</span>
            <span>{{ session('quote_response_rejected') }}</span>
        </div>
    @endif

    @if(!$quote)
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-8 text-center shadow-sm">
            <div class="text-4xl mb-3">⏳</div>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Propuesta en preparación</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Actualmente estamos preparando vuestro presupuesto personalizado. En cuanto esté listo podréis consultarlo y descargarlo desde este mismo enlace.
            </p>
        </div>
    @else

        <!-- SELECTOR DE VERSIONES SI HAY MÁS DE UN PRESUPUESTO -->
        @if($event->quotes->count() > 1)
            <div class="mb-6 flex items-center justify-center gap-2 flex-wrap">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mr-1">Versiones disponibles:</span>
                @foreach($event->quotes->sortByDesc('id') as $qItem)
                    <button type="button"
                        wire:click="selectQuote({{ $qItem->id }})"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-extrabold border transition cursor-pointer {{ $quote->id === $qItem->id ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-800 hover:border-indigo-400' }}">
                        Propuesta #{{ $qItem->id }} ({{ number_format((float)$qItem->amount, 0, ',', '.') }} €)
                        @if($qItem->status === 'accepted') ✅ @elseif($qItem->status === 'rejected') ❌ @endif
                    </button>
                @endforeach
            </div>
        @endif

        <!-- BARRA PRINCIPAL: DESCARGAR DOSSIER PDF COMPLETO -->
        <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white rounded-3xl p-5 sm:p-6 shadow-xl mb-6 border border-indigo-500/30 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4 text-center sm:text-left">
                <div class="w-12 h-12 rounded-2xl bg-indigo-500/20 border border-indigo-400/40 flex items-center justify-center text-2xl shrink-0 mx-auto sm:mx-0">
                    📄
                </div>
                <div>
                    <span class="text-[11px] font-extrabold uppercase tracking-widest text-amber-400 block">Dossier Comercial & Presupuesto Oficial</span>
                    <h2 class="text-base sm:text-lg font-black text-white">Propuesta #{{ $quote->id }} &bull; {{ number_format((float)$quote->amount, 2, ',', '.') }} €</h2>
                    <p class="text-xs text-slate-300 mt-0.5">
                        Emitida el {{ $quote->created_at_formatted }} &bull; Válida hasta el <strong>{{ $quote->expires_at_formatted }}</strong>
                    </p>
                </div>
            </div>
            <a href="{{ route('pdf.quote', ['quote' => $quote->id, 'token' => $event->token]) }}"
               target="_blank"
               class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black px-5 py-3 rounded-2xl text-xs sm:text-sm shadow-lg shadow-amber-500/20 transition shrink-0">
                <span>⬇️</span>
                <span>Descargar Dossier en PDF</span>
            </a>
        </div>

        <!-- ESTADO ACTUAL SI YA HA SIDO ACEPTADO O RECHAZADO -->
        @if($quote->status === 'accepted' && $actionMode === 'view')
            <div class="bg-emerald-50 dark:bg-emerald-950/40 border-2 border-emerald-500 rounded-3xl p-6 sm:p-8 text-center shadow-lg mb-8">
                <div class="w-14 h-14 bg-emerald-100 dark:bg-emerald-900/60 text-emerald-600 dark:text-emerald-300 rounded-2xl mx-auto flex items-center justify-center text-3xl mb-3">
                    ✅
                </div>
                <h2 class="text-xl font-black text-emerald-900 dark:text-emerald-200">
                    ¡Presupuesto Aceptado!
                </h2>
                <p class="text-xs sm:text-sm text-emerald-800 dark:text-emerald-300 mt-1 max-w-lg mx-auto">
                    Habéis aceptado esta propuesta comercial{{ $quote->responded_at ? ' el ' . $quote->responded_at->format('d/m/Y \a \l\a\s H:i') : '' }}. El estado de vuestro evento ha quedado actualizado como <strong>Confirmado</strong>.
                </p>

                @if($quote->client_notes)
                    <div class="mt-4 max-w-md mx-auto bg-white dark:bg-slate-900 p-3.5 rounded-2xl border border-emerald-200 dark:border-emerald-800 text-xs text-slate-700 dark:text-slate-300 text-left">
                        <span class="font-bold text-emerald-700 dark:text-emerald-400 block mb-0.5">💬 Vuestro comentario:</span>
                        {{ $quote->client_notes }}
                    </div>
                @endif

                @if($quote->signal_amount > 0 && ($companyIban || $companyBizum))
                    <div class="mt-5 max-w-lg mx-auto bg-white dark:bg-slate-900 p-4 rounded-2xl border border-emerald-300 dark:border-emerald-800 text-left space-y-2">
                        <div class="text-xs font-extrabold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">
                            📌 Datos para abonar la señal de reserva ({{ number_format($quote->signal_amount, 2, ',', '.') }} €):
                        </div>
                        @if(in_array('transfer', $quote->active_payment_methods) && $companyIban)
                            <div class="text-xs text-slate-700 dark:text-slate-300">
                                🏦 <strong>Transferencia Bancaria (IBAN):</strong>
                                <span class="font-mono font-bold text-slate-900 dark:text-white select-all block sm:inline mt-0.5">{{ $companyIban }}</span>
                            </div>
                        @endif
                        @if(in_array('bizum', $quote->active_payment_methods) && $companyBizum)
                            <div class="text-xs text-slate-700 dark:text-slate-300">
                                📱 <strong>Bizum:</strong>
                                <span class="font-mono font-bold text-slate-900 dark:text-white select-all">{{ $companyBizum }}</span>
                            </div>
                        @endif
                        <div class="text-[11px] text-slate-500 dark:text-slate-400">
                            Concepto: <strong>{{ $event->name }}</strong>
                        </div>
                    </div>
                @endif

                <div class="mt-5 flex items-center justify-center gap-3 flex-wrap">
                    <a href="{{ route('pdf.quote', ['quote' => $quote->id, 'token' => $event->token]) }}" target="_blank" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-2.5 rounded-xl text-xs shadow-sm transition">
                        <span>📄</span> Descargar Dossier PDF
                    </a>
                    <button type="button" wire:click="openRejectBox" class="text-xs text-slate-500 hover:text-rose-600 dark:text-slate-400 underline cursor-pointer">
                        ¿Necesitas cancelar o rechazar la propuesta?
                    </button>
                </div>
            </div>
        @elseif($quote->status === 'rejected' && $actionMode === 'view')
            <div class="bg-rose-50 dark:bg-rose-950/30 border-2 border-rose-400 dark:border-rose-700 rounded-3xl p-6 sm:p-8 text-center shadow-md mb-8">
                <div class="w-14 h-14 bg-rose-100 dark:bg-rose-900/60 text-rose-600 dark:text-rose-300 rounded-2xl mx-auto flex items-center justify-center text-3xl mb-3">
                    ❌
                </div>
                <h2 class="text-xl font-black text-rose-900 dark:text-rose-200">
                    Propuesta Rechazada
                </h2>
                <p class="text-xs sm:text-sm text-rose-800 dark:text-rose-300 mt-1 max-w-lg mx-auto">
                    Esta propuesta figura como rechazada{{ $quote->responded_at ? ' el ' . $quote->responded_at->format('d/m/Y \a \l\a\s H:i') : '' }}.
                </p>
                @if($quote->rejection_reason)
                    <div class="mt-4 max-w-md mx-auto bg-white dark:bg-slate-900 p-3.5 rounded-2xl border border-rose-200 dark:border-rose-800 text-xs text-slate-700 dark:text-slate-300 text-left">
                        <span class="font-bold text-rose-700 dark:text-rose-400 block mb-0.5">📝 Motivo indicado:</span>
                        {{ $quote->rejection_reason }}
                    </div>
                @endif
                <div class="mt-5">
                    <button type="button" wire:click="openAcceptBox" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-2.5 rounded-xl text-xs shadow-sm transition cursor-pointer">
                        <span>✅</span> ¿Habéis cambiado de opinión? Aceptar Propuesta
                    </button>
                </div>
            </div>
        @endif

        <!-- DESGLOSE DETALLADO DEL PRESUPUESTO -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm overflow-hidden mb-8">
            <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between flex-wrap gap-2">
                <div>
                    <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">
                        📋 Desglose de Servicios Incluidos
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Detalle de equipamiento, cobertura musical y extras seleccionados para vuestro evento
                    </p>
                </div>
                <span class="text-xs font-bold px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                    {{ $quote->tax_label }}
                </span>
            </div>

            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach($quote->items as $item)
                    @php
                        $lineTotal = (float)$item->price * (int)($item->quantity ?: 1);
                        $isDiscount = $lineTotal < 0;
                        $isIncluded = $lineTotal == 0 && !str_contains(mb_strtolower($item->service_name ?? ''), 'consultar');
                    @endphp
                    <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 {{ $isDiscount ? 'bg-emerald-50/50 dark:bg-emerald-950/20' : '' }}">
                        <div class="space-y-1 pr-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-extrabold text-sm {{ $isDiscount ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-900 dark:text-white' }}">
                                    {{ $item->service_name ?: $item->concept }}
                                </span>
                                @if(($item->quantity ?? 1) > 1)
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300">
                                        x{{ $item->quantity }}
                                    </span>
                                @endif
                            </div>
                            @if($item->description)
                                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                                    {{ $item->description }}
                                </p>
                            @endif
                        </div>
                        <div class="sm:text-right shrink-0">
                            @if($isDiscount)
                                <span class="text-sm font-black text-emerald-600 dark:text-emerald-400">
                                    {{ number_format($lineTotal, 2, ',', '.') }} €
                                </span>
                            @elseif($lineTotal == 0)
                                <span class="text-xs font-extrabold uppercase px-2.5 py-1 rounded-lg bg-emerald-100 dark:bg-emerald-950/70 text-emerald-800 dark:text-emerald-300">
                                    {{ str_contains(mb_strtolower($item->service_name ?? ''), 'consultar') ? 'A consultar' : 'Incluido' }}
                                </span>
                            @else
                                <span class="text-sm sm:text-base font-black text-slate-900 dark:text-white">
                                    {{ number_format($lineTotal, 2, ',', '.') }} €
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if(!empty($quote->admin_notes))
                <div class="px-5 py-4 bg-amber-50/80 dark:bg-amber-950/30 border-t border-amber-200 dark:border-amber-800/60">
                    <div class="flex items-start gap-2.5">
                        <span class="text-base leading-none mt-0.5">📝</span>
                        <div class="text-xs text-amber-950 dark:text-amber-200 leading-relaxed">
                            <strong class="font-black uppercase tracking-wider text-[11px] text-amber-900 dark:text-amber-300 block mb-1">Notas y Condiciones Especiales de la Propuesta:</strong>
                            <p class="whitespace-pre-line font-medium">{{ $quote->admin_notes }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- TOTALES Y CONDICIONES DE RESERVA -->
            <div class="bg-slate-50 dark:bg-slate-950/60 p-5 sm:p-6 border-t border-slate-200 dark:border-slate-800">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Importe Total Presupuesto</span>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400">{{ $quote->tax_label }}</span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-indigo-600 dark:text-indigo-400">
                        {{ number_format((float)$quote->amount, 2, ',', '.') }} €
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 text-xs">
                    <div class="bg-white dark:bg-slate-900 p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500 dark:text-slate-400 font-semibold block">1️⃣ Señal de Reserva para bloquear fecha:</span>
                        <strong class="text-sm text-slate-900 dark:text-white mt-0.5 block">
                            {{ $quote->signal_amount > 0 ? number_format($quote->signal_amount, 2, ',', '.') . ' € (' . $quote->signal_label . ')' : 'Sin señal previa (0,00 €)' }}
                        </strong>
                    </div>
                    <div class="bg-white dark:bg-slate-900 p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800">
                        <span class="text-slate-500 dark:text-slate-400 font-semibold block">2️⃣ Restante a abonar en el evento:</span>
                        <strong class="text-sm text-slate-900 dark:text-white mt-0.5 block">
                            {{ number_format($quote->remaining_amount, 2, ',', '.') }} €
                        </strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- PANEL DE DECISIÓN DEL CLIENTE: ACEPTAR O RECHAZAR PRESUPUESTO -->
        <div class="bg-white dark:bg-slate-900 border-2 border-indigo-500/40 dark:border-indigo-500/30 rounded-3xl p-6 sm:p-8 shadow-xl">
            <div class="text-center max-w-xl mx-auto mb-6">
                <span class="text-[11px] font-extrabold uppercase tracking-widest text-indigo-600 dark:text-indigo-400 block mb-1">
                    Respuesta Online Directa
                </span>
                <h3 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white">
                    ¿Qué os parece nuestra propuesta para {{ $event->name }}?
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Podéis aceptar el presupuesto directamente desde aquí para confirmar vuestra fecha o indicarnos si preferís rechazarlo.
                </p>
            </div>

            @if($actionMode === 'view')
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4 max-w-lg mx-auto">
                    <button type="button"
                            wire:click="openAcceptBox"
                            class="w-full sm:flex-1 inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-black py-3.5 px-6 rounded-2xl text-sm shadow-lg shadow-emerald-600/25 transition cursor-pointer">
                        <span>✅</span>
                        <span>Aceptar Presupuesto</span>
                    </button>

                    <button type="button"
                            wire:click="openRejectBox"
                            class="w-full sm:flex-1 inline-flex items-center justify-center gap-2 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/50 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 border border-rose-300 dark:border-rose-800 font-bold py-3.5 px-6 rounded-2xl text-sm transition cursor-pointer">
                        <span>❌</span>
                        <span>Rechazar Presupuesto</span>
                    </button>
                </div>
            @endif

            <!-- FORMULARIO DE CONFIRMACIÓN DE ACEPTACIÓN -->
            @if($actionMode === 'accept_box')
                <div class="max-w-lg mx-auto bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-300 dark:border-emerald-800 rounded-2xl p-5 space-y-4">
                    <div class="flex items-center gap-2.5">
                        <span class="text-2xl">🎉</span>
                        <div>
                            <h4 class="text-sm font-black text-emerald-950 dark:text-emerald-200">
                                Confirmar Aceptación de la Propuesta #{{ $quote->id }}
                            </h4>
                            <p class="text-xs text-emerald-800 dark:text-emerald-300">
                                Importe total: <strong>{{ number_format((float)$quote->amount, 2, ',', '.') }} €</strong> ({{ $quote->tax_label }})
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Comentario u observación opcional (ej. dudas, horario, etc.):
                        </label>
                        <textarea wire:model="client_notes"
                                  rows="2"
                                  placeholder="Opcional: ¡Todo genial! Contad con nosotros..."
                                  class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-3.5 py-2 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:outline-none"></textarea>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center gap-2.5 pt-1">
                        <button type="button"
                                wire:click="acceptQuoteOnline"
                                wire:loading.attr="disabled"
                                class="w-full sm:flex-1 inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-black py-3 px-5 rounded-xl text-xs sm:text-sm shadow-md transition cursor-pointer">
                            <span wire:loading.remove wire:target="acceptQuoteOnline">✅ Confirmar y Aceptar Ahora</span>
                            <span wire:loading wire:target="acceptQuoteOnline">⏳ Confirmando...</span>
                        </button>
                        <button type="button"
                                wire:click="cancelAction"
                                class="w-full sm:w-auto px-4 py-3 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800 transition cursor-pointer">
                            Volver
                        </button>
                    </div>
                </div>
            @endif

            <!-- FORMULARIO DE RECHAZO CON MOTIVO OBLIGATORIO -->
            @if($actionMode === 'reject_box')
                <div class="max-w-lg mx-auto bg-rose-50/70 dark:bg-rose-950/30 border-2 border-rose-300 dark:border-rose-800 rounded-2xl p-5 space-y-4">
                    <div class="flex items-start gap-2.5">
                        <span class="text-2xl">❌</span>
                        <div>
                            <h4 class="text-sm font-black text-rose-950 dark:text-rose-200">
                                Rechazar Propuesta #{{ $quote->id }}
                            </h4>
                            <p class="text-xs text-rose-800 dark:text-rose-300">
                                Para poder tramitar el rechazo y liberar la fecha en nuestra agenda, <strong>es obligatorio indicar el motivo</strong>.
                            </p>
                        </div>
                    </div>

                    <!-- Botones rápidos de motivo -->
                    <div>
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-2">
                            Selecciona un motivo rápido o escríbelo debajo:
                        </span>
                        <div class="flex flex-wrap gap-1.5">
                            @php
                                $quickReasons = [
                                    'Se nos va de presupuesto',
                                    'Ya hemos contratado otra opción',
                                    'Queremos adaptar o quitar algún servicio del presupuesto',
                                    'Cambio de fecha o planes del evento',
                                ];
                            @endphp
                            @foreach($quickReasons as $qReason)
                                <button type="button"
                                        wire:click="pickRejectionTag('{{ $qReason }}')"
                                        class="text-[11px] font-bold px-3 py-1.5 rounded-xl border transition cursor-pointer {{ $rejection_reason === $qReason ? 'bg-rose-600 text-white border-rose-600' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700 hover:border-rose-400' }}">
                                    {{ $qReason }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold text-rose-900 dark:text-rose-200 mb-1">
                            Motivo del rechazo <span class="text-rose-600">* (Obligatorio)</span>
                        </label>
                        <textarea wire:model.live="rejection_reason"
                                  rows="3"
                                  placeholder="Indícanos por favor el motivo por el que rechazas el presupuesto (ej. se nos escapa de presupuesto, ya tenemos DJ, queríamos menos horas...)"
                                  class="w-full rounded-xl border {{ $errors->has('rejection_reason') ? 'border-rose-600 ring-2 ring-rose-500/30' : 'border-slate-300 dark:border-slate-700' }} bg-white dark:bg-slate-900 px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-rose-500 focus:outline-none"></textarea>
                        @error('rejection_reason')
                            <p class="mt-1.5 text-xs font-bold text-rose-600 dark:text-rose-400 flex items-center gap-1">
                                <span>⚠️</span> {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="flex flex-col sm:flex-row items-center gap-2.5 pt-1">
                        <button type="button"
                                wire:click="rejectQuoteOnline"
                                wire:loading.attr="disabled"
                                class="w-full sm:flex-1 inline-flex items-center justify-center gap-2 bg-rose-600 hover:bg-rose-700 disabled:opacity-50 text-white font-black py-3 px-5 rounded-xl text-xs sm:text-sm shadow-md transition cursor-pointer">
                            <span wire:loading.remove wire:target="rejectQuoteOnline">❌ Confirmar Rechazo con este Motivo</span>
                            <span wire:loading wire:target="rejectQuoteOnline">⏳ Procesando...</span>
                        </button>
                        <button type="button"
                                wire:click="cancelAction"
                                class="w-full sm:w-auto px-4 py-3 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800 transition cursor-pointer">
                            Cancelar
                        </button>
                    </div>
                </div>
            @endif
        </div>

    @endif
</div>
