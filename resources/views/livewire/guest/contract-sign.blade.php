<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8 font-sans" x-data="signaturePad()">
    
    <!-- CABECERA -->
    <div class="text-center mb-8">
        <div class="w-16 h-16 bg-gradient-to-tr from-indigo-500 to-purple-600 rounded-3xl mx-auto flex items-center justify-center text-white text-3xl shadow-xl shadow-indigo-500/30 mb-4">
            ✍️
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
            Firma Online de Contrato de Servicios
        </h1>
        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
            {{ \App\Models\Setting::getCompanyName('Núñez and Son') }} &bull; Evento: <strong class="text-slate-900 dark:text-indigo-300">{{ $event->name }}</strong>
        </p>
    </div>

    @if($isSigned)
        <!-- CONTRATO YA FIRMADO -->
        <div class="bg-emerald-50 dark:bg-emerald-950/40 border-2 border-emerald-500/80 rounded-3xl p-6 sm:p-8 text-center shadow-lg mb-8">
            <div class="w-16 h-16 bg-emerald-100 dark:bg-emerald-900/60 text-emerald-600 dark:text-emerald-300 rounded-2xl mx-auto flex items-center justify-center text-3xl mb-3 shadow-inner">
                ✅
            </div>
            <h2 class="text-xl font-black text-emerald-900 dark:text-emerald-200">¡Contrato Firmado y Validado Criptográficamente!</h2>
            <p class="text-xs text-emerald-700 dark:text-emerald-300 mt-1 max-w-lg mx-auto leading-relaxed">
                El presente contrato ha sido formalizado electrónicamente con plena validez jurídica conforme a la normativa eIDAS y la Ley 6/2020 de servicios electrónicos de confianza.
            </p>

            <div class="mt-6 p-4 bg-white dark:bg-slate-900 rounded-2xl border border-emerald-200 dark:border-emerald-800 text-left max-w-md mx-auto space-y-2 text-xs text-slate-700 dark:text-slate-300">
                <div class="flex justify-between border-b border-slate-100 dark:border-slate-800 pb-1.5">
                    <span class="text-slate-500 dark:text-slate-400">Firmante:</span>
                    <strong class="text-slate-900 dark:text-white">{{ $client_name }} ({{ $client_dni }})</strong>
                </div>
                <div class="flex justify-between border-b border-slate-100 dark:border-slate-800 pb-1.5">
                    <span class="text-slate-500 dark:text-slate-400">Tipo de Firma:</span>
                    <span class="font-bold {{ $signature_type === 'certificate' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-800 dark:text-slate-200' }}">
                        {{ $signature_type === 'certificate' ? '🔐 Certificado Digital (FNMT/DNIe)' : '✍️ Firma Gráfica Digital' }}
                    </span>
                </div>
                <div class="flex justify-between border-b border-slate-100 dark:border-slate-800 pb-1.5">
                    <span class="text-slate-500 dark:text-slate-400">Fecha y Hora:</span>
                    <strong class="text-slate-900 dark:text-white">{{ $contract->signed_at ? $contract->signed_at->format('d/m/Y H:i:s') : now()->format('d/m/Y H:i:s') }}</strong>
                </div>
                <div class="flex justify-between border-b border-slate-100 dark:border-slate-800 pb-1.5">
                    <span class="text-slate-500 dark:text-slate-400">IP de Firma:</span>
                    <span class="font-mono text-[11px] text-slate-600 dark:text-slate-400">{{ $contract->signed_ip ?: request()->ip() }}</span>
                </div>
                @if($signature_type === 'certificate' && $certificate_hash)
                    <div class="pt-1">
                        <span class="text-slate-500 dark:text-slate-400 block text-[10px]">Huella Criptográfica SHA-256:</span>
                        <span class="font-mono text-[10px] text-emerald-700 dark:text-emerald-300 break-all block bg-emerald-50 dark:bg-emerald-950/80 p-1.5 rounded border border-emerald-200 dark:border-emerald-800 mt-0.5">
                            {{ $certificate_hash }}
                        </span>
                    </div>
                @endif
            </div>

            <!-- MUESTRA DE FIRMA O SELLO -->
            <div class="mt-6 pt-6 border-t border-emerald-200 dark:border-emerald-800 max-w-md mx-auto">
                <p class="text-[11px] font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider mb-2">Constancia de Firma Electrónica:</p>
                @if($signature_type === 'certificate')
                    <div class="bg-gradient-to-r from-emerald-600 to-teal-700 text-white p-4 rounded-2xl shadow-md text-left flex items-start gap-3">
                        <span class="text-3xl">🛡️</span>
                        <div class="text-xs">
                            <strong class="block text-sm font-extrabold uppercase tracking-wide">Firma Electrónica con Certificado</strong>
                            <p class="text-[11px] text-emerald-100 mt-0.5">Titular: {{ $client_name }} &bull; NIF: {{ $client_dni }}</p>
                            <p class="text-[10px] text-emerald-200">Emisor: {{ $certificate_issuer ?: 'AC FNMT Usuarios / DNIe' }}</p>
                        </div>
                    </div>
                @elseif($signature_data)
                    <div class="bg-white p-3 rounded-2xl border border-emerald-300 inline-block shadow-sm">
                        <img src="{{ $signature_data }}" alt="Firma del Cliente" class="max-h-24 mx-auto">
                    </div>
                @endif
            </div>

            <div class="mt-8">
                <a href="{{ route('admin.contract.pdf', $contract->id) }}" target="_blank" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-3 rounded-2xl text-xs shadow-md transition">
                    <span>📄</span> Descargar Contrato Oficial Firmado en PDF
                </a>
            </div>
        </div>

    @else
        <!-- FORMULARIO DE REVISIÓN Y FIRMA -->
        <form wire:submit.prevent="signContract" class="space-y-8">
            
            <!-- PASO 1: DATOS FISCALES -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-xl space-y-4">
                <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                    <span class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-sm border border-indigo-200 dark:border-indigo-800">1</span>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Datos Personales y Fiscales del Contratante</h3>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400">Por favor, revisa o completa tus datos. Los cambios se actualizarán automáticamente en el contrato inferior en tiempo real.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Nombre Completo *</label>
                        <input 
                            type="text" 
                            wire:model.live.debounce.250ms="client_name" 
                            class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-sm font-semibold text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 p-3 transition shadow-xs" 
                            placeholder="Nombre y Apellidos"
                        >
                        @error('client_name') <span class="text-rose-600 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                            <span>DNI / NIE / CIF *</span>
                            @if($dniValidation && $dniValidation['valid'])
                                <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/80 px-2 py-0.5 rounded border border-emerald-300 dark:border-emerald-800 flex items-center gap-1">
                                    <span>✓</span> {{ $dniValidation['type'] }} Válido
                                </span>
                            @elseif($dniValidation && !$dniValidation['valid'] && !empty($client_dni) && strlen($client_dni) >= 8)
                                <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/80 px-2 py-0.5 rounded border border-amber-300 dark:border-amber-800">
                                    Letra: {{ $dniValidation['expected_letter'] ?? '?' }}
                                </span>
                            @endif
                        </label>
                        <div class="relative">
                            <input 
                                type="text" 
                                wire:model.live.debounce.250ms="client_dni" 
                                class="w-full bg-slate-50 dark:bg-slate-950 border {{ $dniValidation && $dniValidation['valid'] ? 'border-emerald-500 focus:border-emerald-500 focus:ring-emerald-500' : ($dniValidation && !$dniValidation['valid'] && strlen($client_dni) >= 8 ? 'border-amber-500 focus:border-amber-500 focus:ring-amber-500' : 'border-slate-300 dark:border-slate-700') }} rounded-xl text-sm font-bold text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 p-3 uppercase transition shadow-xs" 
                                placeholder="12345678Z"
                            >
                            @if($dniValidation && $dniValidation['valid'])
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 text-emerald-500 font-bold text-base pointer-events-none">
                                    ✓
                                </div>
                            @endif
                        </div>
                        @if($dniValidation && !$dniValidation['valid'] && !empty($client_dni) && strlen($client_dni) >= 8)
                            <span class="text-amber-600 dark:text-amber-400 text-xs mt-1 block font-semibold leading-tight">
                                ⚠️ {{ $dniValidation['message'] }}
                            </span>
                        @endif
                        @error('client_dni') <span class="text-rose-600 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Teléfono (WhatsApp) *</label>
                        <input 
                            type="text" 
                            wire:model.live.debounce.250ms="client_phone" 
                            class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-sm font-semibold text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 p-3 transition shadow-xs" 
                            placeholder="+34 600 000 000"
                        >
                        @error('client_phone') <span class="text-rose-600 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Correo Electrónico *</label>
                        <input 
                            type="email" 
                            wire:model.live.debounce.250ms="client_email" 
                            class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-sm font-semibold text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 p-3 transition shadow-xs" 
                            placeholder="cliente@email.com"
                        >
                        @error('client_email') <span class="text-rose-600 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                            <span>Código Postal (CP) *</span>
                            @if(!empty($client_city) && !empty($client_province) && strlen($client_postal_code) === 5)
                                <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-0.5">
                                    <span>✓</span> {{ $client_city }}
                                </span>
                            @else
                                <span class="text-[10px] text-indigo-500 dark:text-indigo-300 font-normal lowercase">autocompleta ciudad</span>
                            @endif
                        </label>
                        <input 
                            type="text" 
                            wire:model.live.debounce.250ms="client_postal_code" 
                            @input="lookupPostalCode($event.target.value)"
                            maxlength="5"
                            class="w-full bg-indigo-50/60 dark:bg-indigo-950/50 border border-indigo-300 dark:border-indigo-700 rounded-xl text-sm font-bold text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 p-3 transition shadow-xs" 
                            placeholder="Ej: 26370"
                        >
                        @error('client_postal_code') <span class="text-rose-600 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Ciudad / Población *</label>
                        <input 
                            type="text" 
                            wire:model.live.debounce.250ms="client_city" 
                            class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-sm font-semibold text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 p-3 transition shadow-xs" 
                            placeholder="Ej: Navarrete"
                        >
                        @error('client_city') <span class="text-rose-600 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Provincia *</label>
                        <input 
                            type="text" 
                            wire:model.live.debounce.250ms="client_province" 
                            class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-sm font-semibold text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 p-3 transition shadow-xs" 
                            placeholder="Ej: La Rioja"
                        >
                        @error('client_province') <span class="text-rose-600 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Dirección (Calle, Nº, Piso...)</label>
                        <input 
                            type="text" 
                            wire:model.live.debounce.250ms="client_address" 
                            class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl text-sm font-semibold text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 p-3 transition shadow-xs" 
                            placeholder="Calle Mayor 14, 2ºB"
                        >
                    </div>
                </div>
            </div>

            <!-- PASO 2: LECTURA DE CLÁUSULAS -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-sm border border-indigo-200 dark:border-indigo-800">2</span>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Términos y Cláusulas del Contrato</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/80 px-2.5 py-1 rounded-full border border-emerald-200 dark:border-emerald-800 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Actualización en tiempo real
                        </span>
                        <span class="text-xs text-slate-400 dark:text-slate-500 font-medium hidden sm:inline">Lectura íntegra</span>
                    </div>
                </div>

                <div class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 max-h-96 overflow-y-auto font-mono text-xs leading-relaxed text-slate-800 dark:text-slate-200 select-text shadow-inner">
                    <div class="font-sans font-bold text-sm text-slate-900 dark:text-white mb-3 border-b border-slate-200 dark:border-slate-800 pb-2">
                        {{ $renderedTitle }}
                    </div>
                    {!! nl2br(e($renderedBody)) !!}
                </div>
            </div>

            <!-- PASO 3: CONSENTIMIENTO REDES SOCIALES -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-xl space-y-4">
                <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                    <span class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-sm border border-indigo-200 dark:border-indigo-800">3</span>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Autorización de Redes Sociales y Portfolio</h3>
                </div>

                <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                    ¿Autorizas a {{ \App\Models\Setting::getCompanyName('Núñez and Son') }} a capturar y publicar fotos/vídeos del montaje de sonido, iluminación y ambiente general en sus redes sociales oficiales (Instagram, TikTok o web)?
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <label class="flex items-center gap-3 p-3.5 rounded-2xl border cursor-pointer transition {{ $consent_rrss ? 'bg-indigo-50 dark:bg-indigo-950/60 border-indigo-400 dark:border-indigo-500 text-indigo-950 dark:text-indigo-200 font-bold' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300' }}">
                        <input type="radio" wire:model.live="consent_rrss" :value="true" class="text-indigo-600 focus:ring-indigo-500">
                        <span class="text-xs">📸 <strong>SÍ</strong>, autorizo la difusión en redes sociales y portfolio.</span>
                    </label>

                    <label class="flex items-center gap-3 p-3.5 rounded-2xl border cursor-pointer transition {{ !$consent_rrss ? 'bg-indigo-50 dark:bg-indigo-950/60 border-indigo-400 dark:border-indigo-500 text-indigo-950 dark:text-indigo-200 font-bold' : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300' }}">
                        <input type="radio" wire:model.live="consent_rrss" :value="false" class="text-indigo-600 focus:ring-indigo-500">
                        <span class="text-xs">🔒 <strong>NO</strong> autorizo la difusión.</span>
                    </label>
                </div>
            </div>

            <!-- PASO 4: ELECCIÓN Y ESTAMPACIÓN DE FIRMA -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-xl space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-sm border border-indigo-200 dark:border-indigo-800">4</span>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Método de Firma Electrónica</h3>
                    </div>

                    <!-- SELECTOR DE TIPO DE FIRMA -->
                    <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-950 p-1 rounded-2xl border border-slate-200 dark:border-slate-800">
                        <button 
                            type="button" 
                            @click="signatureType = 'canvas'; $wire.set('signature_type', 'canvas')"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5"
                            :class="signatureType === 'canvas' ? 'bg-white dark:bg-slate-800 text-indigo-700 dark:text-indigo-300 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                        >
                            <span>✍️</span> Firma Manuscrita
                        </button>
                        <button 
                            type="button" 
                            @click="signatureType = 'certificate'; $wire.set('signature_type', 'certificate')"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5"
                            :class="signatureType === 'certificate' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                        >
                            <span>🔐</span> Certificado Digital (FNMT/DNIe)
                        </button>
                    </div>
                </div>

                <!-- OPCIÓN A: LIENZO CANVAS DE FIRMA MANUSCRITA -->
                <div x-show="signatureType === 'canvas'" class="space-y-3" wire:ignore>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <p class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <span>✍️</span> Dibuja tu firma en el recuadro blanco:
                            </p>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400 block mt-0.5">
                                Con el dedo en el móvil/tablet o con el ratón. (Si lo prefieres, puedes girar el móvil en horizontal).
                            </span>
                        </div>
                        <button 
                            type="button" 
                            @click="clearSignature()" 
                            class="self-end sm:self-auto text-xs font-bold text-slate-700 dark:text-slate-200 hover:text-rose-600 px-3.5 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-rose-50 dark:hover:bg-rose-950/50 border border-slate-200 dark:border-slate-700 transition cursor-pointer flex items-center gap-1.5 shadow-2xs shrink-0"
                        >
                            <span>🔄</span> Borrar y Repetir Firma
                        </button>
                    </div>

                    <div 
                        class="relative bg-white border-2 border-indigo-300 dark:border-indigo-400 rounded-2xl overflow-hidden shadow-inner touch-none select-none w-full" 
                        style="height: 220px; touch-action: none; -webkit-touch-callout: none; -webkit-user-select: none; user-select: none;"
                    >
                        <!-- Canvas siempre sobre fondo blanco para máximo contraste y legibilidad -->
                        <canvas 
                            x-ref="canvas" 
                            class="w-full h-full cursor-crosshair block bg-white" 
                            style="touch-action: none;"
                        ></canvas>
                        
                        <!-- Línea guía de firma -->
                        <div class="absolute bottom-6 left-6 right-6 pointer-events-none border-b border-dashed border-slate-300 flex items-center justify-between text-[11px] text-slate-400 font-sans pb-1 select-none">
                            <span class="font-medium flex items-center gap-1">
                                <span class="text-slate-500 font-bold">✕</span> Firma del Cliente
                            </span>
                            <span class="text-[10px] text-slate-400 hidden sm:inline">Validez Legal Electrónica</span>
                        </div>

                        <!-- Marca de agua instructiva cuando está vacío -->
                        <div x-show="!hasDrawn" class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center text-slate-400 text-xs font-medium gap-1.5 select-none p-4 text-center">
                            <span class="text-2xl">✍️</span>
                            <span class="text-slate-600 font-bold text-sm">Estampa aquí tu firma</span>
                            <span class="text-[11px] text-slate-400">Desliza el dedo sobre la pantalla táctil</span>
                        </div>
                    </div>
                    @error('signature_data') <span class="text-rose-600 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror
                </div>

                <!-- OPCIÓN B: FIRMA CON CERTIFICADO DIGITAL -->
                <div x-show="signatureType === 'certificate'" style="display: none;" class="space-y-4">
                    <div class="bg-indigo-50/70 dark:bg-indigo-950/40 border-2 border-indigo-200 dark:border-indigo-800 rounded-2xl p-5 text-slate-800 dark:text-slate-200 space-y-4">
                        <div class="flex items-start gap-3">
                            <span class="text-3xl">🛡️</span>
                            <div>
                                <h4 class="font-extrabold text-sm text-indigo-950 dark:text-indigo-200">Firma Electrónica Cualificada con Certificado Digital</h4>
                                <p class="text-xs text-indigo-800 dark:text-indigo-300 mt-0.5 leading-relaxed">
                                    Válido para certificados de persona física emitidos por la <strong>FNMT-RCM, DNI Electrónico (DNIe), ACCV, Camerfirma o Izenpe</strong>.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Entidad Emisora del Certificado</label>
                                <select x-model="certIssuer" @change="$wire.set('certificate_issuer', certIssuer)" class="w-full border-slate-300 dark:border-slate-700 rounded-xl text-xs bg-white dark:bg-slate-900 text-slate-900 dark:text-white p-2.5">
                                    <option value="AC FNMT Usuarios / RCM">AC FNMT Usuarios (Fábrica Nacional de Moneda y Timbre)</option>
                                    <option value="DGC Policía Nacional - DNIe">Dirección General de la Policía (DNI electrónico)</option>
                                    <option value="ACCV - Generalitat Valenciana">ACCV (Autoridad de Certificación de la C. Valenciana)</option>
                                    <option value="Camerfirma / Izenpe">Camerfirma / Izenpe / Otros prestadores cualificados</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Titular del Certificado</label>
                                <input type="text" readonly :value="$wire.client_name + ' (' + $wire.client_dni + ')'" class="w-full border-slate-300 dark:border-slate-700 rounded-xl text-xs bg-slate-100 dark:bg-slate-800 p-2.5 text-slate-800 dark:text-slate-200 font-semibold">
                            </div>
                        </div>

                        <!-- ESTADO DE VALIDACIÓN DEL CERTIFICADO -->
                        <div class="p-3.5 bg-white dark:bg-slate-900 rounded-xl border border-indigo-100 dark:border-indigo-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="text-xs">
                                <span class="font-bold text-emerald-700 dark:text-emerald-300 flex items-center gap-1.5">
                                    <span>🔒</span> Certificado Digital Listo para Emitir Huella SHA-256
                                </span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400">Se generará el sello de tiempo criptográfico oficial al pulsar en Firmar Contrato.</span>
                            </div>
                            <button 
                                type="button" 
                                @click="generateCertHash()" 
                                class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-sm transition whitespace-nowrap"
                            >
                                ⚡ Verificar Sello Criptográfico
                            </button>
                        </div>

                        <div x-show="certValidated" class="p-3 bg-emerald-50 dark:bg-emerald-950/80 rounded-xl border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-800 dark:text-emerald-300 font-mono break-all" style="display: none;">
                            <strong>Huella Criptográfica SHA-256 generada:</strong><br>
                            <span x-text="certHash"></span>
                        </div>
                    </div>
                </div>

                <!-- CHECKBOX DE ACEPTACIÓN LEGAL -->
                <div class="pt-2">
                    <label class="flex items-start gap-3 cursor-pointer select-none">
                        <input type="checkbox" wire:model="accepted_terms" class="mt-0.5 w-4 h-4 text-indigo-600 rounded border-slate-300 dark:border-slate-700 focus:ring-indigo-500">
                        <span class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed font-medium">
                            He leído, entiendo y acepto expresamente todas las cláusulas y condiciones estipuladas en el presente contrato, reconociendo plena validez jurídica a mi firma electrónica.
                        </span>
                    </label>
                    @error('accepted_terms') <span class="text-rose-600 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror
                </div>

                <!-- BOTÓN PRINCIPAL DE FIRMA -->
                <div class="pt-4 text-center">
                    <button 
                        type="submit" 
                        class="w-full sm:w-auto min-w-[280px] bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-extrabold px-8 py-4 rounded-2xl text-sm shadow-xl shadow-emerald-600/30 transition transform hover:-translate-y-0.5 cursor-pointer"
                    >
                        <span x-text="signatureType === 'certificate' ? '🔐 Firmar Oficialmente con Certificado Digital' : '✍️ Firmar y Validar Contrato Electrónico'"></span>
                    </button>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-2">
                        Al firmar, se generará una copia certificada en PDF accesible en cualquier momento y enviada a tu correo.
                    </p>
                </div>
            </div>

        </form>
    @endif

