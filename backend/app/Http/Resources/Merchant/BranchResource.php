<?php

namespace App\Http\Resources\Merchant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'city_id' => $this->city_id,
            'city' => $this->whenLoaded('city', fn () => $this->city?->name),
            'phone' => $this->phone,
            'hours' => $this->hours,
            'lat' => $this->lat !== null ? (float) $this->lat : null,
            'lng' => $this->lng !== null ? (float) $this->lng : null,
            'is_main' => (bool) $this->is_main,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
