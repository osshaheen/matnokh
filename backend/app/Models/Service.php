<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes;

    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean', 'base_price' => 'decimal:2'];

    public function orders() { return $this->hasMany(Order::class); }
}
