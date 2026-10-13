<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Modificar columna status de ENUM a VARCHAR(50) para soportar 'no_response', 'rejected' sin error de truncado
        try {
            DB::statement("ALTER TABLE `events` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'draft'");
        } catch (\Throwable $e) {
            // Ignorar si el motor es SQLite o si ya es varchar
        }

        // 2. Añadir columna no_response_at para tracking del recordatorio de seguimiento
        if (Schema::hasTable('events') && !Schema::hasColumn('events', 'no_response_at')) {
            Schema::table('events', function (Blueprint $table) {
                $table->timestamp('no_response_at')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('events') && Schema::hasColumn('events', 'no_response_at')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('no_response_at');
            });
        }

        try {
            DB::statement("ALTER TABLE `events` MODIFY COLUMN `status` ENUM('draft', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'draft'");
        } catch (\Throwable $e) {
        }
    }
};
