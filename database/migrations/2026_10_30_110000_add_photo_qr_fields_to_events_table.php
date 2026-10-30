<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade campos opcionales de vinculación con el servicio externo FotosEventosQR (vía HTTP API).
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'photo_qr_slug')) {
                $table->string('photo_qr_slug', 80)->nullable()->after('staff_token');
            }
            if (!Schema::hasColumn('events', 'photo_qr_url')) {
                $table->string('photo_qr_url', 500)->nullable()->after('photo_qr_slug');
            }
            if (!Schema::hasColumn('events', 'photo_qr_drive_url')) {
                $table->string('photo_qr_drive_url', 500)->nullable()->after('photo_qr_url');
            }
            if (!Schema::hasColumn('events', 'photo_qr_poster_url')) {
                $table->string('photo_qr_poster_url', 500)->nullable()->after('photo_qr_drive_url');
            }
            if (!Schema::hasColumn('events', 'photo_qr_created_at')) {
                $table->timestamp('photo_qr_created_at')->nullable()->after('photo_qr_poster_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $cols = [];
            foreach (['photo_qr_slug', 'photo_qr_url', 'photo_qr_drive_url', 'photo_qr_poster_url', 'photo_qr_created_at'] as $col) {
                if (Schema::hasColumn('events', $col)) {
                    $cols[] = $col;
                }
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
