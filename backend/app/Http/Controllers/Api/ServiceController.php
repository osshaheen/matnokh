<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesResourceQuery;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceController extends Controller
{
    use HandlesResourceQuery;

    public function index(Request $request): AnonymousResourceCollection
    {
        $services = $this->listing(
            Service::withCount('orders'),
            $request,
            searchable: ['name', 'description'],
            filters: ['is_active' => 'is_active'],
            sortable: ['id', 'name', 'sort', 'created_at'],
        );

        return ServiceResource::collection($services);
    }

    public function store(Request $request): JsonResponse
    {
        $service = Service::create($this->validated($request));

        return (new ServiceResource($service))->response()->setStatusCode(201);
    }

    public function update(Request $request, Service $service): JsonResponse
    {
        $service->update($this->validated($request, $service));

        return (new ServiceResource($service->fresh()))->response();
    }

    public function destroy(Service $service): JsonResponse
    {
        $this->guardDeletion();

        if ($service->orders()->exists()) {
            $this->fail('id', 'لا يمكن حذف خدمة مرتبطة بطلبات');
        }

        $service->delete();

        return response()->json(['message' => 'تم نقل الخدمة إلى سلّة المحذوفات']);
    }

    protected function validated(Request $request, ?Service $service = null): array
    {
        $required = $service ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'point_type' => ['nullable', 'in:pickup_dropoff,pickup_only'],
            'is_active' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
