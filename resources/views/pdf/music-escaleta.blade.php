<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Escaleta Musical - {{ $event->name }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #1e293b; font-size: 11px; line-height: 1.4; margin: 0; padding: 20px; }
        .header { margin-bottom: 20px; border-bottom: 2px solid #6366f1; padding-bottom: 12px; }
        .header table { width: 100%; border-collapse: collapse; }
        .header-title { font-size: 20px; font-weight: bold; color: #312e81; margin: 0; text-transform: uppercase; }
        .header-subtitle { font-size: 11px; color: #64748b; margin-top: 3px; }
        .company-logo { max-height: 45px; max-width: 160px; }
        
        .info-card { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; margin-bottom: 18px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 4px 6px; font-size: 11px; vertical-align: top; }
        .info-label { font-weight: bold; color: #475569; width: 18%; }
        .info-val { color: #0f172a; width: 32%; }
        
        .phase-block { margin-bottom: 18px; page-break-inside: avoid; }
        .phase-title { background-color: #e0e7ff; color: #3730a3; font-weight: bold; font-size: 12px; padding: 6px 10px; border-radius: 4px 4px 0 0; text-transform: uppercase; letter-spacing: 0.5px; }
        .phase-title.blacklist { background-color: #fee2e2; color: #991b1b; }
        
        .items-table { width: 100%; border-collapse: collapse; border: 1px solid #e2e8f0; border-top: none; }
        .items-table th { background-color: #f1f5f9; color: #475569; font-size: 10px; text-transform: uppercase; padding: 6px 8px; text-align: left; border-bottom: 1px solid #cbd5e1; }
        .items-table td { padding: 7px 8px; border-bottom: 1px solid #f1f5f9; font-size: 11px; vertical-align: top; }
        .items-table tr:nth-child(even) { background-color: #fafafa; }
        
        .moment-tag { font-weight: bold; color: #4338ca; display: inline-block; }
        .cue-badge { background-color: #f59e0b; color: #ffffff; font-weight: bold; font-size: 9px; padding: 2px 5px; border-radius: 4px; display: inline-block; margin-left: 4px; }
        .req-by { font-size: 10px; color: #059669; font-weight: 600; }
        .notes-box { font-size: 10px; color: #475569; background-color: #f8fafc; border-left: 2px solid #cbd5e1; padding: 2px 6px; margin-top: 3px; }
        
        .footer { margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 10px; text-align: center; font-size: 10px; color: #94a3b8; }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td style="vertical-align: middle;">
                    <h1 class="header-title">🎧 Escaleta Musical y Momentos</h1>
                    <div class="header-subtitle">Guía de canciones clave y peticiones para el DJ y Personal</div>
                </td>
                <td style="text-align: right; vertical-align: middle;">
                    @php $logoPath = \App\Models\Setting::getLogoPathForPdf(); @endphp
                    @if($logoPath)
                        <img src="{{ $logoPath }}" class="company-logo" alt="Logo">
                    @else
                        <strong style="font-size: 16px; color: #4f46e5;">{{ \App\Models\Setting::getCompanyName('Eventos Musicales') }}</strong>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="info-card">
        <table class="info-table">
            <tr>
                <td class="info-label">Evento:</td>
                <td class="info-val"><strong>{{ $event->name }}</strong></td>
                <td class="info-label">Fecha:</td>
                <td class="info-val"><strong>{{ \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') }}</strong></td>
            </tr>
            <tr>
                <td class="info-label">Lugar / Finca:</td>
                <td class="info-val">{{ $event->location }}</td>
                <td class="info-label">Cliente / Novios:</td>
                <td class="info-val">{{ $client->name ?? 'No especificado' }} {{ isset($client->phone) ? '('.$client->phone.')' : '' }}</td>
            </tr>
            <tr>
                <td class="info-label">🎧 DJ (Baile):</td>
                <td class="info-val"><strong>{{ $dj->name ?? 'Sin asignar' }}</strong> {{ isset($dj->phone) ? '('.$dj->phone.')' : '' }}</td>
                <td class="info-label">👷‍♂️ Asistente(s):</td>
                <td class="info-val">
                    @php
                        $escaletaAssistants = $event->all_assistants;
                    @endphp
                    @if($escaletaAssistants->isNotEmpty())
                        <strong>{{ $escaletaAssistants->map(fn($a) => $a->name . ($a->phone ? ' ('.$a->phone.')' : ''))->implode(', ') }}</strong>
                    @else
                        <strong>Sin asignar</strong>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    @php
        $categoryNames = [
            'ceremonia' => '💍 Ceremonia',
            'coctel' => '🍸 Cóctel / Aperitivo',
            'banquete' => '🍽️ Banquete / Comida / Cena',
            'baile' => '💃 Baile / Fiesta',
            'lista_negra' => '🚫 Lista Negra (Canciones Prohibidas)',
        ];
    @endphp

    @if($requestsGrouped->count() > 0)
        @foreach($categoryNames as $catKey => $catLabel)
            @if(isset($requestsGrouped[$catKey]) && $requestsGrouped[$catKey]->count() > 0)
                @php $items = $requestsGrouped[$catKey]; @endphp
                <div class="phase-block">
                    <div class="phase-title {{ $catKey === 'lista_negra' ? 'blacklist' : '' }}">
                        {{ $catLabel }} ({{ $items->count() }})
                    </div>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th style="width: 25%;">Momento / Bloque</th>
                                <th style="width: 35%;">Canción / Artista</th>
                                <th style="width: 20%;">Petición / Dedicatoria</th>
                                <th>Instrucciones / Notas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                                <tr>
                                    <td>
                                        <span class="moment-tag">{{ $item->moment }}</span>
                                        @if($item->cue_time)
                                            <span class="cue-badge">CUE {{ $item->cue_time }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $item->title }}</strong>
                                        @if($item->artist)
                                            <br><span style="color: #64748b; font-size: 10px;">{{ $item->artist }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($item->requested_by)
                                            <span class="req-by">👤 {{ $item->requested_by }}</span>
                                        @else
                                            <span style="color: #94a3b8; font-size: 10px;">Novios</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($item->notes)
                                            <div class="notes-box">📝 {{ $item->notes }}</div>
                                        @else
                                            <span style="color: #cbd5e1;">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endforeach
    @else
        <div style="text-align: center; padding: 40px; border: 2px dashed #cbd5e1; border-radius: 6px; color: #64748b;">
            No hay canciones o momentos musicales registrados para este evento todavía.
        </div>
    @endif

    <div class="footer">
        Escaleta generada por {{ \App\Models\Setting::get('company_name', 'Eventos Musicales') }} — Fecha: {{ now()->format('d/m/Y H:i') }}
    </div>

</body>
</html>
