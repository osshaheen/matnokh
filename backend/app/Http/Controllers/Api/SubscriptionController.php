<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesResourceQuery;
use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    use HandlesResourceQuery;

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Subscription::with(['merchant', 'plan']);

        // `expiring=7` → active subscriptions ending within the next N days
        if ($days = (int) $request->query('expiring', 0)) {
            $query->where('status', 'active')
                ->whereBetween('ends_at', [today(), today()->addDays($days)]);
        }

        $subscriptions = $this->listing(
            $query,
            $request,
            searchable: ['merchant.store_name', 'merchant.phone'],
            filters: [
                'status' => 'status',
                'merchant_id' => 'merchant_id',
                'subscription_plan_id' => 'subscription_plan_id',
            ],
            sortable: ['id', 'price', 'starts_at', 'ends_at', 'status', 'created_at'],
        );

        return SubscriptionResource::collection($subscriptions);
    }

    public function show(Subscription $subscription): JsonResponse
    {
        return (new SubscriptionResource($subscription->load(['merchant', 'plan'])))->response();
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'merchant_id' => ['required', 'exists:merchants,id'],
            'subscription_plan_id' => ['required', 'exists:subscription_plans,id'],
            'starts_at' => ['nullable', 'date'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
        ]);

        $plan = SubscriptionPlan::findOrFail($data['subscription_plan_id']);
        $starts = Carbon::parse($data['starts_at'] ?? today());

        // a merchant runs one active subscription at a time — the new one supersedes it
        Subscription::where('merchant_id', $data['merchant_id'])
            ->where('status', 'active')->update(['status' => 'canceled']);

        $subscription = Subscription::create([
            'merchant_id' => $data['merchant_id'],
            'subscription_plan_id' => $plan->id,
            'price' => $data['price'] ?? $plan->price,
            'starts_at' => $starts->toDateString(),
            'ends_at' => $starts->copy()->addDays($plan->duration_days)->toDateString(),
            'status' => 'active',
            'note' => $data['note'] ?? null,
        ]);

        return (new SubscriptionResource($subscription->load(['merchant', 'plan'])))->response()->setStatusCode(201);
    }

    public function update(Request $request, Subscription $subscription): JsonResponse
    {
        $data = $request->validate([
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date', 'after_or_equal:starts_at'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', Rule::in(['active', 'expired', 'canceled'])],
            'note' => ['nullable', 'string'],
        ]);

        $subscription->update($data);

        return (new SubscriptionResource($subscription->fresh()->load(['merchant', 'plan'])))->response();
    }

    /** POST /api/subscriptions/{subscription}/renew — extend from today or from the current end date. */
    public function renew(Request $request, Subscription $subscription): JsonResponse
    {
        $plan = $subscription->plan;
        if (! $plan) {
            $this->fail('subscription_plan_id', 'لا يمكن التجديد: الباقة المرتبطة محذوفة');
        }

        $base = $subscription->ends_at?->isFuture() ? $subscription->ends_at->copy() : today();

        $renewed = Subscription::create([
            'merchant_id' => $subscription->merchant_id,
            'subscription_plan_id' => $plan->id,
            'price' => $plan->price,
            'starts_at' => $base->toDateString(),
            'ends_at' => $base->copy()->addDays($plan->duration_days)->toDateString(),
            'status' => 'active',
            'note' => 'تجديد الاشتراك #'.$subscription->id,
        ]);

        $subscription->update(['status' => 'expired']);

        return (new SubscriptionResource($renewed->load(['merchant', 'plan'])))->response()->setStatusCode(201);
    }

    public function destroy(Subscription $subscription): JsonResponse
    {
        $this->guardDeletion();
        $subscription->delete();

        return response()->json(['message' => 'تم نقل الاشتراك إلى سلّة المحذوفات']);
    }
}
