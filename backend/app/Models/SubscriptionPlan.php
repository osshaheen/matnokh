<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPlan extends Model
{
    use SoftDeletes;

    protected $guarded = [];
    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'features' => 'array',
    ];

    public function subscriptions() { return $this->hasMany(Subscription::class); }
}
