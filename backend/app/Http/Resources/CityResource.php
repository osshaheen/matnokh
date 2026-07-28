<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_en' => $this->name_en,
            'delivery_fee' => (float) $this->delivery_fee,
            'is_active' => (bool) $this->is_active,
            'sort' => (int) $this->sort,
            'merchants_count' => $this->whenCounted('merchants'),
            'drivers_count' => $this->whenCounted('drivers'),
            'orders_count' => $this->whenCounted('orders'),
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
        ];
    }
}
