<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesResourceQuery;
use App\Http\Controllers\Controller;
use App\Http\Resources\DriverResource;
use App\Http\Resources\OrderResource;
use App\Models\Driver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class DriverController extends Controller
{
    use HandlesResourceQuery;

    public const VEHICLES = ['motorcycle', 'car', 'bicycle', 'van'];
    public const STATUSES = ['pending', 'approved', 'rejected', 'suspended'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $drivers = $this->listing(
            Driver::with('city')->withCount('orders'),
            $request,
            searchable: ['name', 'phone', 'email', 'vehicle_plate', 'national_id'],
            filters: [
                'status' => 'status',
                'city_id' => 'city_id',
                'vehicle_type' => 'vehicle_type',
                'is_available' => 'is_available',
            ],
            sortable: ['id', 'name', 'balance', 'rating', 'created_at'],
        );

        return DriverResource::collection($drivers);
    }

    public function show(Driver $driver): JsonResponse
    {
        $driver->load('city')->loadCount('orders');

        return response()->json([
            'data' => new DriverResource($driver),
            'recent_orders' => OrderResource::collection(
                $driver->orders()->with(['customer', 'merchant'])->latest('id')->limit(10)->get()
            ),
            'stats' => [
                'delivered' => $driver->orders()->where('status', 'delivered')->count(),
                'canceled' => $driver->orders()->where('status', 'canceled')->count(),
                'active' => $driver->orders()->whereNotIn('status', ['delivered', 'canceled', 'returned'])->count(),
                'earnings' => round((float) $driver->orders()->where('status', 'delivered')->sum('delivery_fee'), 2),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $driver = Driver::create($this->validated($request));

        return (new DriverResource($driver->load('city')))->response()->setStatusCode(201);
    }

    public function update(Request $request, Driver $driver): JsonResponse
    {
        $driver->update($this->validated($request, $driver));

        return (new DriverResource($driver->fresh()->load('city')))->response();
    }

    /** PATCH /api/drivers/{driver}/status */
    public function updateStatus(Request $request, Driver $driver): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
            'notes' => ['nullable', 'string'],
        ]);

        // a driver who is no longer approved must not stay in the dispatch pool
        $driver->fill($data);
        if ($data['status'] !== 'approved') {
            $driver->is_available = false;
        }
        $driver->save();

        return (new DriverResource($driver->load('city')))->response();
    }

    /** PATCH /api/drivers/{driver}/availability */
    public function updateAvailability(Request $request, Driver $driver): JsonResponse
    {
        $data = $request->validate(['is_available' => ['required', 'boolean']]);

        if ($data['is_available'] && $driver->status !== 'approved') {
            $this->fail('is_available', 'لا يمكن إتاحة سائق غير معتمد');
        }

        $driver->update($data);

        return (new DriverResource($driver->load('city')))->response();
    }

    public function destroy(Driver $driver): JsonResponse
    {
        $this->guardDeletion();

        if ($driver->orders()->whereNotIn('status', ['delivered', 'canceled', 'returned'])->exists()) {
            $this->fail('id', 'لا يمكن حذف سائق لديه طلبات جارية');
        }

        $driver->delete();

        return response()->json(['message' => 'تم نقل السائق إلى سلّة المحذوفات']);
    }

    protected function validated(Request $request, ?Driver $driver = null): array
    {
        $required = $driver ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:255'],
            'phone' => [$required, 'string', 'max:30', Rule::unique('drivers', 'phone')->ignore($driver?->id)->whereNull('deleted_at')],
            'email' => ['nullable', 'email', 'max:255'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'vehicle_type' => ['nullable', Rule::in(self::VEHICLES)],
            'vehicle_plate' => ['nullable', 'string', 'max:50'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'license_number' => ['nullable', 'string', 'max:50'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'is_available' => ['nullable', 'boolean'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
