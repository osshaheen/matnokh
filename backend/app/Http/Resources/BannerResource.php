<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'image' => $this->image,
            'link' => $this->link,
            'position' => $this->position,
            'audience' => $this->audience,
            'is_active' => (bool) $this->is_active,
            'sort' => (int) $this->sort,
            'starts_at' => $this->starts_at?->toDateString(),
            'ends_at' => $this->ends_at?->toDateString(),
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
        ];
    }
}
