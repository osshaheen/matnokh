<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    /** GET /api/merchant/store — profile + status + settings + subscription. */
    public function show(Request $request): JsonResponse
    {
        $m = $request->attributes->get('merchant')->loadCount(['branches', 'products'])
            ->load(['city', 'category', 'activeSubscription.plan']);
        $sub = $m->activeSubscription;

        return response()->json(['data' => [
            'id' => $m->id,
            'store_name' => $m->store_name,
            'owner_name' => $m->owner_name,
            'phone' => $m->phone,
            'email' => $m->email,
            'logo' => $m->logo,
            'address' => $m->address,
            'city' => $m->city?->name,
            'city_id' => $m->city_id,
            'category' => $m->category?->name,
            'store_category_id' => $m->store_category_id,
            'rating' => (float) $m->rating,
            'balance' => (float) $m->balance,
            'branches_count' => $m->branches_count,
            'products_count' => $m->products_count,
            'is_open' => (bool) $m->is_open,
            'prep_mode' => (bool) $m->prep_mode,
            'auto_accept' => (bool) $m->auto_accept,
            'subscription' => $sub ? [
                'plan' => $sub->plan?->name,
                'ends_at' => $sub->ends_at,
                'status' => $sub->status,
            ] : null,
        ]]);
    }

    /** PUT /api/merchant/store — update profile + operating switches. */
    public function update(Request $request): JsonResponse
    {
        $m = $request->attributes->get('merchant');
        $data = $request->validate([
            'store_name' => ['sometimes', 'string', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'logo' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'store_category_id' => ['nullable', 'exists:store_categories,id'],
            'is_open' => ['sometimes', 'boolean'],
            'prep_mode' => ['sometimes', 'boolean'],
            'auto_accept' => ['sometimes', 'boolean'],
        ]);
        $m->update($data);

        return $this->show($request);
    }
}
