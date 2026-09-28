<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_music_requests', function (Blueprint $table) {
            $table->unsignedInteger('likes')->default(0)->after('status');
            $table->boolean('is_guest_request')->default(false)->after('likes');
            $table->string('guest_name')->nullable()->after('is_guest_request');
            $table->string('guest_note')->nullable()->after('guest_name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('calendar_token', 64)->nullable()->unique()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('event_music_requests', function (Blueprint $table) {
            $table->dropColumn(['likes', 'is_guest_request', 'guest_name', 'guest_note']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('calendar_token');
        });
    }
};
