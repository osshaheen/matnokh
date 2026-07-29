<?php

namespace App\Http\Resources\Merchant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,          // funding | withdrawal
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'method' => $this->method,
            'order_no' => $this->whenLoaded('order', fn () => $this->order?->order_no),
            'note' => $this->note,
            'created_at' => $this->created_at,
        ];
    }
}
