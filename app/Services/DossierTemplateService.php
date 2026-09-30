<?php

namespace App\Services;

class DossierTemplateService
{
    public static function getDefaults(): array
    {
        return [
            'dossier_cover_title' => "Propuesta para\ntu evento",
            'dossier_page2_subtitle' => 'QUÉ LLEVAMOS',
            'dossier_page2_title' => 'DJ, sonido e iluminación propios',
            'dossier_intro_text' => 'Nos encargamos de todo: llevamos el equipo, lo montamos y lo probamos antes de que lleguen los invitados, y pinchamos toda la tarde leyendo el ambiente para que la pista no se vacíe. Tú solo te preocupas de disfrutar.',
            
            // Bloque 1: Sonido
            'dossier_block1_title' => 'Sonido Profesional de Gran Potencia y Claridad',
            'dossier_block1_desc' => 'Sistemas autoamplificados de alta definición con refuerzo de subgraves para interiores y exteriores.',
            
            // Bloque 2: Iluminación
            'dossier_block2_title' => 'Iluminación Dinámica de Pista',
            'dossier_block2_desc' => 'Cabezas móviles, focos LED y efectos de ambientación con máquina de humo.',
            
            // Bloque 3: Sesión DJ
            'dossier_block3_title' => 'Sesión DJ en Directo',
            'dossier_block3_desc' => 'Lectura continua de la pista, animación cercana y coordinación en directo.',
            
            // Cómo trabajamos
            'dossier_work_title' => 'Cómo trabajamos',
            'dossier_work_item1' => 'Montaje y prueba de sonido antes del inicio del evento.',
            'dossier_work_item2' => 'Música a vuestro gusto: antes del evento hablamos para conocer qué os gusta y qué no.',
            'dossier_work_item3' => 'Desmontaje al terminar, sin que tengáis que preocuparos de nada.',

            // Página 3: Tarjetas informativas
            'dossier_extra_hours_title' => 'Horas extra',
            'dossier_extra_hours_desc' => 'Si la fiesta se alarga, se pueden añadir horas extra in situ a la tarifa establecida. Sin sorpresas.',
            'dossier_music_custom_title' => 'Personalización total',
            'dossier_music_custom_desc' => 'Lista de canciones imprescindibles y canciones prohibidas a través de vuestro panel exclusivo.',
        ];
    }

