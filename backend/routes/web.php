<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

// API-only backend — the root just reports status (no session/view).
Route::get('/', fn () => response()->json([
    'app' => 'Wassilha API',
    'status' => 'ok',
    'ts' => now()->toIso8601String(),
]));

/*
|--------------------------------------------------------------------------
| TEMPORARY — one-shot admin password recovery
|--------------------------------------------------------------------------
| Open once in the browser, write down the password, then DELETE this block.
| It burns itself after the first successful call by writing a lock file, so
| a second visit returns 404 even if the route is still deployed.
*/
Route::get('/adminpassreset/{token}', function (string $token) {
    $expected = 'ed83c789a29150a12921d1d99cc16b37';
    $lock = storage_path('app/adminpassreset.lock');

    abort_unless(hash_equals($expected, $token), 404);
    abort_if(file_exists($lock), 404);

    $email = request('email', 'admin@wassilha.ps');
    $password = request('password', 'Wassilha@2026');

    $user = User::withTrashed()->firstOrNew(['email' => $email]);
    $user->name = $user->name ?: 'مدير النظام';
    $user->password = Hash::make($password);
    $user->is_active = true;
    $user->deleted_at = null;
    $user->save();

    // make sure the account can reach every module, then drop stale tokens
    $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    if (Permission::count() > 0) {
        $role->syncPermissions(Permission::all());
    }
    $user->syncRoles([$role]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user->tokens()->delete();

    file_put_contents($lock, now()->toIso8601String());

    return response()->json([
        'status' => 'ok',
        'email' => $user->email,
        'password' => $password,
        'abilities' => $role->permissions()->count(),
        'note' => 'تم. هذا الرابط لن يعمل مرة أخرى — احذف المسار من routes/web.php',
    ], 200, [], JSON_UNESCAPED_UNICODE);
});
