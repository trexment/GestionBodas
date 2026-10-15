<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Contract;
use App\Models\User;
use App\Models\Setting;
use Carbon\Carbon;

class WhatsAppTemplateService
{
    public static function cleanPhone($phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', (string)$phone);
        if (strlen($clean) === 9 && in_array(substr($clean, 0, 1), ['6', '7'])) {
            $clean = '34' . $clean;
        }
        return $clean;
    }

    public static function buildUrl($phone, $message): string
    {
        $cleanPhone = self::cleanPhone($phone);
        if (!empty($cleanPhone)) {
            return "https://wa.me/{$cleanPhone}?text=" . rawurlencode($message);
        }
        return "https://api.whatsapp.com/send?text=" . rawurlencode($message);
    }

    public static function getLink($phone, $message): string
    {
        return self::buildUrl($phone, $message);
    }

    public static function getWebLink($phone, $message): string
    {
        $cleanPhone = self::cleanPhone($phone);
        if (!empty($cleanPhone)) {
            return "https://web.whatsapp.com/send?phone={$cleanPhone}&text=" . rawurlencode($message);
        }
        return "https://web.whatsapp.com/send?text=" . rawurlencode($message);
    }

    public static function cuestionario(Event $event): string
    {
        $clientName = $event->client ? $event->client->name : 'pareja';
        $companyName = Setting::get('company_name', 'Eventos Musicales');
        $formUrl = route('guest.form', $event->token);

        return "¡Hola {$clientName}! 👋 Os escribe el equipo de *{$companyName}*.\n\n"
             . "Se acerca vuestro gran día (*{$event->name}*) y estamos preparando la música y momentos especiales. 🎵💍\n\n"
             . "Por favor, cuando tengáis un momento, completad vuestras canciones y preferencias desde este enlace:\n"
             . "👉 {$formUrl}\n\n"
             . "Podréis elegir las canciones de la entrada, tarta, baile, regalos y vuestros temazos favoritos para la fiesta.\n\n"
             . "¡Cualquier duda nos decís! 🎧";
    }

    public static function contrato($contractOrEvent): string
    {
        $companyName = Setting::get('company_name', 'Eventos Musicales');
        if ($contractOrEvent instanceof Contract) {
            $event = $contractOrEvent->event;
            $token = $contractOrEvent->token ?: ($event ? $event->token : '');
            $eventName = $event ? $event->name : 'vuestro evento';
            $clientName = ($event && $event->client) ? $event->client->name : 'pareja';
        } else {
            $event = $contractOrEvent;
            $token = $event->token;
            $eventName = $event->name;
            $clientName = $event->client ? $event->client->name : 'pareja';
        }

        $signUrl = route('guest.contract', $token);

        return "¡Hola {$clientName}! 👋 Os escribe *{$companyName}*.\n\n"
             . "Os compartimos el enlace para revisar y firmar online vuestro contrato oficial de servicios para *{$eventName}*:\n\n"
             . "✍️ {$signUrl}\n\n"
             . "Podéis confirmar vuestros datos y estampar la firma digital directamente desde la pantalla de vuestro móvil en 30 segundos.\n\n"
             . "¡Un saludo!";
    }

    public static function liquidacion(Event $event): string
    {
        $clientName = $event->client ? $event->client->name : 'pareja';
        $quote = $event->quotes()->latest()->first();
        $amount = $quote ? number_format($quote->amount, 2, ',', '.') . ' €' : 'el importe acordado';

        return "¡Hola {$clientName}! 👋 Esperamos que los preparativos para *{$event->name}* vayan de maravilla.\n\n"
             . "Os recordamos que, según las condiciones acordadas, la liquidación final del servicio ({$amount}) debe realizarse antes de la fecha del evento.\n\n"
             . "Si ya habéis realizado la transferencia, ignorad este mensaje. Si necesitáis el número de cuenta o justificante, avisadnos.\n\n"
             . "¡Estamos deseando que llegue el gran día! 🎧✨";
    }

