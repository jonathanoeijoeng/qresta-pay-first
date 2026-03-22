<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = \App\Models\Branch::all();

        foreach ($branches as $branch) {
            for ($i = 1; $i <= 10; $i++) {
                \App\Models\Table::create([
                    'branch_id' => $branch->id,
                    'number' => str_pad($i, 2, '0', STR_PAD_LEFT), // 01, 02, dst
                    'capacity' => 4,
                    'status' => 'available',
                ]);
            }
        }
    }
}
