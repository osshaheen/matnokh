<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'duration_days' => (int) $this->duration_days,
            'orders_limit' => $this->orders_limit,
            'features' => $this->features ?? [],
            'is_active' => (bool) $this->is_active,
            'sort' => (int) $this->sort,
            'subscriptions_count' => $this->whenCounted('subscriptions'),
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
        ];
    }
}