    /**
     * ETAPA 1: APERTURA & PRIMERA TOMA DE CONTACTO (Respuesta inmediata / Lead in).
     * Conexión rápida, confirmación de disponibilidad en agenda y pregunta rompehielos.
     */
    public static function primeraTomaContacto(Event $event, string $variant = 'estandar'): string
    {
        $clientName = $event->client ? $event->client->name : 'pareja';
        $brand = Setting::getBrandInfo($event ? $event->brand_clean : null);
        $companyName = $brand['name'] ?? Setting::get('company_name', 'Núñez & Son');
        $eventDate = $event->event_date ? Carbon::parse($event->event_date)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') : 'vuestra fecha';
        $location = $event->location ?: 'vuestro espacio elegido';

        if ($variant === 'disponibilidad') {
            return "¡Hola {$clientName}! 👋 Os escribe *{$companyName}*.\n\n"
                 . "¡Enhorabuena por vuestro compromiso! 💍 Acabo de consultar la agenda para el *{$eventDate}* en *{$location}* y ¡tenemos la fecha disponible!\n\n"
                 . "Para poder orientaros y ver qué tipo de montaje os encajaría mejor (barra libre, sonido en directo para cóctel/ceremonia, iluminación dinámica...), ¿tenéis un momento para contarme qué tenéis en mente? 🎧";
        }

        if ($variant === 'filtro_finca') {
            return "¡Hola {$clientName}! 👋 ¡Qué alegría recibir vuestra consulta para el *{$eventDate}*! Os escribe *{$companyName}*.\n\n"
                 . "Tenemos la fecha libre en el calendario. 🎉 Antes de enviaros catálogos genéricos que no os digan nada, nos gusta conocer vuestra idea de fiesta para proponeros justo lo que necesitáis.\n\n"
                 . "¿Tenéis ya cerrado el lugar o finca en {$location}? ¿Buscáis música para todo el día o principalmente para la barra libre? ¡Un abrazo!";
        }

        // Variante por defecto: estándar cálida y directa
        return "¡Hola {$clientName}! 👋 ¡Qué ilusión saber de vosotros! Os escribe *{$companyName}*.\n\n"
             . "Confirmado: ¡tenemos libre vuestra fecha (*{$eventDate}*)! 🎉 Nos encantaría acompañaros en *{$location}* y montar un fiestón a vuestra medida.\n\n"
             . "Para ponéroslo muy fácil y orientaros sin compromiso, ¿me contáis si tenéis ya en mente algún estilo de música o canciones que no puedan faltar en vuestra boda? ¡Un saludo!";
    }

    /**
     * ETAPA 2 & 3: SEGUIMIENTO DE CONVERSACIÓN EN CURSO (Descubrimiento y mantener el interés).
     * Retomar la conversación de forma natural tras unos días, sin agobiar.
     */
    public static function seguimientoConversacion(Event $event, string $variant = 'suave'): string
    {
        $clientName = $event->client ? $event->client->name : 'pareja';
        $brand = Setting::getBrandInfo($event ? $event->brand_clean : null);
        $companyName = $brand['name'] ?? Setting::get('company_name', 'Núñez & Son');
        $eventDate = $event->event_date ? Carbon::parse($event->event_date)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') : 'vuestro gran día';
        $eventName = $event->name ?: 'vuestro evento';

        if ($variant === 'preparativos') {
            return "¡Hola {$clientName}! 👋 Espero que estéis teniendo muy buena semana. Imagino que estaréis a tope con los preparativos de *{$eventName}*.\n\n"
                 . "Solo os escribía para ver cómo vais y si habéis podido valorar lo que comentamos de la música para el *{$eventDate}*.\n\n"
                 . "¡Cualquier duda que os surja, estoy a vuestro lado para ayudaros en lo que necesitéis! 🎧✨";
        }

        // Variante suave (por defecto)
        return "¡Hola {$clientName}! 👋 ¿Cómo vais con los preparativos de *{$eventName}*?\n\n"
             . "Os escribía un momento por aquí por si os surgió cualquier duda con lo que estuvimos hablando o si queréis que miremos algún detalle del montaje o de los horarios.\n\n"
             . "¡Sin ningún compromiso, cuando tengáis un ratillo me decís! 🎧";
    }

