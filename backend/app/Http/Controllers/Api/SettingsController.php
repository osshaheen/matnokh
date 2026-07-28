<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /** GET /api/settings — every known setting, defaults filled in. */
    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->payload()]);
    }

    /** PUT /api/settings — partial update; unknown keys are ignored. */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'app_name' => ['sometimes', 'string', 'max:100'],
            'support_phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'support_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'currency' => ['sometimes', 'string', 'max:10'],
            'default_delivery_fee' => ['sometimes', 'numeric', 'min:0'],
            'default_commission_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'min_withdraw_amount' => ['sometimes', 'numeric', 'min:0'],
            'auto_assign_driver' => ['sometimes', 'boolean'],
            'orders_enabled' => ['sometimes', 'boolean'],
            'deletion_enabled' => ['sometimes', 'boolean'],
            'trash_enabled' => ['sometimes', 'boolean'],
            'restore_enabled' => ['sometimes', 'boolean'],
            'maintenance_mode' => ['sometimes', 'boolean'],
        ]);

        foreach ($data as $key => $value) {
            Setting::put($key, in_array($key, Setting::BOOLEANS, true) ? (bool) $value : $value);
        }

        return response()->json(['data' => $this->payload(), 'message' => 'تم حفظ الإعدادات']);
    }

    /** Booleans survive the JSON round-trip as real booleans for the frontend. */
    protected function payload(): array
    {
        $values = Setting::values();

        foreach (Setting::BOOLEANS as $key) {
            $values[$key] = (bool) ($values[$key] ?? false);
        }

        return $values;
    }
}
