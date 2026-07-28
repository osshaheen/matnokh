<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    /** Override with ADMIN_EMAIL in .env */
    public const EMAIL = 'admin@wassilha.ps';

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

        $user = User::withTrashed()->firstOrNew(['email' => env('ADMIN_EMAIL', self::EMAIL)]);

        // No password is ever hard-coded here — this repository is public.
        // ADMIN_PASSWORD wins; otherwise a fresh install gets a random one and
        // an existing account keeps the password it already has.
        $password = env('ADMIN_PASSWORD') ?: ($user->exists ? null : Str::password(16, symbols: false));

        $user->name = $user->name ?: 'مدير النظام';
        $user->is_active = true;
        $user->deleted_at = null;
        if ($password) {
            $user->password = Hash::make($password);
        }
        $user->save();
        $user->syncRoles([$admin]);

        if ($password && ! env('ADMIN_PASSWORD')) {
            $this->command?->newLine();
            $this->command?->warn("  حساب المدير: {$user->email}");
            $this->command?->warn("  كلمة المرور: {$password}");
            $this->command?->warn('  سجّلها الآن — لن تُعرض مرة أخرى. لتغييرها: php artisan admin:reset-password');
            $this->command?->newLine();
        }
    }
}