    /**
     * PROPUESTA DE LLAMADA O VIDEOLLAMADA RÁPIDA (10-15 min).
     * Conectar antes de enviar presupuesto para entender gustos y multiplicar cierres (Método Madrigal).
     */
    public static function propuestaLlamada(Event $event): string
    {
        $clientName = $event->client ? $event->client->name : 'pareja';
        $brand = Setting::getBrandInfo($event ? $event->brand_clean : null);
        $companyName = $brand['name'] ?? Setting::get('company_name', 'Núñez & Son');

        return "¡Hola {$clientName}! 👋 Os escribe *{$companyName}*.\n\n"
             . "Para no volveros locos con mil audios y mensajes de texto por WhatsApp, ¿qué os parece si hacemos una llamada o videollamada rápida de 10-15 minutos esta semana?\n\n"
             . "Así nos ponemos cara, me contáis vuestro estilo de música, los temas que no pueden faltar (y los que queréis prohibir), y os explico exactamente cómo organizamos el montaje para que tengáis la máxima tranquilidad.\n\n"
             . "¿Qué día y franja horaria os vendría mejor? (Tardes o noches nos adaptamos sin problema). ¡Un abrazo! 💻📱";
    }

    /**
     * ETAPA 4: ENVÍO DEL DOSSIER CON EL PRESUPUESTO PERSONALIZADO.
     * Presentación de la propuesta con valor, fotos de montajes y presupuesto final mascadito al final.
     */
    public static function envioDossierPresupuesto(Event $event, ?\App\Models\Quote $quote = null): string
    {
        $clientName = $event->client ? $event->client->name : 'pareja';
        $brand = Setting::getBrandInfo($event ? $event->brand_clean : null);
        $companyName = $brand['name'] ?? Setting::get('company_name', 'Núñez & Son');
        $eventDate = $event->event_date ? Carbon::parse($event->event_date)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') : 'vuestra fecha';
        $location = $event->location ?: 'el lugar acordado';
        
        $activeQuote = $quote ?: $event->quotes()->latest()->first();
        $totalStr = $activeQuote ? number_format($activeQuote->amount, 2, ',', '.') . ' €' : '';
        $validityDays = $activeQuote ? $activeQuote->validity_days : 15;
        $expiresStr = ($activeQuote && $activeQuote->expires_at) ? $activeQuote->expires_at->format('d/m/Y') : '';

        $priceNote = $totalStr ? " (*{$totalStr}*, con IVA y todo incluido)" : "";
        $validityNote = $expiresStr ? " Para vuestra tranquilidad, os guardamos la fecha bloqueada de forma provisional durante {$validityDays} días (hasta el *{$expiresStr}*) para que lo valoréis con calma." : " Os guardamos la fecha bloqueada unos días para que lo valoréis en casa.";

        return "¡Hola {$clientName}! 👋 Os escribe *{$companyName}*.\n\n"
             . "Os comparto por aquí vuestro dossier completo y personalizado para *{$event->name}* ({$eventDate} en {$location}):\n\n"
             . "📑 En el documento podréis ver fotos reales de nuestros montajes de sonido e iluminación profesional, cómo trabajamos paso a paso, y al final tenéis el *presupuesto cerrado con las opciones seleccionadas*{$priceNote}.\n\n"
             . "{$validityNote}\n\n"
             . "Echadle un vistazo cuando tengáis un momento y decidme: ¿qué os parece? ¿Tenéis alguna duda? 🎧✨";
    }

    /**
     * SEGUIMIENTO TRAS ENVIAR PRESUPUESTO (A los 2-3 días).
     * Toque ligero y educado antes de pasar a la técnica del NO o psicología inversa.
     */
    public static function seguimientoPostPresupuesto(Event $event): string
    {
        $clientName = $event->client ? $event->client->name : 'pareja';
        $brand = Setting::getBrandInfo($event ? $event->brand_clean : null);
        $companyName = $brand['name'] ?? Setting::get('company_name', 'Núñez & Son');

        return "¡Hola {$clientName}! 👋 Os escribe *{$companyName}*.\n\n"
             . "¿Pudisteis echarle un vistazo al dossier y presupuesto que os compartí?\n\n"
             . "Si os queda alguna duda sobre lo que incluye, o si queréis que adaptemos algún servicio o detalle para ajustarlo exactamente a lo que tenéis pensado, decídmelo por aquí con total confianza o nos llamamos 5 minutos.\n\n"
             . "¡Seguimos en contacto! 🎧";
    }

