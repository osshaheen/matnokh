<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Article extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    /** Mirrors the column defaults so `create()` doesn't hand back nulls. */
    protected $attributes = [
        'is_published' => false,
        'views' => 0,
    ];
    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Article $article) {
            if (blank($article->slug)) {
                $article->slug = static::uniqueSlug($article->title, $article->id);
            }
            if ($article->is_published && ! $article->published_at) {
                $article->published_at = now();
            }
        });
    }

    /** Arabic titles slugify to an empty string, so fall back to a stable id-based slug. */
    protected static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'article';
        $slug = $base;
        $i = 2;
        while (static::withTrashed()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = "$base-".$i++;
        }

        return $slug;
    }
}
