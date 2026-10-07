<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('event_assistants')) {
            Schema::create('event_assistants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['event_id', 'user_id']);
            });

            // Migrate existing assistant_id in events table to event_assistants
            try {
                if (Schema::hasColumn('events', 'assistant_id')) {
                    $existing = DB::table('events')->whereNotNull('assistant_id')->get();
                    foreach ($existing as $ev) {
                        DB::table('event_assistants')->insertOrIgnore([
                            'event_id' => $ev->id,
                            'user_id' => $ev->assistant_id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            } catch (\Throwable $e) {}
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_assistants');
    }
};
