<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPlan extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    /** Mirrors the column defaults so `create()` doesn't hand back nulls. */
    protected $attributes = [
        'is_active' => true,
        'price' => 0,
        'duration_days' => 30,
        'sort' => 0,
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'features' => 'array',
    ];

    public function subscriptions() { return $this->hasMany(Subscription::class); }
}
