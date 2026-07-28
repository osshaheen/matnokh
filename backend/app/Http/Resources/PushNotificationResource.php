<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PushNotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'audience' => $this->audience,
            'target_ids' => $this->target_ids ?? [],
            'status' => $this->status,
            'sent_at' => $this->sent_at,
            'sent_count' => (int) $this->sent_count,
            'created_by' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
        ];
    }
}
