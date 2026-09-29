<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato {{ $contract->contract_number }}</title>
    <style>
        @page {
            margin: 20mm 15mm 20mm 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            line-height: 1.45;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .header-logo {
            max-height: 55px;
            max-width: 180px;
        }
        .company-title {
            font-size: 14pt;
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
        .doc-badge {
            text-align: right;
            vertical-align: middle;
        }
        .doc-badge-title {
            font-size: 13pt;
            font-weight: bold;
            color: #2563eb;
            margin: 0 0 4px 0;
            text-transform: uppercase;
        }
        .doc-badge-meta {
            font-size: 8pt;
            color: #64748b;
        }
        .summary-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 16px;
        }
        .summary-table {
            width: 100%;
            font-size: 8pt;
        }
        .summary-table td {
            padding: 2px 4px;
            vertical-align: top;
        }
        .summary-label {
            color: #64748b;
            font-weight: 600;
            width: 18%;
        }
        .summary-value {
            color: #0f172a;
            width: 32%;
        }
        .content-body {
            text-align: justify;
            font-size: 8.5pt;
            color: #334155;
            line-height: 1.5;
            margin-bottom: 20px;
        }
        .content-body p {
            margin: 0 0 8px 0;
        }
        .consent-box {
            background: #fffbeb;
            border: 1px solid #fef3c7;
            border-left: 3px solid #d97706;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 18px;
            font-size: 7.5pt;
            page-break-inside: avoid;
        }
        .consent-title {
            font-weight: bold;
            color: #92400e;
            margin-bottom: 4px;
            font-size: 8pt;
        }
        .signatures-table {
            width: 100%;
            margin-top: 15px;
            page-break-inside: avoid;
        }
        .signature-box {
            width: 47%;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
            background-color: #fafbfc;
            vertical-align: top;
        }
        .signature-spacer {
            width: 6%;
        }
        .sign-title {
            font-size: 7.5pt;
            font-weight: bold;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }
        .signature-graphic {
            height: 60px;
            margin: 6px 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .signature-graphic img {
            max-height: 55px;
            max-width: 90%;
        }
        .sign-name {
            font-size: 8.5pt;
            font-weight: bold;
            color: #0f172a;
            margin: 4px 0 2px 0;
        }
        .sign-sub {
            font-size: 7.5pt;
            color: #64748b;
            margin: 0;
        }
        .footer-note {
            margin-top: 20px;
            font-size: 7pt;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            page-break-inside: avoid;
        }
    </style>
</head>
<body>

    <!-- CABECERA -->
    <table class="header-table">
        <tr>
            <td style="vertical-align: middle;">
                @php $contractLogoPath = \App\Models\Setting::getLogoPathForPdf(); @endphp
                @if($contractLogoPath)
                    <img src="{{ $contractLogoPath }}" class="header-logo" alt="Logo">
                @else
                    <h1 class="company-title">{{ $replacements['{{ empresa }}'] }}</h1>
                    <p class="company-subtitle">{{ \App\Models\Setting::get('company_subtitle', 'Servicios Musicales, Sonorización e Iluminación') }}</p>
                @endif
            </td>
            <td class="doc-badge">
                <h2 class="doc-badge-title">Contrato de Servicios</h2>
                <div class="doc-badge-meta">
                    <strong>Nº Contrato:</strong> {{ $replacements['{{ numero_contrato }}'] }} &bull; 
                    <strong>Emisión:</strong> {{ $replacements['{{ fecha_emision }}'] }}
                </div>
            </td>
        </tr>
    </table>

    <!-- FICHA RESUMEN DEL EVENTO -->
    <div class="summary-card">
        <table class="summary-table">
            <tr>
                <td class="summary-label">Evento:</td>
                <td class="summary-value"><strong>{{ $replacements['{{ evento }}'] }}</strong></td>
                <td class="summary-label">Fecha Evento:</td>
                <td class="summary-value"><strong>{{ $replacements['{{ fecha_evento }}'] }}</strong></td>
            </tr>
            <tr>
                <td class="summary-label">Cliente:</td>
                <td class="summary-value">{{ $replacements['{{ cliente }}'] }}</td>
                <td class="summary-label">Ubicación:</td>
                <td class="summary-value">{{ $replacements['{{ ubicacion }}'] }}</td>
            </tr>
            <tr>
                <td class="summary-label">Horario:</td>
                <td class="summary-value">{{ $replacements['{{ hora_inicio }}'] }} - {{ $replacements['{{ hora_fin }}'] }}</td>
                <td class="summary-label">Importe Total:</td>
                <td class="summary-value" style="color: #1d4ed8; font-weight: bold; font-size: 10.5pt;">{{ $replacements['{{ importe }}'] }}</td>
            </tr>
        </table>
    </div>

    <!-- CUERPO DEL CONTRATO / CLÁUSULAS -->
    <div class="content-body">
        {!! nl2br(e($body)) !!}
    </div>

    <!-- CONSENTIMIENTO DE IMAGEN Y REDES SOCIALES -->
    <div class="consent-box">
        <div class="consent-title">Autorización de Difusión en Redes Sociales y Portfolio</div>
        <p style="margin: 0 0 6px 0; color: #475569;">
            Autorizo a {{ $replacements['{{ empresa }}'] }} a capturar y publicar fotografías o vídeos del montaje técnico, efectos de iluminación y ambiente general de la fiesta en sus canales profesionales oficiales (Instagram, TikTok o web corporativa), con derecho de revocación en cualquier momento:
        </p>
        <table style="width: 100%; font-size: 8pt; color: #1e293b;">
            <tr>
                <td style="width: 50%;">[ &nbsp;<strong>{{ ($contract->consent_rrss ?? true) ? 'X' : '&nbsp;' }}</strong>&nbsp; ] <strong>SÍ</strong> autorizo la difusión en redes sociales y portfolio.</td>
                <td style="width: 50%;">[ &nbsp;<strong>{{ !($contract->consent_rrss ?? true) ? 'X' : '&nbsp;' }}</strong>&nbsp; ] <strong>NO</strong> autorizo la difusión.</td>
            </tr>
        </table>
    </div>

    <!-- BLOQUE DE FIRMAS -->
    <table class="signatures-table">
        <tr>
            <td class="signature-box">
                <div class="sign-title">Por la Empresa Prestadora</div>
                <div class="signature-graphic"></div>
                <p class="sign-name">{{ $replacements['{{ empresa }}'] }}</p>
                <p class="sign-sub">NIF/CIF: {{ $replacements['{{ cif_empresa }}'] }}</p>
            </td>
            <td class="signature-spacer"></td>
            <td class="signature-box">
                <div class="sign-title">Por el Cliente / Contratante</div>
                <div class="signature-graphic">
                    @if(($contract->signature_type ?? '') === 'certificate' || (!empty($contract->certificate_hash) && empty($contract->signature_data)))
                        <div style="border: 1px solid #2563eb; background: #eff6ff; padding: 4px 6px; border-radius: 4px; text-align: left; font-size: 6.5pt; color: #1e3a8a; line-height: 1.25;">
                            <strong style="color: #1d4ed8;">&#x1F510; SELLO DE CERTIFICADO DIGITAL</strong><br>
                            <strong>Emisor:</strong> {{ $contract->certificate_issuer ?? 'FNMT / DNIe' }}<br>
                            <strong>Titular:</strong> {{ $contract->certificate_subject ?? $replacements['{{ cliente }}'] }}<br>
                            <strong>SHA-256:</strong> <span style="font-family: monospace; font-size: 5pt;">{{ substr($contract->certificate_hash ?? '', 0, 28) }}...</span>
                        </div>
                    @elseif(!empty($contract->signature_data))
                        <img src="{{ $contract->signature_data }}" alt="Firma Digital">
                    @endif
                </div>
                <p class="sign-name">{{ $replacements['{{ cliente }}'] }}</p>
                <p class="sign-sub">
                    DNI/NIF: {{ $replacements['{{ nif_cliente }}'] }}
                    @if(!empty($contract->signed_at))
                        <br><span style="font-size: 6.5pt; color: #16a34a; font-weight: bold;">
                            @if(($contract->signature_type ?? '') === 'certificate')
                                (Firmado con Certificado Digital el {{ $contract->signed_at->format('d/m/Y \a \l\a\s H:i\h') }})
                            @else
                                (Firmado digitalmente el {{ $contract->signed_at->format('d/m/Y \a \l\a\s H:i\h') }})
                            @endif
                        </span>
                    @endif
                </p>
            </td>
        </tr>
    </table>

    <!-- PIE LEGAL -->
    @if(!empty($footer))
        <div class="footer-note">
            {{ $footer }}
        </div>
    @endif

</body>
</html>
