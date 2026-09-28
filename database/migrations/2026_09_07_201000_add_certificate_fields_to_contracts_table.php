<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('contracts', 'signature_type')) {
                $table->string('signature_type', 30)->default('canvas')->after('signature_data');
            }
            if (!Schema::hasColumn('contracts', 'certificate_issuer')) {
                $table->string('certificate_issuer', 255)->nullable()->after('signature_type');
            }
            if (!Schema::hasColumn('contracts', 'certificate_serial')) {
                $table->string('certificate_serial', 100)->nullable()->after('certificate_issuer');
            }
            if (!Schema::hasColumn('contracts', 'certificate_hash')) {
                $table->string('certificate_hash', 128)->nullable()->after('certificate_serial');
            }
            if (!Schema::hasColumn('contracts', 'certificate_subject')) {
                $table->string('certificate_subject', 255)->nullable()->after('certificate_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'signature_type',
                'certificate_issuer',
                'certificate_serial',
                'certificate_hash',
                'certificate_subject',
            ]);
        });
    }
};
