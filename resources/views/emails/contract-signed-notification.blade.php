<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Contrato Firmado</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 20px; font-size: 14px; line-height: 1.5; }
        .card { max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #059669 0%, #0d9488 100%); color: #ffffff; padding: 24px; text-align: center; }
        .content { padding: 24px; }
        .info-table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        .info-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        .info-table td.label { font-weight: bold; color: #64748b; width: 35%; }
        .btn { display: inline-block; background-color: #059669; color: #ffffff !important; text-decoration: none; padding: 12px 24px; font-weight: bold; border-radius: 10px; text-align: center; margin-top: 16px; }
        .footer { background: #f1f5f9; color: #94a3b8; font-size: 11px; text-align: center; padding: 16px; }
        .highlight-box { background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 10px; padding: 12px 16px; margin: 14px 0; color: #065f46; font-size: 13px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1 style="margin: 0; font-size: 20px;">✍️ ¡Contrato Formalizado y Firmado!</h1>
            <p style="margin: 6px 0 0 0; font-size: 13px; opacity: 0.9;">{{ $brand['name'] ?? 'Eventos Musicales' }}</p>
        </div>

        <div class="content">
            <div class="highlight-box">
                ✅ El cliente <strong>{{ $contract->client_name_signed }}</strong> ha completado la firma electrónica de su contrato con plena validez legal.
            </div>

            <table class="info-table">
                <tr>
                    <td class="label">Evento:</td>
                    <td><strong>{{ $event ? $event->name : 'N/A' }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Fecha del Evento:</td>
                    <td><strong>{{ ($event && $event->event_date) ? $event->event_date->format('d/m/Y') : 'Por definir' }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Firmante:</td>
                    <td>{{ $contract->client_name_signed }} (DNI: {{ $contract->client_dni_signed }})</td>
                </tr>
                <tr>
                    <td class="label">Teléfono / Email:</td>
                    <td>{{ $contract->client_phone_signed }} &bull; {{ $contract->client_email_signed }}</td>
                </tr>
                <tr>
                    <td class="label">Importe Total:</td>
                    <td><strong>{{ number_format($contract->amount ?: (($event && $event->quotes()->exists()) ? ($event->quotes()->latest()->first()->total_amount ?? 0) : 0), 2, ',', '.') }} €</strong></td>
                </tr>
                <tr>
                    <td class="label">Tipo de Firma:</td>
                    <td>{{ $contract->signature_type === 'certificate' ? '🔐 Certificado Digital (FNMT/DNIe)' : '✍️ Firma Digital en Pantalla' }}</td>
                </tr>
                <tr>
                    <td class="label">Fecha y Hora de Firma:</td>
                    <td>{{ $contract->signed_at ? $contract->signed_at->format('d/m/Y H:i:s') : now()->format('d/m/Y H:i:s') }} (IP: {{ $contract->signed_ip }})</td>
                </tr>
            </table>

            @if($event)
                <div style="text-align: center; margin-top: 20px;">
                    <a href="{{ route('admin.events.show', $event->id) }}" class="btn">
                        Ver Contrato y Evento en el Panel &rarr;
                    </a>
                </div>
            @endif
        </div>

        <div class="footer">
            Notificación automática de {{ $brand['name'] ?? 'Eventos Musicales' }} &bull; {{ date('Y') }}
        </div>
    </div>
</body>
</html>
