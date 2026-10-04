<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $invoice->isReceipt() ? 'Recibo' : 'Factura' }} {{ $invoice->invoice_number }} - {{ $event->name }}</title>
    <style>
        @page {
            margin: 12mm 14mm 12mm 14mm;
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
            margin: 0;
            padding: 0;
            font-size: 8.5pt;
            line-height: 1.4;
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
            border-bottom: 2px solid {{ $invoice->isReceipt() ? '#059669' : '#1e3a8a' }};
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .header-logo {
            max-height: 55px;
            max-width: 190px;
        }
        .company-name {
            font-size: 15pt;
            font-weight: 800;
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
            font-size: 15pt;
            font-weight: 800;
            color: {{ $invoice->isReceipt() ? '#059669' : '#1e3a8a' }};
            margin: 0 0 2px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .doc-badge-meta {
            font-size: 8pt;
            color: #334155;
            line-height: 1.35;
        }

        /* BLOQUES DATOS FISCALES */
        .parties-table {
            width: 100%;
            margin-bottom: 14px;
            border-collapse: separate;
            border-spacing: 8px 0;
        }
        .parties-table td {
            vertical-align: top;
            width: 50%;
            padding: 0;
        }
        .party-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 9px 12px;
            min-height: 105px;
        }
        .party-title {
            font-size: 7.5pt;
            font-weight: 800;
            text-transform: uppercase;
            color: #475569;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }
        .party-name {
            font-size: 9.5pt;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 2px;
        }
        .party-line {
            font-size: 7.5pt;
            color: #475569;
            line-height: 1.35;
        }

        /* TABLA DE CONCEPTOS */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            margin-bottom: 14px;
        }
        .items-table th {
            background-color: {{ $invoice->isReceipt() ? '#065f46' : '#1e3a8a' }};
            color: #ffffff;
            font-size: 7.5pt;
            font-weight: 800;
            text-transform: uppercase;
            padding: 6px 10px;
            text-align: left;
            border: none;
            letter-spacing: 0.5px;
        }
        .items-table th.text-right {
            text-align: right;
        }
        .items-table td {
            padding: 7px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 8pt;
            vertical-align: middle;
        }
        .items-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .item-title {
            font-weight: 700;
            color: #0f172a;
            font-size: 8pt;
        }
        .item-desc {
            font-size: 7pt;
            color: #64748b;
            margin-top: 1px;
            line-height: 1.25;
        }
        .item-discount {
            color: #16a34a;
            font-weight: 700;
        }

        /* TOTALES Y FORMAS DE PAGO */
        .bottom-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
            margin-top: 4px;
        }
        .bottom-table td {
            vertical-align: top;
            padding: 0;
        }
        .payment-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 7.5pt;
            height: 100%;
        }
        .totals-box {
            border: 1.5px solid {{ $invoice->isReceipt() ? '#059669' : '#1e3a8a' }};
            border-radius: 6px;
            background-color: #ffffff;
            overflow: hidden;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }
        .totals-table td {
            padding: 5px 10px;
        }
        .total-highlight-row td {
            background-color: {{ $invoice->isReceipt() ? '#ecfdf5' : '#eff6ff' }};
            border-top: 1.5px solid {{ $invoice->isReceipt() ? '#059669' : '#1e3a8a' }};
            font-size: 10pt;
            font-weight: 900;
            color: {{ $invoice->isReceipt() ? '#065f46' : '#1e3a8a' }};
            padding: 7px 10px;
            white-space: nowrap;
        }

        /* PIE DE PÁGINA */
        .footer {
            margin-top: 20px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            text-align: center;
            font-size: 7pt;
            color: #94a3b8;
            line-height: 1.35;
        }
    </style>
