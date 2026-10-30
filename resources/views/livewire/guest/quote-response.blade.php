<div class="max-w-4xl mx-auto py-8 sm:py-12 px-4 sm:px-6 lg:px-8 font-sans antialiased">

    @if(session()->has('quote_response_success'))
        <div class="mb-6 p-4 rounded-xl bg-zinc-900 dark:bg-zinc-800 border border-amber-500/40 text-white text-sm flex items-center gap-3 shadow-sm">
            <svg class="w-5 h-5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span class="font-medium">{{ session('quote_response_success') }}</span>
        </div>
    @endif

    @if(session()->has('quote_response_rejected'))
        <div class="mb-6 p-4 rounded-xl bg-zinc-900 dark:bg-zinc-800 border border-zinc-700 text-zinc-200 text-sm flex items-center gap-3 shadow-sm">
            <svg class="w-5 h-5 text-zinc-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <span class="font-medium">{{ session('quote_response_rejected') }}</span>
        </div>
    @endif

    @if(!$quote)
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-10 text-center shadow-sm">
            <svg class="w-10 h-10 text-zinc-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Propuesta en elaboración</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1 max-w-md mx-auto">
                Estamos ultimando los detalles técnicos y económicos de vuestra propuesta. En cuanto esté disponible podréis consultarla y descargar el dossier desde este enlace.
            </p>
        </div>
    @else

        <!-- SELECTOR DE VERSIONES SI EXISTE MÁS DE UNA PROPUESTA -->
        @if($event->quotes->count() > 1)
            <div class="mb-5 flex items-center justify-between gap-2 flex-wrap bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl px-4 py-2.5">
                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Revisiones de la propuesta:</span>
                <div class="flex items-center gap-2 flex-wrap">
                    @foreach($event->quotes->sortByDesc('id') as $qItem)
                        <button type="button"
                            wire:click="selectQuote({{ $qItem->id }})"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition cursor-pointer {{ $quote->id === $qItem->id ? 'bg-zinc-900 dark:bg-amber-500 text-white dark:text-zinc-950 border-zinc-900 dark:border-amber-500' : 'bg-zinc-50 dark:bg-zinc-800/70 text-zinc-700 dark:text-zinc-300 border-zinc-200 dark:border-zinc-700 hover:border-zinc-400' }}">
                            Ref. #{{ str_pad($qItem->id, 4, '0', STR_PAD_LEFT) }} ({{ number_format((float)$qItem->amount, 0, ',', '.') }} €)
                            @if($qItem->status === 'accepted') &bull; Confirmada @endif
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- DOCUMENTO EJECUTIVO ÚNICO -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-lg overflow-hidden">

            <!-- CABECERA FORMAL DEL DOCUMENTO -->
            <div class="bg-zinc-900 dark:bg-zinc-950 text-white px-6 py-7 sm:px-10 sm:py-8 border-b border-amber-500/30">
                <div class="flex flex-col md:flex-row md:items-start justify-between gap-6">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-amber-400/90 mb-1.5">
                            {{ $brand['name'] ?? 'Núñez & Son' }} &bull; Propuesta Económica y de Producción
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">
                            {{ $event->name }}
                        </h1>

                        <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-1.5 text-xs text-zinc-300">
                            @if($event->event_date)
                                <span class="inline-flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-amber-400/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span>{{ \Carbon\Carbon::parse($event->event_date)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</span>
                                </span>
                            @endif
                            @if($event->location)
                                <span class="inline-flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-amber-400/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span>{{ $event->location }}</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row md:flex-col items-start sm:items-center md:items-end justify-between gap-3 pt-4 md:pt-0 border-t md:border-t-0 border-zinc-800 shrink-0">
                        <div class="md:text-right">
                            <div class="text-xs font-medium text-zinc-400">
                                Referencia <span class="text-white font-semibold">#PRE-{{ str_pad($quote->id, 5, '0', STR_PAD_LEFT) }}</span>
                            </div>
                            <div class="text-[11px] text-zinc-400 mt-0.5">
                                Emitida el {{ $quote->created_at_formatted }} &bull; Validez: <strong class="text-zinc-200">{{ $quote->expires_at_formatted }}</strong>
                            </div>
                        </div>

                        <a href="{{ route('pdf.quote', ['quote' => $quote->id, 'token' => $event->token]) }}"
                           target="_blank"
                           class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-400 text-zinc-950 font-semibold px-4 py-2.5 rounded-lg text-xs transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span>Descargar Dossier PDF</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- ESTADO DE LA PROPUESTA SI YA FUE RESPONDIDA -->
            @if($quote->status === 'accepted' && $actionMode === 'view')
                <div class="bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-800 px-6 py-5 sm:px-10">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-start gap-3.5">
                            <div class="w-9 h-9 rounded-lg bg-zinc-900 dark:bg-amber-500 text-white dark:text-zinc-950 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h2 class="text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wider">
                                        Propuesta Aceptada y Confirmada
                                    </h2>
                                    @if($quote->responded_at)
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                            ({{ $quote->responded_at->format('d/m/Y H:i') }})
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-zinc-600 dark:text-zinc-300 mt-1">
                                    Esta propuesta ha sido confirmada. El evento figura reservado en firme en nuestra planificación.
                                </p>
                                @if($quote->client_notes)
                                    <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-2 italic">
                                        Observaciones registradas: "{{ $quote->client_notes }}"
                                    </p>
                                @endif
                            </div>
                        </div>
                        <button type="button" wire:click="openRejectBox" class="text-xs text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200 underline shrink-0 cursor-pointer self-start sm:self-center">
                            Modificar estado de la propuesta
                        </button>
                    </div>

                    @if($quote->signal_amount > 0 && ($companyIban || $companyBizum))
                        <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700/80 grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                            <div>
                                <span class="text-zinc-500 dark:text-zinc-400 block text-[11px] uppercase tracking-wider font-semibold">Señal de Reserva</span>
                                <strong class="text-zinc-900 dark:text-white text-sm">{{ number_format($quote->signal_amount, 2, ',', '.') }} €</strong>
                                <span class="text-zinc-500 dark:text-zinc-400 text-[11px] block">Concepto: {{ $event->name }}</span>
                            </div>
                            @if(in_array('transfer', $quote->active_payment_methods) && $companyIban)
                                <div>
                                    <span class="text-zinc-500 dark:text-zinc-400 block text-[11px] uppercase tracking-wider font-semibold">Transferencia Bancaria (IBAN)</span>
                                    <span class="font-mono font-semibold text-zinc-900 dark:text-white select-all">{{ $companyIban }}</span>
                                </div>
                            @endif
                            @if(in_array('bizum', $quote->active_payment_methods) && $companyBizum)
                                <div>
                                    <span class="text-zinc-500 dark:text-zinc-400 block text-[11px] uppercase tracking-wider font-semibold">Bizum Autorizado</span>
                                    <span class="font-mono font-semibold text-zinc-900 dark:text-white select-all">{{ $companyBizum }}</span>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @elseif($quote->status === 'rejected' && $actionMode === 'view')
                <div class="bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-800 px-6 py-5 sm:px-10">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="inline-block w-2 h-2 rounded-full bg-zinc-400"></span>
                                <h2 class="text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wider">
                                    Propuesta Desestimada
                                </h2>
                                @if($quote->responded_at)
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                        ({{ $quote->responded_at->format('d/m/Y H:i') }})
                                    </span>
                                @endif
                            </div>
                            @if($quote->rejection_reason)
                                <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">
                                    Motivo registrado: {{ $quote->rejection_reason }}
                                </p>
                            @endif
                        </div>
                        <button type="button" wire:click="openAcceptBox" class="inline-flex items-center gap-2 bg-zinc-900 dark:bg-amber-500 hover:bg-zinc-800 dark:hover:bg-amber-400 text-white dark:text-zinc-950 font-semibold px-4 py-2 rounded-lg text-xs transition cursor-pointer shrink-0">
                            Reactivar y Aceptar Propuesta
                        </button>
                    </div>
                </div>
            @endif

            <!-- DESGLOSE DE SERVICIOS EN FORMATO TABLA EJECUTIVA -->
            <div class="px-6 py-6 sm:px-10 sm:py-8">
                <div class="flex items-center justify-between pb-3 border-b-2 border-zinc-900 dark:border-zinc-700">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-zinc-900 dark:text-zinc-200">
                        Detalle de Servicios y Producción Técnica
                    </h3>
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Importe ({{ $quote->tax_label }})
                    </span>
                </div>

                @php
                    $isMagicQuoteBrand = ($event->brand_clean ?? '') === 'mago_leugim';
                    $hasCeremonyOrCocktailOnline = false;
                    $hasQrPhotosItemOnline = false;
                @endphp
                <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach($quote->items as $item)
                        @php
                            $lineTotal = (float)$item->price * (int)($item->quantity ?: 1);
                            $isDiscount = $lineTotal < 0;
                            $onlineItemTitle = (string)($item->service_name ?: $item->concept ?: '');
                            $onlineLower = mb_strtolower($onlineItemTitle);
                            $isQrGiftLine = $lineTotal == 0 && (str_contains($onlineLower, 'fotos qr') || str_contains($onlineLower, 'incluido de regalo'));
                            if (str_contains($onlineLower, 'fotos qr') || str_contains($onlineLower, 'álbum colaborativo') || str_contains($onlineLower, 'album colaborativo')) {
                                $hasQrPhotosItemOnline = true;
                            } elseif (
                                !$isDiscount &&
                                !str_contains($onlineLower, 'descuento') &&
                                !str_contains($onlineLower, 'fotografía') &&
                                !str_contains($onlineLower, 'fotografia') &&
                                (
                                    str_contains($onlineLower, 'ceremonia') ||
                                    str_contains($onlineLower, 'cóctel') ||
                                    str_contains($onlineLower, 'coctel') ||
                                    str_contains($onlineLower, 'recepción') ||
                                    str_contains($onlineLower, 'recepcion') ||
                                    str_contains($onlineLower, 'aperitivo')
                                )
                            ) {
                                $hasCeremonyOrCocktailOnline = true;
                            }
                        @endphp
                        <div class="py-4 flex flex-col sm:flex-row sm:items-start justify-between gap-3 {{ $isQrGiftLine ? 'bg-emerald-50/60 dark:bg-emerald-950/20 -mx-4 px-4 rounded-xl' : '' }}">
                            <div class="space-y-1 pr-4">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-semibold text-sm {{ $isDiscount ? 'text-zinc-700 dark:text-amber-400' : ($isQrGiftLine ? 'text-emerald-800 dark:text-emerald-300' : 'text-zinc-900 dark:text-white') }}">
                                        {{ $item->service_name ?: $item->concept }}
                                    </span>
                                    @if(($item->quantity ?? 1) > 1)
                                        <span class="text-[11px] font-medium px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">
                                            {{ $item->quantity }} uds.
                                        </span>
                                    @endif
                                </div>
                                @if($item->description)
                                    <p class="text-xs {{ $isQrGiftLine ? 'text-emerald-700 dark:text-emerald-400/90' : 'text-zinc-500 dark:text-zinc-400' }} leading-relaxed max-w-2xl">
                                        {{ $item->description }}
                                    </p>
                                @endif
                            </div>
                            <div class="sm:text-right shrink-0 pt-0.5">
                                @if($isDiscount)
                                    <span class="text-sm font-semibold text-zinc-700 dark:text-amber-400">
                                        {{ number_format($lineTotal, 2, ',', '.') }} €
                                    </span>
                                @elseif($isQrGiftLine)
                                    <span class="text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">
                                        Incluido (0 €)
                                    </span>
                                @elseif($lineTotal == 0)
                                    <span class="text-xs font-semibold uppercase tracking-wider px-2.5 py-1 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">
                                        {{ str_contains(mb_strtolower($item->service_name ?? ''), 'consultar') ? 'A consultar' : 'Incluido' }}
                                    </span>
                                @else
                                    <span class="text-sm sm:text-base font-semibold text-zinc-900 dark:text-white tabular-nums">
                                        {{ number_format($lineTotal, 2, ',', '.') }} €
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    @if(!$isMagicQuoteBrand && $hasCeremonyOrCocktailOnline && !$hasQrPhotosItemOnline)
                        <div class="py-4 flex flex-col sm:flex-row sm:items-start justify-between gap-3 bg-emerald-50/60 dark:bg-emerald-950/20 -mx-4 px-4 rounded-xl">
                            <div class="space-y-1 pr-4">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-semibold text-sm text-emerald-800 dark:text-emerald-300">
                                        ★ Álbum de Fotos QR para Invitados (Incluido de regalo con Ceremonia / Cóctel)
                                    </span>
                                </div>
                                <p class="text-xs text-emerald-700 dark:text-emerald-400/90 leading-relaxed max-w-2xl">
                                    Código QR personalizado para que todos los invitados suban y descarguen en calidad original las fotos que hagan con su móvil durante el evento, sin instalar ninguna app.
                                </p>
                            </div>
                            <div class="sm:text-right shrink-0 pt-0.5">
                                <span class="text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">
                                    Incluido (0 €)
                                </span>
                            </div>
                        </div>
                    @endif
                </div>

                @if(!empty($quote->admin_notes))
                    <div class="mt-6 p-4 sm:p-5 rounded-xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-800">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1.5">
                            Observaciones y Condiciones Particulares
                        </div>
                        <p class="text-xs text-zinc-700 dark:text-zinc-300 whitespace-pre-line leading-relaxed">{{ $quote->admin_notes }}</p>
                    </div>
                @endif

                <!-- RESUMEN ECONÓMICO Y CONDICIONES DE RESERVA -->
                <div class="mt-6 pt-6 border-t-2 border-zinc-900 dark:border-zinc-700">
                    <div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-2">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-widest text-zinc-900 dark:text-zinc-200 block">
                                Importe Total del Presupuesto
                            </span>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $quote->tax_label }} &bull; Desplazamiento, montaje y desmontaje incluidos
                            </span>
                        </div>
                        <div class="text-2xl sm:text-3xl font-bold text-zinc-900 dark:text-white tabular-nums">
                            {{ number_format((float)$quote->amount, 2, ',', '.') }} €
                        </div>
                    </div>

                    <div class="mt-5 pt-4 border-t border-zinc-200 dark:border-zinc-800 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div class="flex items-center justify-between sm:justify-start sm:gap-3">
                            <span class="text-zinc-500 dark:text-zinc-400">Señal de reserva para bloqueo de fecha:</span>
                            <strong class="text-zinc-900 dark:text-white font-semibold">
                                {{ $quote->signal_amount > 0 ? number_format($quote->signal_amount, 2, ',', '.') . ' € (' . $quote->signal_label . ')' : 'Sin señal previa (0,00 €)' }}
                            </strong>
                        </div>
                        <div class="flex items-center justify-between sm:justify-end sm:gap-3">
                            <span class="text-zinc-500 dark:text-zinc-400">Importe restante a liquidar en el evento:</span>
                            <strong class="text-zinc-900 dark:text-white font-semibold">
                                {{ number_format($quote->remaining_amount, 2, ',', '.') }} €
                            </strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PIE DE FORMALIZACIÓN / RESPUESTA ONLINE -->
            @if($quote->status !== 'accepted' || $actionMode !== 'view')
                <div class="bg-zinc-50 dark:bg-zinc-950/70 border-t border-zinc-200 dark:border-zinc-800 px-6 py-6 sm:px-10 sm:py-8">
                    @if($actionMode === 'view')
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
                            <div>
                                <h3 class="text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wider">
                                    Formalización de la Propuesta
                                </h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 max-w-xl">
                                    Podéis confirmar directamente esta propuesta para bloquear vuestra fecha o indicarnos si deseáis realizar alguna modificación.
                                </p>
                            </div>

                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
                                <button type="button"
                                        wire:click="openAcceptBox"
                                        class="inline-flex items-center justify-center gap-2 bg-zinc-900 hover:bg-zinc-800 dark:bg-amber-500 dark:hover:bg-amber-400 text-white dark:text-zinc-950 font-semibold py-3 px-6 rounded-xl text-xs sm:text-sm transition cursor-pointer shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span>Aceptar Propuesta</span>
                                </button>

                                <button type="button"
                                        wire:click="openRejectBox"
                                        class="inline-flex items-center justify-center gap-2 bg-white hover:bg-zinc-100 dark:bg-zinc-900 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border border-zinc-300 dark:border-zinc-700 font-medium py-3 px-5 rounded-xl text-xs sm:text-sm transition cursor-pointer">
                                    <span>Declinar o solicitar cambios</span>
                                </button>
                            </div>
                        </div>
                    @endif

                    <!-- FORMULARIO DE CONFIRMACIÓN DE ACEPTACIÓN -->
                    @if($actionMode === 'accept_box')
                        <div class="max-w-xl mx-auto space-y-4">
                            <div class="border-b border-zinc-200 dark:border-zinc-800 pb-3">
                                <h4 class="text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wider">
                                    Confirmar Aceptación &bull; Propuesta #PRE-{{ str_pad($quote->id, 5, '0', STR_PAD_LEFT) }}
                                </h4>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    Importe total: <strong class="text-zinc-900 dark:text-white">{{ number_format((float)$quote->amount, 2, ',', '.') }} €</strong> ({{ $quote->tax_label }})
                                </p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                                    Observaciones o indicaciones adicionales (opcional):
                                </label>
                                <textarea wire:model="client_notes"
                                          rows="2"
                                          placeholder="Podéis indicar cualquier comentario sobre horarios o detalles del evento..."
                                          class="w-full rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-3.5 py-2.5 text-xs text-zinc-900 dark:text-white focus:ring-2 focus:ring-zinc-900 dark:focus:ring-amber-500 focus:outline-none"></textarea>
                            </div>

                            <div class="flex flex-col sm:flex-row items-center gap-3 pt-1">
                                <button type="button"
                                        wire:click="acceptQuoteOnline"
                                        wire:loading.attr="disabled"
                                        class="w-full sm:flex-1 inline-flex items-center justify-center gap-2 bg-zinc-900 hover:bg-zinc-800 dark:bg-amber-500 dark:hover:bg-amber-400 text-white dark:text-zinc-950 font-semibold py-3 px-5 rounded-xl text-xs sm:text-sm transition cursor-pointer">
                                    <span wire:loading.remove wire:target="acceptQuoteOnline">Confirmar Aceptación</span>
                                    <span wire:loading wire:target="acceptQuoteOnline">Procesando confirmación...</span>
                                </button>
                                <button type="button"
                                        wire:click="cancelAction"
                                        class="w-full sm:w-auto px-5 py-3 rounded-xl text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200/70 dark:hover:bg-zinc-800 transition cursor-pointer">
                                    Volver
                                </button>
                            </div>
                        </div>
                    @endif

                    <!-- FORMULARIO PARA DECLINAR O SOLICITAR CAMBIOS (ESTÉTICA NEUTRA, SIN ROJO) -->
                    @if($actionMode === 'reject_box')
                        <div class="max-w-xl mx-auto space-y-4">
                            <div class="border-b border-zinc-200 dark:border-zinc-800 pb-3">
                                <h4 class="text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wider">
                                    Declinar Propuesta o Solicitar Modificación
                                </h4>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    Por favor, indicadnos el motivo para actualizar el estado de la reserva o preparar una revisión ajustada a vuestras necesidades.
                                </p>
                            </div>

                            <div>
                                <span class="block text-[11px] font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-2">
                                    Opciones frecuentes:
                                </span>
                                <div class="flex flex-wrap gap-2">
                                    @php
                                        $quickReasons = [
                                            'Queremos adaptar o quitar algún servicio del presupuesto',
                                            'Se nos va de presupuesto',
                                            'Ya hemos contratado otra opción',
                                            'Cambio de fecha o planes del evento',
                                        ];
                                    @endphp
                                    @foreach($quickReasons as $qReason)
                                        <button type="button"
                                                wire:click="pickRejectionTag('{{ $qReason }}')"
                                                class="text-xs font-medium px-3 py-1.5 rounded-lg border transition cursor-pointer {{ $rejection_reason === $qReason ? 'bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 border-zinc-900 dark:border-zinc-100' : 'bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 border-zinc-300 dark:border-zinc-700 hover:border-zinc-500' }}">
                                            {{ $qReason }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                                    Motivo o comentarios <span class="text-zinc-400 font-normal">(requerido)</span>
                                </label>
                                <textarea wire:model.live="rejection_reason"
                                          rows="3"
                                          placeholder="Indicadnos el motivo o los ajustes que necesitaríais en la propuesta..."
                                          class="w-full rounded-xl border {{ $errors->has('rejection_reason') ? 'border-amber-500 ring-1 ring-amber-500/30' : 'border-zinc-300 dark:border-zinc-700' }} bg-white dark:bg-zinc-900 px-3.5 py-2.5 text-xs text-zinc-900 dark:text-white focus:ring-2 focus:ring-zinc-900 dark:focus:ring-amber-500 focus:outline-none"></textarea>
                                @error('rejection_reason')
                                    <p class="mt-1.5 text-xs font-medium text-amber-600 dark:text-amber-400">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div class="flex flex-col sm:flex-row items-center gap-3 pt-1">
                                <button type="button"
                                        wire:click="rejectQuoteOnline"
                                        wire:loading.attr="disabled"
                                        class="w-full sm:flex-1 inline-flex items-center justify-center gap-2 bg-zinc-900 hover:bg-zinc-800 dark:bg-zinc-100 dark:hover:bg-white text-white dark:text-zinc-950 font-semibold py-3 px-5 rounded-xl text-xs sm:text-sm transition cursor-pointer">
                                    <span wire:loading.remove wire:target="rejectQuoteOnline">Enviar Respuesta</span>
                                    <span wire:loading wire:target="rejectQuoteOnline">Procesando...</span>
                                </button>
                                <button type="button"
                                        wire:click="cancelAction"
                                        class="w-full sm:w-auto px-5 py-3 rounded-xl text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200/70 dark:hover:bg-zinc-800 transition cursor-pointer">
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

        </div>

    @endif
</div>
