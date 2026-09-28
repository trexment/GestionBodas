<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $invoice->isReceipt() ? 'Recibo' : 'Factura' }} {{ $invoice->invoice_number }} - {{ $event->name }}</title>
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
        
        .primary-color { color: #1e3a8a; }
        .text-muted { color: #64748b; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        
        /* CABECERA */
        .header-table {
            width: 100%;
            border-bottom: 2px solid {{ $invoice->isReceipt() ? '#059669' : '#2563eb' }};
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
            color: {{ $invoice->isReceipt() ? '#065f46' : '#1e3a8a' }};
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
            font-size: 14pt;
            font-weight: bold;
            color: {{ $invoice->isReceipt() ? '#059669' : '#2563eb' }};
            margin: 0 0 3px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .doc-badge-meta {
            font-size: 8.5pt;
            color: #334155;
        }

        /* BLOQUES DATOS FISCALES */
        .parties-table {
            width: 100%;
            margin-bottom: 15px;
        }
        .parties-table td {
            vertical-align: top;
            width: 50%;
            padding: 0 6px;
        }
        .party-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
            min-height: 110px;
        }
        .party-title {
            font-size: 8.5pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #475569;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
            margin-bottom: 6px;
        }
        .party-name {
            font-size: 10pt;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 3px;
        }
        .party-line {
            font-size: 8pt;
            color: #475569;
            line-height: 1.35;
        }

        /* TABLA DE CONCEPTOS */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 15px;
        }
        .items-table th {
            background-color: {{ $invoice->isReceipt() ? '#065f46' : '#1e3a8a' }};
            color: #ffffff;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 8px;
            text-align: left;
            border: none;
        }
        .items-table th.text-right {
            text-align: right;
        }
        .items-table td {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 8.5pt;
            vertical-align: middle;
        }
        .items-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        /* TOTALES Y FORMAS DE PAGO */
        .bottom-table {
            width: 100%;
            margin-top: 10px;
        }
        .bottom-table td {
            vertical-align: top;
        }
        .payment-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
            width: 55%;
            font-size: 8pt;
        }
        .totals-box {
            width: 42%;
            margin-left: auto;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background-color: #ffffff;
            overflow: hidden;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
        }
        .totals-table td {
            padding: 5px 10px;
        }
        .total-highlight-row td {
            background-color: {{ $invoice->isReceipt() ? '#ecfdf5' : '#eff6ff' }};
            border-top: 2px solid {{ $invoice->isReceipt() ? '#059669' : '#2563eb' }};
            font-size: 11pt;
            font-weight: bold;
            color: {{ $invoice->isReceipt() ? '#065f46' : '#1e3a8a' }};
            padding: 8px 10px;
        }

        /* PIE DE PÁGINA */
        .footer {
            margin-top: 25px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            text-align: center;
            font-size: 7.5pt;
            color: #94a3b8;
            line-height: 1.4;
        }
    </style>
