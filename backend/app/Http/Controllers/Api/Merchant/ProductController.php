<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Api\Concerns\HandlesResourceQuery;
use App\Http\Controllers\Controller;
use App\Http\Resources\Merchant\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    use HandlesResourceQuery;

    public function index(Request $request): AnonymousResourceCollection
    {
        $m = $request->attributes->get('merchant');
        $query = Product::where('merchant_id', $m->id)->with(['section', 'images', 'addons', 'stock']);

        $rows = $this->listing(
            $query, $request,
            searchable: ['name', 'description'],
            filters: ['status' => 'status', 'store_section_id' => 'store_section_id'],
            sortable: ['id', 'name', 'price', 'sort', 'created_at'],
        );

        return ProductResource::collection($rows);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        $this->guard($request, $product);
        return (new ProductResource($product->load(['section', 'images', 'addons', 'stock'])))->response();
    }

    public function store(Request $request): JsonResponse
    {
        $m = $request->attributes->get('merchant');
        $data = $this->validated($request);

        $product = DB::transaction(function () use ($m, $data) {
            $p = $m->products()->create(collect($data)->except(['images', 'addons'])->all());
            $this->syncImages($p, $data['images'] ?? []);
            $this->syncAddons($p, $data['addons'] ?? []);
            $this->ensureStockRows($m, $p);
            return $p;
        });

        return (new ProductResource($product->load(['section', 'images', 'addons', 'stock'])))
            ->additional(['message' => 'تم حفظ المنتج'])->response()->setStatusCode(201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $this->guard($request, $product);
        $data = $this->validated($request, true);

        DB::transaction(function () use ($product, $data) {
            $product->update(collect($data)->except(['images', 'addons'])->all());
            if (array_key_exists('images', $data)) $this->syncImages($product, $data['images']);
            if (array_key_exists('addons', $data)) $this->syncAddons($product, $data['addons']);
        });

        return (new ProductResource($product->fresh()->load(['section', 'images', 'addons', 'stock'])))
            ->additional(['message' => 'تم تعديل المنتج'])->response();
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->guard($request, $product);
        $product->delete();
        return response()->json(['message' => 'تم حذف المنتج']);
    }

    /** PATCH /api/merchant/products/{product}/stock — mark in/out of stock per branch. */
    public function setStock(Request $request, Product $product): JsonResponse
    {
        $this->guard($request, $product);
        $data = $request->validate([
            'branch_id' => ['required', 'integer'],
            'in_stock' => ['required', 'boolean'],
        ]);
        abort_unless($request->attributes->get('merchant')->branches()->whereKey($data['branch_id'])->exists(), 404, 'الفرع غير موجود');

        $product->stock()->updateOrCreate(['branch_id' => $data['branch_id']], ['in_stock' => $data['in_stock']]);

        return response()->json(['message' => $data['in_stock'] ? 'المنتج متوفر في هذا الفرع' : 'تم تعيين نفاد الكمية في هذا الفرع']);
    }

    // ── helpers ──────────────────────────────────────────────────────────────
    protected function validated(Request $request, bool $partial = false): array
    {
        $req = $partial ? 'sometimes' : 'required';
        return $request->validate([
            'name' => [$req, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'store_section_id' => ['nullable', Rule::exists('store_sections', 'id')->where('merchant_id', $request->attributes->get('merchant')->id)],
            'price' => [$req, 'numeric', 'min:0'],
            'price_before' => ['nullable', 'numeric', 'min:0'],   // offer
            'status' => ['nullable', Rule::in(['active', 'draft', 'archived'])],
            'sort' => ['nullable', 'integer', 'min:0'],
            'images' => ['sometimes', 'array', 'min:1', 'max:6'],       // at least one image
            'images.*' => ['string', 'max:500'],
            'addons' => ['sometimes', 'array', 'max:3'],               // up to 3 addons
            'addons.*.name' => ['required_with:addons', 'string', 'max:255'],
            'addons.*.price' => ['required_with:addons', 'numeric', 'min:0'],
        ]);
    }

    protected function syncImages(Product $p, array $urls): void
    {
        $p->images()->delete();
        foreach (array_values($urls) as $i => $url) {
            $p->images()->create(['url' => $url, 'sort' => $i]);
        }
    }

    protected function syncAddons(Product $p, array $addons): void
    {
        $p->addons()->delete();
        foreach (array_slice($addons, 0, 3) as $a) {
            $p->addons()->create(['name' => $a['name'], 'price' => $a['price']]);
        }
    }

    protected function ensureStockRows($m, Product $p): void
    {
        foreach ($m->branches()->pluck('id') as $bid) {
            $p->stock()->firstOrCreate(['branch_id' => $bid], ['in_stock' => true]);
        }
    }

    protected function guard(Request $request, Product $product): void
    {
        abort_unless($product->merchant_id === $request->attributes->get('merchant')->id, 404, 'المنتج غير موجود');
    }
}
