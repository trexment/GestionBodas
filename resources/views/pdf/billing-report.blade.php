<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Informe de Facturación y Control de Ingresos - {{ $company['name'] }}</title>
    <style>
        @page {
            margin: 9mm 10mm 9mm 10mm;
            size: a4 landscape;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #0f172a;
            font-size: 7.5pt;
            line-height: 1.3;
            background-color: #ffffff;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #1e1b4b;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .company-name {
            font-size: 13pt;
            font-weight: bold;
            color: #1e1b4b;
            text-transform: uppercase;
        }
        .company-meta {
            font-size: 7pt;
            color: #64748b;
            margin-top: 2px;
        }
        .report-title {
            font-size: 12pt;
            font-weight: bold;
            color: #1e1b4b;
            text-align: right;
            text-transform: uppercase;
        }
        .report-subtitle {
            font-size: 7pt;
            color: #475569;
            text-align: right;
            margin-top: 2px;
        }
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-bottom: 10px;
        }
        .kpi-box {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 6px 8px;
            background-color: #f8fafc;
            vertical-align: top;
        }
        .kpi-box.green {
            border-color: #86efac;
            background-color: #f0fdf4;
        }
        .kpi-box.amber {
            border-color: #fde047;
            background-color: #fefce8;
        }
        .kpi-label {
            font-size: 6.5pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #475569;
        }
        .kpi-value {
            font-size: 11pt;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        .kpi-value.green { color: #15803d; }
        .kpi-value.amber { color: #b45309; }
        .kpi-sub {
            font-size: 6.5pt;
            color: #64748b;
            margin-top: 2px;
        }
        .section-heading {
            font-size: 8.5pt;
            font-weight: bold;
            color: #1e1b4b;
            text-transform: uppercase;
            margin: 8px 0 4px 0;
            padding-bottom: 2px;
            border-bottom: 1px solid #cbd5e1;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table.data-table th {
            background-color: #1e1b4b;
            color: #ffffff;
            font-size: 6.5pt;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px 5px;
            text-align: left;
            border: 1px solid #1e1b4b;
        }
        table.data-table td {
            padding: 4.5px 5px;
            border-bottom: 1px solid #e2e8f0;
            border-left: 1px solid #f1f5f9;
            border-right: 1px solid #f1f5f9;
            font-size: 7pt;
            vertical-align: top;
        }
        table.data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        table.data-table tr.total-row td {
            background-color: #e2e8f0;
            font-weight: bold;
            border-top: 1.5px solid #0f172a;
            border-bottom: 1.5px solid #0f172a;
            font-size: 7.5pt;
        }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .font-bold { font-weight: bold; }
        .text-green { color: #15803d; font-weight: bold; }
        .text-amber { color: #b45309; font-weight: bold; }
        .text-blue { color: #1d4ed8; font-weight: bold; }
        .text-muted { color: #64748b; font-size: 6.5pt; }
        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 6pt;
            font-weight: bold;
            background-color: #e2e8f0;
            color: #1e293b;
        }
        .badge.paid { background-color: #dcfce7; color: #166534; }
        .badge.partial { background-color: #dbeafe; color: #1e40af; }
        .badge.unpaid { background-color: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
    <!-- CABECERA -->
    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                <div class="company-name">{{ $company['name'] }}</div>
                <div class="company-meta">
                    CIF/NIF: {{ $company['cif'] }} &bull; Tel: {{ $company['phone'] }} &bull; {{ $company['email'] }}
                </div>
            </td>
            <td style="width: 45%;">
                <div class="report-title">Informe de Facturación e Ingresos</div>
                <div class="report-subtitle">
                    Generado el {{ now()->format('d/m/Y H:i') }}
                    @if(($filters['year'] ?? 'all') !== 'all')
                        &bull; Año: {{ $filters['year'] }}
                    @endif
                    @if(($filters['quarter'] ?? 'all') !== 'all')
                        ({{ $filters['quarter'] }}T)
                    @endif
                    @if(($filters['payment_method'] ?? 'all') !== 'all')
                        &bull; Método: {{ strtoupper($filters['payment_method']) }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- RESUMEN EJECUTIVO DE INGRESOS Y MÉTODOS DE PAGO -->
    <table class="kpi-table">
        <tr>
            <td class="kpi-box green" style="width: 22%;">
                <div class="kpi-label">Total Cobrado / Ingresado</div>
                <div class="kpi-value green">{{ number_format($kpis['total_collected'], 2, ',', '.') }} €</div>
                <div class="kpi-sub">
                    Señales: {{ number_format($kpis['total_deposits_collected'], 2, ',', '.') }} € | Resto: {{ number_format($kpis['total_finals_collected'], 2, ',', '.') }} €
                </div>
            </td>
            <td class="kpi-box amber" style="width: 18%;">
                <div class="kpi-label">Pendiente de Cobrar</div>
                <div class="kpi-value amber">{{ number_format($kpis['total_pending'], 2, ',', '.') }} €</div>
                <div class="kpi-sub">
                    Volumen Eventos: {{ number_format($kpis['total_agreed'], 2, ',', '.') }} € ({{ $kpis['events_count'] }} ev.)
                </div>
            </td>
            <td class="kpi-box" style="width: 34%;">
                <div class="kpi-label">Ingresos Cobrados por Método de Pago</div>
                <div class="kpi-sub" style="margin-top: 4px; color: #0f172a; font-size: 7pt;">
                    <strong>Bizum:</strong> {{ number_format($kpis['by_method']['bizum']['amount'], 2, ',', '.') }} € ({{ $kpis['by_method']['bizum']['count'] }})
                    &nbsp;|&nbsp;
                    <strong>Transferencia:</strong> {{ number_format($kpis['by_method']['transfer']['amount'], 2, ',', '.') }} € ({{ $kpis['by_method']['transfer']['count'] }})
                    <br>
                    <strong>Efectivo:</strong> {{ number_format($kpis['by_method']['cash']['amount'], 2, ',', '.') }} € ({{ $kpis['by_method']['cash']['count'] }})
                    &nbsp;|&nbsp;
                    <strong>Tarjeta:</strong> {{ number_format($kpis['by_method']['card']['amount'], 2, ',', '.') }} € ({{ $kpis['by_method']['card']['count'] }})
                </div>
            </td>
            <td class="kpi-box" style="width: 26%;">
                <div class="kpi-label">Desglose Facturas (IVA) vs Recibos</div>
                <div class="kpi-sub" style="margin-top: 4px; color: #0f172a; font-size: 7pt;">
                    <strong>Facturas ({{ $kpis['tax_summary']['invoices_count'] }}):</strong> {{ number_format($kpis['tax_summary']['invoices_total'], 2, ',', '.') }} €
                    (Base: {{ number_format($kpis['tax_summary']['invoices_base'], 2, ',', '.') }} € + IVA: {{ number_format($kpis['tax_summary']['invoices_tax'], 2, ',', '.') }} €)<br>
                    <strong>Recibos / Señales ({{ $kpis['tax_summary']['receipts_count'] }}):</strong> {{ number_format($kpis['tax_summary']['receipts_total'], 2, ',', '.') }} €
                </div>
            </td>
        </tr>
    </table>

    <!-- TABLA 1: DESGLOSE POR EVENTOS (QUIÉN HA PAGADO, FECHA, MÉTODO Y PENDIENTE) -->
    <div class="section-heading">1. Control de Pagos por Evento ({{ count($eventsRows) }} eventos)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 18%;">Evento / Fecha</th>
                <th style="width: 15%;">Cliente / DNI</th>
                <th style="width: 8%;" class="text-right">Total Evento</th>
                <th style="width: 17%;">1º Pago: Señal (Fecha y Método)</th>
                <th style="width: 17%;">2º Pago: Resto / Final (Fecha y Método)</th>
                <th style="width: 8%;" class="text-right">Total Cobrado</th>
                <th style="width: 8%;" class="text-right">Pendiente</th>
                <th style="width: 9%;">Facturas / Recibos</th>
            </tr>
        </thead>
        <tbody>
            @forelse($eventsRows as $row)
                @php
                    $ev = $row['event'];
                    $fb = $row['fb'];
                    $docs = $ev->invoices->where('status', '!=', 'cancelled')->pluck('invoice_number')->implode(', ');
                @endphp
                <tr>
                    <td>
                        <div class="font-bold">{{ $ev->name }}</div>
                        <div class="text-muted">
                            Fecha: {{ $ev->event_date ? $ev->event_date->format('d/m/Y') : 'Sin fecha' }} &bull; {{ $ev->brand_label }}
                        </div>
                    </td>
                    <td>
                        <div class="font-bold">{{ $ev->client?->name ?? 'Sin cliente' }}</div>
                        <div class="text-muted">
                            {{ $ev->client?->dni ?: ($ev->partner_dni ?: '') }}
                            @if($ev->client_contact_phone) &bull; {{ $ev->client_contact_phone }} @endif
                        </div>
                    </td>
                    <td class="text-right font-bold">
                        {{ number_format($fb['quote_total'], 2, ',', '.') }} €
                    </td>
                    <td>
                        @if($fb['deposit_paid'] && $fb['deposit_amount'] > 0)
                            <span class="text-green">{{ number_format($fb['deposit_amount'], 2, ',', '.') }} €</span>
                            — <strong>{{ $fb['deposit_method_label'] }}</strong>
                            @if($fb['deposit_date'])
                                <span class="text-muted">({{ \Carbon\Carbon::parse($fb['deposit_date'])->format('d/m/Y') }})</span>
                            @endif
                        @else
                            <span class="text-muted">Pendiente</span>
                        @endif
                    </td>
                    <td>
                        @if($fb['final_paid_amount'] > 0)
                            <span class="text-blue">{{ number_format($fb['final_paid_amount'], 2, ',', '.') }} €</span>
                            @if($fb['final_method_label'])
                                — <strong>{{ $fb['final_method_label'] }}</strong>
                            @endif
                            @if($fb['final_paid_date'])
                                <span class="text-muted">({{ \Carbon\Carbon::parse($fb['final_paid_date'])->format('d/m/Y') }})</span>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-right text-green">
                        {{ number_format($fb['total_paid'], 2, ',', '.') }} €
                    </td>
                    <td class="text-right {{ $fb['pending_amount'] > 0 ? 'text-amber' : 'text-muted' }}">
                        {{ $fb['pending_amount'] > 0 ? number_format($fb['pending_amount'], 2, ',', '.') . ' €' : '0,00 €' }}
                    </td>
                    <td>
                        <span class="text-muted">{{ $docs ?: '—' }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted">No hay eventos para los filtros seleccionados.</td>
                </tr>
            @endforelse
            @if(count($eventsRows) > 0)
                <tr class="total-row">
                    <td colspan="2">TOTALES ({{ count($eventsRows) }} EVENTOS)</td>
                    <td class="text-right">{{ number_format($kpis['total_agreed'], 2, ',', '.') }} €</td>
                    <td>Señales: {{ number_format($kpis['total_deposits_collected'], 2, ',', '.') }} €</td>
                    <td>Liquidaciones: {{ number_format($kpis['total_finals_collected'], 2, ',', '.') }} €</td>
                    <td class="text-right text-green">{{ number_format($kpis['total_collected'], 2, ',', '.') }} €</td>
                    <td class="text-right text-amber">{{ number_format($kpis['total_pending'], 2, ',', '.') }} €</td>
                    <td></td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- TABLA 2: LIBRO CRONOLÓGICO DE COBROS, SEÑALES, RECIBOS Y FACTURAS -->
    @if(count($ledgerRows) > 0)
        <div class="section-heading" style="margin-top: 12px;">2. Libro Cronológico de Cobros, Recibos y Facturas ({{ count($ledgerRows) }} movimientos)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 9%;">Fecha Cobro</th>
                    <th style="width: 12%;">Nº Documento</th>
                    <th style="width: 14%;">Concepto</th>
                    <th style="width: 18%;">Evento</th>
                    <th style="width: 16%;">Cliente / DNI</th>
                    <th style="width: 12%;">Método de Pago</th>
                    <th style="width: 9%;" class="text-right">Base / IVA</th>
                    <th style="width: 10%;" class="text-right">Importe Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ledgerRows as $item)
                    <tr>
                        <td class="font-bold">{{ $item['date'] ? \Carbon\Carbon::parse($item['date'])->format('d/m/Y') : '—' }}</td>
                        <td class="font-bold">{{ $item['document_number'] }}</td>
                        <td>{{ $item['concept'] }}</td>
                        <td>
                            {{ $item['event_name'] }}
                            <span class="text-muted">({{ $item['event_date'] ? \Carbon\Carbon::parse($item['event_date'])->format('d/m/Y') : '-' }})</span>
                        </td>
                        <td>
                            {{ $item['client_name'] }}
                            @if(!empty($item['client_dni'])) <span class="text-muted">({{ $item['client_dni'] }})</span> @endif
                        </td>
                        <td class="font-bold">{{ $item['method_label'] }}</td>
                        <td class="text-right text-muted">
                            @if(($item['document_type'] ?? '') === 'factura')
                                {{ number_format($item['base_amount'], 2, ',', '.') }} € + {{ number_format($item['tax_amount'], 2, ',', '.') }} €
                            @else
                                Sin IVA
                            @endif
                        </td>
                        <td class="text-right font-bold text-green">
                            {{ number_format($item['total'], 2, ',', '.') }} €
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
