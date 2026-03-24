<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            ['name' => 'Sudirman', 'location' => 'Jakarta Pusat', 'code' => 'SUD', 'color' => '#1E90FF'],
            ['name' => 'Menteng', 'location' => 'Jakarta Pusat', 'code' => 'MTG', 'color' => '#32CD32'],
            ['name' => 'Senopati', 'location' => 'Jakarta Selatan', 'code' => 'SPI', 'color' => '#FF4500'],
        ];

        foreach ($branches as $branch) {
            Branch::create($branch);
        }
    }
}
