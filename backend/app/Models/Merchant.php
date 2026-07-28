<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Merchant extends Model
{
    use SoftDeletes;

    protected $guarded = [];
    protected $casts = [
        'is_active' => 'boolean',
        'commission_rate' => 'decimal:2',
        'balance' => 'decimal:2',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    public function city() { return $this->belongsTo(City::class); }
    public function category() { return $this->belongsTo(StoreCategory::class, 'store_category_id'); }
    public function user() { return $this->belongsTo(User::class); }
    public function orders() { return $this->hasMany(Order::class); }
    public function subscriptions() { return $this->hasMany(Subscription::class); }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')->latestOfMany();
    }

    public function withdraws(): MorphMany
    {
        return $this->morphMany(Withdraw::class, 'requester');
    }
}
