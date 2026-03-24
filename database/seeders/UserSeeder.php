<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil ID cabang yang baru dibuat
        $sudirman = Branch::where('name', 'Sudirman')->first();
        $menteng = Branch::where('name', 'Menteng')->first();
        $senopati = Branch::where('name', 'Senopati')->first();

        // 1. SUPER ADMIN (Pusat - Tidak terikat cabang)
        User::create([
            'name' => 'Jonathan Owner',
            'email' => 'owner@qresta.com',
            'password' => Hash::make('password'),
            'branch_id' => null,
        ]);

        // 2. ADMIN CABANG (Hanya akses Cabang Sudirman)
        User::create([
            'name' => 'Admin Sudirman',
            'email' => 'admin.sudirman@qresta.com',
            'password' => Hash::make('password'),
            'branch_id' => $sudirman->id,
        ]);

        // 3. WAITRESS (Cabang Sudirman)
        User::create([
            'name' => 'Siti Waitress',
            'email' => 'waitress.sudirman@qresta.com',
            'password' => Hash::make('password'),
            'branch_id' => $sudirman->id,
        ]);

        // 4. KITCHEN (Cabang Sudirman)
        User::create([
            'name' => 'Chef Juna',
            'email' => 'kitchen.sudirman@qresta.com',
            'password' => Hash::make('password'),
            'branch_id' => $sudirman->id,
        ]);

        // 5. CASHIER (Cabang Menteng - Contoh beda cabang)
        User::create([
            'name' => 'Budi Kasir',
            'email' => 'cashier.menteng@qresta.com',
            'password' => Hash::make('password'),
            'branch_id' => $menteng->id,
        ]);
    }
}