    public static function presets(): array
    {
        return [
            'bodas' => [
                'name' => '💍 Bodas y Grandes Celebraciones',
                'description' => 'Enfoque elegante y emotivo para bodas, cóctel, banquete y barra libre.',
                'values' => [
                    'dossier_cover_title' => "Propuesta para\nvuestra boda",
                    'dossier_page2_subtitle' => 'QUÉ INCLUYE EL SERVICIO',
                    'dossier_page2_title' => 'Música, sonido e iluminación premium',
                    'dossier_intro_text' => 'Nos encargamos de que la música de vuestro gran día sea inolvidable: llevamos equipo de alta gama, lo dejamos todo montado con total discreción y pinchamos leyendo la pista en cada momento para que vosotros y vuestros invitados lo deis todo.',
                    'dossier_block1_title' => 'Sonido Profesional de Alta Fidelidad',
                    'dossier_block1_desc' => 'Sistemas autoamplificados con refuerzo de subgraves para ceremonia, cóctel, banquete y barra libre.',
                    'dossier_block2_title' => 'Iluminación y Efectos Espectaculares',
                    'dossier_block2_desc' => 'Robótica móvil, iluminación LED ambiental, máquina de humo y opciones de fuego frío.',
                    'dossier_block3_title' => 'Sesión DJ y Animación Adaptada',
                    'dossier_block3_desc' => 'Mezclas en directo, control de momentos clave (entrada, ramo, corte de tarta) y pista activa hasta el final.',
                    'dossier_work_title' => 'Cómo trabajamos en vuestra boda',
                    'dossier_work_item1' => 'Montaje previo y prueba de sonido antes de la llegada de los novios e invitados.',
                    'dossier_work_item2' => 'Reunión de coordinación y selección de temas favoritos, momentos especiales y canciones prohibidas.',
                    'dossier_work_item3' => 'Desmontaje limpio al concluir la fiesta para que vosotros solo os dediquéis a disfrutar.',
                    'dossier_extra_hours_title' => 'Horas extra en barra libre',
                    'dossier_extra_hours_desc' => 'Si la fiesta no para, se pueden ampliar horas adicionales directamente durante el evento.',
                    'dossier_music_custom_title' => 'Portal interactivo para novios',
                    'dossier_music_custom_desc' => 'Panel exclusivo donde indicar vuestras canciones preferidas y vetar las que no queréis que suenen.',
                ]
            ],
            'fiestas_empresa' => [
                'name' => '🎉 Fiestas Privadas, Cumpleaños y Empresa',
                'description' => 'Formato dinámico y versátil para eventos corporativos, galas, aniversarios y fiestas.',
                'values' => [
                    'dossier_cover_title' => "Propuesta para\ntu evento",
                    'dossier_page2_subtitle' => 'EQUIPAMIENTO Y PRODUCCIÓN',
                    'dossier_page2_title' => 'DJ, Sonorización e Iluminación Profesional',
                    'dossier_intro_text' => 'Ofrecemos una solución técnica y musical completa para vuestro evento o fiesta corporativa. Nos ocupamos del transporte, montaje, microfonía inalámbrica para presentaciones y una sesión musical adaptada a vuestro público.',
                    'dossier_block1_title' => 'Sonorización y Microfonía Profesional',
                    'dossier_block1_desc' => 'Equipos de audio de alta potencia, micrófonos inalámbricos de largo alcance y sonido nítido.',
                    'dossier_block2_title' => 'Show de Luces y Ambientación',
                    'dossier_block2_desc' => 'Cabezas móviles, barras LED decorativas y efectos dinámicos sincronizados con el ritmo.',
                    'dossier_block3_title' => 'Sesión DJ Versátil en Directo',
                    'dossier_block3_desc' => 'Repertorio desde hits comerciales y pop-rock hasta remember, reggaeton o música electrónica según la ocasión.',
                    'dossier_work_title' => 'Nuestro proceso de trabajo',
                    'dossier_work_item1' => 'Llegada anticipada, instalación técnica y verificación acústica del recinto.',
                    'dossier_work_item2' => 'Planificación musical previa y atención a peticiones de los asistentes en directo.',
                    'dossier_work_item3' => 'Desmontaje rápido y seguro una vez finalizada la celebración.',
                    'dossier_extra_hours_title' => 'Ampliación horaria',
                    'dossier_extra_hours_desc' => 'Posibilidad de prorrogar la actuación según la energía del evento y disponibilidad de la sala.',
                    'dossier_music_custom_title' => 'Peticiones y lista musical',
                    'dossier_music_custom_desc' => 'Plataforma para configurar el estilo musical, momentos protocolarios y canciones deseadas.',
                ]
            ],
            'discomovil_javnx' => [
                'name' => '🎧 Discomóvil, Clubbing & Eventos JAVNX',
                'description' => 'Producción de alto impacto con enfoque discoteca, festivales y fiestas multitudinarias.',
                'values' => [
                    'dossier_cover_title' => "Dossier Técnico\n& Show DJ",
                    'dossier_page2_subtitle' => 'PRODUCCIÓN TÉCNICA Y ESCENARIO',
                    'dossier_page2_title' => 'Cabina DJ, Sonido Contundente y Show Visual',
                    'dossier_intro_text' => 'Experiencia club y festival en directo: cabina profesional de última generación, máxima potencia acústica con graves arrolladores y un show de iluminación reactiva diseñado para crear una atmósfera inolvidable.',
                    'dossier_block1_title' => 'Presión Sonora y Audio Alta Gama',
                    'dossier_block1_desc' => 'PA profesional de gran pegada y subgraves contundentes para recintos medianos y grandes.',
                    'dossier_block2_title' => 'Iluminación Clubbing y Efectos',
                    'dossier_block2_desc' => 'Cabezas móviles BEAM/SPOT, láseres, cegadoras, humo denso y efectos visuales sincronizados.',
                    'dossier_block3_title' => 'DJ Set Energético y Directo',
                    'dossier_block3_desc' => 'Sesiones non-stop con mashups exclusivos, transiciones rápidas y máxima conexión con el público.',
                    'dossier_work_title' => 'Logística y Show',
                    'dossier_work_item1' => 'Montaje técnico estructurado y ajuste de procesadores de sonido antes de apertura.',
                    'dossier_work_item2' => 'Sesión personalizada al estilo del evento (Comercial, EDM, Urban, Remember o Tech-House).',
                    'dossier_work_item3' => 'Desmontaje profesional con personal especializado.',
                    'dossier_extra_hours_title' => 'Horas adicionales',
                    'dossier_extra_hours_desc' => 'Ampliación disponible in situ acordada con la organización.',
                    'dossier_music_custom_title' => 'Dirección musical',
                    'dossier_music_custom_desc' => 'Coordinación de estilos y playlist clave a través del sistema web.',
                ]
            ],
        ];
    }
}
