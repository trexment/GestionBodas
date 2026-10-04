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
        Schema::create('event_dj_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('session_name')->default('Sesión Principal');
            $table->integer('order')->default(1);
            $table->string('title');
            $table->string('artist')->nullable();
            $table->string('played_at_time')->nullable(); // e.g. "01:25:30" o "23:45"
            $table->decimal('bpm', 5, 1)->nullable();
            $table->string('key')->nullable(); // e.g. "8A", "Am"
            $table->string('duration')->nullable(); // e.g. "03:45"
            $table->string('genre')->nullable();
            $table->string('source_software')->default('engine_dj'); // engine_dj, rekordbox, serato, traktor, m3u, csv
            $table->foreignId('matched_request_id')->nullable()->constrained('event_music_requests')->nullOnDelete();
            $table->timestamps();

            $table->index(['event_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_dj_histories');
    }
};
