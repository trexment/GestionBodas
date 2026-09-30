<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $events = DB::table('events')->whereNull('token')->orWhere('token', '')->get();
        foreach ($events as $event) {
            DB::table('events')->where('id', $event->id)->update([
                'token' => Str::random(32)
            ]);
        }
    }

    public function down(): void
    {
        // No down needed
    }
};
