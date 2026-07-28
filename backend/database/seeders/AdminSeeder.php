<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    /** Overridable per-environment with ADMIN_EMAIL / ADMIN_PASSWORD in .env */
    public const EMAIL = 'admin@wassilha.ps';
    public const PASSWORD = 'Wassilha@2026';

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

        // updateOrCreate so re-seeding also resets a forgotten password
        $user = User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', self::EMAIL)],
            [
                'name' => 'مدير النظام',
                'password' => Hash::make(env('ADMIN_PASSWORD', self::PASSWORD)),
                'is_active' => true,
            ]
        );
        $user->assignRole($admin);
    }
}
