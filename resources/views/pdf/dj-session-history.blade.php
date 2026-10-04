<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tracklist Oficial de la Sesión - {{ $event->name }}</title>
    <style>
        @page {
            margin: 15mm 15mm 15mm 15mm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1e293b;
            font-size: 10px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header {
            margin-bottom: 15px;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 10px;
        }
        .header table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-title {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
            text-transform: uppercase;
        }
        .header-subtitle {
            font-size: 10px;
            color: #64748b;
            margin-top: 3px;
        }
        .company-logo {
            max-height: 40px;
            max-width: 140px;
        }
        .info-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 15px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 3px 5px;
            font-size: 10px;
        }
        .info-label {
            font-weight: bold;
            color: #475569;
            width: 18%;
        }
        .info-val {
            color: #0f172a;
            width: 32%;
        }
        .tracks-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
        }
        .tracks-table th {
            background-color: #1e1b4b;
            color: #ffffff;
            font-size: 9px;
            text-transform: uppercase;
            padding: 5px 6px;
            text-align: left;
            border: 1px solid #1e1b4b;
        }
        .tracks-table td {
            padding: 4px 6px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 9.5px;
        }
        .tracks-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .order-col {
            width: 25px;
            text-align: center;
            font-weight: bold;
            color: #64748b;
        }
        .time-badge {
            background-color: #e0e7ff;
            color: #3730a3;
            font-weight: bold;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 8.5px;
            display: inline-block;
        }
        .bpm-badge {
            background-color: #f1f5f9;
            color: #475569;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 8.5px;
            font-family: monospace;
        }
        .matched-badge {
            background-color: #dcfce7;
            color: #166534;
            font-weight: bold;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 8px;
            display: inline-block;
        }
        .footer {
            margin-top: 20px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    @php
        $brandInfo = $event->brand_info;
        $logoPath = $brandInfo['logo_path'] ?? \App\Models\Setting::getLogoPathForPdf();
    @endphp

    <div class="header">
        <table>
            <tr>
                <td style="vertical-align: middle;">
                    <h1 class="header-title">🎵 Tracklist Oficial de la Sesión DJ</h1>
                    <div class="header-subtitle">Historial cronológico de la música en directo</div>
                </td>
                <td style="text-align: right; vertical-align: middle;">
                    @if($logoPath && file_exists($logoPath))
                        <img src="{{ $logoPath }}" class="company-logo" alt="Logo">
                    @else
                        <strong style="font-size: 15px; color: #4f46e5;">{{ $brandInfo['name'] }}</strong>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="info-card">
        <table class="info-table">
            <tr>
                <td class="info-label">Evento:</td>
                <td class="info-val"><strong>{{ $event->name }}</strong> ({{ $event->event_type_label }})</td>
                <td class="info-label">Fecha:</td>
                <td class="info-val"><strong>{{ $event->event_date ? $event->event_date->format('d/m/Y') : 'Sin fecha' }}</strong></td>
            </tr>
            <tr>
                <td class="info-label">Lugar / Finca:</td>
                <td class="info-val">{{ $event->location }}</td>
                <td class="info-label">DJ Asignado:</td>
                <td class="info-val"><strong>{{ $event->dj ? $event->dj->name : 'DJ Profesional' }}</strong></td>
            </tr>
            <tr>
                <td class="info-label">Total Temas:</td>
                <td class="info-val"><strong>{{ $histories->count() }} canciones</strong></td>
                <td class="info-label">Marca:</td>
                <td class="info-val">{{ $brandInfo['name'] }}</td>
            </tr>
        </table>
    </div>

    <table class="tracks-table">
        <thead>
            <tr>
                <th class="order-col">#</th>
                <th style="width: 50px;">Hora</th>
                <th>Canción / Título</th>
                <th>Artista</th>
                <th style="width: 45px; text-align: center;">BPM</th>
                <th style="width: 45px; text-align: center;">Tono</th>
                <th style="width: 100px;">Petición</th>
            </tr>
        </thead>
        <tbody>
            @forelse($histories as $track)
                <tr>
                    <td class="order-col">{{ $loop->iteration }}</td>
                    <td>
                        @if($track->played_at_time)
                            <span class="time-badge">{{ $track->played_at_time }}</span>
                        @else
                            <span style="color: #cbd5e1;">-</span>
                        @endif
                    </td>
                    <td><strong>{{ $track->title }}</strong></td>
                    <td>{{ $track->artist ?: '-' }}</td>
                    <td style="text-align: center;">
                        @if($track->bpm)
                            <span class="bpm-badge">{{ number_format($track->bpm, 0) }}</span>
                        @else
                            <span style="color: #cbd5e1;">-</span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        {{ $track->key ?: '-' }}
                    </td>
                    <td>
                        @if($track->matchedRequest)
                            <span class="matched-badge">
                                @if($track->matchedRequest->is_guest_request)
                                    👥 {{ $track->matchedRequest->guest_name ?: 'Invitado' }}
                                @else
                                    💍 Novios / Escaleta
                                @endif
                            </span>
                        @else
                            <span style="color: #94a3b8; font-size: 8px;">Sesión DJ</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px; color: #94a3b8;">
                        No hay canciones registradas en el historial del evento.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        {{ $brandInfo['name'] }} &bull; {{ $brandInfo['phone'] }} &bull; {{ $brandInfo['email'] }} &bull; {{ $brandInfo['website'] }}
    </div>

</body>
</html>
