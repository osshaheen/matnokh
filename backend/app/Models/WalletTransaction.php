<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    protected $guarded = [];
    protected $casts = ['amount' => 'decimal:2'];

    public function merchant() { return $this->belongsTo(Merchant::class); }
    public function order() { return $this->belongsTo(Order::class); }
}
