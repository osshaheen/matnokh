<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'merchant_id' => $this->merchant_id,
            'merchant' => $this->whenLoaded('merchant', fn () => $this->merchant ? [
                'id' => $this->merchant->id, 'name' => $this->merchant->store_name, 'phone' => $this->merchant->phone,
            ] : null),
            'subscription_plan_id' => $this->subscription_plan_id,
            'plan' => $this->whenLoaded('plan', fn () => $this->plan ? [
                'id' => $this->plan->id, 'name' => $this->plan->name,
            ] : null),
            'price' => (float) $this->price,
            'starts_at' => $this->starts_at?->toDateString(),
            'ends_at' => $this->ends_at?->toDateString(),
            'days_left' => $this->days_left,
            'status' => $this->status,
            'note' => $this->note,
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
        ];
    }
}
