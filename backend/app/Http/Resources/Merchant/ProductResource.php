<?php

namespace App\Http\Resources\Merchant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'store_section_id' => $this->store_section_id,
            'section' => $this->whenLoaded('section', fn () => $this->section?->name),
            'price' => (float) $this->price,
            'price_before' => $this->price_before !== null ? (float) $this->price_before : null,
            'discount' => $this->discount,   // % or null
            'status' => $this->status,
            'sort' => (int) $this->sort,
            'images' => $this->whenLoaded('images', fn () => $this->images->pluck('url')),
            'addons' => $this->whenLoaded('addons', fn () => $this->addons->map(fn ($a) => ['id' => $a->id, 'name' => $a->name, 'price' => (float) $a->price])),
            'stock' => $this->whenLoaded('stock', fn () => $this->stock->map(fn ($s) => ['branch_id' => $s->branch_id, 'in_stock' => (bool) $s->in_stock])),
            'created_at' => $this->created_at,
        ];
    }
}
