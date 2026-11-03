<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $companyName = $brandInfo['name'] ?? ($config['brand_title'] ?? 'Núñez & Son');
        $companySub = $brandInfo['subtitle'] ?? 'Producción Musical, Sonido, Iluminación & Eventos';
        $logoUrl = \App\Models\Setting::getLogoUrl();
        $faviconUrl = \App\Models\Setting::getFaviconUrl();
        $tariffYear = $config['tariff_year'] ?? '2027';
        $phoneDisplay = $config['contact_whatsapp'] ?? '+34 690 87 89 16';
        $igDisplay = $config['contact_instagram'] ?? '@nunezandson';
        $webDisplay = $config['contact_web'] ?? 'www.nunezandson.com';
    @endphp
    <title>Pantalla Stand TV · {{ $companyName }} ({{ $fairName }})</title>
    <link rel="icon" type="image/svg+xml" href="{{ $faviconUrl }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,800;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        :root {
            --bg-deep: #040711;
            --bg-card: rgba(15, 23, 42, 0.82);
            --gold: #f59e0b;
            --gold-light: #fde68a;
            --emerald: #10b981;
            --emerald-light: #a7f3d0;
            --green-wa: #22c55e;
            --indigo: #6366f1;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            user-select: none;
        }

        html, body {
            width: 100%;
            height: 100%;
            background-color: var(--bg-deep);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow: hidden;
        }

        /* En pantallas pequeñas/móviles permitir scroll vertical */
        @media (max-width: 1024px), (max-height: 620px) {
            html, body {
                overflow-y: auto;
                height: auto;
                min-height: 100%;
            }
        }

        .tv-stage {
            position: relative;
            width: 100vw;
            height: 100vh;
            display: grid;
            grid-template-rows: auto 1fr auto;
            padding: clamp(14px, 1.8vh, 26px) clamp(18px, 2.2vw, 38px);
            gap: clamp(12px, 1.6vh, 22px);
            background:
                radial-gradient(circle at 12% 15%, rgba(245, 158, 11, 0.16) 0%, transparent 42%),
                radial-gradient(circle at 88% 18%, rgba(16, 185, 129, 0.14) 0%, transparent 42%),
                radial-gradient(circle at 50% 92%, rgba(99, 102, 241, 0.15) 0%, transparent 48%),
                linear-gradient(160deg, #03060f 0%, #091022 50%, #040711 100%);
            z-index: 1;
        }

        @media (max-width: 1024px), (max-height: 620px) {
            .tv-stage {
                height: auto;
                min-height: 100vh;
            }
        }

        /* Borde decorativo sutil de stand */
        .tv-stage::before {
            content: "";
            position: fixed;
            inset: 8px;
            border: 1px solid rgba(245, 158, 11, 0.22);
            border-radius: 22px;
            pointer-events: none;
            z-index: 2;
        }

        /* CABECERA SUPERIOR */
        .tv-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            background: rgba(15, 23, 42, 0.78);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: clamp(10px, 1.4vh, 18px) clamp(16px, 1.8vw, 28px);
            backdrop-filter: blur(16px);
            box-shadow: 0 15px 35px -10px rgba(0, 0, 0, 0.6);
        }

        .brand-block {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
        }

        .brand-logo {
            width: clamp(48px, 5.2vh, 66px);
            height: clamp(48px, 5.2vh, 66px);
            border-radius: 16px;
            background: #020617;
            border: 2px solid rgba(245, 158, 11, 0.55);
            padding: 6px;
            object-fit: contain;
            box-shadow: 0 0 25px rgba(245, 158, 11, 0.25);
            flex-shrink: 0;
        }

        .brand-logo-fallback {
            width: clamp(48px, 5.2vh, 66px);
            height: clamp(48px, 5.2vh, 66px);
            border-radius: 16px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #020617;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 900;
            flex-shrink: 0;
        }

        .brand-titles h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(20px, 2.3vw, 34px);
            font-weight: 800;
            letter-spacing: -0.01em;
            color: #ffffff;
            line-height: 1.08;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .brand-titles h1 .year-pill {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: clamp(11px, 0.95vw, 14px);
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            color: #020617;
            padding: 4px 12px;
            border-radius: 999px;
        }

        .brand-titles p {
            font-size: clamp(11px, 1vw, 15px);
            color: #cbd5e1;
            font-weight: 500;
            margin-top: 3px;
        }

        /* BANNER PROMO FERIA EN CABECERA */
        .fair-promo-pill {
            display: flex;
             flex-direction: column;
            align-items: flex-end;
            justify-content: center;
            text-align: right;
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(16, 185, 129, 0.18));
            border: 1.5px solid rgba(245, 158, 11, 0.6);
            border-radius: 16px;
            padding: 10px 18px;
            box-shadow: 0 0 30px rgba(245, 158, 11, 0.18);
            flex-shrink: 0;
        }

        .fair-promo-pill .fair-kicker {
            font-size: clamp(10px, 0.85vw, 12px);
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: var(--gold-light);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .fair-promo-pill .fair-headline {
            font-size: clamp(14px, 1.35vw, 21px);
            font-weight: 900;
            color: #ffffff;
            margin-top: 2px;
        }

        .fair-promo-pill .fair-headline strong {
            color: #34d399;
        }

        .fair-promo-pill .fair-sub {
            font-size: clamp(10px, 0.8vw, 12px);
            color: #cbd5e1;
            margin-top: 1px;
        }

        /* GRID CENTRAL DE QRs */
        .qr-grid {
            display: grid;
            gap: clamp(14px, 1.6vw, 24px);
            align-items: stretch;
            min-height: 0;
        }

        .qr-grid.cols-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .qr-grid.cols-4 {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        @media (max-width: 1024px) {
            .qr-grid.cols-3,
            .qr-grid.cols-4 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .tv-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .fair-promo-pill {
                align-items: flex-start;
                text-align: left;
                width: 100%;
            }
        }

        @media (max-width: 640px) {
            .qr-grid.cols-3,
            .qr-grid.cols-4 {
                grid-template-columns: 1fr;
            }
        }

        /* TARJETA INDIVIDUAL QR */
        .qr-card {
            position: relative;
            background: var(--bg-card);
            border: 1.5px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: clamp(14px, 2vh, 24px) clamp(14px, 1.5vw, 22px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            text-align: center;
            cursor: pointer;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 18px 40px -15px rgba(0, 0, 0, 0.7);
            overflow: hidden;
        }

        .qr-card:hover,
        .qr-card.active-pulse {
            transform: translateY(-5px) scale(1.015);
        }

        .qr-card.accent-amber {
            border-color: rgba(245, 158, 11, 0.45);
            background: linear-gradient(180deg, rgba(245, 158, 11, 0.12) 0%, rgba(15, 23, 42, 0.88) 45%, rgba(15, 23, 42, 0.95) 100%);
        }
        .qr-card.accent-amber.active-pulse,
        .qr-card.accent-amber:hover {
            border-color: #fbbf24;
            box-shadow: 0 20px 45px -10px rgba(245, 158, 11, 0.32);
        }

        .qr-card.accent-emerald {
            border-color: rgba(16, 185, 129, 0.45);
            background: linear-gradient(180deg, rgba(16, 185, 129, 0.12) 0%, rgba(15, 23, 42, 0.88) 45%, rgba(15, 23, 42, 0.95) 100%);
        }
        .qr-card.accent-emerald.active-pulse,
        .qr-card.accent-emerald:hover {
            border-color: #34d399;
            box-shadow: 0 20px 45px -10px rgba(16, 185, 129, 0.32);
        }

        .qr-card.accent-green {
            border-color: rgba(34, 197, 94, 0.45);
            background: linear-gradient(180deg, rgba(34, 197, 94, 0.12) 0%, rgba(15, 23, 42, 0.88) 45%, rgba(15, 23, 42, 0.95) 100%);
        }
        .qr-card.accent-green.active-pulse,
        .qr-card.accent-green:hover {
            border-color: #4ade80;
            box-shadow: 0 20px 45px -10px rgba(34, 197, 94, 0.32);
        }

        .qr-card.accent-indigo {
            border-color: rgba(99, 102, 241, 0.45);
            background: linear-gradient(180deg, rgba(99, 102, 241, 0.12) 0%, rgba(15, 23, 42, 0.88) 45%, rgba(15, 23, 42, 0.95) 100%);
        }
        .qr-card.accent-indigo.active-pulse,
        .qr-card.accent-indigo:hover {
            border-color: #818cf8;
            box-shadow: 0 20px 45px -10px rgba(99, 102, 241, 0.32);
        }

        .qr-step-pill {
            font-size: clamp(10px, 0.78vw, 12px);
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            padding: 4px 12px;
            border-radius: 999px;
            margin-bottom: 6px;
        }
        .accent-amber .qr-step-pill { background: rgba(245, 158, 11, 0.2); color: #fde68a; border: 1px solid rgba(245, 158, 11, 0.4); }
        .accent-emerald .qr-step-pill { background: rgba(16, 185, 129, 0.2); color: #a7f3d0; border: 1px solid rgba(16, 185, 129, 0.4); }
        .accent-green .qr-step-pill { background: rgba(34, 197, 94, 0.2); color: #bbf7d0; border: 1px solid rgba(34, 197, 94, 0.4); }
        .accent-indigo .qr-step-pill { background: rgba(99, 102, 241, 0.2); color: #c7d2fe; border: 1px solid rgba(99, 102, 241, 0.4); }

        .qr-card-title {
            font-size: clamp(16px, 1.45vw, 23px);
            font-weight: 900;
            color: #ffffff;
            line-height: 1.2;
            margin-bottom: 4px;
        }

        .qr-card-sub {
            font-size: clamp(11px, 0.88vw, 13.5px);
            color: #cbd5e1;
            line-height: 1.38;
            max-width: 34ch;
        }

        /* CAJA BLANCA DE ALTO CONTRASTE PARA LECTURA INMEDIATA DE CÁMARA */
        .qr-box-wrapper {
            position: relative;
            background: #ffffff;
            padding: clamp(12px, 1.6vh, 18px);
            border-radius: 22px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.55);
            margin: clamp(8px, 1.4vh, 16px) 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 4px solid #ffffff;
        }

        .accent-amber .qr-box-wrapper { outline: 3px solid rgba(245, 158, 11, 0.65); }
        .accent-emerald .qr-box-wrapper { outline: 3px solid rgba(16, 185, 129, 0.65); }
        .accent-green .qr-box-wrapper { outline: 3px solid rgba(34, 197, 94, 0.65); }
        .accent-indigo .qr-box-wrapper { outline: 3px solid rgba(99, 102, 241, 0.65); }

        .qr-canvas-target img,
        .qr-canvas-target canvas {
            display: block !important;
            width: clamp(145px, 21vh, 220px) !important;
            height: clamp(145px, 21vh, 220px) !important;
        }

        .cols-3 .qr-canvas-target img,
        .cols-3 .qr-canvas-target canvas {
            width: clamp(160px, 23.5vh, 245px) !important;
            height: clamp(160px, 23.5vh, 245px) !important;
        }

        .qr-scan-cta {
            width: 100%;
            padding: 8px 12px;
            border-radius: 12px;
            font-size: clamp(11px, 0.88vw, 13px);
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .accent-amber .qr-scan-cta { background: linear-gradient(135deg, #f59e0b, #fbbf24); color: #020617; }
        .accent-emerald .qr-scan-cta { background: linear-gradient(135deg, #10b981, #34d399); color: #020617; }
        .accent-green .qr-scan-cta { background: linear-gradient(135deg, #22c55e, #4ade80); color: #020617; }
        .accent-indigo .qr-scan-cta { background: linear-gradient(135deg, #6366f1, #818cf8); color: #ffffff; }

        .qr-zoom-hint {
            font-size: 10px;
            color: #64748b;
            margin-top: 4px;
            font-weight: 600;
        }

        /* FRANJA INFERIOR (SERVICIOS O PACKS DE FERIA) */
        .tv-footer {
            background: rgba(15, 23, 42, 0.82);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 18px;
            padding: clamp(10px, 1.3vh, 16px) clamp(16px, 1.8vw, 26px);
            backdrop-filter: blur(16px);
        }

        .services-strip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .service-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: rgba(30, 41, 59, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 7px 14px;
            border-radius: 999px;
            font-size: clamp(11px, 0.9vw, 13.5px);
            font-weight: 700;
            color: #f1f5f9;
        }

        .packs-strip {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        @media (max-width: 900px) {
            .packs-strip {
                grid-template-columns: 1fr;
            }
        }

        .pack-mini-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: rgba(2, 6, 23, 0.75);
            border: 1px solid rgba(245, 158, 11, 0.35);
            border-radius: 14px;
            padding: 8px 16px;
        }

        .pack-mini-card .pack-name {
            font-size: clamp(12px, 1vw, 15px);
            font-weight: 800;
            color: #ffffff;
        }

        .pack-mini-card .pack-tagline {
            font-size: 11px;
            color: #94a3b8;
        }

        .pack-mini-card .pack-prices {
            text-align: right;
            flex-shrink: 0;
        }

        .pack-mini-card .old-price {
            font-size: 11px;
            color: #94a3b8;
            text-decoration: line-through;
            margin-right: 6px;
        }

        .pack-mini-card .dto-badge {
            font-size: 10px;
            font-weight: 900;
            background: rgba(16, 185, 129, 0.2);
            color: #6ee7b7;
            border: 1px solid rgba(16, 185, 129, 0.4);
            padding: 2px 7px;
            border-radius: 999px;
        }

        .pack-mini-card .final-price {
            display: block;
            font-size: clamp(15px, 1.3vw, 20px);
            font-weight: 900;
            color: #fbbf24;
            line-height: 1.1;
            margin-top: 2px;
        }

        /* BARRA FLOTANTE DISCRETA DE CONTROLES (SE OCULTA AL NO MOVER EL RATÓN) */
        .tv-controls-dock {
            position: fixed;
            bottom: 16px;
            right: 20px;
            z-index: 50;
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(2, 6, 23, 0.92);
            border: 1px solid rgba(255, 255, 255, 0.18);
            padding: 8px 12px;
            border-radius: 999px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.8);
            transition: opacity 0.4s ease, transform 0.4s ease;
        }

        .tv-controls-dock.dock-hidden {
            opacity: 0;
            pointer-events: none;
            transform: translateY(10px);
        }

        .tv-ctrl-btn {
            background: rgba(30, 41, 59, 0.9);
            hover: rgba(51, 65, 85, 1);
            color: #e2e8f0;
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }

        .tv-ctrl-btn:hover {
            background: #f59e0b;
            color: #020617;
            border-color: #fbbf24;
        }

        .tv-ctrl-btn.active {
            background: rgba(16, 185, 129, 0.25);
            color: #6ee7b7;
            border-color: rgba(16, 185, 129, 0.5);
        }

        /* MODAL ZOOM QR GIGANTE AL PULSAR UNA TARJETA */
        .qr-spotlight-modal {
            position: fixed;
            inset: 0;
            z-index: 100;
            background: rgba(2, 6, 23, 0.92);
            backdrop-filter: blur(18px);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .qr-spotlight-modal.open {
            display: flex;
        }

        .spotlight-card {
            background: #0f172a;
            border: 2px solid #f59e0b;
            border-radius: 32px;
            padding: 32px 42px;
            max-width: 640px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.9);
            position: relative;
        }

        .spotlight-qr-box {
            background: #ffffff;
            padding: 22px;
            border-radius: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6);
        }

        .spotlight-close {
            margin-top: 14px;
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 10px 24px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <div class="tv-stage" id="tvStage">

        <!-- 1. CABECERA SUPERIOR DE LA TELE -->
        <header class="tv-header">
            <div class="brand-block">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $companyName }}" class="brand-logo" onerror="this.style.display='none'; document.getElementById('fallbackLogo').style.display='flex';">
                    <div id="fallbackLogo" class="brand-logo-fallback" style="display: none;">🎧</div>
                @else
                    <div class="brand-logo-fallback">🎧</div>
                @endif

                <div class="brand-titles">
                    <h1>
                        <span>{{ $companyName }}</span>
                        <span class="year-pill">Temporada Bodas {{ $tariffYear }}</span>
                    </h1>
                    <p>
                        🎧 Sonorización, Iluminación, DJ, Efectos Especiales, Fotomatón, Plataforma 360º y Mesas Dulces &bull;
                        <strong>{{ $config['coverage_areas'] ?? 'La Rioja · Álava · Navarra · Burgos' }}</strong>
                    </p>
                </div>
            </div>

            @if($fairEnabled)
                <div class="fair-promo-pill">
                    <span class="fair-kicker">
                        <span>🎪</span>
                        <span>PROMOCIÓN EXCLUSIVA STAND &bull; {{ $fairName }}</span>
                    </span>
                    <div class="fair-headline">
                        ¡Hasta <strong>-{{ number_format($maxDiscount, 0, ',', '.') }} € de Descuento</strong> en vuestra Boda!
                    </div>
                    <div class="fair-sub">
                        Escanea cualquiera de los códigos QR con la cámara de tu móvil
                    </div>
                </div>
            @else
                <div class="fair-promo-pill">
                    <span class="fair-kicker">
                        <span>📲</span>
                        <span>MOSTRADOR INTERACTIVO DE NOVIOS</span>
                    </span>
                    <div class="fair-headline">
                        Escanea con tu móvil y llévate toda la información
                    </div>
                    <div class="fair-sub">
                        {{ $phoneDisplay }} &bull; {{ $igDisplay }} &bull; {{ $webDisplay }}
                    </div>
                </div>
            @endif
        </header>

        <!-- 2. GRID CENTRAL DE CÓDIGOS QR -->
        <main class="qr-grid cols-4" id="qrGrid">
            @foreach($qrItems as $idx => $item)
                <div class="qr-card accent-{{ $item['accent'] }}"
                     id="qrCard-{{ $idx }}"
                     data-idx="{{ $idx }}"
                     data-title="{{ $item['icon'] }} {{ $item['title'] }}"
                     data-subtitle="{{ $item['subtitle'] }}"
                     data-badge="{{ $item['badge'] }}"
                     data-url="{{ $item['url'] }}"
                     onclick="openSpotlight(this)">
                    <div>
                        <span class="qr-step-pill">{{ $item['step'] }}</span>
                        <h2 class="qr-card-title">{{ $item['icon'] }} {{ $item['title'] }}</h2>
                        <p class="qr-card-sub">{{ $item['subtitle'] }}</p>
                    </div>

                    <div class="qr-box-wrapper">
                        <div id="qrTarget-{{ $idx }}" class="qr-canvas-target" data-url="{{ $item['url'] }}"></div>
                    </div>

                    <div style="width: 100%;">
                        <div class="qr-scan-cta">
                            <span>📱</span>
                            <span>{{ $item['badge'] }}</span>
                        </div>
                        <div class="qr-zoom-hint">🔍 Toca esta tarjeta para ampliar el QR a pantalla completa</div>
                    </div>
                </div>
            @endforeach
        </main>

        <!-- 3. PIE DE PANTALLA: SERVICIOS O RESUMEN DE PACKS DE FERIA -->
        <footer class="tv-footer">
            <!-- Vista A: Píldoras de todos los servicios -->
            <div class="services-strip" id="footerServices">
                <span class="service-pill">💍 Sonorización Ceremonia Civil / Exterior</span>
                <span class="service-pill">🥂 Cóctel & Entrada al Banquete</span>
                <span class="service-pill">🎧 Producción DJ, Sonido & Iluminación</span>
                <span class="service-pill">🌫️ Humo Bajo & Fuego Frío (Chispas)</span>
                <span class="service-pill">📸 Fotomatón & Plataforma Vídeo 360º</span>
                <span class="service-pill">🍫 Fuente de Chocolate, Candy Bar & Gofres</span>
                <span class="service-pill">📲 Álbum de Fotos QR para Invitados</span>
            </div>

            <!-- Vista B: Los 3 Packs con el Descuento de Feria (conmutable en 1 clic) -->
            <div class="packs-strip" id="footerPacks" style="display: none;">
                @foreach($packsComputed as $p)
                    @php
                        $pr = $p['pricing'];
                    @endphp
                    <div class="pack-mini-card">
                        <div>
                            <div class="pack-name">
                                {{ $p['name'] }}
                                @if(!empty($p['highlight']))
                                    <span style="color: #fbbf24;">⭐</span>
                                @endif
                            </div>
                            <div class="pack-tagline">{{ $p['tagline'] ?? '' }}</div>
                        </div>
                        <div class="pack-prices">
                            @if(!empty($pr['fair_enabled']) && $pr['discount_amount'] > 0)
                                <div>
                                    <span class="old-price">{{ number_format($pr['inflated_price'], 0, ',', '.') }} €</span>
                                    <span class="dto-badge">-{{ number_format($pr['discount_amount'], 0, ',', '.') }} € Feria</span>
                                </div>
                            @endif
                            <span class="final-price">{{ number_format($pr['final_price'], 0, ',', '.') }} €</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </footer>

    </div>

    <!-- MODAL DE ZOOM GIGANTE AL PULSAR CUALQUIER QR -->
    <div class="qr-spotlight-modal" id="spotlightModal" onclick="closeSpotlight()">
        <div class="spotlight-card" onclick="event.stopPropagation()">
            <div id="spotTitle" style="font-size: 26px; font-weight: 900; color: #ffffff;"></div>
            <p id="spotSub" style="font-size: 14px; color: #cbd5e1; margin-top: 6px;"></p>

            <div class="spotlight-qr-box">
                <div id="spotQrTarget"></div>
            </div>

            <div id="spotBadge" style="font-size: 14px; font-weight: 800; color: #fbbf24;"></div>
            <button type="button" class="spotlight-close" onclick="closeSpotlight()">
                ✕ Volver a todos los códigos QR (ESC)
            </button>
        </div>
    </div>

    <!-- BARRA DE CONTROL DISCRETA (SE OCULTA SOLA EN LA TV CUANDO NO SE MUEVE EL RATÓN) -->
    <div class="tv-controls-dock" id="controlsDock">
        <button type="button" class="tv-ctrl-btn" onclick="toggleFullScreen()" id="btnFullscreen" title="Poner en Pantalla Completa en la TV (F11)">
            <span>⛶</span> <span id="lblFullscreen">Pantalla Completa</span>
        </button>
        <button type="button" class="tv-ctrl-btn" onclick="toggleQrColumns()" id="btnCols" title="Alternar entre 3 QRs gigantes o los 4 QRs">
            <span>🔢</span> <span id="lblCols">Ver 3 QRs Gigantes</span>
        </button>
        <button type="button" class="tv-ctrl-btn" onclick="toggleFooterMode()" id="btnFooterMode" title="Mostrar los 3 Packs con precios o las píldoras de Servicios sin precios en el pie">
            <span>💶</span> <span id="lblFooterMode">Mostrar Precios Packs en Pie</span>
        </button>
        <button type="button" class="tv-ctrl-btn active" onclick="toggleAutoHighlight()" id="btnAutoPulse" title="Resaltar suavemente cada QR cada 6 segundos para atraer miradas en el stand">
            <span>✨</span> <span id="lblAutoPulse">Auto-Destacado: ON</span>
        </button>
    </div>

    <script>
        let currentCols = parseInt(localStorage.getItem('stand_tv_cols') || '4', 10);
        let showPacksFooter = localStorage.getItem('stand_tv_footer') === 'packs';
        let autoPulseEnabled = localStorage.getItem('stand_tv_autopulse') !== '0';
        let pulseIndex = 0;
        let pulseTimer = null;
        let hideDockTimer = null;

        function renderAllQrs() {
            const targets = document.querySelectorAll('.qr-canvas-target');
            targets.forEach((el) => {
                const url = el.getAttribute('data-url');
                el.innerHTML = '';
                if (window.QRCode && url) {
                    new QRCode(el, {
                        text: url,
                        width: 240,
                        height: 240,
                        colorDark: '#020617',
                        colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.M
                    });
                }
            });
        }

        function applyLayoutState() {
            const grid = document.getElementById('qrGrid');
            const card4 = document.getElementById('qrCard-3');
            const lblCols = document.getElementById('lblCols');

            if (currentCols === 3) {
                grid.classList.remove('cols-4');
                grid.classList.add('cols-3');
                if (card4) card4.style.display = 'none';
                if (lblCols) lblCols.textContent = 'Ver los 4 QRs';
            } else {
                grid.classList.remove('cols-3');
                grid.classList.add('cols-4');
                if (card4) card4.style.display = 'flex';
                if (lblCols) lblCols.textContent = 'Ver 3 QRs Gigantes';
            }

            const fServ = document.getElementById('footerServices');
            const fPacks = document.getElementById('footerPacks');
            const lblFooter = document.getElementById('lblFooterMode');
            const btnFooter = document.getElementById('btnFooterMode');

            if (showPacksFooter) {
                fServ.style.display = 'none';
                fPacks.style.display = 'grid';
                lblFooter.textContent = 'Ocultar Precios en Pie';
                btnFooter.classList.add('active');
            } else {
                fServ.style.display = 'flex';
                fPacks.style.display = 'none';
                lblFooter.textContent = 'Mostrar Precios Packs en Pie';
                btnFooter.classList.remove('active');
            }

            const btnAuto = document.getElementById('btnAutoPulse');
            const lblAuto = document.getElementById('lblAutoPulse');
            if (autoPulseEnabled) {
                btnAuto.classList.add('active');
                lblAuto.textContent = 'Auto-Destacado: ON';
                startAutoPulse();
            } else {
                btnAuto.classList.remove('active');
                lblAuto.textContent = 'Auto-Destacado: OFF';
                stopAutoPulse();
            }
        }

        function toggleQrColumns() {
            currentCols = currentCols === 4 ? 3 : 4;
            localStorage.setItem('stand_tv_cols', String(currentCols));
            applyLayoutState();
        }

        function toggleFooterMode() {
            showPacksFooter = !showPacksFooter;
            localStorage.setItem('stand_tv_footer', showPacksFooter ? 'packs' : 'services');
            applyLayoutState();
        }

        function toggleAutoHighlight() {
            autoPulseEnabled = !autoPulseEnabled;
            localStorage.setItem('stand_tv_autopulse', autoPulseEnabled ? '1' : '0');
            applyLayoutState();
        }

        function startAutoPulse() {
            stopAutoPulse();
            pulseTimer = setInterval(() => {
                const maxCards = currentCols === 3 ? 3 : 4;
                document.querySelectorAll('.qr-card').forEach(c => c.classList.remove('active-pulse'));
                const target = document.getElementById('qrCard-' + (pulseIndex % maxCards));
                if (target) {
                    target.classList.add('active-pulse');
                }
                pulseIndex = (pulseIndex + 1) % maxCards;
            }, 5500);
        }

        function stopAutoPulse() {
            if (pulseTimer) {
                clearInterval(pulseTimer);
                pulseTimer = null;
            }
            document.querySelectorAll('.qr-card').forEach(c => c.classList.remove('active-pulse'));
        }

        function openSpotlight(cardEl) {
            const title = cardEl.getAttribute('data-title');
            const sub = cardEl.getAttribute('data-subtitle');
            const badge = cardEl.getAttribute('data-badge');
            const url = cardEl.getAttribute('data-url');

            document.getElementById('spotTitle').textContent = title;
            document.getElementById('spotSub').textContent = sub;
            document.getElementById('spotBadge').textContent = badge;

            const qrContainer = document.getElementById('spotQrTarget');
            qrContainer.innerHTML = '';
            if (window.QRCode && url) {
                new QRCode(qrContainer, {
                    text: url,
                    width: 340,
                    height: 340,
                    colorDark: '#020617',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });
            }

            document.getElementById('spotlightModal').classList.add('open');
        }

        function closeSpotlight() {
            document.getElementById('spotlightModal').classList.remove('open');
        }

        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
            } else {
                document.exitFullscreen().catch(() => {});
            }
        }

        document.addEventListener('fullscreenchange', () => {
            const lbl = document.getElementById('lblFullscreen');
            if (lbl) {
                lbl.textContent = document.fullscreenElement ? 'Salir Pantalla Completa' : 'Pantalla Completa';
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeSpotlight();
            } else if (['1', '2', '3', '4'].includes(e.key)) {
                const idx = parseInt(e.key, 10) - 1;
                const card = document.getElementById('qrCard-' + idx);
                if (card && card.style.display !== 'none') {
                    openSpotlight(card);
                }
            }
        });

        function resetDockTimer() {
            const dock = document.getElementById('controlsDock');
            if (!dock) return;
            dock.classList.remove('dock-hidden');
            if (hideDockTimer) clearTimeout(hideDockTimer);
            hideDockTimer = setTimeout(() => {
                dock.classList.add('dock-hidden');
            }, 4000);
        }

        window.addEventListener('mousemove', resetDockTimer);
        window.addEventListener('touchstart', resetDockTimer);

        window.addEventListener('DOMContentLoaded', () => {
            renderAllQrs();
            applyLayoutState();
            resetDockTimer();
        });
    </script>
</body>
</html>
