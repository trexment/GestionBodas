<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Copia de tu Contrato Firmado</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 20px; font-size: 14px; line-height: 1.5; }
        .card { max-width: 620px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #ffffff; padding: 30px 24px; text-align: center; }
        .content { padding: 28px 24px; }
        .info-table { width: 100%; border-collapse: collapse; margin: 18px 0; }
        .info-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        .info-table td.label { font-weight: bold; color: #64748b; width: 38%; }
        .btn { display: inline-block; background-color: #4f46e5; color: #ffffff !important; text-decoration: none; padding: 13px 28px; font-weight: bold; border-radius: 12px; text-align: center; margin: 18px 0 8px 0; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25); }
        .btn-portal { display: inline-block; background-color: #059669; color: #ffffff !important; text-decoration: none; padding: 10px 20px; font-weight: bold; border-radius: 10px; text-align: center; font-size: 12px; }
        .footer { background: #f8fafc; color: #94a3b8; font-size: 11px; text-align: center; padding: 20px; border-top: 1px solid #e2e8f0; }
        .highlight-box { background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; padding: 14px 18px; margin: 16px 0; color: #065f46; font-size: 13px; }
        .attachment-badge { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 10px 14px; margin-top: 16px; color: #1e40af; font-size: 12px; display: flex; align-items: center; gap: 8px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1 style="margin: 0; font-size: 22px; font-weight: 800;">🎉 ¡Contrato Formalizado con Éxito!</h1>
            <p style="margin: 6px 0 0 0; font-size: 14px; opacity: 0.95;">{{ $brand['name'] ?? 'Núñez and Son' }}</p>
        </div>

        <div class="content">
            <p style="font-size: 15px; margin-top: 0;">
                Hola <strong>{{ $contract->client_name_signed }}</strong>,
            </p>
            <p style="color: #475569; line-height: 1.6;">
                Te confirmamos que la firma electrónica de tu contrato para el evento <strong>{{ $event ? $event->name : 'Evento' }}</strong> se ha registrado correctamente con plena validez legal.
            </p>

            <div class="highlight-box">
                📄 <strong>Adjunto en este correo:</strong> Encontrarás el documento <strong>PDF oficial con tu firma estampada</strong>, sello de tiempo, desglose de servicios contratados y cláusulas aceptadas.
            </div>

            <table class="info-table">
                <tr>
                    <td class="label">📅 Evento:</td>
                    <td><strong>{{ $event ? $event->name : 'N/A' }}</strong></td>
                </tr>
                <tr>
                    <td class="label">🗓️ Fecha de Celebración:</td>
                    <td><strong>{{ ($event && $event->event_date) ? $event->event_date->format('d/m/Y') : 'Por definir' }}</strong></td>
                </tr>
                <tr>
                    <td class="label">📍 Lugar / Finca:</td>
                    <td>{{ $event?->location ?? 'Por determinar' }}</td>
                </tr>
                <tr>
                    <td class="label">⏰ Horario Estimado:</td>
                    <td>De {{ $event ? $event->effective_start_time : 'Inicio' }} a {{ $event ? $event->effective_end_time : 'Fin' }}</td>
                </tr>
                <tr>
                    <td class="label">💶 Importe Total:</td>
                    <td><strong style="color: #4f46e5; font-size: 15px;">{{ number_format($renderedData['amount'] ?? 0, 2, ',', '.') }} €</strong></td>
                </tr>
                <tr>
                    <td class="label">✍️ Firmado Por:</td>
                    <td>{{ $contract->client_name_signed }} (DNI: {{ $contract->client_dni_signed }})</td>
                </tr>
                <tr>
                    <td class="label">🕒 Fecha de Firma:</td>
                    <td>{{ $contract->signed_at ? $contract->signed_at->format('d/m/Y H:i:s') : now()->format('d/m/Y H:i:s') }}</td>
                </tr>
            </table>

            <div style="text-align: center; margin: 24px 0 10px 0;">
                <a href="{{ url('/contrato/' . $contract->token) }}" class="btn">
                    Ver / Descargar Contrato en Línea &rarr;
                </a>
            </div>

            @if($event && $event->token)
                <div style="background-color: #f1f5f9; border-radius: 12px; padding: 14px; text-align: center; margin-top: 18px;">
                    <p style="margin: 0 0 8px 0; font-size: 12px; color: #475569; font-weight: bold;">
                        🎵 ¿Quieres gestionar la música y momentos de tu boda/evento?
                    </p>
                    <a href="{{ url('/invitado/evento/' . $event->token) }}" class="btn-portal">
                        Acceder a tu Portal Musical &rarr;
                    </a>
                </div>
            @endif

            <p style="font-size: 12px; color: #64748b; margin-top: 24px; line-height: 1.5;">
                Si tienes cualquier duda o necesitas modificar algún detalle, puedes responder a este correo o contactarnos al teléfono <strong>{{ $brand['phone'] ?? '+34 622 634 790' }}</strong>.
            </p>
        </div>

        <div class="footer">
            {{ $brand['name'] ?? 'Núñez and Son' }} &bull; {{ $brand['email'] ?? 'info@nunezandson.com' }} &bull; {{ $brand['phone'] ?? '' }}<br>
            Guardamos este documento digitalmente para garantizar la seguridad y transparencia de la prestación del servicio.
        </div>
    </div>
</body>
</html>
