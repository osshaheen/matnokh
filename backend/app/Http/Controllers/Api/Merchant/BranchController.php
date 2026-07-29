<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Resources\Merchant\BranchResource;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BranchController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $rows = $request->attributes->get('merchant')->branches()->with('city')->orderByDesc('is_main')->get();
        return BranchResource::collection($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $m = $request->attributes->get('merchant');
        $data = $this->validated($request);
        // first branch is the main one automatically
        $data['is_main'] = $m->branches()->count() === 0 ? true : ($data['is_main'] ?? false);
        $branch = $m->branches()->create($data);

        return (new BranchResource($branch->load('city')))->additional(['message' => 'تمت إضافة الفرع'])->response()->setStatusCode(201);
    }

    public function update(Request $request, Branch $branch): JsonResponse
    {
        $this->authorizeBranch($request, $branch);
        $branch->update($this->validated($request, true));

        return (new BranchResource($branch->fresh()->load('city')))->additional(['message' => 'تم تعديل الفرع'])->response();
    }

    public function destroy(Request $request, Branch $branch): JsonResponse
    {
        $this->authorizeBranch($request, $branch);
        if ($branch->is_main && $request->attributes->get('merchant')->branches()->count() > 1) {
            return response()->json(['message' => 'عيّن فرعاً رئيسياً آخر قبل حذف الرئيسي'], 422);
        }
        $branch->delete();

        return response()->json(['message' => 'تم حذف الفرع']);
    }

    protected function validated(Request $request, bool $partial = false): array
    {
        $req = $partial ? 'sometimes' : 'required';
        return $request->validate([
            'name' => [$req, 'string', 'max:255'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'hours' => ['nullable', 'string', 'max:60'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'is_main' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    protected function authorizeBranch(Request $request, Branch $branch): void
    {
        abort_unless($branch->merchant_id === $request->attributes->get('merchant')->id, 404, 'الفرع غير موجود');
    }
}
