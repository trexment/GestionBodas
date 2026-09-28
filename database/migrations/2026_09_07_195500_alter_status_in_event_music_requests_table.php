<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Alter status column from enum to varchar(50) to support 'playing', 'paused', 'played', 'pending', etc.
        DB::statement("ALTER TABLE `event_music_requests` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `event_music_requests` MODIFY COLUMN `status` ENUM('pending', 'ready', 'played') NOT NULL DEFAULT 'pending'");
    }
};
