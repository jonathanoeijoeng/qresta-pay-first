<?php

namespace Database\Seeders;

use App\Models\Branch; // Pastikan Model Menu di-import
use App\Models\Category; // Pastikan Model Branch di-import
use App\Models\Menu; // Pastikan Model Category di-import
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MenuSeeder extends Seeder
{
    public function run()
    {
        // Ambil semua branch yang ada
        $branches = Branch::all();

        // 1. Buat Kategori Terlebih Dahulu
        $catFood = Category::create(['name' => 'Food', 'slug' => 'food']);
        $catBev = Category::create(['name' => 'Beverage', 'slug' => 'beverage']);
        $catSnack = Category::create(['name' => 'Snack', 'slug' => 'snack']);

        $menus = [
            // CATEGORY 1: FOOD
            ['category_id' => 1, 'name' => 'Nasi Goreng Spesial', 'base_price' => 35000],
            ['category_id' => 1, 'name' => 'Mie Ayam Jamur', 'base_price' => 25000],
            ['category_id' => 1, 'name' => 'Sate Ayam Madura', 'base_price' => 40000],
            ['category_id' => 1, 'name' => 'Soto Betawi', 'base_price' => 45000],
            ['category_id' => 1, 'name' => 'Rendang Sapi', 'base_price' => 55000],
            ['category_id' => 1, 'name' => 'Ayam Bakar Taliwang', 'base_price' => 42000],
            ['category_id' => 1, 'name' => 'Gado-Gado Siram', 'base_price' => 28000],
            ['category_id' => 1, 'name' => 'Ikan Bakar Cianjur', 'base_price' => 85000],
            ['category_id' => 1, 'name' => 'Bakso Urat Granat', 'base_price' => 22000],
            ['category_id' => 1, 'name' => 'Nasi Uduk Komplit', 'base_price' => 30000],
            ['category_id' => 1, 'name' => 'Rawon Surabaya', 'base_price' => 48000],
            ['category_id' => 1, 'name' => 'Pempek Kapal Selam', 'base_price' => 35000],
            ['category_id' => 1, 'name' => 'Bebek Goreng Kremes', 'base_price' => 50000],
            ['category_id' => 1, 'name' => 'Kwetiap Goreng Sapi', 'base_price' => 38000],
            ['category_id' => 1, 'name' => 'Capcay Seafood', 'base_price' => 32000],
            ['category_id' => 1, 'name' => 'Gurame Asam Manis', 'base_price' => 90000],
            ['category_id' => 1, 'name' => 'Sop Buntut', 'base_price' => 95000],
            ['category_id' => 1, 'name' => 'Nasi Kuning Manado', 'base_price' => 33000],
            ['category_id' => 1, 'name' => 'Ayam Geprek Sambal Bawang', 'base_price' => 25000],
            ['category_id' => 1, 'name' => 'Fu Yung Hai', 'base_price' => 40000],

            // CATEGORY 2: BEVERAGES
            ['category_id' => 2, 'name' => 'Es Teh Manis', 'base_price' => 8000],
            ['category_id' => 2, 'name' => 'Es Jeruk Peras', 'base_price' => 15000],
            ['category_id' => 2, 'name' => 'Kopi Susu Gula Aren', 'base_price' => 22000],
            ['category_id' => 2, 'name' => 'Matcha Latte', 'base_price' => 28000],
            ['category_id' => 2, 'name' => 'Thai Tea', 'base_price' => 18000],
            ['category_id' => 2, 'name' => 'Jus Alpukat Kocok', 'base_price' => 25000],
            ['category_id' => 2, 'name' => 'Es Campur Segar', 'base_price' => 20000],
            ['category_id' => 2, 'name' => 'Lemon Tea Ice', 'base_price' => 15000],
            ['category_id' => 2, 'name' => 'Cappuccino Hot', 'base_price' => 30000],
            ['category_id' => 2, 'name' => 'Chocolate Milkshake', 'base_price' => 25000],
            ['category_id' => 2, 'name' => 'Es Kelapa Muda', 'base_price' => 18000],
            ['category_id' => 2, 'name' => 'Soda Gembira', 'base_price' => 20000],
            ['category_id' => 2, 'name' => 'Wedang Jahe', 'base_price' => 12000],
            ['category_id' => 2, 'name' => 'Lychee Tea', 'base_price' => 22000],
            ['category_id' => 2, 'name' => 'Air Mineral', 'base_price' => 6000],

            // CATEGORY 3: SNACK
            ['category_id' => 3, 'name' => 'Kentang Goreng', 'base_price' => 20000],
            ['category_id' => 3, 'name' => 'Tempe Mendoan', 'base_price' => 15000],
            ['category_id' => 3, 'name' => 'Pisang Goreng Keju', 'base_price' => 18000],
            ['category_id' => 3, 'name' => 'Cireng Sambal Rujak', 'base_price' => 15000],
            ['category_id' => 3, 'name' => 'Tahu Bakso Goreng', 'base_price' => 20000],
            ['category_id' => 3, 'name' => 'Singkong Keju', 'base_price' => 17000],
            ['category_id' => 3, 'name' => 'Dimsum Ayam', 'base_price' => 25000],
            ['category_id' => 3, 'name' => 'Roti Bakar Coklat', 'base_price' => 22000],
            ['category_id' => 3, 'name' => 'Martabak Telur Mini', 'base_price' => 25000],
            ['category_id' => 3, 'name' => 'Bakwan Jagung', 'base_price' => 12000],
            ['category_id' => 3, 'name' => 'Risoles Mayonnaise', 'base_price' => 15000],
            ['category_id' => 3, 'name' => 'Edamame Rebus', 'base_price' => 18000],
            ['category_id' => 3, 'name' => 'Chicken Wings', 'base_price' => 35000],
            ['category_id' => 3, 'name' => 'Nachos Cheese', 'base_price' => 30000],
            ['category_id' => 3, 'name' => 'Onion Rings', 'base_price' => 20000],
            ['category_id' => 3, 'name' => 'Pisang Bakar Coklat', 'base_price' => 18000],
            ['category_id' => 3, 'name' => 'Otak-Otak Bakar', 'base_price' => 20000],
            ['category_id' => 3, 'name' => 'Lumpia Semarang', 'base_price' => 15000],
            ['category_id' => 3, 'name' => 'Kebab Mini', 'base_price' => 22000],
            ['category_id' => 3, 'name' => 'Calamari Rings', 'base_price' => 38000],
        ];

        foreach ($menus as $item) {
            // Buat record menu utama
            $menu = Menu::create([
                'category_id' => $item['category_id'],
                'name' => $item['name'],
                'slug' => Str::slug($item['name']),
                'description' => 'Menu '.$item['name'].' yang lezat dan bergizi.',
                'base_price' => $item['base_price'],
                'is_active' => true,
            ]);

            // Hubungkan menu dengan semua branch (Pivot Table)
            foreach ($branches as $branch) {
                $menu->branches()->attach($branch->id, [
                    'price' => $item['base_price'], // Menggunakan harga dasar
                    'is_available' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
