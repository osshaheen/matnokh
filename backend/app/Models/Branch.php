<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use SoftDeletes;

    protected $guarded = [];
    protected $attributes = ['is_main' => false, 'is_active' => true];
    protected $casts = ['is_main' => 'boolean', 'is_active' => 'boolean', 'lat' => 'decimal:7', 'lng' => 'decimal:7'];

    public function merchant() { return $this->belongsTo(Merchant::class); }
    public function city() { return $this->belongsTo(City::class); }
}
