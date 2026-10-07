<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Event;
use App\Models\Setting;
use Carbon\Carbon;

class ContractTemplateService
{
    public static function availableVariables(): array
    {
        return [
            '{{ empresa }}' => 'Nombre de la empresa / DJ',
            '{{ cif_empresa }}' => 'CIF / NIF de la empresa',
            '{{ email_empresa }}' => 'Email de contacto empresa',
            '{{ telefono_empresa }}' => 'Teléfono de la empresa',
            '{{ direccion_empresa }}' => 'Dirección fiscal de la empresa',
            '{{ ciudad_empresa }}' => 'Ciudad / Sede de la empresa (Ej: Navarrete)',
            '{{ ciudad_firma }}' => 'Ciudad de firma (Ciudad del cliente o Navarrete)',
            '{{ ciudad_juzgados }}' => 'Ciudad de los Juzgados y Tribunales',
            '{{ cliente }}' => 'Nombre del cliente',
            '{{ nif_cliente }}' => 'DNI / NIF del cliente',
            '{{ email_cliente }}' => 'Email del cliente',
            '{{ telefono_cliente }}' => 'Teléfono del cliente',
            '{{ direccion_cliente }}' => 'Dirección del cliente',
            '{{ codigo_postal_cliente }}' => 'Código postal del cliente',
            '{{ ciudad_cliente }}' => 'Ciudad / Población del cliente',
            '{{ provincia_cliente }}' => 'Provincia del cliente',
            '{{ evento }}' => 'Nombre del evento',
            '{{ tipo_evento }}' => 'Tipo de evento (Boda, Fiesta, etc.)',
            '{{ fecha_evento }}' => 'Fecha del evento (dd/mm/aaaa)',
            '{{ ubicacion }}' => 'Lugar / Finca / Recinto',
            '{{ hora_inicio }}' => 'Hora estimada de inicio de los servicios contratados (Ceremonia, Cóctel o Baile)',
            '{{ hora_fin }}' => 'Hora estimada de finalización de los servicios (Fin del baile)',
            '{{ horario_baile }}' => 'Horario específico del baile / barra libre',
            '{{ cronograma_completo }}' => 'Desglose completo de horarios de las fases del evento',
            '{{ importe }}' => 'Importe total acordado con €',
            '{{ importe_total }}' => 'Importe total acordado con €',
            '{{ importe_senal }}' => 'Importe del 40% de señal de reserva con €',
            '{{ importe_restante }}' => 'Importe del 60% restante con €',
            '{{ servicios_contratados }}' => 'Listado y desglose de servicios contratados (DJ, Fotografía, Cóctel, etc.)',
            '{{ iban_empresa }}' => 'IBAN de la empresa para pagos',
            '{{ bizum_empresa }}' => 'Teléfono Bizum de la empresa',
            '{{ formas_pago }}' => 'Formas de pago aceptadas (Transferencia, Bizum, Metálico/Efectivo, Tarjeta, etc. según la propuesta)',
            '{{ metodos_pago }}' => 'Alias de formas de pago aceptadas',
            '{{ numero_presupuesto }}' => 'Número de presupuesto',
            '{{ numero_contrato }}' => 'Número de contrato',
            '{{ fecha_emision }}' => 'Fecha de emisión del contrato',
        ];
    }

