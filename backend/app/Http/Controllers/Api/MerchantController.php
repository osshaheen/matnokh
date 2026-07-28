<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesResourceQuery;
use App\Http\Controllers\Controller;
use App\Http\Resources\MerchantResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\Merchant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class MerchantController extends Controller
{
    use HandlesResourceQuery;

    public const STATUSES = ['pending', 'approved', 'rejected', 'suspended'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $merchants = $this->listing(
            Merchant::with(['city', 'category', 'activeSubscription.plan'])->withCount('orders'),
            $request,
            searchable: ['store_name', 'owner_name', 'phone', 'email'],
            filters: [
                'status' => 'status',
                'city_id' => 'city_id',
                'store_category_id' => 'store_category_id',
                'is_active' => 'is_active',
            ],
            sortable: ['id', 'store_name', 'balance', 'created_at'],
        );

        return MerchantResource::collection($merchants);
    }

    public function show(Merchant $merchant): JsonResponse
    {
        $merchant->load(['city', 'category', 'activeSubscription.plan'])->loadCount('orders');

        return response()->json([
            'data' => new MerchantResource($merchant),
            'recent_orders' => OrderResource::collection(
                $merchant->orders()->with(['customer', 'driver'])->latest('id')->limit(10)->get()
            ),
            'subscriptions' => SubscriptionResource::collection(
                $merchant->subscriptions()->with('plan')->latest('id')->limit(10)->get()
            ),
            'stats' => [
                'delivered' => $merchant->orders()->where('status', 'delivered')->count(),
                'canceled' => $merchant->orders()->where('status', 'canceled')->count(),
                'revenue' => round((float) $merchant->orders()->where('status', 'delivered')->sum('total'), 2),
                'commission' => round((float) $merchant->orders()->where('status', 'delivered')->sum('commission'), 2),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $merchant = Merchant::create($this->validated($request));

        return (new MerchantResource($merchant->load(['city', 'category'])))->response()->setStatusCode(201);
    }

    public function update(Request $request, Merchant $merchant): JsonResponse
    {
        $merchant->update($this->validated($request, $merchant));

        return (new MerchantResource($merchant->fresh()->load(['city', 'category'])))->response();
    }

    /** PATCH /api/merchants/{merchant}/status */
    public function updateStatus(Request $request, Merchant $merchant): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
            'notes' => ['nullable', 'string'],
        ]);

        $merchant->fill($data);
        $merchant->is_active = $data['status'] === 'approved';
        $merchant->save();

        return (new MerchantResource($merchant->load(['city', 'category'])))->response();
    }

    public function destroy(Merchant $merchant): JsonResponse
    {
        $this->guardDeletion();

        if ($merchant->orders()->whereNotIn('status', ['delivered', 'canceled', 'returned'])->exists()) {
            $this->fail('id', 'لا يمكن حذف تاجر لديه طلبات جارية');
        }

        $merchant->delete();

        return response()->json(['message' => 'تم نقل التاجر إلى سلّة المحذوفات']);
    }

    protected function validated(Request $request, ?Merchant $merchant = null): array
    {
        $required = $merchant ? 'sometimes' : 'required';

        return $request->validate([
            'store_name' => [$required, 'string', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'phone' => [$required, 'string', 'max:30', Rule::unique('merchants', 'phone')->ignore($merchant?->id)->whereNull('deleted_at')],
            'email' => ['nullable', 'email', 'max:255'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'store_category_id' => ['nullable', 'exists:store_categories,id'],
            'address' => ['nullable', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'logo' => ['nullable', 'string', 'max:255'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
