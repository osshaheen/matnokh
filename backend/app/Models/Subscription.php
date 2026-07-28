<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use SoftDeletes, Auditable;

    protected $guarded = [];

    /** Mirrors the column defaults so `create()` doesn't hand back nulls. */
    protected $attributes = [
        'status' => 'active',
        'price' => 0,
    ];
    protected $casts = [
        'price' => 'decimal:2',
        'starts_at' => 'date',
        'ends_at' => 'date',
    ];

    public function merchant() { return $this->belongsTo(Merchant::class); }
    public function plan() { return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id'); }

    /** Days left before expiry — negative once expired. */
    public function getDaysLeftAttribute(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->ends_at, false);
    }
}