    /**
     * Técnica del NO (Chris Voss - Never Split the Difference).
     * Pregunta orientada al "NO" para reactivar clientes sin ser insistente ni desesperado.
     * Decir "NO" aporta seguridad y control al cliente, provocando una respuesta inmediata.
     */
    public static function seguimientoPreguntaNo(Event $event): string
    {
        $clientName = $event->client ? $event->client->name : 'pareja';
        $brand = Setting::getBrandInfo($event ? $event->brand_clean : null);
        $companyName = $brand['name'] ?? Setting::get('company_name', 'Núñez & Son');
        $eventDate = $event->event_date ? Carbon::parse($event->event_date)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') : 'vuestro gran día';
        $eventName = $event->name ?: 'vuestro evento';

        return "¡Hola {$clientName}! 👋 Os escribe *{$companyName}*.\n\n"
             . "Os hago una consulta muy rápida y directa: *¿habéis decidido descartar la opción de contar con nosotros para {$eventName} ({$eventDate})?*\n\n"
             . "Os pregunto con total confianza porque nos ha entrado otra consulta para vuestra misma fecha y, por respeto y deferencia a vosotros que contactasteis primero, no queríamos disponer del día sin consultaros antes.\n\n"
             . "Si habéis elegido otra opción o preferís no seguir adelante, no os preocupéis en absoluto: respondedme simplemente *\"la descartamos\"* y así liberamos la fecha para la otra pareja con total tranquilidad.\n\n"
             . "Y si por el contrario seguís con la idea de contar con nosotros o queréis que adaptemos algún detalle del presupuesto a vuestra medida, decidnos y lo dejamos reservado hoy mismo.\n\n"
             . "¡Un abrazo y que tengáis muy buen día! 🎧✨";
    }

    /**
     * Psicología Inversa & Cierre de Expediente (Técnica "Breakup Message").
     * Asume amablemente que han elegido otra alternativa para eliminar la presión
     * y activar el miedo a perder la fecha (FOMO / aversión a la pérdida).
     */
    public static function seguimientoPsicologiaInversa(Event $event): string
    {
        $clientName = $event->client ? $event->client->name : 'pareja';
        $brand = Setting::getBrandInfo($event ? $event->brand_clean : null);
        $companyName = $brand['name'] ?? Setting::get('company_name', 'Núñez & Son');
        $eventDate = $event->event_date ? Carbon::parse($event->event_date)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') : 'la fecha prevista';
        $eventName = $event->name ?: 'vuestro evento';

        return "¡Hola {$clientName}! 👋 Os escribe *{$companyName}*.\n\n"
             . "Imagino que estáis a tope de gestiones con los preparativos de *{$eventName}*.\n\n"
             . "Como no hemos tenido noticias vuestras tras enviaros la propuesta, doy por hecho que habéis decidido elegir otra opción o aplazar la música para más adelante (lo cual entendemos perfectamente).\n\n"
             . "Para no ser pesados ni saturaros con más mensajes, cerramos vuestro expediente por nuestra parte para no molestaros más.\n\n"
             . "Solo os escribía para despedirme y desearos lo mejor en vuestro evento. Si por algún motivo cambiáis de opinión o aún queréis contar con nosotros antes de que la fecha ({$eventDate}) se ocupe definitivamente, sabéis dónde encontrarnos.\n\n"
             . "¡Mucho éxito con todo y un abrazo grande! 🎉";
    }

    /**
     * ETAPA MADRIGAL DÍA 2-3: DUDAS & FLEXIBILIDAD (Post-presupuesto).
     * No presiona por la fecha, sino que ofrece adaptabilidad y cercanía.
     */
    public static function seguimientoDia3Dudas(Event $event): string
    {
        $clientName = $event->client ? $event->client->name : 'pareja';
        $brand = Setting::getBrandInfo($event ? $event->brand_clean : null);
        $companyName = $brand['name'] ?? Setting::get('company_name', 'Núñez & Son');
        $eventName = $event->name ?: 'vuestro evento';

        return "¡Hola {$clientName}! 👋 Os escribe *{$companyName}*.\n\n"
             . "¿Pudisteis echarle un vistazo con calma al presupuesto que os pasé para *{$eventName}*?\n\n"
             . "Os escribía porque muchas veces surgen dudas con los horarios, o queréis quitar o añadir algún extra (como la plataforma 360, la hora loca o sonido para el cóctel). Nos adaptamos al 100% a lo que tengáis en mente sin ningún problema.\n\n"
             . "¿Cómo lo veis? ¿Tenéis alguna duda o queréis que ajustemos algún detalle? 🎧✨";
    }

