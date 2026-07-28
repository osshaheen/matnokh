<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PushNotification extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    /** Mirrors the column defaults so `create()` doesn't hand back nulls. */
    protected $attributes = [
        'status' => 'draft',
        'audience' => 'all',
        'sent_count' => 0,
    ];
    protected $casts = [
        'target_ids' => 'array',
        'sent_at' => 'datetime',
    ];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }

    /** How many accounts the chosen audience currently resolves to. */
    public function audienceSize(): int
    {
        if (is_array($this->target_ids) && $this->target_ids !== []) {
            return count($this->target_ids);
        }

        return match ($this->audience) {
            'customers' => Customer::where('is_active', true)->count(),
            'drivers' => Driver::where('status', 'approved')->count(),
            'merchants' => Merchant::where('is_active', true)->count(),
            default => Customer::where('is_active', true)->count()
                + Driver::where('status', 'approved')->count()
                + Merchant::where('is_active', true)->count(),
        };
    }
}
