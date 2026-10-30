<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dossier de Tarifas {{ $config['tariff_year'] ?? '2027' }} - {{ $brandInfo['name'] }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #0f172a;
            background-color: #ffffff;
            font-size: 10.5pt;
            line-height: 1.45;
        }
        .page {
            width: 210mm;
            height: 297mm;
            position: relative;
            overflow: hidden;
            page-break-after: always;
            background-color: #ffffff;
        }
        .page:last-child {
            page-break-after: auto;
        }

        /* PORTADA AZUL OSCURO Y DORADO */
        .cover-page {
            background-color: #0a0f1d;
            color: #f8fafc;
        }
        .cover-top-bar {
            height: 6px;
            background-color: #d4af37;
            width: 100%;
        }
        .cover-inner {
            padding: 16mm 18mm 14mm 18mm;
        }
        .gold-pill {
            display: inline-block;
            background-color: rgba(212, 175, 55, 0.16);
            border: 1.5px solid #d4af37;
            color: #facc15;
            font-size: 10pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            padding: 5px 14px;
            border-radius: 4px;
        }
        .cover-photo-frame {
            margin-top: 8mm;
            margin-bottom: 8mm;
            width: 100%;
            height: 122mm;
            border: 2px solid #d4af37;
            border-radius: 10px;
            overflow: hidden;
            background-color: #111827;
            text-align: center;
        }
        .cover-photo-frame img {
            width: 100%;
            height: 122mm;
            object-fit: cover;
        }

        /* CABECERA Y PIE DE PÁGINAS INTERIORES */
        .page-header {
            background-color: #0f172a;
            color: #ffffff;
            padding: 10mm 16mm 8mm 16mm;
            border-bottom: 3px solid #d4af37;
        }
        .page-header-kicker {
            color: #d4af37;
            font-size: 8pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 3px;
        }
        .page-header-title {
            font-size: 18pt;
            font-weight: 800;
            margin: 0;
            color: #ffffff;
            letter-spacing: -0.3px;
        }
        .page-body {
            padding: 11mm 16mm 18mm 16mm;
        }
        .page-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 12mm;
            padding: 3.5mm 16mm;
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            font-size: 8pt;
            color: #64748b;
        }

        /* TARJETAS Y ELEMENTOS */
        .card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 14px;
        }
        .gold-accent-card {
            background-color: #0f172a;
            color: #ffffff;
            border: 1.5px solid #d4af37;
            border-radius: 10px;
            padding: 14px 16px;
        }
        .step-badge {
            display: inline-block;
            background-color: #fef9c3;
            color: #854d0e;
            border: 1px solid #eab308;
            font-size: 8.5pt;
            font-weight: 800;
            padding: 3px 9px;
            border-radius: 4px;
        }
        .vat-badge {
            display: inline-block;
            font-size: 8pt;
            font-weight: 700;
            color: #166534;
            background-color: #dcfce7;
            border: 1px solid #86efac;
            padding: 2px 7px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
@php
    $year = trim((string) ($config['tariff_year'] ?? '2027')) ?: '2027';
    $pricesIncludeVat = !empty($config['prices_include_vat']);
    $vatRate = (float) ($config['vat_rate'] ?? 21);
    $validPhotos = \App\Services\GeneralTariffDossierService::getValidGalleryPhotos($config);

    // Determinar foto de portada
    $coverPhotoPath = null;
    if (!empty($config['cover_custom_photo'])) {
        $coverPhotoPath = \App\Services\GeneralTariffDossierService::resolveCompressedJpgPathForPdf($config['cover_custom_photo']);
    }
    if (!$coverPhotoPath && !empty($validPhotos)) {
        $selIdx = (int) ($config['cover_photo_index'] ?? 0);
        $chosen = $validPhotos[$selIdx] ?? $validPhotos[0];
        $coverPhotoPath = $chosen['pdf_path'] ?? null;
    }

    $aboutPhotoPath = !empty($config['about_photo'])
        ? \App\Services\GeneralTariffDossierService::resolveCompressedJpgPathForPdf($config['about_photo'])
        : ($validPhotos[0]['pdf_path'] ?? null);

    $brandKey = $config['brand'] ?? 'nunez_and_son';
    $contactEmail = $brandKey === 'javnx'
        ? ($config['contact_email_javnx'] ?? 'info@javnxdj.com')
        : ($config['contact_email_nunez'] ?? 'info@nunezandson.com');
    $contactWeb = $brandKey === 'javnx'
        ? ($config['contact_website_javnx'] ?? 'www.javnxdj.com')
        : ($config['contact_website_nunez'] ?? 'www.nunezandson.com');
@endphp

@foreach($plan['pages'] as $page)
    @if($page['type'] === 'cover')
        <!-- ======================================================= -->
        <!-- PÁGINA 1: PORTADA                                       -->
        <!-- ======================================================= -->
        <div class="page cover-page">
            <div class="cover-top-bar"></div>
            <div class="cover-inner">
                <!-- Cabecera de marca y año de tarifas -->
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="vertical-align: middle;">
                            @if(!empty($brandInfo['logo_pdf_path']) && file_exists($brandInfo['logo_pdf_path']))
                                <img src="{{ $brandInfo['logo_pdf_path'] }}" style="max-height: 52px; max-width: 190px; display: block;">
                            @else
                                <div style="font-size: 22pt; font-weight: 900; color: #ffffff; letter-spacing: 0.5px;">
                                    {{ $brandInfo['name'] }}
                                </div>
                                <div style="font-size: 8.5pt; color: #d4af37; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; margin-top: 2px;">
                                    {{ $brandInfo['subtitle'] }}
                                </div>
                            @endif
                        </td>
                        <td style="text-align: right; vertical-align: middle;">
                            <span class="gold-pill">Tarifas {{ $year }}</span>
                        </td>
                    </tr>
                </table>

                <!-- Foto grande de una boda real nuestra -->
                <div class="cover-photo-frame">
                    @if($coverPhotoPath && file_exists($coverPhotoPath))
                        <img src="{{ $coverPhotoPath }}" alt="Boda real {{ $brandInfo['name'] }}">
                    @else
                        <div style="padding-top: 48mm; color: #94a3b8; font-size: 12pt;">
                            Fotografía real de boda — {{ $brandInfo['name'] }}
                        </div>
                    @endif
                </div>

                <!-- Frase qué hacemos y dónde -->
                <div style="border-left: 4px solid #d4af37; padding-left: 14px; margin-top: 6mm; margin-bottom: 6mm;">
                    <h1 style="font-size: 21pt; font-weight: 900; color: #ffffff; margin: 0 0 6px 0; line-height: 1.2;">
                        {{ $config['cover_phrase'] ?: 'DJ para bodas en La Rioja, Álava, Burgos y Navarra' }}
                    </h1>
                    @if(!empty($config['cover_subtitle']))
                        <p style="font-size: 11pt; color: #cbd5e1; margin: 0; line-height: 1.4;">
                            {{ $config['cover_subtitle'] }}
                        </p>
                    @endif
                </div>

                <!-- Pie de portada con datos directos -->
                <div style="margin-top: 10mm; padding-top: 5mm; border-top: 1px solid #1e293b;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 9.5pt; color: #cbd5e1;">
                        <tr>
                            <td>
                                <strong style="color: #d4af37;">Contacto directo:</strong><br>
                                Fran: {{ $config['phone_fran'] ?? '+34 622 62 47 90' }} &nbsp;&bull;&nbsp; Miguel: {{ $config['phone_miguel'] ?? '+34 674 37 89 93' }}
                            </td>
                            <td style="text-align: right;">
                                <strong style="color: #d4af37;">{{ $contactWeb }}</strong><br>
                                {{ $contactEmail }}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

    @elseif($page['type'] === 'about')
        <!-- ======================================================= -->
        <!-- PÁGINA 2: QUIÉNES SOMOS                                 -->
        <!-- ======================================================= -->
        <div class="page">
            <div class="page-header">
                <div class="page-header-kicker">{{ $brandInfo['name'] }} &bull; Tarifas {{ $year }}</div>
                <h2 class="page-header-title">Quiénes somos</h2>
            </div>

            <div class="page-body">
                <!-- Foto de Fran y Miguel / Equipo -->
                @if($aboutPhotoPath && file_exists($aboutPhotoPath))
                    <div style="width: 100%; height: 74mm; border-radius: 10px; overflow: hidden; border: 2px solid #0f172a; margin-bottom: 6mm; background-color: #0f172a;">
                        <img src="{{ $aboutPhotoPath }}" style="width: 100%; height: 74mm; object-fit: cover;">
                    </div>
                @endif

                <!-- Texto en primera persona basado en hechos -->
                <div class="card" style="margin-bottom: 5mm; border-left: 4px solid #d4af37; background-color: #ffffff;">
                    <div style="font-size: 10.5pt; color: #1e293b; line-height: 1.55; white-space: pre-line;">{{ trim((string) ($config['about_text'] ?? '')) }}</div>
                </div>

                <!-- Hechos clave -->
                @if(!empty($config['about_facts']) && is_array($config['about_facts']))
                    <div style="margin-bottom: 5mm;">
                        <div style="font-size: 9pt; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #0f172a; margin-bottom: 6px;">
                            Por qué trabajamos así
                        </div>
                        <table style="width: 100%; border-collapse: separate; border-spacing: 0 5px;">
                            @foreach($config['about_facts'] as $fact)
                                @if(trim((string) $fact) !== '')
                                    <tr>
                                        <td style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 7px 11px; font-size: 9.5pt; color: #0f172a;">
                                            <strong style="color: #b45309;">&#10003;</strong> &nbsp;{{ $fact }}
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </table>
                    </div>
                @endif

                <!-- Fincas y locales donde hemos trabajado -->
                @if(!empty(trim((string) ($config['about_venues'] ?? ''))))
                    <div class="gold-accent-card">
                        <div style="font-size: 8.5pt; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: #d4af37; margin-bottom: 4px;">
                            {{ $config['about_venues_title'] ?? 'Fincas, bodegas y restaurantes donde hemos trabajado' }}
                        </div>
                        <div style="font-size: 9.5pt; color: #e2e8f0; line-height: 1.45;">
                            {{ $config['about_venues'] }}
                        </div>
                    </div>
                @endif
            </div>

            <div class="page-footer">
                <table style="width: 100%;">
                    <tr>
                        <td><strong>{{ $brandInfo['name'] }}</strong> &bull; Dossier General de Tarifas {{ $year }}</td>
                        <td style="text-align: right;">Página {{ $page['page_number'] }} de {{ $page['total_pages'] }}</td>
                    </tr>
                </table>
            </div>
        </div>

    @elseif($page['type'] === 'gallery')
        <!-- ======================================================= -->
        <!-- PÁGINA 3 (Y 4): NUESTRO TRABAJO (FOTOS GRANDES REALES)  -->
        <!-- ======================================================= -->
        <div class="page">
            <div class="page-header">
                <div class="page-header-kicker">
                    Bodas Reales @if(($page['total_parts'] ?? 1) > 1) &bull; Parte {{ $page['part'] }} de {{ $page['total_parts'] }} @endif
                </div>
                <h2 class="page-header-title">Nuestro trabajo</h2>
            </div>

            <div class="page-body">
                <p style="margin: 0 0 4mm 0; font-size: 9.5pt; color: #475569;">
                    Cada fotografía corresponde a una boda real sonorizada e iluminada por nuestro equipo (publicada con permiso expreso de la pareja).
                </p>

                @php
                    $pagePhotos = $page['photos'] ?? [];
                    $countP = count($pagePhotos);
                @endphp

                @if($countP <= 3)
                    <!-- Hasta 3 fotos en la página: formato panorámico grande una debajo de otra -->
                    @foreach($pagePhotos as $photo)
                        <div style="margin-bottom: 5mm; border: 1px solid #cbd5e1; border-radius: 8px; overflow: hidden; background-color: #0f172a;">
                            @if(!empty($photo['pdf_path']) && file_exists($photo['pdf_path']))
                                <img src="{{ $photo['pdf_path'] }}" style="width: 100%; height: 56mm; object-fit: cover; display: block;">
                            @endif
                            <div style="padding: 6px 12px; background-color: #0f172a; color: #ffffff; font-size: 9pt;">
                                <strong style="color: #facc15;">{{ $photo['wedding_name'] }}</strong>
                                &nbsp;&bull;&nbsp;
                                <span style="color: #cbd5e1;">{{ \App\Services\GeneralTariffDossierService::formatSpanishDate($photo['wedding_date']) }}</span>
                                @if(!empty($photo['caption']))
                                    <span style="color: #94a3b8;"> — {{ $photo['caption'] }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <!-- De 4 a 6 fotos en la página: cuadrícula amplia de 2 columnas con fotos grandes -->
                    @php
                        $rowHeight = $countP <= 4 ? '78mm' : '50mm';
                        $chunks = array_chunk($pagePhotos, 2);
                    @endphp
                    <table style="width: 100%; border-collapse: separate; border-spacing: 4mm;">
                        @foreach($chunks as $pair)
                            <tr>
                                @foreach($pair as $photo)
                                    <td style="width: 50%; vertical-align: top; border: 1px solid #cbd5e1; border-radius: 8px; overflow: hidden; background-color: #0f172a; padding: 0;">
                                        @if(!empty($photo['pdf_path']) && file_exists($photo['pdf_path']))
                                            <img src="{{ $photo['pdf_path'] }}" style="width: 100%; height: {{ $rowHeight }}; object-fit: cover; display: block;">
                                        @endif
                                        <div style="padding: 6px 10px; background-color: #0f172a; color: #ffffff; font-size: 8.5pt;">
                                            <div style="font-weight: 800; color: #facc15;">{{ $photo['wedding_name'] }}</div>
                                            <div style="color: #cbd5e1; font-size: 8pt;">
                                                Fecha: {{ \App\Services\GeneralTariffDossierService::formatSpanishDate($photo['wedding_date']) }}
                                                @if(!empty($photo['caption'])) &bull; {{ $photo['caption'] }} @endif
                                            </div>
                                        </div>
                                    </td>
                                @endforeach
                                @if(count($pair) === 1)
                                    <td style="width: 50%;"></td>
                                @endif
                            </tr>
                        @endforeach
                    </table>
                @endif
            </div>

            <div class="page-footer">
                <table style="width: 100%;">
                    <tr>
                        <td><strong>{{ $brandInfo['name'] }}</strong> &bull; Fotografías reales de bodas con autorización</td>
                        <td style="text-align: right;">Página {{ $page['page_number'] }} de {{ $page['total_pages'] }}</td>
                    </tr>
                </table>
            </div>
        </div>

    @elseif($page['type'] === 'how_we_work')
        <!-- ======================================================= -->
        <!-- PÁGINA 4: CÓMO TRABAJAMOS (PASO A PASO CON PLAZOS)      -->
        <!-- ======================================================= -->
        <div class="page">
            <div class="page-header">
                <div class="page-header-kicker">Organización y Plazos Claros</div>
                <h2 class="page-header-title">Cómo trabajamos (paso a paso)</h2>
            </div>

            <div class="page-body">
                @php
                    $validSteps = array_values(array_filter(
                        $config['work_steps'] ?? [],
                        fn($s) => !empty(trim((string) ($s['title'] ?? '')))
                    ));
                    $isDenseSteps = count($validSteps) > 5;
                @endphp
                <p style="margin: 0 0 {{ $isDenseSteps ? '3.5mm' : '6mm' }} 0; font-size: {{ $isDenseSteps ? '9.5pt' : '10pt' }}; color: #334155;">
                    Seguimos un proceso claro y con plazos definidos desde vuestra primera consulta hasta el día de la boda:
                </p>

                @foreach($validSteps as $idx => $step)
                    <div style="margin-bottom: {{ $isDenseSteps ? '2.8mm' : '4.5mm' }}; border: 1px solid #cbd5e1; border-left: 4px solid #0f172a; border-radius: 8px; padding: {{ $isDenseSteps ? '8px 12px' : '11px 14px' }}; background-color: #f8fafc;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="vertical-align: top; width: 30px;">
                                    <div style="width: 22px; height: 22px; border-radius: 11px; background-color: #0f172a; color: #facc15; font-weight: 900; font-size: 9.5pt; text-align: center; line-height: 22px;">
                                        {{ $idx + 1 }}
                                    </div>
                                </td>
                                <td style="vertical-align: top;">
                                    <table style="width: 100%; border-collapse: collapse;">
                                        <tr>
                                            <td style="font-size: {{ $isDenseSteps ? '10.2pt' : '11pt' }}; font-weight: 800; color: #0f172a; padding-bottom: 2px;">
                                                Paso {{ $idx + 1 }} – {{ $step['title'] }}
                                            </td>
                                            <td style="text-align: right; vertical-align: top; white-space: nowrap;">
                                                <span class="step-badge">Plazo: {{ $step['timeframe'] }}</span>
                                            </td>
                                        </tr>
                                    </table>
                                    @if(!empty($step['description']))
                                        <div style="font-size: {{ $isDenseSteps ? '9pt' : '9.5pt' }}; color: #475569; line-height: 1.38;">
                                            {{ $step['description'] }}
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                @endforeach
            </div>

            <div class="page-footer">
                <table style="width: 100%;">
                    <tr>
                        <td><strong>{{ $brandInfo['name'] }}</strong> &bull; Tarifas {{ $year }}</td>
                        <td style="text-align: right;">Página {{ $page['page_number'] }} de {{ $page['total_pages'] }}</td>
                    </tr>
                </table>
            </div>
        </div>

    @elseif($page['type'] === 'packs')
        <!-- ======================================================= -->
        <!-- PÁGINA 5 (CENTRAL): PACKS Y PRECIO (3 PACKS, IVA INCL.) -->
        <!-- ======================================================= -->
        <div class="page">
            <div class="page-header">
                <div class="page-header-kicker">Tarifas Oficiales {{ $year }} &bull; Precios Finales Cerrados</div>
                <h2 class="page-header-title">Packs y precio</h2>
            </div>

            <div class="page-body" style="padding-top: 7mm;">
                @if(!empty($config['packs_section_note']))
                    <p style="margin: 0 0 5mm 0; font-size: 9.5pt; color: #334155; text-align: center;">
                        {{ $config['packs_section_note'] }}
                    </p>
                @endif

                <!-- 3 PACKS EN COLUMNAS, EL DEL MEDIO DESTACADO -->
                <table style="width: 100%; border-collapse: separate; border-spacing: 3mm 0; table-layout: fixed;">
                    <tr>
                        @foreach(($config['packs'] ?? []) as $pIdx => $pack)
                            @php
                                $isFeatured = ($pIdx === 1);
                                $finalPackPrice = \App\Services\GeneralTariffDossierService::computeFinalPriceWithVat(
                                    $pack['price_input'] ?? 0,
                                    $pricesIncludeVat,
                                    $vatRate
                                );
                            @endphp
                            <td style="width: 33.33%; vertical-align: top; border-radius: 10px; padding: {{ $isFeatured ? '14px 12px' : '12px 11px' }}; {{ $isFeatured ? 'background-color: #0f172a; color: #ffffff; border: 2.5px solid #d4af37;' : 'background-color: #f8fafc; color: #0f172a; border: 1.5px solid #cbd5e1;' }}">
                                <!-- Etiqueta superior -->
                                <div style="margin-bottom: 6px; min-height: 20px;">
                                    @if($isFeatured)
                                        <span style="display: inline-block; background-color: #d4af37; color: #0f172a; font-size: 8pt; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; padding: 3px 8px; border-radius: 4px;">
                                            ★ {{ $pack['badge'] ?: 'El más elegido' }}
                                        </span>
                                    @else
                                        <span style="display: inline-block; background-color: #e2e8f0; color: #334155; font-size: 7.5pt; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; padding: 2px 7px; border-radius: 4px;">
                                            {{ $pack['hours'] ?? 4 }} h de fiesta
                                        </span>
                                    @endif
                                </div>

                                <!-- Nombre del Pack -->
                                <div style="font-size: {{ $isFeatured ? '14.5pt' : '13pt' }}; font-weight: 900; color: {{ $isFeatured ? '#ffffff' : '#0f172a' }}; margin-bottom: 4px;">
                                    {{ $pack['name'] }}
                                </div>

                                <!-- Subtítulo descriptivo -->
                                <div style="font-size: 8.5pt; color: {{ $isFeatured ? '#cbd5e1' : '#475569' }}; line-height: 1.35; margin-bottom: 10px; min-height: 34px;">
                                    {{ $pack['subtitle'] }}
                                </div>

                                <!-- Precio final con IVA incluido -->
                                <div style="padding: 10px 8px; border-radius: 8px; text-align: center; margin-bottom: 10px; {{ $isFeatured ? 'background-color: #1e293b; border: 1px solid #d4af37;' : 'background-color: #ffffff; border: 1px solid #cbd5e1;' }}">
                                    <div style="font-size: {{ $isFeatured ? '22pt' : '19pt' }}; font-weight: 900; color: {{ $isFeatured ? '#facc15' : '#0f172a' }}; line-height: 1.1;">
                                        {{ number_format($finalPackPrice, 0, ',', '.') }} €
                                    </div>
                                    <div style="font-size: 8pt; font-weight: 800; color: {{ $isFeatured ? '#86efac' : '#166534' }}; margin-top: 3px;">
                                        IVA incluido &bull; {{ $pack['hours'] ?? 4 }} h de fiesta
                                    </div>
                                </div>

                                <!-- Lista de lo que incluye con números -->
                                <div style="font-size: 8pt; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; color: {{ $isFeatured ? '#d4af37' : '#475569' }}; margin-bottom: 6px;">
                                    Qué incluye este pack:
                                </div>
                                <table style="width: 100%; border-collapse: collapse;">
                                    @foreach(($pack['includes'] ?? []) as $incLine)
                                        @if(trim((string) $incLine) !== '')
                                            <tr>
                                                <td style="vertical-align: top; width: 13px; padding: 3px 0; color: {{ $isFeatured ? '#facc15' : '#0f172a' }}; font-weight: 900; font-size: 8.5pt;">
                                                    &#10003;
                                                </td>
                                                <td style="vertical-align: top; padding: 3px 0; font-size: 8.3pt; color: {{ $isFeatured ? '#f1f5f9' : '#1e293b' }}; line-height: 1.35;">
                                                    {{ $incLine }}
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </table>
                            </td>
                        @endforeach
                    </tr>
                </table>

                <!-- Nota inferior de garantía de precios cerrados -->
                <div style="margin-top: 6mm; padding: 10px 14px; background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; text-align: center; font-size: 9pt; color: #334155;">
                    <strong>Precios finales cerrados con IVA incluido.</strong> Sin costes ocultos de desplazamiento en nuestra zona habitual ni recargos sorpresa.
                </div>
            </div>

            <div class="page-footer">
                <table style="width: 100%;">
                    <tr>
                        <td><strong>{{ $brandInfo['name'] }}</strong> &bull; Todos los precios incluyen el IVA ({{ $vatRate }}%)</td>
                        <td style="text-align: right;">Página {{ $page['page_number'] }} de {{ $page['total_pages'] }}</td>
                    </tr>
                </table>
            </div>
        </div>

    @elseif($page['type'] === 'extras' || $page['type'] === 'extras_cont')
        <!-- ======================================================= -->
        <!-- PÁGINA 6: EXTRAS (SOLO PRECIOS CERRADOS CON IVA INCL.)  -->
        <!-- ======================================================= -->
        <div class="page">
            <div class="page-header">
                <div class="page-header-kicker">Servicios Adicionales con Precio Cerrado</div>
                <h2 class="page-header-title">{{ $page['type'] === 'extras_cont' ? 'Extras (Continuación)' : 'Extras para completar vuestra boda' }}</h2>
            </div>

            <div class="page-body">
                <p style="margin: 0 0 5mm 0; font-size: 10pt; color: #334155;">
                    Podéis añadir cualquiera de estos servicios a vuestro pack en el momento de reservar o más adelante al definir los horarios de la boda. Todos los importes son precios finales con <strong>IVA incluido</strong>:
                </p>

                @if($page['type'] === 'extras' && !empty($config['qr_photos_gift_enabled']))
                    <div style="margin-bottom: 4.5mm; border: 1.5px solid #10b981; border-left: 5px solid #10b981; border-radius: 8px; padding: 10px 15px; background-color: #ecfdf5;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="vertical-align: middle; padding-right: 12px;">
                                    <div style="font-size: 10.5pt; font-weight: 900; color: #065f46; margin-bottom: 3px;">
                                        &#9733; {{ $config['qr_photos_gift_title'] ?? 'REGALO INCLUIDO al contratar Ceremonia o Cóctel: Álbum de Fotos QR para Invitados' }}
                                    </div>
                                    <div style="font-size: 8.8pt; color: #047857; line-height: 1.38;">
                                        {{ $config['qr_photos_gift_text'] ?? 'Colocamos un código QR personalizado para que todos los invitados suban y descarguen en calidad original las fotos que hagan con su móvil durante la ceremonia, el cóctel y la fiesta, directo a vuestro álbum privado sin instalar ninguna app.' }}
                                    </div>
                                </td>
                                <td style="vertical-align: middle; text-align: right; width: 120px; white-space: nowrap;">
                                    <div style="font-size: 13.5pt; font-weight: 900; color: #065f46;">
                                        0 €
                                    </div>
                                    <span style="display: inline-block; padding: 2px 7px; border-radius: 4px; background-color: #059669; color: #ffffff; font-size: 7pt; font-weight: 800; text-transform: uppercase;">
                                        Incluido de regalo
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                @endif

                @foreach(($page['extras'] ?? []) as $extra)
                    <div style="margin-bottom: 4.5mm; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px 16px; background-color: #f8fafc;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="vertical-align: middle; padding-right: 12px;">
                                    <div style="font-size: 11.5pt; font-weight: 800; color: #0f172a; margin-bottom: 3px;">
                                        {{ $extra['name'] }}
                                    </div>
                                    @if(!empty($extra['description']))
                                        <div style="font-size: 9.2pt; color: #475569; line-height: 1.4;">
                                            {{ $extra['description'] }}
                                        </div>
                                    @endif
                                </td>
                                <td style="vertical-align: middle; text-align: right; width: 125px; white-space: nowrap;">
                                    <div style="font-size: 15pt; font-weight: 900; color: #0f172a;">
                                        {{ number_format((float) $extra['final_price'], 0, ',', '.') }} €
                                    </div>
                                    <span class="vat-badge">IVA incluido</span>
                                </td>
                            </tr>
                        </table>
                    </div>
                @endforeach

                <div class="gold-accent-card" style="margin-top: 6mm;">
                    <div style="font-size: 9pt; font-weight: 800; color: #d4af37; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">
                        ¿Necesitáis añadir horas extra el mismo día de la boda?
                    </div>
                    <div style="font-size: 9.5pt; color: #e2e8f0; line-height: 1.45;">
                        Si la fiesta está en lo más alto y queréis alargar el baile, podéis ampliar horas adicionales en el momento (siempre dentro del horario permitido por la finca o restaurante) al mismo precio cerrado indicado arriba.
                    </div>
                </div>
            </div>

            <div class="page-footer">
                <table style="width: 100%;">
                    <tr>
                        <td><strong>{{ $brandInfo['name'] }}</strong> &bull; Tarifas de Extras {{ $year }} (IVA incluido)</td>
                        <td style="text-align: right;">Página {{ $page['page_number'] }} de {{ $page['total_pages'] }}</td>
                    </tr>
                </table>
            </div>
        </div>

    @elseif($page['type'] === 'reviews')
        <!-- ======================================================= -->
        <!-- PÁGINA 7: OPINIONES REALES + ENLACE/QR GOOGLE           -->
        <!-- ======================================================= -->
        <div class="page">
            <div class="page-header">
                <div class="page-header-kicker">Experiencias Verificadas de Parejas</div>
                <h2 class="page-header-title">Opiniones</h2>
            </div>

            <div class="page-body">
                @foreach(($page['reviews'] ?? []) as $rev)
                    <div style="margin-bottom: 4.5mm; border: 1px solid #cbd5e1; border-left: 4px solid #d4af37; border-radius: 8px; padding: 11px 15px; background-color: #f8fafc;">
                        <div style="font-size: 9.8pt; color: #1e293b; font-style: italic; margin-bottom: 6px; line-height: 1.45;">
                            «{{ $rev['text'] }}»
                        </div>
                        <div style="font-size: 8.8pt; font-weight: 800; color: #0f172a;">
                            {{ $rev['couple_name'] }}
                            @if(!empty($rev['location']))
                                <span style="font-weight: 600; color: #475569;"> &bull; {{ $rev['location'] }}</span>
                            @endif
                            <span style="font-weight: 600; color: #b45309;"> &bull; {{ \App\Services\GeneralTariffDossierService::formatSpanishDate($rev['event_date']) }}</span>
                        </div>
                    </div>
                @endforeach

                @if(!empty($config['google_reviews_url']))
                    <div class="gold-accent-card" style="margin-top: 6mm; text-align: center;">
                        <div style="font-size: 9.5pt; font-weight: 800; color: #facc15; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">
                            ★ Consulta todas nuestras reseñas verificadas en Google
                        </div>
                        <div style="font-size: 9.5pt; color: #e2e8f0; margin-bottom: 4px;">
                            Puedes leer más opiniones reales de parejas en nuestro perfil oficial:
                        </div>
                        <div style="font-size: 10pt; font-weight: 800; color: #ffffff; word-break: break-all;">
                            {{ $config['google_reviews_url'] }}
                        </div>
                    </div>
                @endif
            </div>

            <div class="page-footer">
                <table style="width: 100%;">
                    <tr>
                        <td><strong>{{ $brandInfo['name'] }}</strong> &bull; Reseñas publicadas con permiso de cada pareja</td>
                        <td style="text-align: right;">Página {{ $page['page_number'] }} de {{ $page['total_pages'] }}</td>
                    </tr>
                </table>
            </div>
        </div>

    @elseif($page['type'] === 'how_to_book')
        <!-- ======================================================= -->
        <!-- PÁGINA 8 (FINAL): CÓMO RESERVAR Y VALIDEZ DE TARIFAS    -->
        <!-- ======================================================= -->
        @php
            $depPct = (float) ($config['deposit_percentage'] ?? 40);
            $restPct = max(0, 100 - $depPct);
        @endphp
        <div class="page">
            <div class="page-header">
                <div class="page-header-kicker">Reserva de Fecha y Condiciones Clarísimas</div>
                <h2 class="page-header-title">Cómo reservar</h2>
            </div>

            <div class="page-body">
                <!-- 1. Señal y forma de pago -->
                <div style="margin-bottom: 5mm; border: 1.5px solid #0f172a; border-radius: 10px; padding: 14px 16px; background-color: #f8fafc;">
                    <div style="font-size: 9pt; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #b45309; margin-bottom: 8px;">
                        1. Señal de reserva y pagos
                    </div>
                    <table style="width: 100%; border-collapse: separate; border-spacing: 4mm 0;">
                        <tr>
                            <td style="width: 50%; background-color: #0f172a; color: #ffffff; border-radius: 8px; padding: 12px; text-align: center; border: 1px solid #d4af37;">
                                <div style="font-size: 20pt; font-weight: 900; color: #facc15;">{{ rtrim(rtrim(number_format($depPct, 2, ',', '.'), '0'), ',') }} %</div>
                                <div style="font-size: 9pt; font-weight: 700; color: #ffffff; margin-top: 2px;">Señal para reservar la fecha</div>
                                <div style="font-size: 8pt; color: #cbd5e1; margin-top: 2px;">A la firma del contrato</div>
                            </td>
                            <td style="width: 50%; background-color: #ffffff; color: #0f172a; border-radius: 8px; padding: 12px; text-align: center; border: 1px solid #cbd5e1;">
                                <div style="font-size: 20pt; font-weight: 900; color: #0f172a;">{{ rtrim(rtrim(number_format($restPct, 2, ',', '.'), '0'), ',') }} %</div>
                                <div style="font-size: 9pt; font-weight: 700; color: #0f172a; margin-top: 2px;">Importe restante</div>
                                <div style="font-size: 8pt; color: #475569; margin-top: 2px;">El día del evento o antes</div>
                            </td>
                        </tr>
                    </table>
                    <div style="margin-top: 10px; font-size: 9.8pt; color: #1e293b;">
                        <strong>Formas de pago aceptadas:</strong> {{ $config['payment_methods_text'] ?: 'Transferencia bancaria, Bizum o efectivo' }}.
                    </div>
                </div>

                <!-- 2. Qué pasa después de reservar -->
                <div class="card" style="margin-bottom: 5mm; border-left: 4px solid #0f172a;">
                    <div style="font-size: 9pt; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #0f172a; margin-bottom: 4px;">
                        2. Qué pasa después de reservar
                    </div>
                    <div style="font-size: 10pt; color: #334155; line-height: 1.5;">
                        {{ $config['after_booking_text'] }}
                    </div>
                </div>

                <!-- 3. Validez de las tarifas -->
                <div style="margin-bottom: 5mm; padding: 11px 16px; border-radius: 8px; background-color: #fef9c3; border: 1.5px solid #eab308; color: #713f12; font-size: 10.5pt; font-weight: 800; text-align: center;">
                    Tarifas válidas hasta el {{ \App\Services\GeneralTariffDossierService::formatSpanishDate($config['valid_until'] ?? '', true) ?: \App\Services\GeneralTariffDossierService::formatSpanishDate($config['valid_until'] ?? '') }}
                </div>

                <!-- 4. Contacto directo -->
                <div class="gold-accent-card" style="margin-bottom: 6mm;">
                    <div style="font-size: 9pt; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: #d4af37; margin-bottom: 8px;">
                        Contacto directo
                    </div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 10.5pt; color: #ffffff;">
                        <tr>
                            <td style="padding: 4px 0;">
                                <strong>Fran:</strong> {{ $config['phone_fran'] ?? '+34 622 62 47 90' }} (Llamadas y WhatsApp)
                            </td>
                            <td style="padding: 4px 0; text-align: right;">
                                <strong>Correo:</strong> {{ $contactEmail }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 0;">
                                <strong>Miguel:</strong> {{ $config['phone_miguel'] ?? '+34 674 37 89 93' }} (Llamadas y WhatsApp)
                            </td>
                            <td style="padding: 4px 0; text-align: right;">
                                <strong>Web:</strong> {{ $contactWeb }}
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Frase de cierre tranquila -->
                <div style="text-align: center; padding: 10px 14px; font-size: 12pt; font-weight: 800; color: #0f172a;">
                    «{{ $config['closing_phrase'] ?: 'Si queréis la fecha, os la guardamos con la señal.' }}»
                </div>
            </div>

            <div class="page-footer">
                <table style="width: 100%;">
                    <tr>
                        <td><strong>{{ $brandInfo['name'] }}</strong> &bull; {{ $contactWeb }}</td>
                        <td style="text-align: right;">Página {{ $page['page_number'] }} de {{ $page['total_pages'] }}</td>
                    </tr>
                </table>
            </div>
        </div>
    @endif
@endforeach
</body>
</html>
