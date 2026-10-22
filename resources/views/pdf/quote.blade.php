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
            padding-bottom: 35px;
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
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            width: 100%;
            background-color: #0b1329;
            color: #94a3b8;
            padding: 8px 35px;
            font-size: 7.5pt;
            text-align: center;
            box-sizing: border-box;
            z-index: 9999;
        }
        .page-footer-banner strong {
            color: #ffffff;
        }

        /* BANNER PANORÁMICO DE FIESTA / EFECTOS (PÁGINA 2) */
        .dossier-panoramic-card {
            margin-top: 10px;
            border-radius: 8px;
            overflow: hidden;
            background-color: #0b1329;
            border: 1px solid #1e293b;
        }
        .dossier-panoramic-img {
            width: 100%;
            height: 140px;
            display: block;
        }
        .dossier-panoramic-caption {
            background-color: #0b1329;
            padding: 7px 12px;
            border-top: 1px solid #1e293b;
        }
        .dossier-panoramic-title {
            font-size: 9pt;
            font-weight: 800;
            color: #facc15;
            letter-spacing: 0.5px;
        }
        .dossier-panoramic-sub {
            font-size: 7pt;
            color: #cbd5e1;
            margin-top: 2px;
            line-height: 1.25;
        }
        .dossier-panoramic-default {
            background-color: #111c34;
            padding: 12px 16px;
            border: 1px solid #1e293b;
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
            margin-bottom: 5px;
            border: 1px solid #eab308;
        }
        /* AVISO DESTACADO DE VALIDEZ Y CADUCIDAD DE OFERTA */
        .quote-validity-banner {
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            border-left: 4px solid #f59e0b;
            border-radius: 6px;
            padding: 4px 10px;
            margin-bottom: 6px;
        }
        .quote-validity-banner.expired {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-left: 4px solid #ef4444;
        }
        .quote-validity-table {
            width: 100%;
            border-collapse: collapse;
        }
        .quote-validity-title {
            font-size: 6.8pt;
            font-weight: 800;
            color: #92400e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .quote-validity-banner.expired .quote-validity-title {
            color: #991b1b;
        }
        .quote-validity-text {
            font-size: 6.2pt;
            color: #b45309;
            line-height: 1.25;
            margin-top: 1px;
        }
        .quote-validity-banner.expired .quote-validity-text {
            color: #b91c1c;
        }
        .quote-validity-badge {
            display: inline-block;
            background-color: #f59e0b;
            color: #ffffff;
            font-weight: 800;
            font-size: 6.5pt;
            padding: 2.5px 6px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        .quote-validity-banner.expired .quote-validity-badge {
            background-color: #ef4444;
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

        /* TABLA DE DATOS DE LA BODA (PÁGINA PRESUPUESTO) */
        .couple-meta-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #0b1329;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 10px;
        }
        .couple-meta-table {
            width: 100%;
            border-collapse: collapse;
        }
        .couple-meta-table td {
            padding: 2.5px 4px;
            font-size: 7.5pt;
        }
        .couple-meta-lbl {
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 6.5pt;
            width: 85px;
        }
        .couple-meta-val {
            font-weight: 700;
            color: #0f172a;
        }
        
        /* BANNER DE PAGO BIZUM / IBAN */
        .payment-methods-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-left: 4px solid #059669;
            border-radius: 6px;
            padding: 6px 12px;
            margin-bottom: 6px;
        }
        .payment-methods-title {
            font-size: 6.8pt;
            font-weight: 800;
            color: #065f46;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .payment-methods-table {
            width: 100%;
            border-collapse: collapse;
        }
        .payment-methods-table td {
            font-size: 6.8pt;
            vertical-align: top;
            padding: 2px 4px;
            color: #334155;
        }
    </style>
