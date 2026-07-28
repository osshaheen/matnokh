<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    /** Seeds the ability list and signs in as a user holding all of it. */
    protected function actingAsAdmin(): User
    {
        $this->seed(AdminSeeder::class);

        return $this->authenticate(User::where('email', AdminSeeder::EMAIL)->firstOrFail());
    }

    /**
     * Signs in as a user holding only the given abilities — used to prove a
     * route really is gated rather than merely labelled.
     *
     * @param  array<int,string>  $abilities
     */
    protected function actingAsUserWith(array $abilities): User
    {
        foreach ($abilities as $ability) {
            Permission::firstOrCreate(['name' => $ability, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => 'limited', 'guard_name' => 'web']);
        $role->syncPermissions($abilities);

        $user = User::create([
            'name' => 'موظف',
            'email' => 'staff@wassilha.ps',
            'password' => 'secret-password',
            'is_active' => true,
        ]);
        $user->assignRole($role);

        return $this->authenticate($user);
    }

    /** Real bearer token rather than Sanctum::actingAs, so the guard is exercised too. */
    protected function authenticate(User $user): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withHeader('Authorization', 'Bearer '.$user->createToken('test')->plainTextToken);
        $this->withHeader('Accept', 'application/json');

        return $user;
    }
}
