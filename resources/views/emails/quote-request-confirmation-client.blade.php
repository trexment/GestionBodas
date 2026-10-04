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
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #ffffff;
            padding: 35px 25px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            color: #ffffff;
        }
        .header p {
            margin: 6px 0 0;
            color: #eab308;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .content {
            padding: 30px 25px;
        }
        .greeting {
            font-size: 17px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 12px;
        }
        .lead {
            font-size: 14px;
            color: #475569;
            margin-bottom: 22px;
            line-height: 1.6;
        }
        .highlight-badge {
            display: inline-block;
            background-color: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 14px;
        }
        .event-card {
            background-color: #f8fafc;
            border-radius: 12px;
            padding: 18px 20px;
            margin-bottom: 25px;
            border-left: 4px solid #eab308;
            border: 1px solid #e2e8f0;
            border-left-width: 4px;
            border-left-color: #eab308;
        }
        .event-card-item {
            font-size: 13px;
            margin: 6px 0;
            color: #334155;
        }
        .event-card-item strong {
            color: #0f172a;
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
            background: linear-gradient(135deg, #fefce8 0%, #fef08a 100%);
            border: 1px solid #fde047;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 25px;
        }
        .total-label {
            font-size: 12px;
            color: #854d0e;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .total-amount {
            font-size: 26px;
            color: #713f12;
            font-weight: 900;
            margin: 4px 0 0;
        }
        .info-step {
            background-color: #f1f5f9;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 25px;
            font-size: 13px;
            color: #334155;
        }
        .info-step strong {
            color: #0f172a;
            display: block;
            margin-bottom: 4px;
        }
        .contact-box {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px;
            text-align: center;
            margin-bottom: 20px;
        }
        .contact-box h4 {
            margin: 0 0 8px 0;
            font-size: 14px;
            color: #0f172a;
        }
        .contact-phone {
            font-size: 14px;
            font-weight: 800;
            color: #4f46e5;
            margin: 4px 0;
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px 25px;
            text-align: center;
            font-size: 11px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- CABECERA -->
        <div class="header">
            <h1>{{ $companyName }}</h1>
            <p>{{ $brand['subtitle'] ?: 'DJ, Sonido & Producción de Eventos' }}</p>
        </div>

        <!-- CONTENIDO PRINCIPAL -->
        <div class="content">
            <div class="highlight-badge">
                ✅ Solicitud Registrada con Éxito
            </div>

            <div class="greeting">
                ¡Hola, {{ $event->client ? $event->client->name : 'Hola' }}!
            </div>

            <div class="lead">
                Muchas gracias por ponerte en contacto con nosotros. Hemos recibido correctamente tu solicitud de presupuesto y estamos revisando los detalles y la disponibilidad para la fecha de tu evento.
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
                    <strong>Lugar / Espacio:</strong> {{ $event->location ?: 'A convenir' }}
                </div>
                @if($event->start_time)
                    <div class="event-card-item">
                        <strong>Horario aproximado:</strong> {{ $event->start_time }} {{ $event->end_time ? 'a ' . $event->end_time : '' }}
                    </div>
                @endif
            </div>

            <!-- SERVICIOS SOLICITADOS -->
            <h3 style="font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 10px;">
                📋 Resumen de Servicios Solicitados:
            </h3>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Concepto / Servicio</th>
                        <th style="text-align: center; width: 60px;">Cant.</th>
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
                                <div class="item-name" style="{{ $isDiscount ? 'color: #059669;' : '' }}">
                                    @if($isDiscount) 🎁 @else • @endif {{ $item->service_name }}
                                </div>
                                @if(!empty($item->description))
                                    <div class="item-desc">{{ $item->description }}</div>
                                @endif
                            </td>
                            <td style="text-align: center; color: #64748b; font-weight: bold;">
                                {{ $item->quantity ?: 1 }}
                            </td>
                            <td style="text-align: right; font-weight: 800; {{ $isDiscount ? 'color: #059669;' : '' }}">
                                @if($isDiscount)
                                    -{{ number_format(abs($itemPrice), 2, ',', '.') }} €
                                @elseif($itemPrice == 0 && (str_contains(mb_strtolower($item->service_name), 'consultar') || str_contains(mb_strtolower($item->service_name), 'extra')))
                                    <span style="color: #d97706; font-size: 11px;">A consultar</span>
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
                <div class="total-label">Presupuesto Estimado</div>
                <div class="total-amount">{{ number_format($quote->amount, 2, ',', '.') }} €</div>
                <p style="margin: 4px 0 0; font-size: 11px; color: #a16207;">
                    IVA y desplazamiento incluidos según condiciones indicadas en la propuesta formal.
                </p>
            </div>

            <!-- PRÓXIMOS PASOS -->
            <div class="info-step">
                <strong>¿Cuáles son los siguientes pasos?</strong>
                Nuestro equipo comprobará la agenda de fechas y se pondrá en contacto contigo muy pronto (vía WhatsApp, teléfono o email) para resolver cualquier duda, ajustar detalles a vuestra medida y formalizar la reserva.
            </div>

            <!-- CONTACTO DIRECTO -->
            <div class="contact-box">
                <h4>¿Tienes alguna duda urgente o necesitas consultarnos algo ya?</h4>
                @if(!empty($brand['phone']))
                    <div class="contact-phone">📞 {{ $brand['phone'] }}</div>
                @endif
                @if(!empty($brand['phone_2']))
                    <div class="contact-phone">📞 {{ $brand['phone_2'] }}</div>
                @endif
                @if(!empty($brand['email']))
                    <div style="font-size: 12px; color: #64748b; margin-top: 4px;">✉️ {{ $brand['email'] }}</div>
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
