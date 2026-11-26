<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade campos puramente aditivos para el seguimiento de transferencias a cuenta común,
     * reembolsos de gastos adelantados por socios y liquidación final de eventos a socios.
     */
    public function up(): void
    {
        // 1. Campos en events
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'deposit_transferred_to_joint')) {
                $table->boolean('deposit_transferred_to_joint')->default(false)->after('deposit_collected_by');
            }
            if (!Schema::hasColumn('events', 'deposit_transferred_at')) {
                $table->date('deposit_transferred_at')->nullable()->after('deposit_transferred_to_joint');
            }
            if (!Schema::hasColumn('events', 'settlement_status')) {
                $table->string('settlement_status', 30)->default('pending')->after('deposit_transferred_at');
            }
            if (!Schema::hasColumn('events', 'settlement_paid_at')) {
                $table->date('settlement_paid_at')->nullable()->after('settlement_status');
            }
            if (!Schema::hasColumn('events', 'settlement_notes')) {
                $table->text('settlement_notes')->nullable()->after('settlement_paid_at');
            }
        });

        // 2. Campos en invoices (recibos y facturas)
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('invoices', 'transferred_to_joint')) {
                    $table->boolean('transferred_to_joint')->default(false)->after('collected_by');
                }
                if (!Schema::hasColumn('invoices', 'transferred_at')) {
                    $table->date('transferred_at')->nullable()->after('transferred_to_joint');
                }
            });
        }

        // 3. Campos en material_expenses (compras y gastos)
        if (Schema::hasTable('material_expenses')) {
            Schema::table('material_expenses', function (Blueprint $table) {
                if (!Schema::hasColumn('material_expenses', 'is_reimbursed')) {
                    $table->boolean('is_reimbursed')->default(false)->after('paid_by');
                }
                if (!Schema::hasColumn('material_expenses', 'reimbursed_at')) {
                    $table->date('reimbursed_at')->nullable()->after('is_reimbursed');
                }
            });
        }

        // 4. Campos en event_provider_bookings (gastos a proveedores)
        if (Schema::hasTable('event_provider_bookings')) {
            Schema::table('event_provider_bookings', function (Blueprint $table) {
                if (!Schema::hasColumn('event_provider_bookings', 'is_reimbursed')) {
                    $table->boolean('is_reimbursed')->default(false)->after('paid_by');
                }
                if (!Schema::hasColumn('event_provider_bookings', 'reimbursed_at')) {
                    $table->date('reimbursed_at')->nullable()->after('is_reimbursed');
                }
            });
        }
    }

    /**
     * Revierte los campos añadidos.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $cols = [];
            foreach (['deposit_transferred_to_joint', 'deposit_transferred_at', 'settlement_status', 'settlement_paid_at', 'settlement_notes'] as $c) {
                if (Schema::hasColumn('events', $c)) $cols[] = $c;
            }
            if (!empty($cols)) $table->dropColumn($cols);
        });

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                $cols = [];
                foreach (['transferred_to_joint', 'transferred_at'] as $c) {
                    if (Schema::hasColumn('invoices', $c)) $cols[] = $c;
                }
                if (!empty($cols)) $table->dropColumn($cols);
            });
        }

        if (Schema::hasTable('material_expenses')) {
            Schema::table('material_expenses', function (Blueprint $table) {
                $cols = [];
                foreach (['is_reimbursed', 'reimbursed_at'] as $c) {
                    if (Schema::hasColumn('material_expenses', $c)) $cols[] = $c;
                }
                if (!empty($cols)) $table->dropColumn($cols);
            });
        }

        if (Schema::hasTable('event_provider_bookings')) {
            Schema::table('event_provider_bookings', function (Blueprint $table) {
                $cols = [];
                foreach (['is_reimbursed', 'reimbursed_at'] as $c) {
                    if (Schema::hasColumn('event_provider_bookings', $c)) $cols[] = $c;
                }
                if (!empty($cols)) $table->dropColumn($cols);
            });
        }
    }
};
