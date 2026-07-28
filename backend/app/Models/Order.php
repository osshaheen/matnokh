<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes, Auditable;

    /** Lifecycle states, in the order the dashboard timeline renders them. */
    public const STATUSES = ['pending', 'accepted', 'picked_up', 'on_the_way', 'delivered', 'canceled', 'returned'];

    /** States that count as finished — no further transitions offered. */
    public const CLOSED = ['delivered', 'canceled', 'returned'];

    protected $guarded = [];
    protected $casts = [
        'is_paid' => 'boolean',
        'items_total' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'commission' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'scheduled_at' => 'datetime',
        'accepted_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->order_no ??= static::nextOrderNo();
            $order->total = static::computeTotal($order);
        });

        static::updating(function (Order $order) {
            if ($order->isDirty(['items_total', 'delivery_fee', 'discount'])) {
                $order->total = static::computeTotal($order);
            }
        });
    }

    /** Derived from max(id) rather than count() so purging rows can't recycle a number. */
    public static function nextOrderNo(): string
    {
        $next = (int) static::withTrashed()->max('id') + 1;

        return 'WS-'.now()->format('ymd').'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    protected static function computeTotal(Order $order): float
    {
        return round((float) $order->items_total + (float) $order->delivery_fee - (float) $order->discount, 2);
    }

    public function customer() { return $this->belongsTo(Customer::class); }
    public function merchant() { return $this->belongsTo(Merchant::class); }
    public function driver() { return $this->belongsTo(Driver::class); }
    public function city() { return $this->belongsTo(City::class); }
    public function service() { return $this->belongsTo(Service::class); }

    public function statusLogs()
    {
        return $this->hasMany(OrderStatusLog::class)->latest('id');
    }

    /** Stamps the matching *_at column when a status is entered. */
    public function markStatus(string $status): void
    {
        $this->status = $status;
        match ($status) {
            'accepted' => $this->accepted_at = now(),
            'picked_up' => $this->picked_up_at = now(),
            'delivered' => $this->delivered_at = now(),
            'canceled' => $this->canceled_at = now(),
            default => null,
        };
    }
}
