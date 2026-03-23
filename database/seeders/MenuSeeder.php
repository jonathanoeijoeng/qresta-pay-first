<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Branch;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::all();

        // 1. Buat Kategori Terlebih Dahulu
        $catFood = Category::create(['name' => 'Food', 'slug' => 'food']);
        $catBev  = Category::create(['name' => 'Beverage', 'slug' => 'beverage']);
        $catSnack = Category::create(['name' => 'Snack', 'slug' => 'snack']);

        // 2. Data Menu dengan Mapping Category ID
        $menus = [
            ['name' => 'Nasi Goreng Spesial', 'price' => 35000, 'cat_id' => $catFood->id, 'desc' => 'Nasi goreng dengan telur, ayam, dan kerupuk.'],
            ['name' => 'Mie Goreng Jawa', 'price' => 30000, 'cat_id' => $catFood->id, 'desc' => 'Mie goreng bumbu jawa otentik.'],
            ['name' => 'Ayam Bakar Madu', 'price' => 45000, 'cat_id' => $catFood->id, 'desc' => 'Ayam bakar dengan bumbu madu manis gurih.'],
            ['name' => 'Sate Ayam isi 10', 'price' => 38000, 'cat_id' => $catFood->id, 'desc' => 'Sate ayam bumbu kacang spesial.'],
            ['name' => 'Soto Betawi', 'price' => 42000, 'cat_id' => $catFood->id, 'desc' => 'Soto daging sapi dengan kuah santan kental.'],
            ['name' => 'Gado-Gado', 'price' => 25000, 'cat_id' => $catFood->id, 'desc' => 'Sayuran segar dengan bumbu kacang.'],
            ['name' => 'Ikan Bakar Nila', 'price' => 55000, 'cat_id' => $catFood->id, 'desc' => 'Ikan nila segar dibakar dengan bumbu rempah.'],
            ['name' => 'Es Teh Manis', 'price' => 8000, 'cat_id' => $catBev->id, 'desc' => 'Teh manis segar dengan es batu.'],
            ['name' => 'Kopi Susu Gula Aren', 'price' => 22000, 'cat_id' => $catBev->id, 'desc' => 'Espresso dengan susu dan gula aren asli.'],
            ['name' => 'Jus Alpukat', 'price' => 18000, 'cat_id' => $catBev->id, 'desc' => 'Jus alpukat mentega dengan topping cokelat.'],
            ['name' => 'Es Jeruk Peras', 'price' => 15000, 'cat_id' => $catBev->id, 'desc' => 'Jeruk peras murni segar.'],
            ['name' => 'Lemon Tea Hot', 'price' => 12000, 'cat_id' => $catBev->id, 'desc' => 'Teh lemon hangat penambah stamina.'],
            ['name' => 'Pisang Goreng Keju', 'price' => 20000, 'cat_id' => $catSnack->id, 'desc' => 'Pisang goreng topping keju dan susu kental manis.'],
            ['name' => 'Cireng Bumbu Rujak', 'price' => 15000, 'cat_id' => $catSnack->id, 'desc' => 'Cireng renyah dengan sambal rujak pedas.'],
            ['name' => 'Singkong Goreng', 'price' => 15000, 'cat_id' => $catSnack->id, 'desc' => 'Singkong empuk bumbu bawang putih.'],
        ];

        foreach ($menus as $item) {
            $menu = Menu::create([
                'name' => $item['name'],
                'slug' => Str::slug($item['name']),
                'category_id' => $item['cat_id'], // Menggunakan ID Kategori yang benar
                'description' => $item['desc'],
                'base_price' => $item['price'],
                'image' => null,
            ]);

            // Hubungkan ke semua cabang
            foreach ($branches as $branch) {
                $menu->branches()->attach($branch->id, [
                    'price' => $item['price'],
                    'is_available' => rand(0, 1), // Random ketersediaan untuk demo
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
