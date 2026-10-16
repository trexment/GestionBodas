<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quotes')) {
            Schema::table('quotes', function (Blueprint $table) {
                if (!Schema::hasColumn('quotes', 'rejection_reason')) {
                    $table->text('rejection_reason')->nullable()->after('status');
                }
                if (!Schema::hasColumn('quotes', 'client_notes')) {
                    $table->text('client_notes')->nullable()->after('rejection_reason');
                }
                if (!Schema::hasColumn('quotes', 'responded_at')) {
                    $table->timestamp('responded_at')->nullable()->after('client_notes');
                }
            });

            try {
                DB::statement("ALTER TABLE `quotes` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'pending'");
            } catch (\Throwable $e) {
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('quotes')) {
            Schema::table('quotes', function (Blueprint $table) {
                $cols = [];
                foreach (['rejection_reason', 'client_notes', 'responded_at'] as $col) {
                    if (Schema::hasColumn('quotes', $col)) {
                        $cols[] = $col;
                    }
                }
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
