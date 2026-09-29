<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Hoja de Carga - {{ $event->name }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #333; font-size: 11px; line-height: 1.4; margin: 0; padding: 20px; }
        .header { margin-bottom: 20px; border-bottom: 2px solid #4f46e5; padding-bottom: 12px; }
        .header table { width: 100%; border-collapse: collapse; }
        .header-title { font-size: 20px; font-weight: bold; color: #1e1b4b; margin: 0; text-transform: uppercase; }
        .header-subtitle { font-size: 11px; color: #6b7280; margin-top: 3px; }
        .company-logo { max-height: 45px; max-width: 160px; }
        
        .info-card { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; margin-bottom: 20px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 4px 6px; font-size: 11px; }
        .info-label { font-weight: bold; color: #475569; width: 18%; }
        .info-val { color: #0f172a; width: 32%; }
        
        .category-block { margin-bottom: 20px; page-break-inside: avoid; }
        .category-title { background-color: #e0e7ff; color: #3730a3; font-weight: bold; font-size: 12px; padding: 6px 10px; border-radius: 4px 4px 0 0; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .items-table { width: 100%; border-collapse: collapse; border: 1px solid #e2e8f0; border-top: none; }
        .items-table th { background-color: #f1f5f9; color: #475569; font-size: 10px; text-transform: uppercase; padding: 6px 8px; text-align: left; border-bottom: 1px solid #cbd5e1; }
        .items-table td { padding: 6px 8px; border-bottom: 1px solid #f1f5f9; font-size: 11px; }
        .items-table tr:nth-child(even) { background-color: #fafafa; }
        
        .checkbox-cell { width: 35px; text-align: center; }
        .box { display: inline-block; width: 12px; height: 12px; border: 1.5px solid #64748b; border-radius: 2px; }
        .qty-badge { background-color: #4f46e5; color: #fff; font-weight: bold; padding: 2px 6px; border-radius: 10px; font-size: 10px; display: inline-block; }
        .dmx-badge { background-color: #fef3c7; color: #92400e; padding: 2px 5px; border-radius: 3px; font-size: 9px; font-weight: bold; border: 1px solid #fde68a; }
        
        .footer { margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 10px; text-align: center; font-size: 10px; color: #94a3b8; }
        .signature-table { width: 100%; margin-top: 35px; border-collapse: collapse; }
        .signature-table td { width: 50%; vertical-align: top; padding: 0 20px; }
        .sig-line { border-bottom: 1px solid #64748b; height: 40px; margin-bottom: 5px; }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td style="vertical-align: middle;">
                    <h1 class="header-title">📦 Hoja de Carga y Material Técnico</h1>
                    <div class="header-subtitle">Checklist de salida de almacén, montaje y recogida</div>
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
                <td class="info-label">Fecha del Evento:</td>
                <td class="info-val"><strong>{{ \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') }}</strong></td>
            </tr>
            <tr>
                <td class="info-label">Lugar / Espacio:</td>
                <td class="info-val">{{ $event->location }}</td>
                <td class="info-label">Cliente / Contacto:</td>
                <td class="info-val">{{ $client->name ?? 'No asignado' }} {{ isset($client->phone) ? '('.$client->phone.')' : '' }}</td>
            </tr>
            <tr>
                <td class="info-label">🎧 DJ Asignado:</td>
                <td class="info-val"><strong>{{ $dj->name ?? 'Sin asignar' }}</strong> {{ isset($dj->phone) ? '('.$dj->phone.')' : '' }}</td>
                <td class="info-label">👷‍♂️ Asistente:</td>
                <td class="info-val"><strong>{{ $assistant->name ?? 'Sin asignar' }}</strong> {{ isset($assistant->phone) ? '('.$assistant->phone.')' : '' }}</td>
            </tr>
            @if($event->notes)
            <tr>
                <td class="info-label">Notas Generales:</td>
                <td class="info-val" colspan="3" style="color: #b45309;">{{ $event->notes }}</td>
            </tr>
            @endif
        </table>
    </div>

    @if($equipmentGrouped->count() > 0)
        @foreach($equipmentGrouped as $category => $items)
            <div class="category-block">
                <div class="category-title">
                    📂 {{ $category }} ({{ $items->sum('pivot.quantity') }} uds)
                </div>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th class="checkbox-cell" title="Cargado en Almacén">Carga</th>
                            <th class="checkbox-cell" title="Recogido / Retornado">Retorno</th>
                            <th style="width: 35%;">Equipo / Modelo</th>
                            <th style="width: 8%; text-align: center;">Cant.</th>
                            <th style="width: 25%;">Configuración / DMX</th>
                            <th>Ubicación / Notas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            <tr>
                                <td class="checkbox-cell"><span class="box"></span></td>
                                <td class="checkbox-cell"><span class="box"></span></td>
                                <td>
                                    <strong>{{ $item->name }}</strong>
                                    @if($item->brand_model)
                                        <br><span style="color: #64748b; font-size: 10px;">{{ $item->brand_model }}</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <span class="qty-badge">{{ $item->pivot->quantity }}</span>
                                </td>
                                <td>
                                    @if($item->is_dmx)
                                        @php
                                            $start = (int)$item->dmx_address;
                                            $mode = (int)$item->dmx_mode;
                                            $qty = (int)$item->pivot->quantity;
                                        @endphp
                                        @if($start > 0 && $mode > 0 && $qty > 0)
                                            @php
                                                $addrs = [];
                                                for ($i = 0; $i < $qty; $i++) {
                                                    $addrs[] = $start + ($i * $mode);
                                                }
                                            @endphp
                                            <span class="dmx-badge">DMX: {{ implode(', ', $addrs) }}</span>
                                            <span style="font-size: 9px; color: #64748b;">({{ $item->dmx_mode }}ch)</span>
                                        @else
                                            <span class="dmx-badge">{{ $item->dmx_address ? 'CH ' . $item->dmx_address : 'DMX Sí' }}</span>
                                        @endif
                                    @else
                                        <span style="color: #cbd5e1;">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($item->pivot->notes)
                                        <span style="color: #475569;">📍 {{ $item->pivot->notes }}</span>
                                    @else
                                        <span style="color: #cbd5e1;">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach

        <table class="signature-table">
            <tr>
                <td>
                    <div class="sig-line"></div>
                    <div style="font-size: 10px; color: #475569; text-align: center;">Firma Responsable Salida Almacén</div>
                </td>
                <td>
                    <div class="sig-line"></div>
                    <div style="font-size: 10px; color: #475569; text-align: center;">Firma DJ / Asistente Responsable Retorno</div>
                </td>
            </tr>
        </table>
    @else
        <div style="text-align: center; padding: 40px; border: 2px dashed #cbd5e1; border-radius: 6px; color: #64748b;">
            No hay equipos asignados a este evento.
        </div>
    @endif

    <div class="footer">
        Documento generado por {{ \App\Models\Setting::get('company_name', 'Eventos Musicales') }} — Fecha: {{ now()->format('d/m/Y H:i') }}
    </div>

</body>
</html>