    /**
     * ETAPA MADRIGAL DÍA 5-7: CIERRE POR ESCASEZ (Prioridad 24 horas).
     * Activa la urgencia real porque otra pareja ha preguntado o se va a abrir la fecha.
     */
    public static function seguimientoDia7Escasez(Event $event): string
    {
        $clientName = $event->client ? $event->client->name : 'pareja';
        $brand = Setting::getBrandInfo($event ? $event->brand_clean : null);
        $companyName = $brand['name'] ?? Setting::get('company_name', 'Núñez & Son');
        $eventDate = $event->event_date ? Carbon::parse($event->event_date)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') : 'vuestra fecha';
        $location = $event->location ?: 'vuestra zona';

        return "¡Hola {$clientName}! 👋 Os escribo rápido desde *{$companyName}*.\n\n"
             . "Nos acaba de entrar otra consulta para vuestra misma fecha (*{$eventDate}* en {$location}).\n\n"
             . "Como me escribisteis vosotros primero y ya tenéis el presupuesto en mano, por respeto y cortesía os guardo la prioridad durante *24 horas* antes de disponer del día.\n\n"
             . "¿Seguís interesados en reservar la fecha o la dejamos libre para la otra pareja? Decidme algo en cuanto podáis para tenerlo en cuenta. ¡Un saludo! 🎧";
    }

    /**
     * Determina la clave de seguimiento recomendada según los días sin respuesta (Método Madrigal)
     */
    public static function getRecommendedFollowupKey(Event $event): string
    {
        $days = $event->days_without_response;
        if ($days <= 3) {
            return 'dia3_dudas';
        }
        if ($days <= 8) {
            return 'dia7_escasez';
        }
        if ($days <= 14) {
            return 'pregunta_no';
        }
        return 'psicologia_inversa';
    }

    /**
     * Devuelve todas las opciones de seguimiento con su URL y estado recomendado
     */
    public static function getFollowupOptions(Event $event): array
    {
        $recommendedKey = self::getRecommendedFollowupKey($event);
        $clientPhone = $event->client ? $event->client->phone : '';

        $options = [
            'dia3_dudas' => [
                'key' => 'dia3_dudas',
                'title' => 'Día 2-3: Dudas & Flexibilidad',
                'stage' => 'Día 2 a 3',
                'icon' => '💡',
                'description' => 'Tacto suave: consulta si tienen dudas y ofrece adaptar horarios o extras.',
                'text' => self::seguimientoDia3Dudas($event),
            ],
            'dia7_escasez' => [
                'key' => 'dia7_escasez',
                'title' => 'Día 5-7: Cierre por Escasez (Prioridad 24h)',
                'stage' => 'Día 5 a 7',
                'icon' => '⚡',
                'description' => 'Urgencia Madrigal: avisa de otra consulta y ofrece 24h de prioridad.',
                'text' => self::seguimientoDia7Escasez($event),
            ],
            'pregunta_no' => [
                'key' => 'pregunta_no',
                'title' => 'Día 8-14: Técnica del NO (Chris Voss)',
                'stage' => 'Día 8 a 14',
                'icon' => '🎯',
                'description' => 'Pregunta directa orientada al NO: ¿habéis decidido descartar la opción?',
                'text' => self::seguimientoPreguntaNo($event),
            ],
            'psicologia_inversa' => [
                'key' => 'psicologia_inversa',
                'title' => 'Día 15+: Cierre de Expediente (Psicología Inversa)',
                'stage' => 'Día 15+',
                'icon' => '🚪',
                'description' => 'Asume que han elegido otra alternativa y cierra el expediente cortésmente.',
                'text' => self::seguimientoPsicologiaInversa($event),
            ],
            'presupuesto' => [
                'key' => 'presupuesto',
                'title' => 'Objeción de Presupuesto',
                'stage' => 'Económica',
                'icon' => '💶',
                'description' => 'Consulta si el freno es económico para ofrecer adaptar el equipamiento.',
                'text' => self::seguimientoPresupuestoNo($event),
            ],
        ];

        foreach ($options as $k => &$opt) {
            $opt['is_recommended'] = ($k === $recommendedKey);
            $opt['url'] = self::buildUrl($clientPhone, $opt['text']);
            $opt['web_url'] = self::getWebLink($clientPhone, $opt['text']);
        }

        return $options;
    }

