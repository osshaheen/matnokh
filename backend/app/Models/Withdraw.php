<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Withdraw extends Model
{
    use SoftDeletes, Auditable;

    /** Morph aliases accepted from the API, mapped to their models. */
    public const REQUESTERS = ['driver' => Driver::class, 'merchant' => Merchant::class];

    protected $guarded = [];

    /** Mirrors the column defaults so `create()` doesn't hand back nulls. */
    protected $attributes = [
        'status' => 'pending',
        'method' => 'bank',
    ];
    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function requester() { return $this->morphTo(); }
    public function processor() { return $this->belongsTo(User::class, 'processed_by'); }

    /** 'driver' | 'merchant' — the short alias used by the dashboard. */
    public function getRequesterTypeKeyAttribute(): ?string
    {
        return array_search($this->requester_type, self::REQUESTERS, true) ?: null;
    }
}