</head>
<body>

    <!-- CABECERA -->
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 55%; vertical-align: middle;">
                @if(!empty($company['logo']) && file_exists(public_path('storage/' . $company['logo'])))
                    <img src="{{ public_path('storage/' . $company['logo']) }}" class="header-logo" alt="Logo">
                @else
                    <h1 class="company-name">{{ $company['name'] ?? 'Eventos Musicales' }}</h1>
                    <p class="company-subtitle">{{ $company['subtitle'] ?? 'Sound in Motion &bull; Servicios Audiovisuales' }}</p>
                @endif
            </td>
            <td style="width: 45%; vertical-align: middle; text-align: right;">
                <div class="doc-badge-title">
                    {{ $invoice->isReceipt() ? 'RECIBO DE PAGO' : 'FACTURA' }}
                </div>
                <div class="doc-badge-meta">
                    <strong>Nº Documento:</strong> {{ $invoice->invoice_number }}<br>
                    <strong>Fecha Emisión:</strong> {{ $invoice->issue_date ? $invoice->issue_date->format('d/m/Y') : now()->format('d/m/Y') }}<br>
                    <strong>Estado:</strong> 
                    <span style="font-weight: bold; color: {{ $invoice->status === 'paid' ? '#059669' : '#d97706' }};">
                        {{ $invoice->status === 'paid' ? 'COBRADO / PAGADO' : 'PENDIENTE' }}
                    </span>
                </div>
            </td>
        </tr>
    </table>

    <!-- DATOS EMISOR Y CLIENTE -->
    <table class="parties-table" cellpadding="0" cellspacing="0">
        <tr>
            <!-- EMISOR -->
            <td>
                <div class="party-card">
                    <div class="party-title">Datos del Emisor</div>
                    <div class="party-name">{{ $company['name'] ?? 'Eventos Musicales' }}</div>
                    <div class="party-line"><strong>CIF/NIF:</strong> {{ $company['cif'] ?? 'B-12345678' }}</div>
                    <div class="party-line">{{ $company['address'] ?? 'Calle Principal s/n' }}</div>
                    <div class="party-line">{{ $company['city'] ?? 'Navarrete (La Rioja)' }}</div>
                    <div class="party-line"><strong>Tel:</strong> {{ $company['phone'] ?? '+34 622 634 790' }} &bull; {{ $company['email'] ?? 'info@eventosmusicales.es' }}</div>
                </div>
            </td>

            <!-- CLIENTE / RECEPTOR -->
            <td>
                <div class="party-card">
                    <div class="party-title">{{ $invoice->isReceipt() ? 'Recibido de / Cliente' : 'Facturar a / Cliente' }}</div>
                    <div class="party-name">{{ $client ? $client->name : ($event->client_name ?: 'Cliente General') }}</div>
                    <div class="party-line"><strong>NIF / DNI:</strong> {{ $client ? ($client->dni ?? $client->nif ?? 'No especificado') : 'No especificado' }}</div>
                    @if($client && $client->address)
                        <div class="party-line">{{ $client->address }}</div>
                    @endif
                    @if($client && ($client->postal_code || $client->city || $client->province))
                        <div class="party-line">{{ $client->postal_code }} {{ $client->city }} {{ $client->province ? '('.$client->province.')' : '' }}</div>
                    @endif
                    <div class="party-line"><strong>Evento:</strong> {{ $event->name }} ({{ $event->event_date ? $event->event_date->format('d/m/Y') : '' }})</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- TABLA DE CONCEPTOS -->
    <table class="items-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th style="width: 55%;">Descripción del Servicio / Concepto</th>
                <th style="width: 15%; text-align: center;">Cantidad</th>
                <th style="width: 15%; text-align: right;">Precio Unit.</th>
                <th style="width: 15%; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @if($quote && $quote->items && $quote->items->count() > 0)
                @foreach($quote->items as $item)
                    <tr>
                        <td>
                            <strong>{{ $item->concept ?: $item->service_name }}</strong>
                            @if($item->hours > 0)
                                <span class="text-muted" style="font-size: 7.5pt;">({{ $item->hours }}h de servicio)</span>
                            @endif
                            @if(!empty($item->custom_note))
                                <br><span class="text-muted" style="font-size: 7.5pt;">{{ $item->custom_note }}</span>
                            @endif
                        </td>
                        <td style="text-align: center;">{{ $item->quantity ?: 1 }}</td>
                        <td style="text-align: right;">{{ number_format($item->unit_price, 2, ',', '.') }} €</td>
                        <td style="text-align: right; font-weight: bold;">{{ number_format($item->total_price, 2, ',', '.') }} €</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td>
                        <strong>Servicios Musicales, DJ y Audiovisuales</strong><br>
                        <span class="text-muted" style="font-size: 8pt;">
                            Evento: {{ $event->name }} &bull; Lugar: {{ $event->location ?: 'A convenir' }}
                        </span>
                        @if($invoice->notes)
                            <br><span class="text-muted" style="font-size: 7.5pt;">{{ $invoice->notes }}</span>
                        @endif
                    </td>
                    <td style="text-align: center;">1</td>
                    <td style="text-align: right;">{{ number_format($invoice->amount, 2, ',', '.') }} €</td>
                    <td style="text-align: right; font-weight: bold;">{{ number_format($invoice->amount, 2, ',', '.') }} €</td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- TOTALES Y DATOS DE PAGO -->
    <table class="bottom-table" cellpadding="0" cellspacing="0">
        <tr>
            <!-- CAJA DE INFORMACIÓN DE PAGO -->
            <td style="width: 54%;">
                <div class="payment-box">
                    <strong style="color: #0f172a; font-size: 8.5pt;">Información de Pago y Datos Bancarios:</strong>
                    <div style="margin-top: 4px; line-height: 1.4;">
                        @if(!empty($company['iban']))
                            <div><strong>IBAN Transferencia:</strong> <span style="font-family: monospace;">{{ $company['iban'] }}</span></div>
                        @endif
                        @if(!empty($company['bizum']))
                            <div><strong>Bizum Empresas:</strong> {{ $company['bizum'] }}</div>
                        @endif
                        <div><strong>Concepto:</strong> {{ $invoice->invoice_number }} - {{ $event->name }}</div>
                    </div>

                    @if($invoice->isReceipt())
                        <div style="margin-top: 6px; padding-top: 6px; border-top: 1px dashed #cbd5e1; font-size: 7.5pt; color: #059669;">
                            ✓ <strong>Recibo oficial justificativo</strong> emitido para constancia de los servicios contratados y devengados.
                        </div>
                    @else
                        <div style="margin-top: 6px; padding-top: 6px; border-top: 1px dashed #cbd5e1; font-size: 7.5pt; color: #475569;">
                            Régimen General de IVA. Factura emitida con arreglo a la normativa tributaria vigente.
                        </div>
                    @endif
                </div>
            </td>

            <!-- TABLA DE TOTALES -->
            <td style="width: 46%; padding-left: 15px;">
                <div class="totals-box">
                    <table class="totals-table">
                        @if($invoice->isInvoice())
                            <tr>
                                <td class="text-muted">Base Imponible:</td>
                                <td class="text-right font-bold">{{ number_format($invoice->amount, 2, ',', '.') }} €</td>
                            </tr>
                            <tr>
                                <td class="text-muted">IVA ({{ number_format($invoice->tax_rate ?: 21, 0) }}%):</td>
                                <td class="text-right font-bold">{{ number_format($invoice->tax, 2, ',', '.') }} €</td>
                            </tr>
                            <tr class="total-highlight-row">
                                <td>TOTAL FACTURA:</td>
                                <td class="text-right">{{ number_format($invoice->total, 2, ',', '.') }} €</td>
                            </tr>
                        @else
                            <tr>
                                <td class="text-muted">Importe Servicios:</td>
                                <td class="text-right font-bold">{{ number_format($invoice->amount, 2, ',', '.') }} €</td>
                            </tr>
                            <tr>
                                <td class="text-muted" style="font-size: 7.5pt;">Impuestos (Sin IVA):</td>
                                <td class="text-right" style="font-size: 7.5pt; color: #059669; font-weight: bold;">0,00 € (Exento)</td>
                            </tr>
                            <tr class="total-highlight-row">
                                <td>TOTAL RECIBO:</td>
                                <td class="text-right">{{ number_format($invoice->total, 2, ',', '.') }} €</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- PIE DE PÁGINA -->
    <div class="footer">
        {{ $company['name'] ?? 'Eventos Musicales' }} &bull; CIF: {{ $company['cif'] ?? 'B-12345678' }} &bull; {{ $company['address'] ?? '' }} &bull; {{ $company['city'] ?? '' }}<br>
        ¡Gracias por confiar en nuestros servicios musicales para vuestro gran día!
    </div>

</body>
</html>
