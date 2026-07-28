<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoreCategory extends Model
{
    use SoftDeletes;

    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];

    public function merchants() { return $this->hasMany(Merchant::class); }
}
