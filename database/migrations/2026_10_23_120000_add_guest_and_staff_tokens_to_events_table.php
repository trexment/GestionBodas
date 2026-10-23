<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Separa el token único del evento en tres niveles de acceso:
     *  - token        → cliente (cuestionario, contrato, propuesta, PDFs)
     *  - guest_token  → invitados (QR de peticiones en vivo)
     *  - staff_token  → personal (Modo Cabina DJ /live)
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'guest_token')) {
                $table->string('guest_token', 64)->nullable()->unique()->after('token');
            }
            if (!Schema::hasColumn('events', 'staff_token')) {
                $table->string('staff_token', 64)->nullable()->unique()->after('guest_token');
            }
        });

        DB::table('events')->select('id')->orderBy('id')->chunkById(200, function ($events) {
            foreach ($events as $event) {
                DB::table('events')->where('id', $event->id)->update([
                    'guest_token' => Str::random(32),
                    'staff_token' => Str::random(40),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropUnique(['guest_token']);
            $table->dropUnique(['staff_token']);
            $table->dropColumn(['guest_token', 'staff_token']);
        });
    }
};
