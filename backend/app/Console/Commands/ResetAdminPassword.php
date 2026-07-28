<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Resets (or creates) a dashboard admin account.
 *
 *   php artisan admin:reset-password                                    # random password, printed once
 *   php artisan admin:reset-password ops@wassilha.ps --password="…"     # a password you choose
 */
class ResetAdminPassword extends Command
{
    protected $signature = 'admin:reset-password
        {email? : Account email (defaults to the seeded admin)}
        {--password= : Password to set (default: a random one is generated)}';

    protected $description = 'إعادة تعيين كلمة مرور مدير اللوحة (وإنشاء الحساب إن لم يكن موجوداً)';

    public function handle(): int
    {
        $email = $this->argument('email') ?: env('ADMIN_EMAIL', AdminSeeder::EMAIL);

        // never fall back to a value committed to this (public) repository
        $password = (string) ($this->option('password') ?: Str::password(16, symbols: false));

        if (strlen($password) < 8) {
            $this->error('كلمة المرور يجب أن تكون 8 أحرف على الأقل.');

            return self::FAILURE;
        }

        $user = User::withTrashed()->firstOrNew(['email' => $email]);
        $existed = $user->exists;

        $user->name ??= 'مدير النظام';
        $user->password = Hash::make($password);
        $user->is_active = true;
        $user->deleted_at = null;
        $user->save();

        // make sure the account can actually reach every module
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        if (Permission::count() > 0) {
            $role->syncPermissions(Permission::all());
        }
        $user->syncRoles([$role]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // any stale API token would keep working with the old session otherwise
        $user->tokens()->delete();

        $this->newLine();
        $this->info($existed ? '✅ تم تحديث كلمة المرور' : '✅ تم إنشاء حساب المدير');
        $this->line('   البريد الإلكتروني : '.$user->email);
        $this->line('   كلمة المرور      : '.$password);
        $this->line('   الصلاحيات        : '.$role->permissions()->count().' صلاحية');
        $this->newLine();
        $this->comment('   تم إلغاء الجلسات السابقة — سجّل الدخول من جديد.');
        $this->newLine();

        return self::SUCCESS;
    }
}
