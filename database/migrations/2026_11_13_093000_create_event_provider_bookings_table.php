<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('event_provider_bookings')) {
            Schema::create('event_provider_bookings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
                $table->string('provider_key', 50)->nullable(); // 'logrono', 'vitoria', 'other'
                $table->string('provider_name', 150)->nullable();
                $table->string('service_description', 255)->nullable();
                $table->decimal('client_price', 10, 2)->default(0);
                $table->decimal('provider_total_cost', 10, 2)->default(0);
                $table->boolean('deposit_paid')->default(false);
                $table->decimal('deposit_amount', 10, 2)->default(0);
                $table->date('deposit_paid_at')->nullable();
                $table->string('deposit_payment_method', 50)->nullable();
                $table->boolean('final_paid')->default(false);
                $table->decimal('final_amount', 10, 2)->default(0);
                $table->date('final_paid_at')->nullable();
                $table->string('final_payment_method', 50)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_provider_bookings');
    }
};
