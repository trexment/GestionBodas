<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Propuesta de Presupuesto</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 25px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            border: 1px solid #e4e4e7;
        }
        .header {
            background-color: #18181b;
            color: #ffffff;
            padding: 32px 28px;
            border-bottom: 2px solid #eab308;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.3px;
        }
        .header p {
            margin: 6px 0 0;
            color: #eab308;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
        .content {
            padding: 30px 28px;
        }
        .greeting {
            font-size: 16px;
            font-weight: 700;
            color: #18181b;
            margin-bottom: 12px;
        }
        .lead {
            font-size: 14px;
            color: #3f3f46;
            margin-bottom: 22px;
        }
        .event-card {
            background-color: #fafafa;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 24px;
            border: 1px solid #e4e4e7;
        }
        .event-card-item {
            font-size: 13px;
            margin: 4px 0;
            color: #27272a;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 13px;
        }
        .items-table th {
            text-align: left;
            padding: 10px 12px;
            background-color: #fafafa;
            color: #52525b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #18181b;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #f4f4f5;
            color: #18181b;
        }
        .item-name {
            font-weight: 600;
            color: #18181b;
        }
        .item-desc {
            font-size: 11px;
            color: #71717a;
            margin-top: 2px;
        }
        .total-box {
            background-color: #fafafa;
            border: 1px solid #e4e4e7;
            border-top: 2px solid #18181b;
            border-radius: 8px;
            padding: 18px;
            margin: 24px 0;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            margin-bottom: 6px;
            color: #3f3f46;
        }
        .total-row.final {
            font-size: 18px;
            font-weight: 700;
            color: #18181b;
            margin-bottom: 0;
        }
        .btn-wrapper {
            text-align: center;
            margin: 28px 0 12px;
        }
        .btn {
            display: inline-block;
            background-color: #18181b;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 0.3px;
        }
        .contact-box {
            margin-top: 26px;
            padding: 16px;
            background-color: #fafafa;
            border-radius: 8px;
            font-size: 12px;
            color: #52525b;
            text-align: center;
            border: 1px solid #e4e4e7;
        }
        .footer {
            background-color: #fafafa;
            padding: 18px;
            text-align: center;
            font-size: 11px;
            color: #71717a;
            border-top: 1px solid #e4e4e7;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <p>Propuesta Económica y de Producción</p>
            <h1>{{ $companyName }}</h1>
        </div>

        <div class="content">
            <div class="greeting">
                Estimado/a {{ $event->client ? $event->client->name : 'cliente' }},
            </div>
            <p class="lead">
                Adjuntamos la propuesta económica detallada para vuestro evento, junto con el dossier completo en formato PDF adjunto a este correo.
            </p>

            <div class="event-card">
                <div class="event-card-item"><strong>Fecha del Evento:</strong> {{ $event->event_date ? \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') : 'Por determinar' }}</div>
                <div class="event-card-item"><strong>Ubicación:</strong> {{ $event->location ?: 'A determinar' }}</div>
                <div class="event-card-item"><strong>Referencia:</strong> #PRE-{{ str_pad($quote->id, 5, '0', STR_PAD_LEFT) }}</div>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Concepto / Servicio</th>
                        <th style="text-align: center; width: 60px;">Uds.</th>
                        <th style="text-align: right; width: 90px;">Importe</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quote->items as $item)
                        <tr>
                            <td>
                                <div class="item-name">{{ $item->service_name ?: $item->concept }}</div>
                                @if($item->description)
                                    <div class="item-desc">{{ $item->description }}</div>
                                @endif
                            </td>
                            <td style="text-align: center; font-weight: 600;">
                                {{ $item->quantity ?: 1 }}
                            </td>
                            <td style="text-align: right; font-weight: 700; color: #18181b;">
                                @if($item->price == 0 && (str_contains(strtolower($item->service_name), 'consultar') || str_contains(strtolower($item->description), 'consultar')))
                                    <span style="font-size: 11px; color: #52525b;">A consultar</span>
                                @else
                                    {{ number_format($item->total, 2, ',', '.') }} €
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="total-box">
                <div class="total-row final">
                    <span>Importe Total del Presupuesto:</span>
                    <span>{{ number_format($quote->amount, 2, ',', '.') }} €</span>
                </div>
                @if($quote->deposit_amount > 0)
                    <div class="total-row" style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #e4e4e7;">
                        <span>Señal de reserva para bloqueo de fecha:</span>
                        <span style="font-weight: 700;">{{ number_format($quote->deposit_amount, 2, ',', '.') }} €</span>
                    </div>
                @endif
            </div>

            <p style="font-size: 12px; color: #52525b; line-height: 1.5; margin: 16px 0;">
                <strong>Vigencia de la propuesta:</strong> Esta cotización y el bloqueo provisional de la fecha tienen una validez de <strong>{{ $quote->validity_days }} días naturales</strong> (hasta el <strong>{{ $quote->expires_at ? $quote->expires_at->format('d/m/Y') : '' }}</strong>).
            </p>

            <div class="btn-wrapper">
                <a href="{{ $quotePortalUrl ?? $quote->public_url }}" class="btn">
                    Consultar y Responder Propuesta Online
                </a>
                <br>
                <a href="{{ $portalUrl }}" style="display: inline-block; margin-top: 12px; font-size: 12px; font-weight: 600; color: #3f3f46; text-decoration: underline;">
                    Acceder al área de planificación musical del evento
                </a>
            </div>

            <div class="contact-box">
                Para cualquier consulta o ajuste sobre esta propuesta, estamos a vuestra disposición:<br>
                @if($companyPhone)<strong>Teléfono / WhatsApp:</strong> {{ $companyPhone }} &bull; @endif
                @if($companyEmail)<strong>Email:</strong> {{ $companyEmail }}@endif
            </div>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} {{ $companyName }} &bull; Todos los derechos reservados.
        </div>
    </div>
</body>
</html>
