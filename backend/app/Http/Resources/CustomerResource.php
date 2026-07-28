<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'city_id' => $this->city_id,
            'city' => $this->whenLoaded('city', fn () => ['id' => $this->city?->id, 'name' => $this->city?->name]),
            'address' => $this->address,
            'is_active' => (bool) $this->is_active,
            'notes' => $this->notes,
            'orders_count' => $this->whenCounted('orders'),
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
        ];
    }
}
