<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Resources\Merchant\SectionResource;
use App\Models\StoreSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SectionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $rows = $request->attributes->get('merchant')->sections()->withCount('products')->orderBy('sort')->get();
        return SectionResource::collection($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $m = $request->attributes->get('merchant');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]);
        if ($m->sections()->where('name', $data['name'])->exists()) {
            return response()->json(['message' => 'هذا القسم موجود مسبقاً'], 422);
        }
        $section = $m->sections()->create($data);

        return (new SectionResource($section))->additional(['message' => 'تمت إضافة القسم'])->response()->setStatusCode(201);
    }

    public function update(Request $request, StoreSection $section): JsonResponse
    {
        $this->guard($request, $section);
        $section->update($request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]));

        return (new SectionResource($section->fresh()))->additional(['message' => 'تم التعديل'])->response();
    }

    public function destroy(Request $request, StoreSection $section): JsonResponse
    {
        $this->guard($request, $section);
        $section->delete();

        return response()->json(['message' => 'تم حذف القسم']);
    }

    protected function guard(Request $request, StoreSection $section): void
    {
        abort_unless($section->merchant_id === $request->attributes->get('merchant')->id, 404, 'القسم غير موجود');
    }
}
