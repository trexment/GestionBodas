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

        /* ==================== PÁGINA 3: OPCIONES Y PROPUESTA SELECCIONADA ==================== */
        .quote-selection-card {
            background-color: #ffffff;
            border: 1.5px solid #0b1329;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        .quote-selection-header {
            background-color: #0b1329;
            color: #ffffff;
            padding: 6px 12px;
            font-size: 8.5pt;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .quote-items-table {
            width: 100%;
            border-collapse: collapse;
        }
        .quote-items-table th {
            background-color: #f8fafc;
            color: #475569;
            font-size: 7pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 5px 12px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }
        .quote-items-table td {
            padding: 6px 12px;
            font-size: 7.5pt;
            color: #1e293b;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .quote-items-table tr:last-child td {
            border-bottom: none;
        }
        .quote-item-title {
            font-weight: 700;
            color: #0f172a;
            font-size: 8pt;
        }
        .quote-item-desc {
            font-size: 6.5pt;
            color: #64748b;
            line-height: 1.2;
            margin-top: 1px;
        }
        .quote-item-discount {
            color: #16a34a;
            font-weight: 700;
        }

        /* BANNER DE TOTAL DESTACADO */
        .quote-total-banner {
            background-color: #0b1329;
            color: #ffffff;
            border-radius: 8px;
            padding: 8px 14px;
            margin-bottom: 10px;
            border: 1px solid #eab308;
        }
        .quote-total-table {
            width: 100%;
            border-collapse: collapse;
        }
        .quote-total-table td {
            vertical-align: middle;
        }
        .quote-total-label {
            font-size: 7pt;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #93c5fd;
            font-weight: 800;
        }
        .quote-total-amount {
            font-size: 21pt;
            font-weight: 900;
            color: #ffffff;
            line-height: 1.05;
        }
        .quote-total-amount span {
            color: #eab308;
            font-size: 15pt;
        }
        .quote-signal-box {
            font-size: 7.5pt;
            color: #cbd5e1;
            line-height: 1.35;
        }
        .quote-signal-box strong {
            color: #eab308;
            font-size: 8.5pt;
        }

        /* 3 Columnas de Packs Comparativos */
        .packs-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px;
            margin-bottom: 8px;
        }
        .pack-col {
            width: 33.33%;
            vertical-align: top;
        }
        .pack-card {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 6px;
            text-align: center;
        }
        .pack-card.highlighted {
            background-color: #0b1329;
            border: 1.5px solid #eab308;
            color: #ffffff;
        }
        .pack-badge {
            background-color: #eab308;
            color: #0f172a;
            font-size: 6pt;
            font-weight: 900;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 1.5px 5px;
            border-radius: 3px;
            display: inline-block;
            margin-bottom: 4px;
        }
        .pack-title {
            font-size: 10pt;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 1px;
        }
        .pack-card.highlighted .pack-title {
            color: #ffffff;
        }
        .pack-schedule {
            font-size: 6.5pt;
            color: #64748b;
            margin-bottom: 4px;
        }
        .pack-card.highlighted .pack-schedule {
            color: #94a3b8;
        }
        .pack-price {
            font-size: 14pt;
            font-weight: 900;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .pack-card.highlighted .pack-price {
            color: #eab308;
        }
        .pack-features {
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
            text-align: center;
            list-style: none;
            margin: 0;
        }
        .pack-card.highlighted .pack-features {
            border-top: 1px solid rgba(255, 255, 255, 0.15);
        }
        .pack-features li {
            font-size: 6.5pt;
            color: #475569;
            padding: 1.5px 0;
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
            border-left: 3px solid #d97706;
            border-radius: 5px;
            padding: 6px 10px;
            margin-bottom: 5px;
        }
        .info-card-title {
            font-size: 7.5pt;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 1px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-card-body {
            font-size: 6.5pt;
            color: #475569;
            line-height: 1.25;
        }

        .tax-note {
            font-size: 6.5pt;
            color: #94a3b8;
            margin-top: 2px;
            text-align: center;
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
        
        // Cargar fotos de montajes y equipamiento (1 a 4 fotos)
        $dossierPhotos = [];
        for ($i = 1; $i <= 4; $i++) {
            $img = \App\Models\Setting::get("dossier_photo{$i}_image", \App\Models\Setting::get("dossier_block{$i}_image"));
            $defaultTitle = $dDefaults["dossier_block{$i}_title"] ?? '';
            $defaultDesc = $dDefaults["dossier_block{$i}_desc"] ?? '';
            
            $title = \App\Models\Setting::get("dossier_photo{$i}_title", \App\Models\Setting::get("dossier_block{$i}_title", $defaultTitle));
            $desc = \App\Models\Setting::get("dossier_photo{$i}_desc", \App\Models\Setting::get("dossier_block{$i}_desc", $defaultDesc));

            $imgPath = null;
            if (!empty($img)) {
                $cleanImg = ltrim($img, '/');
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($cleanImg)) {
                    $imgPath = storage_path('app/public/' . $cleanImg);
                } elseif (file_exists(storage_path('app/public/' . $cleanImg))) {
                    $imgPath = storage_path('app/public/' . $cleanImg);
                } elseif (file_exists(public_path('storage/' . $cleanImg))) {
                    $imgPath = public_path('storage/' . $cleanImg);
                } elseif (file_exists(public_path($cleanImg))) {
                    $imgPath = public_path($cleanImg);
                }
            }

            if ($imgPath || !empty($title) || !empty($desc)) {
                $dossierPhotos[] = [
                    'index' => $i,
                    'path' => $imgPath,
                    'title' => $title ?: "Montaje {$i}",
                    'desc' => $desc ?: '',
                ];
            }
        }

        // Si no hay fotos ni textos guardados, cargar los 3 bloques predeterminados
        if (empty($dossierPhotos)) {
            for ($i = 1; $i <= 3; $i++) {
                $dossierPhotos[] = [
                    'index' => $i,
                    'path' => null,
                    'title' => $dDefaults["dossier_block{$i}_title"] ?? "Bloque {$i}",
                    'desc' => $dDefaults["dossier_block{$i}_desc"] ?? '',
                ];
            }
        }

        // Textos de Cómo trabajamos y condiciones
        $dossierWorkTitle = \App\Models\Setting::get('dossier_work_title', $dDefaults['dossier_work_title']);
        $dossierWorkItem1 = \App\Models\Setting::get('dossier_work_item1', $dDefaults['dossier_work_item1']);
        $dossierWorkItem2 = \App\Models\Setting::get('dossier_work_item2', $dDefaults['dossier_work_item2']);
        $dossierWorkItem3 = \App\Models\Setting::get('dossier_work_item3', $dDefaults['dossier_work_item3']);
        
        $dossierExtraHoursTitle = \App\Models\Setting::get('dossier_extra_hours_title', $dDefaults['dossier_extra_hours_title']);
        $dossierExtraHoursDesc = \App\Models\Setting::get('dossier_extra_hours_desc', $dDefaults['dossier_extra_hours_desc']);
        $dossierMusicCustomTitle = \App\Models\Setting::get('dossier_music_custom_title', $dDefaults['dossier_music_custom_title']);
        $dossierMusicCustomDesc = \App\Models\Setting::get('dossier_music_custom_desc', $dDefaults['dossier_music_custom_desc']);

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

            <!-- Mosaico Adaptable de Fotos de Montajes y Equipamiento -->
            @php
                $photoCount = count($dossierPhotos);
                $bgColors = ['#111c34', '#16223f', '#1e293b', '#0f172a'];
            @endphp

            @if($photoCount === 4)
                <!-- CUADRÍCULA 2x2 (4 FOTOS) -->
                <table class="photo-mosaic-table">
                    <tr>
                        @foreach([$dossierPhotos[0], $dossierPhotos[1]] as $k => $p)
                            <td class="photo-card" style="width: 50%; background-color: {{ $bgColors[$k] }};">
                                @if($p['path'])
                                    <table style="width: 100%; border-collapse: collapse;">
                                        <tr>
                                            <td style="width: 38%; padding: 5px; vertical-align: middle; text-align: center;">
                                                <img src="{{ $p['path'] }}" style="width: 100%; height: 75px; object-fit: cover; border-radius: 5px; display: block;">
                                            </td>
                                            <td style="width: 62%; padding: 5px 8px; vertical-align: middle; text-align: left; color: #ffffff;">
                                                <div class="photo-card-title" style="color: #eab308; font-size: 8.5pt;">{{ $p['title'] }}</div>
                                                @if(!empty($p['desc']))
                                                    <div class="photo-card-desc" style="font-size: 7pt;">{{ $p['desc'] }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                @else
                                    <div class="photo-card-inner" style="padding: 10px 8px;">
                                        <div class="photo-card-title" style="font-size: 8.5pt;">{{ $p['title'] }}</div>
                                        @if(!empty($p['desc']))
                                            <div class="photo-card-desc" style="font-size: 7pt;">{{ $p['desc'] }}</div>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach([$dossierPhotos[2], $dossierPhotos[3]] as $k => $p)
                            <td class="photo-card" style="width: 50%; background-color: {{ $bgColors[$k + 2] }};">
                                @if($p['path'])
                                    <table style="width: 100%; border-collapse: collapse;">
                                        <tr>
                                            <td style="width: 38%; padding: 5px; vertical-align: middle; text-align: center;">
                                                <img src="{{ $p['path'] }}" style="width: 100%; height: 75px; object-fit: cover; border-radius: 5px; display: block;">
                                            </td>
                                            <td style="width: 62%; padding: 5px 8px; vertical-align: middle; text-align: left; color: #ffffff;">
                                                <div class="photo-card-title" style="color: #eab308; font-size: 8.5pt;">{{ $p['title'] }}</div>
                                                @if(!empty($p['desc']))
                                                    <div class="photo-card-desc" style="font-size: 7pt;">{{ $p['desc'] }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                @else
                                    <div class="photo-card-inner" style="padding: 10px 8px;">
                                        <div class="photo-card-title" style="font-size: 8.5pt;">{{ $p['title'] }}</div>
                                        @if(!empty($p['desc']))
                                            <div class="photo-card-desc" style="font-size: 7pt;">{{ $p['desc'] }}</div>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                </table>
            @elseif($photoCount === 3)
                <!-- 1 PRINCIPAL + 2 SECUNDARIAS (3 FOTOS) -->
                <table class="photo-mosaic-table">
                    <tr>
                        <td colspan="2" class="photo-card" style="background-color: #111c34;">
                            @if($dossierPhotos[0]['path'])
                                <table style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="width: 36%; padding: 6px; vertical-align: middle; text-align: center;">
                                            <img src="{{ $dossierPhotos[0]['path'] }}" style="width: 100%; height: 95px; object-fit: cover; border-radius: 6px; display: block;">
                                        </td>
                                        <td style="width: 64%; padding: 8px 12px; vertical-align: middle; text-align: left; color: #ffffff;">
                                            <div class="photo-card-pill">MONTAJE DESTACADO</div>
                                            <div class="photo-card-title" style="color: #eab308;">{{ $dossierPhotos[0]['title'] }}</div>
                                            @if(!empty($dossierPhotos[0]['desc']))
                                                <div class="photo-card-desc">{{ $dossierPhotos[0]['desc'] }}</div>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            @else
                                <div class="photo-card-inner">
                                    <div class="photo-card-title">{{ $dossierPhotos[0]['title'] }}</div>
                                    @if(!empty($dossierPhotos[0]['desc']))
                                        <div class="photo-card-desc">{{ $dossierPhotos[0]['desc'] }}</div>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        @foreach([$dossierPhotos[1], $dossierPhotos[2]] as $k => $p)
                            <td class="photo-card" style="width: 50%; background-color: {{ $bgColors[$k + 1] }};">
                                @if($p['path'])
                                    <table style="width: 100%; border-collapse: collapse;">
                                        <tr>
                                            <td style="width: 38%; padding: 6px; vertical-align: middle; text-align: center;">
                                                <img src="{{ $p['path'] }}" style="width: 100%; height: 85px; object-fit: cover; border-radius: 6px; display: block;">
                                            </td>
                                            <td style="width: 62%; padding: 6px 8px; vertical-align: middle; text-align: left; color: #ffffff;">
                                                <div class="photo-card-title" style="color: #eab308;">{{ $p['title'] }}</div>
                                                @if(!empty($p['desc']))
                                                    <div class="photo-card-desc">{{ $p['desc'] }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                @else
                                    <div class="photo-card-inner">
                                        <div class="photo-card-title">{{ $p['title'] }}</div>
                                        @if(!empty($p['desc']))
                                            <div class="photo-card-desc">{{ $p['desc'] }}</div>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                </table>
            @elseif($photoCount === 2)
                <!-- 2 FOTOS EN COLUMNAS -->
                <table class="photo-mosaic-table">
                    <tr>
                        @foreach($dossierPhotos as $k => $p)
                            <td class="photo-card" style="width: 50%; background-color: {{ $bgColors[$k] }};">
                                @if($p['path'])
                                    <table style="width: 100%; border-collapse: collapse;">
                                        <tr>
                                            <td style="width: 42%; padding: 6px; vertical-align: middle; text-align: center;">
                                                <img src="{{ $p['path'] }}" style="width: 100%; height: 110px; object-fit: cover; border-radius: 6px; display: block;">
                                            </td>
                                            <td style="width: 58%; padding: 8px 10px; vertical-align: middle; text-align: left; color: #ffffff;">
                                                <div class="photo-card-title" style="color: #eab308;">{{ $p['title'] }}</div>
                                                @if(!empty($p['desc']))
                                                    <div class="photo-card-desc">{{ $p['desc'] }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                @else
                                    <div class="photo-card-inner">
                                        <div class="photo-card-title">{{ $p['title'] }}</div>
                                        @if(!empty($p['desc']))
                                            <div class="photo-card-desc">{{ $p['desc'] }}</div>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                </table>
            @elseif($photoCount === 1)
                <!-- 1 FOTO PANORÁMICA -->
                <table class="photo-mosaic-table">
                    <tr>
                        <td colspan="2" class="photo-card" style="background-color: #111c34;">
                            @if($dossierPhotos[0]['path'])
                                <table style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="width: 40%; padding: 8px; vertical-align: middle; text-align: center;">
                                            <img src="{{ $dossierPhotos[0]['path'] }}" style="width: 100%; height: 130px; object-fit: cover; border-radius: 6px; display: block;">
                                        </td>
                                        <td style="width: 60%; padding: 12px 16px; vertical-align: middle; text-align: left; color: #ffffff;">
                                            <div class="photo-card-pill">MONTAJE PROFESIONAL</div>
                                            <div class="photo-card-title" style="color: #eab308; font-size: 11pt;">{{ $dossierPhotos[0]['title'] }}</div>
                                            @if(!empty($dossierPhotos[0]['desc']))
                                                <div class="photo-card-desc" style="font-size: 8.5pt; margin-top: 4px;">{{ $dossierPhotos[0]['desc'] }}</div>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            @else
                                <div class="photo-card-inner">
                                    <div class="photo-card-title">{{ $dossierPhotos[0]['title'] }}</div>
                                    @if(!empty($dossierPhotos[0]['desc']))
                                        <div class="photo-card-desc">{{ $dossierPhotos[0]['desc'] }}</div>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                </table>
            @endif

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
         PÁGINA 3: PROPUESTA Y DETALLE DE SERVICIOS
         ========================================== -->
    <div class="page page-last">
        <!-- Cabecera Azul Marino -->
        <div class="page-header-banner">
            <div class="page-header-sub">PROPUESTA ECONÓMICA & SERVICIOS</div>
            <div class="page-header-title">Detalle de vuestro Presupuesto #PRE-{{ str_pad($quote->id, 5, '0', STR_PAD_LEFT) }}</div>
        </div>

        <div class="page-content">
            <!-- 1. BLOQUE PRINCIPAL: DESGLOSE DE SERVICIOS CONTRATADOS / SOLICITADOS -->
            <div class="quote-selection-card">
                <div class="quote-selection-header">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="color: #ffffff; font-weight: 800;">SERVICIOS SOLICITADOS PARA VUESTRO EVENTO</td>
                            <td style="text-align: right; color: #eab308; font-size: 7.5pt; font-weight: 700;">{{ $dateFormatted }}</td>
                        </tr>
                    </table>
                </div>

                <table class="quote-items-table">
                    <thead>
                        <tr>
                            <th style="width: 60%;">Concepto / Equipamiento</th>
                            <th style="width: 15%; text-align: center;">Horas / Ud</th>
                            <th style="width: 25%; text-align: right;">Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($quote->items as $item)
                            @php
                                $itemTitle = $item->service_name ?: ($item->concept ?: 'Servicio');
                                $itemDesc = $item->description ?: $item->custom_note;
                                $itemQty = (int)($item->quantity ?: 1);
                                $itemUnitPrice = (float)($item->price ?? $item->unit_price ?? 0);
                                $itemTotalPrice = (float)($item->total ?? $item->total_price ?? 0);
                                
                                if ($itemTotalPrice == 0 && $itemUnitPrice != 0) {
                                    $itemTotalPrice = $itemUnitPrice * $itemQty;
                                }
                                if ($itemTotalPrice == 0 && $itemUnitPrice == 0 && count($quote->items) === 1 && (float)$quote->amount > 0) {
                                    $itemTotalPrice = (float)$quote->amount;
                                    $itemUnitPrice = $itemTotalPrice / max(1, $itemQty);
                                }
                                
                                $isDiscount = $itemUnitPrice < 0 || $itemTotalPrice < 0;
                            @endphp
                            <tr>
                                <td>
                                    <div class="quote-item-title {{ $isDiscount ? 'quote-item-discount' : '' }}">
                                        @if($isDiscount) [DESCUENTO] @else &bull; @endif {{ $itemTitle }}
                                    </div>
                                    @if(!empty($itemDesc))
                                        <div class="quote-item-desc">{{ $itemDesc }}</div>
                                    @endif
                                </td>
                                <td style="text-align: center; color: #64748b; font-weight: 600;">
                                    {{ $itemQty }}
                                </td>
                                <td style="text-align: right; font-weight: 800; font-size: 8.5pt;" class="{{ $isDiscount ? 'quote-item-discount' : '' }}">
                                    @if($isDiscount)
                                        -{{ number_format(abs($itemTotalPrice), 2, ',', '.') }} €
                                    @elseif($itemTotalPrice == 0 && (str_contains(mb_strtolower($itemTitle), 'consultar') || str_contains(mb_strtolower($itemTitle), 'extra')))
                                        <span style="font-size: 7pt; color: #d97706; font-weight: 700;">A consultar</span>
                                    @else
                                        {{ number_format($itemTotalPrice, 2, ',', '.') }} €
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" style="text-align: center; color: #64748b; padding: 12px;">
                                    Propuesta base para el evento: {{ $event->name }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 2. BANNER DE TOTAL Y SEÑAL DESTACADOS EN GRANDE -->
            <div class="quote-total-banner">
                <table class="quote-total-table">
                    <tr>
                        <td style="width: 55%;" class="quote-signal-box">
                            <div style="font-size: 6.5pt; text-transform: uppercase; letter-spacing: 1px; color: #93c5fd; font-weight: 700; margin-bottom: 2px;">CONDICIONES DE PAGO</div>
                            <div>• Señal de Reserva: <strong>{{ number_format($quote->signal_amount, 2, ',', '.') }} €</strong> <span style="font-size: 6.5pt; opacity: 0.85;">(bloqueo de fecha vía Bizum/Transf.)</span></div>
                            <div style="margin-top: 2px;">• Restante: <strong>{{ number_format(max(0, $quote->amount - $quote->signal_amount), 2, ',', '.') }} €</strong> <span style="font-size: 6.5pt; opacity: 0.85;">(a liquidar al finalizar el evento)</span></div>
                        </td>
                        <td style="width: 45%; text-align: right;">
                            <div class="quote-total-label">TOTAL PRESUPUESTO</div>
                            <div class="quote-total-amount">{{ number_format($quote->amount, 2, ',', '.') }} <span>€</span></div>
                            <div style="font-size: 6pt; color: #94a3b8; margin-top: 1px;">Precios finales &bull; IVA / Impuestos incluidos</div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- 3. COMPARATIVA DE PACKS DE FIESTA / REFERENCIA RÁPIDA -->
            <div style="margin-bottom: 4px; font-size: 7pt; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">
                Catálogo de Packs DJ & Opciones de Fiesta:
            </div>
            <table class="packs-table">
                <tr>
                    <!-- PACK BÁSICO -->
                    <td class="pack-col">
                        <div class="pack-card {{ $hasBasicPack ? 'highlighted' : '' }}">
                            @if($hasBasicPack)
                                <div class="pack-badge">VUESTRA ELECCIÓN</div>
                            @endif
                            <div class="pack-title">Básico</div>
                            <div class="pack-schedule">{{ $packBasicHours }} Horas de servicio</div>
                            <div class="pack-price">{{ number_format($packBasicPrice, 0, ',', '.') }}€</div>
                            <ul class="pack-features">
                                <li>DJ durante {{ $packBasicHours }} horas</li>
                                <li>Equipo de sonido profesional</li>
                                <li>Iluminación de pista</li>
                                <li>Montaje y desmontaje</li>
                            </ul>
                        </div>
                    </td>

                    <!-- PACK MEDIO (RECOMENDADO) -->
                    <td class="pack-col">
                        <div class="pack-card {{ $hasMediumPack ? 'highlighted' : '' }}">
                            @if($hasMediumPack)
                                <div class="pack-badge">VUESTRA ELECCIÓN</div>
                            @else
                                <div class="pack-badge" style="background-color: #3b82f6; color: #ffffff;">MÁS POPULAR</div>
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
                                <div class="pack-badge">VUESTRA ELECCIÓN</div>
                            @endif
                            <div class="pack-title">Premium</div>
                            <div class="pack-schedule">Hasta {{ $packPremiumHours }} Horas de servicio</div>
                            <div class="pack-price">{{ number_format($packPremiumPrice, 0, ',', '.') }}€</div>
                            <ul class="pack-features">
                                <li>Todo lo del pack Medio</li>
                                <li>Efectos de humo y ambientación</li>
                                <li>Fuego frío / Efectos show</li>
                                <li>Montaje y desmontaje</li>
                            </ul>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- 4. BLOQUES DE INFORMACIÓN Y CONDICIONES -->
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 4px;">
                <tr>
                    <td style="width: 50%; padding-right: 4px; vertical-align: top;">
                        <div class="info-card">
                            <div class="info-card-title">{{ $dossierExtraHoursTitle }}</div>
                            <div class="info-card-body">
                                {{ $dossierExtraHoursDesc }} (Tarifa: <strong>{{ number_format($extraHourPrice, 0, ',', '.') }}€/h extra</strong>).
                            </div>
                        </div>
                    </td>
                    <td style="width: 50%; padding-left: 4px; vertical-align: top;">
                        <div class="info-card">
                            <div class="info-card-title">{{ $dossierMusicCustomTitle }}</div>
                            <div class="info-card-body">
                                {{ $dossierMusicCustomDesc }}
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="tax-note">
                Precios finales, impuestos incluidos &bull; Propuesta válida durante 15 días desde su fecha de emisión &bull; Formalización por estricto orden de reserva
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