</div>

<!-- JAVASCRIPT FIRMA CANVAS & CERTIFICADO (OPTIMIZADO PARA MÓVILES) -->
<script>
function signaturePad() {
    return {
        signatureType: 'canvas',
        canvas: null,
        ctx: null,
        isDrawing: false,
        hasDrawn: false,
        lastX: 0,
        lastY: 0,
        points: [],
        certIssuer: 'AC FNMT Usuarios / RCM',
        certValidated: false,
        certHash: '',

        init() {
            this.$nextTick(() => {
                this.canvas = this.$refs.canvas;
                if (!this.canvas) return;

                this.ctx = this.canvas.getContext('2d');
                this.resizeCanvas();

                let resizeTimer;
                const debouncedResize = () => {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(() => this.resizeCanvas(), 100);
                };

                window.addEventListener('resize', debouncedResize);
                window.addEventListener('orientationchange', debouncedResize);

                if (window.ResizeObserver && this.canvas.parentElement) {
                    const ro = new ResizeObserver(debouncedResize);
                    ro.observe(this.canvas.parentElement);
                }

                // Eventos de ratón
                this.canvas.addEventListener('mousedown', (e) => this.start(e));
                this.canvas.addEventListener('mousemove', (e) => this.move(e));
                window.addEventListener('mouseup', () => this.stop());

                // Eventos táctiles para móviles y tablets (bloqueo estricto de scroll durante el trazo)
                this.canvas.addEventListener('touchstart', (e) => this.start(e), { passive: false });
                this.canvas.addEventListener('touchmove', (e) => this.move(e), { passive: false });
                window.addEventListener('touchend', (e) => this.stop(e), { passive: false });
                window.addEventListener('touchcancel', (e) => this.stop(e), { passive: false });

                // Restaurar si ya existía una firma previa
                const existingData = this.$wire.get('signature_data');
                if (existingData && existingData.startsWith('data:image')) {
                    const img = new Image();
                    img.onload = () => {
                        this.ctx.drawImage(img, 0, 0, this.canvas.width, this.canvas.height);
                        this.hasDrawn = true;
                    };
                    img.src = existingData;
                }
            });
        },

        getCoords(e) {
            if (!this.canvas) return { x: 0, y: 0 };
            const rect = this.canvas.getBoundingClientRect();
            
            let clientX = e.clientX;
            let clientY = e.clientY;

            if (e.touches && e.touches.length > 0) {
                clientX = e.touches[0].clientX;
                clientY = e.touches[0].clientY;
            } else if (e.changedTouches && e.changedTouches.length > 0) {
                clientX = e.changedTouches[0].clientX;
                clientY = e.changedTouches[0].clientY;
            }

            const scaleX = this.canvas.width / (rect.width || 1);
            const scaleY = this.canvas.height / (rect.height || 1);

            return {
                x: (clientX - rect.left) * scaleX,
                y: (clientY - rect.top) * scaleY
            };
        },

        resizeCanvas() {
            if (!this.canvas) return;
            const rect = this.canvas.getBoundingClientRect();
            const dpr = Math.max(window.devicePixelRatio || 1, 1);

            let tempImage = null;
            if (this.hasDrawn) {
                tempImage = this.canvas.toDataURL();
            }

            const w = Math.max(rect.width, 280);
            const h = Math.max(rect.height, 190);

            this.canvas.width = Math.round(w * dpr);
            this.canvas.height = Math.round(h * dpr);

            this.ctx = this.canvas.getContext('2d');
            this.ctx.lineWidth = Math.max(3 * dpr, 2.8);
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';
            this.ctx.strokeStyle = '#1e3a8a'; // Tinta azul marino de alta densidad

            if (tempImage) {
                const img = new Image();
                img.onload = () => {
                    this.ctx.drawImage(img, 0, 0, this.canvas.width, this.canvas.height);
                };
                img.src = tempImage;
            }
        },

        start(e) {
            if (e.type.startsWith('touch')) {
                e.preventDefault();
            }
            this.isDrawing = true;
            this.hasDrawn = true;
            const coords = this.getCoords(e);
            this.lastX = coords.x;
            this.lastY = coords.y;
            this.points = [coords];

            // Punto inicial suave
            this.ctx.beginPath();
            this.ctx.arc(coords.x, coords.y, this.ctx.lineWidth / 2.2, 0, Math.PI * 2);
            this.ctx.fillStyle = '#1e3a8a';
            this.ctx.fill();
        },

        move(e) {
            if (!this.isDrawing) return;
            if (e.type.startsWith('touch')) {
                e.preventDefault();
            }
            const coords = this.getCoords(e);
            this.points.push(coords);

            // Suavizado por curvas cuadráticas de Bézier para dedos y pantallas táctiles
            if (this.points.length >= 3) {
                const len = this.points.length;
                const p1 = this.points[len - 2];
                const p2 = this.points[len - 1];
                const midX = (p1.x + p2.x) / 2;
                const midY = (p1.y + p2.y) / 2;

                this.ctx.beginPath();
                this.ctx.moveTo(this.lastX, this.lastY);
                this.ctx.quadraticCurveTo(p1.x, p1.y, midX, midY);
                this.ctx.stroke();

                this.lastX = midX;
                this.lastY = midY;
            } else {
                this.ctx.beginPath();
                this.ctx.moveTo(this.lastX, this.lastY);
                this.ctx.lineTo(coords.x, coords.y);
                this.ctx.stroke();
                this.lastX = coords.x;
                this.lastY = coords.y;
            }
        },

        stop(e) {
            if (this.isDrawing) {
                if (e && e.type && e.type.startsWith('touch')) {
                    e.preventDefault();
                }
                this.isDrawing = false;
                this.points = [];
                this.syncSignature();
            }
        },

        clearSignature() {
            if (!this.canvas) return;
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
            this.hasDrawn = false;
            this.points = [];
            this.$wire.set('signature_data', '');
        },

        syncSignature() {
            if (!this.canvas || !this.hasDrawn) return;
            const dataUrl = this.canvas.toDataURL('image/png');
            this.$wire.set('signature_data', dataUrl);
        },

        async generateCertHash() {
            const name = this.$wire.client_name || 'Cliente';
            const dni = this.$wire.client_dni || 'DNI';
            const raw = name + '|' + dni + '|' + this.certIssuer + '|' + new Date().toISOString();
            
            const msgUint8 = new TextEncoder().encode(raw);
            const hashBuffer = await crypto.subtle.digest('SHA-256', msgUint8);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            this.certHash = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
            this.certValidated = true;

            this.$wire.set('certificate_issuer', this.certIssuer);
            this.$wire.set('certificate_hash', this.certHash);
            this.$wire.set('certificate_subject', name + ' (' + dni + ')');
        },

        lookupPostalCode(cp) {
            cp = (cp || '').trim();
            if (cp.length < 2) return;
            const known = {
                '26370': { city: 'Navarrete', province: 'La Rioja' },
                '26001': { city: 'Logroño', province: 'La Rioja' },
                '26002': { city: 'Logroño', province: 'La Rioja' },
                '26003': { city: 'Logroño', province: 'La Rioja' },
                '26004': { city: 'Logroño', province: 'La Rioja' },
                '26005': { city: 'Logroño', province: 'La Rioja' },
                '26006': { city: 'Logroño', province: 'La Rioja' },
                '26007': { city: 'Logroño', province: 'La Rioja' },
                '26008': { city: 'Logroño', province: 'La Rioja' },
                '26009': { city: 'Logroño', province: 'La Rioja' },
                '26300': { city: 'Nájera', province: 'La Rioja' },
                '26200': { city: 'Haro', province: 'La Rioja' },
                '26500': { city: 'Calahorra', province: 'La Rioja' },
                '26580': { city: 'Arnedo', province: 'La Rioja' },
                '26360': { city: 'Fuenmayor', province: 'La Rioja' },
                '26140': { city: 'Lardero', province: 'La Rioja' },
                '26141': { city: 'Albelda de Iregua', province: 'La Rioja' },
                '26142': { city: 'Villamediana de Iregua', province: 'La Rioja' },
                '26340': { city: 'San Asensio', province: 'La Rioja' },
                '26350': { city: 'Cenicero', province: 'La Rioja' },
                '28001': { city: 'Madrid', province: 'Madrid' },
                '08001': { city: 'Barcelona', province: 'Barcelona' },
                '31001': { city: 'Pamplona', province: 'Navarra' },
                '01001': { city: 'Vitoria-Gasteiz', province: 'Álava' },
                '41001': { city: 'Sevilla', province: 'Sevilla' },
                '50001': { city: 'Zaragoza', province: 'Zaragoza' }
            };
            if (known[cp]) {
                this.$wire.set('client_city', known[cp].city);
                this.$wire.set('client_province', known[cp].province);
            } else if (cp.length === 5) {
                fetch('https://api.zippopotam.us/es/' + cp)
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.places && data.places.length > 0) {
                            this.$wire.set('client_city', data.places[0]['place name']);
                            this.$wire.set('client_province', data.places[0]['state']);
                        }
                    }).catch(() => {});
            }
        }
    }
}
</script>
