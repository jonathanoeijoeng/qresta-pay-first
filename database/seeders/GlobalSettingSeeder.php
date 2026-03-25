<?php

namespace Database\Seeders;

use App\Models\GlobalSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GlobalSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        GlobalSetting::insert([
            ['key' => 'order_workflow', 'value' => 'serve_first'], // pay_first atau serve_first
            ['key' => 'tax_percentage', 'value' => '10'],           // Dalam persen
            ['key' => 'tat_kitchen', 'value' => '15'],        // Batas menit (merah)
        ]);
    }
}
