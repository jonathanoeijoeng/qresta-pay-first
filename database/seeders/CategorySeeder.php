<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    { {
            $categories = [
                'Makanan',
                'Minuman',
                'Snack',
                'Dessert'
            ];

            foreach ($categories as $category) {
                Category::updateOrCreate(
                    ['slug' => Str::slug($category)], // Cek berdasarkan slug
                    ['name' => $category]            // Update/Insert nama
                );
            }
        }
    }
}
