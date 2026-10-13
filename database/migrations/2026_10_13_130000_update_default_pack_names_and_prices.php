<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $updates = [
            'pack_basic_name' => [
                'from' => ['Pack Básico', 'Pack Basico'],
                'to' => 'Pack Esencial'
            ],
            'pack_basic_price' => [
                'from' => ['400', '400.00', '400,00'],
                'to' => '600'
            ],
            'pack_basic_desc' => [
                'from' => ['4 horas de sesión DJ. Sonido profesional e iluminación de pista. Microfonía inalámbrica.'],
                'to' => '4 horas de sesión DJ. Sonido profesional estéreo (2 altavoces de alta definición) + iluminación rítmica básica de pista (2 focos/efectos LED). Microfonía inalámbrica para discursos.'
            ],
            'pack_medium_name' => [
                'from' => ['Pack Medio', 'Pack Medio ⭐'],
                'to' => 'Pack Signature'
            ],
            'pack_medium_price' => [
                'from' => ['700', '700.00', '700,00'],
                'to' => '950'
            ],
            'pack_medium_desc' => [
                'from' => ['5 horas de sesión DJ. Sonido de alta gama, iluminación avanzada robotizada, máquina de humo y microfonía.'],
                'to' => '5 horas de sesión DJ. Sonido reforzado con subgraves (impacto y pegada en pista). Cabina DJ retroiluminada + puente/barras de iluminación LED sincronizada y ambientación de pista. Microfonía inalámbrica.'
            ],
            'pack_premium_name' => [
                'from' => ['Pack Premium'],
                'to' => 'Pack Deluxe Show'
            ],
            'pack_premium_price' => [
                'from' => ['1000', '1000.00', '1000,00'],
                'to' => '1350'
            ],
            'pack_premium_desc' => [
                'from' => ['6 horas de sesión DJ o barra libre completa. Gran despliegue audiovisual, iluminación espectacular, efectos especiales de humo/fuego frío.'],
                'to' => 'Cobertura completa (hasta 6h). Montaje de gran formato: sonido multizona/subgraves potentes + show de iluminación avanzada sincronizada. Incluye efecto especial de máquina de humo frío / chispas frías para momentos clave (apertura de baile).'
            ],
        ];

        foreach ($updates as $key => $config) {
            $current = DB::table('settings')->where('key', $key)->first();
            if ($current) {
                if (in_array(trim($current->value), $config['from'], true)) {
                    DB::table('settings')->where('key', $key)->update([
                        'value' => $config['to'],
                        'updated_at' => now(),
                    ]);
                }
            } else {
                DB::table('settings')->insert([
                    'key' => $key,
                    'value' => $config['to'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe no-op to protect user customizations
    }
};
