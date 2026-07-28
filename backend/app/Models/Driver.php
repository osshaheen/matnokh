<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use SoftDeletes;

    protected $guarded = [];
    protected $casts = [
        'is_available' => 'boolean',
        'balance' => 'decimal:2',
        'rating' => 'decimal:2',
    ];

    public function city() { return $this->belongsTo(City::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function orders() { return $this->hasMany(Order::class); }

    public function withdraws(): MorphMany
    {
        return $this->morphMany(Withdraw::class, 'requester');
    }
}
