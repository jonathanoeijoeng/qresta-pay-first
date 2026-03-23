<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Buat Permission (Contoh)
        Permission::create(['name' => 'manage tables']);
        Permission::create(['name' => 'manage menu']);
        Permission::create(['name' => 'order food']);
        Permission::create(['name' => 'view orders']);
        Permission::create(['name' => 'process payments']);
        Permission::create(['name' => 'update order status']);

        // 2. Buat Role dan berikan Permission

        // Role Dasar
        $roles = ['super_admin', 'admin_cabang', 'waitress', 'kitchen', 'cashier'];

        foreach ($roles as $role) {
            Role::findOrCreate($role);
        }

        $user = User::where('email', 'owner@qresta.com')->first()->assignRole('super_admin');

        // Contoh Assignment Permission Spesifik
        Role::findByName('kitchen')->givePermissionTo(['view orders', 'update order status']);
        Role::findByName('cashier')->givePermissionTo(['view orders', 'process payments']);
    }
}
