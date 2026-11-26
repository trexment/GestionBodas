<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade la columna settlement_custom_split a la tabla events para permitir repartos personalizados de cada evento.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'settlement_custom_split')) {
                $table->text('settlement_custom_split')->nullable()->after('settlement_notes');
            }
        });

        // Inicializar reparto a medida para Vidalia y Omar si existe
        try {
            $event = \Illuminate\Support\Facades\DB::table('events')
                ->where('name', 'LIKE', '%Vidalia%')
                ->first();
            if ($event && empty($event->settlement_custom_split)) {
                \Illuminate\Support\Facades\DB::table('events')
                    ->where('id', $event->id)
                    ->update([
                        'settlement_custom_split' => json_encode([
                            'mode' => 'custom_amounts',
                            'partners' => [
                                [
                                    'name' => 'Fran',
                                    'amount' => 600.00,
                                    'percentage' => 60.0,
                                    'role' => 'Fotografía',
                                    'is_settled' => true,
                                ],
                                [
                                    'name' => 'Miguel',
                                    'amount' => 400.00,
                                    'percentage' => 40.0,
                                    'role' => 'DJ',
                                    'is_settled' => true,
                                ],
                            ],
                            'notes' => 'Miguel cobró 400 € por DJ y Fran 600 € por Fotografía',
                            'updated_at' => now()->toIso8601String(),
                        ], JSON_UNESCAPED_UNICODE),
                    ]);
            }
        } catch (\Throwable $e) {
            // Tolerar
        }
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'settlement_custom_split')) {
                $table->dropColumn('settlement_custom_split');
            }
        });
    }
};
