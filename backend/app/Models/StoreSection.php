<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoreSection extends Model
{
    use SoftDeletes;

    protected $guarded = [];
    protected $attributes = ['sort' => 0];

    public function merchant() { return $this->belongsTo(Merchant::class); }
    public function products() { return $this->hasMany(Product::class); }
}
