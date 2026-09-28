<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('token', 64)->nullable()->unique()->after('id');
            $table->longText('signature_data')->nullable()->after('status');
            $table->string('signed_ip', 45)->nullable()->after('signature_data');
            $table->boolean('consent_rrss')->default(true)->after('signed_ip');
            $table->string('client_name_signed')->nullable()->after('consent_rrss');
            $table->string('client_dni_signed')->nullable()->after('client_name_signed');
            $table->string('client_phone_signed')->nullable()->after('client_dni_signed');
            $table->string('client_email_signed')->nullable()->after('client_phone_signed');
            $table->string('client_address_signed')->nullable()->after('client_email_signed');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'token',
                'signature_data',
                'signed_ip',
                'consent_rrss',
                'client_name_signed',
                'client_dni_signed',
                'client_phone_signed',
                'client_email_signed',
                'client_address_signed',
            ]);
        });
    }
};
