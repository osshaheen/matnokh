<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesResourceQuery;
use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionPlanResource;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubscriptionPlanController extends Controller
{
    use HandlesResourceQuery;

    public function index(Request $request): AnonymousResourceCollection
    {
        $plans = $this->listing(
            SubscriptionPlan::withCount('subscriptions'),
            $request,
            searchable: ['name', 'description'],
            filters: ['is_active' => 'is_active'],
            sortable: ['id', 'name', 'price', 'sort', 'created_at'],
        );

        return SubscriptionPlanResource::collection($plans);
    }

    public function store(Request $request): JsonResponse
    {
        $plan = SubscriptionPlan::create($this->validated($request));

        return (new SubscriptionPlanResource($plan))->response()->setStatusCode(201);
    }

    public function update(Request $request, SubscriptionPlan $plan): JsonResponse
    {
        $plan->update($this->validated($request, $plan));

        return (new SubscriptionPlanResource($plan->fresh()))->response();
    }

    public function destroy(SubscriptionPlan $plan): JsonResponse
    {
        $this->guardDeletion();

        if ($plan->subscriptions()->where('status', 'active')->exists()) {
            $this->fail('id', 'لا يمكن حذف باقة عليها اشتراكات فعّالة');
        }

        $plan->delete();

        return response()->json(['message' => 'تم نقل الباقة إلى سلّة المحذوفات']);
    }

    protected function validated(Request $request, ?SubscriptionPlan $plan = null): array
    {
        $required = $plan ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => [$required, 'numeric', 'min:0'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'orders_limit' => ['nullable', 'integer', 'min:1'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