    public static function followupReminder(Event $event, ?string $variant = null): array
    {
        $clientPhone = $event->client ? $event->client->phone : '';
        if (empty($variant)) {
            $variant = self::getRecommendedFollowupKey($event);
        }

        $text = match($variant) {
            'dia3_dudas' => self::seguimientoDia3Dudas($event),
            'dia7_escasez' => self::seguimientoDia7Escasez($event),
            'psicologia_inversa' => self::seguimientoPsicologiaInversa($event),
            'presupuesto' => self::seguimientoPresupuestoNo($event),
            default => self::seguimientoPreguntaNo($event),
        };

        $title = match($variant) {
            'dia3_dudas' => 'Día 2-3: Dudas & Flexibilidad',
            'dia7_escasez' => 'Día 5-7: Cierre por Escasez (Prioridad 24h)',
            'psicologia_inversa' => 'Psicología Inversa / Cierre de Expediente',
            'presupuesto' => 'Reactivación por Presupuesto (Técnica del NO)',
            default => 'Técnica del NO / ¿Descartáis la fecha?',
        };

        $icon = match($variant) {
            'dia3_dudas' => '💡',
            'dia7_escasez' => '⚡',
            'psicologia_inversa' => '🚪',
            'presupuesto' => '💶',
            default => '🎯',
        };

        return [
            'key' => $variant,
            'title' => $title,
            'icon' => $icon,
            'phone' => $clientPhone,
            'text' => $text,
            'url' => self::buildUrl($clientPhone, $text),
            'web_url' => self::getWebLink($clientPhone, $text),
        ];
    }

    public static function hojaRutaDj(Event $event, ?User $user = null): string
    {
        $targetUser = $user ?: $event->dj;
        $userName = $targetUser ? $targetUser->name : 'Compañero';
        $date = $event->event_date ? Carbon::parse($event->event_date)->format('d/m/Y') : 'Por determinar';
        $location = $event->location ?: 'Sin ubicación fija';
        $clientName = $event->client ? $event->client->name : 'Cliente';
        $clientPhone = $event->client ? $event->client->phone : 'Sin teléfono';
        $boothUrl = route('staff.live', $event->token);

        $venueInfo = '';
        if (!empty($event->venue_contact_name) || !empty($event->venue_contact_phone)) {
            $venueInfo .= "\n🏢 *Contacto Lugar/Bodega:* " . ($event->venue_contact_name ?: 'Responsable') . ($event->venue_contact_phone ? " ({$event->venue_contact_phone})" : "");
        }
        if (!empty($event->venue_notes)) {
            $venueInfo .= "\n🔧 *Notas de Acceso/Montaje:* " . $event->venue_notes;
        }

        $scheduleInfo = '';
        if (!empty($event->setup_date) || !empty($event->start_time)) {
            $scheduleInfo .= "\n🚗 *Montaje:* " . $event->setup_schedule_label;
        }
        if (!empty($event->ceremony_time)) {
            $scheduleInfo .= "\n💍 *Ceremonia:* " . substr($event->ceremony_time, 0, 5) . " h";
        }
        if (!empty($event->cocktail_time)) {
            $scheduleInfo .= "\n🍸 *Cóctel:* " . substr($event->cocktail_time, 0, 5) . " h";
        }
        if (!empty($event->banquet_time)) {
            $scheduleInfo .= "\n🍽️ *Banquete:* " . substr($event->banquet_time, 0, 5) . " h";
        }
        if (!empty($event->dance_start_time)) {
            $scheduleInfo .= "\n⏰ *Horario Baile (DJ):* " . $event->dance_schedule_label;
        }
        if (!empty($event->schedule_notes)) {
            $scheduleInfo .= "\n📝 *Timing:* " . $event->schedule_notes;
        }

        return "¡Hola {$userName}! 🎧 Tienes asignado el evento *{$event->name}*:\n\n"
             . "📅 *Fecha:* {$date}\n"
             . "📍 *Ubicación:* {$location}\n"
             . "👤 *Cliente:* {$clientName} ({$clientPhone})"
             . $scheduleInfo
             . $venueInfo . "\n\n"
             . "📱 *Modo Cabina / Escaleta en Vivo:* {$boothUrl}\n\n"
             . "¡A darlo todo en el evento! 🔥";
    }

