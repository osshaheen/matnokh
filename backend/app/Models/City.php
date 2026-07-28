<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class City extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    /** Mirrors the column defaults so `create()` doesn't hand back nulls. */
    protected $attributes = [
        'is_active' => true,
        'delivery_fee' => 0,
        'sort' => 0,
    ];
    protected $casts = ['is_active' => 'boolean', 'delivery_fee' => 'decimal:2'];

    public function merchants() { return $this->hasMany(Merchant::class); }
    public function drivers() { return $this->hasMany(Driver::class); }
    public function customers() { return $this->hasMany(Customer::class); }
    public function orders() { return $this->hasMany(Order::class); }
}
