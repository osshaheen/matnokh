<?php

namespace App\Http\Resources;

use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'requester_type' => $this->requester_type_key,
            'requester_id' => $this->requester_id,
            'requester' => $this->whenLoaded('requester', fn () => $this->requester ? [
                'id' => $this->requester->id,
                'name' => $this->requester instanceof Driver ? $this->requester->name : $this->requester->store_name,
                'phone' => $this->requester->phone,
                'balance' => (float) $this->requester->balance,
            ] : null),
            'amount' => (float) $this->amount,
            'method' => $this->method,
            'account_name' => $this->account_name,
            'account_number' => $this->account_number,
            'bank_name' => $this->bank_name,
            'status' => $this->status,
            'note' => $this->note,
            'admin_note' => $this->admin_note,
            'processed_by' => $this->whenLoaded('processor', fn () => $this->processor?->name),
            'processed_at' => $this->processed_at,
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
        ];
    }
}
