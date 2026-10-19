<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('events', 'cancellation_reason')) {
            Schema::table('events', function (Blueprint $table) {
                $table->text('cancellation_reason')->nullable()->after('no_response_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('events', 'cancellation_reason')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('cancellation_reason');
            });
        }
    }
};
