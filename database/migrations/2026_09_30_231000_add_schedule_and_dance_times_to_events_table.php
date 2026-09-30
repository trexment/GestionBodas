<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('start_time', 20)->nullable()->after('event_date');
            $table->string('dance_start_time', 20)->nullable()->after('start_time');
            $table->decimal('dance_duration_hours', 4, 1)->nullable()->after('dance_start_time');
            $table->string('dance_end_time', 20)->nullable()->after('dance_duration_hours');
            $table->string('ceremony_time', 20)->nullable()->after('dance_end_time');
            $table->string('cocktail_time', 20)->nullable()->after('ceremony_time');
            $table->string('banquet_time', 20)->nullable()->after('cocktail_time');
            $table->text('schedule_notes')->nullable()->after('banquet_time');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'start_time',
                'dance_start_time',
                'dance_duration_hours',
                'dance_end_time',
                'ceremony_time',
                'cocktail_time',
                'banquet_time',
                'schedule_notes',
            ]);
        });
    }
};
