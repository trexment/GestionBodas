<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Fran',
            'username' => 'Fran',
            'email' => 'fran@example.com',
            'password' => Hash::make('622634790'),
            'role' => 'admin',
        ]);
    }
}
