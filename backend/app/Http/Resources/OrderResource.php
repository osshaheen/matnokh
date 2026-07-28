<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_no' => $this->order_no,
            'status' => $this->status,
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? [
                'id' => $this->customer->id, 'name' => $this->customer->name, 'phone' => $this->customer->phone,
            ] : null),
            'merchant_id' => $this->merchant_id,
            'merchant' => $this->whenLoaded('merchant', fn () => $this->merchant ? [
                'id' => $this->merchant->id, 'name' => $this->merchant->store_name, 'phone' => $this->merchant->phone,
            ] : null),
            'driver_id' => $this->driver_id,
            'driver' => $this->whenLoaded('driver', fn () => $this->driver ? [
                'id' => $this->driver->id, 'name' => $this->driver->name, 'phone' => $this->driver->phone,
            ] : null),
            'city_id' => $this->city_id,
            'city' => $this->whenLoaded('city', fn () => $this->city?->name),
            'service_id' => $this->service_id,
            'service' => $this->whenLoaded('service', fn () => $this->service?->name),
            'pickup_address' => $this->pickup_address,
            'drop_address' => $this->drop_address,
            'recipient_name' => $this->recipient_name,
            'recipient_phone' => $this->recipient_phone,
            'items_total' => (float) $this->items_total,
            'delivery_fee' => (float) $this->delivery_fee,
            'commission' => (float) $this->commission,
            'discount' => (float) $this->discount,
            'total' => (float) $this->total,
            'payment_method' => $this->payment_method,
            'is_paid' => (bool) $this->is_paid,
            'notes' => $this->notes,
            'cancel_reason' => $this->cancel_reason,
            'scheduled_at' => $this->scheduled_at,
            'accepted_at' => $this->accepted_at,
            'picked_up_at' => $this->picked_up_at,
            'delivered_at' => $this->delivered_at,
            'canceled_at' => $this->canceled_at,
            'timeline' => $this->whenLoaded('statusLogs', fn () => $this->statusLogs->map(fn ($log) => [
                'id' => $log->id,
                'status' => $log->status,
                'note' => $log->note,
                'by' => $log->user?->name,
                'at' => $log->created_at,
            ])->values()),
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
        ];
    }
}
