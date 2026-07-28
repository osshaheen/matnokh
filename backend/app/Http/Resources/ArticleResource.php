<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            // list responses stay light; the body only ships on single-article routes
            'body' => $this->when($request->route('article') !== null || $request->boolean('with_body'), $this->body),
            'cover' => $this->cover,
            'is_published' => (bool) $this->is_published,
            'published_at' => $this->published_at,
            'views' => (int) $this->views,
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
        ];
    }
}
