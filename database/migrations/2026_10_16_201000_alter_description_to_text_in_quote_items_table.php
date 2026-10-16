<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        try {
            $driver = DB::connection()->getDriverName();
            if (in_array($driver, ['mysql', 'mariadb'])) {
                DB::statement('ALTER TABLE quote_items MODIFY description TEXT NULL');
            } else {
                Schema::table('quote_items', function (Blueprint $table) {
                    $table->text('description')->nullable()->change();
                });
            }
        } catch (\Throwable $e) {
            // Ignorar si ya es TEXT
        }
    }

    public function down(): void
    {
        // Mantener TEXT para evitar truncamiento de descripciones
    }
};
