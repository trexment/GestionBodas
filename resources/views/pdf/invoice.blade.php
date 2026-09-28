<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Factura {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 14px; color: #333; }
        .invoice-box { max-w-full; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, .15); }
        .header { width: 100%; margin-bottom: 40px; }
        .header td { padding: 5px; vertical-align: top; }
        .title { font-size: 45px; font-weight: bold; color: #333; }
        .company-details { text-align: right; }
        .client-details { margin-top: 40px; margin-bottom: 40px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        .table th { background: #f8f8f8; font-weight: bold; }
        .totals { text-align: right; margin-top: 20px; }
        .totals-row { margin-bottom: 10px; }
        .totals-label { font-weight: bold; display: inline-block; width: 150px; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <table class="header">
            <tr>
                <td class="title">FACTURA</td>
                <td class="company-details">
                    <strong>Eventos Musicales DJ</strong><br>
                    NIF: B00000000<br>
                    info@eventosmusicales.com<br>
                    Tel: 600 000 000
                </td>
            </tr>
        </table>

        <div class="client-details">
            <strong>Facturar a:</strong><br>
            {{ $client ? $client->name : 'Cliente General' }}<br>
            {{ $client ? $client->email : '' }}<br>
            <br>
            <strong>Nº Factura:</strong> {{ $invoice->invoice_number }}<br>
            <strong>Fecha Emisión:</strong> {{ $invoice->issue_date->format('d/m/Y') }}<br>
            <strong>Evento:</strong> {{ $event->name }} ({{ $event->event_date->format('d/m/Y') }})
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Descripción de Servicios</th>
                    <th style="text-align: right;">Importe</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Servicios de DJ / Sonido e Iluminación para el evento: {{ $event->name }}<br>
                    <small>Lugar: {{ $event->location }}</small>
                    </td>
                    <td style="text-align: right;">{{ number_format($invoice->amount, 2, ',', '.') }} €</td>
                </tr>
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-row">
                <span class="totals-label">Base Imponible:</span>
                <span>{{ number_format($invoice->amount, 2, ',', '.') }} €</span>
            </div>
            <div class="totals-row">
                <span class="totals-label">IVA:</span>
                <span>{{ number_format($invoice->tax, 2, ',', '.') }} €</span>
            </div>
            <div class="totals-row" style="font-size: 18px; color: #1a202c; border-top: 2px solid #333; padding-top: 10px; display: inline-block;">
                <span class="totals-label">TOTAL:</span>
                <strong>{{ number_format($invoice->total, 2, ',', '.') }} €</strong>
            </div>
        </div>
        
        <div style="margin-top: 50px; text-align: center; color: #777; font-size: 12px;">
            Gracias por confiar en nuestros servicios musicales.<br>
            El pago de esta factura vence en 30 días o antes de la fecha del evento.
        </div>
    </div>
</body>
</html>
