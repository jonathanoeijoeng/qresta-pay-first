<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ambil semua cabang (3 cabang) dan kategori (2 kategori)
        $branches = Branch::all();
        $categories = Category::all();

        // 2. Daftar 15 Nama Menu (Campuran Makanan & Minuman)
        $menuNames = [
            'Nasi Goreng Special',
            'Mie Goreng Jawa',
            'Ayam Bakar Madu',
            'Sate Ayam Madura',
            'Soto Betawi',
            'Gado-Gado',
            'Es Teh Manis',
            'Es Jeruk Peras',
            'Kopi Susu Gula Aren',
            'Es Campur',
            'Lemon Tea',
            'Soda Gembira',
            'Tahu Tempe Penyet',
            'Bakso Urat',
            'Cah Kangkung Belacan'
        ];

        // 3. Loop untuk setiap cabang
        foreach ($branches as $branch) {
            foreach ($menuNames as $index => $name) {
                // Logika pembagian kategori: 
                // Index 0-8 masuk kategori pertama, sisanya kategori kedua (atau random)
                $category = ($index < 8) ? $categories->first() : $categories->last();

                Menu::create([
                    'branch_id' => $branch->id,
                    'category_id' => $category->id,
                    'name' => $name,
                    'slug' => Str::slug($name) . '-' . $branch->id, // Agar slug unik per cabang
                    'price' => fake()->randomElement([15000, 25000, 35000, 45000, 50000]),
                    'description' => 'Deskripsi lezat untuk ' . $name,
                    // is_available random (true/false)
                    'is_available' => fake()->boolean(80), // 80% peluang untuk aktif
                ]);
            }
        }
    }
}
