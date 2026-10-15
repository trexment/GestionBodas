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
        if (!Schema::hasTable('whatsapp_messages')) {
            Schema::create('whatsapp_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('phone', 35)->index();
                $table->string('sender_name', 150)->nullable();
                $table->enum('direction', ['inbound', 'outbound'])->default('outbound')->index();
                $table->text('message');
                $table->string('status', 30)->default('sent');
                $table->string('external_id', 120)->nullable()->index();
                $table->string('brand', 50)->default('nunez_and_son')->index();
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
