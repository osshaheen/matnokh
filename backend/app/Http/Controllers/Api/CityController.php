<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesResourceQuery;
use App\Http\Controllers\Controller;
use App\Http\Resources\CityResource;
use App\Models\City;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class CityController extends Controller
{
    use HandlesResourceQuery;

    public function index(Request $request): AnonymousResourceCollection
    {
        $cities = $this->listing(
            City::withCount(['merchants', 'drivers', 'orders']),
            $request,
            searchable: ['name', 'name_en'],
            filters: ['is_active' => 'is_active'],
            sortable: ['id', 'name', 'sort', 'delivery_fee', 'created_at'],
        );

        return CityResource::collection($cities);
    }

    public function store(Request $request): JsonResponse
    {
        $city = City::create($this->validated($request));

        return (new CityResource($city))->response()->setStatusCode(201);
    }

    public function update(Request $request, City $city): JsonResponse
    {
        $city->update($this->validated($request, $city));

        return (new CityResource($city->fresh()))->response();
    }

    public function destroy(City $city): JsonResponse
    {
        $this->guardDeletion();

        if ($city->orders()->exists()) {
            $this->fail('id', 'لا يمكن حذف مدينة مرتبطة بطلبات');
        }

        $city->delete();

        return response()->json(['message' => 'تم نقل المدينة إلى سلّة المحذوفات']);
    }

    protected function validated(Request $request, ?City $city = null): array
    {
        $required = $city ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:255', Rule::unique('cities', 'name')->ignore($city?->id)->whereNull('deleted_at')],
            'name_en' => ['nullable', 'string', 'max:255'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
