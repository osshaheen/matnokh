<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_name' => $this->store_name,
            'owner_name' => $this->owner_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'city_id' => $this->city_id,
            'city' => $this->whenLoaded('city', fn () => ['id' => $this->city?->id, 'name' => $this->city?->name]),
            'store_category_id' => $this->store_category_id,
            'category' => $this->whenLoaded('category', fn () => ['id' => $this->category?->id, 'name' => $this->category?->name]),
            'address' => $this->address,
            'lat' => $this->lat !== null ? (float) $this->lat : null,
            'lng' => $this->lng !== null ? (float) $this->lng : null,
            'logo' => $this->logo,
            'balance' => (float) $this->balance,
            'status' => $this->status,
            'is_active' => (bool) $this->is_active,
            'notes' => $this->notes,
            'orders_count' => $this->whenCounted('orders'),
            'subscription' => $this->whenLoaded('activeSubscription', fn () => $this->activeSubscription ? [
                'id' => $this->activeSubscription->id,
                'plan' => $this->activeSubscription->plan?->name,
                'ends_at' => $this->activeSubscription->ends_at?->toDateString(),
                'days_left' => $this->activeSubscription->days_left,
            ] : null),
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
        ];
    }
}
