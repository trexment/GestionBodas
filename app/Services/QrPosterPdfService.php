<?php

namespace App\Services;

use App\Models\Event;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Generador vectorial autónomo de códigos QR estilizados (ISO/IEC 18004 Nivel H 30%)
 * y carteles PDF 1.4 listos para imprimir con iniciales de los novios, frases llamativas
 * y 3 formatos de hoja A4 (1xA4 completo, 2xA5 marcos de mesa, 4xA6 tarjetas de mesa con guías de corte).
 */
class QrPosterPdfService
{
    private const MM = 2.8346456693; // 72 / 25.4 pt/mm
    private const BEZIER_K = 0.5522847498;

    /** @var array<int, int> */
    private static array $exp = [];
    /** @var array<int, int> */
    private static array $log = [];
    private static bool $gfReady = false;

    /** @var array<int, array{0:int,1:int,2:int,3:int,4:int,5:int}> */
    private const VERSION_ECC_M = [
        1  => [16, 10, 1, 16, 0, 0],
        2  => [28, 16, 1, 28, 0, 0],
        3  => [44, 26, 1, 44, 0, 0],
        4  => [64, 18, 2, 32, 0, 0],
        5  => [86, 24, 2, 43, 0, 0],
        6  => [108, 16, 4, 27, 0, 0],
        7  => [124, 18, 4, 31, 0, 0],
        8  => [154, 22, 2, 38, 2, 39],
        9  => [182, 22, 3, 36, 2, 37],
        10 => [216, 26, 4, 43, 1, 44],
        11 => [254, 30, 1, 50, 4, 51],
        12 => [290, 22, 6, 36, 2, 37],
        13 => [334, 22, 8, 37, 1, 38],
        14 => [365, 24, 4, 40, 5, 41],
        15 => [415, 24, 5, 41, 5, 42],
    ];

    /** @var array<int, array{0:int,1:int,2:int,3:int,4:int,5:int}> */
    private const VERSION_ECC_H = [
        1  => [9, 17, 1, 9, 0, 0],
        2  => [16, 28, 1, 16, 0, 0],
        3  => [26, 22, 2, 13, 0, 0],
        4  => [36, 16, 4, 9, 0, 0],
        5  => [46, 22, 2, 11, 2, 12],
        6  => [60, 28, 4, 15, 0, 0],
        7  => [66, 26, 4, 13, 1, 14],
        8  => [86, 26, 4, 14, 2, 15],
        9  => [100, 24, 4, 12, 4, 13],
        10 => [122, 28, 6, 15, 2, 16],
        11 => [140, 24, 3, 12, 8, 13],
        12 => [158, 28, 7, 14, 4, 15],
        13 => [180, 22, 12, 11, 4, 12],
        14 => [197, 24, 11, 12, 5, 13],
        15 => [223, 24, 11, 12, 7, 13],
    ];

    /** @var array<int, array<int, int>> */
    private const ALIGNMENT_POS = [
        1  => [],
        2  => [6, 18],
        3  => [6, 22],
        4  => [6, 26],
        5  => [6, 30],
        6  => [6, 34],
        7  => [6, 22, 38],
        8  => [6, 24, 42],
        9  => [6, 26, 46],
        10 => [6, 28, 50],
        11 => [6, 30, 54],
        12 => [6, 32, 58],
        13 => [6, 34, 62],
        14 => [6, 26, 46, 66],
        15 => [6, 26, 48, 70],
    ];