    public static function presets(): array
    {
        return [
            'servicio_dj' => [
                'name' => 'Servicio DJ / Discomóvil / Eventos Musicales y Bodas',
                'description' => 'Plantilla integral y blindada para bodas y eventos con decibelios, horas extra según límite legal, pagos escalonados (40% señal / 60% evento), roturas por invitados, SGAE, climatología, RRSS y RGPD.',
                'title' => 'Contrato de Prestación de Servicios Musicales, Sonorización e Iluminación - {{ evento }}',
                'body' => <<<'TEXT'
En {{ ciudad_firma }}, a {{ fecha_emision }}.

REUNIDOS

De una parte, {{ empresa }}, con NIF/CIF {{ cif_empresa }}, domicilio en {{ direccion_empresa }}, {{ ciudad_empresa }}, y correo de contacto {{ email_empresa }}, en adelante, "LA EMPRESA / DJ".

Y de otra parte, {{ cliente }}, con NIF/DNI {{ nif_cliente }}, teléfono {{ telefono_cliente }}, correo electrónico {{ email_cliente }}, y domicilio en {{ direccion_cliente }}{{ ciudad_cliente_info }}, en adelante, "EL CLIENTE".

Ambas partes, reconociéndose mutua y recíprocamente plena capacidad jurídica y de obrar para el otorgamiento del presente CONTRATO DE PRESTACIÓN DE SERVICIOS MUSICALES, SONORIZACIÓN E ILUMINACIÓN,

MANIFIESTAN:

I. Que LA EMPRESA se dedica profesionalmente a la prestación de servicios de sonorización, iluminación técnica, disc-jockey y producción audiovisual para eventos sociales, corporativos y bodas.
II. Que EL CLIENTE tiene interés en contratar dichos servicios para el evento denominado "{{ evento }}", previsto para el día {{ fecha_evento }} en {{ ubicacion }}.

Y acuerdan someterse a las siguientes:

CLÁUSULAS:

PRIMERA. OBJETO DEL CONTRATO Y HORARIOS
LA EMPRESA prestará al CLIENTE los servicios de sonorización, iluminación y actuación musical para el evento "{{ evento }}", de tipo {{ tipo_evento }}, con horario estimado de {{ hora_inicio }} a {{ hora_fin }}. 
Cumplida la hora de finalización pactada, EL CLIENTE podrá solicitar la ampliación de horas adicionales de servicio/baile, quedando la prórroga supeditada a la disponibilidad del personal y al estricto cumplimiento del horario límite legal establecido por la normativa autonómica y municipal de espectáculos públicos y actividades recreativas.

SEGUNDA. ALCANCE TÉCNICO Y SERVICIOS CONTRATADOS
Los servicios incluidos y acordados para el evento son los siguientes:
{{ servicios_contratados }}

El servicio se desarrollará conforme al desglose técnico detallado en el Presupuesto aceptado nº {{ numero_presupuesto }}. En caso de que EL CLIENTE contrate proveedores o actuaciones extraordinarias externas (v. gr. saxofonistas, violinistas, grupos en directo, fotomatón o efectos especiales) que requieran conexión de audio o tomas de corriente a los equipos de LA EMPRESA, deberá comunicarlo previamente por escrito. Dichos proveedores deberán contar con su correspondiente Seguro de Responsabilidad Civil en vigor.

TERCERA. PRECIO Y FORMA DE PAGO
El precio total pactado asciende a {{ importe }} (IVA incluido o aplicable según normativa). El pago se formalizará conforme a las siguientes condiciones:
1. Un importe de {{ importe_senal }} en concepto de señal y reserva en firme de la fecha y bloqueo de equipamiento técnico a la firma del presente contrato.
2. El importe restante de {{ importe_restante }} a abonar el día de la celebración del evento o con anterioridad al inicio de la actuación.
Formas de pago aceptadas: {{ formas_pago }}.
Cualquier ampliación horaria o extra no contemplado en el presupuesto inicial se abonará conforme a la tarifa por hora extra establecida.

CUARTA. CONDICIONES TÉCNICAS, SUMINISTRO ELÉCTRICO Y CLIMATOLOGÍA (CARPA)
EL CLIENTE garantizará el acceso de los técnicos al recinto con antelación suficiente para el montaje y pruebas de sonido, así como una toma de corriente estable y adecuada (230V con toma de tierra).
En eventos al aire libre, si las condiciones climatológicas fuesen adversas (lluvia, tormenta, viento severo o humedad extrema), EL CLIENTE deberá facilitar un espacio cubierto o carpa debidamente impermeabilizada que garantice la seguridad del público, técnicos y material.

QUINTA. LIMITACIÓN DE DECIBELIOS Y NORMATIVAS ACÚSTICAS
EL CLIENTE manifiesta ser conocedor de las ordenanzas municipales sobre medio ambiente, límites de decibelios y aforo aplicables en el recinto. A estos efectos, LA EMPRESA / DJ se reserva la facultad técnica de moderar el volumen de emisión sonora con el fin de respetar en todo momento la normativa acústica vigente y evitar sanciones administrativas.

SEXTA. RESPONSABILIDAD, CUSTODIA Y DAÑOS AL EQUIPAMIENTO POR INVITADOS
EL CLIENTE asume la custodia del equipamiento técnico instalado durante el desarrollo del evento. Cualquier daño, rotura, caída, sustracción o vertido de bebidas/líquidos sobre altavoces, mesas de mezclas, cabina DJ, micrófonos, ordenadores o iluminación provocado por la imprudencia, descuido, tropiezo o negligencia de los invitados, asistentes o terceros presentes en el evento será RESPONSABILIDAD ECONÓMICA EXCLUSIVA DEL CLIENTE. Éste abonará el coste íntegro de la reparación técnica oficial o sustitución a nuevo del material a precio de mercado en un plazo máximo de siete (7) días naturales.

SÉPTIMA. DERECHOS DE AUTOR Y PROPIEDAD INTELECTUAL (SGAE / AGEDI-AIE)
Los cánones, licencias y autorizaciones que pudieran devengarse ante entidades de gestión de derechos de autor (SGAE, AGEDI-AIE) por la comunicación pública musical en el recinto corresponden al organizador del evento o a la finca/establecimiento conforme a la legislación aplicable.

OCTAVA. SEGURIDAD Y SUSPENSIÓN DEL SERVICIO
Si durante el desarrollo del evento se suscitaran altercados, actos vandálicos, agresiones verbales o físicas al personal técnico/DJ, o un riesgo eléctrico o meteorológico inminente que ponga en peligro vidas o bienes, LA EMPRESA podrá suspender cautelarmente la actividad sin que ello genere derecho a devolución de los importes abonados ni indemnización alguna.

NOVENA. CANCELACIÓN Y REPROGRAMACIÓN POR FUERZA MAYOR
En caso de desistimiento unilateral o cancelación por parte del CLIENTE no imputable a LA EMPRESA, se retendrán las cantidades abonadas en concepto de reserva y gastos de gestión. Si la cancelación se produjera por causa demostrable de fuerza mayor sobrevenida (incluyendo fallecimiento o enfermedad grave de familiar directo de primer grado), LA EMPRESA reservará íntegramente las cantidades entregadas para aplicarlas a una nueva fecha consensuada según disponibilidad.

DÉCIMA. PROTECCIÓN DE DATOS (RGPD / LOPD-GDD)
Los datos personales facilitados serán tratados exclusivamente para la gestión, ejecución y facturación del presente contrato. Las partes podrán ejercer sus derechos de acceso, rectificación, supresión y portabilidad dirigiéndose a las direcciones indicadas en el encabezamiento.

DÉCIMO PRIMERA. JURISDICCIÓN Y COMPETENCIA
Para cualquier discrepancia, litigio o reclamación derivada de la interpretación o ejecución del presente contrato, ambas partes acuerdan someterse expresamente a los Juzgados y Tribunales correspondientes al partido judicial de {{ ciudad_juzgados }}, con renuncia expresa a cualquier otro fuero que pudiera corresponderles.
TEXT
                ,
                'footer' => <<<'TEXT'
Y en prueba de conformidad con cuanto antecede, ambas partes otorgan y firman electrónicamente el presente contrato, en la fecha y lugar indicados en el encabezamiento.
TEXT
            ]
        ];
    }