    public static function questionnaireReminder(Event $event): array
    {
        $clientPhone = $event->client ? $event->client->phone : '';
        $text = self::cuestionario($event);

        return [
            'title' => 'Recordatorio Cuestionario Musical',
            'icon' => '🎵',
            'phone' => $clientPhone,
            'text' => $text,
            'url' => self::buildUrl($clientPhone, $text),
        ];
    }

    public static function contractSignReminder(Event $event, ?Contract $contract = null): array
    {
        $clientPhone = $event->client ? $event->client->phone : '';
        $text = self::contrato($contract ?: $event);

        return [
            'title' => 'Enlace de Firma de Contrato',
            'icon' => '📜',
            'phone' => $clientPhone,
            'text' => $text,
            'url' => self::buildUrl($clientPhone, $text),
        ];
    }

    public static function paymentReminder(Event $event): array
    {
        $clientPhone = $event->client ? $event->client->phone : '';
        $text = self::liquidacion($event);

        return [
            'title' => 'Recordatorio de Pago / Liquidación',
            'icon' => '💶',
            'phone' => $clientPhone,
            'text' => $text,
            'url' => self::buildUrl($clientPhone, $text),
        ];
    }

    public static function staffBriefing(Event $event, User $user, string $role = 'dj'): array
    {
        $roleTitle = $role === 'dj' ? 'DJ Principal' : 'Técnico / Asistente';
        $text = self::hojaRutaDj($event, $user);

        return [
            'title' => "Hoja de Ruta {$roleTitle}",
            'icon' => '📋',
            'phone' => $user->phone,
            'text' => $text,
            'url' => self::buildUrl($user->phone, $text),
        ];
    }

    public static function meetingConfirmation(\App\Models\ClientMeeting $meeting): string
    {
        $event = $meeting->event;
        $clientName = $event && $event->client ? $event->client->name : 'pareja';
        $brand = Setting::getBrandInfo($event ? $event->brand_clean : null);
        $companyName = $brand['name'] ?? Setting::get('company_name', 'Núñez & Son');

        $dateFormatted = $meeting->meeting_date ? $meeting->meeting_date->translatedFormat('l, d \d\e F \a \l\a\s H:i\h') : 'Por determinar';
        $locText = $meeting->location_type === 'video_call' 
            ? "💻 *Videollamada online*" . ($meeting->video_call_url ? "\nEnlace: " . $meeting->video_call_url : '')
            : ($meeting->location_type === 'phone' ? "📱 *Llamada telefónica*" : "📍 *Lugar:* " . ($meeting->location ?: 'Lugar acordado'));

        return "¡Hola {$clientName}! 👋 Os escribe *{$companyName}*.\n\n"
             . "Os confirmamos nuestra cita para *{$meeting->title}* ({$meeting->meeting_type_label}):\n\n"
             . "📅 *Fecha y hora:* {$dateFormatted}\n"
             . "⏱️ *Duración estimada:* {$meeting->duration_minutes} min\n"
             . "{$locText}\n\n"
             . "¡Nos vemos pronto para avanzar con los preparativos de vuestro evento! 🎉";
    }

    public static function meetingReminder(\App\Models\ClientMeeting $meeting): string
    {
        $event = $meeting->event;
        $clientName = $event && $event->client ? $event->client->name : 'pareja';
        $brand = Setting::getBrandInfo($event ? $event->brand_clean : null);
        $companyName = $brand['name'] ?? Setting::get('company_name', 'Núñez & Son');

        $dateFormatted = $meeting->meeting_date ? $meeting->meeting_date->translatedFormat('d/m/Y \a \l\a\s H:i\h') : '';
        $locText = $meeting->location_type === 'video_call' 
            ? "💻 *Videollamada online*" . ($meeting->video_call_url ? "\nEnlace: " . $meeting->video_call_url : '')
            : ($meeting->location_type === 'phone' ? "📱 *Llamada telefónica*" : "📍 *Lugar:* " . ($meeting->location ?: 'Lugar acordado'));

        return "¡Hola {$clientName}! 👋 Recordatorio de *{$companyName}*:\n\n"
             . "Os recordamos que tenemos nuestra reunión (*{$meeting->title}*) el próximo *{$dateFormatted}*.\n\n"
             . "{$locText}\n\n"
             . "¡Hasta pronto! 🎧";
    }
}
