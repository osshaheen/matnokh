<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Gives a partner (driver / merchant) a login account. Called with a password
// either at creation time or later from the "set password" action, so a partner
// can be added first and given credentials afterwards (the "forgot password"
// case is the same call from the partner's side once their app exists).
trait LinksUserAccount
{
    /**
     * Create or update the User linked to a partner and set its password.
     * $partner must have: name (or store_name/owner_name), phone, email, user_id.
     */
    protected function setPartnerPassword(object $partner, string $role, string $password, string $name, string $phone, ?string $email = null): User
    {
        // Login identifier: the real email if given, else a stable phone-derived one.
        $digits = preg_replace('/\D/', '', $phone) ?: (string) $partner->id;
        $loginEmail = $email ?: $role.$digits.'@wassilha.ps';

        $user = $partner->user_id ? User::find($partner->user_id) : null;
        $user ??= User::where('phone', $phone)->first();

        if ($user) {
            $user->update([
                'name' => $name,
                'phone' => $phone,
                'password' => Hash::make($password),
                'is_active' => true,
            ]);
        } else {
            $user = User::create([
                'name' => $name,
                'email' => $loginEmail,
                'phone' => $phone,
                'password' => Hash::make($password),
                'is_active' => true,
            ]);
        }

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        if ((int) $partner->user_id !== (int) $user->id) {
            $partner->forceFill(['user_id' => $user->id])->save();
        }

        return $user;
    }
}
