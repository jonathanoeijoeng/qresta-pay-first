<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            ['name' => 'Resto Cabang Sudirman', 'location' => 'Jakarta Pusat'],
            ['name' => 'Resto Cabang Menteng', 'location' => 'Jakarta Pusat'],
            ['name' => 'Resto Cabang Senopati', 'location' => 'Jakarta Selatan'],
        ];

        foreach ($branches as $branch) {
            Branch::create($branch);
        }
    }
}
