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
                    <div class="cover-badge-title">{{ $brandKey === 'javnx' ? 'JAVNX' : 'NÚÑEZ' }}</div>
                    <div class="cover-badge-sub">{{ $brandKey === 'javnx' ? 'DJ & EVENTS' : 'AND SON · DJ' }}</div>
                </div>
            @endif
        </div>

        <!-- Tarjeta flotante inferior -->
        <div class="cover-bottom-card">
            <div class="cover-subtitle-top">{{ mb_strtoupper($companyName) }} &bull; {{ mb_strtoupper($companySubtitle) }}</div>
            <div class="cover-main-title">{!! nl2br(e($dossierCoverTitle)) !!}</div>
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
                    <td colspan="2" class="photo-card" style="height: 135px; background-color: #111c34;">
                        @if($block1ImgPath)
                            <table style="width: 100%; height: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="width: 38%; padding: 6px; vertical-align: middle; text-align: center;">
                                        <img src="{{ $block1ImgPath }}" style="width: 100%; height: 115px; object-fit: cover; border-radius: 6px; display: block;">
                                    </td>
                                    <td style="width: 62%; padding: 10px 14px; vertical-align: middle; text-align: left; color: #ffffff;">
                                        <div class="photo-card-title" style="font-size: 10pt; color: #eab308; margin-bottom: 4px;">{{ $dossierBlock1Title }}</div>
                                        <div class="photo-card-desc" style="font-size: 8pt; color: #e2e8f0; line-height: 1.35;">{{ $dossierBlock1Desc }}</div>
                                    </td>
                                </tr>
                            </table>
                        @else
                            <div class="photo-card-inner">
                                <span class="photo-card-icon">🔊</span>
                                <div class="photo-card-title">{{ $dossierBlock1Title }}</div>
                                <div class="photo-card-desc">{{ $dossierBlock1Desc }}</div>
                            </div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="photo-card" style="width: 50%; height: 130px; background-color: #16223f;">
                        @if($block2ImgPath)
                            <table style="width: 100%; height: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="width: 40%; padding: 6px; vertical-align: middle; text-align: center;">
                                        <img src="{{ $block2ImgPath }}" style="width: 100%; height: 110px; object-fit: cover; border-radius: 6px; display: block;">
                                    </td>
                                    <td style="width: 60%; padding: 8px 10px; vertical-align: middle; text-align: left; color: #ffffff;">
                                        <div class="photo-card-title" style="font-size: 9pt; color: #eab308; margin-bottom: 3px;">{{ $dossierBlock2Title }}</div>
                                        <div class="photo-card-desc" style="font-size: 7.5pt; color: #cbd5e1; line-height: 1.3;">{{ $dossierBlock2Desc }}</div>
                                    </td>
                                </tr>
                            </table>
                        @else
                            <div class="photo-card-inner">
                                <span class="photo-card-icon">💡</span>
                                <div class="photo-card-title">{{ $dossierBlock2Title }}</div>
                                <div class="photo-card-desc">{{ $dossierBlock2Desc }}</div>
                            </div>
                        @endif
                    </td>
                    <td class="photo-card" style="width: 50%; height: 130px; background-color: #1e293b;">
                        @if($block3ImgPath)
                            <table style="width: 100%; height: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="width: 40%; padding: 6px; vertical-align: middle; text-align: center;">
                                        <img src="{{ $block3ImgPath }}" style="width: 100%; height: 110px; object-fit: cover; border-radius: 6px; display: block;">
                                    </td>
                                    <td style="width: 60%; padding: 8px 10px; vertical-align: middle; text-align: left; color: #ffffff;">
                                        <div class="photo-card-title" style="font-size: 9pt; color: #eab308; margin-bottom: 3px;">{{ $dossierBlock3Title }}</div>
                                        <div class="photo-card-desc" style="font-size: 7.5pt; color: #cbd5e1; line-height: 1.3;">{{ $dossierBlock3Desc }}</div>
                                    </td>
                                </tr>
                            </table>
                        @else
                            <div class="photo-card-inner">
                                <span class="photo-card-icon">🎧</span>
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
                            {{ $dossierWorkItem1 }}
                        </li>
                    @endif
                    @if(!empty($dossierWorkItem2))
                        <li>
                            <span class="bullet">&bull;</span>
                            {{ $dossierWorkItem2 }}
                        </li>
                    @endif
                    @if(!empty($dossierWorkItem3))
                        <li>
                            <span class="bullet">&bull;</span>
                            {{ $dossierWorkItem3 }}
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

            <!-- Tarjeta Informativa 1 (Horas extra) -->
            <div class="info-card">
                <div class="info-card-title">{{ $dossierExtraHoursTitle }}</div>
                <div class="info-card-body">
                    {{ $dossierExtraHoursDesc }} (Tarifa adicional: <strong>{{ number_format($extraHourPrice, 0, ',', '.') }}€/hora</strong>).
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
                <div class="info-card-title">Reserva de Fecha</div>
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
            <strong>{{ $companyName }}</strong>
            @if(!empty($companyPhone)) &bull; {{ $companyPhone }} @endif
            @if(!empty($companyPhone2)) &bull; {{ $companyPhone2 }} @endif
            @if(!empty($companyWebsite)) &bull; {{ $companyWebsite }} @endif
        </div>
    </div>

</body>
</html>
