<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_music_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('category')->default('banquete'); // ceremonia, coctel, banquete, baile, lista_negra
            $table->string('moment')->default('General'); // Entrada Comedor, Regalo Padres, Ramo, Tarta, etc.
            $table->string('title');
            $table->string('artist')->nullable();
            $table->string('requested_by')->nullable(); // Novios, Amigos, Hermana, etc.
            $table->text('notes')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('spotify_url')->nullable();
            $table->string('audio_file')->nullable();
            $table->string('cue_time')->nullable(); // Ej: 01:15
            $table->enum('status', ['pending', 'ready', 'played'])->default('pending');
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_music_requests');
    }
};
