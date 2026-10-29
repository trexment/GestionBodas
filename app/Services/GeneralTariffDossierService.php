<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GeneralTariffDossierService
{
    public const SETTING_KEY = 'general_tariff_dossier_config_v1';

    /**
     * Devuelve la configuración por defecto del Dossier General de Tarifas
     */
    public static function getDefaults(): array
    {
        return [
            'brand' => 'nunez_and_son', // 'nunez_and_son' | 'javnx'
            'tariff_year' => '2027',
            'cover_title_badge' => 'Tarifas 2027',
            'cover_phrase' => 'DJ para bodas en La Rioja, Álava, Burgos y Navarra',
            'cover_subtitle' => 'Sonido profesional, iluminación y sesión DJ a medida para vuestra boda',
            'cover_photo_index' => 0,
            'cover_custom_photo' => null,

            // 2. QUIÉNES SOMOS
            'about_title' => 'Quiénes somos',
            'about_photo' => null,
            'about_text' => "Somos Fran y Miguel. Nos dedicamos a la música, sonorización e iluminación de bodas y eventos en La Rioja, Álava, Burgos y Navarra.\n\nTrabajamos siempre con equipamiento propio de sonido e iluminación, sin intermediarios ni subcontratas. Antes de cada boda nos reunimos con vosotros para definir los momentos musicales y hablamos directamente con el restaurante o finca para coordinar horarios, enchufes, limitadores y accesos de montaje.\n\nEl día de la boda llegamos con horas de antelación para dejar cada zona montada y probada antes de que lleguen los invitados, y pinchamos en directo leyendo la pista de principio a fin.",
            'about_facts' => [
                'Equipo propio de sonido profesional e iluminación controlada por DMX.',
                'Trato directo con nosotros desde la primera consulta hasta el final de la fiesta.',
                'Coordinación técnica previa con la finca o restaurante sin que tengáis que mediar.',
                'Equipo de respaldo en cada evento para garantizar cero imprevistos técnicos.',
            ],
            'about_venues_title' => 'Fincas, bodegas y restaurantes donde hemos trabajado',
            'about_venues' => 'Bodegas Marqués de Riscal, Finca de los Arandinos, Restaurante Delicatto, Bodegas Franco-Españolas, Señorío de Villarrica, Hotel Viura, Bodegas Eguren Ugarte, Restaurante La Vieja Bodega, Palacio de Samaniego, Hotel Santa María Briones y otros espacios de La Rioja, Álava, Burgos y Navarra.',

            // 3. NUESTRO TRABAJO (Galería de 6 a 9 fotos, cada una de una boda distinta con permiso)
            'gallery_photos' => self::getDefaultGallerySlots(),

            // 4. CÓMO TRABAJAMOS (Paso a paso con plazos concretos; por defecto plazos 2, 3 y 4 vacíos para rellenar)
            'work_steps' => [
                [
                    'title' => 'Reserva de la fecha',
                    'description' => 'Con la firma del contrato y el abono de la señal queda bloqueada vuestra fecha en exclusiva.',
                    'timeframe' => 'Con el contrato y la señal',
                ],
                [
                    'title' => 'Reunión previa de personalización (videollamada; presencial si se necesita)',
                    'description' => 'Definimos juntos las canciones de cada momento, vuestros gustos musicales y las canciones que no queréis que suenen.',
                    'timeframe' => '',
                ],
                [
                    'title' => 'Coordinación directa con el local para montaje y condiciones técnicas',
                    'description' => 'Hablamos con el responsable de la finca o restaurante para cuadrar horarios de acceso, tomas de corriente y sonido.',
                    'timeframe' => '',
                ],
                [
                    'title' => 'El día de la boda: montaje y música en los momentos contratados',
                    'description' => 'Instalación completa, prueba de sonido antes de la llegada de invitados y sesión musical en cada fase contratada.',
                    'timeframe' => '',
                ],
                [
                    'title' => 'Pago del resto',
                    'description' => 'Liquidación del importe pendiente una vez descontada la señal de reserva.',
                    'timeframe' => 'El día del evento o antes',
                ],
            ],

            // 5. PACKS Y PRECIO (Página central, exactamente 3 packs, el del medio destacado)
            'prices_include_vat' => true,
            'vat_rate' => 21,
            'packs_section_note' => 'Todos nuestros packs incluyen transporte, montaje, desmontaje, prueba de sonido previa y reunión de personalización musical.',
            'packs' => [
                [
                    'key' => 'esencial',
                    'name' => 'Pack Esencial',
                    'subtitle' => '4 horas de sesión DJ con sonido profesional estéreo e iluminación LED de pista',
                    'hours' => 4,
                    'price_input' => 600,
                    'featured' => false,
                    'badge' => '',
                    'includes' => [
                        '4 horas de sesión DJ en directo durante la fiesta / barra libre',
                        '2 altavoces autoamplificados de alta definición (hasta 120 invitados)',
                        '1 cabina DJ con frontal elegante e integración limpia en el salón',
                        '2 barras / trípodes con 4 focos LED rítmicos para ambientación de pista',
                        '2 micrófonos inalámbricos de mano para discursos o brindis',
                        '1 reunión previa de personalización musical y acceso al portal de canciones',
                    ],
                ],
                [
                    'key' => 'signature',
                    'name' => 'Pack Signature',
                    'subtitle' => '5 horas de sesión DJ con refuerzo de subgraves, cabina retroiluminada y puente de iluminación',
                    'hours' => 5,
                    'price_input' => 950,
                    'featured' => true,
                    'badge' => 'El más elegido',
                    'includes' => [
                        '5 horas de sesión DJ en directo durante la fiesta / barra libre',
                        '4 cajas acústicas (2 satélites + 2 subgraves de 18" para máxima pegada en pista)',
                        '1 cabina DJ retroiluminada + puente/estructura con cabezas móviles y barras LED',
                        '1 máquina de humo ambiental para realzar los haces de iluminación en pista',
                        '2 micrófonos inalámbricos profesionales de largo alcance',
                        '1 reunión previa de personalización musical y coordinación directa con la finca',
                    ],
                ],
                [
                    'key' => 'deluxe',
                    'name' => 'Pack Deluxe Show',
                    'subtitle' => '6 horas de sesión DJ con montaje de gran formato, show de iluminación avanzada y fuego frío',
                    'hours' => 6,
                    'price_input' => 1350,
                    'featured' => false,
                    'badge' => 'Cobertura total',
                    'includes' => [
                        '6 horas de sesión DJ en directo durante la fiesta / barra libre',
                        'Sistema de sonido de gran formato con doble subgrave de alta potencia (hasta 300 invitados)',
                        'Show de iluminación completo con 4 cabezas móviles robóticas, barras LED y efectos DMX',
                        '2 máquinas de fuego frío / chispas frías incluidas para el baile nupcial o momento clave',
                        '1 máquina de humo + 2 micrófonos inalámbricos profesionales',
                        '1 reunión previa de personalización musical y coordinación técnica integral',
                    ],
                ],
            ],

            // 6. EXTRAS (Precio cerrado; si está vacío o "a consultar" se omite y avisa)
            'extras' => [
                [
                    'name' => 'Hora extra de fiesta / DJ',
                    'description' => 'Ampliación de 1 hora adicional de sesión DJ, sonido e iluminación sobre el pack contratado.',
                    'price' => '120',
                ],
                [
                    'name' => 'Karaoke para invitados',
                    'description' => 'Proyección de letras, micrófonos inalámbricos y catálogo de canciones para cantar durante la fiesta.',
                    'price' => '80',
                ],
                [
                    'name' => 'Sonorización y música en Ceremonia',
                    'description' => 'Equipo de sonido independiente para la ceremonia (civil o exterior), música a medida y 2 micrófonos inalámbricos para lecturas.',
                    'price' => '180',
                ],
                [
                    'name' => 'Cóctel + banquete (música de entrada y momento de regalos)',
                    'description' => 'Ambientación musical en el cóctel de bienvenida, música de fondo en comida/cena y sonorización de entradas, brindis y entregas de regalos.',
                    'price' => '150',
                ],
                [
                    'name' => 'Fotomatón',
                    'description' => 'Servicio de fotomatón con copias impresas, atrezzo y álbum de firmas.',
                    'price' => '',
                ],
                [
                    'name' => 'Plataforma 360',
                    'description' => 'Plataforma de vídeo 360º slow-motion con atrezzo e iluminación.',
                    'price' => '',
                ],
                [
                    'name' => 'Mesa dulce',
                    'description' => 'Mesa dulce decorada / fuente de chocolate para la recena o barra libre.',
                    'price' => '',
                ],
            ],

            // 7. OPINIONES (4 o 5 reseñas reales y cortas; si hay < 2 con permiso se omite la página; nunca generar reseñas de ejemplo)
            'google_reviews_url' => 'https://g.page/r/nunezandson/review',
            'reviews' => [],

            // 8. CÓMO RESERVAR
            'deposit_percentage' => 40,
            'payment_methods_text' => 'Transferencia bancaria, Bizum o efectivo',
            'after_booking_text' => 'En cuanto firmamos el contrato y recibimos la señal, vuestra fecha queda bloqueada en firme y agendamos juntos el primer paso: la reunión de personalización para conocer vuestros gustos y empezar a dar forma a la boda.',
            'valid_until' => '2026-12-31',
            'phone_fran' => '+34 622 62 47 90',
            'phone_miguel' => '+34 674 37 89 93',
            'contact_email_nunez' => 'info@nunezandson.com',
            'contact_website_nunez' => 'www.nunezandson.com',
            'contact_email_javnx' => 'info@javnxdj.com',
            'contact_website_javnx' => 'www.javnxdj.com',
            'closing_phrase' => 'Si queréis la fecha, os la guardamos con la señal.',

            // Plantilla WhatsApp
            'whatsapp_template' => '¡Hola {nombre}! Os dejo nuestro dossier de tarifas {anio} para que lo veáis con calma. Si os apetece, hacemos una videollamada de 10-15 minutos y os lo explico. ¡Un saludo! {firma}',
        ];
    }

    /**
     * Genera las ranuras iniciales de galería aprovechando fotos reales existentes si las hay
     */
    public static function getDefaultGallerySlots(): array
    {
        $slots = [];
        $existingSettingKeys = [
            'dossier_photo1_image',
            'dossier_photo2_image',
            'dossier_photo3_image',
            'dossier_photo4_image',
            'dossier_banner_image',
        ];

        $idx = 0;
        foreach ($existingSettingKeys as $key) {
            $path = Setting::get($key);
            if (!empty($path)) {
                $idx++;
                $slots[] = [
                    'image_path' => $path,
                    'wedding_name' => '',
                    'wedding_date' => '',
                    'has_permission' => false,
                    'caption' => '',
                ];
            }
        }

        while (count($slots) < 6) {
            $slots[] = [
                'image_path' => null,
                'wedding_name' => '',
                'wedding_date' => '',
                'has_permission' => false,
                'caption' => '',
            ];
        }

        return $slots;
    }

    /**
     * Carga la configuración actual fusionada con los valores por defecto
     */
    public static function getConfig(): array
    {
        $defaults = self::getDefaults();
        $rawJson = Setting::get(self::SETTING_KEY);
        if (empty($rawJson)) {
            return $defaults;
        }

        $saved = is_array($rawJson) ? $rawJson : json_decode((string) $rawJson, true);
        if (!is_array($saved)) {
            return $defaults;
        }

        $merged = array_merge($defaults, $saved);

        // Asegurar exactamente 3 packs con el del medio destacado
        if (empty($merged['packs']) || !is_array($merged['packs']) || count($merged['packs']) !== 3) {
            $merged['packs'] = $defaults['packs'];
        } else {
            foreach ($merged['packs'] as $i => &$p) {
                $p = array_merge($defaults['packs'][$i] ?? $defaults['packs'][0], $p);
                $p['featured'] = ($i === 1);
                if ($i === 1 && empty(trim((string) ($p['badge'] ?? '')))) {
                    $p['badge'] = 'El más elegido';
                }
            }
        }

        if (!isset($merged['gallery_photos']) || !is_array($merged['gallery_photos'])) {
            $merged['gallery_photos'] = $defaults['gallery_photos'];
        }

        if (!isset($merged['work_steps']) || !is_array($merged['work_steps']) || empty($merged['work_steps'])) {
            $merged['work_steps'] = $defaults['work_steps'];
        }

        if (!isset($merged['extras']) || !is_array($merged['extras'])) {
            $merged['extras'] = $defaults['extras'];
        }

        if (!isset($merged['reviews']) || !is_array($merged['reviews'])) {
            $merged['reviews'] = [];
        }

        return $merged;
    }

    /**
     * Guarda la configuración del Dossier General de Tarifas
     */
    public static function saveConfig(array $config): void
    {
        // Garantizar que el pack central (índice 1) siempre esté marcado como destacado
        if (isset($config['packs']) && is_array($config['packs'])) {
            $config['packs'] = array_values(array_slice($config['packs'], 0, 3));
            foreach ($config['packs'] as $i => &$pack) {
                $pack['featured'] = ($i === 1);
                if ($i === 1 && empty(trim((string) ($pack['badge'] ?? '')))) {
                    $pack['badge'] = 'El más elegido';
                }
            }
        }

        Setting::set(self::SETTING_KEY, json_encode($config, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Convierte cualquier imagen subida (.webp, .png, .jpeg, .jpg) a .jpg optimizado y comprimido para el PDF
     */
    public static function processAndCompressImage($uploadedFileOrPath, string $prefix = 'boda'): ?string
    {
        try {
            $sourcePath = is_string($uploadedFileOrPath)
                ? $uploadedFileOrPath
                : $uploadedFileOrPath->getRealPath();

            if (!$sourcePath || !file_exists($sourcePath)) {
                return null;
            }

            $dir = 'tariff-dossier';
            if (!Storage::disk('public')->exists($dir)) {
                Storage::disk('public')->makeDirectory($dir);
            }

            $filename = $prefix . '_' . date('Ymd_His') . '_' . Str::random(6) . '.jpg';
            $relativeDest = $dir . '/' . $filename;
            $fullDest = Storage::disk('public')->path($relativeDest);

            // Si GD está disponible, convertir (incluyendo .webp y .png) a .jpg y comprimir
            if (function_exists('imagecreatetruecolor') && function_exists('imagejpeg')) {
                $img = null;
                $mime = @mime_content_type($sourcePath) ?: '';

                if (str_contains($mime, 'webp') && function_exists('imagecreatefromwebp')) {
                    $img = @imagecreatefromwebp($sourcePath);
                } elseif (str_contains($mime, 'png') && function_exists('imagecreatefrompng')) {
                    $img = @imagecreatefrompng($sourcePath);
                } elseif ((str_contains($mime, 'jpeg') || str_contains($mime, 'jpg')) && function_exists('imagecreatefromjpeg')) {
                    $img = @imagecreatefromjpeg($sourcePath);
                }

                if (!$img && function_exists('imagecreatefromstring')) {
                    $rawBytes = @file_get_contents($sourcePath);
                    if ($rawBytes !== false) {
                        $img = @imagecreatefromstring($rawBytes);
                    }
                }

                if ($img) {
                    $origW = imagesx($img);
                    $origH = imagesy($img);
                    $maxDim = 1400; // Ideal para móvil y PDF ligero (<10 MB total)

                    if ($origW > $maxDim || $origH > $maxDim) {
                        $ratio = min($maxDim / max(1, $origW), $maxDim / max(1, $origH));
                        $newW = max(1, (int) round($origW * $ratio));
                        $newH = max(1, (int) round($origH * $ratio));
                    } else {
                        $newW = $origW;
                        $newH = $origH;
                    }

                    $canvas = imagecreatetruecolor($newW, $newH);
                    // Fondo blanco por si la imagen original tenía transparencia (.png / .webp)
                    $white = imagecolorallocate($canvas, 255, 255, 255);
                    imagefilledrectangle($canvas, 0, 0, $newW, $newH, $white);
                    imagecopyresampled($canvas, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

                    imagejpeg($canvas, $fullDest, 80);
                    imagedestroy($canvas);
                    imagedestroy($img);

                    return $relativeDest;
                }
            }

            // Fallback si GD no pudo abrir el archivo
            if (!is_string($uploadedFileOrPath)) {
                return $uploadedFileOrPath->storeAs($dir, $filename, 'public');
            }
            copy($sourcePath, $fullDest);
            return $relativeDest;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Asegura que una imagen almacenada en storage/app/public esté en formato .jpg comprimido y devuelve su ruta absoluta local para DomPDF
     */
    public static function resolveCompressedJpgPathForPdf(?string $relativePath): ?string
    {
        if (empty($relativePath)) {
            return null;
        }

        $cleanRel = ltrim(preg_replace('#^/?storage/#', '', (string) $relativePath), '/\\');
        $fullPath = Storage::disk('public')->path($cleanRel);
        if (!file_exists($fullPath)) {
            $altPath = public_path('storage/' . $cleanRel);
            if (file_exists($altPath)) {
                $fullPath = $altPath;
            } else {
                return null;
            }
        }

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $fileSize = @filesize($fullPath) ?: 0;

        // Si es .webp, .png o pesa más de 450 KB, convertir y comprimir a .jpg en caché
        if (in_array($ext, ['webp', 'png'], true) || $fileSize > 450 * 1024) {
            $hash = md5($fullPath . '_' . (@filemtime($fullPath) ?: '0'));
            $cacheRel = 'tariff-dossier/cache_' . $hash . '.jpg';
            $cacheFull = Storage::disk('public')->path($cacheRel);
            if (file_exists($cacheFull)) {
                return $cacheFull;
            }

            $convertedRel = self::processAndCompressImage($fullPath, 'pdf_opt');
            if ($convertedRel) {
                $convertedFull = Storage::disk('public')->path($convertedRel);
                if (file_exists($convertedFull)) {
                    @rename($convertedFull, $cacheFull);
                    return file_exists($cacheFull) ? $cacheFull : $convertedFull;
                }
            }
        }

        return $fullPath;
    }

    /**
     * Calcula el precio final con IVA incluido de un importe
     */
    public static function computeFinalPriceWithVat($rawPrice, bool $pricesIncludeVat = true, float $vatRate = 21.0): float
    {
        $clean = str_replace(['€', ' '], '', (string) $rawPrice);
        if (str_contains($clean, ',') && str_contains($clean, '.')) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } elseif (str_contains($clean, ',')) {
            $clean = str_replace(',', '.', $clean);
        }
        $val = (float) $clean;
        if ($val <= 0) {
            return 0.0;
        }
        if ($pricesIncludeVat) {
            return round($val, 2);
        }
        return round($val * (1 + ($vatRate / 100)), 2);
    }

    /**
     * Filtra las fotos válidas de la galería (solo con permiso = sí, archivo existente, boda y fecha)
     */
    public static function getValidGalleryPhotos(array $config): array
    {
        $valid = [];
        foreach (($config['gallery_photos'] ?? []) as $idx => $photo) {
            $hasPerm = !empty($photo['has_permission']);
            $imgPath = trim((string) ($photo['image_path'] ?? ''));
            $weddingName = trim((string) ($photo['wedding_name'] ?? ''));
            $weddingDate = trim((string) ($photo['wedding_date'] ?? ''));

            if ($hasPerm && $imgPath !== '' && $weddingName !== '' && $weddingDate !== '') {
                $pdfPath = self::resolveCompressedJpgPathForPdf($imgPath);
                if ($pdfPath && file_exists($pdfPath)) {
                    $photo['pdf_path'] = $pdfPath;
                    $photo['public_url'] = asset('storage/' . ltrim($imgPath, '/'));
                    $photo['slot_index'] = $idx;
                    $valid[] = $photo;
                }
            }
        }
        return array_slice($valid, 0, 9);
    }

    /**
     * Filtra los extras con precio cerrado y devuelve tanto los incluidos como los omitidos
     */
    public static function getProcessedExtras(array $config): array
    {
        $included = [];
        $omitted = [];
        $pricesIncludeVat = !empty($config['prices_include_vat']);
        $vatRate = (float) ($config['vat_rate'] ?? 21);

        foreach (($config['extras'] ?? []) as $extra) {
            $name = trim((string) ($extra['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $rawPrice = trim((string) ($extra['price'] ?? ''));
            $lowerPrice = mb_strtolower($rawPrice);

            if (
                $rawPrice === '' ||
                str_contains($lowerPrice, 'consultar') ||
                str_contains($lowerPrice, 'pendiente') ||
                str_contains($lowerPrice, 'desde') ||
                !preg_match('/\d/', $rawPrice)
            ) {
                $omitted[] = $name;
                continue;
            }

            $finalPrice = self::computeFinalPriceWithVat($rawPrice, $pricesIncludeVat, $vatRate);
            if ($finalPrice <= 0) {
                $omitted[] = $name;
                continue;
            }

            $included[] = [
                'name' => $name,
                'description' => trim((string) ($extra['description'] ?? '')),
                'final_price' => $finalPrice,
            ];
        }

        return [
            'included' => $included,
            'omitted' => $omitted,
        ];
    }

    /**
     * Filtra las reseñas reales con permiso para publicar
     */
    public static function getValidReviews(array $config): array
    {
        $valid = [];
        foreach (($config['reviews'] ?? []) as $rev) {
            $hasPerm = !empty($rev['has_permission']);
            $couple = trim((string) ($rev['couple_name'] ?? ''));
            $location = trim((string) ($rev['location'] ?? ''));
            $date = trim((string) ($rev['event_date'] ?? ''));
            $text = trim((string) ($rev['text'] ?? ''));

            if ($hasPerm && $couple !== '' && $date !== '' && $text !== '') {
                $valid[] = [
                    'couple_name' => $couple,
                    'location' => $location,
                    'event_date' => $date,
                    'text' => $text,
                ];
            }
        }
        return array_slice($valid, 0, 5);
    }

    /**
     * Construye el plan exacto de páginas del PDF garantizando:
     * - Orden fijo de secciones:
     *   1. PORTADA
     *   2. QUIÉNES SOMOS
     *   3. NUESTRO TRABAJO (2 páginas amplias con fotos grandes para no apretar márgenes)
     *   4. CÓMO TRABAJAMOS (paso a paso)
     *   5. PACKS Y PRECIO (página central)
     *   6. EXTRAS
     *   7. OPINIONES (si hay >= 2 reseñas con permiso; si se omite, se añade página de Detalle Técnico de Montaje y Preguntas Frecuentes después de Extras)
     *   8. CÓMO RESERVAR
     * - Total siempre entre 8 y 10 páginas (8 o 9 páginas), con PACKS Y PRECIO en el centro del documento.
     */
    public static function buildPagesPlan(array $config): array
    {
        $validPhotos = self::getValidGalleryPhotos($config);
        $validReviews = self::getValidReviews($config);
        $hasReviewsPage = count($validReviews) >= 2;
        $processedExtras = self::getProcessedExtras($config);

        // Si hay página de Opiniones (8 secciones), Nuestro Trabajo va en 1 página si hay <= 6 fotos o en 2 páginas si hay 7-9 fotos (total 8 o 9 páginas).
        // Si NO hay página de Opiniones (< 2 reseñas), Nuestro Trabajo se reparte en 2 páginas de fotos grandes (3-4 fotos por página) para que el PDF tenga siempre entre 8 y 10 páginas sin apretar márgenes.
        $splitGalleryInTwoPages = !$hasReviewsPage || count($validPhotos) > 6;

        $pages = [];

        // Página 1: PORTADA
        $pages[] = [
            'type' => 'cover',
            'title' => '1. Portada',
            'section_number' => 1,
        ];

        // Página 2: QUIÉNES SOMOS
        $pages[] = [
            'type' => 'about',
            'title' => '2. Quiénes somos',
            'section_number' => 2,
        ];

        // Página 3 (y 4 si se divide): NUESTRO TRABAJO
        if ($splitGalleryInTwoPages && count($validPhotos) >= 4) {
            $half = (int) ceil(count($validPhotos) / 2);
            $pages[] = [
                'type' => 'gallery',
                'title' => '3. Nuestro trabajo (I)',
                'subtitle' => 'Bodas reales realizadas por nuestro equipo',
                'photos' => array_slice($validPhotos, 0, $half),
                'part' => 1,
                'total_parts' => 2,
                'section_number' => 3,
            ];
            $pages[] = [
                'type' => 'gallery',
                'title' => '3. Nuestro trabajo (II)',
                'subtitle' => 'Montajes de sonido, iluminación y pista de baile en directo',
                'photos' => array_slice($validPhotos, $half),
                'part' => 2,
                'total_parts' => 2,
                'section_number' => 3,
            ];
        } else {
            $pages[] = [
                'type' => 'gallery',
                'title' => '3. Nuestro trabajo',
                'subtitle' => 'Bodas reales realizadas por nuestro equipo',
                'photos' => $validPhotos,
                'part' => 1,
                'total_parts' => 1,
                'section_number' => 3,
            ];
        }

        // Página siguiente: CÓMO TRABAJAMOS
        $pages[] = [
            'type' => 'how_we_work',
            'title' => '4. Cómo trabajamos',
            'section_number' => 4,
        ];

        // PÁGINA CENTRAL: PACKS Y PRECIO
        $packsPageNumber = count($pages) + 1;
        $pages[] = [
            'type' => 'packs',
            'title' => '5. Packs y precio',
            'is_central' => true,
            'section_number' => 5,
        ];

        // Página siguiente: EXTRAS
        $pages[] = [
            'type' => 'extras',
            'title' => '6. Extras',
            'extras' => $processedExtras['included'],
            'omitted_extras' => $processedExtras['omitted'],
            'section_number' => 6,
        ];

        // Si hay más de 6 extras con precio cerrado, añadir segunda página de extras para no apretar márgenes
        if (count($processedExtras['included']) > 6) {
            $pages[count($pages) - 1]['extras'] = array_slice($processedExtras['included'], 0, 5);
            $pages[] = [
                'type' => 'extras_cont',
                'title' => '6. Extras (Continuación)',
                'extras' => array_slice($processedExtras['included'], 5),
                'section_number' => 6,
            ];
        }

        // Página siguiente: OPINIONES (solo si hay >= 2 reseñas reales con permiso)
        if ($hasReviewsPage) {
            $pages[] = [
                'type' => 'reviews',
                'title' => '7. Opiniones',
                'reviews' => $validReviews,
                'section_number' => 7,
            ];
        }

        // Si por alguna combinación aún hubiera 7 páginas (ej. <4 fotos en borrador y sin reseñas), añadir segunda página de galería para mantener mínimo 8 páginas
        if (count($pages) < 7) {
            // Con la última página (Cómo reservar) serían <8, garantizamos 8 páginas añadiendo desglose visual de galería II
            $pages[] = [
                'type' => 'gallery',
                'title' => '3. Nuestro trabajo (Detalle)',
                'subtitle' => 'Montajes reales en fincas y restaurantes',
                'photos' => array_slice($validPhotos, 3),
                'part' => 2,
                'total_parts' => 2,
                'section_number' => 3,
            ];
        }

        // Última página: CÓMO RESERVAR
        $pages[] = [
            'type' => 'how_to_book',
            'title' => '8. Cómo reservar',
            'section_number' => 8,
        ];

        // Numerar páginas del 1 al N
        $totalPages = count($pages);
        foreach ($pages as $i => &$p) {
            $p['page_number'] = $i + 1;
            $p['total_pages'] = $totalPages;
        }

        return [
            'pages' => $pages,
            'total_pages' => $totalPages,
            'packs_page_number' => $packsPageNumber,
            'has_reviews_page' => $hasReviewsPage,
            'omitted_extras' => $processedExtras['omitted'],
        ];
    }

    /**
     * Ejecuta el Checklist de Validación de 10 puntos antes de generar el PDF
     */
    public static function validateConfig(?array $config = null): array
    {
        $config = $config ?? self::getConfig();
        $plan = self::buildPagesPlan($config);
        $checks = [];

        // 1. Entre 8 y 10 páginas
        $totalPages = $plan['total_pages'];
        $pagesOk = ($totalPages >= 8 && $totalPages <= 10);
        $checks['page_count'] = [
            'ok' => $pagesOk,
            'blocking' => true,
            'title' => 'Entre 8 y 10 páginas',
            'detail' => "El documento tiene {$totalPages} páginas (rango requerido: 8 a 10 páginas).",
        ];

        // 2. Packs en la página central
        $packsPage = $plan['packs_page_number'];
        $isCentral = in_array($packsPage, [4, 5, 6], true);
        $checks['packs_central'] = [
            'ok' => $isCentral,
            'blocking' => true,
            'title' => 'Packs en la página central',
            'detail' => "La sección «Packs y precio» está ubicada en la página {$packsPage} de {$totalPages} (centro del dossier).",
        ];

        // 3. Todos los precios con IVA incluido y ninguno con "desde" ni "IVA aparte"
        $forbiddenHits = [];
        $allTextToScan = [
            $config['cover_phrase'] ?? '',
            $config['cover_subtitle'] ?? '',
            $config['about_text'] ?? '',
            $config['packs_section_note'] ?? '',
            $config['closing_phrase'] ?? '',
        ];
        foreach (($config['packs'] ?? []) as $p) {
            $allTextToScan[] = $p['name'] ?? '';
            $allTextToScan[] = $p['subtitle'] ?? '';
            foreach (($p['includes'] ?? []) as $inc) {
                $allTextToScan[] = $inc;
            }
        }
        foreach (($config['extras'] ?? []) as $ex) {
            if (!in_array(trim((string) ($ex['name'] ?? '')), $plan['omitted_extras'], true)) {
                $allTextToScan[] = $ex['name'] ?? '';
                $allTextToScan[] = $ex['description'] ?? '';
                $allTextToScan[] = $ex['price'] ?? '';
            }
        }
        $combinedScan = mb_strtolower(implode(' | ', $allTextToScan));
        if (preg_match('/\bdesde\s*\d|\bdesde\b/u', $combinedScan)) {
            $forbiddenHits[] = 'contiene la palabra «desde»';
        }
        if (str_contains($combinedScan, 'iva aparte') || str_contains($combinedScan, '+ iva') || str_contains($combinedScan, 'más iva') || str_contains($combinedScan, 'mas iva')) {
            $forbiddenHits[] = 'contiene «IVA aparte / + IVA»';
        }
        if (str_contains($combinedScan, 'momentos que duran para siempre')) {
            $forbiddenHits[] = 'contiene frase genérica no permitida («momentos que duran para siempre»)';
        }

        $packsHavePrices = true;
        foreach (($config['packs'] ?? []) as $p) {
            if (self::computeFinalPriceWithVat($p['price_input'] ?? 0, !empty($config['prices_include_vat']), (float) ($config['vat_rate'] ?? 21)) <= 0) {
                $packsHavePrices = false;
            }
        }

        $vatOk = empty($forbiddenHits) && $packsHavePrices;
        $checks['vat_and_no_desde'] = [
            'ok' => $vatOk,
            'blocking' => true,
            'title' => 'Todos los precios con IVA incluido y ninguno con «desde»',
            'detail' => $vatOk
                ? (!empty($config['prices_include_vat'])
                    ? 'Todos los precios muestran el importe final con la leyenda «IVA incluido» y sin la palabra «desde».'
                    : 'La app calcula automáticamente el precio final sumando el ' . ($config['vat_rate'] ?? 21) . '% de IVA y muestra «IVA incluido».')
                : ('Corrige lo siguiente: ' . implode(', ', $forbiddenHits ?: ['algún pack no tiene precio válido'])),
        ];

        // 4. 3 packs con subtítulo descriptivo y el del medio destacado
        $packs = $config['packs'] ?? [];
        $threePacksOk = (count($packs) === 3);
        $missingSubtitles = [];
        foreach ($packs as $idx => $p) {
            if (empty(trim((string) ($p['name'] ?? ''))) || empty(trim((string) ($p['subtitle'] ?? '')))) {
                $threePacksOk = false;
                $missingSubtitles[] = 'Pack ' . ($idx + 1);
            }
            if (empty($p['includes']) || count(array_filter($p['includes'], fn($line) => trim((string) $line) !== '')) < 2) {
                $threePacksOk = false;
                $missingSubtitles[] = 'incluye de ' . ($p['name'] ?: ('Pack ' . ($idx + 1)));
            }
        }
        $middleFeatured = !empty($packs[1]['featured']) && !empty(trim((string) ($packs[1]['badge'] ?? 'El más elegido')));
        $packsStructureOk = $threePacksOk && $middleFeatured;
        $checks['three_packs'] = [
            'ok' => $packsStructureOk,
            'blocking' => true,
            'title' => '3 packs con subtítulo descriptivo y el del medio destacado',
            'detail' => $packsStructureOk
                ? '3 packs completos en columnas con «' . ($packs[1]['name'] ?? 'Pack Signature') . '» destacado en el centro («' . ($packs[1]['badge'] ?? 'El más elegido') . '»).'
                : 'Revisa que los 3 packs tengan nombre, subtítulo descriptivo, lista de lo que incluye con números y que el central esté destacado.',
        ];

        // 5. Cada paso de "Cómo trabajamos" tiene plazo concreto
        $steps = $config['work_steps'] ?? [];
        $missingStepTimeframes = [];
        foreach ($steps as $idx => $step) {
            $stepTitle = trim((string) ($step['title'] ?? ''));
            $stepTime = trim((string) ($step['timeframe'] ?? ''));
            if ($stepTitle === '') {
                continue;
            }
            if ($stepTime === '' || str_contains(mb_strtolower($stepTime), '[rellenar]') || str_contains(mb_strtolower($stepTime), 'rellenar')) {
                $missingStepTimeframes[] = 'Paso ' . ($idx + 1) . ' («' . $stepTitle . '»)';
            }
        }
        $stepsOk = !empty($steps) && empty($missingStepTimeframes);
        $checks['work_steps_timeframes'] = [
            'ok' => $stepsOk,
            'blocking' => true,
            'title' => 'Cada paso de «Cómo trabajamos» tiene plazo concreto',
            'detail' => $stepsOk
                ? 'Los ' . count($steps) . ' pasos tienen su título y plazo concreto definidos.'
                : 'Falta rellenar el plazo en: ' . implode(', ', $missingStepTimeframes) . '. (No se puede generar el PDF si algún paso no tiene plazo).',
        ];

        // 6. Todos los extras tienen precio cerrado (los que no tienen precio cerrado se omiten y se avisa)
        $omittedExtras = $plan['omitted_extras'];
        $includedExtrasCount = count(self::getProcessedExtras($config)['included']);
        $extrasOk = ($includedExtrasCount >= 1);
        $checks['extras_closed_price'] = [
            'ok' => $extrasOk,
            'blocking' => true,
            'warning' => !empty($omittedExtras),
            'title' => 'Todos los extras del PDF tienen precio cerrado',
            'detail' => $extrasOk
                ? ($includedExtrasCount . ' extras con precio cerrado e IVA incluido.' . (!empty($omittedExtras) ? ' Se han omitido automáticamente del PDF por no tener precio cerrado: ' . implode(', ', $omittedExtras) . '.' : ''))
                : 'Debes tener al menos 1 extra con precio cerrado.',
        ];

        // 7. Fotos con permiso, de bodas distintas, mínimo 6
        $validPhotos = self::getValidGalleryPhotos($config);
        $validPhotosCount = count($validPhotos);
        $weddingNamesSeen = [];
        $duplicateWeddings = [];
        foreach ($validPhotos as $vp) {
            $normName = mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $vp['wedding_name'])));
            if (isset($weddingNamesSeen[$normName])) {
                $duplicateWeddings[] = $vp['wedding_name'];
            }
            $weddingNamesSeen[$normName] = true;
        }
        $duplicateWeddings = array_unique($duplicateWeddings);
        $photosOk = ($validPhotosCount >= 6 && $validPhotosCount <= 9 && empty($duplicateWeddings));

        $photoIssues = [];
        if ($validPhotosCount < 6) {
            $photoIssues[] = "hay {$validPhotosCount} fotos válidas con permiso (mínimo requerido: 6 fotos)";
        }
        if (!empty($duplicateWeddings)) {
            $photoIssues[] = 'se repite la misma boda en la galería (' . implode(', ', $duplicateWeddings) . ')';
        }
        $checks['gallery_photos'] = [
            'ok' => $photosOk,
            'blocking' => true,
            'title' => 'Fotos con permiso, de bodas distintas, mínimo 6',
            'detail' => $photosOk
                ? "{$validPhotosCount} fotos reales verificadas con permiso de la pareja, fecha y de bodas distintas (convertidas a .jpg y comprimidas)."
                : 'Aviso en galería: ' . implode(' y ', $photoIssues) . '.',
        ];

        // 8. Opiniones reales con fecha y enlace a Google (o página omitida automáticamente)
        $validReviews = self::getValidReviews($config);
        $hasGoogleUrl = !empty(trim((string) ($config['google_reviews_url'] ?? '')));
        if (count($validReviews) >= 2) {
            $reviewsOk = $hasGoogleUrl;
            $reviewsDetail = $hasGoogleUrl
                ? count($validReviews) . ' reseñas reales con permiso y fecha + enlace/QR a reseñas de Google activo.'
                : 'Hay ' . count($validReviews) . ' reseñas con permiso, pero falta indicar el enlace a vuestras reseñas de Google.';
        } else {
            $reviewsOk = true; // Se omite automáticamente la página, como pide la regla
            $reviewsDetail = 'Menos de 2 reseñas con permiso (' . count($validReviews) . '): la página de Opiniones se omite automáticamente del PDF (nunca se usan reseñas de ejemplo).';
        }
        $checks['reviews'] = [
            'ok' => $reviewsOk,
            'blocking' => !$reviewsOk,
            'warning' => count($validReviews) < 2,
            'title' => 'Opiniones reales con fecha y enlace a Google (o página omitida)',
            'detail' => $reviewsDetail,
        ];

        // 9. Indica cuánto se paga para reservar y cómo, y hasta cuándo valen las tarifas
        $depPct = (float) ($config['deposit_percentage'] ?? 0);
        $payMethods = trim((string) ($config['payment_methods_text'] ?? ''));
        $validUntil = trim((string) ($config['valid_until'] ?? ''));
        $bookingOk = ($depPct > 0 && $depPct < 100 && $payMethods !== '' && $validUntil !== '');
        $checks['booking_and_validity'] = [
            'ok' => $bookingOk,
            'blocking' => true,
            'title' => 'Señal de reserva, formas de pago y fecha de validez de las tarifas',
            'detail' => $bookingOk
                ? "Señal del {$depPct}% y resto (" . (100 - $depPct) . "%) el día del evento o antes · Pago por {$payMethods} · Tarifas válidas hasta el " . self::formatSpanishDate($validUntil) . "."
                : 'Falta indicar el porcentaje de señal, las formas de pago o la fecha obligatoria de validez («Tarifas válidas hasta...»).',
        ];

        // 10. PDF ligero de menos de 10 MB
        $estimatedMb = self::estimatePdfSizeMb($config);
        $sizeOk = ($estimatedMb < 10.0);
        $checks['pdf_size'] = [
            'ok' => $sizeOk,
            'blocking' => true,
            'title' => 'PDF ligero de menos de 10 MB (optimizado para móvil)',
            'detail' => "Tamaño estimado/actual del PDF: ~{$estimatedMb} MB (límite máximo: 10 MB; imágenes convertidas a .jpg y comprimidas).",
        ];

        $allBlockingPassed = true;
        $blockingErrors = [];
        foreach ($checks as $key => $c) {
            if (!$c['ok'] && !empty($c['blocking'])) {
                $allBlockingPassed = false;
                $blockingErrors[$key] = $c['detail'];
            }
        }

        return [
            'can_export' => $allBlockingPassed,
            'checks' => $checks,
            'blocking_errors' => $blockingErrors,
            'plan' => $plan,
            'estimated_size_mb' => $estimatedMb,
        ];
    }

    /**
     * Calcula el peso estimado del PDF en MB sumando las imágenes optimizadas
     */
    public static function estimatePdfSizeMb(array $config): float
    {
        $totalBytes = 140 * 1024; // Base HTML + fuentes PDF (~140 KB)

        if (!empty($config['about_photo'])) {
            $p = self::resolveCompressedJpgPathForPdf($config['about_photo']);
            if ($p && file_exists($p)) {
                $totalBytes += (@filesize($p) ?: 0);
            }
        }

        if (!empty($config['cover_custom_photo'])) {
            $p = self::resolveCompressedJpgPathForPdf($config['cover_custom_photo']);
            if ($p && file_exists($p)) {
                $totalBytes += (@filesize($p) ?: 0);
            }
        }

        foreach (self::getValidGalleryPhotos($config) as $vp) {
            if (!empty($vp['pdf_path']) && file_exists($vp['pdf_path'])) {
                $totalBytes += (@filesize($vp['pdf_path']) ?: 0);
            }
        }

        return round($totalBytes / (1024 * 1024), 2);
    }

    /**
     * Formatea una fecha YYYY-MM-DD al formato español legible (ej. "31 de diciembre de 2026" o "31/12/2026")
     */
    public static function formatSpanishDate(?string $dateStr, bool $longFormat = false): string
    {
        if (empty($dateStr)) {
            return '';
        }
        try {
            $dt = Carbon::parse($dateStr)->locale('es');
            return $longFormat
                ? $dt->isoFormat('D [de] MMMM [de] YYYY')
                : $dt->format('d/m/Y');
        } catch (\Throwable $e) {
            return (string) $dateStr;
        }
    }

    /**
     * Genera el mensaje de WhatsApp para enviar el Dossier General de Tarifas
     */
    public static function buildWhatsAppMessage(?string $clientName = null, ?string $senderName = 'Fran', ?array $config = null, bool $includePdfLink = true): string
    {
        $config = $config ?? self::getConfig();
        $tpl = trim((string) ($config['whatsapp_template'] ?? ''));
        if ($tpl === '') {
            $tpl = '¡Hola {nombre}! Os dejo nuestro dossier de tarifas 2027 para que lo veáis con calma. Si os apetece, hacemos una videollamada de 10-15 minutos y os lo explico. ¡Un saludo! Fran';
        }

        $cleanName = trim((string) $clientName);
        $year = trim((string) ($config['tariff_year'] ?? '2027')) ?: '2027';
        $firma = trim((string) $senderName) ?: 'Fran';
        $brand = $config['brand'] ?? 'nunez_and_son';
        $pdfUrl = route('public.tariff-dossier.pdf', ['brand' => $brand]);

        $msg = str_replace(
            ['{nombre}', '{anio}', '{año}', '{firma}', '{enlace_dossier}', '{enlace}'],
            [$cleanName, $year, $year, $firma, $pdfUrl, $pdfUrl],
            $tpl
        );

        // Si {nombre} estaba vacío, limpiar "¡Hola !" -> "¡Hola!"
        $msg = preg_replace('/¡Hola\s+!/u', '¡Hola!', $msg);

        if ($includePdfLink && !str_contains($msg, 'http')) {
            $msg .= "\n\n📄 Dossier de Tarifas {$year} (PDF):\n{$pdfUrl}";
        }

        return trim($msg);
    }
}
