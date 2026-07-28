<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesResourceQuery;
use App\Http\Controllers\Controller;
use App\Http\Resources\StoreCategoryResource;
use App\Models\StoreCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class StoreCategoryController extends Controller
{
    use HandlesResourceQuery;

    public function index(Request $request): AnonymousResourceCollection
    {
        $categories = $this->listing(
            StoreCategory::withCount('merchants'),
            $request,
            searchable: ['name', 'name_en'],
            filters: ['is_active' => 'is_active'],
            sortable: ['id', 'name', 'sort', 'created_at'],
        );

        return StoreCategoryResource::collection($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $category = StoreCategory::create($this->validated($request));

        return (new StoreCategoryResource($category))->response()->setStatusCode(201);
    }

    public function update(Request $request, StoreCategory $store_category): JsonResponse
    {
        $store_category->update($this->validated($request, $store_category));

        return (new StoreCategoryResource($store_category->fresh()))->response();
    }

    public function destroy(StoreCategory $store_category): JsonResponse
    {
        $this->guardDeletion();

        if ($store_category->merchants()->exists()) {
            $this->fail('id', 'لا يمكن حذف تصنيف مرتبط بتجّار');
        }

        $store_category->delete();

        return response()->json(['message' => 'تم نقل التصنيف إلى سلّة المحذوفات']);
    }

    protected function validated(Request $request, ?StoreCategory $category = null): array
    {
        $required = $category ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:255', Rule::unique('store_categories', 'name')->ignore($category?->id)->whereNull('deleted_at')],
            'name_en' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
