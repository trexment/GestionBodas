<?php

namespace App\Services;

class PostalCodeService
{
    /**
     * Map of 2-digit province prefixes to province names in Spain.
     */
    public static $provinces = [
        '01' => 'Álava',
        '02' => 'Albacete',
        '03' => 'Alicante',
        '04' => 'Almería',
        '05' => 'Ávila',
        '06' => 'Badajoz',
        '07' => 'Islas Baleares',
        '08' => 'Barcelona',
        '09' => 'Burgos',
        '10' => 'Cáceres',
        '11' => 'Cádiz',
        '12' => 'Castellón',
        '13' => 'Ciudad Real',
        '14' => 'Córdoba',
        '15' => 'A Coruña',
        '16' => 'Cuenca',
        '17' => 'Girona',
        '18' => 'Granada',
        '19' => 'Guadalajara',
        '20' => 'Guipúzcoa',
        '21' => 'Huelva',
        '22' => 'Huesca',
        '23' => 'Jaén',
        '24' => 'León',
        '25' => 'Lleida',
        '26' => 'La Rioja',
        '27' => 'Lugo',
        '28' => 'Madrid',
        '29' => 'Málaga',
        '30' => 'Murcia',
        '31' => 'Navarra',
        '32' => 'Ourense',
        '33' => 'Asturias',
        '34' => 'Palencia',
        '35' => 'Las Palmas',
        '36' => 'Pontevedra',
        '37' => 'Salamanca',
        '38' => 'Santa Cruz de Tenerife',
        '39' => 'Cantabria',
        '40' => 'Segovia',
        '41' => 'Sevilla',
        '42' => 'Soria',
        '43' => 'Tarragona',
        '44' => 'Teruel',
        '45' => 'Toledo',
        '46' => 'Valencia',
        '47' => 'Valladolid',
        '48' => 'Vizcaya',
        '49' => 'Zamora',
        '50' => 'Zaragoza',
        '51' => 'Ceuta',
        '52' => 'Melilla',
    ];

    /**
     * Map of common postal codes to town/city names.
     */
    public static $knownTowns = [
        // La Rioja
        '26370' => 'Navarrete',
        '26001' => 'Logroño',
        '26002' => 'Logroño',
        '26003' => 'Logroño',
        '26004' => 'Logroño',
        '26005' => 'Logroño',
        '26006' => 'Logroño',
        '26007' => 'Logroño',
        '26008' => 'Logroño',
        '26009' => 'Logroño',
        '26140' => 'Lardero',
        '26141' => 'Albelda de Iregua',
        '26142' => 'Villamediana de Iregua',
        '26143' => 'Entrena',
        '26144' => 'Alberite',
        '26147' => 'Sorzano',
        '26300' => 'Nájera',
        '26315' => 'Alesón',
        '26340' => 'San Asensio',
        '26350' => 'Cenicero',
        '26360' => 'Fuenmayor',
        '26200' => 'Haro',
        '26250' => 'Santo Domingo de la Calzada',
        '26500' => 'Calahorra',
        '26540' => 'Alfaro',
        '26580' => 'Arnedo',
        '26550' => 'Rincón de Soto',
        '26560' => 'Autol',
        '26584' => 'Pradejón',
        // Navarra & surroundings
        '31001' => 'Pamplona',
        '31200' => 'Estella-Lizarra',
        '31230' => 'Viana',
        '31500' => 'Tudela',
        '31260' => 'Lerín',
        '31261' => 'Andosilla',
        '31262' => 'San Adrián',
        // Álava
        '01001' => 'Vitoria-Gasteiz',
        '01300' => 'Laguardia',
        '01320' => 'Oyón-Oion',
        '01330' => 'Labastida',
        '01340' => 'Elciego',
        // Capitals
        '28001' => 'Madrid',
        '08001' => 'Barcelona',
        '41001' => 'Sevilla',
        '46001' => 'Valencia',
        '50001' => 'Zaragoza',
        '29001' => 'Málaga',
        '48001' => 'Bilbao',
        '20001' => 'Donostia-San Sebastián',
        '09001' => 'Burgos',
        '42001' => 'Soria',
    ];

    public static function lookup($postalCode): array
    {
        $code = trim((string)$postalCode);
        if (strlen($code) < 2) {
            return ['city' => '', 'province' => ''];
        }

        $code5 = str_pad($code, 5, '0', STR_PAD_LEFT);
        $prefix = substr($code5, 0, 2);

        $province = self::$provinces[$prefix] ?? '';
        $city = self::$knownTowns[$code5] ?? ($province ? $province : '');

        return [
            'postal_code' => $code5,
            'city' => $city,
            'province' => $province,
        ];
    }
}