    /**
     * @return array<string, array{label:string, headline:string, subtitle:string}>
     */
    public static function presetPhrases(): array
    {
        return [
            'directa' => [
                'label' => 'Directa y llamativa (Recomendada)',
                'headline' => '¡Capturad nuestra boda desde vuestros ojos!',
                'subtitle' => 'Foto que hagas hoy, escanea este código QR con tu móvil y súbela al instante. ¡Sin instalar ninguna app!',
            ],
            'paparazzi' => [
                'label' => 'Paparazzi oficial (Divertida para las mesas)',
                'headline' => '¡Hoy los fotógrafos oficiales sois vosotros!',
                'subtitle' => 'Queremos ver la boda desde todas las mesas. Escanea el QR con tu cámara y sube todas las fotos que hagas.',
            ],
            'fiesta' => [
                'label' => 'Barra libre y fiesta (Cañera para la noche)',
                'headline' => 'Lo que pasa en la fiesta... ¡se sube a este QR!',
                'subtitle' => 'Escanea aquí y comparte tus fotos más divertidas de la noche. ¡Las queremos ver todas mañana!',
            ],
            'emotiva' => [
                'label' => 'Emotiva y elegante',
                'headline' => 'Ayúdanos a guardar cada recuerdo',
                'subtitle' => 'Apunta con la cámara de tu móvil al código QR y comparte con nosotros tus mejores momentos de hoy.',
            ],
            'clara' => [
                'label' => 'Súper clara y sencilla (Para todas las edades)',
                'headline' => 'Sube aquí las fotos que hagas en la boda',
                'subtitle' => 'Solo tienes que abrir la cámara de tu móvil, apuntar al código QR de abajo y elegir tus fotos.',
            ],
            'evento' => [
                'label' => 'Evento general / Cumpleaños / Fiesta privada',
                'headline' => '¡Comparte aquí tus mejores fotos del evento!',
                'subtitle' => 'Escanea este código QR con la cámara de tu móvil y sube tus fotos en calidad original sin instalar nada.',
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function visualStyles(): array
    {
        return [
            'elegante' => [
                'label' => 'Clásico Boda (Dorado & Marfil)',
                'bg' => '#FDFBF7',
                'card_bg' => '#FFFFFF',
                'outer_border' => '#B68D40',
                'inner_border' => '#DFC896',
                'accent' => '#A67C2E',
                'accent_soft' => '#F7F1E3',
                'title_color' => '#1C1917',
                'text_color' => '#334155',
                'muted_color' => '#78716C',
                'qr_dark' => '#1C1917',
                'qr_eye' => '#9A7024',
                'serif' => true,
            ],
            'minimal' => [
                'label' => 'Minimalista Editorial (Blanco & Negro)',
                'bg' => '#FFFFFF',
                'card_bg' => '#FFFFFF',
                'outer_border' => '#111827',
                'inner_border' => '#CBD5E1',
                'accent' => '#111827',
                'accent_soft' => '#F1F5F9',
                'title_color' => '#0F172A',
                'text_color' => '#334155',
                'muted_color' => '#64748B',
                'qr_dark' => '#0F172A',
                'qr_eye' => '#0F172A',
                'serif' => true,
            ],
            'botanico' => [
                'label' => 'Romántico Salvia & Olivo',
                'bg' => '#F6F9F6',
                'card_bg' => '#FFFFFF',
                'outer_border' => '#3F6251',
                'inner_border' => '#B7CEC1',
                'accent' => '#355746',
                'accent_soft' => '#EAF2EC',
                'title_color' => '#182A21',
                'text_color' => '#334155',
                'muted_color' => '#5C7467',
                'qr_dark' => '#182A21',
                'qr_eye' => '#355746',
                'serif' => true,
            ],
            'fiesta' => [
                'label' => 'Fiesta & Barra Libre (Índigo & Magenta)',
                'bg' => '#F8F7FF',
                'card_bg' => '#FFFFFF',
                'outer_border' => '#4F46E5',
                'inner_border' => '#C7D2FE',
                'accent' => '#4F46E5',
                'accent_soft' => '#EEF2FF',
                'title_color' => '#1E1B4B',
                'text_color' => '#334155',
                'muted_color' => '#6366F1',
                'qr_dark' => '#1E1B4B',
                'qr_eye' => '#DB2777',
                'serif' => false,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function layouts(): array
    {
        return [
            'a4'   => '1 × A4 Cartel Grande (Barra libre / Entrada / Photocall)',
            '2xa5' => '2 × A5 en 1 hoja A4 (Marcos medianos de mesa)',
            '4xa6' => '4 × A6 en 1 hoja A4 con guías de corte (Tarjetas para todas las mesas)',
        ];
    }

    /**
     * Calcula los valores por defecto ideales para un Event de EventosMusicales.
     *
     * @return array{names:string, initials:string, kicker:string, footer:string, preset:string, headline:string, subtitle:string}
     */
    public static function defaultsForEvent(Event $event): array
    {
        $extracted = self::extractCoupleDefaults((string) $event->name);
        $isWedding = (bool) ($event->is_wedding || $extracted['is_couple']);
        $presets = self::presetPhrases();
        $presetKey = $isWedding ? 'directa' : 'evento';
        $preset = $presets[$presetKey];

        $footer = '';
        if (!empty($event->event_date)) {
            $ymd = Carbon::parse($event->event_date)->format('Y-m-d');
            $footer = self::formatSpanishDate($ymd);
        }

        return [
            'names' => $extracted['names'],
            'initials' => $extracted['initials'],
            'kicker' => $isWedding ? 'NUESTRA BODA · ÁLBUM DE FOTOS' : 'ÁLBUM COLABORATIVO DE FOTOS',
            'footer' => $footer,
            'preset' => $presetKey,
            'headline' => $preset['headline'],
            'subtitle' => $preset['subtitle'],
        ];
    }

    /**
     * @return array{names:string, initials:string, is_couple:bool}
     */
    public static function extractCoupleDefaults(string $rawTitle): array
    {
        $clean = trim($rawTitle);
        if ($clean === '') {
            return ['names' => 'Nuestra Boda', 'initials' => '', 'is_couple' => false];
        }

        $clean = (string) preg_replace('/\s+(?:[-–—|]|\().*$/u', '', $clean);
        $stripped = (string) preg_replace('/^(?:la\s+)?(?:boda|enlace|celebraci[oó]n|fiesta|evento)\s+(?:de\s+)?/iu', '', trim($clean));
        $stripped = trim($stripped);
        if ($stripped === '') {
            $stripped = $clean;
        }

        if (preg_match('/^([^\d&+\/]{2,28}?)\s+(?:y|e|&|\+|\/)\s+([^\d&+\/]{2,28})$/iu', $stripped, $m)) {
            $p1 = self::titleCaseName(trim($m[1]));
            $p2 = self::titleCaseName(trim($m[2]));
            $i1 = self::firstLetterUpper($p1);
            $i2 = self::firstLetterUpper($p2);
            if ($i1 !== '' && $i2 !== '') {
                return [
                    'names' => $p1 . ' & ' . $p2,
                    'initials' => $i1 . '&' . $i2,
                    'is_couple' => true,
                ];
            }
        }

        if (preg_match('/^([^\d&+\/]{2,25})\s*[&+]\s*([^\d&+\/]{2,25})$/iu', $stripped, $m)) {
            $p1 = self::titleCaseName(trim($m[1]));
            $p2 = self::titleCaseName(trim($m[2]));
            $i1 = self::firstLetterUpper($p1);
            $i2 = self::firstLetterUpper($p2);
            if ($i1 !== '' && $i2 !== '') {
                return [
                    'names' => $p1 . ' & ' . $p2,
                    'initials' => $i1 . '&' . $i2,
                    'is_couple' => true,
                ];
            }
        }

        return [
            'names' => $stripped,
            'initials' => self::firstLetterUpper($stripped),
            'is_couple' => false,
        ];
    }

    public static function formatSpanishDate(?string $ymd): string
    {
        $ymd = trim((string) $ymd);
        if ($ymd === '' || !preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $ymd, $m)) {
            return '';
        }
        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
        return ((int) $m[3]) . ' de ' . ($months[(int) $m[2]] ?? '') . ' de ' . ((int) $m[1]);
    }

    /**
     * Renderiza un SVG estilizado con iniciales en el centro para previsualización inmediata en la web.
     *
     * @param array<string, mixed> $opts
     */
    public static function toStylizedSvg(string $url, array $opts = []): string
    {
        $initials = mb_substr(trim((string) ($opts['initials'] ?? '')), 0, 6);
        $hasMedallion = $initials !== '';
        $qr = self::encodeQr($url, 'H', $hasMedallion ? 5 : 2);
        $size = $qr['size'];
        $margin = 1.6;
        $styleKey = (string) ($opts['style'] ?? 'elegante');
        $styles = self::visualStyles();
        $theme = $styles[$styleKey] ?? $styles['elegante'];

        $dark = (string) ($opts['dark'] ?? $theme['qr_dark']);
        $eye = (string) ($opts['eye'] ?? $theme['qr_eye']);
        $border = (string) ($opts['border'] ?? $theme['outer_border']);
        $soft = (string) ($opts['soft'] ?? $theme['accent_soft']);
        $serif = (bool) ($theme['serif'] ?? true);

        $viewSize = $size + ($margin * 2.0);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $viewSize . ' ' . $viewSize . '" class="w-full h-full">';
        $svg .= '<rect width="100%" height="100%" fill="#ffffff" rx="1.5"/>';

        $dotSize = 0.86;
        $dotOffset = (1.0 - $dotSize) / 2.0;

        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                if (!$qr['modules'][$r][$c]) {
                    continue;
                }
                if (self::isInFinderEye($r, $c, $size) || self::isInCenterMedallion($r, $c, $size, $hasMedallion)) {
                    continue;
                }
                $x = round($c + $margin + $dotOffset, 3);
                $y = round($r + $margin + $dotOffset, 3);
                $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $dotSize . '" height="' . $dotSize . '" rx="0.27" fill="' . e($dark) . '"/>';
            }
        }

        $eyes = [
            [$margin, $margin],
            [$margin + $size - 7, $margin],
            [$margin, $margin + $size - 7],
        ];
        foreach ($eyes as [$ex, $ey]) {
            $svg .= '<rect x="' . $ex . '" y="' . $ey . '" width="7" height="7" rx="1.85" fill="' . e($dark) . '"/>';
            $svg .= '<rect x="' . ($ex + 1) . '" y="' . ($ey + 1) . '" width="5" height="5" rx="1.25" fill="#ffffff"/>';
            $svg .= '<rect x="' . ($ex + 2) . '" y="' . ($ey + 2) . '" width="3" height="3" rx="0.9" fill="' . e($eye) . '"/>';
        }

        if ($hasMedallion) {
            $cx = $viewSize / 2.0;
            $cy = $viewSize / 2.0;
            $rOuter = round(max(3.65, $size * 0.118), 3);
            $rRing = round($rOuter - 0.45, 3);
            $rInner = round($rRing - 0.55, 3);
            $displayInit = mb_strtoupper($initials, 'UTF-8');
            $len = mb_strlen($displayInit, 'UTF-8');
            $fontSize = round($rInner * ($len <= 1 ? 1.15 : ($len === 2 ? 0.95 : ($len === 3 ? 0.76 : 0.56))), 2);
            $fontFamily = $serif ? "Georgia, 'Times New Roman', serif" : "system-ui, -apple-system, sans-serif";
            $fontStyle = $serif ? 'italic' : 'normal';

            $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $rOuter . '" fill="#ffffff"/>';
            $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $rRing . '" fill="' . e($soft) . '" stroke="' . e($border) . '" stroke-width="0.36"/>';
            $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $rInner . '" fill="#ffffff"/>';
            $svg .= '<text x="' . $cx . '" y="' . round($cy + ($fontSize * 0.34), 3) . '" text-anchor="middle" font-family="' . $fontFamily . '" font-style="' . $fontStyle . '" font-weight="700" font-size="' . $fontSize . '" fill="' . e($dark) . '">'
                . e($displayInit)
                . '</text>';
        }

        $svg .= '</svg>';
        return $svg;
    }

    /**
     * Genera el binario PDF 1.4 listo para imprimir para un evento.
     *
     * @param array<string, mixed> $options
     */
    public static function generatePdf(string $url, array $options = []): string
    {
        $url = trim($url) !== '' ? trim($url) : 'https://fotos.appinformaticas.com';

        $layout = (string) ($options['layout'] ?? 'a4');
        if (!isset(self::layouts()[$layout])) {
            $layout = 'a4';
        }

        $styles = self::visualStyles();
        $styleKey = (string) ($options['style'] ?? 'elegante');
        if (!isset($styles[$styleKey])) {
            $styleKey = 'elegante';
        }
        $theme = $styles[$styleKey];

        $presets = self::presetPhrases();
        $defaultPreset = $presets['directa'];

        $showInitials = !array_key_exists('show_initials', $options) || (bool) $options['show_initials'];
        $initials = $showInitials ? mb_substr(trim((string) ($options['initials'] ?? '')), 0, 6) : '';

        $config = [
            'url' => $url,
            'short_display_url' => (string) preg_replace('#^https?://#i', '', $url),
            'kicker' => trim((string) ($options['kicker'] ?? 'NUESTRA BODA · ÁLBUM DE FOTOS')),
            'names' => trim((string) ($options['names'] ?? 'Nuestra Boda')),
            'initials' => $initials,
            'headline' => trim((string) ($options['headline'] ?? $defaultPreset['headline'])),
            'subtitle' => trim((string) ($options['subtitle'] ?? $defaultPreset['subtitle'])),
            'footer' => trim((string) ($options['footer'] ?? '')),
            'pin' => trim((string) ($options['pin'] ?? '')),
            'show_url' => !array_key_exists('show_url', $options) || (bool) $options['show_url'],
            'theme' => $theme,
        ];

        $qrMatrix = self::encodeQr($url, 'H', $initials !== '' ? 5 : 2);

        $isLandscape = ($layout === '2xa5');
        $pageWMm = $isLandscape ? 297.0 : 210.0;
        $pageHMm = $isLandscape ? 210.0 : 297.0;
        $pageWPt = $pageWMm * self::MM;
        $pageHPt = $pageHMm * self::MM;

        $stream = '';
        if ($layout === 'a4') {
            $stream .= self::renderCardCommands(0.0, 0.0, 210.0, 297.0, $pageHPt, $config, $qrMatrix, false);
        } elseif ($layout === '2xa5') {
            $stream .= self::renderCardCommands(0.0, 0.0, 148.5, 210.0, $pageHPt, $config, $qrMatrix, true);
            $stream .= self::renderCardCommands(148.5, 0.0, 148.5, 210.0, $pageHPt, $config, $qrMatrix, true);
            $stream .= self::renderCutLine(148.5, 0.0, 148.5, 210.0, $pageHPt);
        } else {
            $stream .= self::renderCardCommands(0.0, 0.0, 105.0, 148.5, $pageHPt, $config, $qrMatrix, true);
            $stream .= self::renderCardCommands(105.0, 0.0, 105.0, 148.5, $pageHPt, $config, $qrMatrix, true);
            $stream .= self::renderCardCommands(0.0, 148.5, 105.0, 148.5, $pageHPt, $config, $qrMatrix, true);
            $stream .= self::renderCardCommands(105.0, 148.5, 105.0, 148.5, $pageHPt, $config, $qrMatrix, true);
            $stream .= self::renderCutLine(105.0, 0.0, 105.0, 297.0, $pageHPt);
            $stream .= self::renderCutLine(0.0, 148.5, 210.0, 148.5, $pageHPt);
        }

        return self::assemblePdfDocument($stream, $pageWPt, $pageHPt, $config['names']);
    }

    // =========================================================================
    // MOTOR QR ISO/IEC 18004 (NIVELES H & M)
    // =========================================================================

    private static function initGf(): void
    {
        if (self::$gfReady) {
            return;
        }
        self::$exp = array_fill(0, 512, 0);
        self::$log = array_fill(0, 256, 0);
        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$exp[$i] = $x;
            self::$log[$x] = $i;
            $x <<= 1;
            if (($x & 0x100) !== 0) {
                $x ^= 0x11d;
            }
        }
        for ($i = 255; $i < 512; $i++) {
            self::$exp[$i] = self::$exp[$i - 255];
        }
        self::$gfReady = true;
    }

    private static function gfMul(int $a, int $b): int
    {
        if ($a === 0 || $b === 0) {
            return 0;
        }
        return self::$exp[self::$log[$a] + self::$log[$b]];
    }

    /** @return array<int, int> */
    private static function rsGeneratorPoly(int $degree): array
    {
        $poly = [1];
        for ($i = 0; $i < $degree; $i++) {
            $next = array_fill(0, count($poly) + 1, 0);
            $root = self::$exp[$i];
            $len = count($poly);
            for ($j = 0; $j < $len; $j++) {
                $next[$j] ^= $poly[$j];
                $next[$j + 1] ^= self::gfMul($poly[$j], $root);
            }
            $poly = $next;
        }
        return $poly;
    }

    /**
     * @param array<int, int> $data
     * @return array<int, int>
     */
    private static function rsRemainder(array $data, int $ecLen): array
    {
        $gen = self::rsGeneratorPoly($ecLen);
        $rem = array_fill(0, $ecLen, 0);
        foreach ($data as $byte) {
            $factor = $byte ^ $rem[0];
            array_shift($rem);
            $rem[] = 0;
            if ($factor !== 0) {
                for ($j = 0; $j < $ecLen; $j++) {
                    $rem[$j] ^= self::gfMul($gen[$j + 1], $factor);
                }
            }
        }
        return $rem;
    }

    /**
     * @return array{version:int, size:int, mask:int, ecc:string, modules:array<int, array<int, int>>}
     */
    public static function encodeQr(string $text, string $ecc = 'H', int $minVersion = 1): array
    {
        self::initGf();
        $level = strtoupper($ecc) === 'M' ? 'M' : 'H';
        $table = $level === 'H' ? self::VERSION_ECC_H : self::VERSION_ECC_M;

        $bytes = array_values(unpack('C*', $text) ?: []);
        $byteLen = count($bytes);

        $version = 0;
        $startV = max(1, min(15, $minVersion));
        for ($v = $startV; $v <= 15; $v++) {
            $totalData = $table[$v][0];
            $lenBits = $v <= 9 ? 8 : 16;
            if ((4 + $lenBits + ($byteLen * 8)) <= ($totalData * 8)) {
                $version = $v;
                break;
            }
        }

        if ($version === 0) {
            if ($level === 'H') {
                return self::encodeQr($text, 'M', $minVersion);
            }
            throw new InvalidArgumentException('URL demasiado larga para código QR.');
        }

        $codewords = self::buildCodewords($bytes, $table[$version], $version);
        [$size, $baseModules, $isFunc] = self::createBaseMatrix($version);

        $bitIdx = 0;
        $totalBits = count($codewords) * 8;
        for ($right = $size - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5;
            }
            for ($vert = 0; $vert < $size; $vert++) {
                $upward = ((($right + 1) & 2) === 0);
                $r = $upward ? ($size - 1 - $vert) : $vert;
                for ($j = 0; $j < 2; $j++) {
                    $c = $right - $j;
                    if (!$isFunc[$r][$c]) {
                        $dark = 0;
                        if ($bitIdx < $totalBits) {
                            $dark = ($codewords[$bitIdx >> 3] >> (7 - ($bitIdx & 7))) & 1;
                            $bitIdx++;
                        }
                        $baseModules[$r][$c] = $dark;
                    }
                }
            }
        }

        $bestMask = 0;
        $bestScore = PHP_INT_MAX;
        $bestModules = $baseModules;

        for ($mask = 0; $mask < 8; $mask++) {
            $copy = $baseModules;
            for ($r = 0; $r < $size; $r++) {
                for ($c = 0; $c < $size; $c++) {
                    if (!$isFunc[$r][$c] && self::maskCondition($mask, $r, $c)) {
                        $copy[$r][$c] ^= 1;
                    }
                }
            }
            self::drawFormatBits($copy, $size, $mask, $level);
            $score = self::penaltyScore($copy, $size);
            if ($score < $bestScore) {
                $bestScore = $score;
                $bestMask = $mask;
                $bestModules = $copy;
            }
        }

        return [
            'version' => $version,
            'size' => $size,
            'mask' => $bestMask,
            'ecc' => $level,
            'modules' => $bestModules,
        ];
    }

    private static function isInFinderEye(int $r, int $c, int $size): bool
    {
        return ($r >= 0 && $r <= 6 && $c >= 0 && $c <= 6)
            || ($r >= 0 && $r <= 6 && $c >= $size - 7 && $c < $size)
            || ($r >= $size - 7 && $r < $size && $c >= 0 && $c <= 6);
    }

    private static function isInCenterMedallion(int $r, int $c, int $size, bool $hasMedallion): bool
    {
        if (!$hasMedallion) {
            return false;
        }
        $center = ($size - 1) / 2.0;
        $radius = max(3.6, $size * 0.118);
        $dr = $r - $center;
        $dc = $c - $center;
        return (($dr * $dr) + ($dc * $dc)) <= ($radius * $radius);
    }

    /**
     * @param array<int, int> $bytes
     * @param array{0:int,1:int,2:int,3:int,4:int,5:int} $spec
     * @return array<int, int>
     */
    private static function buildCodewords(array $bytes, array $spec, int $version): array
    {
        [$totalData, $ecLen, $g1Blocks, $g1Data, $g2Blocks, $g2Data] = $spec;
        $bits = [];
        $pushBits = static function (int $val, int $len) use (&$bits): void {
            for ($i = $len - 1; $i >= 0; $i--) {
                $bits[] = ($val >> $i) & 1;
            }
        };

        $pushBits(0b0100, 4);
        $pushBits(count($bytes), $version <= 9 ? 8 : 16);
        foreach ($bytes as $b) {
            $pushBits($b, 8);
        }

        $capacityBits = $totalData * 8;
        $term = min(4, $capacityBits - count($bits));
        if ($term > 0) {
            $pushBits(0, $term);
        }
        while ((count($bits) % 8) !== 0) {
            $bits[] = 0;
        }

        $dataBytes = array_fill(0, $totalData, 0);
        $filledBytes = intdiv(count($bits), 8);
        for ($i = 0; $i < $filledBytes; $i++) {
            $val = 0;
            for ($j = 0; $j < 8; $j++) {
                $val = ($val << 1) | $bits[($i * 8) + $j];
            }
            $dataBytes[$i] = $val;
        }

        $pads = [0xec, 0x11];
        for ($i = $filledBytes, $p = 0; $i < $totalData; $i++, $p ^= 1) {
            $dataBytes[$i] = $pads[$p];
        }

        $dataBlocks = [];
        $ecBlocks = [];
        $offset = 0;
        for ($b = 0; $b < $g1Blocks; $b++) {
            $slice = array_slice($dataBytes, $offset, $g1Data);
            $offset += $g1Data;
            $dataBlocks[] = $slice;
            $ecBlocks[] = self::rsRemainder($slice, $ecLen);
        }
        for ($b = 0; $b < $g2Blocks; $b++) {
            $slice = array_slice($dataBytes, $offset, $g2Data);
            $offset += $g2Data;
            $dataBlocks[] = $slice;
            $ecBlocks[] = self::rsRemainder($slice, $ecLen);
        }

        $result = [];
        $maxDataLen = max($g1Data, $g2Data);
        for ($i = 0; $i < $maxDataLen; $i++) {
            foreach ($dataBlocks as $blk) {
                if ($i < count($blk)) {
                    $result[] = $blk[$i];
                }
            }
        }
        for ($i = 0; $i < $ecLen; $i++) {
            foreach ($ecBlocks as $eblk) {
                $result[] = $eblk[$i];
            }
        }
        return $result;
    }

    /**
     * @return array{0:int, 1:array<int, array<int, int>>, 2:array<int, array<int, bool>>}
     */
    private static function createBaseMatrix(int $version): array
    {
        $size = ($version * 4) + 17;
        $modules = array_fill(0, $size, array_fill(0, $size, 0));
        $isFunc = array_fill(0, $size, array_fill(0, $size, false));

        $setFunc = static function (int $r, int $c, bool $dark) use ($size, &$modules, &$isFunc): void {
            if ($r >= 0 && $r < $size && $c >= 0 && $c < $size) {
                $modules[$r][$c] = $dark ? 1 : 0;
                $isFunc[$r][$c] = true;
            }
        };

        $drawFinder = static function (int $top, int $left) use ($setFunc): void {
            for ($dr = -1; $dr <= 7; $dr++) {
                for ($dc = -1; $dc <= 7; $dc++) {
                    $m = max(abs($dr - 3), abs($dc - 3));
                    $dark = ($dr >= 0 && $dr <= 6 && $dc >= 0 && $dc <= 6) && ($m !== 2 && $m !== 4);
                    $setFunc($top + $dr, $left + $dc, $dark);
                }
            }
        };

        $drawFinder(0, 0);
        $drawFinder(0, $size - 7);
        $drawFinder($size - 7, 0);

        for ($i = 8; $i < $size - 8; $i++) {
            $setFunc(6, $i, ($i % 2) === 0);
            $setFunc($i, 6, ($i % 2) === 0);
        }

        $pos = self::ALIGNMENT_POS[$version];
        $pCount = count($pos);
        for ($i = 0; $i < $pCount; $i++) {
            for ($j = 0; $j < $pCount; $j++) {
                if (($i === 0 && $j === 0) || ($i === 0 && $j === $pCount - 1) || ($i === $pCount - 1 && $j === 0)) {
                    continue;
                }
                for ($dr = -2; $dr <= 2; $dr++) {
                    for ($dc = -2; $dc <= 2; $dc++) {
                        $setFunc($pos[$i] + $dr, $pos[$j] + $dc, max(abs($dr), abs($dc)) !== 1);
                    }
                }
            }
        }

        $setFunc($size - 8, 8, true);
        for ($i = 0; $i <= 8; $i++) {
            if ($i !== 6) {
                if (!$isFunc[8][$i]) {
                    $setFunc(8, $i, false);
                }
                if (!$isFunc[$i][8]) {
                    $setFunc($i, 8, false);
                }
            }
        }
        for ($i = 0; $i < 8; $i++) {
            if (!$isFunc[8][$size - 1 - $i]) {
                $setFunc(8, $size - 1 - $i, false);
            }
            if (!$isFunc[$size - 1 - $i][8]) {
                $setFunc($size - 1 - $i, 8, false);
            }
        }

        if ($version >= 7) {
            $rem = $version;
            for ($i = 0; $i < 12; $i++) {
                $rem = ($rem << 1) ^ (($rem >> 11) * 0x1f25);
            }
            $vBits = ($version << 12) | $rem;
            for ($i = 0; $i < 18; $i++) {
                $bit = (($vBits >> $i) & 1) === 1;
                $a = $size - 11 + ($i % 3);
                $b = intdiv($i, 3);
                $setFunc($b, $a, $bit);
                $setFunc($a, $b, $bit);
            }
        }

        return [$size, $modules, $isFunc];
    }

    /** @param array<int, array<int, int>> $modules */
    private static function drawFormatBits(array &$modules, int $size, int $mask, string $level): void
    {
        $eccBits = $level === 'H' ? 0b10 : 0b00;
        $data = ($eccBits << 3) | $mask;
        $rem = $data;
        for ($i = 0; $i < 10; $i++) {
            $rem = ($rem << 1) ^ (($rem >> 9) * 0x537);
        }
        $bits = (($data << 10) | $rem) ^ 0x5412;

        for ($i = 0; $i <= 5; $i++) {
            $modules[$i][8] = ($bits >> $i) & 1;
        }
        $modules[7][8] = ($bits >> 6) & 1;
        $modules[8][8] = ($bits >> 7) & 1;
        $modules[8][7] = ($bits >> 8) & 1;
        for ($i = 9; $i < 15; $i++) {
            $modules[8][14 - $i] = ($bits >> $i) & 1;
        }
        for ($i = 0; $i < 8; $i++) {
            $modules[8][$size - 1 - $i] = ($bits >> $i) & 1;
        }
        for ($i = 8; $i < 15; $i++) {
            $modules[$size - 15 + $i][8] = ($bits >> $i) & 1;
        }
        $modules[$size - 8][8] = 1;
    }

    private static function maskCondition(int $mask, int $r, int $c): bool
    {
        return match ($mask) {
            0 => (($r + $c) % 2) === 0,
            1 => ($r % 2) === 0,
            2 => ($c % 3) === 0,
            3 => (($r + $c) % 3) === 0,
            4 => ((intdiv($r, 2) + intdiv($c, 3)) % 2) === 0,
            5 => ((($r * $c) % 2) + (($r * $c) % 3)) === 0,
            6 => (((($r * $c) % 2) + (($r * $c) % 3)) % 2) === 0,
            7 => (((($r + $c) % 2) + (($r * $c) % 3)) % 2) === 0,
            default => false,
        };
    }

    /** @param array<int, array<int, int>> $m */
    private static function penaltyScore(array $m, int $size): int
    {
        $score = 0;
        for ($r = 0; $r < $size; $r++) {
            $runColor = $m[$r][0];
            $runLen = 1;
            for ($c = 1; $c < $size; $c++) {
                if ($m[$r][$c] === $runColor) {
                    $runLen++;
                    if ($runLen === 5) {
                        $score += 3;
                    } elseif ($runLen > 5) {
                        $score += 1;
                    }
                } else {
                    $runColor = $m[$r][$c];
                    $runLen = 1;
                }
            }
        }
        for ($c = 0; $c < $size; $c++) {
            $runColor = $m[0][$c];
            $runLen = 1;
            for ($r = 1; $r < $size; $r++) {
                if ($m[$r][$c] === $runColor) {
                    $runLen++;
                    if ($runLen === 5) {
                        $score += 3;
                    } elseif ($runLen > 5) {
                        $score += 1;
                    }
                } else {
                    $runColor = $m[$r][$c];
                    $runLen = 1;
                }
            }
        }
        for ($r = 0; $r < $size - 1; $r++) {
            for ($c = 0; $c < $size - 1; $c++) {
                $v = $m[$r][$c];
                if ($v === $m[$r][$c + 1] && $v === $m[$r + 1][$c] && $v === $m[$r + 1][$c + 1]) {
                    $score += 3;
                }
            }
        }
        return $score;
    }

    // =========================================================================
    // MOTOR VECTORIAL PDF 1.4
    // =========================================================================

    /**
     * @param array<string, mixed> $config
     * @param array{version:int, size:int, mask:int, ecc:string, modules:array<int, array<int, int>>} $qr
     */
    private static function renderCardCommands(
        float $oxMm,
        float $oyMm,
        float $wMm,
        float $hMm,
        float $pageHPt,
        array $config,
        array $qr,
        bool $isMultiUp
    ): string {
        $s = $wMm / 210.0;
        $theme = $config['theme'];
        $serif = (bool) $theme['serif'];

        $titleFont = $serif ? 'F2' : 'F6';
        $titleItalicFont = $serif ? 'F4' : 'F6';
        $headlineFont = $serif ? 'F2' : 'F6';
        $bodyFont = 'F5';
        $bodyBoldFont = 'F6';
        $italicFont = $serif ? 'F3' : 'F7';

        $out = "q\n";

        $bleedInset = $isMultiUp ? (2.5 * $s) : 0.0;
        $out .= self::cmdFillRgb($theme['bg']);
        $out .= self::cmdRoundedRect($oxMm + $bleedInset, $oyMm + $bleedInset, $wMm - ($bleedInset * 2.0), $hMm - ($bleedInset * 2.0), 0.0, $pageHPt, 'f');

        $mOuter = 10.5 * $s;
        $mInner = 13.5 * $s;

        $out .= self::cmdFillRgb($theme['card_bg']);
        $out .= self::cmdStrokeRgb($theme['outer_border']);
        $out .= sprintf("%.3f w\n", max(0.55, 1.35 * $s));
        $out .= self::cmdRoundedRect($oxMm + $mOuter, $oyMm + $mOuter, $wMm - ($mOuter * 2.0), $hMm - ($mOuter * 2.0), 3.5 * $s, $pageHPt, 'B');

        $out .= self::cmdStrokeRgb($theme['inner_border']);
        $out .= sprintf("%.3f w\n", max(0.35, 0.65 * $s));
        $out .= self::cmdRoundedRect($oxMm + $mInner, $oyMm + $mInner, $wMm - ($mInner * 2.0), $hMm - ($mInner * 2.0), 2.2 * $s, $pageHPt, 'S');

        $cornerOffset = 17.2 * $s;
        $diamondR = 1.35 * $s;
        $out .= self::cmdFillRgb($theme['outer_border']);
        $out .= self::cmdDiamond($oxMm + $cornerOffset, $oyMm + $cornerOffset, $diamondR, $diamondR, $pageHPt, 'f');
        $out .= self::cmdDiamond($oxMm + $wMm - $cornerOffset, $oyMm + $cornerOffset, $diamondR, $diamondR, $pageHPt, 'f');
        $out .= self::cmdDiamond($oxMm + $cornerOffset, $oyMm + $hMm - $cornerOffset, $diamondR, $diamondR, $pageHPt, 'f');
        $out .= self::cmdDiamond($oxMm + $wMm - $cornerOffset, $oyMm + $hMm - $cornerOffset, $diamondR, $diamondR, $pageHPt, 'f');

        $cxMm = $oxMm + ($wMm / 2.0);
        $maxContentWMm = 160.0 * $s;

        $kicker = mb_strtoupper((string) $config['kicker'], 'UTF-8');
        if ($kicker !== '') {
            $kickerFontPt = 8.4 * $s;
            $kickerW = self::measureTextMm($kicker, $bodyBoldFont, $kickerFontPt);
            $pillWMm = min($maxContentWMm, $kickerW + (14.0 * $s));
            $pillHMm = 7.4 * $s;
            $pillYMm = $oyMm + (22.5 * $s);

            $out .= self::cmdFillRgb($theme['accent_soft']);
            $out .= self::cmdStrokeRgb($theme['inner_border']);
            $out .= sprintf("%.3f w\n", max(0.3, 0.5 * $s));
            $out .= self::cmdRoundedRect($cxMm - ($pillWMm / 2.0), $pillYMm, $pillWMm, $pillHMm, 3.7 * $s, $pageHPt, 'B');

            $out .= self::cmdFillRgb($theme['accent']);
            $out .= self::cmdCenteredText($kicker, $cxMm, $pillYMm + (5.05 * $s), $bodyBoldFont, $kickerFontPt, $pageHPt);
        }

        $names = (string) $config['names'];
        $namesFontPt = 29.0 * $s;
        while ($namesFontPt > (15.0 * $s) && self::measureTextMm($names, $titleFont, $namesFontPt) > (158.0 * $s)) {
            $namesFontPt -= (1.2 * $s);
        }
        $namesLines = self::wrapText($names, $titleFont, $namesFontPt, 162.0 * $s, 2);
        $namesY = $oyMm + (45.0 * $s);
        $out .= self::cmdFillRgb($theme['title_color']);
        if (count($namesLines) <= 1) {
            $out .= self::cmdCenteredText($namesLines[0] ?? $names, $cxMm, $namesY, $titleFont, $namesFontPt, $pageHPt);
            $dividerYMm = $oyMm + (53.5 * $s);
        } else {
            $lineGapMm = ($namesFontPt * 1.12) / self::MM;
            $out .= self::cmdCenteredText($namesLines[0], $cxMm, $namesY - ($lineGapMm * 0.42), $titleFont, $namesFontPt, $pageHPt);
            $out .= self::cmdCenteredText($namesLines[1], $cxMm, $namesY + ($lineGapMm * 0.58), $titleFont, $namesFontPt, $pageHPt);
            $dividerYMm = $oyMm + (57.5 * $s);
        }

        $lineHalfMm = 26.0 * $s;
        $gapCenterMm = 4.5 * $s;
        $out .= self::cmdStrokeRgb($theme['outer_border']);
        $out .= sprintf("%.3f w\n", max(0.35, 0.65 * $s));
        $out .= self::cmdLine($cxMm - $lineHalfMm, $dividerYMm, $cxMm - $gapCenterMm, $dividerYMm, $pageHPt);
        $out .= self::cmdLine($cxMm + $gapCenterMm, $dividerYMm, $cxMm + $lineHalfMm, $dividerYMm, $pageHPt);
        $out .= self::cmdFillRgb($theme['outer_border']);
        $out .= self::cmdDiamond($cxMm, $dividerYMm, 1.8 * $s, 1.8 * $s, $pageHPt, 'f');

        $headline = (string) $config['headline'];
        $headlinePt = 15.2 * $s;
        $headlineLines = self::wrapText($headline, $headlineFont, $headlinePt, 158.0 * $s, 2);
        $curYMm = $dividerYMm + (12.0 * $s);
        $out .= self::cmdFillRgb($theme['title_color']);
        foreach ($headlineLines as $hl) {
            $out .= self::cmdCenteredText($hl, $cxMm, $curYMm, $headlineFont, $headlinePt, $pageHPt);
            $curYMm += (6.6 * $s);
        }

        $subtitle = (string) $config['subtitle'];
        if ($subtitle !== '') {
            $subPt = 10.4 * $s;
            $subLines = self::wrapText($subtitle, $bodyFont, $subPt, 154.0 * $s, 3);
            $curYMm += (1.6 * $s);
            $out .= self::cmdFillRgb($theme['text_color']);
            foreach ($subLines as $sl) {
                $out .= self::cmdCenteredText($sl, $cxMm, $curYMm, $bodyFont, $subPt, $pageHPt);
                $curYMm += (4.8 * $s);
            }
        }

        $qrFrameSizeMm = 94.0 * $s;
        $qrFrameXMm = $cxMm - ($qrFrameSizeMm / 2.0);
        $qrFrameYMm = $oyMm + (111.0 * $s);

        $out .= self::cmdFillRgb($theme['accent_soft']);
        $out .= self::cmdRoundedRect($qrFrameXMm - (1.8 * $s), $qrFrameYMm - (1.8 * $s), $qrFrameSizeMm + (3.6 * $s), $qrFrameSizeMm + (3.6 * $s), 7.5 * $s, $pageHPt, 'f');

        $out .= self::cmdFillRgb('#FFFFFF');
        $out .= self::cmdStrokeRgb($theme['outer_border']);
        $out .= sprintf("%.3f w\n", max(0.65, 1.5 * $s));
        $out .= self::cmdRoundedRect($qrFrameXMm, $qrFrameYMm, $qrFrameSizeMm, $qrFrameSizeMm, 6.2 * $s, $pageHPt, 'B');

        $scanBadgeText = 'ESCANEA AQUÍ';
        $scanBadgePt = 7.6 * $s;
        $scanBadgeWMm = self::measureTextMm($scanBadgeText, $bodyBoldFont, $scanBadgePt) + (10.0 * $s);
        $scanBadgeHMm = 5.8 * $s;
        $scanBadgeYMm = $qrFrameYMm - ($scanBadgeHMm / 2.0);

        $out .= self::cmdFillRgb($theme['outer_border']);
        $out .= self::cmdRoundedRect($cxMm - ($scanBadgeWMm / 2.0), $scanBadgeYMm, $scanBadgeWMm, $scanBadgeHMm, 2.9 * $s, $pageHPt, 'f');
        $out .= self::cmdFillRgb('#FFFFFF');
        $out .= self::cmdCenteredText($scanBadgeText, $cxMm, $scanBadgeYMm + (4.0 * $s), $bodyBoldFont, $scanBadgePt, $pageHPt);

        $qrInnerPadMm = 5.5 * $s;
        $out .= self::renderStylizedQrVector(
            $qrFrameXMm + $qrInnerPadMm,
            $qrFrameYMm + $qrInnerPadMm,
            $qrFrameSizeMm - ($qrInnerPadMm * 2.0),
            $pageHPt,
            $qr,
            (string) $config['initials'],
            $theme,
            $titleItalicFont
        );

        $stepsYMm = $oyMm + (219.5 * $s);
        $stepsBoxWMm = 166.0 * $s;
        $stepsBoxHMm = 13.0 * $s;
        $out .= self::cmdFillRgb($theme['accent_soft']);
        $out .= self::cmdStrokeRgb($theme['inner_border']);
        $out .= sprintf("%.3f w\n", max(0.3, 0.45 * $s));
        $out .= self::cmdRoundedRect($cxMm - ($stepsBoxWMm / 2.0), $stepsYMm, $stepsBoxWMm, $stepsBoxHMm, 3.8 * $s, $pageHPt, 'B');

        $stepsText = '1. Abre la cámara de tu móvil · 2. Apunta al QR · 3. Elige y sube tus fotos';
        $out .= self::cmdFillRgb($theme['title_color']);
        $out .= self::cmdCenteredText($stepsText, $cxMm, $stepsYMm + (8.1 * $s), $bodyBoldFont, 9.2 * $s, $pageHPt);

        $bottomCursorMm = $oyMm + (246.0 * $s);
        if ($config['footer'] !== '') {
            $out .= self::cmdFillRgb($theme['accent']);
            $out .= self::cmdCenteredText((string) $config['footer'], $cxMm, $bottomCursorMm, $italicFont, 11.2 * $s, $pageHPt);
        }

        if (!empty($config['show_url'])) {
            $urlDisplay = (string) $config['short_display_url'];
            $urlPt = 7.6 * $s;
            while ($urlPt > (5.2 * $s) && self::measureTextMm($urlDisplay, $bodyFont, $urlPt) > (164.0 * $s)) {
                $urlPt -= (0.4 * $s);
            }
            $out .= self::cmdFillRgb($theme['muted_color']);
            $out .= self::cmdCenteredText($urlDisplay, $cxMm, $oyMm + (272.5 * $s), $bodyFont, $urlPt, $pageHPt);
        }

        $out .= "Q\n";
        return $out;
    }

    /**
     * @param array{version:int, size:int, mask:int, ecc:string, modules:array<int, array<int, int>>} $qr
     * @param array<string, mixed> $theme
     */
    private static function renderStylizedQrVector(
        float $xMm,
        float $yMm,
        float $boxMm,
        float $pageHPt,
        array $qr,
        string $initials,
        array $theme,
        string $monogramFont
    ): string {
        $size = $qr['size'];
        $hasMedallion = trim($initials) !== '';
        $quiet = 1.2;
        $modMm = $boxMm / ($size + ($quiet * 2.0));
        $startX = $xMm + ($quiet * $modMm);
        $startY = $yMm + ($quiet * $modMm);

        $out = self::cmdFillRgb((string) $theme['qr_dark']);
        $dotMm = $modMm * 0.86;
        $dotOffsetMm = ($modMm - $dotMm) / 2.0;
        $dotRadiusMm = $modMm * 0.27;

        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                if (!$qr['modules'][$r][$c]) {
                    continue;
                }
                if (self::isInFinderEye($r, $c, $size) || self::isInCenterMedallion($r, $c, $size, $hasMedallion)) {
                    continue;
                }
                $out .= self::cmdRoundedRect($startX + ($c * $modMm) + $dotOffsetMm, $startY + ($r * $modMm) + $dotOffsetMm, $dotMm, $dotMm, $dotRadiusMm, $pageHPt, 'f');
            }
        }

