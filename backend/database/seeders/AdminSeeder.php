<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // permission modules for the admin dashboard (وصلها)
        $modules = ['dashboard', 'order', 'driver', 'merchant', 'customer', 'withdraw', 'subscription', 'city', 'store_category', 'service', 'banner', 'article', 'notification', 'settings', 'trash'];
        $abilities = ['view', 'create', 'update', 'delete'];
        foreach ($modules as $m) {
            foreach ($abilities as $a) {
                Permission::firstOrCreate(['name' => "$m.$a", 'guard_name' => 'web']);
            }
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        $user = User::firstOrCreate(
            ['email' => 'admin@wassilha.ps'],
            ['name' => 'مدير النظام', 'password' => Hash::make('wassilha123'), 'is_active' => true]
        );
        $user->assignRole($admin);
    }
}
