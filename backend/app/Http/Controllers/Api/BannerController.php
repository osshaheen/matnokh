<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesResourceQuery;
use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class BannerController extends Controller
{
    use HandlesResourceQuery;

    public const POSITIONS = ['home_top', 'home_middle', 'offers'];
    public const AUDIENCES = ['all', 'customers', 'drivers', 'merchants'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $banners = $this->listing(
            Banner::query(),
            $request,
            searchable: ['title', 'link'],
            filters: ['is_active' => 'is_active', 'position' => 'position', 'audience' => 'audience'],
            sortable: ['id', 'title', 'sort', 'created_at'],
        );

        return BannerResource::collection($banners);
    }

    public function store(Request $request): JsonResponse
    {
        $banner = Banner::create($this->validated($request));

        return (new BannerResource($banner))->response()->setStatusCode(201);
    }

    public function update(Request $request, Banner $banner): JsonResponse
    {
        $banner->update($this->validated($request, $banner));

        return (new BannerResource($banner->fresh()))->response();
    }

    public function destroy(Banner $banner): JsonResponse
    {
        $this->guardDeletion();
        $banner->delete();

        return response()->json(['message' => 'تم نقل البانر إلى سلّة المحذوفات']);
    }

    protected function validated(Request $request, ?Banner $banner = null): array
    {
        $required = $banner ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$required, 'string', 'max:255'],
            'image' => ['nullable', 'string', 'max:255'],
            'link' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', Rule::in(self::POSITIONS)],
            'audience' => ['nullable', Rule::in(self::AUDIENCES)],
            'is_active' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);
    }
}
