<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('type', 20)->default('factura')->after('invoice_number'); // 'factura' o 'recibo'
            $table->foreignId('quote_id')->nullable()->after('event_id')->constrained('quotes')->nullOnDelete();
            $table->decimal('tax_rate', 5, 2)->default(21.00)->after('tax');
            $table->text('notes')->nullable()->after('pdf_path');
            $table->string('payment_method', 50)->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['quote_id']);
            $table->dropColumn(['type', 'quote_id', 'tax_rate', 'notes', 'payment_method']);
        });
    }
};
