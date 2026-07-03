<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class HotWheelsSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::create(['name' => 'Admin']);
        $userRole  = Role::create(['name' => 'User']);

        User::create([
            'role_id'  => $adminRole->id,
            'name'     => 'Fernando Admin',
            'email'    => 'admin@gmail.com',
            'password' => Hash::make('password123'),
        ]);

        User::create([
            'role_id'  => $userRole->id,
            'name'     => 'Budi Kolektor',
            'email'    => 'user@gmail.com',
            'password' => Hash::make('password123'),
        ]);

        $mainline = Category::create([
            'name' => 'Mainline Basic',
            'slug' => Str::slug('Mainline Basic'),
        ]);

        $premium = Category::create([
            'name' => 'Premium Car Culture',
            'slug' => Str::slug('Premium Car Culture'),
        ]);

        $treasure = Category::create([
            'name' => 'Treasure Hunt',
            'slug' => Str::slug('Treasure Hunt'),
        ]);

        Product::create([
            'category_id' => $mainline->id,
            'name'        => "'67 Chevy Camaro",
            'series'      => 'HW First Editions',
            'price'       => 35000,
            'stock'       => 12,
        ]);

        Product::create([
            'category_id' => $mainline->id,
            'name'        => 'Nissan Skyline GT-R (R34)',
            'series'      => 'HW Speed Graphics',
            'price'       => 45000,
            'stock'       => 8,
        ]);

        Product::create([
            'category_id' => $premium->id,
            'name'        => 'Toyota Supra MK4',
            'series'      => 'Fast & Furious Premium',
            'price'       => 110000,
            'stock'       => 5,
        ]);
    }
}