    public static function getDefaultTitle(): string
    {
        return Setting::get('contract_title', self::presets()['servicio_dj']['title']);
    }

    public static function getDefaultBody(): string
    {
        return Setting::get('contract_body', self::presets()['servicio_dj']['body']);
    }

    public static function getDefaultFooter(): string
    {
        return Setting::get('contract_footer', self::presets()['servicio_dj']['footer']);
    }

    public static function renderContract(Contract $contract, array $overrides = []): array
    {
        $event = $contract->event;
        $client = $event ? $event->client : null;
        $quote = $event ? $event->quotes()->latest()->first() : null;
        $amount = $quote ? (float)$quote->amount : 0;

        if ($quote) {
            $signalAmount = $quote->signal_amount;
            $remainingAmount = $quote->remaining_amount;
            $signalLabel = $quote->signal_label;
        } else {
            $type = Setting::get('deposit_type', 'percentage');
            if ($type === 'fixed') {
                $signalAmount = min((float)Setting::get('deposit_fixed_amount', 200), $amount);
                $signalLabel = number_format($signalAmount, 2, ',', '.') . ' €';
            } else {
                $pct = (float)Setting::get('deposit_percentage', 40);
                $signalAmount = round(($amount * $pct) / 100, 2);
                $signalLabel = rtrim(rtrim(number_format($pct, 2, ',', '.'), '0'), ',') . '% (' . number_format($signalAmount, 2, ',', '.') . ' €)';
            }
            $remainingAmount = max(0, $amount - $signalAmount);
        }

        // Desglose de servicios
        $servicesList = [];
        if ($quote && $quote->items && $quote->items->count() > 0) {
            foreach ($quote->items as $item) {
                $qtyStr = $item->quantity > 1 ? " ({$item->quantity}h/uds)" : "";
                $descStr = $item->description ? " - {$item->description}" : "";
                $servicesList[] = "• {$item->service_name}{$qtyStr}{$descStr} (" . number_format($item->price * $item->quantity, 2, ',', '.') . " €)";
            }
        }
        if (empty($servicesList) && $event && $event->notes) {
            $servicesList[] = "• " . str_replace("\n", "\n• ", trim($event->notes));
        }
        $renderedServices = !empty($servicesList) ? implode("\n", $servicesList) : "• Servicio de DJ, Sonorización e Iluminación según presupuesto acordado.";

        $clientName = $overrides['client_name'] ?? ($contract->client_name_signed ?: ($client ? $client->name : '_____________________'));
        $clientDni = $overrides['client_dni'] ?? ($contract->client_dni_signed ?: ($client?->dni ?? $client?->nif ?? '_________________'));
        $clientPhone = $overrides['client_phone'] ?? ($contract->client_phone_signed ?: ($client?->phone ?? '_________________'));
        $clientEmail = $overrides['client_email'] ?? ($contract->client_email_signed ?: ($client?->email ?? '_________________'));
        $clientAddress = $overrides['client_address'] ?? ($contract->client_address_signed ?: ($client?->address ?? '_________________'));
        
        $clientPostalCode = $overrides['client_postal_code'] ?? ($contract->client_postal_code_signed ?: ($client?->postal_code ?? ''));
        $clientCity = $overrides['client_city'] ?? ($contract->client_city_signed ?: ($client?->city ?? ''));
        $clientProvince = $overrides['client_province'] ?? ($contract->client_province_signed ?: ($client?->province ?? ''));

        // Formatted client city string: e.g. ", 26370 Navarrete (La Rioja)"
        $clientCityInfo = '';
        if ($clientCity || $clientPostalCode) {
            $parts = array_filter([$clientPostalCode, $clientCity, $clientProvince ? "({$clientProvince})" : '']);
            $clientCityInfo = ', ' . implode(' ', $parts);
        }

        $brandKey = $event ? $event->brand_clean : ($quote ? $quote->brand_clean : null);
        $brand = Setting::getBrandInfo($brandKey);
        $companyName = (!empty($brand['name'])) ? $brand['name'] : Setting::getCompanyName('Núñez and Son');
        $companyCif = (!empty($brand['cif'])) ? $brand['cif'] : Setting::get('company_cif', 'B-12345678');
        $companyEmail = (!empty($brand['email'])) ? $brand['email'] : Setting::get('company_email', 'info@eventosmusicales.es');
        $companyPhone = (!empty($brand['phone'])) ? $brand['phone'] : Setting::get('company_phone', '+34 622 634 790');
        $companyAddress = Setting::get('company_address', 'Calle Principal s/n');
        $companyCity = Setting::get('company_city', 'Navarrete');
        $companyIban = (!empty($brand['iban'])) ? $brand['iban'] : Setting::get('company_iban', 'ES00 0000 0000 0000 0000 0000');
        $companyBizum = (!empty($brand['bizum'])) ? $brand['bizum'] : Setting::get('company_bizum', '622634790');

        $signingCity = $clientCity ?: $companyCity;
        $courtCity = Setting::get('company_court_city', $companyCity === 'Navarrete' ? 'Logroño (La Rioja)' : $companyCity);

        // Resolve active payment methods
        $activePaymentMethods = $quote ? $quote->active_payment_methods : ['transfer', 'bizum'];
        $paymentMethodTexts = [];
        foreach ($activePaymentMethods as $pm) {
            if ($pm === 'transfer') {
                $paymentMethodTexts[] = "Transferencia bancaria al IBAN {$companyIban}";
            } elseif ($pm === 'bizum') {
                $paymentMethodTexts[] = "Bizum al {$companyBizum}";
            } elseif ($pm === 'cash') {
                $paymentMethodTexts[] = "Abono en efectivo / metálico al DJ / técnico el día del evento";
            } elseif ($pm === 'card') {
                $paymentMethodTexts[] = "Tarjeta / TPV";
            } elseif ($pm === 'other') {
                $paymentMethodTexts[] = "Otro método previamente acordado";
            }
        }
        if (empty($paymentMethodTexts)) {
            $paymentMethodTexts[] = "Transferencia bancaria al IBAN {$companyIban} o Bizum al {$companyBizum}";
        }
        if (count($paymentMethodTexts) === 1) {
            $formattedPaymentMethods = $paymentMethodTexts[0];
        } else {
            $last = array_pop($paymentMethodTexts);
            $formattedPaymentMethods = implode(', ', $paymentMethodTexts) . ' o ' . $last;
        }

        $replacements = [
            '{{ empresa }}' => $companyName,
            '{{ cif_empresa }}' => $companyCif,
            '{{ email_empresa }}' => $companyEmail,
            '{{ telefono_empresa }}' => $companyPhone,
            '{{ direccion_empresa }}' => $companyAddress,
            '{{ ciudad_empresa }}' => $companyCity,
            '{{ ciudad_firma }}' => $signingCity,
            '{{ ciudad_juzgados }}' => $courtCity,
            '{{ iban_empresa }}' => $companyIban,
            '{{ bizum_empresa }}' => $companyBizum,
            '{{ formas_pago }}' => $formattedPaymentMethods,
            '{{ metodos_pago }}' => $formattedPaymentMethods,
            '{{ cliente }}' => $clientName,
            '{{ nif_cliente }}' => $clientDni,
            '{{ email_cliente }}' => $clientEmail,
            '{{ telefono_cliente }}' => $clientPhone,
            '{{ direccion_cliente }}' => $clientAddress,
            '{{ codigo_postal_cliente }}' => $clientPostalCode,
            '{{ ciudad_cliente }}' => $clientCity,
            '{{ provincia_cliente }}' => $clientProvince,
            '{{ ciudad_cliente_info }}' => $clientCityInfo,
            '{{ evento }}' => $event ? $event->name : 'Evento',
            '{{ tipo_evento }}' => $event?->type ?? 'Boda / Evento',
            '{{ fecha_evento }}' => $event && $event->event_date ? Carbon::parse($event->event_date)->format('d/m/Y') : 'Por determinar',
            '{{ ubicacion }}' => $event?->location ?? 'Por determinar',
            '{{ hora_inicio }}' => $event ? $event->effective_start_time : 'Por determinar',
            '{{ hora_fin }}' => $event ? $event->effective_end_time : 'Fin de fiesta / Según desarrollo',
            '{{ horario_baile }}' => $event ? $event->dance_schedule_label : 'Horario no definido',
            '{{ cronograma_completo }}' => $event ? $event->schedule_breakdown : '',
            '{{ importe }}' => number_format($amount, 2, ',', '.') . ' €',
            '{{ importe_total }}' => number_format($amount, 2, ',', '.') . ' €',
            '{{ importe_senal }}' => number_format($signalAmount, 2, ',', '.') . ' €',
            '{{ importe_restante }}' => number_format($remainingAmount, 2, ',', '.') . ' €',
            '{{ servicios_contratados }}' => $renderedServices,
            '{{ desglose_servicios }}' => $renderedServices,
            '{{ numero_presupuesto }}' => $quote ? 'PRE-' . str_pad($quote->id, 5, '0', STR_PAD_LEFT) : 'PRE-' . str_pad($event->id ?? 1, 5, '0', STR_PAD_LEFT),
            '{{ numero_contrato }}' => 'CTR-' . str_pad($contract->id, 5, '0', STR_PAD_LEFT),
            '{{ fecha_emision }}' => ($contract->created_at ?? now())->format('d/m/Y'),
        ];

        $titleTemplate = self::getDefaultTitle();
        $bodyTemplate = self::getDefaultBody();
        $footerTemplate = self::getDefaultFooter();

        if (!str_contains($bodyTemplate, '{{ formas_pago }}') && !str_contains($bodyTemplate, '{{ metodos_pago }}')) {
            $bodyTemplate = str_replace([
                'Transferencia bancaria al IBAN {{ iban_empresa }} o Bizum al {{ bizum_empresa }}.',
                'Transferencia bancaria al IBAN {{ iban_empresa }} o Bizum al {{ bizum_empresa }}',
            ], '{{ formas_pago }}.', $bodyTemplate);
        }

        $renderedTitle = str_replace(array_keys($replacements), array_values($replacements), $titleTemplate);
        $renderedBody = str_replace(array_keys($replacements), array_values($replacements), $bodyTemplate);
        $renderedFooter = str_replace(array_keys($replacements), array_values($replacements), $footerTemplate);

        return [
            'title' => $renderedTitle,
            'body' => $renderedBody,
            'footer' => $renderedFooter,
            'contract' => $contract,
            'event' => $event,
            'client' => $client,
            'amount' => $amount,
            'replacements' => $replacements,
        ];
    }
}
