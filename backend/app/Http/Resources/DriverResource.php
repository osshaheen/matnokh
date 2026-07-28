<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
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
            'vehicle_type' => $this->vehicle_type,
            'vehicle_plate' => $this->vehicle_plate,
            'national_id' => $this->national_id,
            'license_number' => $this->license_number,
            'avatar' => $this->avatar,
            'status' => $this->status,
            'is_available' => (bool) $this->is_available,
            'balance' => (float) $this->balance,
            'rating' => (float) $this->rating,
            'notes' => $this->notes,
            'orders_count' => $this->whenCounted('orders'),
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
        ];
    }
}