        $eyes = [
            [$startX, $startY],
            [$startX + (($size - 7) * $modMm), $startY],
            [$startX, $startY + (($size - 7) * $modMm)],
        ];
        foreach ($eyes as [$ex, $ey]) {
            $out .= self::cmdFillRgb((string) $theme['qr_dark']);
            $out .= self::cmdRoundedRect($ex, $ey, 7.0 * $modMm, 7.0 * $modMm, 1.85 * $modMm, $pageHPt, 'f');
            $out .= self::cmdFillRgb('#FFFFFF');
            $out .= self::cmdRoundedRect($ex + $modMm, $ey + $modMm, 5.0 * $modMm, 5.0 * $modMm, 1.25 * $modMm, $pageHPt, 'f');
            $out .= self::cmdFillRgb((string) $theme['qr_eye']);
            $out .= self::cmdRoundedRect($ex + (2.0 * $modMm), $ey + (2.0 * $modMm), 3.0 * $modMm, 3.0 * $modMm, 0.9 * $modMm, $pageHPt, 'f');
        }

        if ($hasMedallion) {
            $cxMm = $xMm + ($boxMm / 2.0);
            $cyMm = $yMm + ($boxMm / 2.0);
            $rOuterMm = max(3.65, $size * 0.118) * $modMm;
            $rRingMm = $rOuterMm - (0.45 * $modMm);
            $rInnerMm = $rRingMm - (0.55 * $modMm);

            $out .= self::cmdFillRgb('#FFFFFF');
            $out .= self::cmdCircle($cxMm, $cyMm, $rOuterMm, $pageHPt, 'f');

            $out .= self::cmdFillRgb((string) $theme['accent_soft']);
            $out .= self::cmdStrokeRgb((string) $theme['outer_border']);
            $out .= sprintf("%.3f w\n", max(0.45, 0.36 * $modMm * self::MM));
            $out .= self::cmdCircle($cxMm, $cyMm, $rRingMm, $pageHPt, 'B');

            $out .= self::cmdFillRgb('#FFFFFF');
            $out .= self::cmdStrokeRgb((string) $theme['inner_border']);
            $out .= sprintf("%.3f w\n", max(0.25, 0.18 * $modMm * self::MM));
            $out .= self::cmdCircle($cxMm, $cyMm, $rInnerMm, $pageHPt, 'B');

            $displayInit = mb_strtoupper(trim($initials), 'UTF-8');
            $charCount = mb_strlen($displayInit, 'UTF-8');
            $radiusPt = $rInnerMm * self::MM;
            $fontPt = $radiusPt * ($charCount <= 1 ? 1.15 : ($charCount === 2 ? 0.95 : ($charCount === 3 ? 0.76 : 0.56)));
            $maxTextMm = $rInnerMm * 1.68;
            while ($fontPt > 4.0 && self::measureTextMm($displayInit, $monogramFont, $fontPt) > $maxTextMm) {
                $fontPt -= 0.35;
            }

            $baselineYMm = $cyMm + (($fontPt * 0.33) / self::MM);
            $out .= self::cmdFillRgb((string) $theme['title_color']);
            $out .= self::cmdCenteredText($displayInit, $cxMm, $baselineYMm, $monogramFont, $fontPt, $pageHPt);
        }

