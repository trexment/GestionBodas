<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cuestionario Musical Recibido</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 20px; font-size: 14px; line-height: 1.5; }
        .card { max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #ffffff; padding: 24px; text-align: center; }
        .content { padding: 24px; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 9999px; font-size: 11px; font-weight: bold; background-color: #e0e7ff; color: #4338ca; text-transform: uppercase; }
        .info-table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        .info-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        .info-table td.label { font-weight: bold; color: #64748b; width: 35%; }
        .section-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; margin-bottom: 12px; }
        .section-title { font-size: 12px; font-weight: bold; text-transform: uppercase; color: #4f46e5; margin-bottom: 6px; }
        .btn { display: inline-block; background-color: #4f46e5; color: #ffffff !important; text-decoration: none; padding: 12px 24px; font-weight: bold; border-radius: 10px; text-align: center; margin-top: 16px; }
        .footer { background: #f1f5f9; color: #94a3b8; font-size: 11px; text-align: center; padding: 16px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1 style="margin: 0; font-size: 20px;">🎵 ¡Cuestionario Musical Recibido!</h1>
            <p style="margin: 6px 0 0 0; font-size: 13px; opacity: 0.9;">{{ $brand['name'] ?? 'Eventos Musicales' }}</p>
        </div>

        <div class="content">
            <p style="margin-top: 0;">Un cliente acaba de cumplimentar o actualizar las canciones y preferencias musicales para su evento.</p>

            <table class="info-table">
                <tr>
                    <td class="label">Evento:</td>
                    <td><strong>{{ $event->name }}</strong> ({{ $event->event_type_label }})</td>
                </tr>
                <tr>
                    <td class="label">Fecha:</td>
                    <td><strong>{{ $event->event_date ? $event->event_date->format('d/m/Y') : 'Por definir' }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Lugar:</td>
                    <td>{{ $event->location ?: 'No especificado' }}</td>
                </tr>
                <tr>
                    <td class="label">Cliente:</td>
                    <td>{{ $event->client ? $event->client->name : 'No asignado' }} {{ $event->client && $event->client->phone ? '(' . $event->client->phone . ')' : '' }}</td>
                </tr>
                @if($event->dj)
                    <tr>
                        <td class="label">DJ Asignado:</td>
                        <td>{{ $event->dj->name }}</td>
                    </tr>
                @endif
            </table>

            @if(!empty($songsSummary))
                <div style="margin-top: 18px;">
                    <h3 style="font-size: 14px; margin-bottom: 10px; color: #1e293b;">Resumen de Peticiones Enviadas:</h3>
                    
                    @foreach($songsSummary as $section => $songs)
                        @if(!empty(trim($songs)))
                            <div class="section-box">
                                <div class="section-title">{{ $section }}</div>
                                <div style="font-size: 12px; color: #334155; white-space: pre-line;">{{ $songs }}</div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

            <div style="text-align: center; margin-top: 20px;">
                <a href="{{ route('admin.events.show', $event->id) }}" class="btn">
                    Ver Evento y Escaleta en el Panel &rarr;
                </a>
            </div>
        </div>

        <div class="footer">
            Notificación automática de {{ $brand['name'] ?? 'Eventos Musicales' }} &bull; {{ date('Y') }}
        </div>
    </div>
</body>
</html>
