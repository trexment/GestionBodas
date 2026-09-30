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
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: #ffffff;
            padding: 35px 25px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
        }
        .header p {
            margin: 6px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 30px 25px;
        }
        .greeting {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
        }
        .lead {
            font-size: 14px;
            color: #475569;
            margin-bottom: 22px;
        }
        .event-card {
            background-color: #f1f5f9;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 25px;
            border-left: 4px solid #4f46e5;
        }
        .event-card-item {
            font-size: 13px;
            margin: 4px 0;
            color: #334155;
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
            background-color: #f8fafc;
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }
        .item-name {
            font-weight: 700;
            color: #0f172a;
        }
        .item-desc {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }
        .total-box {
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
            border: 1px solid #c7d2fe;
            border-radius: 12px;
            padding: 18px;
            margin: 25px 0;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 6px;
            color: #3730a3;
        }
        .total-row.final {
            font-size: 18px;
            font-weight: 800;
            color: #312e81;
            border-top: 2px solid #c7d2fe;
            padding-top: 8px;
            margin-top: 8px;
            margin-bottom: 0;
        }
        .btn-wrapper {
            text-align: center;
            margin: 30px 0 10px;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
        }
        .contact-box {
            margin-top: 30px;
            padding: 16px;
            background-color: #f8fafc;
            border-radius: 12px;
            font-size: 12px;
            color: #64748b;
            text-align: center;
            border: 1px dashed #cbd5e1;
        }
        .footer {
            background-color: #f1f5f9;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $companyName }}</h1>
            <p>Propuesta de Servicios & Presupuesto</p>
        </div>

        <div class="content">
            <div class="greeting">
                ¡Hola {{ $event->client ? $event->client->name : 'estimado/a cliente' }}!
            </div>
            <p class="lead">
                Te enviamos la propuesta de presupuesto detallada para tu celebración. Adjunto a este correo encontrarás el documento oficial en formato PDF.
            </p>

            <div class="event-card">
                <div class="event-card-item"><strong>📅 Fecha del Evento:</strong> {{ \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') }}</div>
                <div class="event-card-item"><strong>📍 Lugar / Finca:</strong> {{ $event->location ?: 'A determinar' }}</div>
                <div class="event-card-item"><strong>📋 Referencia de Propuesta:</strong> #{{ $quote->id }}</div>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Concepto / Servicio</th>
                        <th style="text-align: center; width: 60px;">Cant.</th>
                        <th style="text-align: right; width: 90px;">Total</th>
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
                            <td style="text-align: right; font-weight: 700; color: {{ $item->total < 0 ? '#15803d' : '#0f172a' }};">
                                @if($item->price == 0 && (str_contains(strtolower($item->service_name), 'consultar') || str_contains(strtolower($item->description), 'consultar')))
                                    <span style="font-size: 11px; color: #d97706;">A consultar</span>
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
                    <span>Total Presupuesto:</span>
                    <span>{{ number_format($quote->amount, 2, ',', '.') }} €</span>
                </div>
                @if($quote->deposit_amount > 0)
                    <div class="total-row" style="margin-top: 6px; font-size: 12px; color: #4338ca;">
                        <span>Señal de reserva para bloquear fecha:</span>
                        <span style="font-weight: 700;">{{ number_format($quote->deposit_amount, 2, ',', '.') }} €</span>
                    </div>
                @endif
            </div>

            <div class="btn-wrapper">
                <a href="{{ $portalUrl }}" class="btn">
                    ✨ Acceder a mi Portal de Evento
                </a>
            </div>

            <div class="contact-box">
                ¿Tienes cualquier duda o quieres realizar algún ajuste? Estamos a tu completa disposición:<br>
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
