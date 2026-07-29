<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\Otp;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MerchantAuthController extends Controller
{
    /** GET /api/merchant/login-method — the app renders the right form from this. */
    public function method(): JsonResponse
    {
        return response()->json(['method' => Setting::get('merchant_login_method')]);
    }

    /** POST /api/merchant/request-otp — used only when the active method is phone_otp. */
    public function requestOtp(Request $request): JsonResponse
    {
        if (Setting::get('merchant_login_method') !== 'phone_otp') {
            return response()->json(['message' => 'الدخول برمز غير مفعّل'], 422);
        }
        $data = $request->validate(['phone' => ['required', 'string', 'max:30']]);
        if (! Merchant::where('phone', $data['phone'])->exists()) {
            return response()->json(['message' => 'لا يوجد متجر بهذا الرقم'], 404);
        }
        $code = (string) random_int(1000, 9999);
        Otp::create([
            'phone' => $data['phone'], 'code' => $code,
            'expires_at' => now()->addMinutes(10),
        ]);
        // TODO: hand off to an SMS provider. For now the code is returned in non-prod.
        return response()->json([
            'message' => 'تم إرسال الرمز',
            'dev_code' => app()->environment('production') ? null : $code,
        ]);
    }

    /** POST /api/merchant/login — validates per the active method, returns a token. */
    public function login(Request $request): JsonResponse
    {
        $method = Setting::get('merchant_login_method');

        $merchant = match ($method) {
            'email_password' => $this->byEmailPassword($request),
            'phone_otp' => $this->byPhoneOtp($request),
            default => $this->byPhonePassword($request),
        };

        $user = $merchant->user;
        if (! $user) {
            return response()->json(['message' => 'لم تُعيَّن كلمة مرور لهذا المتجر بعد — تواصل مع الإدارة'], 403);
        }

        $token = $user->createToken('merchant')->plainTextToken;

        return response()->json([
            'token' => $token,
            'merchant' => ['id' => $merchant->id, 'store_name' => $merchant->store_name, 'phone' => $merchant->phone],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();
        return response()->json(['message' => 'تم تسجيل الخروج']);
    }

    // ── strategies ──────────────────────────────────────────────────────────
    protected function byPhonePassword(Request $request): Merchant
    {
        $data = $request->validate(['phone' => ['required', 'string'], 'password' => ['required', 'string']]);
        $merchant = Merchant::where('phone', $data['phone'])->first();
        $user = $merchant?->user;
        abort_unless($user && Hash::check($data['password'], $user->password), 422, 'بيانات الدخول غير صحيحة');
        return $merchant;
    }

    protected function byEmailPassword(Request $request): Merchant
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $user = User::where('email', $data['email'])->first();
        abort_unless($user && Hash::check($data['password'], $user->password), 422, 'بيانات الدخول غير صحيحة');
        $merchant = Merchant::where('user_id', $user->id)->first();
        abort_unless($merchant, 422, 'هذا الحساب غير مرتبط بمتجر');
        return $merchant;
    }

    protected function byPhoneOtp(Request $request): Merchant
    {
        $data = $request->validate(['phone' => ['required', 'string'], 'code' => ['required', 'string']]);
        $otp = Otp::where('phone', $data['phone'])->where('code', $data['code'])
            ->whereNull('used_at')->where('expires_at', '>', now())->latest('id')->first();
        abort_unless($otp, 422, 'الرمز غير صحيح أو منتهٍ');
        $otp->update(['used_at' => now()]);

        $merchant = Merchant::where('phone', $data['phone'])->first();
        abort_unless($merchant, 404, 'لا يوجد متجر بهذا الرقم');
        // phone_otp needs no password, but the token still hangs off the linked user
        if (! $merchant->user) {
            $u = User::create(['name' => $merchant->store_name, 'phone' => $merchant->phone, 'password' => Hash::make(Str::random(24)), 'is_active' => true]);
            $u->assignRole('merchant');
            $merchant->forceFill(['user_id' => $u->id])->save();
            $merchant->refresh();
        }
        return $merchant;
    }
}