        return $out;
    }

    private static function renderCutLine(float $x1Mm, float $y1Mm, float $x2Mm, float $y2Mm, float $pageHPt): string
    {
        return "q\n0.78 0.82 0.87 RG\n0.45 w\n[4 4] 0 d\n" . self::cmdLine($x1Mm, $y1Mm, $x2Mm, $y2Mm, $pageHPt) . "Q\n";
    }

    private static function cmdFillRgb(string $hex): string
    {
        [$r, $g, $b] = self::hexToRgbFloats($hex);
        return sprintf("%.4f %.4f %.4f rg\n", $r, $g, $b);
    }

    private static function cmdStrokeRgb(string $hex): string
    {
        [$r, $g, $b] = self::hexToRgbFloats($hex);
        return sprintf("%.4f %.4f %.4f RG\n", $r, $g, $b);
    }

    /** @return array{0:float, 1:float, 2:float} */
    private static function hexToRgbFloats(string $hex): array
    {
        $h = ltrim(trim($hex), '#');
        if (strlen($h) === 3) {
            $h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
        }
        if (strlen($h) !== 6 || !ctype_xdigit($h)) {
            return [0.1, 0.1, 0.12];
        }
        return [
            hexdec(substr($h, 0, 2)) / 255.0,
            hexdec(substr($h, 2, 2)) / 255.0,
            hexdec(substr($h, 4, 2)) / 255.0,
        ];
    }

    private static function cmdLine(float $x1Mm, float $y1Mm, float $x2Mm, float $y2Mm, float $pageHPt): string
    {
        return sprintf(
            "%.3f %.3f m %.3f %.3f l S\n",
            $x1Mm * self::MM,
            $pageHPt - ($y1Mm * self::MM),
            $x2Mm * self::MM,
            $pageHPt - ($y2Mm * self::MM)
        );
    }

    private static function cmdRoundedRect(float $xMm, float $yMm, float $wMm, float $hMm, float $rMm, float $pageHPt, string $op): string
    {
        $x = $xMm * self::MM;
        $w = $wMm * self::MM;
        $h = $hMm * self::MM;
        $y = $pageHPt - (($yMm + $hMm) * self::MM);
        $r = min($rMm * self::MM, $w / 2.0, $h / 2.0);

        if ($r <= 0.05) {
            return sprintf("%.3f %.3f %.3f %.3f re %s\n", $x, $y, $w, $h, $op);
        }

        $k = $r * self::BEZIER_K;
        $xr = $x + $w;
        $yt = $y + $h;

        return sprintf(
            "%.3f %.3f m %.3f %.3f l %.3f %.3f %.3f %.3f %.3f %.3f c %.3f %.3f l %.3f %.3f %.3f %.3f %.3f %.3f c %.3f %.3f l %.3f %.3f %.3f %.3f %.3f %.3f c %.3f %.3f l %.3f %.3f %.3f %.3f %.3f %.3f c h %s\n",
            $x + $r, $y,
            $xr - $r, $y,
            $xr - $r + $k, $y, $xr, $y + $r - $k, $xr, $y + $r,
            $xr, $yt - $r,
            $xr, $yt - $r + $k, $xr - $r + $k, $yt, $xr - $r, $yt,
            $x + $r, $yt,
            $x + $r - $k, $yt, $x, $yt - $r + $k, $x, $yt - $r,
            $x, $y + $r,
            $x, $y + $r - $k, $x + $r - $k, $y, $x + $r, $y,
            $op
        );
    }

    private static function cmdCircle(float $cxMm, float $cyMm, float $rMm, float $pageHPt, string $op): string
    {
        $cx = $cxMm * self::MM;
        $cy = $pageHPt - ($cyMm * self::MM);
        $r = $rMm * self::MM;
        $k = $r * self::BEZIER_K;

        return sprintf(
            "%.3f %.3f m %.3f %.3f %.3f %.3f %.3f %.3f c %.3f %.3f %.3f %.3f %.3f %.3f c %.3f %.3f %.3f %.3f %.3f %.3f c %.3f %.3f %.3f %.3f %.3f %.3f c h %s\n",
            $cx, $cy + $r,
            $cx + $k, $cy + $r, $cx + $r, $cy + $k, $cx + $r, $cy,
            $cx + $r, $cy - $k, $cx + $k, $cy - $r, $cx, $cy - $r,
            $cx - $k, $cy - $r, $cx - $r, $cy - $k, $cx - $r, $cy,
            $cx - $r, $cy + $k, $cx - $k, $cy + $r, $cx, $cy + $r,
            $op
        );
    }

    private static function cmdDiamond(float $cxMm, float $cyMm, float $rxMm, float $ryMm, float $pageHPt, string $op): string
    {
        $cx = $cxMm * self::MM;
        $cy = $pageHPt - ($cyMm * self::MM);
        $rx = $rxMm * self::MM;
        $ry = $ryMm * self::MM;

        return sprintf(
            "%.3f %.3f m %.3f %.3f l %.3f %.3f l %.3f %.3f l h %s\n",
            $cx, $cy + $ry,
            $cx + $rx, $cy,
            $cx, $cy - $ry,
            $cx - $rx, $cy,
            $op
        );
    }

    private static function cmdCenteredText(string $utf8Text, float $cxMm, float $baselineYMm, string $fontRef, float $fontSizePt, float $pageHPt): string
    {
        $clean = self::sanitizePrintableText($utf8Text);
        if ($clean === '') {
            return '';
        }
        $widthMm = self::measureTextMm($clean, $fontRef, $fontSizePt);
        $xPt = ($cxMm - ($widthMm / 2.0)) * self::MM;
        $yPt = $pageHPt - ($baselineYMm * self::MM);
        $encoded = self::escapePdfLiteral(self::utf8ToWinAnsi($clean));

        return sprintf("BT /%s %.3f Tf %.3f %.3f Td (%s) Tj ET\n", $fontRef, $fontSizePt, $xPt, $yPt, $encoded);
    }

    /** @return array<int, string> */
    private static function wrapText(string $utf8Text, string $fontRef, float $fontSizePt, float $maxWidthMm, int $maxLines = 3): array
    {
        $clean = trim(self::sanitizePrintableText($utf8Text));
        if ($clean === '') {
            return [];
        }
        $words = preg_split('/\s+/u', $clean) ?: [$clean];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : ($current . ' ' . $word);
            if ($current === '' || self::measureTextMm($candidate, $fontRef, $fontSizePt) <= $maxWidthMm) {
                $current = $candidate;
            } else {
                $lines[] = $current;
                $current = $word;
                if (count($lines) >= $maxLines - 1) {
                    continue;
                }
            }
        }
        if ($current !== '') {
            if (count($lines) >= $maxLines) {
                $lines[$maxLines - 1] .= ' ' . $current;
            } else {
                $lines[] = $current;
            }
        }
        return array_slice($lines, 0, $maxLines);
    }

    private static function measureTextMm(string $utf8Text, string $fontRef, float $fontSizePt): float
    {
        $clean = self::sanitizePrintableText($utf8Text);
        if ($clean === '') {
            return 0.0;
        }
        $isSerif = in_array($fontRef, ['F1', 'F2', 'F3', 'F4'], true);
        $isBold = in_array($fontRef, ['F2', 'F4', 'F6'], true);
        $len = mb_strlen($clean, 'UTF-8');
        $totalUnits = 0;
        for ($i = 0; $i < $len; $i++) {
            $totalUnits += self::charWidthUnits(mb_substr($clean, $i, 1, 'UTF-8'), $isSerif, $isBold);
        }
        return (($totalUnits / 1000.0) * $fontSizePt) / self::MM;
    }

    private static function charWidthUnits(string $ch, bool $serif, bool $bold): int
    {
        $baseMap = [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c',
            'Á' => 'A', 'À' => 'A', 'Ä' => 'A', 'Â' => 'A', 'Ã' => 'A',
            'É' => 'E', 'È' => 'E', 'Ë' => 'E', 'Ê' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Ï' => 'I', 'Î' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Ö' => 'O', 'Ô' => 'O', 'Õ' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Ü' => 'U', 'Û' => 'U',
            'Ñ' => 'N', 'Ç' => 'C',
            '¡' => '!', '¿' => '?', '·' => '.', '•' => 'o',
            '«' => '"', '»' => '"', '“' => '"', '”' => '"',
            '‘' => "'", '’' => "'", '–' => '-', '—' => 'M',
        ];
        $c = $baseMap[$ch] ?? $ch;

        if ($serif) {
            $table = $bold ? [
                ' ' => 250, '!' => 333, '"' => 555, '#' => 500, '$' => 500, '%' => 1000, '&' => 833, "'" => 278,
                '(' => 333, ')' => 333, '*' => 500, '+' => 570, ',' => 250, '-' => 333, '.' => 250, '/' => 278,
                '0' => 500, '1' => 500, '2' => 500, '3' => 500, '4' => 500, '5' => 500, '6' => 500, '7' => 500,
                '8' => 500, '9' => 500, ':' => 333, ';' => 333, '=' => 570, '?' => 500, '@' => 930,
                'A' => 722, 'B' => 667, 'C' => 722, 'D' => 722, 'E' => 667, 'F' => 611, 'G' => 778, 'H' => 778,
                'I' => 389, 'J' => 500, 'K' => 778, 'L' => 667, 'M' => 944, 'N' => 722, 'O' => 778, 'P' => 611,
                'Q' => 778, 'R' => 722, 'S' => 556, 'T' => 667, 'U' => 722, 'V' => 722, 'W' => 1000, 'X' => 722,
                'Y' => 722, 'Z' => 667,
                'a' => 500, 'b' => 556, 'c' => 444, 'd' => 556, 'e' => 444, 'f' => 333, 'g' => 500, 'h' => 556,
                'i' => 278, 'j' => 333, 'k' => 556, 'l' => 278, 'm' => 833, 'n' => 556, 'o' => 500, 'p' => 556,
                'q' => 556, 'r' => 444, 's' => 389, 't' => 333, 'u' => 556, 'v' => 500, 'w' => 722, 'x' => 500,
                'y' => 500, 'z' => 444,
            ] : [
                ' ' => 250, '!' => 333, '"' => 408, '#' => 500, '$' => 500, '%' => 833, '&' => 778, "'" => 180,
                '(' => 333, ')' => 333, '*' => 500, '+' => 564, ',' => 250, '-' => 333, '.' => 250, '/' => 278,
                '0' => 500, '1' => 500, '2' => 500, '3' => 500, '4' => 500, '5' => 500, '6' => 500, '7' => 500,
                '8' => 500, '9' => 500, ':' => 278, ';' => 278, '=' => 564, '?' => 444, '@' => 921,
                'A' => 722, 'B' => 667, 'C' => 667, 'D' => 722, 'E' => 611, 'F' => 556, 'G' => 722, 'H' => 722,
                'I' => 333, 'J' => 389, 'K' => 722, 'L' => 611, 'M' => 889, 'N' => 722, 'O' => 722, 'P' => 556,
                'Q' => 722, 'R' => 667, 'S' => 556, 'T' => 611, 'U' => 722, 'V' => 722, 'W' => 944, 'X' => 722,
                'Y' => 722, 'Z' => 611,
                'a' => 444, 'b' => 500, 'c' => 444, 'd' => 500, 'e' => 444, 'f' => 333, 'g' => 500, 'h' => 500,
                'i' => 278, 'j' => 278, 'k' => 500, 'l' => 278, 'm' => 778, 'n' => 500, 'o' => 500, 'p' => 500,
                'q' => 500, 'r' => 333, 's' => 389, 't' => 278, 'u' => 500, 'v' => 500, 'w' => 722, 'x' => 500,
                'y' => 500, 'z' => 444,
            ];
            return $table[$c] ?? 520;
        }

        $table = $bold ? [
            ' ' => 278, '!' => 333, '"' => 474, '#' => 556, '$' => 556, '%' => 889, '&' => 722, "'" => 238,
            '(' => 333, ')' => 333, '*' => 389, '+' => 584, ',' => 278, '-' => 333, '.' => 278, '/' => 278,
            '0' => 556, '1' => 556, '2' => 556, '3' => 556, '4' => 556, '5' => 556, '6' => 556, '7' => 556,
            '8' => 556, '9' => 556, ':' => 333, ';' => 333, '=' => 584, '?' => 611, '@' => 975,
            'A' => 722, 'B' => 722, 'C' => 722, 'D' => 722, 'E' => 667, 'F' => 611, 'G' => 778, 'H' => 722,
            'I' => 278, 'J' => 556, 'K' => 722, 'L' => 611, 'M' => 833, 'N' => 722, 'O' => 778, 'P' => 667,
            'Q' => 778, 'R' => 722, 'S' => 667, 'T' => 611, 'U' => 722, 'V' => 667, 'W' => 944, 'X' => 667,
            'Y' => 667, 'Z' => 611,
            'a' => 556, 'b' => 611, 'c' => 556, 'd' => 611, 'e' => 556, 'f' => 333, 'g' => 611, 'h' => 611,
            'i' => 278, 'j' => 278, 'k' => 556, 'l' => 278, 'm' => 889, 'n' => 611, 'o' => 611, 'p' => 611,
            'q' => 611, 'r' => 389, 's' => 556, 't' => 333, 'u' => 611, 'v' => 556, 'w' => 778, 'x' => 556,
            'y' => 556, 'z' => 500,
        ] : [
            ' ' => 278, '!' => 278, '"' => 355, '#' => 556, '$' => 556, '%' => 889, '&' => 667, "'" => 191,
            '(' => 333, ')' => 333, '*' => 389, '+' => 584, ',' => 278, '-' => 333, '.' => 278, '/' => 278,
            '0' => 556, '1' => 556, '2' => 556, '3' => 556, '4' => 556, '5' => 556, '6' => 556, '7' => 556,
            '8' => 556, '9' => 556, ':' => 278, ';' => 278, '=' => 584, '?' => 556, '@' => 1015,
            'A' => 667, 'B' => 667, 'C' => 722, 'D' => 722, 'E' => 667, 'F' => 611, 'G' => 778, 'H' => 722,
            'I' => 278, 'J' => 500, 'K' => 667, 'L' => 556, 'M' => 833, 'N' => 722, 'O' => 778, 'P' => 667,
            'Q' => 778, 'R' => 722, 'S' => 667, 'T' => 611, 'U' => 722, 'V' => 667, 'W' => 944, 'X' => 667,
            'Y' => 667, 'Z' => 611,
            'a' => 556, 'b' => 556, 'c' => 500, 'd' => 556, 'e' => 556, 'f' => 278, 'g' => 556, 'h' => 556,
            'i' => 222, 'j' => 222, 'k' => 500, 'l' => 222, 'm' => 833, 'n' => 556, 'o' => 556, 'p' => 556,
            'q' => 556, 'r' => 333, 's' => 500, 't' => 278, 'u' => 556, 'v' => 500, 'w' => 722, 'x' => 500,
            'y' => 500, 'z' => 500,
        ];
        return $table[$c] ?? 550;
    }

    private static function sanitizePrintableText(string $utf8): string
    {
        $clean = (string) preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $utf8);
        $clean = str_replace(["\r", "\n", "\t"], ' ', $clean);
        return trim((string) preg_replace('/\s{2,}/u', ' ', $clean));
    }

    private static function utf8ToWinAnsi(string $utf8): string
    {
        static $cp1252Extras = [
            0x20AC => "\x80", 0x201A => "\x82", 0x0192 => "\x83", 0x201E => "\x84",
            0x2026 => "\x85", 0x2020 => "\x86", 0x2021 => "\x87", 0x02C6 => "\x88",
            0x2030 => "\x89", 0x0160 => "\x8A", 0x2039 => "\x8B", 0x0152 => "\x8C",
            0x017D => "\x8E", 0x2018 => "\x91", 0x2019 => "\x92", 0x201C => "\x93",
            0x201D => "\x94", 0x2022 => "\x95", 0x2013 => "\x96", 0x2014 => "\x97",
            0x02DC => "\x98", 0x2122 => "\x99", 0x0161 => "\x9A", 0x203A => "\x9B",
            0x0153 => "\x9C", 0x017E => "\x9E", 0x0178 => "\x9F",
        ];

        $out = '';
        $len = mb_strlen($utf8, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $cp = mb_ord(mb_substr($utf8, $i, 1, 'UTF-8'), 'UTF-8');
            if ($cp === false) {
                continue;
            }
            if (($cp >= 0x20 && $cp <= 0x7E) || ($cp >= 0xA0 && $cp <= 0xFF)) {
                $out .= chr($cp);
            } elseif (isset($cp1252Extras[$cp])) {
                $out .= $cp1252Extras[$cp];
            }
        }
        return $out;
    }

    private static function escapePdfLiteral(string $winAnsi): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $winAnsi);
    }

    private static function assemblePdfDocument(string $contentStream, float $pageWPt, float $pageHPt, string $docTitle): string
    {
        $compressed = function_exists('gzcompress') ? gzcompress($contentStream, 6) : false;
        if (is_string($compressed) && $compressed !== '') {
            $streamBytes = $compressed;
            $filterDict = ' /Filter /FlateDecode';
        } else {
            $streamBytes = $contentStream;
            $filterDict = '';
        }

        $safeTitle = self::escapePdfLiteral(self::utf8ToWinAnsi('Cartel QR - ' . $docTitle));

        $objects = [
            1  => '<< /Type /Catalog /Pages 2 0 R >>',
            2  => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3  => sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.3f %.3f] /Resources << /Font << ' .
                '/F1 5 0 R /F2 6 0 R /F3 7 0 R /F4 8 0 R /F5 9 0 R /F6 10 0 R /F7 11 0 R ' .
                '>> >> /Contents 4 0 R >>',
                $pageWPt,
                $pageHPt
            ),
            4  => sprintf("<< /Length %d%s >>\nstream\n%s\nendstream", strlen($streamBytes), $filterDict, $streamBytes),
            5  => '<< /Type /Font /Subtype /Type1 /BaseFont /Times-Roman /Encoding /WinAnsiEncoding >>',
            6  => '<< /Type /Font /Subtype /Type1 /BaseFont /Times-Bold /Encoding /WinAnsiEncoding >>',
            7  => '<< /Type /Font /Subtype /Type1 /BaseFont /Times-Italic /Encoding /WinAnsiEncoding >>',
            8  => '<< /Type /Font /Subtype /Type1 /BaseFont /Times-BoldItalic /Encoding /WinAnsiEncoding >>',
            9  => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            10 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            11 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Oblique /Encoding /WinAnsiEncoding >>',
            12 => sprintf('<< /Title (%s) /Creator (EventosMusicales) /Producer (EventosMusicales Vector PDF) >>', $safeTitle),
        ];

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $totalObjs = count($objects) + 1;
        $pdf .= "xref\n0 " . $totalObjs . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($id = 1; $id <= count($objects); $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }

        $pdf .= "trailer\n<< /Size " . $totalObjs . " /Root 1 0 R /Info 12 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    private static function titleCaseName(string $name): string
    {
        $words = preg_split('/\s+/u', trim($name)) ?: [$name];
        $particles = ['de', 'del', 'la', 'las', 'los', 'van', 'von'];
        $res = [];
        foreach ($words as $idx => $w) {
            $lower = mb_strtolower($w, 'UTF-8');
            if ($idx > 0 && in_array($lower, $particles, true)) {
                $res[] = $lower;
            } else {
                $res[] = mb_strtoupper(mb_substr($lower, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($lower, 1, null, 'UTF-8');
            }
        }
        return implode(' ', $res);
    }

    private static function firstLetterUpper(string $str): string
    {
        if (preg_match('/(\p{L})/u', $str, $m)) {
            return mb_strtoupper($m[1], 'UTF-8');
        }
        return '';
    }
}
