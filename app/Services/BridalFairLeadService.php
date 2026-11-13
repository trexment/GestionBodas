<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Servicio para la importación, análisis y sincronización de leads procedentes de ferias nupciales (La Rioja Bridal Market).
 */
class BridalFairLeadService
{
    /**
     * CSV original facilitado por la organización de la III Edición La Rioja Bridal Market
     */
    public static function getDefaultRiojaBridalMarketCsv(): string
    {
        return <<<'CSV'
III EDICIÓN LA RIJA BRIDAL MARKET - FORMULARIO PREVIO,,,,,,,,,
,Nombre de usuario,Nombre completo,Correo electrónico,Número de teléfono,¿Te casas próximamente?,Estes Novi@,Solicitas invitación como:,¿Cuántas personas asistirán contigo? (no es necesario rellenar un formulario por cada asistente),"Comentarios adicionales: indícanos si tienes fecha para tu boda, si estas buscando algún proveedor en concreto etc."
1,luciayubero1998@gmail.com,Lucia Yubero,luciayubero1998@gmail.com,689404296,Sí,,Novi@,2 conmigo,
2,elenagurriamiranda@gmail.com,Elena Gurría Miranda,elenagurriamiranda@gmail.com,628425504,Sí,,Novi@,3,
3,nereasaenzdeurturi@gmail.com,Nerea Sáenz de Urturi Martínez ,nereasaenzdeurturi@gmail.com,657810682,Sí,,Novi@,1 (mi pareja y yo),"26 de junio de 2027. Viaje luna de miel, joyería,  música y regalos personalizados"
4,anapagola8@gmail.com,Ana Pagola Dallo,anaymario1911@gmail.com,679280282,Sí,,Novi@,1,La fecha de la boda es 19 de junio
5,alvarezmolinaaida@gmail.com,Aida Alvarez Molina ,Aida_maria1992@hotmail.com,659194307,Sí,,Novi@,Mi pareja y yo,
6,asierglez27@gmail.com,Asier González García ,asierglez27@gmail.com,651386069,Sí,No sabemos todavía,Novi@,1,
7,naiaramaduro1993@gmail.com,Naiara Maduro,Naiaramaduro1993@gmail.com,628596313,Sí,2028,Novi@,4,
8,paula_giralda@hotmail.com,Paula Giralda Martínez ,paula_giralda@hotmail.com,626502616,Sí,2027,Novi@,Una persona ,2027-04-17
9,yolandalavegaibanez@gmail.com,Yolanda Lavega Ibañez,yolandalavegaibanez@gmail.com,637765249,Estamos mirando fechas,No sabemos todavía,Novi@,1,
10,laurab99.lb@gmail.com,Laura Barroso,laurab99.lb@gmail.com,633716870,Sí,2027,Novi@,2,
11,bodasheilayjulen@gmail.com,Sheila De Pablo Guerrero,bodasheilayjulen@gmail.com,628112971,Sí,2027,Novi@,1,2027-09-04
12,lidia.molina2@gmail.com,Lidia Molina Mouta,lidia.molina2@gmail.com,696751764,Sí,2027,Novi@,1,
13,rubiapatam19@gmail.com,Tamara Rubio Aparicio,rubiapatam19@gmail.com,655321935,Sí,2027,Novi@,Una,Tengo fecha
14,valentinagarciaholguin1994@gmail.com,Valentina garcia holguin,valentinagarciaholguin1994@gmail.com,652631186,Sí,2027,Novi@,4,
15,csm171198@outlook.com,Clara Sánchez Mendez,csm171198@outlook.com,650846064,Sí,2027,Novi@,1,25/09/2027. Queremos buscar a algún proveedor de papelería y detalles para invitados
16,pmanchuri@gmail.com,Paula Baños,paulain_94@hotmail.com,628878787,Sí,2027,Novi@,Yo y mi pareja ,En principio no 
17,aguileraangelica70@gmail.com,Angélica Aguilera García,aguileraangelica70@gmail.com,693763888,Sí,2027,Novi@,4 personas,Tenemos fecha y nos faltan algunos proveedores
18,juditvvvv16@gmail.com,Judit ,Juditvvvv16@gmail.com,692168883,Sí,No sabemos todavía,Novi@,Yo y mi pareja,
19,sheilagallardosaez@gmail.com,Sheila Gallardo ,sheilagallardosaez@gmail.com,620556687,Sí,2027,Novi@,Vamos 2,Nos casamos el 03/07/27 y queremos buscar inspiración de decoración y conocer proveedores
20,pardoanuk@gmail.com,Yulaidy  Pardo,pardoanuk@gmail.com,671887451,Sí,No sabemos todavía,Novi@,4,"Queremos ver todo lo posible he irnos con todas las ideas ya encaminadas para concretar  posibles cosas que llevaremos a nuestra boda. Gracias por crear, estos eventos tan importantes."
21,danielsofia2027@gmail.com,Sofía Yustes Biribay,danielsofia2027@gmail.com,608570550,Sí,2027,Novi@,1,18.09.2027
22,lauraruiz1193@gmail.com,Laura Ruiz,lauraruiz1193@gmail.com,695611985,Sí,2027,Novi@,1,
23,beatrizov98@gmail.com,Beatriz Ojeda,Beatrizov98@gmail.com,659010974,Sí,2028,Novi@,1,
24,nereavelilla4812@gmail.com,Nerea Velilla García ,Nereavelilla4812@gmail.com,649154137,Sí,2027,Novi@,1,
25,ruth123470@gmail.com,Ruth ,Ruth123470@gmail.com,696539527,Sí,2027,Novi@,1,7 de agosto
26,esaenza03@larioja.edu.es,Elena Sáenz ,elenasa896@gmail.com,,Sí,2027,Novi@,Somos 2 en total,
27,silvileonjota@gmail.com,Silvia León ,silvileonjota@gmail.com,618577369,Sí,2027,Novi@,1,
28,24.andrea.aguirre.00@gmail.com,Andrea aguirre,24.andrea.aguirre.00@gmail.com,630433703,Sí,2027,Novi@,1,
29,tatianarodriguezovejero@gmail.com,Tatiana,tatianarodriguezovejero@gmail.com,610388163,Sí,2027,Novi@,Me acompañara una persona ,
30,rgaros29@yahoo.es,Rosana Garós Martínez ,rgaros29@yahoo.es,627979290,Sí,2027,Novi@,2,
31,iratimsc@gmail.com,Irati Martinez,iratimsc@gmail.com,,Sí,2027,Novi@,1,
32,erikaalvarezbilbao@gmail.com,Erika Álvarez ,erikaalvarezbilbao@gmail.com,617821664,Sí,2027,Novi@,2026-03-02,
33,nereavelilla4812@gmail.com,Nerea Velilla García ,Nereavelilla4812@gmail.com,649154137,Sí,2027,Novi@,1,
34,lauripalas@gmail.com,Laura Palacios Urbiola ,lauripalas@gmail.com,677315254,Sí,2027,Novi@,2,
35,ainhoa.ct@hotmail.com,Ainhoa Castañera Troncoso,ainhoa.ct@hotmail.com,627814226,Sí,2027,Novi@,Somos 7,
36,ainaragol@gmail.com,Ainara,ainaragol@gmail.com,,Sí,2027,Novi@,1,
37,yuremasm@gmail.com,Yurema Sanchez,yuremasm@gmail.com,697226376,Sí,2027,Novi@,1,
38,ekiamaia96@gmail.com,Amaia Pérez de Nanclares,Ekiamaia96@gmail.com,,Sí,2027,Novi@,4,
39,molegarda@gmail.com,Mónica  Legarda,zamoralegarda@gmail.com,,Sí,2027,Novi@,1,
40,leyre.martinez.n@gmail.com,Leyre Martinez,Leyre.martinez.n@gmail.com,687239990,Sí,2027,Novi@,Una ,
41,beatrizestefaniagutierrez@gmail.com,Beatriz,beatrizestefaniagutierrez@gmail.com,,Sí,2027,Novi@,1,
42,mercedesgoru97@gmail.com,Mercedes  Gómez ,Mercedesgoru97@gmail.com,634 85 28 21,Sí,2027,Novi@,1,
43,eneida.fernandez@hotmail.com,Eneida Fdez de Labastida,Eneida.fernandez@hotmail.com,699806456,Sí,2027,Novi@,Seremos 2 adultos y 2 niños.,
44,silviavalente123@hotmail.com,Silvia Valente,silviavalente123@hotmail.com,,,,,,
45,,Amelia álvarez,sinrumbo.danza@gmail.com,664 74 87 10,,,,2,
46,,Sofía El Kovissi,sofia.elkovissi@gmail.com,600 037 642,,,,,
CSV;
    }

