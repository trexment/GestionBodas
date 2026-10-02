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
        Schema::create('client_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Staff / DJ / Mago
            $table->string('title')->default('Reunión de Preparación');
            $table->string('meeting_type')->default('escaleta_musica'); // primera_toma, visita_tecnica, escaleta_musica, magia_guion, otro
            $table->dateTime('meeting_date');
            $table->integer('duration_minutes')->default(45);
            $table->string('location_type')->default('in_person'); // in_person, video_call, phone
            $table->string('location')->nullable();
            $table->string('video_call_url')->nullable();
            $table->string('status')->default('scheduled'); // scheduled, completed, cancelled
            $table->text('summary')->nullable(); // Acuerdos / Conclusiones
            $table->text('notes')->nullable(); // Notas previas de preparación
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_meetings');
    }
};
