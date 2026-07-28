<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesResourceQuery;
use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ArticleController extends Controller
{
    use HandlesResourceQuery;

    public function index(Request $request): AnonymousResourceCollection
    {
        $articles = $this->listing(
            Article::query(),
            $request,
            searchable: ['title', 'excerpt'],
            filters: ['is_published' => 'is_published'],
            sortable: ['id', 'title', 'views', 'published_at', 'created_at'],
        );

        return ArticleResource::collection($articles);
    }

    public function show(Article $article): JsonResponse
    {
        return (new ArticleResource($article))->response();
    }

    public function store(Request $request): JsonResponse
    {
        $article = Article::create($this->validated($request));

        return (new ArticleResource($article))->response()->setStatusCode(201);
    }

    public function update(Request $request, Article $article): JsonResponse
    {
        $article->update($this->validated($request, $article));

        return (new ArticleResource($article->fresh()))->response();
    }

    public function destroy(Article $article): JsonResponse
    {
        $this->guardDeletion();
        $article->delete();

        return response()->json(['message' => 'تم نقل المقال إلى سلّة المحذوفات']);
    }

    protected function validated(Request $request, ?Article $article = null): array
    {
        $required = $article ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$required, 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
            'cover' => ['nullable', 'string', 'max:255'],
            'is_published' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ]);
    }
}