    /**
     * Parsea un texto CSV o TSV (copiado de Excel o del archivo de la feria) y devuelve filas normalizadas y deduplicadas.
     */
    public static function parseFairCsv(string $rawCsv, string $fairName = 'III Edición La Rioja Bridal Market'): array
    {
        $rawCsv = str_replace(["\r\n", "\r"], "\n", trim($rawCsv));
        // Limpiar espacios de no separación (nbsp) habituales en Excel
        $rawCsv = str_replace("\xC2\xA0", ' ', $rawCsv);

        $lines = explode("\n", $rawCsv);
        $parsedRows = [];
        $seenKeys = [];

        foreach ($lines as $lineIdx => $line) {
            $trimmedLine = trim($line);
            if ($trimmedLine === '' || preg_match('/^[,;\t]+$/', $trimmedLine)) {
                continue;
            }

            // Detectar delimitador (tabulador si se pega desde Excel, punto y coma o coma)
            $delimiter = ',';
            if (substr_count($line, "\t") >= 3) {
                $delimiter = "\t";
            } elseif (substr_count($line, ';') > substr_count($line, ',')) {
                $delimiter = ';';
            }

            $cols = str_getcsv($line, $delimiter);
            $cols = array_map(fn ($c) => trim((string) $c), $cols);

            // Ignorar cabeceras o títulos
            $joinedLower = mb_strtolower(implode(' ', $cols));
            if (
                str_contains($joinedLower, 'formulario previo') ||
                str_contains($joinedLower, 'nombre completo') ||
                str_contains($joinedLower, 'correo electrónico') ||
                str_contains($joinedLower, 'numero de telefono') ||
                str_contains($joinedLower, 'número de teléfono')
            ) {
                continue;
            }

            // Detectar si la columna 0 es un índice numérico (1, 2, 3...) como en el Excel de La Rioja Bridal Market
            $offset = 0;
            if (isset($cols[0]) && ($cols[0] === '' || preg_match('/^\d{1,4}$/', $cols[0])) && count($cols) >= 5) {
                $offset = 1;
            }

            $userEmailCol = $cols[$offset] ?? '';
            $fullName = $cols[$offset + 1] ?? '';
            $contactEmailCol = $cols[$offset + 2] ?? '';
            $phoneRaw = $cols[$offset + 3] ?? '';
            $marryingSoon = $cols[$offset + 4] ?? '';
            $yearCol = $cols[$offset + 5] ?? '';
            $roleCol = $cols[$offset + 6] ?? '';
            $attendeesCol = $cols[$offset + 7] ?? '';
            $commentsCol = $cols[$offset + 8] ?? '';

            // Si alguien pega un CSV simple de 3 columnas (Nombre, Email, Teléfono)
            if ($fullName === '' && filter_var($userEmailCol, FILTER_VALIDATE_EMAIL) === false && $userEmailCol !== '') {
                $fullName = $userEmailCol;
                $contactEmailCol = $cols[$offset + 1] ?? '';
                $phoneRaw = $cols[$offset + 2] ?? '';
                $commentsCol = $cols[$offset + 3] ?? '';
            }

            $fullName = preg_replace('/\s+/u', ' ', trim($fullName));
            if ($fullName !== '') {
                // Capitalizar nombres que venían en minúsculas ("Andrea aguirre" -> "Andrea Aguirre")
                $fullName = mb_convert_case($fullName, MB_CASE_TITLE, 'UTF-8');
                $fullName = str_replace([' De ', ' Del ', ' La ', ' Las ', ' Los ', ' Y '], [' de ', ' del ', ' la ', ' las ', ' los ', ' y '], $fullName);
            }

            $email = '';
            if (filter_var($contactEmailCol, FILTER_VALIDATE_EMAIL)) {
                $email = strtolower($contactEmailCol);
            } elseif (filter_var($userEmailCol, FILTER_VALIDATE_EMAIL)) {
                $email = strtolower($userEmailCol);
            }

            $phoneDigits = preg_replace('/\D/', '', $phoneRaw);
            if (strlen($phoneDigits) === 11 && str_starts_with($phoneDigits, '34')) {
                $phoneDigits = substr($phoneDigits, 2);
            }
            $cleanPhone = strlen($phoneDigits) >= 9 ? $phoneDigits : '';

            // Corregir el caso concreto de fila 18 si venía con 692168803
            if ($email === 'juditvvvv16@gmail.com' && $phoneRaw === '692168803') {
                $cleanPhone = '692168803';
            }

            if ($fullName === '' && $email === '' && $cleanPhone === '') {
                continue;
            }

            // Deduplicación por teléfono o email (ej. Nerea Velilla aparece en filas 24 y 33)
            $dedupeKey = $cleanPhone !== '' ? ('tel:' . $cleanPhone) : ('mail:' . $email);
            if ($dedupeKey !== 'mail:' && isset($seenKeys[$dedupeKey])) {
                continue;
            }
            $seenKeys[$dedupeKey] = true;

            // Extraer fecha exacta o año estimado
            $dateInfo = self::extractDateFromFairRow($yearCol, $commentsCol, $attendeesCol);

            // Comprobar si ya existe en la BD como cliente o evento
            $existingEvent = self::findExistingEventForLead($fullName, $email, $cleanPhone);

            $firstName = explode(' ', $fullName)[0] ?? $fullName;
            $mentionsMusic = (bool) preg_match('/m[uú]sica|dj|sonido|proveedores/iu', $commentsCol);

            $parsedRows[] = [
                'uid' => md5($dedupeKey . '_' . $fullName),
                'selected' => true,
                'name' => $fullName ?: 'Pareja Feria',
                'first_name' => $firstName,
                'email' => $email,
                'phone' => $cleanPhone,
                'has_phone' => $cleanPhone !== '',
                'has_email' => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
                'target_year' => $dateInfo['year_label'],
                'event_date' => $dateInfo['event_date'],
                'has_exact_date' => $dateInfo['has_exact_date'],
                'date_display' => $dateInfo['display'],
                'date_label' => $dateInfo['display'],
                'comments' => $commentsCol,
                'looks_for_music' => $mentionsMusic,
                'mentions_music' => $mentionsMusic,
                'fair_name' => $fairName,
                'existing_event_id' => $existingEvent ? $existingEvent->id : null,
                'existing_event_name' => $existingEvent ? $existingEvent->name : null,
                'existing_event_token' => $existingEvent ? $existingEvent->token : null,
                'existing_event_status' => $existingEvent ? $existingEvent->status : null,
                'wa_sent' => false,
                'email_sent' => false,
            ];
        }

        return $parsedRows;
    }

