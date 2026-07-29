<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $guarded = [];
    protected $attributes = ['status' => 'active', 'sort' => 0, 'price' => 0];
    protected $casts = ['price' => 'decimal:2', 'price_before' => 'decimal:2'];

    public function merchant() { return $this->belongsTo(Merchant::class); }
    public function section() { return $this->belongsTo(StoreSection::class, 'store_section_id'); }
    public function images() { return $this->hasMany(ProductImage::class)->orderBy('sort'); }
    public function addons() { return $this->hasMany(ProductAddon::class); }
    public function stock() { return $this->hasMany(ProductStock::class); }

    /** discount % when the product is on offer, else null. */
    public function getDiscountAttribute(): ?int
    {
        if (! $this->price_before || $this->price_before <= $this->price) return null;
        return (int) round((1 - $this->price / $this->price_before) * 100);
    }
}
