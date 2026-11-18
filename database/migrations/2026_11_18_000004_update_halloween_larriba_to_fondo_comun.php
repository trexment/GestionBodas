<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Actualiza los cobros de los eventos 'Fiesta Halloween La Cava' y 'Larriba'
     * para que su receptor sea 'Cuenta Conjunta / Fondo Común' y no se imputen a Fran personalmente.
     */
    public function up(): void
    {
        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'collected_by')) {
            DB::statement("
                UPDATE invoices i
                JOIN events e ON i.event_id = e.id
                SET i.collected_by = 'Cuenta Conjunta / Fondo Común'
                WHERE (e.name LIKE '%Halloween%' OR e.name LIKE '%Larriba%')
            ");
        }

        if (Schema::hasTable('events') && Schema::hasColumn('events', 'deposit_collected_by')) {
            DB::statement("
                UPDATE events
                SET deposit_collected_by = 'Cuenta Conjunta / Fondo Común'
                WHERE (name LIKE '%Halloween%' OR name LIKE '%Larriba%')
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