</head>
<body>

    <!-- CABECERA -->
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 55%; vertical-align: middle;">
                @if(!empty($company['logo']) && file_exists($company['logo']))
                    <img src="{{ $company['logo'] }}" class="header-logo" alt="Logo">
                @else
                    <h1 class="company-name">{{ $company['name'] ?? 'Núñez and Son' }}</h1>
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
                    <div class="party-name">{{ $company['name'] ?? 'Núñez and Son' }}</div>
                    <div class="party-line"><strong>CIF/NIF:</strong> {{ $company['cif'] ?? '78902362B' }}</div>
                    <div class="party-line">{{ $company['address'] ?? 'Calle Principal s/n' }}</div>
                    <div class="party-line">{{ $company['city'] ?? 'Logroño (La Rioja)' }}</div>
                    <div class="party-line">
                        <strong>Tel:</strong> {{ $company['phone'] ?? '+34 622 62 47 90' }} 
                        @if(!empty($company['phone_2'])) &bull; {{ $company['phone_2'] }} @endif
                    </div>
                    <div class="party-line"><strong>Email:</strong> {{ $company['email'] ?? 'info@nunezandson.com' }}</div>
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
                    <div class="party-line"><strong>Evento:</strong> {{ $event->name }} ({{ $event->event_date ? $event->event_date->format('d/m/Y') : 'Fecha a convenir' }})</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- TABLA DE CONCEPTOS -->
    <table class="items-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th style="width: 55%;">Descripción del Servicio / Concepto</th>
                <th style="width: 12%; text-align: center;">Cantidad</th>
                <th style="width: 16%; text-align: right;">Precio Unit.</th>
                <th style="width: 17%; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @if($quote && $quote->items && $quote->items->count() > 0)
                @foreach($quote->items as $item)
                    @php
                        $itemTitle = $item->service_name ?: ($item->concept ?: 'Servicio');
                        $itemDesc = $item->description ?: $item->custom_note;
                        $itemQty = (int)($item->quantity ?: 1);
                        $itemUnitPrice = (float)($item->price ?? $item->unit_price ?? 0);
                        $itemTotalPrice = (float)($item->total ?? $item->total_price ?? ($itemUnitPrice * $itemQty));
                        $isDiscount = $itemUnitPrice < 0 || $itemTotalPrice < 0;
                    @endphp
                    <tr>
                        <td>
                            <div class="item-title {{ $isDiscount ? 'item-discount' : '' }}">
                                @if($isDiscount) 🎁 @else • @endif {{ $itemTitle }}
                            </div>
                            @if(!empty($itemDesc))
                                <div class="item-desc">{{ $itemDesc }}</div>
                            @endif
                        </td>
                        <td style="text-align: center; color: #64748b; font-weight: 600;">
                            {{ $itemQty }}
                        </td>
                        <td style="text-align: right; white-space: nowrap;" class="{{ $isDiscount ? 'item-discount' : '' }}">
                            @if($isDiscount)
                                -{{ number_format(abs($itemUnitPrice), 2, ',', '.') }} €
                            @elseif($itemUnitPrice == 0 && (str_contains(mb_strtolower($itemTitle), 'consultar') || str_contains(mb_strtolower($itemTitle), 'extra')))
                                <span style="font-size: 7pt; color: #d97706;">A consultar</span>
                            @else
                                {{ number_format($itemUnitPrice, 2, ',', '.') }} €
                            @endif
                        </td>
                        <td style="text-align: right; font-weight: bold; white-space: nowrap;" class="{{ $isDiscount ? 'item-discount' : '' }}">
                            @if($isDiscount)
                                -{{ number_format(abs($itemTotalPrice), 2, ',', '.') }} €
                            @elseif($itemTotalPrice == 0 && (str_contains(mb_strtolower($itemTitle), 'consultar') || str_contains(mb_strtolower($itemTitle), 'extra')))
                                <span style="font-size: 7pt; color: #d97706;">A consultar</span>
                            @else
                                {{ number_format($itemTotalPrice, 2, ',', '.') }} €
                            @endif
                        </td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td>
                        <div class="item-title">Servicios Musicales, DJ y Audiovisuales</div>
                        <div class="item-desc">
                            Evento: {{ $event->name }} &bull; Lugar: {{ $event->location ?: 'A convenir' }}
                            @if($invoice->notes)
                                &bull; {{ $invoice->notes }}
                            @endif
                        </div>
                    </td>
                    <td style="text-align: center; color: #64748b; font-weight: 600;">1</td>
                    <td style="text-align: right; white-space: nowrap;">{{ number_format($invoice->amount, 2, ',', '.') }} €</td>
                    <td style="text-align: right; font-weight: bold; white-space: nowrap;">{{ number_format($invoice->amount, 2, ',', '.') }} €</td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- TOTALES Y DATOS DE PAGO -->
    <table class="bottom-table" cellpadding="0" cellspacing="0">
        <tr>
            <!-- CAJA DE INFORMACIÓN DE PAGO -->
            <td style="width: 52%;">
                <div class="payment-box">
                    <div style="color: #0f172a; font-size: 8pt; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                        Información de Pago y Datos Bancarios:
                    </div>
                    <div style="line-height: 1.4;">
                        @if(!empty($company['iban']))
                            <div><strong>IBAN Transferencia:</strong> <span style="font-family: monospace; font-size: 7pt;">{{ $company['iban'] }}</span></div>
                        @endif
                        @if(!empty($company['bizum']))
                            <div><strong>Bizum Empresas:</strong> {{ $company['bizum'] }}</div>
                        @endif
                        <div><strong>Concepto:</strong> {{ $invoice->invoice_number }} - {{ $event->name }}</div>
                    </div>

                    @if($invoice->isReceipt())
                        <div style="margin-top: 6px; padding-top: 5px; border-top: 1px dashed #cbd5e1; font-size: 7pt; color: #059669; line-height: 1.3;">
                            ✓ <strong>Recibo oficial justificativo</strong> emitido para constancia del abono de los servicios contratados.
                        </div>
                    @else
                        <div style="margin-top: 6px; padding-top: 5px; border-top: 1px dashed #cbd5e1; font-size: 7pt; color: #475569; line-height: 1.3;">
                            Régimen General de IVA. Factura emitida con arreglo a la normativa tributaria vigente.
                        </div>
                    @endif
                </div>
            </td>

            <!-- TABLA DE TOTALES -->
            <td style="width: 48%;">
                <div class="totals-box">
                    <table class="totals-table">
                        @if($invoice->isInvoice())
                            <tr>
                                <td class="text-muted" style="width: 50%;">Base Imponible:</td>
                                <td class="text-right font-bold" style="width: 50%; white-space: nowrap;">{{ number_format($invoice->amount, 2, ',', '.') }} €</td>
                            </tr>
                            <tr>
                                <td class="text-muted">IVA ({{ number_format($invoice->tax_rate ?: 21, 0) }}%):</td>
                                <td class="text-right font-bold" style="white-space: nowrap;">{{ number_format($invoice->tax, 2, ',', '.') }} €</td>
                            </tr>
                            <tr class="total-highlight-row">
                                <td>TOTAL FACTURA:</td>
                                <td class="text-right">{{ number_format($invoice->total, 2, ',', '.') }} €</td>
                            </tr>
                        @else
                            <tr>
                                <td class="text-muted" style="width: 50%;">Importe Servicios:</td>
                                <td class="text-right font-bold" style="width: 50%; white-space: nowrap;">{{ number_format($invoice->amount, 2, ',', '.') }} €</td>
                            </tr>
                            <tr>
                                <td class="text-muted" style="font-size: 7pt;">Impuestos (Sin IVA):</td>
                                <td class="text-right" style="font-size: 7pt; color: #059669; font-weight: bold; white-space: nowrap;">0,00 € (Exento)</td>
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
        {{ $company['name'] ?? 'Núñez and Son' }} &bull; CIF: {{ $company['cif'] ?? '78902362B' }} &bull; {{ $company['address'] ?? '' }} &bull; {{ $company['city'] ?? '' }}<br>
        ¡Gracias por confiar en nuestros servicios musicales para vuestro gran día!
    </div>

</body>
</html>

