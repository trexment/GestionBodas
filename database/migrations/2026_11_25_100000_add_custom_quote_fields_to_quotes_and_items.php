<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ampliación de la tabla `quotes` para presupuestos a medida
        if (Schema::hasTable('quotes')) {
            Schema::table('quotes', function (Blueprint $table) {
                if (!Schema::hasColumn('quotes', 'quote_type')) {
                    $table->string('quote_type', 30)->default('packs')->after('brand');
                }
                if (!Schema::hasColumn('quotes', 'is_recommended')) {
                    $table->boolean('is_recommended')->default(false)->after('quote_type');
                }
                if (!Schema::hasColumn('quotes', 'equipment_notes')) {
                    $table->text('equipment_notes')->nullable()->after('is_recommended');
                }
                if (!Schema::hasColumn('quotes', 'party_hours')) {
                    $table->decimal('party_hours', 4, 1)->nullable()->after('equipment_notes');
                }
                if (!Schema::hasColumn('quotes', 'party_price')) {
                    $table->decimal('party_price', 10, 2)->nullable()->after('party_hours');
                }
                if (!Schema::hasColumn('quotes', 'extra_hour_rate')) {
                    $table->decimal('extra_hour_rate', 10, 2)->default(120.00)->after('party_price');
                }
                if (!Schema::hasColumn('quotes', 'travel_km')) {
                    $table->decimal('travel_km', 8, 2)->nullable()->default(0)->after('extra_hour_rate');
                }
                if (!Schema::hasColumn('quotes', 'travel_free_km')) {
                    $table->decimal('travel_free_km', 8, 2)->default(100.00)->after('travel_km');
                }
                if (!Schema::hasColumn('quotes', 'travel_rate')) {
                    $table->decimal('travel_rate', 8, 2)->default(0.26)->after('travel_free_km');
                }
                if (!Schema::hasColumn('quotes', 'travel_cost')) {
                    $table->decimal('travel_cost', 10, 2)->default(0.00)->after('travel_rate');
                }
            });
        }

        // 2. Ampliación de la tabla `quote_items` para estado del concepto (normal, included, optional)
        if (Schema::hasTable('quote_items')) {
            Schema::table('quote_items', function (Blueprint $table) {
                if (!Schema::hasColumn('quote_items', 'item_status')) {
                    $table->string('item_status', 20)->default('normal')->after('quantity');
                }
                if (!Schema::hasColumn('quote_items', 'item_type')) {
                    $table->string('item_type', 30)->nullable()->after('item_status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('quotes')) {
            Schema::table('quotes', function (Blueprint $table) {
                $cols = [
                    'quote_type', 'is_recommended', 'equipment_notes',
                    'party_hours', 'party_price', 'extra_hour_rate',
                    'travel_km', 'travel_free_km', 'travel_rate', 'travel_cost'
                ];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('quotes', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('quote_items')) {
            Schema::table('quote_items', function (Blueprint $table) {
                foreach (['item_status', 'item_type'] as $col) {
                    if (Schema::hasColumn('quote_items', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
