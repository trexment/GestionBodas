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
            font-family: 'DejaVu Sans', 'Helvetica Neue', Arial, sans-serif;
            color: #1e293b;
            background-color: #ffffff;
            font-size: 8.5pt;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        /* ESTRUCTURA EXACTA DE PÁGINAS A4 */
        .page {
            page-break-after: always;
            position: relative;
            box-sizing: border-box;
            width: 100%;
        }
        .page-last {
            page-break-after: avoid;
        }

        /* ==================== PÁGINA 1: PORTADA ==================== */
        .cover-page {
            background-color: #0b1329;
            color: #ffffff;
            padding: 45px 40px;
            min-height: 1000px;
        }
        .cover-logo-wrapper {
            text-align: center;
            margin-top: 30px;
            margin-bottom: 60px;
        }
        .cover-logo-img {
            max-height: 120px;
            max-width: 260px;
            margin: 0 auto;
        }
        .cover-badge-logo {
            display: inline-block;
            border: 2px solid #eab308;
            border-radius: 50%;
            width: 120px;
            height: 120px;
            padding: 24px 10px;
            text-align: center;
        }
        .cover-badge-title {
            font-size: 14pt;
            font-weight: bold;
            letter-spacing: 2px;
            color: #ffffff;
            text-transform: uppercase;
        }
        .cover-badge-sub {
            font-size: 7pt;
            letter-spacing: 3px;
            color: #eab308;
            text-transform: uppercase;
            margin-top: 4px;
        }

        .cover-bottom-card {
            background-color: #111c34;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            padding: 28px 30px;
            margin-top: 40px;
        }
        .cover-subtitle-top {
            font-size: 8.5pt;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: #93c5fd;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .cover-main-title {
            font-size: 24pt;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.15;
            margin-bottom: 12px;
        }
        .cover-gold-line {
            width: 50px;
            height: 3px;
            background-color: #eab308;
            margin-bottom: 16px;
        }
        .cover-meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        .cover-meta-table td {
            padding: 4px 0;
            font-size: 10pt;
            color: #e2e8f0;
        }
        .cover-meta-label {
            color: #eab308;
            font-weight: 800;
            font-size: 8.5pt;
            text-transform: uppercase;
            letter-spacing: 1px;
            width: 100px;
        }
        .cover-meta-value {
            color: #ffffff;
            font-weight: 700;
        }

        /* ==================== PÁGINA 2 & 3: CABECERAS Y PIES ==================== */
        .page-header-banner {
            background-color: #0b1329;
            color: #ffffff;
            padding: 20px 35px;
        }
        .page-header-sub {
            font-size: 7.5pt;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: #93c5fd;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .page-header-title {
            font-size: 15pt;
            font-weight: 800;
            color: #ffffff;
        }

        .page-content {
            padding: 18px 35px 25px 35px;
        }

        .page-footer-banner {
            background-color: #0b1329;
            color: #94a3b8;
            padding: 9px 35px;
            font-size: 7.5pt;
            text-align: center;
            margin-top: 15px;
        }
        .page-footer-banner strong {
            color: #ffffff;
        }

        /* INTRO TEXT */
        .intro-lead {
            font-size: 9pt;
            color: #334155;
            line-height: 1.45;
            margin-bottom: 14px;
            text-align: justify;
        }

        /* MOSAICO DE FOTOS / EQUIPO */
        .photo-mosaic-table {
            width: 100%;
            margin-bottom: 14px;
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
            padding: 14px 12px;
        }
        .photo-card-pill {
            display: inline-block;
            background-color: rgba(234, 179, 8, 0.15);
            border: 1px solid #eab308;
            color: #eab308;
            font-size: 7pt;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 2px 8px;
            border-radius: 4px;
            margin-bottom: 6px;
        }
        .photo-card-title {
            font-size: 9pt;
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 3px;
        }
        .photo-card-desc {
            font-size: 7.5pt;
            color: #cbd5e1;
            line-height: 1.25;
        }

        /* TARJETA CÓMO TRABAJAMOS */
        .work-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #d97706;
            border-radius: 8px;
            padding: 12px 16px;
        }
        .work-box-title {
            font-size: 9.5pt;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
        }
        .work-box-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .work-box-list li {
            font-size: 8pt;
            color: #334155;
            margin-bottom: 4px;
            line-height: 1.35;
            position: relative;
            padding-left: 12px;
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
            border-spacing: 7px;
            margin-bottom: 10px;
        }
        .pack-col {
            width: 33.33%;
            vertical-align: top;
        }
        .pack-card {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 12px 10px;
            text-align: center;
        }
        .pack-card.highlighted {
            background-color: #0b1329;
            border: 2px solid #eab308;
            color: #ffffff;
        }
        .pack-badge {
            background-color: #eab308;
            color: #0f172a;
            font-size: 6.5pt;
            font-weight: 900;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 2px 6px;
            border-radius: 3px;
            display: inline-block;
            margin-bottom: 6px;
        }
        .pack-title {
            font-size: 12pt;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 1px;
        }
        .pack-card.highlighted .pack-title {
            color: #ffffff;
        }
        .pack-schedule {
            font-size: 7.5pt;
            color: #64748b;
            margin-bottom: 8px;
        }
        .pack-card.highlighted .pack-schedule {
            color: #94a3b8;
        }
        .pack-price {
            font-size: 18pt;
            font-weight: 900;
            color: #0f172a;
            margin-bottom: 8px;
        }
        .pack-card.highlighted .pack-price {
            color: #eab308;
        }
        .pack-features {
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
            text-align: center;
            list-style: none;
            margin: 0;
        }
        .pack-card.highlighted .pack-features {
            border-top: 1px solid rgba(255, 255, 255, 0.15);
        }
        .pack-features li {
            font-size: 7.5pt;
            color: #475569;
            padding: 2.5px 0;
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
            border-left: 3.5px solid #d97706;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 7px;
        }
        .info-card-title {
            font-size: 8.5pt;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-card-body {
            font-size: 7.5pt;
            color: #475569;
            line-height: 1.3;
        }

        .custom-selection-card {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-left: 3.5px solid #16a34a;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 7px;
        }
        .custom-selection-title {
            font-size: 8.5pt;
            font-weight: 800;
            color: #15803d;
            margin-bottom: 2px;
        }
        .custom-selection-body {
            font-size: 7.5pt;
            color: #166534;
            line-height: 1.3;
        }

        .tax-note {
            font-size: 7pt;
            color: #94a3b8;
            margin-top: 3px;
        }
    </style>
</head>
<body>

    @php
        $brandKey = $event->brand_clean ?? (\App\Models\Setting::getBrandInfo()['key']);
        $brand = \App\Models\Setting::getBrandInfo($brandKey);

        $companyName = $brand['name'];
        $companySubtitle = $brand['subtitle'];
        $companyPhone = $brand['phone'];
        $companyPhone2 = $brand['phone_2'];
        $companyWebsite = $brand['website'];
        $logoPath = $brand['logo_path'];

        // Configuración dinámica del Dossier
        $dDefaults = \App\Services\DossierTemplateService::getDefaults();
        $dossierCoverTitle = \App\Models\Setting::get('dossier_cover_title', $dDefaults['dossier_cover_title']);
        $dossierPage2Subtitle = \App\Models\Setting::get('dossier_page2_subtitle', $dDefaults['dossier_page2_subtitle']);
        $dossierPage2Title = \App\Models\Setting::get('dossier_page2_title', $dDefaults['dossier_page2_title']);
        $dossierIntroText = \App\Models\Setting::get('dossier_intro_text', $dDefaults['dossier_intro_text']);
        
        $dossierBlock1Title = \App\Models\Setting::get('dossier_block1_title', $dDefaults['dossier_block1_title']);
        $dossierBlock1Desc = \App\Models\Setting::get('dossier_block1_desc', $dDefaults['dossier_block1_desc']);
        
        $dossierBlock2Title = \App\Models\Setting::get('dossier_block2_title', $dDefaults['dossier_block2_title']);
        $dossierBlock2Desc = \App\Models\Setting::get('dossier_block2_desc', $dDefaults['dossier_block2_desc']);
        
        $dossierBlock3Title = \App\Models\Setting::get('dossier_block3_title', $dDefaults['dossier_block3_title']);
        $dossierBlock3Desc = \App\Models\Setting::get('dossier_block3_desc', $dDefaults['dossier_block3_desc']);
        
        $dossierWorkTitle = \App\Models\Setting::get('dossier_work_title', $dDefaults['dossier_work_title']);
        $dossierWorkItem1 = \App\Models\Setting::get('dossier_work_item1', $dDefaults['dossier_work_item1']);
        $dossierWorkItem2 = \App\Models\Setting::get('dossier_work_item2', $dDefaults['dossier_work_item2']);
        $dossierWorkItem3 = \App\Models\Setting::get('dossier_work_item3', $dDefaults['dossier_work_item3']);
        
        $dossierExtraHoursTitle = \App\Models\Setting::get('dossier_extra_hours_title', $dDefaults['dossier_extra_hours_title']);
        $dossierExtraHoursDesc = \App\Models\Setting::get('dossier_extra_hours_desc', $dDefaults['dossier_extra_hours_desc']);
        $dossierMusicCustomTitle = \App\Models\Setting::get('dossier_music_custom_title', $dDefaults['dossier_music_custom_title']);
        $dossierMusicCustomDesc = \App\Models\Setting::get('dossier_music_custom_desc', $dDefaults['dossier_music_custom_desc']);

        // Imágenes de Bloques
        $b1Img = \App\Models\Setting::get('dossier_block1_image');
        $block1ImgPath = $b1Img && \Illuminate\Support\Facades\Storage::disk('public')->exists($b1Img) ? storage_path('app/public/' . $b1Img) : null;

        $b2Img = \App\Models\Setting::get('dossier_block2_image');
        $block2ImgPath = $b2Img && \Illuminate\Support\Facades\Storage::disk('public')->exists($b2Img) ? storage_path('app/public/' . $b2Img) : null;

        $b3Img = \App\Models\Setting::get('dossier_block3_image');
        $block3ImgPath = $b3Img && \Illuminate\Support\Facades\Storage::disk('public')->exists($b3Img) ? storage_path('app/public/' . $b3Img) : null;

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
        $dateFormatted = $carbonDate ? ucfirst($carbonDate->isoFormat('dddd D [de] MMMM [de] YYYY')) : 'Fecha a convenir';

        // Schedule string
        $scheduleStr = 'Horario personalizado a convenir';
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
                    <div class="cover-badge-title">{{ $brandKey === 'javnx' ? 'JAVNX' : 'NÚÑEZ' }}</div>
                    <div class="cover-badge-sub">{{ $brandKey === 'javnx' ? 'DJ & EVENTS' : 'AND SON · DJ' }}</div>
                </div>
            @endif
        </div>

        <!-- Tarjeta de Detalles de Portada -->
        <div class="cover-bottom-card">
            <div class="cover-subtitle-top">{{ mb_strtoupper($companyName) }} &bull; {{ mb_strtoupper($companySubtitle) }}</div>
            <div class="cover-main-title">{!! nl2br(e($dossierCoverTitle)) !!}</div>
            <div class="cover-gold-line"></div>
            
            <table class="cover-meta-table">
                <tr>
                    <td class="cover-meta-label">FECHA</td>
                    <td class="cover-meta-value">{{ $dateFormatted }}</td>
                </tr>
                <tr>
                    <td class="cover-meta-label">LUGAR</td>
                    <td class="cover-meta-value">{{ $event->location ?: 'Lugar a convenir' }}</td>
                </tr>
                <tr>
                    <td class="cover-meta-label">HORARIO</td>
                    <td class="cover-meta-value">{{ $scheduleStr }}</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- ==========================================
         PÁGINA 2: QUÉ LLEVAMOS Y CÓMO TRABAJAMOS
         ========================================== -->
    <div class="page">
        <!-- Cabecera Azul Marino -->
        <div class="page-header-banner">
            @if(!empty($dossierPage2Subtitle))
                <div class="page-header-sub">{{ mb_strtoupper($dossierPage2Subtitle) }}</div>
            @endif
            <div class="page-header-title">{{ $dossierPage2Title }}</div>
        </div>

        <div class="page-content">
            <!-- Párrafo introductorio -->
            <p class="intro-lead">
                {{ $dossierIntroText }}
            </p>

            <!-- Mosaico de Bloques de Equipamiento -->
            <table class="photo-mosaic-table">
                <tr>
                    <td colspan="2" class="photo-card" style="background-color: #111c34;">
                        @if($block1ImgPath)
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="width: 36%; padding: 6px; vertical-align: middle; text-align: center;">
                                        <img src="{{ $block1ImgPath }}" style="width: 100%; height: 100px; object-fit: cover; border-radius: 6px; display: block;">
                                    </td>
                                    <td style="width: 64%; padding: 8px 12px; vertical-align: middle; text-align: left; color: #ffffff;">
                                        <div class="photo-card-pill">SONIDO PROFESIONAL</div>
                                        <div class="photo-card-title" style="color: #eab308;">{{ $dossierBlock1Title }}</div>
                                        <div class="photo-card-desc">{{ $dossierBlock1Desc }}</div>
                                    </td>
                                </tr>
                            </table>
                        @else
                            <div class="photo-card-inner">
                                <div class="photo-card-pill">SONIDO DE ALTA DEFINICIÓN</div>
                                <div class="photo-card-title">{{ $dossierBlock1Title }}</div>
                                <div class="photo-card-desc">{{ $dossierBlock1Desc }}</div>
                            </div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="photo-card" style="width: 50%; background-color: #16223f;">
                        @if($block2ImgPath)
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="width: 38%; padding: 6px; vertical-align: middle; text-align: center;">
                                        <img src="{{ $block2ImgPath }}" style="width: 100%; height: 90px; object-fit: cover; border-radius: 6px; display: block;">
                                    </td>
                                    <td style="width: 62%; padding: 6px 8px; vertical-align: middle; text-align: left; color: #ffffff;">
                                        <div class="photo-card-pill">ILUMINACIÓN</div>
                                        <div class="photo-card-title" style="color: #eab308;">{{ $dossierBlock2Title }}</div>
                                        <div class="photo-card-desc">{{ $dossierBlock2Desc }}</div>
                                    </td>
                                </tr>
                            </table>
                        @else
                            <div class="photo-card-inner">
                                <div class="photo-card-pill">ILUMINACIÓN & SHOW</div>
                                <div class="photo-card-title">{{ $dossierBlock2Title }}</div>
                                <div class="photo-card-desc">{{ $dossierBlock2Desc }}</div>
                            </div>
                        @endif
                    </td>
                    <td class="photo-card" style="width: 50%; background-color: #1e293b;">
                        @if($block3ImgPath)
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="width: 38%; padding: 6px; vertical-align: middle; text-align: center;">
                                        <img src="{{ $block3ImgPath }}" style="width: 100%; height: 90px; object-fit: cover; border-radius: 6px; display: block;">
                                    </td>
                                    <td style="width: 62%; padding: 6px 8px; vertical-align: middle; text-align: left; color: #ffffff;">
                                        <div class="photo-card-pill">SESIÓN DJ</div>
                                        <div class="photo-card-title" style="color: #eab308;">{{ $dossierBlock3Title }}</div>
                                        <div class="photo-card-desc">{{ $dossierBlock3Desc }}</div>
                                    </td>
                                </tr>
                            </table>
                        @else
                            <div class="photo-card-inner">
                                <div class="photo-card-pill">SESIÓN EN DIRECTO</div>
                                <div class="photo-card-title">{{ $dossierBlock3Title }}</div>
                                <div class="photo-card-desc">{{ $dossierBlock3Desc }}</div>
                            </div>
                        @endif
                    </td>
                </tr>
            </table>

            <!-- Bloque Cómo Trabajamos -->
            <div class="work-box">
                <div class="work-box-title">{{ $dossierWorkTitle }}</div>
                <ul class="work-box-list">
                    @if(!empty($dossierWorkItem1))
                        <li>
                            <span class="bullet">&bull;</span>
                            <strong>Montaje y prueba:</strong> {{ $dossierWorkItem1 }}
                        </li>
                    @endif
                    @if(!empty($dossierWorkItem2))
                        <li>
                            <span class="bullet">&bull;</span>
                            <strong>Personalización:</strong> {{ $dossierWorkItem2 }}
                        </li>
                    @endif
                    @if(!empty($dossierWorkItem3))
                        <li>
                            <span class="bullet">&bull;</span>
                            <strong>Desmontaje:</strong> {{ $dossierWorkItem3 }}
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        <!-- Pie de página -->
        <div class="page-footer-banner">
            <strong>{{ $companyName }}</strong>
            @if(!empty($companyPhone)) &bull; {{ $companyPhone }} @endif
            @if(!empty($companyPhone2)) &bull; {{ $companyPhone2 }} @endif
            @if(!empty($companyWebsite)) &bull; {{ $companyWebsite }} @endif
        </div>
    </div>

    <!-- ==========================================
         PÁGINA 3: OPCIONES Y PACKS
         ========================================== -->
    <div class="page page-last">
        <!-- Cabecera Azul Marino -->
        <div class="page-header-banner">
            <div class="page-header-sub">OPCIONES Y TARIFAS</div>
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
                            @if($hasMediumPack)
                                <div class="pack-badge">MÁS POPULAR</div>
                            @endif
                            <div class="pack-title">Medio</div>
                            <div class="pack-schedule">Hasta {{ $packMediumHours }} Horas de servicio</div>
                            <div class="pack-price">{{ number_format($packMediumPrice, 0, ',', '.') }}€</div>
                            <ul class="pack-features">
                                <li>Todo lo del pack Básico</li>
                                <li>Hasta {{ $packMediumHours }} horas completas</li>
                                <li>Iluminación avanzada + robotizadas</li>
                                <li>Máquina de humo en pista</li>
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
                    <div class="custom-selection-title">PRESUPUESTO SELECCIONADO (#PRE-{{ str_pad($quote->id, 5, '0', STR_PAD_LEFT) }})</div>
                    <div class="custom-selection-body">
                        <strong>Total Presupuestado: {{ number_format($quote->amount, 2, ',', '.') }} €</strong>
                        &bull; Incluye: 
                        @foreach($quote->items as $idx => $item)
                            {{ $item->service_name }}{{ $idx < count($quote->items) - 1 ? ', ' : '.' }}
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Tarjeta Informativa 1 (Horas extra) -->
            <div class="info-card">
                <div class="info-card-title">{{ $dossierExtraHoursTitle }}</div>
                <div class="info-card-body">
                    {{ $dossierExtraHoursDesc }} (Tarifa: <strong>{{ number_format($extraHourPrice, 0, ',', '.') }}€/hora adicional</strong>).
                </div>
            </div>

            <!-- Tarjeta Informativa 2 (Personalización musical) -->
            <div class="info-card">
                <div class="info-card-title">{{ $dossierMusicCustomTitle }}</div>
                <div class="info-card-body">
                    {{ $dossierMusicCustomDesc }}
                </div>
            </div>

            <!-- Tarjeta Reserva -->
            <div class="info-card">
                <div class="info-card-title">Reserva y Condiciones</div>
                <div class="info-card-body">
                    La reserva de fecha se formaliza por estricto orden de contratación. Señal estipulada: <strong>{{ number_format($quote->signal_amount, 2, ',', '.') }} €</strong> vía Bizum o Transferencia. Las condiciones y detalles se consolidan al aceptar la propuesta.
                </div>
            </div>

            <div class="tax-note">
                Precios finales, impuestos incluidos. Propuesta válida durante 15 días desde su fecha de emisión.
            </div>
        </div>

        <!-- Pie de página -->
        <div class="page-footer-banner">
            <strong>{{ $companyName }}</strong>
            @if(!empty($companyPhone)) &bull; {{ $companyPhone }} @endif
            @if(!empty($companyPhone2)) &bull; {{ $companyPhone2 }} @endif
            @if(!empty($companyWebsite)) &bull; {{ $companyWebsite }} @endif
        </div>
    </div>

</body>
</html>