    /**
     * Extrae la fecha real (si la indicaron en comentarios) o el año previsto (2027, 2028 o Por definir).
     */
    public static function extractDateFromFairRow(string $yearCol, string $commentsCol, string $attendeesCol = ''): array
    {
        $targetYear = 2027;
        $yearLabel = '2027';

        if (preg_match('/\b(202[6-9])\b/', $yearCol, $m)) {
            $targetYear = (int) $m[1];
            $yearLabel = (string) $targetYear;
        } elseif (str_contains(mb_strtolower($yearCol), 'no sabemos') || $yearCol === '') {
            $yearLabel = 'Por definir (2027/28)';
        }

        $textToScan = trim($commentsCol);
        $months = [
            'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
            'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
            'septiembre' => 9, 'setiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12,
        ];

        // 1. Formato YYYY-MM-DD (ej. 2027-04-17, 2027-09-04)
        if (preg_match('/\b(202[6-9])[\-\/\.]([01]\d)[\-\/\.]([0-3]\d)\b/', $textToScan, $m)) {
            try {
                $dt = Carbon::createFromDate((int) $m[1], (int) $m[2], (int) $m[3]);
                return [
                    'has_exact_date' => true,
                    'event_date' => $dt->format('Y-m-d'),
                    'year_label' => (string) $dt->year,
                    'display' => '📅 ' . $dt->format('d/m/Y') . ' (Confirmada)',
                ];
            } catch (\Throwable $e) {}
        }

        // 2. Formato DD/MM/YYYY o DD.MM.YYYY o DD/MM/YY (ej. 25/09/2027, 18.09.2027, 03/07/27)
        if (preg_match('/\b([0-3]?\d)[\/\.]([01]?\d)[\/\.](202[6-9]|\d{2})\b/', $textToScan, $m)) {
            $day = (int) $m[1];
            $month = (int) $m[2];
            $yr = (int) $m[3];
            if ($yr < 100) {
                $yr += 2000;
            }
            if ($yr >= 2026 && $yr <= 2030 && $month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
                try {
                    $dt = Carbon::createFromDate($yr, $month, $day);
                    return [
                        'has_exact_date' => true,
                        'event_date' => $dt->format('Y-m-d'),
                        'year_label' => (string) $dt->year,
                        'display' => '📅 ' . $dt->format('d/m/Y') . ' (Confirmada)',
                    ];
                } catch (\Throwable $e) {}
            }
        }

        // 3. Formato "26 de junio de 2027" o "19 de junio" o "7 de agosto"
        if (preg_match('/\b([0-3]?\d)\s+de\s+(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|setiembre|octubre|noviembre|diciembre)(?:\s+(?:de|del)\s+(202[6-9]))?/iu', $textToScan, $m)) {
            $day = (int) $m[1];
            $month = $months[mb_strtolower($m[2])] ?? 6;
            $yr = !empty($m[3]) ? (int) $m[3] : $targetYear;
            try {
                $dt = Carbon::createFromDate($yr, $month, $day);
                return [
                    'has_exact_date' => true,
                    'event_date' => $dt->format('Y-m-d'),
                    'year_label' => (string) $dt->year,
                    'display' => '📅 ' . $dt->format('d/m/Y') . ' (Confirmada)',
                ];
            } catch (\Throwable $e) {}
        }

        // Sin fecha exacta: asignamos una fecha provisional de referencia (1 de junio del año objetivo) indicando que está por definir
        $fallbackDate = Carbon::createFromDate($targetYear, 6, 1)->format('Y-m-d');

        return [
            'has_exact_date' => false,
            'event_date' => $fallbackDate,
            'year_label' => $yearLabel,
            'display' => '⏳ Sin día fijo (' . $yearLabel . ')',
        ];
    }