</head>
<body>

    @php
        $cleanPdf = fn(?string $str) => trim(preg_replace('/\s{2,}/', ' ', preg_replace('/[\x{1F000}-\x{1FFFF}\x{2300}-\x{23FF}\x{26A1}\x{2728}\x{FE0F}]/u', '', (string)$str)));
        $isSinglePage = $isSinglePage ?? false;
        $brandKey = $event->brand_clean ?? (\App\Models\Setting::getBrandInfo()['key']);
        $brand = \App\Models\Setting::getBrandInfo($brandKey);

        $companyName = $brand['name'];
        $companySubtitle = $brand['subtitle'];
        $companyPhone = $brand['phone'];
        $companyPhone2 = $brand['phone_2'];
        $companyWebsite = $brand['website'];
        $companyCif = \App\Models\Setting::get('company_cif', '78902362B');
        $companyIban = \App\Models\Setting::get('company_iban', 'ES39 3035 0241 13 2411043571');
        $companyAddress = \App\Models\Setting::get('company_address', 'Calle Principal s/n');
        $companyCity = \App\Models\Setting::get('company_city', 'Logroño');
        $companyBizum = $brand['phone'] ?: \App\Models\Setting::get('company_bizum', '622634790');
        $companyEmail = $brand['email'] ?: \App\Models\Setting::get('company_email', 'info@nunezandson.com');
        $logoPath = $brand['logo_path'];

        // Configuración dinámica del Dossier adaptada al Tipo de Evento y Marca
        $evType = $event->event_type_clean;
        $isWedding = $event->is_wedding;
        $isMagicBrand = ($event->brand_clean ?? 'nunez_and_son') === 'mago_leugim';
        $presets = \App\Services\DossierTemplateService::presets();
        $dDefaults = $isWedding
            ? \App\Services\DossierTemplateService::getDefaults()
            : ($presets['fiestas_empresa']['values'] ?? \App\Services\DossierTemplateService::getDefaults());

        $hasWeddingWords = function (?string $txt): bool {
            if (empty($txt)) return false;
            $low = mb_strtolower($txt);
            return str_contains($low, 'boda') || str_contains($low, 'novio') || str_contains($low, 'nupcial') || str_contains($low, 'enlace') || str_contains($low, 'pareja');
        };

        $getDossierText = function (string $key, ?string $customFallback = null) use ($isWedding, $dDefaults, $hasWeddingWords): string {
            $fallback = $customFallback ?? ($dDefaults[$key] ?? '');
            $stored = \App\Models\Setting::get($key, $fallback);
            if (!$isWedding && $hasWeddingWords($stored)) {
                return $fallback;
            }
            return (string)$stored;
        };

        $defaultCoverByEvent = $isMagicBrand
            ? 'Propuesta de Espectáculo de Magia y Animación'
            : match ($evType) {
                'boda' => 'Propuesta para vuestra boda',
                'empresa' => 'Propuesta para vuestro evento corporativo',
                'comunion' => 'Propuesta para vuestra comunión',
                'cumpleanos' => 'Propuesta para vuestra celebración',
                default => 'Propuesta para vuestro evento',
            };

        $dossierCoverTitle = $isMagicBrand
            ? $defaultCoverByEvent
            : $getDossierText('dossier_cover_title', $defaultCoverByEvent);
        $dossierPage2Subtitle = $isMagicBrand
            ? 'MAGIA PROFESIONAL, ILUSIONISMO Y ENTRETENIMIENTO'
            : $getDossierText('dossier_page2_subtitle');
        $dossierPage2Title = $isMagicBrand
            ? 'Un Espectáculo Inolvidable para Todos'
            : $getDossierText('dossier_page2_title');
        $dossierIntroText = $isMagicBrand
            ? "Con Mago Leugim viviréis un espectáculo de ilusionismo dinámico, participativo y sorprendente, diseñado a medida según las edades y el estilo de vuestra celebración (Infantil, Familiar/Mixto o Adultos).\n\nCuidamos cada detalle de la puesta en escena: sonido autónomo de alta nitidez, microfonía inalámbrica y la posibilidad de continuar la fiesta con sesión musical y animación después de la magia."
            : $getDossierText('dossier_intro_text');
        
        // Cargar fotos de montajes y equipamiento (1 a 4 fotos)
        $dossierPhotos = [];
        for ($i = 1; $i <= 4; $i++) {
            $img = \App\Models\Setting::get("dossier_photo{$i}_image", \App\Models\Setting::get("dossier_block{$i}_image"));
            $defaultTitle = $dDefaults["dossier_block{$i}_title"] ?? '';
            $defaultDesc = $dDefaults["dossier_block{$i}_desc"] ?? '';
            
            $rawTitle = \App\Models\Setting::get("dossier_photo{$i}_title", \App\Models\Setting::get("dossier_block{$i}_title", $defaultTitle));
            $rawDesc = \App\Models\Setting::get("dossier_photo{$i}_desc", \App\Models\Setting::get("dossier_block{$i}_desc", $defaultDesc));

            $title = (!$isWedding && $hasWeddingWords($rawTitle)) ? $defaultTitle : $rawTitle;
            $desc = (!$isWedding && $hasWeddingWords($rawDesc)) ? $defaultDesc : $rawDesc;

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
        $dossierWorkTitle = $getDossierText('dossier_work_title');
        $dossierWorkItem1 = $getDossierText('dossier_work_item1');
        $dossierWorkItem2 = $getDossierText('dossier_work_item2');
        $dossierWorkItem3 = $getDossierText('dossier_work_item3');
        
        $dossierExtraHoursTitle = $getDossierText('dossier_extra_hours_title');
        $dossierExtraHoursDesc = $getDossierText('dossier_extra_hours_desc');
        $dossierMusicCustomTitle = $getDossierText('dossier_music_custom_title');
        $dossierMusicCustomDesc = $getDossierText('dossier_music_custom_desc');

        // Pack settings (específicos por tipo de evento con fallback al general)
        $getEvPrice = function (string $key, float $default) use ($evType): float {
            $val = \App\Models\Setting::get("{$key}_{$evType}");
            if ($val !== null && $val !== '') {
                return (float)$val;
            }
            return (float)\App\Models\Setting::get($key, $default);
        };

        $packBasicName = $isMagicBrand ? 'Magia Infantil' : \App\Models\Setting::get('pack_basic_name', 'Pack Esencial');
        $packBasicPrice = $isMagicBrand ? $getEvPrice('price_magic_kids', 250) : $getEvPrice('pack_basic_price', 600);
        $packBasicHours = (int)\App\Models\Setting::get("pack_basic_hours_{$evType}", \App\Models\Setting::get('pack_basic_hours', 4));
        
        $packMediumName = $isMagicBrand ? 'Magia Mixta / Familiar' : \App\Models\Setting::get('pack_medium_name', 'Pack Signature');
        $packMediumPrice = $isMagicBrand ? $getEvPrice('price_magic_mixed', 300) : $getEvPrice('pack_medium_price', 950);
        $packMediumHours = (int)\App\Models\Setting::get("pack_medium_hours_{$evType}", \App\Models\Setting::get('pack_medium_hours', 5));

        $packPremiumName = $isMagicBrand ? 'Magia para Adultos' : \App\Models\Setting::get('pack_premium_name', 'Pack Deluxe Show');
        $packPremiumPrice = $isMagicBrand ? $getEvPrice('price_magic_adults', 300) : $getEvPrice('pack_premium_price', 1350);
        $packPremiumHours = (int)\App\Models\Setting::get("pack_premium_hours_{$evType}", \App\Models\Setting::get('pack_premium_hours', 6));

        $magicCloseupPrice = $getEvPrice('price_magic_closeup', 220);
        $djCustomPrice = $getEvPrice('price_dj_custom', 80);
        $extraHourPrice = $getEvPrice('price_extra_hours', 120);

        // Formatted Date (del evento)
        $carbonDate = $event->event_date ? \Carbon\Carbon::parse($event->event_date)->locale('es') : null;
        $dateFormatted = $carbonDate ? ucfirst($carbonDate->isoFormat('dddd D [de] MMMM [de] YYYY')) : 'Fecha a convenir';

        // Fechas de Emisión y Caducidad de la Propuesta
        $validityDays = $quote->validity_days;
        $issueDate = $quote->created_at ? \Carbon\Carbon::parse($quote->created_at)->locale('es') : now()->locale('es');
        $expiresAt = $quote->expires_at->locale('es');
        $isQuoteExpired = $quote->is_expired;
        
        $issueDateFormatted = $issueDate->isoFormat('D [de] MMMM [de] YYYY');
        $expiresDateFormatted = $expiresAt->isoFormat('D [de] MMMM [de] YYYY');
        $issueDateShort = $issueDate->format('d/m/Y');
        $expiresDateShort = $expiresAt->format('d/m/Y');

        // Schedule string
        $scheduleStr = 'Horario personalizado a convenir';
        if ($event->dance_start_time) {
            $scheduleStr = $event->dance_schedule_label;
        } elseif ($event->effective_start_time !== 'Por determinar') {
            $scheduleStr = 'A partir de las ' . $event->effective_start_time . ' h';
        }

        // Check which pack matches the current quote
        $quoteAmount = (float)$quote->amount;
        $hasBasicPack = false;
        $hasMediumPack = false;
        $hasPremiumPack = false;
        $isCustomQuote = false;

        foreach ($quote->items as $qItem) {
            $sName = mb_strtolower($qItem->service_name ?: $qItem->concept ?: '');
            if (str_contains($sName, 'básico') || str_contains($sName, 'basico') || str_contains($sName, 'esencial') || str_contains($sName, 'clásico') || str_contains($sName, 'clasico')) {
                $hasBasicPack = true;
            } elseif (str_contains($sName, 'medio') || str_contains($sName, 'signature') || str_contains($sName, 'recomendado')) {
                $hasMediumPack = true;
            } elseif (str_contains($sName, 'premium') || str_contains($sName, 'deluxe') || str_contains($sName, 'show') || str_contains($sName, 'festival')) {
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

    <!-- PIE DE PÁGINA FIJADO EN EL BORDE INFERIOR DE TODAS LAS PÁGINAS A4 -->
    <div class="page-footer-banner">
        <strong>{{ $companyName }}</strong>
        @if(!empty($companyPhone)) &bull; {{ $companyPhone }} @endif
        @if(!empty($companyPhone2)) &bull; {{ $companyPhone2 }} @endif
        @if(!empty($companyWebsite)) &bull; {{ $companyWebsite }} @endif
    </div>

    @if(!($isSinglePage ?? false))
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
                <tr>
                    <td class="cover-meta-label">VALIDEZ</td>
                    <td class="cover-meta-value" style="color: #fde047; font-weight: 800;">
                        Hasta el {{ $expiresDateFormatted }} ({{ $validityDays }} días)
                    </td>
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
                $defaultBadges = ['MONTAJE DE SONIDO', 'ILUMINACIÓN & EFECTOS', 'CABINA DJ PROFESIONAL', 'MONTAJE ESPECIAL / FIESTA'];
            @endphp

            @if($photoCount === 4)
                <!-- CUADRÍCULA 2x2 (4 FOTOS DESTACADAS) -->
                <table class="photo-mosaic-table">
                    <tr>
                        @foreach([$dossierPhotos[0], $dossierPhotos[1]] as $k => $p)
                            <td class="photo-card" style="width: 50%; background-color: {{ $bgColors[$k] }};">
                                @if($p['path'])
                                    <table style="width: 100%; border-collapse: collapse;">
                                        <tr>
                                            <td style="width: 44%; padding: 6px; vertical-align: middle; text-align: center;">
                                                <img src="{{ $p['path'] }}" style="width: 100%; height: 110px; object-fit: cover; border-radius: 6px; display: block;">
                                            </td>
                                            <td style="width: 56%; padding: 8px 10px; vertical-align: middle; text-align: left; color: #ffffff;">
                                                <div class="photo-card-pill">{{ $defaultBadges[$k] }}</div>
                                                <div class="photo-card-title" style="color: #facc15; font-size: 9.5pt; line-height: 1.25;">{{ $p['title'] }}</div>
                                                @if(!empty($p['desc']))
                                                    <div class="photo-card-desc" style="font-size: 7.5pt; margin-top: 3px;">{{ $p['desc'] }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                @else
                                    <div class="photo-card-inner" style="padding: 16px 12px;">
                                        <div class="photo-card-pill">{{ $defaultBadges[$k] }}</div>
                                        <div class="photo-card-title" style="font-size: 9.5pt; color: #facc15;">{{ $p['title'] }}</div>
                                        @if(!empty($p['desc']))
                                            <div class="photo-card-desc" style="font-size: 7.5pt; margin-top: 3px;">{{ $p['desc'] }}</div>
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
                                            <td style="width: 44%; padding: 6px; vertical-align: middle; text-align: center;">
                                                <img src="{{ $p['path'] }}" style="width: 100%; height: 110px; object-fit: cover; border-radius: 6px; display: block;">
                                            </td>
                                            <td style="width: 56%; padding: 8px 10px; vertical-align: middle; text-align: left; color: #ffffff;">
                                                <div class="photo-card-pill">{{ $defaultBadges[$k + 2] }}</div>
                                                <div class="photo-card-title" style="color: #facc15; font-size: 9.5pt; line-height: 1.25;">{{ $p['title'] }}</div>
                                                @if(!empty($p['desc']))
                                                    <div class="photo-card-desc" style="font-size: 7.5pt; margin-top: 3px;">{{ $p['desc'] }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                @else
                                    <div class="photo-card-inner" style="padding: 16px 12px;">
                                        <div class="photo-card-pill">{{ $defaultBadges[$k + 2] }}</div>
                                        <div class="photo-card-title" style="font-size: 9.5pt; color: #facc15;">{{ $p['title'] }}</div>
                                        @if(!empty($p['desc']))
                                            <div class="photo-card-desc" style="font-size: 7.5pt; margin-top: 3px;">{{ $p['desc'] }}</div>
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
                                        <td style="width: 38%; padding: 8px; vertical-align: middle; text-align: center;">
                                            <img src="{{ $dossierPhotos[0]['path'] }}" style="width: 100%; height: 125px; object-fit: cover; border-radius: 6px; display: block;">
                                        </td>
                                        <td style="width: 62%; padding: 10px 14px; vertical-align: middle; text-align: left; color: #ffffff;">
                                            <div class="photo-card-pill">MONTAJE DESTACADO</div>
                                            <div class="photo-card-title" style="color: #facc15; font-size: 10.5pt;">{{ $dossierPhotos[0]['title'] }}</div>
                                            @if(!empty($dossierPhotos[0]['desc']))
                                                <div class="photo-card-desc" style="font-size: 8pt; margin-top: 4px;">{{ $dossierPhotos[0]['desc'] }}</div>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            @else
                                <div class="photo-card-inner" style="padding: 18px 14px;">
                                    <div class="photo-card-pill">MONTAJE DESTACADO</div>
                                    <div class="photo-card-title" style="font-size: 10.5pt; color: #facc15;">{{ $dossierPhotos[0]['title'] }}</div>
                                    @if(!empty($dossierPhotos[0]['desc']))
                                        <div class="photo-card-desc" style="font-size: 8pt; margin-top: 4px;">{{ $dossierPhotos[0]['desc'] }}</div>
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
                                            <td style="width: 44%; padding: 6px; vertical-align: middle; text-align: center;">
                                                <img src="{{ $p['path'] }}" style="width: 100%; height: 105px; object-fit: cover; border-radius: 6px; display: block;">
                                            </td>
                                            <td style="width: 56%; padding: 8px 10px; vertical-align: middle; text-align: left; color: #ffffff;">
                                                <div class="photo-card-title" style="color: #facc15; font-size: 9pt;">{{ $p['title'] }}</div>
                                                @if(!empty($p['desc']))
                                                    <div class="photo-card-desc" style="font-size: 7.5pt; margin-top: 2px;">{{ $p['desc'] }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                @else
                                    <div class="photo-card-inner" style="padding: 16px 10px;">
                                        <div class="photo-card-title" style="font-size: 9pt; color: #facc15;">{{ $p['title'] }}</div>
                                        @if(!empty($p['desc']))
                                            <div class="photo-card-desc" style="font-size: 7.5pt; margin-top: 2px;">{{ $p['desc'] }}</div>
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
                                            <td style="width: 45%; padding: 8px; vertical-align: middle; text-align: center;">
                                                <img src="{{ $p['path'] }}" style="width: 100%; height: 130px; object-fit: cover; border-radius: 6px; display: block;">
                                            </td>
                                            <td style="width: 55%; padding: 10px 12px; vertical-align: middle; text-align: left; color: #ffffff;">
                                                <div class="photo-card-pill">{{ $defaultBadges[$k] }}</div>
                                                <div class="photo-card-title" style="color: #facc15; font-size: 10pt;">{{ $p['title'] }}</div>
                                                @if(!empty($p['desc']))
                                                    <div class="photo-card-desc" style="font-size: 8pt; margin-top: 3px;">{{ $p['desc'] }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                @else
                                    <div class="photo-card-inner" style="padding: 20px 12px;">
                                        <div class="photo-card-pill">{{ $defaultBadges[$k] }}</div>
                                        <div class="photo-card-title" style="font-size: 10pt; color: #facc15;">{{ $p['title'] }}</div>
                                        @if(!empty($p['desc']))
                                            <div class="photo-card-desc" style="font-size: 8pt; margin-top: 3px;">{{ $p['desc'] }}</div>
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
                                        <td style="width: 42%; padding: 10px; vertical-align: middle; text-align: center;">
                                            <img src="{{ $dossierPhotos[0]['path'] }}" style="width: 100%; height: 155px; object-fit: cover; border-radius: 6px; display: block;">
                                        </td>
                                        <td style="width: 58%; padding: 14px 18px; vertical-align: middle; text-align: left; color: #ffffff;">
                                            <div class="photo-card-pill">MONTAJE PROFESIONAL</div>
                                            <div class="photo-card-title" style="color: #facc15; font-size: 12pt;">{{ $dossierPhotos[0]['title'] }}</div>
                                            @if(!empty($dossierPhotos[0]['desc']))
                                                <div class="photo-card-desc" style="font-size: 8.5pt; margin-top: 6px; line-height: 1.4;">{{ $dossierPhotos[0]['desc'] }}</div>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            @else
                                <div class="photo-card-inner" style="padding: 24px 18px;">
                                    <div class="photo-card-pill">MONTAJE PROFESIONAL</div>
                                    <div class="photo-card-title" style="font-size: 12pt; color: #facc15;">{{ $dossierPhotos[0]['title'] }}</div>
                                    @if(!empty($dossierPhotos[0]['desc']))
                                        <div class="photo-card-desc" style="font-size: 8.5pt; margin-top: 6px;">{{ $dossierPhotos[0]['desc'] }}</div>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                </table>
            @endif

            <!-- PROCESO CÓMO TRABAJAMOS EN 3 PASOS -->
            <div style="margin-top: 14px; margin-bottom: 12px;">
                <div style="font-size: 8.5pt; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
                    {{ $dossierWorkTitle }}
                </div>
                <table style="width: 100%; border-collapse: separate; border-spacing: 8px 0;">
                    <tr>
                        <td style="width: 33.3%; vertical-align: top; background-color: #f8fafc; border: 1px solid #e2e8f0; border-top: 3px solid #d97706; border-radius: 6px; padding: 10px 12px;">
                            <div style="font-size: 6.5pt; font-weight: 800; color: #d97706; text-transform: uppercase; letter-spacing: 1px;">PASO 1</div>
                            <div style="font-size: 8.5pt; font-weight: 800; color: #0f172a; margin: 2px 0 4px 0;">Montaje y prueba</div>
                            <div style="font-size: 7pt; color: #475569; line-height: 1.35;">{{ $dossierWorkItem1 }}</div>
                        </td>
                        <td style="width: 33.3%; vertical-align: top; background-color: #f8fafc; border: 1px solid #e2e8f0; border-top: 3px solid #3b82f6; border-radius: 6px; padding: 10px 12px;">
                            <div style="font-size: 6.5pt; font-weight: 800; color: #3b82f6; text-transform: uppercase; letter-spacing: 1px;">PASO 2</div>
                            <div style="font-size: 8.5pt; font-weight: 800; color: #0f172a; margin: 2px 0 4px 0;">Personalización</div>
                            <div style="font-size: 7pt; color: #475569; line-height: 1.35;">{{ $dossierWorkItem2 }}</div>
                        </td>
                        <td style="width: 33.3%; vertical-align: top; background-color: #f8fafc; border: 1px solid #e2e8f0; border-top: 3px solid #10b981; border-radius: 6px; padding: 10px 12px;">
                            <div style="font-size: 6.5pt; font-weight: 800; color: #10b981; text-transform: uppercase; letter-spacing: 1px;">PASO 3</div>
                            <div style="font-size: 8.5pt; font-weight: 800; color: #0f172a; margin: 2px 0 4px 0;">Desmontaje</div>
                            <div style="font-size: 7pt; color: #475569; line-height: 1.35;">{{ $dossierWorkItem3 }}</div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- COMPROMISO & GARANTÍAS DE CALIDAD -->
            <div style="background-color: #0b1329; border: 1px solid #1e293b; border-radius: 8px; padding: 10px 14px; color: #ffffff; margin-top: 10px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 33.3%; vertical-align: middle; text-align: center; border-right: 1px solid #1e293b; padding: 0 10px;">
                            <div style="display: inline-block; background-color: rgba(234, 179, 8, 0.2); border: 1px solid #eab308; color: #eab308; font-size: 6pt; font-weight: 800; padding: 1.5px 6px; border-radius: 3px; margin-bottom: 3px; letter-spacing: 0.8px;">SONIDO PRO</div>
                            <div style="font-size: 8pt; font-weight: 800; color: #eab308;">Equipos 100% Propios</div>
                            <div style="font-size: 6.5pt; color: #94a3b8; margin-top: 2px; line-height: 1.25;">Sonido e iluminación profesional revisados antes de cada evento.</div>
                        </td>
                        <td style="width: 33.3%; vertical-align: middle; text-align: center; border-right: 1px solid #1e293b; padding: 0 10px;">
                            <div style="display: inline-block; background-color: rgba(59, 130, 246, 0.25); border: 1px solid #60a5fa; color: #93c5fd; font-size: 6pt; font-weight: 800; padding: 1.5px 6px; border-radius: 3px; margin-bottom: 3px; letter-spacing: 0.8px;">EN DIRECTO</div>
                            <div style="font-size: 8pt; font-weight: 800; color: #eab308;">DJ Profesional Asignado</div>
                            <div style="font-size: 6.5pt; color: #94a3b8; margin-top: 2px; line-height: 1.25;">Mezclas en directo, animación cercana y lectura de pista.</div>
                        </td>
                        <td style="width: 33.3%; vertical-align: middle; text-align: center; padding: 0 10px;">
                            <div style="display: inline-block; background-color: rgba(16, 185, 129, 0.25); border: 1px solid #34d399; color: #6ee7b7; font-size: 6pt; font-weight: 800; padding: 1.5px 6px; border-radius: 3px; margin-bottom: 3px; letter-spacing: 0.8px;">APP ONLINE</div>
                            <div style="font-size: 8pt; font-weight: 800; color: #eab308;">Panel Musical Online</div>
                            <div style="font-size: 6.5pt; color: #94a3b8; margin-top: 2px; line-height: 1.25;">Pedid vuestras canciones favoritas y lista negra desde el móvil.</div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- BANNER PANORÁMICO DE FIESTA / EFECTOS / PISTA DE BAILE -->
            @php
                $bannerImg = \App\Models\Setting::get('dossier_banner_image');
                $bannerTitle = \App\Models\Setting::get('dossier_banner_title', 'Fiesta en Directo & Iluminación Espectacular');
                $bannerSubtitle = \App\Models\Setting::get('dossier_banner_subtitle', 'Pista de baile siempre activa con efectos visuales, sonido de alta fidelidad y máxima energía.');
                
                $bannerImgPath = null;
                if (!empty($bannerImg)) {
                    $cleanBanner = ltrim($bannerImg, '/');
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($cleanBanner)) {
                        $bannerImgPath = storage_path('app/public/' . $cleanBanner);
                    } elseif (file_exists(storage_path('app/public/' . $cleanBanner))) {
                        $bannerImgPath = storage_path('app/public/' . $cleanBanner);
                    } elseif (file_exists(public_path('storage/' . $cleanBanner))) {
                        $bannerImgPath = public_path('storage/' . $cleanBanner);
                    } elseif (file_exists(public_path($cleanBanner))) {
                        $bannerImgPath = public_path($cleanBanner);
                    }
                }
            @endphp

            @if($bannerImgPath)
                <div class="dossier-panoramic-card">
                    <img src="{{ $bannerImgPath }}" class="dossier-panoramic-img">
                    @if(!empty($bannerTitle) || !empty($bannerSubtitle))
                        <div class="dossier-panoramic-caption">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="vertical-align: middle;">
                                        @if(!empty($bannerTitle))
                                            <div class="dossier-panoramic-title">{{ $bannerTitle }}</div>
                                        @endif
                                        @if(!empty($bannerSubtitle))
                                            <div class="dossier-panoramic-sub">{{ $bannerSubtitle }}</div>
                                        @endif
                                    </td>
                                    <td style="width: 75px; text-align: right; vertical-align: middle;">
                                        <span class="photo-card-pill" style="margin: 0; font-size: 6.5pt;">EN DIRECTO</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    @endif
                </div>
            @else
                <div class="dossier-panoramic-card dossier-panoramic-default">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="vertical-align: middle;">
                                <div class="photo-card-pill" style="margin-bottom: 4px; font-size: 6.5pt;">EXPERIENCIA & ENERGÍA EN DIRECTO</div>
                                <div style="font-size: 10pt; font-weight: 800; color: #ffffff; line-height: 1.2;">{{ $bannerTitle }}</div>
                                <div style="font-size: 7pt; color: #cbd5e1; margin-top: 3px; line-height: 1.35;">{{ $bannerSubtitle }}</div>
                            </td>
                            <td style="width: 110px; text-align: right; vertical-align: middle; border-left: 1px solid rgba(255,255,255,0.12); padding-left: 14px;">
                                <div style="font-size: 14pt; line-height: 1; color: #eab308; font-weight: 900;">&#9733; &#9835; &#9733;</div>
                                <div style="font-size: 6.5pt; color: #eab308; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; margin-top: 4px;">Pista Llena</div>
                            </td>
                        </tr>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- ==========================================
         PÁGINA 3: CATÁLOGO DE PACKS Y PRODUCCIÓN TÉCNICA
         ========================================== -->
    <div class="page">
        <!-- Cabecera Azul Marino -->
        <div class="page-header-banner">
            <div class="page-header-sub">{{ $isMagicBrand ? 'MODALIDADES DE ESPECTÁCULO Y FIESTA' : 'PRODUCCIÓN Y OPCIONES DE SERVICIO' }} &bull; {{ mb_strtoupper($companyName) }}</div>
            <div class="page-header-title">{{ $isMagicBrand ? 'Espectáculos de Magia y Música post-Show' : 'Packs DJ y Coberturas para Vuestro Evento' }}</div>
        </div>

        <div class="page-content">
            <p class="intro-lead" style="margin-bottom: 12px;">
                {{ $isMagicBrand ? 'Adaptamos el formato del espectáculo de magia al público de vuestro evento (infantil, familiar o adultos) y os ofrecemos la posibilidad de continuar la fiesta con sesión musical, iluminación y animación.' : 'Diseñamos la experiencia musical y técnica a medida para cada momento de vuestra celebración. A continuación os presentamos nuestras configuraciones más demandadas y servicios de ambientación profesional para que conozcáis todas las opciones disponibles.' }}
            </p>

            <!-- 1. COMPARATIVA DE PACKS DE FIESTA O MAGIA -->
            <div style="margin-bottom: 6px; font-size: 7.5pt; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">
                {{ $isMagicBrand ? 'Modalidades de Espectáculo de Magia (Por Bloques):' : 'Configuraciones DJ para Barra Libre y Fiesta:' }}
            </div>
            <table class="packs-table">
                <tr>
                    <!-- PACK BÁSICO / ESENCIAL / MAGIA INFANTIL -->
                    <td class="pack-col">
                        <div class="pack-card">
                            <div class="pack-badge" style="background-color: #64748b; color: #ffffff;">{{ $isMagicBrand ? 'BLOQUE INFANTIL' : 'OPCIÓN 1' }}</div>
                            <div class="pack-title">{{ $packBasicName }}</div>
                            <div class="pack-schedule">{{ $isMagicBrand ? 'Público Infantil y Cumpleaños' : $packBasicHours . ' Horas de servicio DJ' }}</div>
                            <div class="pack-price">{{ number_format($packBasicPrice, 0, ',', '.') }}€</div>
                            @if($isMagicBrand)
                            <ul class="pack-features">
                                <li>Show 100% visual, cómico y dinámico</li>
                                <li>Participación constante de los niños</li>
                                <li>Protagonismo especial del homenajeado/a</li>
                                <li>Equipo de sonido y microfonía incluido</li>
                            </ul>
                            @else
                            <ul class="pack-features">
                                <li>{{ $packBasicHours }}h de sesión DJ profesional</li>
                                <li>Sonido estéreo alta definición</li>
                                <li>Iluminación rítmica LED de pista</li>
                                <li>Microfonía inalámbrica</li>
                            </ul>
                            @endif
                        </div>
                    </td>

                    <!-- PACK MEDIO / SIGNATURE / MAGIA MIXTA -->
                    <td class="pack-col">
                        <div class="pack-card">
                            <div class="pack-badge" style="background-color: #3b82f6; color: #ffffff;">{{ $isMagicBrand ? 'BLOQUE MIXTO &bull; RECOMENDADO' : 'OPCIÓN 2 &bull; RECOMENDADO' }}</div>
                            <div class="pack-title">{{ $packMediumName }}</div>
                            <div class="pack-schedule">{{ $isMagicBrand ? 'Infantil + Adultos (Familiar)' : $packMediumHours . ' Horas de servicio DJ' }}</div>
                            <div class="pack-price">{{ number_format($packMediumPrice, 0, ',', '.') }}€</div>
                            @if($isMagicBrand)
                            <ul class="pack-features">
                                <li>Diseñado para niños y adultos a la vez</li>
                                <li>Humor blanco, visual e ilusiones de impacto</li>
                                <li>Ideal comuniones, bodas y fiestas familiares</li>
                                <li>Sonido profesional y puesta en escena</li>
                            </ul>
                            @else
                            <ul class="pack-features">
                                <li>{{ $packMediumHours }}h de sesión DJ profesional</li>
                                <li>Sonido reforzado con subgraves</li>
                                <li>Cabina DJ retroiluminada</li>
                                <li>Puente/barras LED sincronizadas</li>
                            </ul>
                            @endif
                        </div>
                    </td>

                    <!-- PACK PREMIUM / DELUXE SHOW / MAGIA ADULTOS -->
                    <td class="pack-col">
                        <div class="pack-card">
                            <div class="pack-badge" style="background-color: #f59e0b; color: #ffffff;">{{ $isMagicBrand ? 'BLOQUE ADULTOS' : 'OPCIÓN 3 &bull; SHOW COMPLETO' }}</div>
                            <div class="pack-title">{{ $packPremiumName }}</div>
                            <div class="pack-schedule">{{ $isMagicBrand ? 'Público Adulto y Eventos' : 'Hasta ' . $packPremiumHours . ' Horas de cobertura' }}</div>
                            <div class="pack-price">{{ number_format($packPremiumPrice, 0, ',', '.') }}€</div>
                            @if($isMagicBrand)
                            <ul class="pack-features">
                                <li>Ilusionismo de alto impacto y mentalismo</li>
                                <li>Humor elegante e interacción con invitados</li>
                                <li>Ideal cenas, fiestas privadas y empresas</li>
                                <li>Sonido autónomo de alta fidelidad</li>
                            </ul>
                            @else
                            <ul class="pack-features">
                                <li>Hasta {{ $packPremiumHours }}h cobertura completa</li>
                                <li>Sonido gran formato + subgraves</li>
                                <li>Show iluminación sincronizada</li>
                                <li>Efecto humo denso / chispas frías</li>
                            </ul>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>

            <!-- 2. COBERTURAS ADICIONALES PARA CADA MOMENTO -->
            <div style="margin-top: 14px; margin-bottom: 6px; font-size: 7.5pt; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">
                {{ $isMagicBrand ? 'Complementos y Música después de la Magia:' : 'Sonorización y Cobertura Integral del Evento:' }}
            </div>
            <table style="width: 100%; border-collapse: separate; border-spacing: 8px 6px;">
                <tr>
                    <td style="width: 50%; vertical-align: top; background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #3b82f6; border-radius: 6px; padding: 8px 10px;">
                        <div style="font-size: 8pt; font-weight: 800; color: #1e3a8a; margin-bottom: 2px;">&bull; {{ $isMagicBrand ? 'Música y Fiesta después de la Magia' : ($event->is_wedding ? 'Ceremonia Civil & Cóctel' : 'Sonorización de Acto & Recepción') }}</div>
                        <div style="font-size: 6.8pt; color: #475569; line-height: 1.3;">
                            @if($isMagicBrand)
                                Continuación del evento tras el espectáculo con sesión musical / DJ por horas, equipo de sonido profesional e iluminación rítmica LED (Desde <strong>{{ number_format($djCustomPrice, 0, ',', '.') }}€/hora</strong>).
                            @else
                                {{ $event->is_wedding ? 'Equipos independientes a batería o corriente, microfonía inalámbrica para oficiante y lecturas, y música de ambientación elegante y fluida.' : 'Equipos de sonido profesionales independientes, microfonía inalámbrica para intervenciones o actuaciones y música de ambientación adaptada.' }}
                            @endif
                        </div>
                    </td>
                    <td style="width: 50%; vertical-align: top; background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #10b981; border-radius: 6px; padding: 8px 10px;">
                        <div style="font-size: 8pt; font-weight: 800; color: #065f46; margin-bottom: 2px;">&bull; {{ $isMagicBrand ? 'Magia de Cerca (Close-Up en Cóctel / Mesas)' : ($event->is_wedding ? 'Banquete & Momentos Especiales' : 'Comida / Cena & Momentos Clave') }}</div>
                        <div style="font-size: 6.8pt; color: #475569; line-height: 1.3;">
                            @if($isMagicBrand)
                                Magia itinerante en pequeños grupos a pocos centímetros de los ojos de los invitados durante la recepción o aperitivo (Tarifa base: <strong>{{ number_format($magicCloseupPrice, 0, ',', '.') }}€</strong>).
                            @else
                                {{ $event->is_wedding ? 'Coordinación precisa de temas para entrada de los novios, corte de tarta, regalos emotivos y sorpresas sin silencios incómodos.' : 'Coordinación precisa de temas musicales, entregas de premios o detalles, actuaciones y momentos destacados sin silencios incómodos.' }}
                            @endif
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="width: 50%; vertical-align: top; background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #d97706; border-radius: 6px; padding: 8px 10px;">
                        <div style="font-size: 8pt; font-weight: 800; color: #92400e; margin-bottom: 2px;">&bull; {{ $cleanPdf($dossierExtraHoursTitle) }}</div>
                        <div style="font-size: 6.8pt; color: #475569; line-height: 1.3;">
                            {{ $cleanPdf($dossierExtraHoursDesc) }} (Tarifa fijada: <strong>{{ number_format($extraHourPrice, 0, ',', '.') }}€/hora</strong>).
                        </div>
                    </td>
                    <td style="width: 50%; vertical-align: top; background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #8b5cf6; border-radius: 6px; padding: 8px 10px;">
                        <div style="font-size: 8pt; font-weight: 800; color: #5b21b6; margin-bottom: 2px;">&#9835; {{ $cleanPdf($dossierMusicCustomTitle) }}</div>
                        <div style="font-size: 6.8pt; color: #475569; line-height: 1.3;">
                            {{ $cleanPdf($dossierMusicCustomDesc) }}
                        </div>
                    </td>
                </tr>
            </table>

            <!-- 3. COMPROMISOS & TRANQUILIDAD -->
            <div style="background-color: #0b1329; border: 1px solid #1e293b; border-radius: 8px; padding: 10px 14px; color: #ffffff; margin-top: 14px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="vertical-align: middle;">
                            <div style="font-size: 6.5pt; text-transform: uppercase; letter-spacing: 1.5px; color: #eab308; font-weight: 800;">MÁXIMA TRANQUILIDAD Y SEGURIDAD</div>
                            <div style="font-size: 9.5pt; font-weight: 800; color: #ffffff; margin-top: 2px;">Contratación 100% Oficial, Segura y Sin Sorpresas</div>
                            <div style="font-size: 6.8pt; color: #94a3b8; margin-top: 3px; line-height: 1.3;">
                                Equipos revisados antes de cada actuación, contrato digital legal con cobertura de seguro y un equipo humano implicado al 100% en que vuestra fiesta sea inolvidable.
                            </div>
                        </td>
                        <td style="width: 130px; text-align: right; vertical-align: middle; border-left: 1px solid #1e293b; padding-left: 14px;">
                            <div style="font-size: 13pt; line-height: 1; color: #eab308; font-weight: 900;">&#10003; 100%</div>
                            <div style="font-size: 6.5pt; color: #eab308; font-weight: 800; text-transform: uppercase; margin-top: 3px;">Garantía de Fiesta</div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- ==========================================
         PÁGINA 4: PRESUPUESTO PERSONALIZADO OFICIAL (EL PRESUPUESTO DE UNA PÁGINA)
         ========================================== -->
    <div class="page page-last">
        <!-- Cabecera Azul Marino -->
        <div class="page-header-banner">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="vertical-align: middle;">
                        <div class="page-header-sub">PRESUPUESTO PERSONALIZADO OFICIAL &bull; {{ mb_strtoupper($companyName) }}</div>
                        <div class="page-header-title">Propuesta Económica para {{ $event->name }} &bull; #PRE-{{ str_pad($quote->id, 5, '0', STR_PAD_LEFT) }}</div>
                    </td>
                    @if($isSinglePage ?? false)
                        <td style="width: 110px; text-align: right; vertical-align: middle;">
                            @if($logoPath && file_exists($logoPath))
                                <img src="{{ $logoPath }}" style="max-height: 42px; max-width: 100px; display: inline-block;" alt="Logo">
                            @endif
                        </td>
                    @endif
                </tr>
            </table>
        </div>

        <div class="page-content">
            <!-- 1. DATOS DE LA BODA (METRICA DE CONTACTO Y EVENTO) -->
            <div class="couple-meta-card">
                <table class="couple-meta-table">
                    <tr>
                        <td class="couple-meta-lbl">{{ $event->is_wedding ? 'PAREJA / EVENTO:' : 'CLIENTE / EVENTO:' }}</td>
                        <td class="couple-meta-val" style="font-size: 8.5pt; color: #1e3a8a;">{{ $event->name }}</td>
                        <td class="couple-meta-lbl">FECHA:</td>
                        <td class="couple-meta-val" style="color: #0f172a;">{{ $dateFormatted }}</td>
                    </tr>
                    <tr>
                        <td class="couple-meta-lbl">LUGAR / FINCA:</td>
                        <td class="couple-meta-val">{{ $event->location ?: 'Lugar a convenir' }}</td>
                        <td class="couple-meta-lbl">HORARIO:</td>
                        <td class="couple-meta-val">{{ $scheduleStr }}</td>
                    </tr>
                </table>
            </div>

            <!-- 2. LO QUE INCLUYE: TABLA DE SERVICIOS CONTRATADOS / SOLICITADOS -->
            <div class="quote-selection-card">
                <div class="quote-selection-header">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="color: #ffffff; font-weight: 800;">LO QUE INCLUYE VUESTRO SERVICIO (VUESTRA ELECCIÓN)</td>
                            <td style="text-align: right; color: #eab308; font-size: 7.5pt; font-weight: 700;">Emisión: {{ $issueDateShort }} &bull; Validez: {{ $expiresDateShort }}</td>
                        </tr>
                    </table>
                </div>

                <table class="quote-items-table">
                    <thead>
                        <tr>
                            <th style="width: 60%;">Concepto / Equipamiento y Horas</th>
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

                                // Detectar si es un servicio de Fotomatón, Plataforma 360º o Pack Dúo para mostrar la opción de 3 horas y/o Horas Extra del proveedor correspondiente
                                $pbProviders = \App\Models\Setting::getPhotoboothProviders();
                                $locLower = mb_strtolower((string)($event->location ?? ''));
                                $isVitoriaLoc = str_contains($locLower, 'vitoria') || str_contains($locLower, 'alava') || str_contains($locLower, 'álava') || str_contains($locLower, 'gasteiz') || str_contains($locLower, 'araba');
                                $pbCfg = $isVitoriaLoc ? $pbProviders['vitoria'] : $pbProviders['logrono'];
                                $lowerTitle = mb_strtolower($itemTitle);
                                $cleanItemDesc = $itemDesc ? $cleanPdf(explode('| 💡 Opción 3 Horas', $itemDesc)[0]) : '';
                                $upgrade3hBanner = null;
                                $is2hItem = str_contains($lowerTitle, '2 hora') || str_contains($lowerTitle, '2h');

                                if (str_contains($lowerTitle, 'pack dúo') || str_contains($lowerTitle, 'pack duo') || (str_contains($lowerTitle, 'fotomat') && str_contains($lowerTitle, '360'))) {
                                    if ($is2hItem) {
                                        if (abs($itemUnitPrice - (float)$pbProviders['vitoria']['pack_duo_2h']) < 0.01 && abs($itemUnitPrice - (float)$pbProviders['logrono']['pack_duo_2h']) > 0.01) {
                                            $pbCfg = $pbProviders['vitoria'];
                                        } elseif (abs($itemUnitPrice - (float)$pbProviders['logrono']['pack_duo_2h']) < 0.01 && abs($itemUnitPrice - (float)$pbProviders['vitoria']['pack_duo_2h']) > 0.01) {
                                            $pbCfg = $pbProviders['logrono'];
                                        }
                                        $p3h = (float)$pbCfg['pack_duo_3h'];
                                        $diff = max(0, $p3h - $itemUnitPrice);
                                        $pbExtra = (float)($pbCfg['photobooth_extra_hour'] ?? 50);
                                        $vExtra = (float)($pbCfg['video_360_extra_hour'] ?? 60);
                                        $upgrade3hBanner = '★ OPCIÓN 3 HORAS Y HORAS EXTRA: Podéis elegir el Pack Dúo de 3 Horas por ' . number_format($p3h, 0, ',', '.') . ' €' . ($diff > 0 ? ' (+' . number_format($diff, 0, ',', '.') . ' €)' : '') . ' · Posibilidad de añadir horas extra (+' . number_format($pbExtra, 0, ',', '.') . ' €/h Fotomatón · +' . number_format($vExtra, 0, ',', '.') . ' €/h 360º).';
                                    } else {
                                        if (abs($itemUnitPrice - (float)$pbProviders['vitoria']['pack_duo_3h']) < 0.01 && abs($itemUnitPrice - (float)$pbProviders['logrono']['pack_duo_3h']) > 0.01) {
                                            $pbCfg = $pbProviders['vitoria'];
                                        } elseif (abs($itemUnitPrice - (float)$pbProviders['logrono']['pack_duo_3h']) < 0.01 && abs($itemUnitPrice - (float)$pbProviders['vitoria']['pack_duo_3h']) > 0.01) {
                                            $pbCfg = $pbProviders['logrono'];
                                        }
                                        $pbExtra = (float)($pbCfg['photobooth_extra_hour'] ?? 50);
                                        $vExtra = (float)($pbCfg['video_360_extra_hour'] ?? 60);
                                        $upgrade3hBanner = '★ OPCIÓN HORAS EXTRA DISPONIBLE: Si queréis ampliar durante el baile, podéis añadir horas extra (+' . number_format($pbExtra, 0, ',', '.') . ' €/h Fotomatón · +' . number_format($vExtra, 0, ',', '.') . ' €/h Plataforma 360º).';
                                    }
                                } elseif (str_contains($lowerTitle, 'fotomatón') || str_contains($lowerTitle, 'fotomaton') || str_contains($lowerTitle, 'photobooth')) {
                                    if ($is2hItem) {
                                        if (abs($itemUnitPrice - (float)$pbProviders['vitoria']['photobooth_2h']) < 0.01 && abs($itemUnitPrice - (float)$pbProviders['logrono']['photobooth_2h']) > 0.01) {
                                            $pbCfg = $pbProviders['vitoria'];
                                        } elseif (abs($itemUnitPrice - (float)$pbProviders['logrono']['photobooth_2h']) < 0.01 && abs($itemUnitPrice - (float)$pbProviders['vitoria']['photobooth_2h']) > 0.01) {
                                            $pbCfg = $pbProviders['logrono'];
                                        }
                                        $p3h = (float)$pbCfg['photobooth_3h'];
                                        $diff = max(0, $p3h - $itemUnitPrice);
                                        $pbExtra = (float)($pbCfg['photobooth_extra_hour'] ?? 50);
                                        $upgrade3hBanner = '★ OPCIÓN 3 HORAS Y HORAS EXTRA: Podéis elegir 3 Horas completas de Fotomatón por ' . number_format($p3h, 0, ',', '.') . ' €' . ($diff > 0 ? ' (solo +' . number_format($diff, 0, ',', '.') . ' € más)' : '') . ($pbExtra > 0 ? ' · Hora extra adicional disponible (+' . number_format($pbExtra, 0, ',', '.') . ' €/h).' : '');
                                    } else {
                                        if (abs($itemUnitPrice - (float)$pbProviders['vitoria']['photobooth_3h']) < 0.01 && abs($itemUnitPrice - (float)$pbProviders['logrono']['photobooth_3h']) > 0.01) {
                                            $pbCfg = $pbProviders['vitoria'];
                                        } elseif (abs($itemUnitPrice - (float)$pbProviders['logrono']['photobooth_3h']) < 0.01 && abs($itemUnitPrice - (float)$pbProviders['vitoria']['photobooth_3h']) > 0.01) {
                                            $pbCfg = $pbProviders['logrono'];
                                        }
                                        $pbExtra = (float)($pbCfg['photobooth_extra_hour'] ?? 50);
                                        $upgrade3hBanner = '★ OPCIÓN HORAS EXTRA DISPONIBLE: Si queréis ampliar el servicio durante el baile, podéis añadir horas extra de Fotomatón' . ($pbExtra > 0 ? ' (+' . number_format($pbExtra, 0, ',', '.') . ' €/hora adicional).' : '.');
                                    }
                                } elseif (str_contains($lowerTitle, '360') || str_contains($lowerTitle, 'plataforma')) {
                                    if ($is2hItem) {
                                        if (abs($itemUnitPrice - (float)$pbProviders['vitoria']['video_360_2h']) < 0.01 && abs($itemUnitPrice - (float)$pbProviders['logrono']['video_360_2h']) > 0.01) {
                                            $pbCfg = $pbProviders['vitoria'];
                                        } elseif (abs($itemUnitPrice - (float)$pbProviders['logrono']['video_360_2h']) < 0.01 && abs($itemUnitPrice - (float)$pbProviders['vitoria']['video_360_2h']) > 0.01) {
                                            $pbCfg = $pbProviders['logrono'];
                                        }
                                        $p3h = (float)$pbCfg['video_360_3h'];
                                        $diff = max(0, $p3h - $itemUnitPrice);
                                        $vExtra = (float)($pbCfg['video_360_extra_hour'] ?? 60);
                                        $upgrade3hBanner = '★ OPCIÓN 3 HORAS Y HORAS EXTRA: Podéis elegir 3 Horas completas de Plataforma 360º por ' . number_format($p3h, 0, ',', '.') . ' €' . ($diff > 0 ? ' (solo +' . number_format($diff, 0, ',', '.') . ' € más)' : '') . ($vExtra > 0 ? ' · Hora extra adicional disponible (+' . number_format($vExtra, 0, ',', '.') . ' €/h).' : '');
                                    } else {
                                        if (abs($itemUnitPrice - (float)$pbProviders['vitoria']['video_360_3h']) < 0.01 && abs($itemUnitPrice - (float)$pbProviders['logrono']['video_360_3h']) > 0.01) {
                                            $pbCfg = $pbProviders['vitoria'];
                                        } elseif (abs($itemUnitPrice - (float)$pbProviders['logrono']['video_360_3h']) < 0.01 && abs($itemUnitPrice - (float)$pbProviders['vitoria']['video_360_3h']) > 0.01) {
                                            $pbCfg = $pbProviders['logrono'];
                                        }
                                        $vExtra = (float)($pbCfg['video_360_extra_hour'] ?? 60);
                                        $upgrade3hBanner = '★ OPCIÓN HORAS EXTRA DISPONIBLE: Si queréis ampliar el servicio durante el baile, podéis añadir horas extra de Plataforma 360º' . ($vExtra > 0 ? ' (+' . number_format($vExtra, 0, ',', '.') . ' €/hora adicional).' : '.');
                                    }
                                }
                            @endphp
                            <tr>
                                <td>
                                    <div class="quote-item-title {{ $isDiscount ? 'quote-item-discount' : '' }}">
                                        @if($isDiscount) [DESCUENTO] @else &bull; @endif {{ $cleanPdf($itemTitle) }}
                                    </div>
                                    @if(!empty($cleanItemDesc))
                                        <div class="quote-item-desc">{{ $cleanItemDesc }}</div>
                                    @endif
                                    @if($upgrade3hBanner)
                                        <div style="margin-top: 3px; background-color: #fffbeb; border: 1px solid #fcd34d; border-left: 3px solid #d97706; border-radius: 4px; padding: 2.5px 7px; font-size: 6.5pt; color: #92400e; font-weight: 700;">
                                            {{ $upgrade3hBanner }}
                                        </div>
                                    @endif
                                </td>
                                <td style="text-align: center; color: #64748b; font-weight: 600;">
                                    {{ $itemQty }}
                                </td>
                                <td style="text-align: right; font-weight: 800; font-size: 8.5pt;" class="{{ $isDiscount ? 'quote-item-discount' : '' }}">
                                    @if($isDiscount)
                                        -{{ number_format(abs($itemTotalPrice), 2, ',', '.') }} €
                                    @elseif($itemTotalPrice == 0 && (str_contains(mb_strtolower($itemTitle), 'incluido') || str_contains(mb_strtolower($itemDesc ?? ''), 'incluido')))
                                        <span style="font-size: 7.5pt; color: #059669; font-weight: 800;">INCLUIDO (0,00 €)</span>
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

            @if(!empty($quote->admin_notes))
                <div style="margin-bottom: 6px; background-color: #fffbeb; border: 1px solid #fcd34d; border-left: 3.5px solid #d97706; border-radius: 6px; padding: 6px 10px;">
                    <div style="font-size: 6.5pt; text-transform: uppercase; letter-spacing: 0.8px; color: #92400e; font-weight: 800; margin-bottom: 2px;">&bull; NOTAS Y CONDICIONES ESPECIALES DE LA PROPUESTA</div>
                    <div style="font-size: 7.5pt; color: #451a03; line-height: 1.35; font-weight: 600;">{!! nl2br(e($cleanPdf($quote->admin_notes))) !!}</div>
                </div>
            @endif

            <!-- 3. PRECIO FINAL Y CONDICIONES DE PAGO -->
            <div class="quote-total-banner">
                <table class="quote-total-table">
                    <tr>
                        <td style="width: 55%;" class="quote-signal-box">
                            <div style="font-size: 6.5pt; text-transform: uppercase; letter-spacing: 1px; color: #93c5fd; font-weight: 700; margin-bottom: 2px;">CONDICIONES DE PAGO Y RESERVA</div>
                            @if($quote->signal_amount > 0)
                                <div>• Señal para Bloquear Fecha: <strong>{{ number_format($quote->signal_amount, 2, ',', '.') }} €</strong> <span style="font-size: 6.5pt; opacity: 0.85;">({{ $quote->signal_label }})</span></div>
                                <div style="margin-top: 2px;">• Restante: <strong>{{ number_format(max(0, $quote->amount - $quote->signal_amount), 2, ',', '.') }} €</strong> <span style="font-size: 6.5pt; opacity: 0.85;">(a liquidar al finalizar el evento)</span></div>
                            @else
                                <div>• Pago Íntegro: <strong>{{ number_format($quote->amount, 2, ',', '.') }} €</strong> <span style="font-size: 6.5pt; opacity: 0.85;">(el día del evento)</span></div>
                                <div style="margin-top: 2px; color: #bbf7d0;">• <strong>Sin señal previa</strong> de reserva (0,00 €)</div>
                            @endif
                        </td>
                        <td style="width: 45%; text-align: right;">
                            <div class="quote-total-label">PRECIO FINAL (CON TODO)</div>
                            <div class="quote-total-amount">{{ number_format($quote->amount, 2, ',', '.') }} <span>€</span></div>
                            <div style="font-size: 6pt; color: #94a3b8; margin-top: 1px;">IVA incluido &bull; Desplazamiento y montaje incluidos</div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- 4. CÓMO RESERVAR LA FECHA (MÉTODOS DE PAGO) -->
            <div class="payment-methods-box">
                <div class="payment-methods-title">&#10003; CÓMO RESERVAR Y BLOQUEAR LA FECHA EN FIRME:</div>
                <table class="payment-methods-table">
                    <tr>
                        <td style="width: 50%;">
                            @if(!empty($companyBizum))
                                <div><span style="display: inline-block; background-color: #059669; color: #ffffff; font-size: 5.5pt; font-weight: 800; padding: 1px 4px; border-radius: 3px; margin-right: 3px;">BIZUM</span> <strong>Bizum:</strong> {{ $companyBizum }}</div>
                                <div style="font-size: 6.2pt; color: #64748b;">Concepto: <em>{{ $event->name }}</em></div>
                            @endif
                        </td>
                        <td style="width: 50%;">
                            @if(!empty($companyIban))
                                <div><span style="display: inline-block; background-color: #1e3a8a; color: #ffffff; font-size: 5.5pt; font-weight: 800; padding: 1px 4px; border-radius: 3px; margin-right: 3px;">IBAN</span> <strong>Transferencia Bancaria (IBAN):</strong></div>
                                <div style="font-family: monospace; font-size: 6.5pt; font-weight: bold; color: #0f172a;">{{ $companyIban }}</div>
                                <div style="font-size: 6.2pt; color: #64748b;">Concepto: <em>{{ $event->name }}</em></div>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            <!-- 5. PLAZO DE VALIDEZ Y CADUCIDAD DE OFERTA -->
            <div class="quote-validity-banner {{ $isQuoteExpired ? 'expired' : '' }}">
                <table class="quote-validity-table">
                    <tr>
                        <td style="width: 72%; vertical-align: middle;">
                            <div class="quote-validity-title">
                                &bull; PLAZO DE VALIDEZ DE ESTA OFERTA
                            </div>
                            <div class="quote-validity-text">
                                Propuesta emitida el <strong>{{ $issueDateShort }}</strong> y válida hasta el <strong>{{ $expiresDateFormatted }}</strong> ({{ $validityDays }} días naturales).
                                <span style="color: #78350f;">Pasada esta fecha, la reserva provisional se libera en el calendario para {{ $event->is_wedding ? 'otras parejas' : 'otros clientes' }} y las tarifas quedan sujetas a revisión.</span>
                            </div>
                        </td>
                        <td style="width: 28%; text-align: right; vertical-align: middle;">
                            <div class="quote-validity-badge">
                                VÁLIDO HASTA: {{ $expiresDateShort }}
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- 6. PASOS PARA FORMALIZAR -->
            <div style="margin-top: 6px; margin-bottom: 6px; background-color: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 8px; padding: 7px 10px; text-align: center;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 33.3%; text-align: center; vertical-align: middle; border-right: 1px dashed #cbd5e1; padding: 0 6px;">
                            @if($quote->signal_amount > 0)
                                <div style="font-weight: 800; color: #1e3a8a; font-size: 7.5pt;">1. Señal de Reserva</div>
                                <div style="font-size: 6.5pt; color: #64748b; line-height: 1.25; margin-top: 1px;">Abona {{ number_format($quote->signal_amount, 2, ',', '.') }}€ por Bizum o Transferencia.</div>
                            @else
                                <div style="font-weight: 800; color: #1e3a8a; font-size: 7.5pt;">1. Confirmación</div>
                                <div style="font-size: 6.5pt; color: #64748b; line-height: 1.25; margin-top: 1px;">Confirmación de fecha sin requerir señal anticipada.</div>
                            @endif
                        </td>
                        <td style="width: 33.3%; text-align: center; vertical-align: middle; border-right: 1px dashed #cbd5e1; padding: 0 6px;">
                            <div style="font-weight: 800; color: #1e3a8a; font-size: 7.5pt;">2. Firma Digital</div>
                            <div style="font-size: 6.5pt; color: #64748b; line-height: 1.25; margin-top: 1px;">Firma tu contrato oficial en 1 clic desde tu móvil con plena validez.</div>
                        </td>
                        <td style="width: 33.3%; text-align: center; vertical-align: middle; padding: 0 6px;">
                            <div style="font-weight: 800; color: #1e3a8a; font-size: 7.5pt;">3. Portal de Música</div>
                            <div style="font-size: 6.5pt; color: #64748b; line-height: 1.25; margin-top: 1px;">Indica momentos especiales, canciones clave y canciones prohibidas.</div>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="tax-note">
                {{ $companyName }} &bull; CIF/NIF: {{ $companyCif }} &bull; {{ $companyPhone }} &bull; {{ $companyEmail }} &bull; {{ $companyWebsite }}
            </div>
        </div>
    </div>

</body>
</html>
