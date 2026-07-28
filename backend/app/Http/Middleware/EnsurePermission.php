<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route guard: `perm:order.view` — or `perm:order.update|order.delete`
 * to allow the request when the user holds any one of the listed abilities.
 *
 * Written against the request user rather than a named guard so it behaves
 * the same for the sanctum-token API as it would for a session login.
 */
class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'غير مصرّح'], 401);
        }

        foreach (explode('|', $permissions) as $permission) {
            if ($user->can(trim($permission))) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'لا تملك صلاحية لهذا الإجراء'], 403);
    }
}
