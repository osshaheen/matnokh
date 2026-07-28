<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class AuthController extends Controller
{
    /** POST /api/login */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة'], 422);
        }
        if (! $user->is_active) {
            return response()->json(['message' => 'الحساب غير مفعّل'], 403);
        }

        $token = $user->createToken('web')->plainTextToken;
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json(['token' => $token, 'user' => new UserResource($user)]);
    }

    /** GET /api/me */
    public function me(Request $request): JsonResponse
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        return (new UserResource($request->user()))->response();
    }

    /** POST /api/logout */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'تم تسجيل الخروج']);
    }
}
