<?php

namespace App\Http\Resources\Merchant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Merchant view of an order: they see items + delivery fee only. Price
// negotiation with the driver/customer is NOT their concern (per the design).
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_no' => $this->order_no,
            'status' => $this->status,
            'customer' => $this->whenLoaded('customer', fn () => $this->customer?->name),
            'recipient_name' => $this->recipient_name,
            'branch_id' => $this->branch_id,
            'branch' => $this->whenLoaded('branch', fn () => $this->branch?->name),
            'items_total' => (float) $this->items_total,
            'delivery_fee' => (float) $this->delivery_fee,
            'payment_method' => $this->payment_method,
            'is_paid' => (bool) $this->is_paid,
            'notes' => $this->notes,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($it) => [
                'name' => $it->name, 'qty' => $it->qty, 'price' => (float) $it->price,
                'addons' => $it->addons ?? [], 'line_total' => (float) $it->line_total,
            ])),
            'created_at' => $this->created_at,
            'accepted_at' => $this->accepted_at,
        ];
    }
}
