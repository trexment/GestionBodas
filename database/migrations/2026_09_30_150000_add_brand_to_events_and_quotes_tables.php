<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('events') && !Schema::hasColumn('events', 'brand')) {
            Schema::table('events', function (Blueprint $table) {
                $table->string('brand')->nullable()->default('nunez_and_son')->after('event_type');
            });
        }

        if (Schema::hasTable('quotes') && !Schema::hasColumn('quotes', 'brand')) {
            Schema::table('quotes', function (Blueprint $table) {
                $table->string('brand')->nullable()->after('event_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('events') && Schema::hasColumn('events', 'brand')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('brand');
            });
        }

        if (Schema::hasTable('quotes') && Schema::hasColumn('quotes', 'brand')) {
            Schema::table('quotes', function (Blueprint $table) {
                $table->dropColumn('brand');
            });
        }
    }
};
