<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Lead Recibido</title>
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
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            color: #ffffff;
            padding: 28px 24px;
            text-align: center;
        }
        .badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .header h1 {
            margin: 0;
            font-size: 21px;
            font-weight: 800;
        }
        .header p {
            margin: 6px 0 0;
            color: #cbd5e1;
            font-size: 13px;
        }
        .content {
            padding: 26px 24px;
        }
        .section-title {
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            color: #0f172a;
            letter-spacing: 0.5px;
            margin: 20px 0 8px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 4px;
        }
        .info-grid {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 18px;
        }
        .info-row {
            padding: 7px 0;
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
        .notes-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 14px;
            font-size: 12px;
            color: #334155;
            white-space: pre-line;
            max-height: 250px;
            overflow-y: auto;
            margin-bottom: 22px;
            font-family: monospace;
        }
        .btn-wrapper {
            display: flex;
            gap: 10px;
            justify-content: center;
            text-align: center;
            margin: 25px 0 10px;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 13px 22px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 13px;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.25);
            margin: 4px;
        }
        .btn-whatsapp {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            box-shadow: 0 4px 6px -1px rgba(22, 163, 74, 0.25);
        }
        .footer {
            background-color: #f8fafc;
            padding: 18px;
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
            <div class="badge">{{ $brandInfo['name'] ?? 'General' }} &bull; Entrada Automática</div>
            <h1>¡Nuevo Lead / Contacto Web!</h1>
            <p>{{ $companyName }} &bull; Evento generado automáticamente por correo</p>
        </div>

        <div class="content">
            <div class="section-title">👤 Datos del Contacto</div>
            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">Cliente / Contacto:</span>
                    <span class="info-val">{{ $event->client ? $event->client->name : ($leadData['client_name'] ?? 'N/D') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Teléfono:</span>
                    <span class="info-val">
                        @if($event->client && $event->client->phone)
                            <a href="tel:{{ $event->client->phone }}" style="color: #2563eb; text-decoration: none;">{{ $event->client->phone }}</a>
                        @else
                            <span style="color: #94a3b8; font-weight: normal;">No indicado</span>
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-val">
                        @if($event->client && $event->client->email)
                            <a href="mailto:{{ $event->client->email }}" style="color: #2563eb; text-decoration: none;">{{ $event->client->email }}</a>
                        @else
                            <span style="color: #94a3b8; font-weight: normal;">No indicado</span>
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fecha del Evento:</span>
                    <span class="info-val" style="color: #1d4ed8;">
                        {{ $event->event_date ? $event->event_date->format('d/m/Y') : 'Por determinar' }}
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tipo de Evento:</span>
                    <span class="info-val">{{ ucfirst($event->event_type ?? 'Boda') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Lugar / Finca:</span>
                    <span class="info-val">{{ $event->location ?: 'Por definir' }}</span>
                </div>
            </div>

            <div class="section-title">📩 Mensaje Original del Formulario / Correo</div>
            <div class="notes-box">{{ $leadData['full_body'] ?? $event->notes }}</div>

            <div class="btn-wrapper">
                <a href="{{ $adminUrl }}" class="btn">
                    📋 Ver Ficha y Gestionar
                </a>
                @if($whatsappUrl)
                    <a href="{{ $whatsappUrl }}" target="_blank" class="btn btn-whatsapp">
                        💬 Escribir por WhatsApp
                    </a>
                @endif
            </div>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} {{ $companyName }} &bull; Notificación generada por el importador automático de leads
        </div>
    </div>
</body>
</html>
