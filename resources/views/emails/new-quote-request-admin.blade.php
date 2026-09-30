<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Solicitud de Presupuesto</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f1f5f9;
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
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
            color: #ffffff;
            padding: 30px 25px;
            text-align: center;
        }
        .badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.25);
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
        }
        .header p {
            margin: 6px 0 0;
            color: #cbd5e1;
            font-size: 13px;
        }
        .content {
            padding: 30px 25px;
        }
        .section-title {
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            color: #4338ca;
            letter-spacing: 0.5px;
            margin: 22px 0 8px;
            border-bottom: 2px solid #e0e7ff;
            padding-bottom: 4px;
        }
        .info-grid {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
        }
        .info-row {
            padding: 6px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #64748b;
            display: inline-block;
            width: 140px;
        }
        .info-val {
            font-weight: 700;
            color: #0f172a;
        }
        .service-list {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 18px;
            list-style: none;
            margin: 0 0 20px 0;
        }
        .service-list li {
            padding: 5px 0;
            font-size: 13px;
            color: #334155;
            font-weight: 600;
            border-bottom: 1px dashed #e2e8f0;
        }
        .service-list li:last-child {
            border-bottom: none;
        }
        .total-box {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid #86efac;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            margin: 20px 0;
        }
        .total-label {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            color: #166534;
            letter-spacing: 0.5px;
        }
        .total-amount {
            font-size: 26px;
            font-weight: 900;
            color: #15803d;
            margin-top: 2px;
        }
        .notes-box {
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 12px;
            padding: 14px;
            font-size: 12px;
            color: #92400e;
            white-space: pre-line;
            margin-bottom: 25px;
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
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
        }
        .footer {
            background-color: #f8fafc;
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
            <div class="badge">{{ $typeConfig['name'] ?? 'Celebración' }}</div>
            <h1>¡Nueva Solicitud de Presupuesto!</h1>
            <p>{{ $companyName }} &bull; Entrada recibida a través del Cotizador Online</p>
        </div>

        <div class="content">
            <div class="section-title">👤 Datos del Cliente & Contacto</div>
            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">Cliente / Pareja:</span>
                    <span class="info-val">{{ $event->client ? $event->client->name : 'N/D' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Teléfono / WhatsApp:</span>
                    <span class="info-val"><a href="tel:{{ $event->client ? $event->client->phone : '' }}" style="color: #4f46e5; text-decoration: none;">{{ $event->client ? $event->client->phone : 'N/D' }}</a></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-val"><a href="mailto:{{ $event->client ? $event->client->email : '' }}" style="color: #4f46e5; text-decoration: none;">{{ $event->client ? $event->client->email : 'N/D' }}</a></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fecha del Evento:</span>
                    <span class="info-val" style="color: #4338ca;">{{ \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Lugar / Finca:</span>
                    <span class="info-val">{{ $event->location ?: 'No especificado' }}</span>
                </div>
            </div>

            <div class="section-title">🎵 Fases y Servicios Solicitados</div>
            <ul class="service-list">
                @foreach($requestedServices as $servText)
                    <li>{{ $servText }}</li>
                @endforeach
            </ul>

            <div class="total-box">
                <div class="total-label">Estimación Estimada Inicial (Propuesta #{{ $quote->id }})</div>
                <div class="total-amount">{{ number_format($quote->amount, 2, ',', '.') }} €</div>
            </div>

            @if(!empty($event->notes))
                <div class="section-title">📝 Observaciones del Formulario</div>
                <div class="notes-box">{{ $event->notes }}</div>
            @endif

            <div class="btn-wrapper">
                <a href="{{ $adminUrl }}" class="btn">
                    👉 Abrir Ficha del Evento en el Panel
                </a>
            </div>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} {{ $companyName }} &bull; Notificaciones automáticas
        </div>
    </div>
</body>
</html>
