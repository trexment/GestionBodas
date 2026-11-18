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
        if (!Schema::hasTable('material_expenses')) {
            Schema::create('material_expenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
                $table->string('season', 10)->nullable()->index();
                $table->string('concept', 255);
                $table->string('category', 50)->default('general');
                $table->decimal('amount', 10, 2)->default(0);
                $table->date('purchase_date');
                $table->string('paid_by', 100)->default('Fran');
                $table->string('payment_method', 50)->default('card');
                $table->string('supplier', 150)->nullable();
                $table->string('invoice_number', 100)->nullable();
                $table->string('receipt_file', 255)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_expenses');
    }
};
