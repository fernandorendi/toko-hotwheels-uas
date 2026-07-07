<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role; // Pastikan memanggil model Role

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Membuat dua role default
        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'User']);
    }
}