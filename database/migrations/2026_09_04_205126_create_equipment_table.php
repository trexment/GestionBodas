<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->index(); // Sonido, Iluminación, Estructuras, Cables, Otros
            $table->string('brand_model')->nullable();
            $table->integer('quantity')->default(1);
            $table->string('status')->default('available'); // available, maintenance, broken, lost
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
