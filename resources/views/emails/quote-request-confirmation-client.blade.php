<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Presupuesto Recibida</title>
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
            color: #ffffff;
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
            line-height: 1.6;
        }
        .highlight-badge {
            display: inline-block;
            background-color: #f4f4f5;
            color: #18181b;
            border: 1px solid #d4d4d8;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 14px;
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
            margin: 6px 0;
            color: #27272a;
        }
        .event-card-item strong {
            color: #18181b;
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
            padding: 20px;
            text-align: center;
            margin-bottom: 24px;
        }
        .total-label {
            font-size: 11px;
            color: #52525b;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .total-amount {
            font-size: 24px;
            color: #18181b;
            font-weight: 800;
            margin: 4px 0 0;
        }
        .info-step {
            background-color: #fafafa;
            border: 1px solid #e4e4e7;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
            font-size: 13px;
            color: #3f3f46;
        }
        .info-step strong {
            color: #18181b;
            display: block;
            margin-bottom: 4px;
        }
        .contact-box {
            background-color: #ffffff;
            border: 1px solid #e4e4e7;
            border-radius: 8px;
            padding: 18px;
            text-align: center;
            margin-bottom: 16px;
        }
        .contact-box h4 {
            margin: 0 0 8px 0;
            font-size: 13px;
            color: #18181b;
        }
        .contact-phone {
            font-size: 14px;
            font-weight: 700;
            color: #18181b;
            margin: 4px 0;
        }
        .footer {
            background-color: #fafafa;
            padding: 18px 25px;
            text-align: center;
            font-size: 11px;
            color: #71717a;
            border-top: 1px solid #e4e4e7;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- CABECERA -->
        <div class="header">
            <p>{{ $brand['subtitle'] ?: 'Producción Musical, Sonido e Iluminación' }}</p>
            <h1>{{ $companyName }}</h1>
        </div>

        <!-- CONTENIDO PRINCIPAL -->
        <div class="content">
            <div class="highlight-badge">
                Solicitud Registrada
            </div>

            <div class="greeting">
                Estimado/a {{ $event->client ? $event->client->name : 'cliente' }},
            </div>

            <div class="lead">
                Muchas gracias por contactar con nosotros. Hemos registrado vuestra solicitud de presupuesto y estamos verificando la disponibilidad técnica para la fecha indicada.
            </div>

            <!-- FICHA DEL EVENTO -->
            <div class="event-card">
                <div class="event-card-item">
                    <strong>Tipo de Evento:</strong> {{ $typeConfig['name'] ?? 'Evento Musical' }}
                </div>
                <div class="event-card-item">
                    <strong>Fecha:</strong> {{ $event->event_date ? \Carbon\Carbon::parse($event->event_date)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') : 'Por definir' }}
                </div>
                <div class="event-card-item">
                    <strong>Ubicación:</strong> {{ $event->location ?: 'A convenir' }}
                </div>
                @if($event->start_time)
                    <div class="event-card-item">
                        <strong>Horario aproximado:</strong> {{ $event->start_time }} {{ $event->end_time ? 'a ' . $event->end_time : '' }}
                    </div>
                @endif
            </div>

            <!-- SERVICIOS SOLICITADOS -->
            <h3 style="font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #18181b; margin-bottom: 10px;">
                Detalle de Servicios Solicitados
            </h3>

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
                        @php
                            $itemPrice = (float)($item->total ?: ($item->price * ($item->quantity ?: 1)));
                            $isDiscount = $itemPrice < 0;
                        @endphp
                        <tr>
                            <td>
                                <div class="item-name">
                                    &bull; {{ $item->service_name }}
                                </div>
                                @if(!empty($item->description))
                                    <div class="item-desc">{{ $item->description }}</div>
                                @endif
                            </td>
                            <td style="text-align: center; color: #52525b; font-weight: bold;">
                                {{ $item->quantity ?: 1 }}
                            </td>
                            <td style="text-align: right; font-weight: 700; color: #18181b;">
                                @if($isDiscount)
                                    -{{ number_format(abs($itemPrice), 2, ',', '.') }} €
                                @elseif($itemPrice == 0 && (str_contains(mb_strtolower($item->service_name), 'consultar') || str_contains(mb_strtolower($item->service_name), 'extra')))
                                    <span style="color: #52525b; font-size: 11px;">A consultar</span>
                                @else
                                    {{ number_format($itemPrice, 2, ',', '.') }} €
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- TOTAL ESTIMADO -->
            <div class="total-box">
                <div class="total-label">Importe Total Estimado</div>
                <div class="total-amount">{{ number_format($quote->amount, 2, ',', '.') }} €</div>
                <p style="margin: 6px 0 0; font-size: 11px; color: #52525b;">
                    Desplazamiento, montaje e impuestos según condiciones de la propuesta formal.
                </p>
            </div>

            <!-- PRÓXIMOS PASOS -->
            <div class="info-step">
                <strong>Siguientes pasos</strong>
                Nuestro equipo revisará la solicitud y se pondrá en contacto con vosotros a la mayor brevedad para resolver cualquier consulta, ajustar la configuración técnica y remitir el dossier definitivo.
            </div>

            <!-- CONTACTO DIRECTO -->
            <div class="contact-box">
                <h4>Contacto Directo</h4>
                @if(!empty($brand['phone']))
                    <div class="contact-phone">Tel. / WhatsApp: {{ $brand['phone'] }}</div>
                @endif
                @if(!empty($brand['phone_2']))
                    <div class="contact-phone">Tel.: {{ $brand['phone_2'] }}</div>
                @endif
                @if(!empty($brand['email']))
                    <div style="font-size: 12px; color: #52525b; margin-top: 4px;">Email: {{ $brand['email'] }}</div>
                @endif
            </div>

        </div>

        <!-- PIE DE PÁGINA -->
        <div class="footer">
            <p style="margin: 0 0 4px;">
                <strong>{{ $companyName }}</strong>
                @if(!empty($brand['website'])) &bull; {{ $brand['website'] }} @endif
            </p>
            <p style="margin: 0; font-size: 10px; color: #94a3b8;">
                Has recibido este mensaje porque has solicitado una cotización en nuestro sitio web oficial.
            </p>
        </div>
    </div>
</body>
</html>
