<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];

    public function city() { return $this->belongsTo(City::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function orders() { return $this->hasMany(Order::class); }
}
