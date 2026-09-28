<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->string('dmx_mode')->nullable()->after('is_dmx'); // Número de canales
            $table->renameColumn('dmx_channel', 'dmx_address'); // Canal DMX
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->renameColumn('dmx_address', 'dmx_channel');
            $table->dropColumn('dmx_mode');
        });
    }
};
