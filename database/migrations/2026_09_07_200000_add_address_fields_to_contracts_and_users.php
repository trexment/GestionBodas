<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('contracts', 'client_postal_code_signed')) {
                $table->string('client_postal_code_signed', 10)->nullable();
            }
            if (!Schema::hasColumn('contracts', 'client_city_signed')) {
                $table->string('client_city_signed', 100)->nullable();
            }
            if (!Schema::hasColumn('contracts', 'client_province_signed')) {
                $table->string('client_province_signed', 100)->nullable();
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'dni')) {
                $table->string('dni', 50)->nullable();
            }
            if (!Schema::hasColumn('users', 'address')) {
                $table->string('address', 255)->nullable();
            }
            if (!Schema::hasColumn('users', 'postal_code')) {
                $table->string('postal_code', 10)->nullable();
            }
            if (!Schema::hasColumn('users', 'city')) {
                $table->string('city', 100)->nullable();
            }
            if (!Schema::hasColumn('users', 'province')) {
                $table->string('province', 100)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['client_postal_code_signed', 'client_city_signed', 'client_province_signed']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['postal_code', 'city', 'province']);
        });
    }
};
