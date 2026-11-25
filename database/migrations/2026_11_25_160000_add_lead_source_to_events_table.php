<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Añade la columna lead_source a la tabla events para registrar el canal/procedencia del lead y realiza un backfill de los existentes.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'lead_source')) {
                $table->string('lead_source', 50)->nullable()->after('event_type')->index();
            }
        });

        // Backfill de notas para eventos existentes
        try {
            DB::table('events')
                ->whereNull('lead_source')
                ->where(function ($q) {
                    $q->where('notes', 'LIKE', '%bodas.net%')
                      ->orWhere('notes', 'LIKE', '%Bodas.net%');
                })
                ->update(['lead_source' => 'bodas_net']);

            DB::table('events')
                ->whereNull('lead_source')
                ->where(function ($q) {
                    $q->where('notes', 'LIKE', '%madrigal%')
                      ->orWhere('notes', 'LIKE', '%Madrigal%');
                })
                ->update(['lead_source' => 'madrigal']);

            DB::table('events')
                ->whereNull('lead_source')
                ->where(function ($q) {
                    $q->where('notes', 'LIKE', '%VÍA WEB%')
                      ->orWhere('notes', 'LIKE', '%VIA WEB%')
                      ->orWhere('notes', 'LIKE', '%Formulario Web%')
                      ->orWhere('notes', 'LIKE', '%formulario web%');
                })
                ->update(['lead_source' => 'web']);

            DB::table('events')
                ->whereNull('lead_source')
                ->where(function ($q) {
                    $q->where('notes', 'LIKE', '%feria%')
                      ->orWhere('notes', 'LIKE', '%Feria%')
                      ->orWhere('notes', 'LIKE', '%Bridal Market%');
                })
                ->update(['lead_source' => 'feria']);

            DB::table('events')
                ->whereNull('lead_source')
                ->where(function ($q) {
                    $q->where('notes', 'LIKE', '%VÍA EMAIL%')
                      ->orWhere('notes', 'LIKE', '%VIA EMAIL%');
                })
                ->update(['lead_source' => 'email']);
        } catch (\Throwable $e) {
            // Registro silencioso o tolerancia a fallo
        }
    }

    /**
     * Revierte la adición de la columna.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'lead_source')) {
                $table->dropColumn('lead_source');
            }
        });
    }
};
