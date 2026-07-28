<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesResourceQuery;
use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    use HandlesResourceQuery;

    public function index(Request $request): AnonymousResourceCollection
    {
        $customers = $this->listing(
            Customer::with('city')->withCount('orders'),
            $request,
            searchable: ['name', 'phone', 'email', 'address'],
            filters: ['city_id' => 'city_id', 'is_active' => 'is_active'],
            sortable: ['id', 'name', 'created_at'],
        );

        return CustomerResource::collection($customers);
    }

    public function show(Customer $customer): JsonResponse
    {
        $customer->load('city')->loadCount('orders');

        return response()->json([
            'data' => new CustomerResource($customer),
            'recent_orders' => OrderResource::collection(
                $customer->orders()->with(['merchant', 'driver'])->latest('id')->limit(10)->get()
            ),
            'stats' => [
                'delivered' => $customer->orders()->where('status', 'delivered')->count(),
                'canceled' => $customer->orders()->where('status', 'canceled')->count(),
                'spent' => round((float) $customer->orders()->where('status', 'delivered')->sum('total'), 2),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $customer = Customer::create($this->validated($request));

        return (new CustomerResource($customer->load('city')))->response()->setStatusCode(201);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $customer->update($this->validated($request, $customer));

        return (new CustomerResource($customer->fresh()->load('city')))->response();
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $this->guardDeletion();
        $customer->delete();

        return response()->json(['message' => 'تم نقل الزبون إلى سلّة المحذوفات']);
    }

    protected function validated(Request $request, ?Customer $customer = null): array
    {
        $required = $customer ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:255'],
            'phone' => [$required, 'string', 'max:30', Rule::unique('customers', 'phone')->ignore($customer?->id)->whereNull('deleted_at')],
            'email' => ['nullable', 'email', 'max:255'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
