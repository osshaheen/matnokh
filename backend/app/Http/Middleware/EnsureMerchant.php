<?php

namespace App\Http\Middleware;

use App\Models\Merchant;
use Closure;
use Illuminate\Http\Request;

// Scopes the merchant API to the authenticated user's own store. Every merchant
// controller reads $request->attributes->get('merchant').
class EnsureMerchant
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $merchant = $user ? Merchant::where('user_id', $user->id)->first() : null;

        if (! $merchant) {
            return response()->json(['message' => 'هذا الحساب غير مرتبط بمتجر'], 403);
        }
        if ((int) $merchant->is_active === 0) {
            return response()->json(['message' => 'حساب المتجر غير مفعّل'], 403);
        }

        $request->attributes->set('merchant', $merchant);

        return $next($request);
    }
}
