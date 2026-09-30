<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Propuesta de Servicios - {{ $event->name }}</title>
    <style>
        @page {
            margin: 15mm 15mm 15mm 15mm;
            size: a4 portrait;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 0;
            font-size: 9pt;
            line-height: 1.45;
            background-color: #ffffff;
        }
        
        /* UTILIDADES Y COLORES */
        .primary-color { color: #1e3a8a; }
        .accent-color { color: #d97706; }
        .text-muted { color: #64748b; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        
        /* CABECERA */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .header-logo {
            max-height: 50px;
            max-width: 180px;
        }
        .company-name {
            font-size: 15pt;
            font-weight: bold;
            color: #1e3a8a;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .company-subtitle {
            font-size: 8pt;
            color: #64748b;
            margin: 2px 0 0 0;
        }
        .doc-badge-title {
            font-size: 13pt;
            font-weight: bold;
            color: #2563eb;
            margin: 0 0 3px 0;
            text-transform: uppercase;
        }
        .doc-badge-meta {
            font-size: 8pt;
            color: #64748b;
        }

        /* FICHA RESUMEN DEL EVENTO */
        .summary-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 16px;
        }
        .summary-table {
            width: 100%;
            font-size: 8.5pt;
        }
        .summary-table td {
            padding: 3px 5px;
            vertical-align: top;
        }
        .summary-label {
            color: #64748b;
            font-weight: 600;
            width: 16%;
        }
        .summary-value {
            color: #0f172a;
            width: 34%;
        }

        /* SECCIONES Y CONTENIDOS */
        .section-header {
            font-size: 11pt;
            font-weight: bold;
            color: #1e3a8a;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin-top: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        p {
            margin: 0 0 8px 0;
            color: #334155;
            text-align: justify;
        }

        /* LISTAS CON VIÑETAS ESTILIZADAS */
        .features-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .features-list li {
            position: relative;
            padding-left: 18px;
            margin-bottom: 7px;
            color: #334155;
            font-size: 8.5pt;
        }
        .features-list li .bullet {
            position: absolute;
            left: 0;
            top: 0;
            color: #2563eb;
            font-weight: bold;
            font-size: 10pt;
        }

        /* TABLA DE PRECIOS */
        .pricing-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 14px;
        }
        .pricing-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            text-align: left;
        }
        .pricing-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 8.5pt;
            vertical-align: top;
        }
        .pricing-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .price-cell {
            text-align: right;
            font-weight: bold;
            color: #0f172a;
            white-space: nowrap;
        }
        .price-included {
            color: #16a34a;
            font-weight: bold;
        }
        .total-row td {
            background-color: #0f172a !important;
            color: #ffffff !important;
            font-weight: bold;
            font-size: 9.5pt;
            border-top: 2px solid #2563eb;
            padding: 9px 10px;
        }
        .total-row .price-cell {
            color: #38bdf8 !important;
            font-size: 11pt;
        }

        /* CONDICIONES DE PAGO DESTACADAS */
        .payment-box {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-left: 4px solid #2563eb;
            border-radius: 6px;
            padding: 10px 14px;
            margin-top: 12px;
            margin-bottom: 14px;
        }
        .payment-title {
            font-size: 9pt;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .payment-table {
            width: 100%;
            font-size: 8.5pt;
        }
        .payment-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        /* CAJA PORTAL MUSICAL */
        .portal-box {
            background-color: #faf5ff;
            border: 1px solid #e9d5ff;
            border-left: 4px solid #9333ea;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 14px;
        }
        .portal-title {
            font-size: 9pt;
            font-weight: bold;
            color: #7e22ce;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        /* PIE DE PÁGINA Y CONTACTO */
        .footer-card {
            border-top: 1px solid #cbd5e1;
            padding-top: 10px;
            margin-top: 12px;
            text-align: center;
            font-size: 8pt;
            color: #64748b;
        }
        .footer-highlight {
            font-weight: bold;
            color: #1e3a8a;
            font-size: 9pt;
            margin-bottom: 3px;
        }

        /* SALTO DE PÁGINA LIMPIO */
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>

    @php
        $companyName = \App\Models\Setting::get('company_name', 'Núñez & Son');
        $companySubtitle = \App\Models\Setting::get('company_subtitle', 'Servicios Musicales, Sonorización e Iluminación');
        $companyPhone = \App\Models\Setting::get('company_phone', '+34 622 634 790');
        $companyEmail = \App\Models\Setting::get('company_email', 'info@eventosmusicales.es');
        $companyIban = \App\Models\Setting::get('company_iban', '');
        $companyBizum = \App\Models\Setting::get('company_bizum', '');
        $companyLogo = \App\Models\Setting::get('company_logo');
        $logoPath = \App\Models\Setting::getLogoPathForPdf();

        $signalAmount = $quote->signal_amount;
        $remainingAmount = $quote->remaining_amount;
        $signalLabel = $quote->signal_label;
    @endphp

    <!-- ==================== PÁGINA 1: PRESENTACIÓN Y FICHA TÉCNICA ==================== -->
    <div class="page-container">
        <!-- CABECERA PRINCIPAL -->
        <table class="header-table">
            <tr>
                <td style="vertical-align: middle; width: 60%;">
                    @if($logoPath)
                        <img src="{{ $logoPath }}" class="header-logo" alt="Logo">
                    @else
                        <div class="company-name">{{ $companyName }}</div>
                        <div class="company-subtitle">{{ $companySubtitle }}</div>
                    @endif
                </td>
                <td class="text-right" style="vertical-align: middle; width: 40%;">
                    <div class="doc-badge-title">Propuesta de Servicios</div>
                    <div class="doc-badge-meta">
                        <strong>Nº Presupuesto:</strong> PRE-{{ str_pad($quote->id, 5, '0', STR_PAD_LEFT) }}<br>
                        <strong>Fecha Emisión:</strong> {{ $quote->created_at->format('d/m/Y') }}
                    </div>
                </td>
            </tr>
        </table>

        <!-- FICHA RESUMEN DEL EVENTO -->
        <div class="summary-card">
            <table class="summary-table">
                <tr>
                    <td class="summary-label">Evento:</td>
                    <td class="summary-value"><strong>{{ $event->name }}</strong></td>
                    <td class="summary-label">Fecha:</td>
                    <td class="summary-value"><strong>{{ $event->event_date ? \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') : 'Por determinar' }}</strong></td>
                </tr>
                <tr>
                    <td class="summary-label">Cliente:</td>
                    <td class="summary-value">{{ $client ? $client->name : 'Cliente' }}</td>
                    <td class="summary-label">Lugar / Finca:</td>
                    <td class="summary-value">{{ $event->location ?: 'Por determinar' }}</td>
                </tr>
                <tr>
                    <td class="summary-label">Teléfono:</td>
                    <td class="summary-value">{{ $client && $client->phone ? $client->phone : ($companyPhone ?: '—') }}</td>
                    <td class="summary-label">Horario Aprox.:</td>
                    <td class="summary-value">{{ $event->start_time ? $event->start_time . ' - ' . ($event->end_time ?: 'Cierre') : 'Según desarrollo' }}</td>
                </tr>
            </table>
        </div>

        <!-- INTRODUCCIÓN Y COMPROMISO -->
        <div class="section-header">Tu evento en las mejores manos</div>
        <p>
            Sabemos que cada celebración es irrepetible. En <strong>{{ $companyName }}</strong> nos especializamos en crear experiencias memorables donde la música, el sonido y la iluminación se adaptan con precisión al ambiente y a la energía de vuestros invitados. No se trata solo de reproducir canciones: coordinamos cada instante para que todo fluya con elegancia y diversión.
        </p>

        <!-- QUÉ INCLUYE NUESTRO SERVICIO -->
        <div class="section-header">Garantías y Alcance del Servicio</div>
        <ul class="features-list">
            <li>
                <span class="bullet">&#8226;</span>
                <strong>DJ Profesional & Animación Adaptada:</strong> Repertorio musical totalmente a medida, leyendo la pista en directo para garantizar el máximo disfrute de todas las edades.
            </li>
            <li>
                <span class="bullet">&#8226;</span>
                <strong>Equipamiento de Sonido de Alta Fidelidad:</strong> Sonorización profesional dimensionada específicamente para el aforo y características acústicas del recinto.
            </li>
            <li>
                <span class="bullet">&#8226;</span>
                <strong>Iluminación Dinámica y Decorativa:</strong> Efectos robóticos, cabezas móviles y ambientación luminosa para transformar la pista de baile.
            </li>
            <li>
                <span class="bullet">&#8226;</span>
                <strong>Cabina DJ Estéticamente Cuidada:</strong> Montaje limpio, cableado oculto y presencia impecable acorde al nivel de vuestra celebración.
            </li>
            <li>
                <span class="bullet">&#8226;</span>
                <strong>Planificación Previa Personalizada:</strong> Selección de canciones para momentos clave (entrada, cóctel, regalos, corte de tarta, baile nupcial) y lista de imprescindibles y prohibidas.
            </li>
            <li>
                <span class="bullet">&#8226;</span>
                <strong>Sincronización con el Restaurante / Finca:</strong> Coordinación directa con el equipo de metres y fotografía para sincronizar tiempos y sorpresas sin interrupciones.
            </li>
            <li>
                <span class="bullet">&#8226;</span>
                <strong>Montaje, Desmontaje y Transporte Incluidos:</strong> Llegada con antelación suficiente para pruebas de sonido y total tranquilidad.
            </li>
        </ul>

        <!-- PIE PÁGINA 1 -->
        <div class="footer-card" style="margin-top: 25px;">
            <div class="footer-highlight">{{ $companyName }} &bull; {{ $companySubtitle }}</div>
            <div>Contacto: {{ $companyPhone }} &bull; {{ $companyEmail }}</div>
        </div>
    </div>

    <!-- ==================== PÁGINA 2: DESGLOSE ECONÓMICO Y CONDICIONES ==================== -->
    <div class="page-break"></div>

    <div class="page-container">
        <!-- CABECERA RESUMIDA PÁGINA 2 -->
        <table class="header-table" style="margin-bottom: 10px; padding-bottom: 8px;">
            <tr>
                <td style="vertical-align: middle;">
                    <div style="font-size: 11pt; font-weight: bold; color: #1e3a8a;">{{ $companyName }}</div>
                    <div style="font-size: 7.5pt; color: #64748b;">Propuesta Económica para: <strong>{{ $event->name }}</strong></div>
                </td>
                <td class="text-right" style="vertical-align: middle;">
                    <div style="font-size: 8pt; color: #64748b;">
                        Presupuesto <strong>PRE-{{ str_pad($quote->id, 5, '0', STR_PAD_LEFT) }}</strong> &bull; {{ $quote->created_at->format('d/m/Y') }}
                    </div>
                </td>
            </tr>
        </table>

        <div class="section-header" style="margin-top: 0;">Desglose de Servicios y Precios</div>

        <!-- TABLA DE PRECIOS -->
        <table class="pricing-table">
            <thead>
                <tr>
                    <th style="width: 35%;">Servicio</th>
                    <th style="width: 48%;">Descripción / Cobertura</th>
                    <th style="width: 17%; text-align: right;">Importe</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quote->items as $item)
                    @php
                        $subtotal = $item->subtotal ?: ($item->price * $item->quantity);
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $item->service_name }}</strong>
                            @if($item->quantity > 1)
                                <div style="font-size: 7.5pt; color: #64748b;">Cantidad: {{ $item->quantity }}</div>
                            @endif
                        </td>
                        <td style="color: #475569;">
                            {{ $item->description ?: 'Servicio profesional acordado según especificaciones del evento.' }}
                        </td>
                        <td class="price-cell">
                            @if(str_contains(mb_strtolower($item->service_name ?: ''), 'consultar') || str_contains(mb_strtolower($item->description ?: ''), 'consultar'))
                                <span style="color: #b45309; font-weight: bold; font-size: 8pt;">A consultar</span>
                            @elseif($subtotal > 0)
                                {{ number_format($subtotal, 2, ',', '.') }} €
                            @elseif($subtotal < 0)
                                <span style="color: #15803d; font-weight: bold;">-{{ number_format(abs($subtotal), 2, ',', '.') }} €</span>
                            @else
                                <span class="price-included">Incluido</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                @if($quote->tax_type === 'excluded')
                    <tr style="background-color: #f8fafc;">
                        <td colspan="2" style="text-align: right; font-weight: bold; color: #475569; font-size: 8pt; border-top: 1px solid #cbd5e1;">
                            BASE IMPONIBLE:
                        </td>
                        <td class="price-cell" style="font-size: 8.5pt; color: #0f172a; border-top: 1px solid #cbd5e1;">
                            {{ number_format($quote->computed_subtotal, 2, ',', '.') }} €
                        </td>
                    </tr>
                    <tr style="background-color: #f8fafc;">
                        <td colspan="2" style="text-align: right; font-weight: bold; color: #475569; font-size: 8pt;">
                            IVA ({{ rtrim(rtrim(number_format($quote->tax_rate ?: 21, 2, ',', '.'), '0'), ',') }}%):
                        </td>
                        <td class="price-cell" style="font-size: 8.5pt; color: #0f172a;">
                            {{ number_format($quote->computed_tax, 2, ',', '.') }} €
                        </td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td colspan="2" style="text-align: right; text-transform: uppercase;">
                        TOTAL PRESUPUESTO:
                        @if($quote->tax_type === 'included')
                            <span style="font-size: 7pt; font-weight: normal; color: #94a3b8; display: block; text-transform: none;">(IVA {{ rtrim(rtrim(number_format($quote->tax_rate ?: 21, 2, ',', '.'), '0'), ',') }}% incluido)</span>
                        @elseif($quote->tax_type === 'none')
                            <span style="font-size: 7pt; font-weight: normal; color: #94a3b8; display: block; text-transform: none;">(Operación exenta / Sin recargo de IVA)</span>
                        @endif
                    </td>
                    <td class="price-cell">
                        {{ number_format($quote->amount, 2, ',', '.') }} €
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- CONDICIONES Y FORMA DE PAGO -->
        <div class="payment-box">
            <div class="payment-title">Condiciones y Modalidad de Pago</div>
            <table class="payment-table">
                <tr>
                    <td style="width: 4%; font-weight: bold; color: #2563eb;">&bull;</td>
                    <td style="width: 40%; font-weight: bold; color: #1e3a8a;">
                        Señal de Reserva ({{ $signalLabel }}):
                    </td>
                    <td style="width: 56%; font-weight: bold; color: #16a34a;">
                        {{ number_format($signalAmount, 2, ',', '.') }} € <span style="font-weight: normal; color: #64748b; font-size: 7.5pt;">(a la formalización y firma del contrato)</span>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: bold; color: #2563eb;">&bull;</td>
                    <td style="font-weight: bold; color: #1e3a8a;">
                        Saldo Restante:
                    </td>
                    <td style="font-weight: bold; color: #0f172a;">
                        {{ number_format($remainingAmount, 2, ',', '.') }} € <span style="font-weight: normal; color: #64748b; font-size: 7.5pt;">(el día de la celebración del evento)</span>
                    </td>
                </tr>
                @php
                    $pms = $quote->active_payment_methods;
                @endphp
                @if(in_array('transfer', $pms) && $companyIban)
                <tr>
                    <td style="font-weight: bold; color: #2563eb;">&bull;</td>
                    <td style="color: #475569;">Transferencia Bancaria:</td>
                    <td style="font-family: monospace; font-size: 8pt; color: #0f172a;"><strong>{{ $companyIban }}</strong></td>
                </tr>
                @endif
                @if(in_array('bizum', $pms) && $companyBizum)
                <tr>
                    <td style="font-weight: bold; color: #2563eb;">&bull;</td>
                    <td style="color: #475569;">Bizum Directo:</td>
                    <td style="font-weight: bold; color: #0f172a;">{{ $companyBizum }}</td>
                </tr>
                @endif
                @if(in_array('cash', $pms))
                <tr>
                    <td style="font-weight: bold; color: #2563eb;">&bull;</td>
                    <td style="color: #475569;">Pago en Efectivo:</td>
                    <td style="color: #0f172a; font-size: 8pt;">Abono en mano al personal técnico/DJ al inicio del servicio el día del evento.</td>
                </tr>
                @endif
                @if(in_array('card', $pms))
                <tr>
                    <td style="font-weight: bold; color: #2563eb;">&bull;</td>
                    <td style="color: #475569;">Pago con Tarjeta:</td>
                    <td style="color: #0f172a; font-size: 8pt;">Pasarela de cobro seguro online con tarjeta de crédito/débito.</td>
                </tr>
                @endif
                <tr>
                    <td style="font-weight: bold; color: #2563eb;">&bull;</td>
                    <td style="color: #475569;">Garantía de Cancelación:</td>
                    <td style="color: #64748b; font-size: 7.5pt;">Reserva íntegra garantizada para nueva fecha en caso de fuerza mayor demostrable.</td>
                </tr>
            </table>
        </div>

        <!-- PORTAL INTERACTIVO DE MÚSICA -->
        <div class="portal-box">
            <div class="portal-title">Portal Interactivo de Novios e Invitados</div>
            <p style="margin: 0; font-size: 8pt; color: #581c87;">
                Una vez confirmada la fecha, tendréis acceso exclusivo a la plataforma interactiva para configurar la música de cada momento (entrada, banquete, tarta, baile) y un <strong>Código QR personalizado</strong> para que vuestros invitados pidan y voten canciones en directo desde su móvil durante la fiesta.
            </p>
        </div>

        <!-- CONTACTO Y FIRMA -->
        <div class="footer-card" style="margin-top: 15px;">
            <div class="footer-highlight">¡Gracias por vuestra confianza en {{ $companyName }}!</div>
            <div style="font-size: 8.5pt; color: #334155; margin-top: 2px;">
                Estamos a vuestra entera disposición para cualquier duda o ajuste en la propuesta.
            </div>
            <div style="margin-top: 6px; font-size: 8pt; color: #64748b;">
                Teléfono: <strong>{{ $companyPhone }}</strong> &bull; Email: <strong>{{ $companyEmail }}</strong>
            </div>
        </div>
    </div>

</body>
</html>
