<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('events', 'max_end_time')) {
            Schema::table('events', function (Blueprint $table) {
                $table->string('max_end_time', 20)->nullable()->after('dance_end_time');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('events', 'max_end_time')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('max_end_time');
            });
        }
    }
};
