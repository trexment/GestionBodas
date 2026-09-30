<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Propuesta para tu evento - {{ $event->name }}</title>
    <style>
        @page {
            margin: 0;
            size: a4 portrait;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            background-color: #ffffff;
            font-size: 9pt;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }

        /* ESTRUCTURA DE PÁGINAS A4 (210mm x 297mm) */
        .page {
            width: 210mm;
            height: 297mm;
            max-height: 297mm;
            page-break-after: always;
            position: relative;
            box-sizing: border-box;
            overflow: hidden;
        }
        .page:last-child {
            page-break-after: avoid;
        }

        /* ==================== PÁGINA 1: PORTADA ==================== */
        .cover-page {
            background-color: #0b1329;
            background: linear-gradient(180deg, #0b1329 0%, #111c34 50%, #060b18 100%);
            color: #ffffff;
            padding: 50px 45px;
            position: relative;
        }
        .cover-logo-wrapper {
            text-align: center;
            margin-top: 25px;
            margin-bottom: 200px;
        }
        .cover-logo-img {
            max-height: 130px;
            max-width: 280px;
            margin: 0 auto;
        }
        .cover-badge-logo {
            display: inline-block;
            border: 2px solid #eab308;
            border-radius: 50%;
            width: 130px;
            height: 130px;
            padding: 20px 10px;
            text-align: center;
            box-shadow: 0 0 25px rgba(234, 179, 8, 0.25);
        }
        .cover-badge-title {
            font-size: 15pt;
            font-weight: bold;
            letter-spacing: 2px;
            color: #ffffff;
            text-transform: uppercase;
        }
        .cover-badge-sub {
            font-size: 7.5pt;
            letter-spacing: 3px;
            color: #eab308;
            text-transform: uppercase;
            margin-top: 5px;
        }

        .cover-bottom-card {
            position: absolute;
            bottom: 60px;
            left: 45px;
            right: 45px;
            background-color: rgba(17, 28, 52, 0.88);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            padding: 30px 35px;
        }
        .cover-subtitle-top {
            font-size: 9pt;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #93c5fd;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .cover-main-title {
            font-size: 26pt;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.15;
            margin-bottom: 12px;
        }
        .cover-gold-line {
            width: 60px;
            height: 3.5px;
            background-color: #eab308;
            margin-bottom: 18px;
            border-radius: 2px;
        }
        .cover-meta-item {
            font-size: 11pt;
            color: #e2e8f0;
            margin-bottom: 6px;
            font-weight: 500;
        }
        .cover-meta-item strong {
            color: #ffffff;
            font-weight: 700;
        }

        /* ==================== PÁGINA 2 & 3: CABECERAS Y PIES ==================== */
        .page-header-banner {
            background-color: #0b1329;
            color: #ffffff;
            padding: 24px 35px;
        }
        .page-header-sub {
            font-size: 8pt;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #93c5fd;
            font-weight: 700;
            margin-bottom: 3px;
        }
        .page-header-title {
            font-size: 16pt;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.3px;
        }

        .page-content {
            padding: 22px 35px 70px 35px;
        }

        .page-footer-banner {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: #0b1329;
            color: #94a3b8;
            padding: 11px 35px;
            font-size: 8pt;
            text-align: center;
        }
        .page-footer-banner strong {
            color: #ffffff;
        }

        /* INTRO TEXT */
        .intro-lead {
            font-size: 9.5pt;
            color: #334155;
            line-height: 1.5;
            margin-bottom: 16px;
            text-align: justify;
        }

        /* MOSAICO DE FOTOS / EQUIPO */
        .photo-mosaic-table {
            width: 100%;
            margin-bottom: 16px;
            border-collapse: separate;
            border-spacing: 8px;
        }
        .photo-card {
            background-color: #0f172a;
            border-radius: 8px;
            overflow: hidden;
            text-align: center;
            color: #ffffff;
            vertical-align: middle;
        }
        .photo-card-inner {
            padding: 20px 15px;
        }
        .photo-card-icon {
            font-size: 24pt;
            margin-bottom: 6px;
            display: block;
        }
        .photo-card-title {
            font-size: 9.5pt;
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 3px;
        }
        .photo-card-desc {
            font-size: 7.5pt;
            color: #cbd5e1;
            line-height: 1.3;
        }

        /* TARJETA CÓMO TRABAJAMOS */
        .work-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #d97706;
            border-radius: 8px;
            padding: 14px 18px;
            margin-top: 10px;
        }
        .work-box-title {
            font-size: 10pt;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 8px;
        }
        .work-box-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .work-box-list li {
            font-size: 8.5pt;
            color: #334155;
            margin-bottom: 5px;
            line-height: 1.4;
            position: relative;
            padding-left: 14px;
        }
        .work-box-list li .bullet {
            position: absolute;
            left: 0;
            top: 0;
            color: #d97706;
            font-weight: bold;
        }

        /* ==================== PÁGINA 3: OPCIONES Y PACKS ==================== */
        .packs-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
            margin-bottom: 12px;
        }
        .pack-col {
            width: 33.33%;
            vertical-align: top;
        }
        .pack-card {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 16px 12px;
            text-align: center;
            height: 100%;
            position: relative;
        }
        .pack-card.highlighted {
            background-color: #0b1329;
            border: 2px solid #eab308;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(11, 19, 41, 0.2);
        }
        .pack-badge {
            background-color: #eab308;
            color: #0f172a;
            font-size: 7pt;
            font-weight: 900;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 8px;
        }
        .pack-title {
            font-size: 13pt;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 2px;
        }
        .pack-card.highlighted .pack-title {
            color: #ffffff;
        }
        .pack-schedule {
            font-size: 8pt;
            color: #64748b;
            margin-bottom: 10px;
        }
        .pack-card.highlighted .pack-schedule {
            color: #94a3b8;
        }
        .pack-price {
            font-size: 20pt;
            font-weight: 900;
            color: #0f172a;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }
        .pack-card.highlighted .pack-price {
            color: #eab308;
        }
        .pack-features {
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            text-align: center;
            list-style: none;
            margin: 0;
        }
        .pack-card.highlighted .pack-features {
            border-top: 1px solid rgba(255, 255, 255, 0.15);
        }
        .pack-features li {
            font-size: 8pt;
            color: #475569;
            padding: 4px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .pack-card.highlighted .pack-features li {
            color: #e2e8f0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .pack-features li:last-child {
            border-bottom: none;
        }

        /* TARJETAS INFORMATIVAS INFERIORES */
        .info-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #d97706;
            border-radius: 8px;
            padding: 10px 15px;
            margin-bottom: 9px;
        }
        .info-card-title {
            font-size: 9pt;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 3px;
        }
        .info-card-body {
            font-size: 8pt;
            color: #475569;
            line-height: 1.35;
        }

        .custom-selection-card {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-left: 4px solid #16a34a;
            border-radius: 8px;
            padding: 10px 15px;
            margin-bottom: 9px;
        }
        .custom-selection-title {
            font-size: 9pt;
            font-weight: 800;
            color: #15803d;
            margin-bottom: 3px;
        }
        .custom-selection-body {
            font-size: 8pt;
            color: #166534;
            line-height: 1.35;
        }

        .tax-note {
            font-size: 7.5pt;
            color: #94a3b8;
            margin-top: 4px;
        }
    </style>
</head>
<body>

    @php
        $companyName = \App\Models\Setting::getCompanyName('Núñez and Son');
        $companyPhone = \App\Models\Setting::get('company_phone', '+34 622 62 47 90');
        $companyPhone2 = \App\Models\Setting::get('company_phone_2', '+34 674 37 89 93');
        $companyWebsite = \App\Models\Setting::get('company_website', 'landing-bodas.es/nunez-and-son');
        $logoPath = \App\Models\Setting::getLogoPathForPdf();
        if (!$logoPath && file_exists(storage_path('app/public/logos/BqwZKGcLFmX0kuiPh9EWKg3BKmHnRB3puOsxm9TI.png'))) {
            $logoPath = storage_path('app/public/logos/BqwZKGcLFmX0kuiPh9EWKg3BKmHnRB3puOsxm9TI.png');
        }

        // Pack settings
        $packBasicPrice = (float)\App\Models\Setting::get('pack_basic_price', 400);
        $packBasicHours = (int)\App\Models\Setting::get('pack_basic_hours', 4);
        
        $packMediumPrice = (float)\App\Models\Setting::get('pack_medium_price', 700);
        $packMediumHours = (int)\App\Models\Setting::get('pack_medium_hours', 5);

        $packPremiumPrice = (float)\App\Models\Setting::get('pack_premium_price', 1000);
        $packPremiumHours = (int)\App\Models\Setting::get('pack_premium_hours', 6);

        $extraHourPrice = (float)\App\Models\Setting::get('price_extra_hours', 120);

        // Formatted Date
        $carbonDate = $event->event_date ? \Carbon\Carbon::parse($event->event_date)->locale('es') : null;
        $dateFormatted = $carbonDate ? ucfirst($carbonDate->isoFormat('dddd D [de] MMMM')) : 'Fecha a convenir';

        // Schedule string
        $scheduleStr = 'Horario de servicio personalizado';
        if ($event->start_time && $event->end_time) {
            $scheduleStr = 'De ' . $event->start_time . ' a ' . $event->end_time;
        } elseif ($event->start_time) {
            $scheduleStr = 'A partir de las ' . $event->start_time;
        }

        // Check which pack matches the current quote
        $quoteAmount = (float)$quote->amount;
        $hasBasicPack = false;
        $hasMediumPack = false;
        $hasPremiumPack = false;
        $isCustomQuote = false;

        foreach ($quote->items as $qItem) {
            $sName = mb_strtolower($qItem->service_name);
            if (str_contains($sName, 'básico') || str_contains($sName, 'basico')) {
                $hasBasicPack = true;
            } elseif (str_contains($sName, 'medio') || str_contains($sName, 'recomendado')) {
                $hasMediumPack = true;
            } elseif (str_contains($sName, 'premium')) {
                $hasPremiumPack = true;
            } else {
                $isCustomQuote = true;
            }
        }

        // Default highlight to Medium if nothing explicitly selected
        if (!$hasBasicPack && !$hasMediumPack && !$hasPremiumPack) {
            $hasMediumPack = true;
        }
    @endphp

    <!-- ==========================================
         PÁGINA 1: PORTADA
         ========================================== -->
    <div class="page cover-page">
        <!-- Logotipo centrado -->
        <div class="cover-logo-wrapper">
            @if($logoPath && file_exists($logoPath))
                <img src="{{ $logoPath }}" class="cover-logo-img" alt="Logo">
            @else
                <div class="cover-badge-logo">
                    <div class="cover-badge-title">NÚÑEZ</div>
                    <div class="cover-badge-sub">AND SON · DJ</div>
                </div>
            @endif
        </div>

        <!-- Tarjeta flotante inferior -->
        <div class="cover-bottom-card">
            <div class="cover-subtitle-top">{{ mb_strtoupper($companyName) }} &bull; DJ & SONIDO</div>
            <div class="cover-main-title">Propuesta para<br>tu evento</div>
            <div class="cover-gold-line"></div>
            
            <div class="cover-meta-item">
                📅 <strong>{{ $dateFormatted }}</strong>
            </div>
            <div class="cover-meta-item">
                📍 <strong>{{ $event->location ?: 'Lugar a convenir' }}</strong>
            </div>
            <div class="cover-meta-item">
                🕒 <strong>{{ $scheduleStr }}</strong>
            </div>
        </div>
    </div>

    <!-- ==========================================
         PÁGINA 2: QUÉ LLEVAMOS Y CÓMO TRABAJAMOS
         ========================================== -->
    <div class="page">
        <!-- Cabecera Azul Marino -->
        <div class="page-header-banner">
            <div class="page-header-sub">QUÉ LLEVAMOS</div>
            <div class="page-header-title">DJ, sonido e iluminación propios</div>
        </div>

        <div class="page-content">
            <!-- Párrafo introductorio -->
            <p class="intro-lead">
                Nos encargamos de todo: llevamos el equipo, lo montamos y lo probamos antes de que lleguen los invitados, y pinchamos toda la tarde leyendo el ambiente para que la pista no se vacíe. Tú solo te preocupas de disfrutar.
            </p>

            <!-- Mosaico de Bloques de Equipamiento -->
            <table class="photo-mosaic-table">
                <tr>
                    <td colspan="2" class="photo-card" style="height: 140px; background-color: #111c34;">
                        <div class="photo-card-inner">
                            <span class="photo-card-icon">🔊</span>
                            <div class="photo-card-title">Sonido Profesional de Gran Potencia y Claridad</div>
                            <div class="photo-card-desc">Sistemas autoamplificados de alta definición con refuerzo de subgraves para interiores y exteriores.</div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="photo-card" style="width: 50%; height: 130px; background-color: #16223f;">
                        <div class="photo-card-inner">
                            <span class="photo-card-icon">💡</span>
                            <div class="photo-card-title">Iluminación Dinámica de Pista</div>
                            <div class="photo-card-desc">Cabezas móviles, focos LED y efectos de ambientación con máquina de humo.</div>
                        </div>
                    </td>
                    <td class="photo-card" style="width: 50%; height: 130px; background-color: #1e293b;">
                        <div class="photo-card-inner">
                            <span class="photo-card-icon">🎧</span>
                            <div class="photo-card-title">Sesión DJ en Directo</div>
                            <div class="photo-card-desc">Lectura continua de la pista, animación cercana y coordinación en directo.</div>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Bloque Cómo Trabajamos -->
            <div class="work-box">
                <div class="work-box-title">Cómo trabajamos</div>
                <ul class="work-box-list">
                    <li>
                        <span class="bullet">&bull;</span>
                        <strong>Montaje y prueba de sonido</strong> antes del inicio del evento
                    </li>
                    <li>
                        <span class="bullet">&bull;</span>
                        <strong>Música a vuestro gusto:</strong> antes del evento hablamos para conocer qué os gusta y qué no
                    </li>
                    <li>
                        <span class="bullet">&bull;</span>
                        <strong>Desmontaje al terminar,</strong> sin que tengáis que preocuparos de nada
                    </li>
                </ul>
            </div>
        </div>

        <!-- Pie de página -->
        <div class="page-footer-banner">
            <strong>{{ $companyName }}</strong> &bull; Fran {{ $companyPhone }} &bull; Miguel {{ $companyPhone2 }} &bull; {{ $companyWebsite }}
        </div>
    </div>

    <!-- ==========================================
         PÁGINA 3: OPCIONES Y PACKS
         ========================================== -->
    <div class="page">
        <!-- Cabecera Azul Marino -->
        <div class="page-header-banner">
            <div class="page-header-sub">OPCIONES</div>
            <div class="page-header-title">Elige la que mejor encaja</div>
        </div>

        <div class="page-content">
            <!-- 3 Columnas de Packs -->
            <table class="packs-table">
                <tr>
                    <!-- PACK BÁSICO -->
                    <td class="pack-col">
                        <div class="pack-card {{ $hasBasicPack ? 'highlighted' : '' }}">
                            @if($hasBasicPack)
                                <div class="pack-badge">SELECCIONADO</div>
                            @endif
                            <div class="pack-title">Básico</div>
                            <div class="pack-schedule">{{ $packBasicHours }} Horas de servicio</div>
                            <div class="pack-price">{{ number_format($packBasicPrice, 0, ',', '.') }}€</div>
                            <ul class="pack-features">
                                <li>DJ durante {{ $packBasicHours }} horas</li>
                                <li>Equipo de sonido</li>
                                <li>Iluminación de pista</li>
                                <li>Montaje y desmontaje</li>
                            </ul>
                        </div>
                    </td>

                    <!-- PACK MEDIO (RECOMENDADO) -->
                    <td class="pack-col">
                        <div class="pack-card {{ $hasMediumPack ? 'highlighted' : '' }}">
                            <div class="pack-badge">{{ $hasMediumPack && $hasBasicPack == false && $hasPremiumPack == false ? 'RECOMENDADO' : 'POPULAR' }}</div>
                            <div class="pack-title">Medio</div>
                            <div class="pack-schedule">Hasta {{ $packMediumHours }} Horas de servicio</div>
                            <div class="pack-price">{{ number_format($packMediumPrice, 0, ',', '.') }}€</div>
                            <ul class="pack-features">
                                <li>DJ hasta {{ $packMediumHours }} horas</li>
                                <li>Sonido reforzado con subgraves</li>
                                <li>Iluminación completa de pista y ambiente</li>
                                <li>Montaje y desmontaje</li>
                            </ul>
                        </div>
                    </td>

                    <!-- PACK PREMIUM -->
                    <td class="pack-col">
                        <div class="pack-card {{ $hasPremiumPack ? 'highlighted' : '' }}">
                            @if($hasPremiumPack)
                                <div class="pack-badge">SELECCIONADO</div>
                            @endif
                            <div class="pack-title">Premium</div>
                            <div class="pack-schedule">Hasta {{ $packPremiumHours }} Horas de servicio</div>
                            <div class="pack-price">{{ number_format($packPremiumPrice, 0, ',', '.') }}€</div>
                            <ul class="pack-features">
                                <li>Todo lo del pack Medio</li>
                                <li>Efecto de humo para la pista</li>
                                <li>Karaoke / Efectos especiales</li>
                                <li>Montaje y desmontaje</li>
                            </ul>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Si la propuesta tiene servicios personalizados contratados -->
            @if($isCustomQuote && count($quote->items) > 0)
                <div class="custom-selection-card">
                    <div class="custom-selection-title">📋 Servicios Seleccionados en esta Propuesta (#PRE-{{ str_pad($quote->id, 5, '0', STR_PAD_LEFT) }})</div>
                    <div class="custom-selection-body">
                        <strong>Total Presupuestado: {{ number_format($quote->amount, 2, ',', '.') }} €</strong>
                        &bull; Incluye: 
                        @foreach($quote->items as $idx => $item)
                            {{ $item->service_name }}{{ $idx < count($quote->items) - 1 ? ', ' : '.' }}
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Tarjeta Hora Extra -->
            <div class="info-card">
                <div class="info-card-title">Hora extra</div>
                <div class="info-card-body">
                    Si la fiesta se alarga, se puede ampliar en cualquier opción: <strong>{{ number_format($extraHourPrice, 0, ',', '.') }}€ por hora adicional</strong>.
                </div>
            </div>

            <!-- Tarjeta Reserva -->
            <div class="info-card">
                <div class="info-card-title">Reserva</div>
                <div class="info-card-body">
                    Al ser una fecha tan solicitada, la reserva de fecha se confirma por estricto orden de contratación. Señal estipulada: <strong>{{ number_format($quote->signal_amount, 2, ',', '.') }} €</strong> vía Bizum o Transferencia. Las condiciones de pago y detalles se confirman al formalizar.
                </div>
            </div>

            <div class="tax-note">
                Precios finales, impuestos incluidos. Propuesta válida durante 15 días desde su emisión.
            </div>
        </div>

        <!-- Pie de página -->
        <div class="page-footer-banner">
            <strong>{{ $companyName }}</strong> &bull; Fran {{ $companyPhone }} &bull; Miguel {{ $companyPhone2 }} &bull; {{ $companyWebsite }}
        </div>
    </div>

</body>
</html>
