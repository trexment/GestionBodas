<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('tax_type', 20)->default('included')->after('deposit_amount'); // 'included' (IVA incluido), 'excluded' (+21% IVA), 'none' (Sin IVA)
            $table->decimal('tax_rate', 5, 2)->default(21.00)->after('tax_type');
            $table->decimal('subtotal_amount', 10, 2)->nullable()->after('tax_rate');
            $table->decimal('tax_amount', 10, 2)->nullable()->after('subtotal_amount');
            $table->json('payment_methods')->nullable()->after('tax_amount'); // e.g. ["transfer", "bizum", "cash"]
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['tax_type', 'tax_rate', 'subtotal_amount', 'tax_amount', 'payment_methods']);
        });
    }
};
