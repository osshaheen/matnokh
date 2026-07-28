<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoreCategory extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    /** Mirrors the column defaults so `create()` doesn't hand back nulls. */
    protected $attributes = [
        'is_active' => true,
        'sort' => 0,
    ];
    protected $casts = ['is_active' => 'boolean'];

    public function merchants() { return $this->hasMany(Merchant::class); }
}