    /**
     * Busca si el contacto ya tiene un evento creado para no duplicarlo.
     */
    public static function findExistingEventForLead(string $name, string $email, string $phone): ?Event
    {
        $client = null;
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $client = User::where('email', $email)->first();
        }
        if (!$client && $phone !== '') {
            $last9 = substr(preg_replace('/\D/', '', $phone), -9);
            if (strlen($last9) === 9) {
                $client = User::where('role', 'client')
                    ->where(function ($q) use ($last9, $phone) {
                        $q->where('phone', 'LIKE', "%{$last9}")
                          ->orWhere('phone', $phone);
                    })->first();
            }
        }

        if ($client) {
            return Event::where('client_id', $client->id)->latest('id')->first();
        }

        if ($phone !== '') {
            $last9 = substr(preg_replace('/\D/', '', $phone), -9);
            $byPartnerPhone = Event::where('partner_phone', 'LIKE', "%{$last9}")->latest('id')->first();
            if ($byPartnerPhone) {
                return $byPartnerPhone;
            }
        }

        return null;
    }

    /**
     * Crea (o recupera si ya existe) el Cliente y el Evento de un contacto de Feria de Novios.
     */
    public static function createOrGetFairEvent(
        array $row,
        string $brand = 'nunez_and_son',
        ?int $djId = null,
        string $initialStatus = 'no_response'
    ): array {
        $name = trim((string) ($row['name'] ?? 'Pareja Feria'));
        $email = strtolower(trim((string) ($row['email'] ?? '')));
        $phone = trim((string) ($row['phone'] ?? ''));
        $fairName = trim((string) ($row['fair_name'] ?? 'III Edición La Rioja Bridal Market'));
        $hasExactDate = !empty($row['has_exact_date']);
        $eventDate = !empty($row['event_date']) ? $row['event_date'] : Carbon::createFromDate(2027, 6, 1)->format('Y-m-d');
        $targetYear = trim((string) ($row['target_year'] ?? '2027'));
        $comments = trim((string) ($row['comments'] ?? ''));

        $existingEvent = self::findExistingEventForLead($name, $email, $phone);
        if ($existingEvent) {
            return [
                'event' => $existingEvent,
                'created_event' => false,
            ];
        }

        // 1. Buscar o crear usuario Cliente
        $client = null;
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $client = User::where('email', $email)->first();
        }
        if (!$client && $phone !== '') {
            $last9 = substr(preg_replace('/\D/', '', $phone), -9);
            $client = User::where('role', 'client')->where('phone', 'LIKE', "%{$last9}")->first();
        }

        if (!$client) {
            $finalEmail = ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL))
                ? $email
                : ('feria_' . ($phone !== '' ? $phone : Str::random(8)) . '@eventosmusicales.local');

            $client = User::firstOrCreate(
                ['email' => $finalEmail],
                [
                    'name' => $name,
                    'phone' => $phone !== '' ? $phone : null,
                    'role' => 'client',
                    'password' => Hash::make(Str::random(16)),
                ]
            );
        } else {
            $updates = [];
            if (empty($client->phone) && $phone !== '') {
                $updates['phone'] = $phone;
            }
            if ($email !== '' && str_ends_with((string) $client->email, '@eventosmusicales.local')) {
                $updates['email'] = $email;
            }
            if (!empty($updates)) {
                $client->update($updates);
            }
        }

        // 2. Construir notas claras indicando que viene de la Feria y si tiene o no fecha/finca
        $noteLines = [
            "🎪 CONTACTO FERIA DE NOVIOS: {$fairName}",
            "🎁 PROMO FERIA ACTIVA: Descuento en Packs + Álbum de Fotos QR GRATIS con cualquier pack",
            "👤 Nombre: {$name}",
        ];
        if ($phone !== '') {
            $noteLines[] = "📱 Teléfono: {$phone}";
        }
        if ($email !== '') {
            $noteLines[] = "📧 Email: {$email}";
        }
        if ($hasExactDate) {
            $noteLines[] = "📅 Fecha indicada por la pareja: " . Carbon::parse($eventDate)->format('d/m/Y');
        } else {
            $noteLines[] = "⏳ Fecha exacta pendiente de definir (Año previsto: {$targetYear})";
        }
        $noteLines[] = "📍 Lugar / Finca: Pendiente de concretar";
        if ($comments !== '') {
            $noteLines[] = "💬 Comentarios en formulario de feria: {$comments}";
        }

        $locationLabel = $hasExactDate
            ? 'Por definir (Feria Bridal Market)'
            : "Fecha y finca por definir ({$targetYear} · Feria Bridal Market)";

        $validStatus = in_array($initialStatus, ['no_response', 'draft', 'on_hold'], true) ? $initialStatus : 'no_response';

        $event = Event::create([
            'name' => "Boda - {$name}",
            'brand' => $brand ?: 'nunez_and_son',
            'event_type' => 'boda',
            'event_date' => $eventDate,
            'location' => $locationLabel,
            'client_id' => $client->id,
            'dj_id' => $djId ?: auth()->id(),
            'status' => $validStatus,
            'no_response_at' => $validStatus === 'no_response' ? now() : null,
            'token' => Str::random(32),
            'notes' => implode("\n", $noteLines),
        ]);

        // Registrar huella en ImapLeadFetcherService para evitar duplicados futuros
        try {
            $sigs = ImapLeadFetcherService::buildLeadSignatures($name, $phone, $eventDate);
            ImapLeadFetcherService::recordProcessedLeadSignatures($sigs);
        } catch (\Throwable $e) {}

        return [
            'event' => $event,
            'created_event' => true,
        ];
    }

    /**
     * Plantilla por defecto para el contacto post-Feria de Novios (con variables dinámicas)
     */
    public static function getDefaultFairTemplate(): string
    {
        return "¡Hola {nombre}! 👋 Soy {firma}, de *{empresa}*. ¡Qué ilusión haberos tenido en la *III Edición de La Rioja Bridal Market*! 💍✨\n\n"
            . "Por haber asistido a la feria tenéis activada nuestra *Promoción Especial Feria de Novios* (con descuento exclusivo en los packs y el *Álbum de Fotos QR para invitados + Carteles PDF totalmente GRATIS* elijáis el paquete que elijáis).\n\n"
            . "📄 *Aquí podéis ver nuestro Dossier de Tarifas 2027 con los precios especiales de feria:*\n"
            . "{enlace_dossier_tarifas}\n\n"
            . "🎛️ *Y aquí podéis configurar vuestro presupuesto a medida en 1 minuto (incluso si aún no tenéis día o finca cerrada):*\n"
            . "{enlace_presupuesto}\n\n"
            . "¿Ya tenéis fecha y finca decididas o todavía estáis viendo opciones? Cualquier duda me decís por aquí. ¡Un saludo! {firma}";
    }

    /**
     * Renderiza el mensaje personalizado para una pareja concreta de la feria.
     */
    public static function renderFairMessageForEvent(
        Event $event,
        $rowOrTemplate = null,
        ?string $rawTemplate = null,
        ?string $senderBrand = null
    ): string {
        $row = is_array($rowOrTemplate) ? $rowOrTemplate : [];
        if (is_string($rowOrTemplate) && $rawTemplate === null) {
            $rawTemplate = $rowOrTemplate;
        }
        $templateText = !empty(trim((string) $rawTemplate)) ? (string) $rawTemplate : self::getDefaultFairTemplate();

        $vars = WhatsAppTemplateService::getTemplateVariables($event, $senderBrand);
        if (!empty($row['first_name'])) {
            $vars['{nombre}'] = $row['first_name'];
        }
        $rendered = str_replace(array_keys($vars), array_values($vars), $templateText);
        return WhatsAppTemplateService::applySenderSignatureToMessage($rendered, $senderBrand, $event);
    }
}
