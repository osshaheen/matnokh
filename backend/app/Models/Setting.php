<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $guarded = [];
    public $timestamps = true;

    /** Every setting the dashboard knows about, with its shipped default. */
    public const DEFAULTS = [
        // general
        'app_name' => 'وصلها',
        'support_phone' => '',
        'support_email' => '',
        'currency' => 'ILS',
        // operations
        'default_delivery_fee' => 15,
        'min_withdraw_amount' => 100,
        'auto_assign_driver' => false,
        'orders_enabled' => true,
        // merchant/partner login method the admin selects: phone_password | email_password | phone_otp
        'merchant_login_method' => 'phone_password',
        // safety switches consumed by the frontend `can()` helper
        'deletion_enabled' => true,
        'trash_enabled' => true,
        'restore_enabled' => true,
        'maintenance_mode' => false,
    ];

    /** Values that stay booleans through the JSON round-trip. */
    public const BOOLEANS = [
        'auto_assign_driver', 'orders_enabled', 'deletion_enabled',
        'trash_enabled', 'restore_enabled', 'maintenance_mode',
    ];

    protected const CACHE_KEY = 'wassilha.settings';

    /** Stored values merged over the defaults. */
    public static function values(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $stored = static::query()->pluck('value', 'key')
                ->map(fn ($v) => json_decode((string) $v, true))
                ->all();

            return array_merge(self::DEFAULTS, $stored);
        });
    }

    public static function get(string $key, mixed $fallback = null): mixed
    {
        return self::values()[$key] ?? $fallback ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function put(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => json_encode($value)]);
        Cache::forget(self::CACHE_KEY);
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
